<?php

namespace Tests\Unit\Controllers\Mobilization;

use Tests\TestCase;

abstract class MobilizationTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['dpid' => []],
        'hhm_mobilization' => ['hhid' => [], 'etoken_serial' => []],
        'nc_token' => ['tokenid' => [], 'uuid' => []],
        'nc_netcard' => ['ncid' => [], 'uuid' => []],
        'nc_netcard_download' => ['download_id' => []],
        'nc_netcard_allocation_order' => ['orderid' => []],
        'hhm_location_categories' => ['id' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'sys_default_settings' => ['id' => []],
        'ms_geo_comm' => ['comid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/mobilization/mapdata.cont.php';
        require_once $this->projectRoot . '/lib/controller/mobilization/mobilization.cont.php';
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

    protected function seedGeoHierarchy(string $prefix, int $netCapping = 4): array
    {
        $stateId = random_int(700, 799);
        $lgaId = random_int(5000, 5999);
        $wardId = random_int(6000, 6999);
        $dpId = random_int(8000, 8999);

        if ($this->tableHasColumns('ms_geo_state', ['StateId', 'Fullname'])) {
            $this->insertRow('ms_geo_state', [
                'StateId' => $stateId,
                'Fullname' => "{$prefix} State",
                'longitude' => '10.1',
                'latitude' => '11.2',
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

        if ($this->tableHasColumns('sys_geo_codex', ['dpid', 'geo_level'])) {
            $data = [
                'guid' => md5(uniqid('', true)),
                'stateid' => $stateId,
                'lgaid' => $lgaId,
                'wardid' => $wardId,
                'dpid' => $dpId,
                'geo_level_id' => $dpId,
                'geo_level' => 'dp',
                'geo_value' => "{$prefix} DP",
                'title' => "{$prefix} DP",
                'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            ];
            if ($this->columnExists('sys_geo_codex', 'net_capping')) {
                $data['net_capping'] = $netCapping;
            }
            $this->insertRow('sys_geo_codex', $data);
            $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);
        }

        if ($this->tableHasColumns('ms_geo_comm', ['comid', 'dpid', 'wardid', 'community'])) {
            $comId = random_int(9000, 9999);
            $this->insertRow('ms_geo_comm', [
                'comid' => $comId,
                'dpid' => $dpId,
                'wardid' => $wardId,
                'community' => "{$prefix} Community",
            ]);
            $this->recordCleanup('ms_geo_comm', 'comid', $comId);
        } else {
            $comId = 0;
        }

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $comId,
        ];
    }

    protected function seedUser(int $userId, string $loginId, string $geoLevel, int $geoLevelId): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userId,
            'loginid' => $loginId,
            'username' => 'User ' . $userId,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => 1,
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
            'phone' => '08000000000',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
    }

    protected function seedMobilization(array $data): int
    {
        $id = $this->insertRow('hhm_mobilization', $data);
        if (isset($data['hhid'])) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $data['hhid']);
        }
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_mobilization', 'etoken_serial', $data['etoken_serial']);
        }
        return (int) $id;
    }

    protected function seedToken(array $data): int
    {
        $id = $this->insertRow('nc_token', $data);
        if ($id) {
            $this->recordCleanup('nc_token', 'tokenid', $id);
        }
        if (isset($data['uuid'])) {
            $this->recordCleanup('nc_token', 'uuid', $data['uuid']);
        }
        return (int) $id;
    }

    protected function seedNetcard(array $data): int
    {
        $id = $this->insertRow('nc_netcard', $data);
        if ($id) {
            $this->recordCleanup('nc_netcard', 'ncid', $id);
        }
        if (isset($data['uuid'])) {
            $this->recordCleanup('nc_netcard', 'uuid', $data['uuid']);
        }
        return (int) $id;
    }

    protected function seedDefaultSettings(array $data): int
    {
        $id = $this->insertRow('sys_default_settings', $data);
        if ($id) {
            $this->recordCleanup('sys_default_settings', 'id', $id);
        }
        return (int) $id;
    }
}
