<?php

namespace Tests\Unit\Controllers\Users;

use Users\BankVerify;
use Users\BulkBankVerification;

require_once __DIR__ . '/UsersTestCase.php';

/**
 * Unit Test: Bulk Bank Verification Controller
 * 
 * Tests the bulk bank verification controller methods in isolation
 */
class BulkBankVerificationControllerTest extends UsersTestCase
{
    public function testBulkVerificationHandlesFailure(): void
    {
        $this->requireSchema([
            'usr_finance' => ['userid', 'bank_code', 'account_no', 'is_verified', 'verification_status'],
            'usr_login' => ['userid', 'loginid', 'geo_level', 'geo_level_id'],
        ]);

        $userId = random_int(3000, 3999);
        $this->seedUser($userId, 'user3', 'pass', 'ward', 11);
        $this->getDb()->executeTransaction('UPDATE usr_finance SET account_no = ?, bank_code = ?, is_verified = 0 WHERE userid = ?', ['0001112225', '001', $userId]);

        $GLOBALS['__users_curl_response__'] = json_encode([
            'status' => false,
            'message' => 'failed',
            'data' => [],
        ]);

        $bulk = new BulkBankVerification();
        $bulk->limit = 5;
        $bulk->Run();

        $row = $this->getDb()->DataTable("SELECT verification_status FROM usr_finance WHERE userid = {$userId}");
        $this->assertSame('failed', $row[0]['verification_status']);
    }

    public function testStatusCounts(): void
    {
        $this->requireSchema([
            'usr_finance' => ['userid', 'verification_status', 'account_no', 'bank_code', 'is_verified'],
            'usr_login' => ['userid', 'geo_level', 'geo_level_id'],
        ]);

        $userId = random_int(4000, 4999);
        $this->seedUser($userId, 'user4', 'pass', 'ward', 12);
        $this->getDb()->executeTransaction('UPDATE usr_finance SET verification_status = ?, account_no = ?, bank_code = ?, is_verified = 0 WHERE userid = ?', ['pending', '0001112226', '001', $userId]);

        $bulk = new BulkBankVerification();
        $needed = $bulk->CountNeeded();
        $this->assertGreaterThanOrEqual(1, (int) $needed);

        $unverified = $bulk->CountUnverified();
        $this->assertGreaterThanOrEqual(1, (int) $unverified);

        $status = $bulk->GetStatus();
        $this->assertNotEmpty($status);
    }
}
