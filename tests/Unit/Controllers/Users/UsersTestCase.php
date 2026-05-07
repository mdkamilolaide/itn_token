<?php

namespace Users;

if (!function_exists(__NAMESPACE__ . '\\curl_init')) {
    function curl_init()
    {
        return (object) [];
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_setopt_array')) {
    function curl_setopt_array($ch, $options)
    {
        return true;
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_exec')) {
    function curl_exec($ch)
    {
        if (!empty($GLOBALS['__users_curl_error__'])) {
            return false;
        }
        return $GLOBALS['__users_curl_response__'] ?? json_encode([
            'status' => false,
            'message' => 'stub',
            'data' => [],
        ]);
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_error')) {
    function curl_error($ch)
    {
        return 'curl error';
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_close')) {
    function curl_close($ch)
    {
        return true;
    }
}

namespace Tests\Unit\Controllers\Users;

use Tests\TestCase;

abstract class UsersTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'usr_login' => ['userid' => [], 'loginid' => [], 'username' => []],
        'usr_identity' => ['userid' => []],
        'usr_finance' => ['userid' => []],
        'usr_security' => ['userid' => []],
        'usr_role' => ['roleid' => []],
        'sys_geo_codex' => ['geo_level_id' => [], 'guid' => []],
        'sys_device_registry' => ['serial_no' => [], 'id' => []],
        'sys_device_login' => ['device_serial' => []],
        'sys_bank_code' => ['bank_code' => []],
        'usr_workhour_extension' => ['id' => []],
        'sys_working_hours' => ['id' => []],
        'usr_temp' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/users/batchuser.cont.php';
        require_once $this->projectRoot . '/lib/controller/users/bulkuser.cont.php';
        require_once $this->projectRoot . '/lib/controller/users/BulkBankVerification.cont.php';
        require_once $this->projectRoot . '/lib/controller/users/login.cont.php';
        require_once $this->projectRoot . '/lib/controller/users/userManage.cont.php';

        $GLOBALS['__users_curl_response__'] = null;
        $GLOBALS['__users_curl_error__'] = false;
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        foreach ($this->cleanup as $table => $columns) {
            foreach ($columns as $column => $values) {
                if (empty($values) || !$this->columnExists($table, $column)) {
                    continue;
                }
                foreach ($values as $value) {
                    $db->executeTransaction("DELETE FROM {$table} WHERE {$column} = ?", [$value]);
                }
            }
        }

        parent::tearDown();
    }

    protected function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    protected function insertRow(string $table, array $data): ?int
    {
        $columns = $this->getColumns($table);
        if (empty($columns)) {
            $this->markTestSkipped("Missing table {$table}");
        }
        $filtered = array_intersect_key($data, array_flip($columns));
        return \DbHelper::Insert($table, $filtered);
    }

    protected function getColumns(string $table): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SHOW COLUMNS FROM ' . $table);
        if (count($rows) === 0) {
            return [];
        }
        return array_map(static fn ($row) => $row['Field'], $rows);
    }

    protected function tableHasColumns(string $table, array $columns): bool
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

    protected function columnExists(string $table, string $column): bool
    {
        return in_array($column, $this->getColumns($table), true);
    }

    protected function recordCleanup(string $table, string $column, $value): void
    {
        if (!isset($this->cleanup[$table][$column])) {
            return;
        }
        $this->cleanup[$table][$column][] = $value;
    }

    protected function requireSchema(array $requirements): void
    {
        foreach ($requirements as $table => $columns) {
            if (!$this->tableHasColumns($table, $columns)) {
                $this->markTestSkipped("Missing schema for {$table}");
            }
        }
    }

    protected function seedGeoCodex(string $level, int $levelId): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $guid = md5(uniqid('', true));
        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => 1,
            'lgaid' => 1,
            'wardid' => 1,
            'dpid' => 1,
            'geo_level' => $level,
            'geo_level_id' => $levelId,
            'geo_value' => $level,
            'title' => strtoupper($level),
            'geo_string' => strtoupper($level) . '-' . $levelId,
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $levelId);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);
    }

    protected function seedUser(int $userId, string $loginid, string $password, string $geoLevel, int $geoLevelId, int $roleId = 1, string $userGroup = 'test', int $active = 1): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userId,
            'loginid' => $loginid,
            'username' => $loginid,
            'pwd' => password_hash($password, PASSWORD_BCRYPT),
            'hash' => md5($password),
            'guid' => md5(uniqid('', true)),
            'roleid' => $roleId,
            'geo_level' => $geoLevel,
            'geo_level_id' => $geoLevelId,
            'user_group' => $userGroup,
            'active' => $active,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->insertRow('usr_identity', [
            'userid' => $userId,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
            'gender' => 'M',
            'email' => 'user@example.com',
            'phone' => '08000000000',
        ]);
        $this->insertRow('usr_finance', [
            'userid' => $userId,
            'bank_name' => 'Test Bank',
            'bank_code' => '001',
            'account_name' => 'Test User',
            'account_no' => '0001112223',
            'is_verified' => 0,
            'verification_status' => 'pending',
            'verification_count' => 0,
        ]);
        $this->insertRow('usr_security', [
            'userid' => $userId,
            'bio_feature' => 'fingerprint',
        ]);

        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
        $this->recordCleanup('usr_finance', 'userid', $userId);
        $this->recordCleanup('usr_security', 'userid', $userId);
    }
}
