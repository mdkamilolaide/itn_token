<?php

namespace Tests\Integration\Monitoring;

use Tests\TestCase;

abstract class MonitoringTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'smc_period' => ['periodid' => []],
        'mo_form_i9a' => ['uid' => []],
        'mo_form_i9b' => ['uid' => []],
        'mo_form_i9c' => ['uid' => []],
        'mo_form_five_revisit' => ['uid' => []],
        'mo_form_end_process' => ['uid' => []],
        'mo_smc_supervisor_cdd' => ['uid' => []],
        'mo_smc_supervisor_hfw' => ['uid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/monitor/monitor.cont.php';
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
        ]);
        $this->recordCleanup('ms_geo_state', 'StateId', $stateId);

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

        $this->insertRow('ms_geo_comm', [
            'comid' => $commId,
            'community' => "{$prefix} Community",
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

    protected function seedUser(int $userid): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => 'user.' . $userid,
            'username' => 'User ' . $userid,
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
        ]);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
    }

    protected function seedSmcPeriod(string $title): int
    {
        $columns = $this->getColumns('smc_period');
        if (!in_array('periodid', $columns, true)) {
            $this->markTestSkipped('Missing smc_period.periodid');
        }
        $titleColumn = in_array('title', $columns, true) ? 'title' : (in_array('period_name', $columns, true) ? 'period_name' : null);
        if ($titleColumn === null) {
            $this->markTestSkipped('Missing smc_period title column');
        }

        $periodId = $this->insertRow('smc_period', [
            $titleColumn => $title,
            'created' => date('Y-m-d H:i:s'),
        ]);

        if ($periodId) {
            $this->recordCleanup('smc_period', 'periodid', $periodId);
        }

        return (int) $periodId;
    }

    protected function requireStatusSchema(): void
    {
        $tables = [
            'mo_form_end_process',
            'mo_form_five_revisit',
            'mo_form_i9a',
            'mo_form_i9b',
            'mo_form_i9c',
            'mo_smc_supervisor_cdd',
            'mo_smc_supervisor_hfw',
        ];

        foreach ($tables as $table) {
            if (empty($this->getColumns($table))) {
                $this->markTestSkipped("Missing table {$table}");
            }
        }
    }

    protected function requireGeoSchema(array $columns): void
    {
        $geoColumns = [
            'ms_geo_lga' => ['LgaId', 'Fullname', 'StateId'],
            'ms_geo_ward' => ['wardid', 'ward', 'lgaid'],
        ];
        foreach ($geoColumns as $table => $required) {
            if (!$this->tableHasColumns($table, $required)) {
                $this->markTestSkipped("Missing {$table} columns");
            }
        }

        if (in_array('dpid', $columns, true) && !$this->tableHasColumns('ms_geo_dp', ['dpid', 'dp', 'wardid'])) {
            $this->markTestSkipped('Missing ms_geo_dp columns');
        }

        if (in_array('comid', $columns, true) && !$this->tableHasColumns('ms_geo_comm', ['comid', 'community', 'dpid'])) {
            $this->markTestSkipped('Missing ms_geo_comm columns');
        }
    }

    protected function requireUserSchema(): void
    {
        if (!$this->tableHasColumns('usr_login', ['userid', 'loginid'])
            || !$this->tableHasColumns('usr_identity', ['userid', 'first', 'middle', 'last'])
        ) {
            $this->markTestSkipped('Missing user schema');
        }
    }
}
