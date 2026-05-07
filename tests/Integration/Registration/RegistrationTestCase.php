<?php

namespace Tests\Integration\Registration;

use Tests\TestCase;

abstract class RegistrationTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'smc_child_household' => ['hhid' => [], 'hh_token' => []],
        'smc_child' => ['child_id' => [], 'beneficiary_id' => []],
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/smc/registration.cont.php';
        require_once $this->projectRoot . '/lib/controller/system/general.cont.php';
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
            'wardid' => $wardId,
        ]);
        $this->recordCleanup('ms_geo_comm', 'comid', $commId);

        $this->insertRow('sys_geo_codex', [
            'geo_level' => 'dp',
            'geo_level_id' => $dpId,
            'geo_value' => $dpId,
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $commId,
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $dpId);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $commId,
        ];
    }

    protected function seedHousehold(string $token, array $data = []): int
    {
        $payload = array_merge([
            'hh_token' => $token,
            'dpid' => $data['dpid'] ?? null,
            'hoh_name' => $data['hoh_name'] ?? null,
            'hoh_phone' => $data['hoh_phone'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'device_serial' => $data['device_serial'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'created' => $data['created'] ?? date('Y-m-d H:i:s'),
            'updated' => $data['updated'] ?? date('Y-m-d H:i:s'),
        ], $data);

        $id = $this->insertRow('smc_child_household', $payload);
        if ($id) {
            $this->recordCleanup('smc_child_household', 'hhid', $id);
        }
        $this->recordCleanup('smc_child_household', 'hh_token', $token);
        return (int) $id;
    }

    protected function seedChild(string $beneficiaryId, array $data = []): int
    {
        $payload = array_merge([
            'beneficiary_id' => $beneficiaryId,
            'hh_token' => $data['hh_token'] ?? null,
            'dpid' => $data['dpid'] ?? null,
            'name' => $data['name'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'device_serial' => $data['device_serial'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'created' => $data['created'] ?? date('Y-m-d H:i:s'),
            'updated' => $data['updated'] ?? date('Y-m-d H:i:s'),
        ], $data);

        $id = $this->insertRow('smc_child', $payload);
        if ($id) {
            $this->recordCleanup('smc_child', 'child_id', $id);
        }
        $this->recordCleanup('smc_child', 'beneficiary_id', $beneficiaryId);
        return (int) $id;
    }

    protected function requireHouseholdSchema(array $columns): void
    {
        if (!$this->tableHasColumns('smc_child_household', $columns)) {
            $this->markTestSkipped('Missing smc_child_household schema');
        }
    }

    protected function requireChildSchema(array $columns): void
    {
        if (!$this->tableHasColumns('smc_child', $columns)) {
            $this->markTestSkipped('Missing smc_child schema');
        }
    }

    protected function requireGeoSchema(array $columns): void
    {
        $required = [
            'ms_geo_state' => ['StateId', 'Fullname'],
            'ms_geo_lga' => ['LgaId', 'StateId'],
            'ms_geo_ward' => ['wardid', 'lgaid'],
            'ms_geo_dp' => ['dpid', 'wardid'],
        ];
        foreach ($required as $table => $cols) {
            if (!$this->tableHasColumns($table, $cols)) {
                $this->markTestSkipped("Missing {$table} schema");
            }
        }

        if (in_array('community', $columns, true) && !$this->tableHasColumns('ms_geo_comm', ['comid', 'dpid'])) {
            $this->markTestSkipped('Missing ms_geo_comm schema');
        }

        if (in_array('sys_geo_codex', $columns, true) && !$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'title'])) {
            $this->markTestSkipped('Missing sys_geo_codex schema');
        }
    }
}
