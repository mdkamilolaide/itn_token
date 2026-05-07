<?php

namespace Tests\Unit\Controllers\Dashboard;

use PHPUnit\Framework\TestCase;

abstract class DashboardTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['dis_id' => [], 'etoken_serial' => []],
        'nc_netcard' => ['ncid' => [], 'uuid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/distribution.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/enetcard.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/eolin.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/mobilization.cont.php';
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
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $dpId);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ];
    }

    protected function seedMobilization(array $data): int
    {
        $id = $this->insertRow('hhm_mobilization', $data);
        if ($id) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $id);
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

    protected function seedNetcards(int $count, array $base): array
    {
        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $uuid = 'NC-' . uniqid('', true);
            $data = array_merge([
                'uuid' => $uuid,
                'netcard_code' => $uuid,
                'active' => 1,
                'location_value' => 60,
                'lgaid' => $base['lgaid'] ?? null,
                'wardid' => $base['wardid'] ?? null,
                'mobilizer_userid' => $base['mobilizer_userid'] ?? null,
                'created' => date('Y-m-d H:i:s'),
            ], $base);

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

    protected function requireDistributionSchema(): void
    {
        $mobCols = ['hhid', 'dp_id', 'family_size', 'allocated_net', 'etoken_serial', 'collected_date'];
        $distCols = ['dis_id', 'etoken_serial', 'collected_nets', 'collected_date', 'dp_id'];
        if (!$this->tableHasColumns('hhm_mobilization', $mobCols)
            || !$this->tableHasColumns('hhm_distribution', $distCols)
            || !$this->tableHasColumns('sys_geo_codex', ['lgaid', 'wardid', 'dpid', 'geo_level'])
        ) {
            $this->markTestSkipped('Missing distribution dashboard schema');
        }
    }

    protected function requireEnetcardSchema(): void
    {
        $cols = ['location_value', 'lgaid', 'wardid', 'mobilizer_userid'];
        if (!$this->tableHasColumns('nc_netcard', $cols)) {
            $this->markTestSkipped('Missing nc_netcard schema');
        }
    }

    protected function requireEolinSchema(): void
    {
        $mobCols = ['dp_id', 'eolin_have_old_net', 'eolin_total_old_net'];
        $distCols = ['dp_id', 'eolin_bring_old_net', 'eolin_total_old_net'];
        if (!$this->tableHasColumns('hhm_mobilization', $mobCols)
            || !$this->tableHasColumns('hhm_distribution', $distCols)
        ) {
            $this->markTestSkipped('Missing EOLIN schema');
        }
    }

    protected function requireMobilizationSchema(): void
    {
        $mobCols = ['hhid', 'dp_id', 'allocated_net', 'family_size', 'collected_date'];
        if (!$this->tableHasColumns('hhm_mobilization', $mobCols)
            || !$this->tableHasColumns('sys_geo_codex', ['lgaid', 'wardid', 'dpid', 'geo_level'])
        ) {
            $this->markTestSkipped('Missing mobilization schema');
        }
    }
}
