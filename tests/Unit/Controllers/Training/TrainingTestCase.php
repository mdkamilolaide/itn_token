<?php

namespace Tests\Unit\Controllers\Training;

use Tests\TestCase;

abstract class TrainingTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'tra_training' => ['trainingid' => [], 'guid' => []],
        'tra_participants' => ['participant_id' => []],
        'tra_session' => ['sessionid' => [], 'guid' => []],
        'tra_attendant' => ['attendant_id' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'usr_role' => ['roleid' => []],
        'usr_finance' => ['userid' => []],
        'usr_security' => ['userid' => []],
        'sys_geo_codex' => ['geo_level_id' => [], 'guid' => []],
        'sys_geo_level' => ['id' => []],
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_default_settings' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/config.php';
        require_once $this->projectRoot . '/lib/controller/training/training.cont.php';
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

    protected function requireSchema(array $requirements): void
    {
        foreach ($requirements as $table => $columns) {
            if (!$this->tableHasColumns($table, $columns)) {
                $this->markTestSkipped("Missing schema for {$table}");
            }
        }
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

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ];
    }

    protected function seedGeoCodex(string $level, int $levelId, array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id'])) {
            return;
        }
        $guid = md5(uniqid('', true));
        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => $level,
            'geo_level_id' => $levelId,
            'geo_value' => $level,
            'title' => strtoupper($level),
            'geo_string' => strtoupper($level) . '-' . $levelId,
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $levelId);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);
    }

    protected function seedGeoLevel(string $level, int $value, string $table): void
    {
        if (!$this->tableHasColumns('sys_geo_level', ['geo_level', 'geo_value', 'geo_table'])) {
            return;
        }
        $id = $this->insertRow('sys_geo_level', [
            'geo_level' => $level,
            'geo_value' => $value,
            'geo_table' => $table,
        ]);
        if ($id) {
            $this->recordCleanup('sys_geo_level', 'id', $id);
        }
    }

    protected function seedUser(int $userId, string $geoLevel, int $geoLevelId, int $roleId = 1): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userId,
            'loginid' => 'user.' . $userId,
            'username' => 'User ' . $userId,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => $roleId,
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

    protected function seedFinance(int $userId): void
    {
        $this->insertRow('usr_finance', [
            'userid' => $userId,
            'bank_name' => 'Test Bank',
            'bank_code' => '001',
            'account_name' => 'Test User',
            'account_no' => '0001112223',
            'is_verified' => 1,
            'verification_status' => 'verified',
            'verified_account_name' => 'Test User',
            'verification_message' => 'ok',
        ]);
        $this->recordCleanup('usr_finance', 'userid', $userId);
    }

    protected function seedSecurity(int $userId): void
    {
        $this->insertRow('usr_security', [
            'userid' => $userId,
            'bio_feature' => 'fingerprint',
        ]);
        $this->recordCleanup('usr_security', 'userid', $userId);
    }

    protected function seedTraining(array $data): int
    {
        $id = $this->insertRow('tra_training', $data);
        if ($id) {
            $this->recordCleanup('tra_training', 'trainingid', $id);
        }
        if (isset($data['guid'])) {
            $this->recordCleanup('tra_training', 'guid', $data['guid']);
        }
        return (int) $id;
    }

    protected function seedParticipant(array $data): int
    {
        $id = $this->insertRow('tra_participants', $data);
        if ($id) {
            $this->recordCleanup('tra_participants', 'participant_id', $id);
        }
        return (int) $id;
    }

    protected function seedSession(array $data): int
    {
        $id = $this->insertRow('tra_session', $data);
        if ($id) {
            $this->recordCleanup('tra_session', 'sessionid', $id);
        }
        if (isset($data['guid'])) {
            $this->recordCleanup('tra_session', 'guid', $data['guid']);
        }
        return (int) $id;
    }

    protected function seedAttendance(array $data): int
    {
        $id = $this->insertRow('tra_attendant', $data);
        if ($id) {
            $this->recordCleanup('tra_attendant', 'attendant_id', $id);
        }
        return (int) $id;
    }
}
