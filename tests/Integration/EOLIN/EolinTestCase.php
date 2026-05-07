<?php

namespace Tests\Integration\EOLIN;

use Tests\TestCase;

abstract class EolinTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['dpid' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['etoken_serial' => [], 'dis_id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/eolin.cont.php';
        require_once $this->projectRoot . '/lib/controller/distribution/distribution.cont.php';
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
            'geo_value' => "{$prefix} DP",
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
        ]);
        $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);

        return ['stateid' => $stateId, 'lgaid' => $lgaId, 'wardid' => $wardId, 'dpid' => $dpId];
    }

    protected function seedMobilization(array $data): void
    {
        $defaults = [
            'hhid' => 'HH-' . uniqid(),
            'dp_id' => $data['dp_id'] ?? 0,
            'hoh_first' => 'Test',
            'hoh_last' => 'User',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'allocated_net' => 2,
            'location_description' => 'Test Location',
            'netcards' => 2,
            'etoken_id' => 1,
            'etoken_serial' => 'ET-' . uniqid(),
            'etoken_pin' => '1234',
            'collected_date' => '2099-09-01',
            'longitude' => '0',
            'latitude' => '0',
            'eolin_have_old_net' => 0,
            'eolin_total_old_net' => 0,
            'created' => date('Y-m-d H:i:s'),
        ];

        $payload = array_merge($defaults, $data);
        $this->insertRow('hhm_mobilization', $payload);
        $this->recordCleanup('hhm_mobilization', 'hhid', $payload['hhid']);
    }

    protected function seedDistribution(array $data): int
    {
        $id = $this->insertRow('hhm_distribution', $data);
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_distribution', 'etoken_serial', $data['etoken_serial']);
        }
        if ($id) {
            $this->recordCleanup('hhm_distribution', 'dis_id', $id);
        }
        return (int) $id;
    }

    protected function requireEolinSchema(): void
    {
        $geoColumns = ['dpid', 'lgaid', 'wardid', 'geo_level'];
        $stateColumns = ['StateId', 'Fullname'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward', 'lgaid'];
        $dpColumns = ['dpid', 'dp', 'wardid'];
        $mobColumns = ['hhid', 'dp_id', 'eolin_have_old_net', 'eolin_total_old_net'];
        $distColumns = ['dp_id', 'hhid', 'eolin_bring_old_net', 'eolin_total_old_net'];
        $hasStateTable = !empty($this->getColumns('ms_geo_state'));

        if (!$this->tableHasColumns('sys_geo_codex', $geoColumns)
            || ($hasStateTable && !$this->tableHasColumns('ms_geo_state', $stateColumns))
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_dp', $dpColumns)
            || !$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('hhm_distribution', $distColumns)
        ) {
            $this->markTestSkipped('EOLIN schema not available');
        }
    }

    protected function requireBulkSchema(): void
    {
        $distColumns = ['dp_id', 'hhid', 'etoken_id', 'etoken_serial', 'recorder_id', 'distributor_id', 'collected_nets', 'is_gs_net', 'gs_net_serial', 'longitude', 'latitude', 'device_serial', 'app_version', 'eolin_bring_old_net', 'eolin_total_old_net', 'collected_date'];
        if (!$this->tableHasColumns('hhm_distribution', $distColumns)) {
            $this->markTestSkipped('Distribution bulk schema not available');
        }
    }
}
