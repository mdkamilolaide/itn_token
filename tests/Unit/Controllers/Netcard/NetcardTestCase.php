<?php

namespace Tests\Unit\Controllers\Netcard;

use Tests\TestCase;

abstract class NetcardTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
        'sys_default_settings' => ['id' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'nc_netcard' => ['ncid' => [], 'uuid' => []],
        'nc_netcard_allocation' => ['atid' => []],
        'nc_netcard_allocation_order' => ['orderid' => []],
        'nc_netcard_allocation_online' => ['id' => []],
        'nc_netcard_movement' => ['mtid' => []],
        'nc_token' => ['tokenid' => [], 'uuid' => [], 'serial_no' => []],
        'nc_token_batch' => ['batchid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/netcard/etoken.cont.php';
        require_once $this->projectRoot . '/lib/controller/netcard/netcard.cont.php';
        require_once $this->projectRoot . '/lib/controller/netcard/netcardTrans.cont.php';
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

    protected function seedGeoHierarchy(string $prefix): array
    {
        $stateId = random_int(700, 799);
        $lgaId = random_int(5000, 5999);
        $wardId = random_int(6000, 6999);
        $dpId = random_int(8000, 8999);
        $commId = random_int(9000, 9999);

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
            ]);
            $this->recordCleanup('ms_geo_comm', 'comid', $commId);
        } else {
            $commId = 0;
        }

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $commId,
        ];
    }

    protected function seedSysGeoCodex(string $level, int $levelId, array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $data = [
            'guid' => md5(uniqid('', true)),
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level_id' => $levelId,
            'geo_level' => $level,
            'geo_value' => strtoupper($level),
            'title' => strtoupper($level),
            'geo_string' => "{$level}-{$levelId}",
        ];
        $this->insertRow('sys_geo_codex', $data);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $levelId);
    }

    protected function seedUser(int $userid, int $wardid, string $loginId = ''): void
    {
        $login = $loginId ?: 'user.' . $userid;
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => $login,
            'username' => 'User ' . $userid,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => 1,
            'geo_level' => 'ward',
            'geo_level_id' => $wardid,
            'user_group' => 'test',
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->insertRow('usr_identity', [
            'userid' => $userid,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
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

    protected function seedToken(array $data): int
    {
        $id = $this->insertRow('nc_token', $data);
        if ($id) {
            $this->recordCleanup('nc_token', 'tokenid', $id);
        }
        if (isset($data['uuid'])) {
            $this->recordCleanup('nc_token', 'uuid', $data['uuid']);
        }
        if (isset($data['serial_no'])) {
            $this->recordCleanup('nc_token', 'serial_no', $data['serial_no']);
        }
        return (int) $id;
    }

    protected function seedDefaultSettings(int $stateId): void
    {
        $this->insertRow('sys_default_settings', [
            'id' => 1,
            'stateid' => $stateId,
        ]);
        $this->recordCleanup('sys_default_settings', 'id', 1);
    }
}
