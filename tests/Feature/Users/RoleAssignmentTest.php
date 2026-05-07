<?php

namespace Tests\Feature\Users;

use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    private string $projectRoot;
    private array $createdUserIds = [];
    private array $createdGroupNames = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/users/userManage.cont.php';
        require_once $this->projectRoot . '/lib/controller/users/bulkuser.cont.php';
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

        if (!empty($this->createdGroupNames)) {
            foreach ($this->createdGroupNames as $group) {
                $rows = $db->DataTable("SELECT userid FROM usr_login WHERE user_group = '$group'");
                if (!empty($rows)) {
                    $ids = array_map(fn ($row) => (int) $row['userid'], $rows);
                    $idList = implode(',', $ids);
                    $db->executeTransaction("DELETE FROM usr_security WHERE userid IN ($idList)", []);
                    $db->executeTransaction("DELETE FROM usr_finance WHERE userid IN ($idList)", []);
                    $db->executeTransaction("DELETE FROM usr_identity WHERE userid IN ($idList)", []);
                    $db->executeTransaction("DELETE FROM usr_login WHERE userid IN ($idList)", []);
                }
            }
        }

        parent::tearDown();
    }

    public function testRoleAssignmentWorkflow(): void
    {
        if (!$this->tableHasColumns('usr_role', ['roleid', 'title'])) {
            $this->markTestSkipped('Role tables missing');
        }

        $userManage = new \Users\UserManage();
        $roleId = $this->getRoleId();

        $userid = $this->createUser();
        $this->assertTrue((bool) $userManage->UpdateUserRole($roleId, $userid));

        $bulk = $userManage->BulkChangeRole([$userid], $roleId);
        $this->assertEquals(1, $bulk);

        $roleInfo = $userManage->GetUserRoleStructure($roleId);
        $this->assertIsArray($roleInfo);

        $roles = $userManage->GetRoleList();
        $this->assertIsArray($roles);

        $geo = $this->getGeoSample();
        $bulkGeo = $userManage->BulkChangeGeoLocation([$userid], $geo['level'], $geo['id']);
        $this->assertEquals(1, $bulkGeo);

        if ($this->tableHasColumns('usr_identity', ['userid'])
            && $this->tableHasColumns('usr_finance', ['userid'])
            && $this->tableHasColumns('usr_security', ['userid'])
            && $this->tableHasColumns('sys_bank_code', ['bank_code'])) {
            $bulkUpdate = $userManage->BulkUserUpdate([
                [
                    'userid' => $userid,
                    'roleid' => $roleId,
                    'first' => 'Role',
                    'middle' => 'Assign',
                    'last' => 'Test',
                    'gender' => 'male',
                    'email' => 'role.assign@example.com',
                    'phone' => '08000000011',
                    'bank_code' => $this->getBankCode(),
                    'account_name' => 'Role Assign',
                    'account_no' => '1234567890',
                    'bio_feature' => 'BIO-ROLE'
                ]
            ]);
            $this->assertEquals(1, $bulkUpdate);
        } else {
            $this->assertTrue(true);
        }

        if ($this->tableHasColumns('usr_workhour_extension', ['userid'])) {
            $extension = $userManage->BulkWorkHourExtension([
                [
                    'userid' => $userid,
                    'extension_hour' => 2,
                    'extension_date' => date('Y-m-d'),
                    'authorized_user' => $userid,
                ]
            ]);
            $this->assertIsInt($extension);
        }

        if ($this->tableHasColumns('sys_working_hours', ['start_time', 'end_time'])) {
            $defaultHours = $userManage->GetDefaultWorkHours();
            $this->assertIsArray($defaultHours);
        }

        if ($this->tableHasColumns('usr_workhour_extension', ['userid'])
            && $this->tableHasColumns('sys_working_hours', ['start_time', 'end_time'])) {
            $this->assertNotFalse($userManage->AddUserWorkHour($userid, 1, date('Y-m-d'), $userid));
            $workingHours = $userManage->GetUserWorkingHours($userid);
            $this->assertIsArray($workingHours);
        }
    }

    public function testBulkUserCreationWithRole(): void
    {
        if (!$this->tableHasColumns('usr_login', ['userid', 'user_group'])) {
            $this->markTestSkipped('User tables missing');
        }

        $geo = $this->getGeoSample();
        $group = 'bulk_' . strtolower(substr(uniqid(), -6));
        $this->createdGroupNames[] = $group;

        $bulk = new \Users\BulkUser($group, 'BulkPass1', $geo['level'], $geo['id'], $this->getRoleId());
        $count = $bulk->CreateBulkUser(1);
        $this->assertEquals(1, $count);

        $rows = $this->getDb()->DataTable("SELECT userid FROM usr_login WHERE user_group = '$group'");
        $this->assertNotEmpty($rows);
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

    private function getBankCode(): string
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SHOW COLUMNS FROM sys_bank_code');
        if (count($rows) === 0) {
            return '001';
        }
        $code = $this->safeSelectValue($db, 'SELECT bank_code AS val FROM sys_bank_code LIMIT 1');
        return $code ?: '001';
    }

    private function createUser(): int
    {
        $userManage = new \Users\UserManage();
        $username = 'usr_' . strtolower(substr(uniqid(), -6));
        $group = 'grp_' . strtolower(substr(uniqid(), -4));
        $password = 'Pass-' . substr(uniqid(), -6);

        $userManage->AddLoginPadding('TST');
        $userid = $userManage->CreateUser($username, $password, $this->getRoleId(), $group);
        $this->createdUserIds[] = (int) $userid;
        return (int) $userid;
    }
}
