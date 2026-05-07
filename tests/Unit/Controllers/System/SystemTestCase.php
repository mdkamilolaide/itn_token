<?php

namespace Tests\Unit\Controllers\System;

use Tests\TestCase;

// Namespace-level stubs for external dependencies used by System\Fcm.
if (!function_exists(__NAMESPACE__ . '\\curl_init')) {
    function curl_init()
    {
        return (object) ['opts' => []];
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_setopt')) {
    function curl_setopt($ch, $option, $value)
    {
        $ch->opts[$option] = $value;
        return true;
    }
}
if (!function_exists(__NAMESPACE__ . '\\curl_exec')) {
    function curl_exec($ch)
    {
        if (!empty($GLOBALS['__force_curl_error__'])) {
            return false;
        }
        return true;
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
if (!function_exists(__NAMESPACE__ . '\\WriteToFile')) {
    function WriteToFile($filename, $content)
    {
        $GLOBALS['__system_test_logs__'][] = ['file' => $filename, 'content' => $content];
    }
}

abstract class SystemTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'sys_device_registry' => ['id' => [], 'serial_no' => [], 'device_id' => []],
        'sys_bank_code' => ['bank_code' => []],
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'ms_geo_cluster' => ['clusterid' => []],
        'sys_geo_codex' => ['geo_level_id' => [], 'guid' => []],
        'sys_geo_level' => ['id' => []],
        'sys_default_settings' => ['id' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'usr_role' => ['roleid' => []],
        'usr_user_activity' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/system/devices.cont.php';
        require_once $this->projectRoot . '/lib/controller/system/fcm.cont.php';
        require_once $this->projectRoot . '/lib/controller/system/general.cont.php';
        require_once $this->projectRoot . '/lib/controller/system/login.cont.php';

        $GLOBALS['__system_test_logs__'] = [];
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

    protected function seedDefaultSettings(int $stateId, string $title = 'Default'): void
    {
        $id = $this->insertRow('sys_default_settings', [
            'id' => 1,
            'state' => $title,
            'stateid' => $stateId,
            'title' => $title,
        ]);
        if ($id) {
            $this->recordCleanup('sys_default_settings', 'id', 1);
        }
    }

    protected function seedGeoHierarchy(string $prefix): array
    {
        $stateId = random_int(700, 799);
        $lgaId = random_int(5000, 5999);
        $wardId = random_int(6000, 6999);
        $dpId = random_int(8000, 8999);
        $commId = random_int(9000, 9999);
        $clusterId = random_int(3000, 3999);

        if ($this->tableHasColumns('ms_geo_state', ['StateId', 'Fullname'])) {
            $this->insertRow('ms_geo_state', [
                'StateId' => $stateId,
                'Fullname' => "{$prefix} State",
            ]);
            $this->recordCleanup('ms_geo_state', 'StateId', $stateId);
        }

        if ($this->tableHasColumns('ms_geo_lga', ['LgaId', 'Fullname', 'StateId'])) {
            $this->insertRow('ms_geo_lga', [
                'LgaId' => $lgaId,
                'Fullname' => "{$prefix} LGA",
                'StateId' => $stateId,
            ]);
            $this->recordCleanup('ms_geo_lga', 'LgaId', $lgaId);
        }

        if ($this->tableHasColumns('ms_geo_ward', ['wardid', 'ward', 'lgaid'])) {
            $this->insertRow('ms_geo_ward', [
                'wardid' => $wardId,
                'ward' => "{$prefix} Ward",
                'lgaid' => $lgaId,
            ]);
            $this->recordCleanup('ms_geo_ward', 'wardid', $wardId);
        }

        if ($this->tableHasColumns('ms_geo_dp', ['dpid', 'dp', 'wardid'])) {
            $this->insertRow('ms_geo_dp', [
                'dpid' => $dpId,
                'dp' => "{$prefix} DP",
                'wardid' => $wardId,
            ]);
            $this->recordCleanup('ms_geo_dp', 'dpid', $dpId);
        }

        if ($this->tableHasColumns('ms_geo_comm', ['comid', 'community', 'dpid'])) {
            $this->insertRow('ms_geo_comm', [
                'comid' => $commId,
                'community' => "{$prefix} Community",
                'dpid' => $dpId,
                'wardid' => $wardId,
            ]);
            $this->recordCleanup('ms_geo_comm', 'comid', $commId);
        }

        if ($this->tableHasColumns('ms_geo_cluster', ['clusterid', 'cluster', 'lgaid'])) {
            $this->insertRow('ms_geo_cluster', [
                'clusterid' => $clusterId,
                'cluster' => "{$prefix} Cluster",
                'lgaid' => $lgaId,
            ]);
            $this->recordCleanup('ms_geo_cluster', 'clusterid', $clusterId);
        }

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $commId,
            'clusterid' => $clusterId,
        ];
    }

    protected function seedGeoCodex(string $level, int $levelId, array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $guid = md5(uniqid('', true));
        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => $level,
            'geo_level_id' => $levelId,
            'geo_value' => $level,
            'title' => strtoupper($level),
            'geo_string' => strtoupper($level) . '-' . $levelId,
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $levelId);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);
    }

    protected function seedUser(int $userId, string $geoLevel, int $geoLevelId, int $roleId, string $roleCode): void
    {
        if ($this->tableHasColumns('usr_role', ['roleid', 'role_code', 'title'])) {
            $this->insertRow('usr_role', [
                'roleid' => $roleId,
                'role_code' => $roleCode,
                'title' => 'Role ' . $roleId,
            ]);
            $this->recordCleanup('usr_role', 'roleid', $roleId);
        }

        $this->insertRow('usr_login', [
            'userid' => $userId,
            'loginid' => 'user.' . $userId,
            'username' => 'User ' . $userId,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => $roleId,
            'geo_level' => $geoLevel,
            'geo_level_id' => $geoLevelId,
            'user_group' => 'test',
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->insertRow('usr_identity', [
            'userid' => $userId,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
            'gender' => 'M',
            'phone' => '08000000000',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
    }

    protected function seedDevice(array $data): int
    {
        $id = $this->insertRow('sys_device_registry', $data);
        if ($id) {
            $this->recordCleanup('sys_device_registry', 'id', $id);
        }
        if (isset($data['serial_no'])) {
            $this->recordCleanup('sys_device_registry', 'serial_no', $data['serial_no']);
        }
        if (isset($data['device_id'])) {
            $this->recordCleanup('sys_device_registry', 'device_id', $data['device_id']);
        }
        return (int) $id;
    }
}
