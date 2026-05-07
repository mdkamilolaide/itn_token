<?php

namespace Tests\Unit\Controllers\Users;

use Users\Login;

require_once __DIR__ . '/UsersTestCase.php';

/**
 * Unit Test: User Login Controller
 * 
 * Tests the user login controller methods in isolation
 */
class LoginControllerTest extends UsersTestCase
{
    public function testRunLoginSuccessAndDeviceRegistry(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'loginid', 'pwd', 'guid', 'roleid', 'geo_level', 'geo_level_id', 'active'],
            'usr_identity' => ['userid', 'first', 'middle', 'last'],
            'usr_finance' => ['userid'],
            'usr_security' => ['userid'],
            'usr_role' => ['roleid', 'title'],
            'sys_geo_codex' => ['geo_level', 'geo_level_id', 'geo_string'],
            'sys_device_registry' => ['serial_no', 'connected', 'connected_loginid'],
            'sys_device_login' => ['device_serial', 'loginid', 'created'],
        ]);

        $this->insertRow('usr_role', [
            'roleid' => 1,
            'title' => 'Role',
        ]);
        $this->recordCleanup('usr_role', 'roleid', 1);

        $this->seedGeoCodex('ward', 10);

        $userId = random_int(910000, 919999);
        $this->seedUser($userId, 'login1', 'Pass1234', 'ward', 10, 1, 'grp', 1);

        $serial = 'DEV-' . uniqid();
        $this->insertRow('sys_device_registry', [
            'device_name' => 'Device',
            'device_id' => 'ID-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'serial_no' => $serial,
            'device_type' => 'android',
        ]);
        $this->recordCleanup('sys_device_registry', 'serial_no', $serial);

        $login = new Login('id');
        $login->SetLoginId('login1', 'Pass1234');
        $success = $login->RunLogin($serial);
        $this->assertTrue($success);
        $this->assertTrue($login->IsLoginSuccessful);

        $guidRow = $this->getDb()->DataTable("SELECT guid FROM usr_login WHERE loginid = 'login1'");
        $badge = new Login('badge');
        $badge->SetBadge('login1|' . $guidRow[0]['guid']);
        $this->assertTrue($badge->RunLogin());

        $deviceRow = $this->getDb()->DataTable("SELECT connected_loginid FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertSame('login1', $deviceRow[0]['connected_loginid']);
    }

    public function testRunLoginFailureCases(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'loginid', 'pwd', 'guid', 'active'],
            'usr_identity' => ['userid', 'first', 'middle', 'last'],
            'usr_finance' => ['userid'],
            'usr_security' => ['userid'],
        ]);

        $userId = random_int(920000, 929999);
        $this->seedUser($userId, 'login2', 'Pass1234', 'ward', 10, 1, 'grp', 0);

        $login = new Login('id');
        $login->SetLoginId('login2', 'Wrong');
        $this->assertFalse($login->RunLogin());
        $this->assertSame('Your password is incorrect, please try again', $login->LastError);

        $login->SetLoginId('login2', 'Pass1234');
        $this->assertFalse($login->RunLogin());
        $this->assertSame('Your account is not active', $login->LastError);

        $badge = new Login('badge');
        $badge->SetBadge('login2|bad-guid');
        $this->assertFalse($badge->RunLogin());
        $this->assertSame('Your badge value was incorrect', $badge->LastError);

        $invalid = new Login('id');
        $invalid->SetLoginId('missing', 'Pass1234');
        $this->assertFalse($invalid->RunLogin());
        $this->assertSame('Invalid login information', $invalid->LastError);
    }
}
