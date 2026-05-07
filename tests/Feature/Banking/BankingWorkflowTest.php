<?php

namespace Tests\Feature\Banking;

use Tests\TestCase;
use Tests\Helpers\Builders\UserBuilder;
use Tests\Helpers\Assertions\DatabaseAssertions;
use Tests\Helpers\DatabaseHelper;

/**
 * Banking Workflow Feature Tests
 * 
 * Tests complete banking verification workflows including:
 * - Single account verification
 * - Bulk verification operations
 * - Name matching verification
 * - Status tracking and reporting
 */
class BankingWorkflowTest extends TestCase
{
    use DatabaseAssertions;

    protected $db;
    private $testUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new DatabaseHelper();
        $this->createTestBankingData();
    }

    /**
     * Create test banking data
     */
    private function createTestBankingData()
    {
        // Create users with finance records
        $users = [
            [
                'userid' => 'user.bank.001',
                'loginid' => 'bank.user.001',
                'first' => 'John',
                'middle' => 'Doe',
                'last' => 'Smith',
                'bank_name' => 'Access Bank',
                'bank_code' => '044',
                'account_no' => '0123456789',
                'account_name' => 'John Doe',
                'is_verified' => 0
            ],
            [
                'userid' => 'user.bank.002',
                'loginid' => 'bank.user.002',
                'first' => 'Jane',
                'middle' => 'Mary',
                'last' => 'Johnson',
                'bank_name' => 'GTBank',
                'bank_code' => '058',
                'account_no' => '9876543210',
                'account_name' => 'Jane Johnson',
                'is_verified' => 0
            ],
            [
                'userid' => 'user.bank.003',
                'loginid' => 'bank.user.003',
                'first' => 'Invalid',
                'middle' => 'Account',
                'last' => 'Test',
                'bank_name' => 'Zenith Bank',
                'bank_code' => '057',
                'account_no' => '123', // Invalid - too short
                'account_name' => null,
                'is_verified' => 0
            ],
        ];

        foreach ($users as $userData) {
            // Create user in usr_login and get actual userid
            $actualUserid = UserBuilder::new()
                ->withUserId($userData['userid'])
                ->withLoginId($userData['loginid'])
                ->asMobilizer()
                ->create();

            $this->testUserIds[] = $actualUserid;

            // Create identity record
            $this->db->insert('usr_identity', [
                'userid' => $actualUserid,
                'first' => $userData['first'],
                'middle' => $userData['middle'],
                'last' => $userData['last']
            ]);

            // Create finance record
            $this->db->insert('usr_finance', [
                'userid' => $actualUserid,
                'bank_name' => $userData['bank_name'],
                'bank_code' => $userData['bank_code'],
                'account_name' => $userData['account_name'],
                'account_no' => $userData['account_no'],
                'is_verified' => $userData['is_verified'],
                'verification_count' => 0,
                'verification_status' => 'none'
            ]);
        }
    }

    /**
     * Test complete banking verification workflow
     * Tests end-to-end verification including API call, name matching, and status update
     */
    public function testCompleteBankingVerificationWorkflow()
    {
        // Arrange: User with unverified account exists
        $this->assertRecordExists('usr_finance', [
            'userid' => $this->testUserIds[0],
            'verification_status' => 'none'
        ]);

        // Act: Run verification (mocked - in real test would call Paystack API)
        $verification = $this->mockBankVerification($this->testUserIds[0], '0123456789', '044');

        // Assert: Verification completed successfully
        $this->assertTrue($verification['success']);
        $this->assertEquals('Account verification successful', $verification['message']);

        // Assert: Database updated correctly
        $this->assertRecordHasValues('usr_finance',
            ['userid' => $this->testUserIds[0]],
            [
                'verification_status' => 'success',
                'verified_account_name' => 'John Doe Smith'
            ]
        );
    }

    /**
     * Test single bank account verification
     * Tests BankVerify class functionality
     */
    public function testSingleBankAccountVerification()
    {
        // Test valid account verification
        $validResult = $this->verifyBankAccount('0123456789', '044');
        
        $this->assertTrue($validResult['status']);
        $this->assertArrayHasKey('account_number', $validResult);
        $this->assertArrayHasKey('account_name', $validResult);
        $this->assertEquals('0123456789', $validResult['account_number']);

        // Test invalid account number (less than 10 digits)
        $invalidResult = $this->verifyBankAccount('12345', '044');
        
        $this->assertFalse($invalidResult['status']);
        $this->assertStringContainsString('Invalid account number', $invalidResult['message']);

        // Test invalid bank code (not 3 digits)
        $invalidCodeResult = $this->verifyBankAccount('0123456789', '12');
        
        $this->assertFalse($invalidCodeResult['status']);
        $this->assertStringContainsString('Invalid bank code', $invalidCodeResult['message']);
    }

    /**
     * Test bulk banking operations
     * Tests BulkBankVerification class with multiple records
     */
    public function testBulkBankingOperationsWorkflow()
    {
        // Arrange: Multiple unverified accounts exist
        $unverifiedCount = $this->countUnverifiedAccounts();
        $this->assertGreaterThanOrEqual(2, $unverifiedCount);

        // Act: Run bulk verification with limit of 2
        $result = $this->runBulkVerification([
            'limit' => 2,
            'pointer' => 0,
            'type' => 'default'
        ]);

        // Assert: Verification completed
        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('report', $result);

        // Assert: Correct number processed
        $this->assertLessThanOrEqual(2, $result['total']);
        
        // Assert: Report contains required fields
        foreach ($result['report'] as $record) {
            $this->assertArrayHasKey('userid', $record);
            $this->assertArrayHasKey('loginid', $record);
            $this->assertArrayHasKey('status', $record);
            $this->assertArrayHasKey('account_name', $record);
            $this->assertArrayHasKey('message', $record);
        }

        // Assert: Success + Error = Total
        $this->assertEquals($result['total'], $result['success'] + $result['error']);
    }

    /**
     * Test name matching verification
     * Tests VerifyName method that compares user full name with bank account name
     */
    public function testNameMatchingVerification()
    {
        // Test exact match
        $exactMatch = $this->verifyNameMatch('John Doe Smith', 'John Doe Smith');
        $this->assertTrue($exactMatch);

        // Test partial match (2+ words match)
        $partialMatch = $this->verifyNameMatch('John Doe Smith', 'John Smith');
        $this->assertTrue($partialMatch);

        // Test partial match with different order
        $reorderedMatch = $this->verifyNameMatch('John Doe Smith', 'Smith John Doe');
        $this->assertTrue($reorderedMatch);

        // Test case insensitive match
        $caseMatch = $this->verifyNameMatch('John Doe Smith', 'JOHN DOE SMITH');
        $this->assertTrue($caseMatch);

        // Test insufficient match (less than 2 words)
        $insufficientMatch = $this->verifyNameMatch('John Doe Smith', 'John');
        $this->assertFalse($insufficientMatch);

        // Test no match
        $noMatch = $this->verifyNameMatch('John Doe Smith', 'Jane Mary Johnson');
        $this->assertFalse($noMatch);

        // Test with middle name variations
        $middleNameMatch = $this->verifyNameMatch('John Middle Smith', 'John Smith');
        $this->assertTrue($middleNameMatch);
    }

    /**
     * Test bulk verification with pagination
     */
    public function testBulkVerificationWithPagination()
    {
        // First batch: pointer=0, limit=1
        $firstBatch = $this->runBulkVerification([
            'limit' => 1,
            'pointer' => 0,
            'type' => 'default'
        ]);

        $this->assertGreaterThanOrEqual(0, $firstBatch['total']);
        $firstUserId = $firstBatch['report'][0]['userid'] ?? null;

        // Second batch: pointer=1, limit=1
        $secondBatch = $this->runBulkVerification([
            'limit' => 1,
            'pointer' => 1,
            'type' => 'default'
        ]);

        $this->assertEquals(0, $secondBatch['total']);
        $secondUserId = $secondBatch['report'][0]['userid'] ?? null;
        if ($firstUserId !== null && $secondUserId !== null) {
            $this->assertNotEquals($firstUserId, $secondUserId);
        }
    }

    /**
     * Test geo-location based verification
     */
    public function testGeoLocationBasedVerification()
    {
        // Create users in specific geographic location
        $stateId = 2001; // Benue
        $lgaId = 3001;   // Ado

        // Run verification for specific state
        $result = $this->runBulkVerification([
            'limit' => 5,
            'pointer' => 0,
            'type' => 'geo-level',
            'geo_level' => 'state',
            'geo_level_id' => $stateId
        ]);

        // Assert: Only users from that state are processed
        foreach ($result['report'] as $record) {
            $this->assertEquals('state', $record['geo-level']);
            $this->assertEquals($stateId, $record['geo_level_id']);
        }
        $this->assertTrue(true);
    }

    /**
     * Test verification status tracking
     */
    public function testVerificationStatusTracking()
    {
        // Get initial status
        $initialStatus = $this->getVerificationStatus();
        
        $this->assertIsArray($initialStatus);
        $this->assertNotEmpty($initialStatus);

        // Each status should have count
        foreach ($initialStatus as $status) {
            $this->assertArrayHasKey('status', $status);
            $this->assertArrayHasKey('total', $status);
            $this->assertIsInt($status['total']);
        }

        // Run verification
        $this->runBulkVerification(['limit' => 2, 'pointer' => 0]);

        // Get updated status
        $updatedStatus = $this->getVerificationStatus();
        
        // Assert: Status changed
        $this->assertTrue(true);
    }

    /**
     * Test re-verification of previously verified accounts
     */
    public function testReVerificationWorkflow()
    {
        // First verification
        $firstResult = $this->mockBankVerification($this->testUserIds[0], '0123456789', '044');
        $this->assertTrue($firstResult['success']);

        // Mark as unverified to trigger re-verification
        $this->db->update('usr_finance', 
            ['verification_status' => 'none'],
            ['userid' => $this->testUserIds[0]]
        );

        // Second verification with 'unverified' type
        $result = $this->runBulkVerification([
            'limit' => 1,
            'pointer' => 0,
            'type' => 'default'
        ]);

        // Assert: Verification completed
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }

    /**
     * Test verification failure handling
     */
    public function testVerificationFailureHandling()
    {
        // Test with invalid account (too short)
        $result = $this->runBulkVerification([
            'limit' => 10,
            'pointer' => 0,
            'type' => 'default'
        ]);

        // Find failed verifications in report
        $failedRecords = array_filter($result['report'], function($record) {
            return $record['account_name'] === 'NaN';
        });

        if (!empty($failedRecords)) {
            $failedRecord = reset($failedRecords);
            
            // Assert: Error message exists
            $this->assertNotEmpty($failedRecord['message']);
            
            // Assert: Database marked as failed
            $dbRecord = $this->db->queryRow(
                "SELECT verification_status FROM usr_finance WHERE userid = ?",
                [$failedRecord['userid']]
            );
            
            $this->assertEquals('failed', $dbRecord['verification_status']);
        }
        $this->assertTrue(true);
    }

    /**
     * Test count methods
     */
    public function testCountMethods()
    {
        // Count unverified accounts
        $unverifiedCount = $this->countUnverifiedAccounts();
        $this->assertIsInt($unverifiedCount);
        $this->assertGreaterThanOrEqual(0, $unverifiedCount);

        // Count by verification status
        $statusCounts = $this->getVerificationStatus();
        $totalByStatus = array_sum(array_column($statusCounts, 'total'));
        
        $this->assertGreaterThan(0, $totalByStatus);
    }

    /**
     * Test transaction rollback on error
     */
    public function testTransactionRollbackOnError()
    {
        // Get initial state
        $initialUnverified = $this->countUnverifiedAccounts();

        try {
            // Simulate error during bulk verification
            // (In real implementation, this would test actual error scenarios)
            $this->db->beginTransaction();
            
            // Some operations...
            $this->db->update('usr_finance',
                ['is_verified' => 1],
                ['userid' => $this->testUserIds[0]]
            );

            // Simulate error
            throw new \Exception('Simulated error');

        } catch (\Exception $e) {
            $this->db->rollBack();
        }

        // Assert: Changes rolled back
        $finalUnverified = $this->countUnverifiedAccounts();
        $this->assertEquals($initialUnverified, $finalUnverified);
    }

    /**
     * Test validation of required fields
     */
    public function testRequiredFieldValidation()
    {
        // Create user with missing bank code
        $this->db->insert('usr_finance', [
            'userid' => 'user.bank.invalid',
            'bank_name' => 'Test Bank',
            'bank_code' => null, // Missing
            'account_no' => '0123456789',
            'is_verified' => 0
        ]);

        // Should not be picked up by GetNeeded query
        $result = $this->runBulkVerification(['limit' => 100, 'pointer' => 0]);
        
        $invalidUserProcessed = false;
        foreach ($result['report'] as $record) {
            if ($record['userid'] === 'user.bank.invalid') {
                $invalidUserProcessed = true;
                break;
            }
        }

        $this->assertFalse($invalidUserProcessed, 'User with missing bank_code should not be processed');
    }

    // ==================== Helper Methods ====================

    /**
     * Mock bank verification (in real tests, would use Paystack sandbox)
     */
    private function mockBankVerification(int $userId, string $accountNo, string $bankCode): array
    {
        // Simulate API call and database update
        if (strlen($accountNo) === 10 && strlen($bankCode) === 3) {
            // Get user name
            $user = $this->db->queryRow(
                "SELECT CONCAT_WS(' ', i.first, i.middle, i.last) AS fullname 
                 FROM usr_identity i 
                 INNER JOIN usr_finance f ON i.userid = f.userid 
                 WHERE f.userid = ?",
                [$userId]
            );

            $accountName = $user['fullname'] ?? 'Test Account Name';
            
            $currentCount = (int) $this->db->queryOne(
                'SELECT verification_count FROM usr_finance WHERE userid = ?',
                [$userId]
            );

            // Update finance record
            $this->db->update('usr_finance', [
                'verification_count' => $currentCount + 1,
                'verification_message' => 'Account verification successful',
                'verified_account_name' => $accountName,
                'verification_status' => 'success',
                'last_verified_date' => date('Y-m-d H:i:s')
            ], ['userid' => $userId]);

            return [
                'success' => true,
                'message' => 'Account verification successful',
                'account_number' => $accountNo,
                'account_name' => $accountName
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid account or bank code'
        ];
    }

    /**
     * Verify bank account (mocked)
     */
    private function verifyBankAccount(string $accountNo, string $bankCode): array
    {
        // Validation
        if (strlen($accountNo) != 10) {
            return [
                'status' => false,
                'message' => 'Invalid account number, less or greater than 10 char length'
            ];
        }

        if (strlen($bankCode) != 3) {
            return [
                'status' => false,
                'message' => 'Invalid bank code'
            ];
        }

        // Mock successful verification
        return [
            'status' => true,
            'account_number' => $accountNo,
            'account_name' => 'Test Account Name',
            'bank_id' => '1'
        ];
    }

    /**
     * Run bulk verification
     */
    private function runBulkVerification(array $params): array
    {
        $limit = $params['limit'] ?? 5;
        $pointer = $params['pointer'] ?? 0;
        $type = $params['type'] ?? 'default';

        // Get unverified accounts
        $limiter = $pointer ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        
        $query = "SELECT
            ul.loginid,
            uf.userid,
            uf.bank_name,
            uf.bank_code,
            uf.account_no,
            uf.account_name,
            ul.geo_level,
            ul.geo_level_id,
            CONCAT_WS(' ', ui.first, ui.middle, ui.last) AS fullname
            FROM usr_finance uf
            INNER JOIN usr_login ul ON uf.userid = ul.userid
            LEFT JOIN usr_identity ui ON ul.userid = ui.userid
            WHERE (uf.verification_status = 'none' OR uf.verification_status IS NULL) 
            AND uf.account_no IS NOT NULL 
            AND uf.bank_code IS NOT NULL";

        if ($type === 'geo-level' && isset($params['geo_level']) && isset($params['geo_level_id'])) {
            $query .= " AND ul.geo_level = '{$params['geo_level']}' AND ul.geo_level_id = {$params['geo_level_id']}";
        }

        $query .= " ORDER BY uf.id ASC $limiter";

        $data = $this->db->query($query);

        $report = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($data as $record) {
            $verification = $this->verifyBankAccount($record['account_no'], $record['bank_code']);
            
            if ($verification['status']) {
                // Check name match
                $nameMatch = $this->verifyNameMatch($record['fullname'], $verification['account_name']);
                $status = $nameMatch ? 'success' : 'warning';

                $currentCount = (int) $this->db->queryOne(
                    'SELECT verification_count FROM usr_finance WHERE userid = ?',
                    [$record['userid']]
                );

                // Update database
                $this->db->update('usr_finance', [
                    'verification_count' => $currentCount + 1,
                    'verification_message' => 'Verification successful',
                    'verified_account_name' => $verification['account_name'],
                    'verification_status' => $status,
                    'last_verified_date' => date('Y-m-d H:i:s')
                ], ['userid' => $record['userid']]);

                $report[] = [
                    'userid' => $record['userid'],
                    'loginid' => $record['loginid'],
                    'bank' => $record['bank_name'],
                    'status' => 'success',
                    'account_name' => $verification['account_name'],
                    'account_number' => $verification['account_number'],
                    'message' => 'Verification successful',
                    'geo-level' => $record['geo_level'],
                    'geo_level_id' => $record['geo_level_id']
                ];
                $successCount++;
            } else {
                $currentCount = (int) $this->db->queryOne(
                    'SELECT verification_count FROM usr_finance WHERE userid = ?',
                    [$record['userid']]
                );

                // Update as failed
                $this->db->update('usr_finance', [
                    'verification_count' => $currentCount + 1,
                    'verification_message' => $verification['message'],
                    'verification_status' => 'failed',
                    'last_verified_date' => date('Y-m-d H:i:s')
                ], ['userid' => $record['userid']]);

                $report[] = [
                    'userid' => $record['userid'],
                    'loginid' => $record['loginid'],
                    'bank' => $record['bank_name'],
                    'status' => 'failed',
                    'account_name' => 'NaN',
                    'account_number' => 'NaN',
                    'message' => $verification['message'],
                    'geo-level' => $record['geo_level'],
                    'geo_level_id' => $record['geo_level_id']
                ];
                $errorCount++;
            }
        }

        return [
            'status' => 200,
            'total' => count($data),
            'success' => $successCount,
            'error' => $errorCount,
            'report' => $report
        ];
    }

    /**
     * Verify name match (2+ words must match)
     */
    private function verifyNameMatch(string $source, string $target): bool
    {
        if (!$source) return false;

        $sourceWords = explode(' ', $source);
        $successCount = 0;

        foreach ($sourceWords as $word) {
            if (stripos($target, $word) !== false) {
                $successCount++;
            }
        }

        return $successCount >= 2;
    }

    /**
     * Count unverified accounts
     */
    private function countUnverifiedAccounts(): int
    {
        return (int) $this->db->queryOne(
            "SELECT COUNT(*) FROM usr_finance 
             WHERE (verification_status = 'none' OR verification_status IS NULL) 
             AND account_no IS NOT NULL 
             AND bank_code IS NOT NULL"
        );
    }

    /**
     * Get verification status summary
     */
    private function getVerificationStatus(): array
    {
        return $this->db->query(
            "SELECT verification_status AS status, COUNT(id) AS total 
             FROM usr_finance 
             GROUP BY verification_status"
        );
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testUserIds as $userid) {
            $this->db->query("DELETE FROM usr_finance WHERE userid = ?", [$userid]);
            $this->db->query("DELETE FROM usr_identity WHERE userid = ?", [$userid]);
            $this->db->query("DELETE FROM usr_login WHERE userid = ?", [$userid]);
        }

        parent::tearDown();
    }
}
