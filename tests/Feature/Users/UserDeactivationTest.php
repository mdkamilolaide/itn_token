<?php

namespace Tests\Feature\Users;

use Tests\TestCase;

class UserDeactivationTest extends TestCase
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
                $db->executeTransaction("DELETE FROM usr_security WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_finance WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_identity WHERE userid IN ($idList)", []);
                $db->executeTransaction("DELETE FROM usr_login WHERE userid IN ($idList)", []);
            }
        }

        parent::tearDown();
    }

    public function testUserActivationAndDeactivationWorkflow(): void
    {
        $userManage = new \Users\UserManage();
        $userid = $this->createUser();

        $toggle = $userManage->ToggleUserStatus($userid);
        $this->assertTrue((bool) $toggle);

        $bulk = $userManage->BulkToggleUserStatus([$userid]);
        $this->assertEquals(1, $bulk);

        $group = $this->getUserGroup($userid);
        if ($group) {
            $this->assertTrue((bool) $userManage->DeavtivateUserByGroup($group));
            $this->assertTrue((bool) $userManage->ActivateUserByGroup($group));
        }

        $this->assertIsArray($userManage->DashCountUser());
        $this->assertIsArray($userManage->DashCountActive());
        $this->assertIsArray($userManage->DashCountGeoLevel());
        $this->assertIsArray($userManage->DashCountUserGroup());
        $this->assertIsArray($userManage->DashCountTotalGroup());
        $this->assertIsArray($userManage->DashCountGender());
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
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

    private function getUserGroup(int $userid): ?string
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT user_group FROM usr_login WHERE userid = $userid");
        if (empty($rows)) {
            return null;
        }
        return $rows[0]['user_group'] ?? null;
    }
}
