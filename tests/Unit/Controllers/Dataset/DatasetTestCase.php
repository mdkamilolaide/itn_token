<?php

namespace Tests\Unit\Controllers\Dataset;

use PHPUnit\Framework\TestCase;

abstract class DatasetTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['dis_id' => [], 'etoken_serial' => []],
        'hhm_gs_net_serial' => ['snid' => []],
        'hhm_gs_net_verification' => ['snid' => []],
        'nc_token' => ['tokenid' => [], 'serial_no' => []],
        'ms_product_sgtin' => ['sgtinid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/dataset/pbi.php';
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

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ];
    }

    protected function seedToken(string $serial): int
    {
        $id = $this->insertRow('nc_token', [
            'serial_no' => $serial,
            'token_code' => $serial,
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('nc_token', 'tokenid', $id);
        }
        if ($this->columnExists('nc_token', 'serial_no')) {
            $this->recordCleanup('nc_token', 'serial_no', $serial);
        }
        return (int) $id;
    }

    protected function seedDistribution(array $data): int
    {
        $id = $this->insertRow('hhm_distribution', $data);
        if ($id) {
            $this->recordCleanup('hhm_distribution', 'dis_id', $id);
        }
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_distribution', 'etoken_serial', $data['etoken_serial']);
        }
        return (int) $id;
    }

    protected function seedMobilization(array $data): int
    {
        $id = $this->insertRow('hhm_mobilization', $data);
        if ($id) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $id);
        }
        return (int) $id;
    }

    protected function requireGeoSchema(): void
    {
        $tables = [
            'ms_geo_state' => ['StateId', 'Fullname'],
            'ms_geo_lga' => ['LgaId', 'StateId'],
            'ms_geo_ward' => ['wardid', 'lgaid'],
            'ms_geo_dp' => ['dpid', 'wardid'],
        ];

        foreach ($tables as $table => $cols) {
            foreach ($cols as $col) {
                if (!$this->columnExists($table, $col)) {
                    $this->markTestSkipped('Missing geo schema');
                }
            }
        }
    }

    protected function requireGsSchema(): void
    {
        $required = [
            'hhm_gs_net_serial' => ['snid', 'dis_id', 'gtin', 'sgtin', 'batch', 'expiry', 'is_verified'],
            'hhm_distribution' => ['dis_id', 'etoken_id', 'dp_id', 'collected_date'],
            'nc_token' => ['tokenid', 'serial_no'],
            'hhm_gs_net_verification' => ['snid', 'status'],
        ];

        foreach ($required as $table => $cols) {
            foreach ($cols as $col) {
                if (!$this->columnExists($table, $col)) {
                    $this->markTestSkipped('Missing GS schema');
                }
            }
        }
    }

    protected function requireSummarySchema(): void
    {
        $tables = [
            'ms_product_sgtin' => ['sgtinid'],
            'hhm_mobilization' => ['family_size', 'allocated_net'],
            'hhm_distribution' => ['collected_nets'],
        ];

        foreach ($tables as $table => $cols) {
            foreach ($cols as $col) {
                if (!$this->columnExists($table, $col)) {
                    $this->markTestSkipped('Missing summary schema');
                }
            }
        }
    }
}
