<?php

namespace Tests\Unit\Controllers\Users;

use Users\UserManage;

require_once __DIR__ . '/UsersTestCase.php';

/**
 * Unit Test: User Management Controller
 * 
 * Tests the user management controller methods in isolation
 */
class UserManageControllerTest extends UsersTestCase
{
    public function testCreateAndUpdateUserDetails(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'username', 'pwd', 'hash', 'guid', 'roleid', 'user_group', 'loginid', 'active'],
            'usr_identity' => ['userid', 'first', 'middle', 'last', 'gender', 'email', 'phone'],
            'usr_finance' => ['userid', 'bank_name', 'bank_code', 'account_name', 'account_no'],
            'usr_security' => ['userid', 'bio_feature'],
            'usr_role' => ['roleid', 'title', 'priority', 'active'],
        ]);

        $manager = new UserManage();
        $manager->AddLoginPadding('LP');

        $this->insertRow('usr_role', [
            'roleid' => 1,
            'title' => 'Role 1',
            'priority' => 1,
            'active' => 1,
        ]);
        $this->recordCleanup('usr_role', 'roleid', 1);
        $this->insertRow('usr_role', [
            'roleid' => 2,
            'title' => 'Role 2',
            'priority' => 1,
            'active' => 1,
        ]);
        $this->recordCleanup('usr_role', 'roleid', 2);

        $userId = $manager->CreateUser('userA', 'Pass1234', 1, 'groupA');
        $this->assertIsNumeric($userId);
        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
        $this->recordCleanup('usr_finance', 'userid', $userId);
        $this->recordCleanup('usr_security', 'userid', $userId);

        $loginId = $manager->GetUserLoginId($userId);
        $this->assertStringStartsWith('LP', $loginId);

        $this->assertTrue((bool) $manager->UpdateFinance('Bank', '001', '123', 'Name', $userId));
        $this->assertTrue((bool) $manager->UpdateIdentity('First', 'Middle', 'Last', 'M', 'email@test.com', '0800', $userId));
        $this->assertTrue((bool) $manager->UpdateSecurity('bio', $userId));
        $this->assertTrue((bool) $manager->UpdateRole(2, $userId));

        $role = $manager->GetUserRoleStructure(2);
        $this->assertIsArray($role);

        $base = $manager->GetUserBaseInfo($userId);
        $this->assertNotEmpty($base);

        $identity = $manager->GetUserIdentity($userId);
        $this->assertNotEmpty($identity);

        $finance = $manager->GetUserFinance($userId);
        $this->assertNotEmpty($finance);
    }

    public function testToggleAndBulkUpdates(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'roleid', 'geo_level', 'geo_level_id', 'active'],
            'usr_identity' => ['userid', 'first', 'middle', 'last', 'gender', 'email', 'phone'],
            'usr_finance' => ['userid', 'bank_name', 'bank_code', 'account_name', 'account_no'],
            'usr_security' => ['userid', 'bio_feature'],
            'sys_bank_code' => ['bank_code', 'bank_name'],
        ]);

        $this->insertRow('sys_bank_code', [
            'bank_code' => '001',
            'bank_name' => 'Test Bank',
        ]);
        $this->recordCleanup('sys_bank_code', 'bank_code', '001');

        $manager = new UserManage();
        $userId = random_int(940000, 949999);
        $this->seedUser($userId, 'loginX', 'Pass1234', 'ward', 10, 1, 'groupA', 1);

        $this->assertTrue((bool) $manager->ActivateUserByGroup('groupA'));

        $this->assertTrue((bool) $manager->ToggleUserStatus($userId));
        $this->assertTrue((bool) $manager->ToggleUserStatus($userId));

        $bulkCount = $manager->BulkToggleUserStatus([$userId]);
        $this->assertSame(1, $bulkCount);

        $bulkUpdated = $manager->BulkUserUpdate([[
            'userid' => $userId,
            'roleid' => 2,
            'first' => 'New',
            'middle' => 'Mid',
            'last' => 'Name',
            'gender' => 'F',
            'email' => 'new@test.com',
            'phone' => '0900',
            'bank_code' => '001',
            'account_name' => 'Acct',
            'account_no' => '123',
            'bio_feature' => 'bio',
        ]]);
        $this->assertSame(1, $bulkUpdated);

        $this->assertSame(1, $manager->BulkPasswordReset([$userId], 'NewPass123'));
        $this->assertSame(1, $manager->BulkChangeGeoLocation([$userId], 'dp', 20));
        $this->assertSame(1, $manager->BulkChangeRole([$userId], 3));

        $this->assertTrue((bool) $manager->UpdateUserRole(4, $userId));
        $this->assertTrue((bool) $manager->ChangeUserLevel($userId, 'dp', 20));
        $this->assertTrue((bool) $manager->RegisterUserFcm($userId, 'DEV', 'TOKEN'));
    }

    public function testPasswordChangeAndReset(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'loginid', 'pwd', 'hash', 'is_change_password'],
        ]);

        $manager = new UserManage();
        $userId = random_int(950000, 959999);
        $this->seedUser($userId, 'loginPwd', 'OldPass', 'ward', 10);

        $this->assertTrue($manager->ChangePassword('loginPwd', 'OldPass', 'NewPass'));
        $this->assertFalse($manager->ChangePassword('loginPwd', 'Wrong', 'NewPass'));

        $this->assertTrue($manager->ResetPassword('loginPwd', 'ResetPass'));
    }

    public function testWorkHoursAndDashboard(): void
    {
        $this->requireSchema([
            'sys_working_hours' => ['id', 'start_time', 'end_time'],
            'usr_workhour_extension' => ['userid', 'extension_hour', 'extension_date', 'created_by_userid'],
            'usr_login' => ['userid', 'geo_level', 'user_group', 'active', 'loginid'],
            'usr_identity' => ['userid', 'gender', 'first', 'middle', 'last'],
            'usr_finance' => ['userid'],
            'usr_security' => ['userid'],
        ]);

        $manager = new UserManage();
        $this->insertRow('sys_working_hours', [
            'id' => 1,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);
        $this->recordCleanup('sys_working_hours', 'id', 1);

        $userId = random_int(970000, 979999);
        $this->seedUser($userId, 'loginWH', 'Pass', 'ward', 10);

        $this->assertIsNumeric($manager->AddUserWorkHour($userId, 2, date('Y-m-d'), $userId));
        $hours = $manager->GetUserWorkingHours($userId);
        $this->assertNotEmpty($hours);

        $bulk = $manager->BulkWorkHourExtension([[
            'userid' => $userId,
            'extension_hour' => 1,
            'extension_date' => date('Y-m-d'),
            'authorized_user' => $userId,
        ]]);
        $this->assertSame(1, $bulk);

        $dashUser = $manager->DashCountUser();
        $this->assertNotEmpty($dashUser);

        $dashActive = $manager->DashCountActive();
        $this->assertNotEmpty($dashActive);

        $dashGeo = $manager->DashCountGeoLevel();
        $this->assertNotEmpty($dashGeo);

        $dashGroup = $manager->DashCountUserGroup();
        $this->assertNotEmpty($dashGroup);

        $dashTotalGroup = $manager->DashCountTotalGroup();
        $this->assertNotEmpty($dashTotalGroup);

        $dashGender = $manager->DashCountGender();
        $this->assertNotEmpty($dashGender);
    }
}
