<?php

namespace Tests\Integration\Mobilization;

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
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'hhm_location_categories' => ['id' => []],
        'hhm_mobilization' => ['hhid' => []],
        'nc_netcard' => ['uuid' => [], 'ncid' => []],
        'nc_token' => ['tokenid' => [], 'serial_no' => []],
        'nc_netcard_download' => ['download_id' => []],
        'sys_default_settings' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/mobilization/mobilization.cont.php';
        require_once $this->projectRoot . '/lib/controller/mobilization/mapdata.cont.php';
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
        return array_map(fn ($row) => $row['Field'], $rows);
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

        if ($this->tableHasColumns('ms_geo_state', ['StateId', 'Fullname'])) {
            $this->insertRow('ms_geo_state', [
                'StateId' => $stateId,
                'Fullname' => "{$prefix} State",
            ]);
            $this->recordCleanup('ms_geo_state', 'StateId', $stateId);
        }

        $this->insertRow('ms_geo_lga', [
            'LgaId' => $lgaId,
            'Fullname' => "{$prefix} LGA",
            'StateId' => $stateId,
        ]);
        $this->recordCleanup('ms_geo_lga', 'LgaId', $lgaId);

        $this->insertRow('ms_geo_ward', [
            'wardid' => $wardId,
            'ward' => "{$prefix} Ward",
            'lgaid' => $lgaId,
        ]);
        $this->recordCleanup('ms_geo_ward', 'wardid', $wardId);

        $this->insertRow('ms_geo_dp', [
            'dpid' => $dpId,
            'dp' => "{$prefix} DP",
            'wardid' => $wardId,
        ]);
        $this->recordCleanup('ms_geo_dp', 'dpid', $dpId);

        $this->insertRow('sys_geo_codex', [
            'guid' => md5(uniqid('', true)),
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'geo_level' => 'dp',
            'geo_level_id' => $dpId,
            'geo_value' => 10,
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
        ]);
        $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);

        return ['stateid' => $stateId, 'lgaid' => $lgaId, 'wardid' => $wardId, 'dpid' => $dpId];
    }

    protected function seedUser(int $userid, string $loginid): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => $loginid,
            'username' => $loginid,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => 1,
            'geo_level' => 'ward',
            'geo_level_id' => 0,
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
            'phone' => '08000000000',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
    }

    protected function seedToken(string $uuid, string $serialNo): int
    {
        $id = $this->insertRow('nc_token', [
            'uuid' => $uuid,
            'serial_no' => $serialNo,
            'status' => 'new',
            'status_code' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('nc_token', 'tokenid', $id);
        }
        $this->recordCleanup('nc_token', 'serial_no', $serialNo);
        return (int) $id;
    }

    protected function seedNetcard(array $data): void
    {
        $defaults = [
            'uuid' => md5(uniqid('', true)),
            'location' => 'stock',
            'location_value' => 40,
            'active' => 1,
            'status' => 'available',
            'updated' => date('Y-m-d H:i:s'),
        ];
        $payload = array_merge($defaults, $data);
        $id = $this->insertRow('nc_netcard', $payload);
        if ($id) {
            $this->recordCleanup('nc_netcard', 'ncid', $id);
        }
        $this->recordCleanup('nc_netcard', 'uuid', $payload['uuid']);
    }

    protected function seedLocationCategory(string $name): int
    {
        $id = $this->insertRow('hhm_location_categories', [
            'location' => $name,
        ]);
        if ($id) {
            $this->recordCleanup('hhm_location_categories', 'id', $id);
        }
        return (int) $id;
    }

    protected function seedDefaultSettings(int $stateId): void
    {
        if ($this->tableHasColumns('sys_default_settings', ['id', 'stateid'])) {
            $this->insertRow('sys_default_settings', [
                'id' => 1,
                'stateid' => $stateId,
                'logo' => 'logo.png',
                'receipt_header' => 'Receipt Header',
            ]);
            $this->recordCleanup('sys_default_settings', 'id', 1);
        }
    }

    protected function seedMobilization(array $data): void
    {
        $defaults = [
            'dp_id' => $data['dp_id'] ?? 0,
            'comid' => 4001,
            'hhm_id' => $data['hhm_id'] ?? 1,
            'co_hhm_id' => 0,
            'hoh_first' => 'Jane',
            'hoh_last' => 'Doe',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'hod_mother' => 'Mary Doe',
            'sleeping_space' => 2,
            'adult_female' => 2,
            'adult_male' => 2,
            'children' => 2,
            'allocated_net' => 2,
            'location_description' => 'Household',
            'longitude' => '7.111',
            'latitude' => '9.222',
            'eolin_have_old_net' => 1,
            'eolin_total_old_net' => 1,
            'netcards' => $data['netcards'] ?? '',
            'etoken_id' => $data['etoken_id'] ?? 0,
            'etoken_serial' => $data['etoken_serial'] ?? 'ET-' . uniqid(),
            'etoken_pin' => '12345',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'collected_date' => $data['collected_date'] ?? '2099-10-01',
            'created' => date('Y-m-d H:i:s'),
        ];
        $payload = array_merge($defaults, $data);
        $this->insertRow('hhm_mobilization', $payload);
        $this->recordCleanup('hhm_mobilization', 'hhid', $payload['hhid'] ?? null);
    }

    protected function requireMobilizationSchema(): void
    {
        $geoColumns = ['dpid', 'lgaid', 'wardid', 'geo_level', 'geo_value'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward', 'lgaid'];
        $dpColumns = ['dpid', 'dp', 'wardid'];
        $mobColumns = ['dp_id', 'hhid', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'longitude', 'Latitude', 'netcards', 'etoken_id', 'etoken_serial', 'etoken_pin', 'collected_date'];
        $netcardColumns = ['uuid', 'location', 'location_value', 'active'];
        $tokenColumns = ['tokenid', 'uuid', 'serial_no', 'status', 'status_code'];
        $downloadColumns = ['download_id', 'device_id', 'userid', 'status', 'is_confirmed', 'is_destroyed', 'netcard_list'];
        $userColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'last', 'phone'];

        if (!$this->tableHasColumns('sys_geo_codex', $geoColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_dp', $dpColumns)
            || !$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('nc_netcard', $netcardColumns)
            || !$this->tableHasColumns('nc_token', $tokenColumns)
            || !$this->tableHasColumns('nc_netcard_download', $downloadColumns)
            || !$this->tableHasColumns('usr_login', $userColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('Mobilization schema not available');
        }
    }
}
