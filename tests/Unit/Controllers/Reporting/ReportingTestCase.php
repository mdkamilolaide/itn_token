<?php

namespace Tests\Unit\Controllers\Reporting;

use Tests\TestCase;

abstract class ReportingTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'ms_geo_comm' => ['comid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'usr_finance' => ['userid' => []],
        'usr_role' => ['roleid' => []],
        'tra_training' => ['trainingid' => []],
        'tra_participants' => ['id' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['dis_id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/reporting/reporting.cont.php';
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
                'wardid' => $wardId,
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

    protected function seedGeoCodexWard(array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $this->insertRow('sys_geo_codex', [
            'guid' => md5(uniqid('', true)),
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'geo_value' => 'WARD',
            'title' => 'WARD',
            'geo_string' => 'WARD-' . $geo['wardid'],
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $geo['wardid']);
    }

    protected function seedGeoCodexDp(array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $this->insertRow('sys_geo_codex', [
            'guid' => md5(uniqid('', true)),
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => 'dp',
            'geo_level_id' => $geo['dpid'],
            'geo_value' => 'DP',
            'title' => 'DP',
            'geo_string' => 'DP-' . $geo['dpid'],
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $geo['dpid']);
    }

    protected function seedUser(int $userId, string $geoLevel, int $geoLevelId): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userId,
            'loginid' => 'user.' . $userId,
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
            'gender' => 'M',
            'email' => 'user@example.com',
            'phone' => '08000000000',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userId);
        $this->recordCleanup('usr_identity', 'userid', $userId);
    }

    protected function seedTraining(string $title): int
    {
        $id = $this->insertRow('tra_training', [
            'title' => $title,
            'active' => 1,
        ]);
        if ($id) {
            $this->recordCleanup('tra_training', 'trainingid', $id);
        }
        return (int) $id;
    }

    protected function seedParticipant(int $trainingId, int $userId): int
    {
        $id = $this->insertRow('tra_participants', [
            'trainingid' => $trainingId,
            'userid' => $userId,
        ]);
        if ($id) {
            $this->recordCleanup('tra_participants', 'id', $id);
        }
        return (int) $id;
    }

    protected function seedFinance(int $userId): void
    {
        $this->insertRow('usr_finance', [
            'userid' => $userId,
            'bank_name' => 'Test Bank',
            'account_name' => 'Test User',
            'account_no' => '0001112223',
            'verification_status' => 'verified',
            'verified_account_name' => 'Test User',
            'last_verified_date' => date('Y-m-d'),
        ]);
        $this->recordCleanup('usr_finance', 'userid', $userId);
    }
}
