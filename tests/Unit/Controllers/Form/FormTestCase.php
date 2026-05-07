<?php

namespace Tests\Unit\Controllers\Form;

use Tests\TestCase;

abstract class FormTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'sys_geo_codex' => ['dpid' => []],
        'mo_form_end_process' => ['uid' => [], 'id' => []],
        'mo_form_five_revisit' => ['uid' => [], 'id' => []],
        'mo_form_i9a' => ['uid' => [], 'id' => []],
        'mo_form_i9b' => ['uid' => [], 'id' => []],
        'mo_form_i9c' => ['uid' => [], 'id' => []],
        'mo_smc_supervisor_cdd' => ['uid' => [], 'id' => []],
        'mo_smc_supervisor_hfw' => ['uid' => [], 'id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/form/endprocess.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/fiverevisit.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/ininea.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/inineb.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/ininec.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/smccdd.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/smchfw.cont.php';
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

        if ($this->tableHasColumns('sys_geo_codex', ['dpid', 'geo_level'])) {
            $this->insertRow('sys_geo_codex', [
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
            ]);
            $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);
        }

        $comId = $this->seedCommunity($dpId, $wardId, $prefix);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'comid' => $comId,
        ];
    }

    protected function seedCommunity(int $dpId, int $wardId, string $prefix): int
    {
        if (!$this->tableHasColumns('ms_geo_comm', ['comid', 'dpid', 'wardid', 'community'])) {
            return 0;
        }
        $comId = random_int(9000, 9999);
        $this->insertRow('ms_geo_comm', [
            'comid' => $comId,
            'dpid' => $dpId,
            'wardid' => $wardId,
            'community' => "{$prefix} Community",
        ]);
        $this->recordCleanup('ms_geo_comm', 'comid', $comId);
        return $comId;
    }

    protected function requireFormSchema(string $table, array $columns): void
    {
        if (!$this->tableHasColumns($table, $columns)) {
            $this->markTestSkipped("Form schema not available for {$table}");
        }
    }
}
