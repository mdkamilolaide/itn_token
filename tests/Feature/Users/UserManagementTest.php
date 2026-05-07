<?php

namespace Tests\Feature\Users;

use Tests\TestCase;

/**
 * Feature tests for user management functionality
 * Tests complete user management workflows from user perspective
 */
class UserManagementTest extends TestCase
{
    private string $projectRoot;
    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/users/userManage.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        if (!empty($this->createdUserIds)) {
            $ids = array_filter(array_map('intval', $this->createdUserIds));
            if (!empty($ids)) {
                $idList = implode(',', $ids);
                $db->executeTransaction("DELETE FROM usr_workhour_extension WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_security WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_finance WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_identity WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_login WHERE userid IN ($idList)", []);
            }
        }

        parent::tearDown();
    }

    /**
     * Test admin can create new user
     */
    public function testAdminCanCreateNewUser()
    {
        if (!$this->tableHasColumns('usr_login', ['userid', 'username', 'roleid'])) {
            $this->markTestSkipped('User tables missing');
        }

        $userManage = new \Users\UserManage();
        $roleId = $this->getRoleId();

        $username = 'usr_' . strtolower(substr(uniqid(), -6));
        $usergroup = 'grp_' . strtolower(substr(uniqid(), -4));
        $password = 'Pass-' . substr(uniqid(), -6);

        $userManage->AddLoginPadding('TST');
        $userid = $userManage->CreateUser($username, $password, $roleId, $usergroup);
        $this->assertNotFalse($userid);
        $this->createdUserIds[] = (int) $userid;

        $loginid = $userManage->GetUserLoginId($userid);
        $this->assertNotEmpty($loginid);

        $baseInfo = $userManage->GetUserBaseInfo($userid);
        $this->assertIsArray($baseInfo);

        $identity = $userManage->GetUserIdentity($userid);
        $this->assertIsArray($identity);

        $finance = $userManage->GetUserFinance($userid);
        $this->assertIsArray($finance);

        $roles = $userManage->GetRoleList();
        $this->assertIsArray($roles);

        $groups = $userManage->GetUserGroupList();
        $this->assertIsArray($groups);

        $this->assertIsArray($userManage->TableTestList());
        $this->assertIsArray($userManage->ListUserFull());

        $this->assertIsArray($userManage->GetBadgeByGroup($usergroup));
        $this->assertIsArray($userManage->GetBadgeByUserID($userid));
        $this->assertIsArray($userManage->GetBadgeByLoginId($loginid));
        $this->assertIsArray($userManage->GetBadgeByUserIdList([$userid]));

        if ($this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            $geoRows = $this->getDb()->DataTable('SELECT geo_level, geo_level_id FROM sys_geo_codex LIMIT 1');
            if (!empty($geoRows)) {
                $geo = $this->getGeoSample();
                $userManage->ChangeUserLevel($userid, $geo['level'], $geo['id']);
                $export = $userManage->ExcelDownloadUsers($geo['level'], $geo['id']);
                $this->assertNotEmpty($export);
                $count = $userManage->ExcelCountUsers($geo['level'], $geo['id']);
                $this->assertIsNumeric($count);
            } else {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test admin can edit user information
     */
    public function testAdminCanEditUser()
    {
        if (!$this->tableHasColumns('usr_identity', ['userid', 'first', 'last'])) {
            $this->markTestSkipped('Identity tables missing');
        }

        $userManage = new \Users\UserManage();
        $userid = $this->createUser();

        $updatedIdentity = $userManage->UpdateIdentity('John', 'M', 'Doe', 'male', 'john.doe@example.com', '08000000001', $userid);
        $this->assertTrue((bool) $updatedIdentity);

        $updatedFinance = $userManage->UpdateFinance('Test Bank', '001', '1234567890', 'John Doe', $userid);
        $this->assertTrue((bool) $updatedFinance);

        $updatedSecurity = $userManage->UpdateSecurity('BIO123', $userid);
        $this->assertTrue((bool) $updatedSecurity);

        $updatedRole = $userManage->UpdateRole($this->getRoleId(), $userid);
        $this->assertTrue((bool) $updatedRole);

        $geo = $this->getGeoSample();
        $changed = $userManage->ChangeUserLevel($userid, $geo['level'], $geo['id']);
        $this->assertTrue((bool) $changed);

        $registered = $userManage->RegisterUserFcm($userid, 'SN-' . substr(uniqid(), -6), 'fcm-' . substr(uniqid(), -6));
        $this->assertTrue((bool) $registered);

        $baseInfo = $userManage->GetUserBaseInfo($userid);
        $this->assertNotEmpty($baseInfo);
    }

    /**
     * Test admin can change user role
     */
    public function testAdminCanChangeUserRole()
    {
        $userManage = new \Users\UserManage();
        $userid = $this->createUser();
        $roleId = $this->getRoleId();

        $updated = $userManage->UpdateUserRole($roleId, $userid);
        $this->assertTrue((bool) $updated);

        $bulkUpdated = $userManage->BulkChangeRole([$userid], $roleId);
        $this->assertEquals(1, $bulkUpdated);

        $roleInfo = $userManage->GetUserRoleStructure($roleId);
        $this->assertIsArray($roleInfo);
    }

    /**
     * Test user can change own password
     */
    public function testUserCanChangeOwnPassword()
    {
        if (!$this->tableHasColumns('usr_login', ['pwd', 'hash', 'loginid'])) {
            $this->markTestSkipped('Login table missing password fields');
        }

        $userManage = new \Users\UserManage();
        $password = 'Old-' . substr(uniqid(), -6);
        $userid = $this->createUser($password);
        $loginid = $userManage->GetUserLoginId($userid);

        $changed = $userManage->ChangePassword($loginid, $password, 'New-' . substr(uniqid(), -6));
        $this->assertTrue((bool) $changed);

        $reset = $userManage->ResetPassword($loginid, 'Reset-' . substr(uniqid(), -6));
        $this->assertTrue((bool) $reset);

        $bulk = $userManage->BulkPasswordReset([$userid], 'Bulk-' . substr(uniqid(), -6));
        $this->assertEquals(1, $bulk);
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SHOW COLUMNS FROM ' . $table);
        if (count($rows) === 0) {
            return false;
        }
        $existing = array_map(fn ($row) => $row['Field'], $rows);
        foreach ($columns as $column) {
            if (!in_array($column, $existing, true)) {
                return false;
            }
        }
        return true;
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }
        return $rows[0]['val'] ?? null;
    }

    private function getRoleId(): int
    {
        $db = $this->getDb();
        $roleId = (int) ($this->safeSelectValue($db, 'SELECT roleid AS val FROM usr_role WHERE active = 1 ORDER BY priority ASC LIMIT 1') ?? 0);
        return $roleId ?: 1;
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SELECT geo_level, geo_level_id FROM sys_geo_codex LIMIT 1');
        if (!empty($rows)) {
            return [
                'level' => $rows[0]['geo_level'],
                'id' => (int) $rows[0]['geo_level_id'],
            ];
        }

        $lgaId = (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 1);
        return [
            'level' => 'lga',
            'id' => $lgaId ?: 1,
        ];
    }

    private function createUser(?string $password = null): int
    {
        $userManage = new \Users\UserManage();
        $roleId = $this->getRoleId();
        $username = 'usr_' . strtolower(substr(uniqid(), -6));
        $group = 'grp_' . strtolower(substr(uniqid(), -4));
        $password = $password ?? ('Pass-' . substr(uniqid(), -6));

        $userManage->AddLoginPadding('TST');
        $userid = $userManage->CreateUser($username, $password, $roleId, $group);
        $this->createdUserIds[] = (int) $userid;
        return (int) $userid;
    }
}
