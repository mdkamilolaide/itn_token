<?php

namespace Tests\Integration\Authentication;

use Tests\TestCase;
use Users\Login;

/**
 * Integration tests for login authentication flow
 * Tests full login process: database query → authentication → session management
 */
class LoginFlowTest extends TestCase
{
    private string $projectRoot;
    private array $cleanup = [
        'usr_login' => [],
        'usr_identity' => [],
        'usr_role' => [],
        'sys_geo_codex' => [],
        'sys_device_registry' => [],
        'sys_device_login' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/autoload.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        foreach ($this->cleanup['sys_device_login'] as $serial) {
            if ($this->tableHasColumns('sys_device_login', ['device_serial'])) {
                $db->executeTransaction('DELETE FROM sys_device_login WHERE device_serial = ?', [$serial]);
            }
        }

        foreach ($this->cleanup['sys_device_registry'] as $serial) {
            if ($this->tableHasColumns('sys_device_registry', ['serial_no'])) {
                $db->executeTransaction('DELETE FROM sys_device_registry WHERE serial_no = ?', [$serial]);
            }
        }

        foreach ($this->cleanup['usr_identity'] as $userid) {
            if ($this->tableHasColumns('usr_identity', ['userid'])) {
                $db->executeTransaction('DELETE FROM usr_identity WHERE userid = ?', [$userid]);
            }
        }

        foreach ($this->cleanup['usr_login'] as $loginid) {
            if ($this->tableHasColumns('usr_login', ['loginid'])) {
                $db->executeTransaction('DELETE FROM usr_login WHERE loginid = ?', [$loginid]);
            }
        }

        foreach ($this->cleanup['usr_role'] as $roleid) {
            if ($this->tableHasColumns('usr_role', ['roleid'])) {
                $db->executeTransaction('DELETE FROM usr_role WHERE roleid = ?', [$roleid]);
            }
        }

        foreach ($this->cleanup['sys_geo_codex'] as $geo) {
            if ($this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
                $db->executeTransaction(
                    'DELETE FROM sys_geo_codex WHERE geo_level = ? AND geo_level_id = ?',
                    [$geo['geo_level'], $geo['geo_level_id']]
                );
            }
        }

        parent::tearDown();
    }

    /**
     * Test login with invalid password
     */
    public function testInactiveUserCannotLogin()
    {
        $this->requireLoginSchema();

        $roleId = $this->seedRole();
        $geo = $this->seedGeoCodex();
        $user = $this->seedUser([
            'roleid' => $roleId,
            'geo_level' => $geo['geo_level'],
            'geo_level_id' => $geo['geo_level_id'],
            'active' => 0,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
        ]);
        $this->seedIdentity($user['userid']);

        $login = new Login();
        $login->SetLoginId($user['loginid'], 'TestPass123');

        $this->assertFalse($login->RunLogin());
        $this->assertTrue($login->IsLoginIdValid);
        $this->assertSame('Your account is not active', $login->LastError);
    }

    /**
     * Test login session creation
     */
    public function testLoginCreatesSession()
    {
        $this->requireLoginSchema();

        $roleId = $this->seedRole();
        $geo = $this->seedGeoCodex();
        $user = $this->seedUser([
            'roleid' => $roleId,
            'geo_level' => $geo['geo_level'],
            'geo_level_id' => $geo['geo_level_id'],
            'active' => 1,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
        ]);
        $this->seedIdentity($user['userid']);

        $login = new Login();
        $login->SetLoginId($user['loginid'], 'TestPass123');

        $this->assertTrue($login->RunLogin());
        $data = $login->GetLoginData();
        $this->assertSame($user['loginid'], $data['loginid']);
        $this->assertNotEmpty($data['guid']);
    }

    public function testInvalidLoginIdFails(): void
    {
        $this->requireLoginSchema();

        $login = new Login();
        $login->SetLoginId('missing.user', 'TestPass123');

        $this->assertFalse($login->RunLogin());
        $this->assertFalse($login->IsLoginIdValid);
        $this->assertSame('Invalid login information', $login->LastError);
    }

    public function testBadgeLoginFailsWhenGuidMismatch(): void
    {
        $this->requireLoginSchema();

        $roleId = $this->seedRole();
        $geo = $this->seedGeoCodex();
        $user = $this->seedUser([
            'roleid' => $roleId,
            'geo_level' => $geo['geo_level'],
            'geo_level_id' => $geo['geo_level_id'],
            'active' => 1,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'guid' => md5(uniqid('', true)),
        ]);
        $this->seedIdentity($user['userid']);

        $login = new Login('badge');
        $login->SetBadge($user['loginid'] . '|invalid-guid');

        $this->assertFalse($login->RunLogin());
        $this->assertSame('Your badge value was incorrect', $login->LastError);
    }

    public function testBadgeLoginFailsWithInvalidBadgeFormat(): void
    {
        $this->requireLoginSchema();

        $login = new Login('badge');
        $login->SetBadge('invalid-badge');

        $this->assertFalse($login->RunLogin());
        $this->assertSame('Invalid login information', $login->LastError);
    }

    public function testBadgeLoginSucceedsWithValidGuid(): void
    {
        $this->requireLoginSchema();

        $roleId = $this->seedRole();
        $geo = $this->seedGeoCodex();
        $guid = md5(uniqid('', true));
        $user = $this->seedUser([
            'roleid' => $roleId,
            'geo_level' => $geo['geo_level'],
            'geo_level_id' => $geo['geo_level_id'],
            'active' => 1,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'guid' => $guid,
        ]);
        $this->seedIdentity($user['userid']);

        $login = new Login('badge');
        $login->SetBadge($user['loginid'] . '|' . $guid);

        $this->assertTrue($login->RunLogin());
        $this->assertTrue($login->IsLoginSuccessful);
    }

    private function requireLoginSchema(): void
    {
        $loginColumns = [
            'userid',
            'loginid',
            'username',
            'pwd',
            'guid',
            'roleid',
            'geo_level',
            'geo_level_id',
            'user_group',
            'active',
            'is_change_password',
        ];

        $roleColumns = [
            'roleid',
            'title',
            'system_privilege',
            'platform',
            'module',
            'priority',
            'role_code',
        ];

        $identityColumns = ['userid', 'first', 'middle', 'last'];
        $geoColumns = ['geo_level', 'geo_level_id', 'geo_value', 'title', 'geo_string'];

        if (!$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_role', $roleColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
        ) {
            $this->markTestSkipped('Required authentication schema not available');
        }
    }

    private function seedRole(array $overrides = []): int
    {
        $roleId = random_int(900000, 909999);
        $data = array_merge([
            'roleid' => $roleId,
            'title' => 'Test Role',
            'role_code' => 'TEST',
            'system_privilege' => json_encode([['name' => 'login']]),
            'platform' => json_encode([['name' => 'web'], ['name' => 'mobile']]),
            'module' => json_encode([['name' => 'auth']]),
            'priority' => 1,
        ], $overrides);

        $this->insertRow('usr_role', $data);
        $this->cleanup['usr_role'][] = $roleId;

        return $roleId;
    }

    private function seedGeoCodex(): array
    {
        $data = [
            'geo_level' => 'ward',
            'geo_level_id' => random_int(960000, 969999),
            'geo_value' => 'Test Ward',
            'title' => 'Test Ward Title',
            'geo_string' => 'Test Ward String',
        ];

        $this->insertRow('sys_geo_codex', $data);
        $this->cleanup['sys_geo_codex'][] = [
            'geo_level' => $data['geo_level'],
            'geo_level_id' => $data['geo_level_id'],
        ];

        return $data;
    }

    private function seedUser(array $overrides = []): array
    {
        $loginid = 'test.login.' . uniqid();
        $userid = random_int(900000, 999999);
        $data = array_merge([
            'userid' => $userid,
            'loginid' => $loginid,
            'username' => 'Test User',
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'guid' => md5(uniqid('', true)),
            'roleid' => 1,
            'geo_level' => 'ward',
            'geo_level_id' => 6001,
            'user_group' => 'test',
            'active' => 1,
            'is_change_password' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ], $overrides);

        $this->insertRow('usr_login', $data);
        $this->cleanup['usr_login'][] = $loginid;

        return [
            'userid' => $data['userid'],
            'loginid' => $data['loginid'],
        ];
    }

    private function seedIdentity(int $userid): void
    {
        $data = [
            'userid' => $userid,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
        ];

        $this->insertRow('usr_identity', $data);
        $this->cleanup['usr_identity'][] = $userid;
    }

    private function seedDeviceRegistry(string $serial): void
    {
        $data = [
            'serial_no' => $serial,
            'connected' => null,
            'connected_loginid' => null,
        ];

        $this->insertRow('sys_device_registry', $data);
        $this->cleanup['sys_device_registry'][] = $serial;
        $this->cleanup['sys_device_login'][] = $serial;
    }

    private function insertRow(string $table, array $data): void
    {
        $columns = $this->getColumns($table);
        if (empty($columns)) {
            $this->markTestSkipped("Missing table {$table}");
        }

        $filtered = array_intersect_key($data, array_flip($columns));
        \DbHelper::Insert($table, $filtered);
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function getColumns(string $table): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SHOW COLUMNS FROM ' . $table);
        if (count($rows) === 0) {
            return [];
        }

        return array_map(fn ($row) => $row['Field'], $rows);
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        $existing = $this->getColumns($table);
        if (empty($existing)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!in_array($column, $existing, true)) {
                return false;
            }
        }

        return true;
    }
}
