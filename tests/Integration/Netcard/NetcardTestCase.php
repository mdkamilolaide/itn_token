<?php

namespace Tests\Integration\Netcard;

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
        'nc_netcard_unused_pushed' => ['id' => []],
        'nc_netcard_unlocked_log' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
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

        $this->insertRow('ms_geo_state', [
            'StateId' => $stateId,
            'Fullname' => "{$prefix} State",
            'StateName' => "{$prefix} State",
        ]);
        $this->recordCleanup('ms_geo_state', 'StateId', $stateId);

        $this->insertRow('ms_geo_lga', [
            'LgaId' => $lgaId,
            'Fullname' => "{$prefix} LGA",
            'LgaName' => "{$prefix} LGA",
            'StateId' => $stateId,
        ]);
        $this->recordCleanup('ms_geo_lga', 'LgaId', $lgaId);

        $this->insertRow('ms_geo_ward', [
            'wardid' => $wardId,
            'ward' => "{$prefix} Ward",
            'ward_name' => "{$prefix} Ward",
            'lgaid' => $lgaId,
        ]);
        $this->recordCleanup('ms_geo_ward', 'wardid', $wardId);

        $this->insertRow('ms_geo_dp', [
            'dpid' => $dpId,
            'dp' => "{$prefix} DP",
            'dp_name' => "{$prefix} DP",
            'wardid' => $wardId,
        ]);
        $this->recordCleanup('ms_geo_dp', 'dpid', $dpId);

        $this->insertRow('ms_geo_comm', [
            'comid' => $commId,
            'community' => "{$prefix} Community",
            'comm_name' => "{$prefix} Community",
            'dpid' => $dpId,
        ]);
        $this->recordCleanup('ms_geo_comm', 'comid', $commId);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $commId,
        ];
    }

    protected function seedUser(int $userid, int $wardid): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => 'user.' . $userid,
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

    protected function seedNetcards(int $count, array $overrides = []): array
    {
        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $uuid = 'NC-' . uniqid('', true);
            $data = array_merge([
                'uuid' => $uuid,
                'netcard_code' => $uuid,
                'tokenid' => null,
                'active' => 1,
                'location' => 'ward',
                'location_value' => 60,
                'geo_level' => 'ward',
                'geo_level_id' => $overrides['wardid'] ?? null,
                'stateid' => $overrides['stateid'] ?? null,
                'lgaid' => $overrides['lgaid'] ?? null,
                'wardid' => $overrides['wardid'] ?? null,
                'mobilizer_userid' => $overrides['mobilizer_userid'] ?? null,
                'device_serial' => $overrides['device_serial'] ?? null,
                'atid' => $overrides['atid'] ?? null,
                'status' => $overrides['status'] ?? 'seed',
                'updated' => date('Y-m-d H:i:s'),
                'created' => date('Y-m-d H:i:s'),
            ], $overrides);

            $id = $this->insertRow('nc_netcard', $data);
            if ($id) {
                $this->recordCleanup('nc_netcard', 'ncid', $id);
            }
            if ($this->columnExists('nc_netcard', 'uuid')) {
                $this->recordCleanup('nc_netcard', 'uuid', $uuid);
            }
            $uuids[] = $uuid;
        }

        return $uuids;
    }

    protected function requireAllocationSchema(): void
    {
        $netcardColumns = [
            'location',
            'location_value',
            'geo_level',
            'geo_level_id',
            'mobilizer_userid',
            'atid',
            'wardid',
            'lgaid',
            'status',
            'updated',
        ];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)) {
            $this->markTestSkipped('Missing nc_netcard allocation columns');
        }

        $allocColumns = ['userid', 'total', 'a_type', 'origin', 'origin_id', 'destination', 'destination_userid', 'created'];
        if (!$this->tableHasColumns('nc_netcard_allocation', $allocColumns)) {
            $this->markTestSkipped('Missing nc_netcard_allocation schema');
        }
    }

    protected function requireMovementSchema(): void
    {
        $netcardColumns = [
            'location',
            'location_value',
            'geo_level',
            'geo_level_id',
            'lgaid',
            'wardid',
            'state_mtid',
            'status',
            'updated',
        ];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)) {
            $this->markTestSkipped('Missing nc_netcard movement columns');
        }

        $movementColumns = ['userid', 'total', 'move_type', 'origin_level', 'origin_level_id', 'destination_level', 'destination_level_id', 'created'];
        if (!$this->tableHasColumns('nc_netcard_movement', $movementColumns)) {
            $this->markTestSkipped('Missing nc_netcard_movement schema');
        }
    }

    protected function requireReverseSchema(): void
    {
        $netcardColumns = [
            'location',
            'location_value',
            'mobilizer_userid',
            'atid',
            'device_serial',
            'status',
            'updated',
        ];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)) {
            $this->markTestSkipped('Missing nc_netcard reverse columns');
        }

        $onlineColumns = ['hhm_id', 'requester_id', 'amount', 'created'];
        if (!$this->tableHasColumns('nc_netcard_allocation_online', $onlineColumns)) {
            $this->markTestSkipped('Missing nc_netcard_allocation_online schema');
        }
    }

    protected function requirePushSchema(): void
    {
        $netcardColumns = ['uuid', 'location_value', 'device_serial', 'status'];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)) {
            $this->markTestSkipped('Missing nc_netcard push columns');
        }

        $pushColumns = ['hhm_id', 'device_serial', 'amount', 'created'];
        if (!$this->tableHasColumns('nc_netcard_unused_pushed', $pushColumns)) {
            $this->markTestSkipped('Missing nc_netcard_unused_pushed schema');
        }
    }

    protected function requireUnlockSchema(): void
    {
        $netcardColumns = ['mobilizer_userid', 'device_serial', 'location_value', 'location', 'atid', 'status'];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)) {
            $this->markTestSkipped('Missing nc_netcard unlock columns');
        }

        $unlockColumns = ['hhm_id', 'requester_id', 'device_serial', 'amount', 'created'];
        if (!$this->tableHasColumns('nc_netcard_unlocked_log', $unlockColumns)) {
            $this->markTestSkipped('Missing nc_netcard_unlocked_log schema');
        }
    }
}
