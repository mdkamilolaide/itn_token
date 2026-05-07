<?php

namespace Tests\Integration\Training;

use Tests\TestCase;

abstract class TrainingTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'tra_training' => ['trainingid' => []],
        'tra_session' => ['sessionid' => []],
        'tra_participants' => ['participant_id' => []],
        'tra_attendant' => ['attendant_id' => []],
        'usr_role' => ['roleid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'usr_finance' => ['id' => [], 'userid' => []],
        'usr_security' => ['id' => [], 'userid' => []],
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
        'sys_geo_level' => ['id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
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
            'geo_level' => 'ward',
            'geo_level_id' => $wardId,
            'geo_value' => $wardId,
            'title' => "{$prefix} Ward",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $wardId);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ];
    }

    protected function seedRole(int $roleId): void
    {
        $this->insertRow('usr_role', [
            'roleid' => $roleId,
            'title' => 'Role ' . $roleId,
            'role_code' => 'ROLE-' . $roleId,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('usr_role', 'roleid', $roleId);
    }

    protected function seedUser(int $userid, int $roleId, string $geoLevel, int $geoLevelId, string $group = 'default'): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => 'user.' . $userid,
            'username' => 'User ' . $userid,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => $roleId,
            'geo_level' => $geoLevel,
            'geo_level_id' => $geoLevelId,
            'user_group' => $group,
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->insertRow('usr_identity', [
            'userid' => $userid,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
            'gender' => 'female',
            'email' => 'test@example.com',
            'phone' => '07000000000',
        ]);
        $this->insertRow('usr_finance', [
            'userid' => $userid,
            'bank_name' => 'Test Bank',
            'bank_code' => '001',
            'account_name' => 'Test User',
            'account_no' => '1234567890',
            'is_verified' => 1,
            'verification_status' => 'verified',
            'verified_account_name' => 'Test User',
            'verification_message' => 'ok',
        ]);
        $this->insertRow('usr_security', [
            'userid' => $userid,
            'bio_feature' => 'yes',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
        $this->recordCleanup('usr_finance', 'userid', $userid);
        $this->recordCleanup('usr_security', 'userid', $userid);
    }

    protected function seedTrainingRow(string $title, string $geoLocation, int $locationId): int
    {
        $id = $this->insertRow('tra_training', [
            'title' => $title,
            'geo_location' => $geoLocation,
            'location_id' => $locationId,
            'guid' => generateUUID(),
            'active' => 1,
            'description' => 'Training',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
            'participant_count' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_training', 'trainingid', $id);
        }
        return (int) $id;
    }

    protected function seedSessionRow(int $trainingId, string $title): int
    {
        $id = $this->insertRow('tra_session', [
            'trainingid' => $trainingId,
            'title' => $title,
            'guid' => generateUUID(),
            'session_date' => date('Y-m-d H:i:s'),
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_session', 'sessionid', $id);
        }
        return (int) $id;
    }

    protected function seedParticipant(int $trainingId, int $userid): int
    {
        $id = $this->insertRow('tra_participants', [
            'trainingid' => $trainingId,
            'userid' => $userid,
            'attendance_status' => 'present',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_participants', 'participant_id', $id);
        }
        return (int) $id;
    }

    protected function seedAttendance(int $sessionId, int $participantId, int $userid, string $type): int
    {
        $id = $this->insertRow('tra_attendant', [
            'session_id' => $sessionId,
            'participant_id' => $participantId,
            'at_type' => $type,
            'bio_auth' => 1,
            'collected' => date('Y-m-d H:i:s'),
            'longitude' => '7.1',
            'latitude' => '9.2',
            'userid' => $userid,
            'app_version' => '1.0',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_attendant', 'attendant_id', $id);
        }
        return (int) $id;
    }

    protected function requireTrainingSchema(): void
    {
        $columns = ['title', 'geo_location', 'location_id', 'guid', 'active', 'description', 'start_date', 'end_date', 'participant_count'];
        if (!$this->tableHasColumns('tra_training', $columns)) {
            $this->markTestSkipped('Missing tra_training schema');
        }
    }

    protected function requireSessionSchema(): void
    {
        $columns = ['trainingid', 'title', 'guid', 'session_date'];
        if (!$this->tableHasColumns('tra_session', $columns)) {
            $this->markTestSkipped('Missing tra_session schema');
        }
    }

    protected function requireParticipantSchema(): void
    {
        $columns = ['participant_id', 'trainingid', 'userid'];
        if (!$this->tableHasColumns('tra_participants', $columns)) {
            $this->markTestSkipped('Missing tra_participants schema');
        }
        if (!$this->tableHasColumns('usr_login', ['userid', 'loginid', 'roleid', 'geo_level', 'geo_level_id'])
            || !$this->tableHasColumns('usr_identity', ['userid', 'first', 'last'])
            || !$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'geo_string'])
        ) {
            $this->markTestSkipped('Missing user or geo schema');
        }
    }

    protected function requireParticipantDetailsSchema(): void
    {
        if (!$this->tableHasColumns('usr_finance', ['userid', 'bank_name', 'account_no'])
            || !$this->tableHasColumns('usr_security', ['userid', 'bio_feature'])
        ) {
            $this->markTestSkipped('Missing finance/security schema');
        }
    }

    protected function requireAttendanceSchema(): void
    {
        $columns = ['session_id', 'participant_id', 'at_type', 'bio_auth', 'collected', 'longitude', 'latitude', 'userid', 'app_version'];
        if (!$this->tableHasColumns('tra_attendant', $columns)) {
            $this->markTestSkipped('Missing tra_attendant schema');
        }
        $this->requireParticipantSchema();
    }

    protected function requireGeoLevelSchema(): void
    {
        if (!$this->tableHasColumns('sys_geo_level', ['geo_level', 'geo_value'])) {
            $this->markTestSkipped('Missing sys_geo_level schema');
        }
    }
}
