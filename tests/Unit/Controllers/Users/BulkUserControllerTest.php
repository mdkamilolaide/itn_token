<?php

namespace Tests\Unit\Controllers\Users;

use Users\BulkUser;

require_once __DIR__ . '/UsersTestCase.php';

/**
 * Unit Test: Bulk User Controller
 * 
 * Tests the bulk user controller methods in isolation
 */
class BulkUserControllerTest extends UsersTestCase
{
    public function testCreateBulkUsersWithAndWithoutRole(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'username', 'pwd', 'hash', 'guid', 'geo_level', 'geo_level_id', 'user_group', 'active', 'created', 'updated', 'loginid', 'roleid'],
            'usr_identity' => ['userid'],
            'usr_finance' => ['userid'],
            'usr_security' => ['userid'],
        ]);

        $bulk = new BulkUser('grp', 'Pass1234', 'ward', 10, 0);
        $count = $bulk->CreateBulkUser(2);
        $this->assertSame(2, $count);

        $rows = $this->getDb()->DataTable("SELECT userid FROM usr_login WHERE user_group = 'grp'");
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->recordCleanup('usr_login', 'userid', $row['userid']);
            $this->recordCleanup('usr_identity', 'userid', $row['userid']);
            $this->recordCleanup('usr_finance', 'userid', $row['userid']);
            $this->recordCleanup('usr_security', 'userid', $row['userid']);
        }

        $bulkRole = new BulkUser('grp2', 'Pass1234', 'ward', 10, 5);
        $countRole = $bulkRole->CreateBulkUser(1);
        $this->assertSame(1, $countRole);
        $rowsRole = $this->getDb()->DataTable("SELECT userid, roleid FROM usr_login WHERE user_group = 'grp2'");
        $this->assertCount(1, $rowsRole);
        $this->assertSame('5', (string) $rowsRole[0]['roleid']);
        $this->recordCleanup('usr_login', 'userid', $rowsRole[0]['userid']);
        $this->recordCleanup('usr_identity', 'userid', $rowsRole[0]['userid']);
        $this->recordCleanup('usr_finance', 'userid', $rowsRole[0]['userid']);
        $this->recordCleanup('usr_security', 'userid', $rowsRole[0]['userid']);
    }

    public function testCreateBulkUserWithInvalidTotal(): void
    {
        $bulk = new BulkUser('grp3', 'Pass1234', 'ward', 10, 1);
        $count = $bulk->CreateBulkUser(0);
        $this->assertSame(0, $count);
    }
}
