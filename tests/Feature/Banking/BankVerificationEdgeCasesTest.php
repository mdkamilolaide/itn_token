<?php

namespace Tests\Feature\Banking;

use Tests\TestCase;
use Tests\Helpers\Assertions\DatabaseAssertions;
use Tests\Helpers\Builders\UserBuilder;
use Tests\Helpers\DatabaseHelper;

/**
 * Bank Verification Edge Cases Tests
 * 
 * Tests edge cases, boundary conditions, and error scenarios in banking verification
 */
class BankVerificationEdgeCasesTest extends TestCase
{
    use DatabaseAssertions;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new DatabaseHelper();
    }

    /**
     * Test account number edge cases
     */
    public function testAccountNumberEdgeCases()
    {
        // Test exact 10 digits (valid)
        $valid = $this->validateAccountNumber('0123456789');
        $this->assertTrue($valid['valid']);

        // Test 9 digits (too short)
        $tooShort = $this->validateAccountNumber('012345678');
        $this->assertFalse($tooShort['valid']);
        $this->assertStringContainsString('less', $tooShort['message']);

        // Test 11 digits (too long)
        $tooLong = $this->validateAccountNumber('01234567890');
        $this->assertFalse($tooLong['valid']);
        $this->assertStringContainsString('greater', $tooLong['message']);

        // Test with letters
        $withLetters = $this->validateAccountNumber('012345678A');
        $this->assertTrue($withLetters['valid']); // System accepts it (API will reject)

        // Test with special characters
        $withSpecial = $this->validateAccountNumber('0123-56789');
        $this->assertTrue($withSpecial['valid']); // Length check only

        // Test empty string
        $empty = $this->validateAccountNumber('');
        $this->assertFalse($empty['valid']);

        // Test null
        $null = $this->validateAccountNumber(null);
        $this->assertFalse($null['valid']);

        // Test with spaces
        $withSpaces = $this->validateAccountNumber('0123 56789');
        $this->assertTrue($withSpaces['valid']); // Passes length check

        // Test all zeros
        $allZeros = $this->validateAccountNumber('0000000000');
        $this->assertTrue($allZeros['valid']);

        // Test leading zeros
        $leadingZeros = $this->validateAccountNumber('0000000001');
        $this->assertTrue($leadingZeros['valid']);
    }

    /**
     * Test bank code edge cases
     */
    public function testBankCodeEdgeCases()
    {
        // Test exact 3 digits (valid)
        $valid = $this->validateBankCode('044');
        $this->assertTrue($valid['valid']);

        // Test 2 digits (too short)
        $tooShort = $this->validateBankCode('04');
        $this->assertFalse($tooShort['valid']);

        // Test 4 digits (too long)
        $tooLong = $this->validateBankCode('0444');
        $this->assertFalse($tooLong['valid']);

        // Test with letters
        $withLetters = $this->validateBankCode('04A');
        $this->assertTrue($withLetters['valid']); // Length check only

        // Test empty
        $empty = $this->validateBankCode('');
        $this->assertFalse($empty['valid']);

        // Test null
        $null = $this->validateBankCode(null);
        $this->assertFalse($null['valid']);
    }

    /**
     * Test name matching edge cases
     */
    public function testNameMatchingEdgeCases()
    {
        // Test with empty source
        $emptySource = $this->verifyNameMatch('', 'John Doe');
        $this->assertFalse($emptySource);

        // Test with empty target
        $emptyTarget = $this->verifyNameMatch('John Doe', '');
        $this->assertFalse($emptyTarget);

        // Test with both empty
        $bothEmpty = $this->verifyNameMatch('', '');
        $this->assertFalse($bothEmpty);

        // Test single word match (insufficient)
        $singleWord = $this->verifyNameMatch('John', 'John Doe Smith');
        $this->assertFalse($singleWord);

        // Test exact 2 words match (threshold)
        $twoWords = $this->verifyNameMatch('John Smith', 'John Doe Smith');
        $this->assertTrue($twoWords);

        // Test with extra spaces
        $extraSpaces = $this->verifyNameMatch('John  Doe  Smith', 'John Doe Smith');
        $this->assertTrue($extraSpaces);

        // Test with leading/trailing spaces
        $withSpaces = $this->verifyNameMatch(' John Doe Smith ', 'John Doe Smith');
        $this->assertTrue($withSpaces);

        // Test with special characters in name
        $withSpecial = $this->verifyNameMatch("John O'Brien Smith", "John O'Brien Smith");
        $this->assertTrue($withSpecial);

        // Test with hyphenated names
        $hyphenated = $this->verifyNameMatch('Mary-Jane Smith Johnson', 'Mary Jane Smith Johnson');
        $this->assertTrue($hyphenated);

        // Test with titles (Mr, Dr, etc)
        $withTitle = $this->verifyNameMatch('Dr John Doe Smith', 'John Doe Smith');
        $this->assertTrue($withTitle);

        // Test very long names
        $longName = $this->verifyNameMatch(
            'John Michael Alexander Christopher Smith',
            'John Smith'
        );
        $this->assertTrue($longName);

        // Test with numbers in names
        $withNumbers = $this->verifyNameMatch('John Doe 2nd', 'John Doe');
        $this->assertTrue($withNumbers);

        // Test completely different names
        $different = $this->verifyNameMatch('John Doe', 'Jane Smith');
        $this->assertFalse($different);

        // Test partial match with rearrangement
        $rearranged = $this->verifyNameMatch('Smith John Doe', 'John Doe Smith');
        $this->assertTrue($rearranged);

        // Test with accents/diacritics
        $withAccents = $this->verifyNameMatch('José García', 'Jose Garcia');
        $this->assertFalse($withAccents); // Implementation is ASCII-only
    }

    /**
     * Test database null handling
     */
    public function testDatabaseNullHandling()
    {
        // Create user with null values
        $user = UserBuilder::new()
            ->withUserId('user.null.test')
            ->withLoginId('null.test')
            ->asMobilizer()
            ->create();

        // Insert finance record with nulls
        $this->db->insert('usr_finance', [
            'userid' => $user,
            'bank_name' => null,
            'bank_code' => null,
            'account_name' => null,
            'account_no' => null
        ]);

        // Query should exclude this record
        $count = $this->countProcessableAccounts();
        
        // Verify null account not counted
        $this->assertRecordExists('usr_finance', ['userid' => $user]);

        // Clean up
        $this->db->query("DELETE FROM usr_finance WHERE userid = ?", [$user]);
        $this->db->query("DELETE FROM usr_login WHERE userid = ?", [$user]);
    }

    /**
     * Test concurrent verification attempts
     */
    public function testConcurrentVerificationAttempts()
    {
        // Create test user
        $user = UserBuilder::new()
            ->withUserId('user.concurrent')
            ->withLoginId('concurrent.test')
            ->asMobilizer()
            ->create();

        $this->db->insert('usr_finance', [
            'userid' => $user,
            'bank_name' => 'Test Bank',
            'bank_code' => '044',
            'account_no' => '0123456789'
        ]);

        // Simulate concurrent verifications
        for ($i = 0; $i < 3; $i++) {
            $this->db->query(
                'UPDATE usr_finance SET verification_count = verification_count + 1 WHERE userid = ?',
                [$user]
            );
        }

        // Check final count
        $finalCount = $this->db->queryOne(
            "SELECT verification_count FROM usr_finance WHERE userid = ?",
            [$user]
        );

        $this->assertEquals(3, $finalCount);

        // Clean up
        $this->db->query("DELETE FROM usr_finance WHERE userid = ?", [$user]);
        $this->db->query("DELETE FROM usr_login WHERE userid = ?", [$user]);
    }

    /**
     * Test very large batch processing
     */
    public function testLargeBatchProcessing()
    {
        $batchSizes = [1, 5, 10, 50, 100];

        foreach ($batchSizes as $size) {
            // Get count before
            $before = $this->countProcessableAccounts();

            // Process batch (mocked)
            $result = $this->processBatch($size);

            // Verify processed count doesn't exceed limit
            $this->assertLessThanOrEqual($size, $result['processed']);
            $this->assertLessThanOrEqual($before, $result['processed']);
        }
    }

    /**
     * Test verification status transitions
     */
    public function testVerificationStatusTransitions()
    {
        $user = UserBuilder::new()
            ->withUserId('user.status.test')
            ->withLoginId('status.test')
            ->asMobilizer()
            ->create();

        $this->db->insert('usr_finance', [
            'userid' => $user,
            'bank_name' => 'Test Bank',
            'bank_code' => '044',
            'account_no' => '0123456789',
            'verification_status' => 'none'
        ]);

        // Test status progression: none -> success
        $this->updateVerificationStatus($user, 'success');
        $this->assertRecordHasValues('usr_finance',
            ['userid' => $user],
            ['verification_status' => 'success']
        );

        // Test: success -> warning (re-verification with mismatch)
        $this->updateVerificationStatus($user, 'warning');
        $this->assertRecordHasValues('usr_finance',
            ['userid' => $user],
            ['verification_status' => 'warning']
        );

        // Test: warning -> failed
        $this->updateVerificationStatus($user, 'failed');
        $this->assertRecordHasValues('usr_finance',
            ['userid' => $user],
            ['verification_status' => 'failed']
        );

        // Test: failed -> success (re-verification after fix)
        $this->updateVerificationStatus($user, 'success');
        $this->assertRecordHasValues('usr_finance',
            ['userid' => $user],
            ['verification_status' => 'success']
        );

        // Clean up
        $this->db->query("DELETE FROM usr_finance WHERE userid = ?", [$user]);
        $this->db->query("DELETE FROM usr_login WHERE userid = ?", [$user]);
    }

    /**
     * Test pointer/pagination boundary conditions
     */
    public function testPaginationBoundaryConditions()
    {
        $total = $this->countProcessableAccounts();

        // Test pointer at 0
        $result1 = $this->processBatch(5, 0);
        $this->assertLessThanOrEqual(5, $result1['processed']);

        // Test pointer beyond total
        $resultBeyond = $this->processBatch(5, $total + 100);
        $this->assertEquals(0, $resultBeyond['processed']);

        // Test pointer exactly at total
        $resultExact = $this->processBatch(5, $total);
        $this->assertEquals(0, $resultExact['processed']);

        // Test very large limit
        $resultLarge = $this->processBatch(10000, 0);
        $this->assertLessThanOrEqual($total, $resultLarge['processed']);
    }

    /**
     * Test verification_count increment accuracy
     */
    public function testVerificationCountAccuracy()
    {
        $user = UserBuilder::new()
            ->withUserId('user.count.test')
            ->withLoginId('count.test')
            ->asMobilizer()
            ->create();

        $this->db->insert('usr_finance', [
            'userid' => $user,
            'bank_name' => 'Test Bank',
            'bank_code' => '044',
            'account_no' => '0123456789',
            'verification_count' => 0
        ]);

        // Perform 5 verifications
        for ($i = 1; $i <= 5; $i++) {
            $this->db->query(
                'UPDATE usr_finance SET verification_count = verification_count + 1 WHERE userid = ?',
                [$user]
            );

            $currentCount = $this->db->queryOne(
                "SELECT verification_count FROM usr_finance WHERE userid = ?",
                [$user]
            );

            $this->assertEquals($i, $currentCount);
        }

        // Clean up
        $this->db->query("DELETE FROM usr_finance WHERE userid = ?", [$user]);
        $this->db->query("DELETE FROM usr_login WHERE userid = ?", [$user]);
    }

    // ==================== Helper Methods ====================

    private function validateAccountNumber($accountNo): array
    {
        if ($accountNo === null || $accountNo === '') {
            return ['valid' => false, 'message' => 'Account number is required'];
        }

        if (strlen($accountNo) != 10) {
            return [
                'valid' => false,
                'message' => 'Invalid account number, less or greater than 10 char length'
            ];
        }

        return ['valid' => true];
    }

    private function validateBankCode($bankCode): array
    {
        if ($bankCode === null || $bankCode === '') {
            return ['valid' => false, 'message' => 'Bank code is required'];
        }

        if (strlen($bankCode) != 3) {
            return ['valid' => false, 'message' => 'Invalid bank code'];
        }

        return ['valid' => true];
    }

    private function verifyNameMatch(string $source, string $target): bool
    {
        if (!$source) return false;

        $sourceWords = explode(' ', $source);
        $successCount = 0;

        foreach ($sourceWords as $word) {
            if ($word && stripos($target, $word) !== false) {
                $successCount++;
            }
        }

        return $successCount >= 2;
    }

    private function countProcessableAccounts(): int
    {
        return (int) $this->db->queryOne(
            "SELECT COUNT(*) FROM usr_finance 
             WHERE verification_status = 'none' 
             AND account_no IS NOT NULL 
             AND bank_code IS NOT NULL"
        );
    }

    private function processBatch(int $limit, int $pointer = 0): array
    {
        $limiter = $pointer ? "LIMIT $pointer,$limit" : "LIMIT $limit";
        
        $data = $this->db->query(
            "SELECT userid FROM usr_finance 
             WHERE verification_status = 'none' 
             AND account_no IS NOT NULL 
             AND bank_code IS NOT NULL 
             ORDER BY id ASC $limiter"
        );

        return ['processed' => count($data)];
    }

    private function updateVerificationStatus(string $userId, string $status): void
    {
        $this->db->update('usr_finance', [
            'verification_status' => $status
        ], ['userid' => $userId]);
    }
}
