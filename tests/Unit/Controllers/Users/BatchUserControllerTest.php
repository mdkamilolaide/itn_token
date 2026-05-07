<?php

namespace Tests\Unit\Controllers\Users;

use Users\BatchUser;

require_once __DIR__ . '/UsersTestCase.php';

/**
 * Unit Test: Batch User Controller
 * 
 * Tests the batch user controller methods in isolation
 */
class BatchUserControllerTest extends UsersTestCase
{
    public function testBulkUploadCreatesUsersFromCsv(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'username', 'pwd', 'hash', 'guid', 'roleid', 'geo_level', 'geo_level_id', 'user_group', 'active', 'created', 'updated', 'loginid'],
            'usr_identity' => ['userid', 'first', 'middle', 'last', 'phone'],
            'usr_finance' => ['userid', 'bank_name', 'bank_code', 'account_name', 'account_no'],
            'usr_security' => ['userid'],
        ]);

        $controller = new BatchUser();
        $tmp = tempnam(sys_get_temp_dir(), 'batch');
        $data = [
            ['geo_level', 'geo_level_id', 'role', 'name', 'phone', 'bank', 'bank_code', 'account_number'],
            ['ward', '10', 'role', 'Jane Mary Doe', '08000000000', 'Test Bank', '001', '0001112223'],
        ];
        $handle = fopen($tmp, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        ob_start();
        $controller->BulkUpload($tmp, 'groupA', 1);
        ob_end_clean();

        $rows = $this->getDb()->DataTable("SELECT userid, loginid FROM usr_login WHERE user_group = 'groupA'");
        $this->assertCount(1, $rows);

        $userId = (int) $rows[0]['userid'];
        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
        $this->recordCleanup('usr_finance', 'userid', $userId);
        $this->recordCleanup('usr_security', 'userid', $userId);

        $identity = $this->getDb()->DataTable("SELECT first, middle, last FROM usr_identity WHERE userid = {$userId}");
        $this->assertSame('Jane', $identity[0]['first']);
        $this->assertSame('Mary', $identity[0]['middle']);
        $this->assertSame('Doe', $identity[0]['last']);

        unlink($tmp);
    }
}
