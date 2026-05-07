<?php

namespace Tests\Integration\Reporting;

use Tests\TestCase;

abstract class ReportingTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
        'usr_role' => ['roleid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'usr_finance' => ['id' => [], 'userid' => []],
        'tra_training' => ['trainingid' => []],
        'tra_participants' => ['participant_id' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['dis_id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
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

        $codex = [
            'geo_level' => 'ward',
            'geo_level_id' => $wardId,
            'geo_value' => $wardId,
            'title' => "{$prefix} Ward",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'clusterid' => null,
        ];
        $this->insertRow('sys_geo_codex', $codex);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $wardId);

        $dpCodex = [
            'geo_level' => 'dp',
            'geo_level_id' => $dpId,
            'geo_value' => $dpId,
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}-{$dpId}",
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'clusterid' => null,
        ];
        $this->insertRow('sys_geo_codex', $dpCodex);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $dpId);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
        ];
    }

    protected function seedRole(string $code): int
    {
        $roleId = $this->insertRow('usr_role', [
            'title' => 'Test Role',
            'role_code' => $code,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        if ($roleId) {
            $this->recordCleanup('usr_role', 'roleid', $roleId);
        }
        return (int) $roleId;
    }

    protected function seedUser(int $userid, int $roleId, string $geoLevel, int $geoLevelId): void
    {
        $this->insertRow('usr_login', [
            'userid' => $userid,
            'loginid' => 'user.' . $userid,
            'username' => 'User ' . $userid,
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
            'userid' => $userid,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
            'gender' => 'female',
            'phone' => '07000000000',
        ]);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
    }

    protected function seedIdentityNulls(int $userid): void
    {
        $this->insertRow('usr_identity', [
            'userid' => $userid,
            'first' => null,
            'middle' => null,
            'last' => null,
            'gender' => null,
            'phone' => null,
        ]);
        $this->recordCleanup('usr_identity', 'userid', $userid);
    }

    protected function seedFinance(int $userid): void
    {
        $id = $this->insertRow('usr_finance', [
            'userid' => $userid,
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_no' => '000111222',
            'verification_status' => 'verified',
            'verified_account_name' => 'Test Account',
            'last_verified_date' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('usr_finance', 'id', $id);
        }
        $this->recordCleanup('usr_finance', 'userid', $userid);
    }

    protected function seedTraining(string $title): int
    {
        $id = $this->insertRow('tra_training', [
            'title' => $title,
            'description' => 'Training',
            'training_date' => date('Y-m-d H:i:s'),
            'geo_location' => 'ward',
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_training', 'trainingid', $id);
        }
        return (int) $id;
    }

    protected function seedParticipant(int $userid, int $trainingId): void
    {
        $id = $this->insertRow('tra_participants', [
            'userid' => $userid,
            'trainingid' => $trainingId,
            'attendance_status' => 'present',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('tra_participants', 'participant_id', $id);
        }
    }

    protected function seedMobilization(int $dpid, string $date): void
    {
        $id = $this->insertRow('hhm_mobilization', [
            'dp_id' => $dpid,
            'allocated_net' => 2,
            'family_size' => 3,
            'collected_date' => $date,
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $id);
        }
    }

    protected function seedDistribution(int $dpid, string $date): void
    {
        $id = $this->insertRow('hhm_distribution', [
            'dp_id' => $dpid,
            'collected_nets' => 2,
            'collected_date' => $date,
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('hhm_distribution', 'dis_id', $id);
        }
    }

    protected function decodeExport(string $json, string $sheet): array
    {
        $payload = json_decode($json, true);
        $this->assertIsArray($payload);
        $this->assertNotEmpty($payload);
        $this->assertSame($sheet, $payload[0]['sheetName']);
        $this->assertArrayHasKey('data', $payload[0]);
        return $payload[0]['data'];
    }

    protected function requireParticipantSchema(): void
    {
        if (!$this->tableHasColumns('tra_training', ['trainingid', 'title', 'active'])
            || !$this->tableHasColumns('tra_participants', ['participant_id', 'userid', 'trainingid'])
            || !$this->tableHasColumns('usr_login', ['userid', 'loginid', 'roleid', 'geo_level', 'geo_level_id'])
            || !$this->tableHasColumns('usr_identity', ['userid', 'first', 'last'])
            || !$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'geo_string'])
        ) {
            $this->markTestSkipped('Missing participant reporting schema');
        }
    }

    protected function requireFinanceSchema(): void
    {
        if (!$this->tableHasColumns('usr_finance', ['userid', 'bank_name', 'account_no', 'verification_status'])) {
            $this->markTestSkipped('Missing finance schema');
        }
    }

    protected function requireGeoSchema(): void
    {
        if (!$this->tableHasColumns('ms_geo_state', ['StateId', 'Fullname'])
            || !$this->tableHasColumns('ms_geo_lga', ['LgaId', 'StateId'])
            || !$this->tableHasColumns('ms_geo_ward', ['wardid', 'lgaid'])
            || !$this->tableHasColumns('ms_geo_dp', ['dpid', 'wardid'])
            || !$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'geo_string'])
        ) {
            $this->markTestSkipped('Missing geo schema');
        }

        $codexColumns = $this->getColumns('sys_geo_codex');
        foreach (['stateid', 'lgaid', 'wardid', 'dpid'] as $column) {
            if (!in_array($column, $codexColumns, true)) {
                $this->markTestSkipped('Missing sys_geo_codex location columns');
            }
        }
    }

    protected function requireMobilizationSchema(): void
    {
        if (!$this->tableHasColumns('hhm_mobilization', ['hhid', 'dp_id', 'allocated_net', 'family_size', 'collected_date'])
            || !$this->tableHasColumns('ms_geo_lga', ['LgaId', 'Fullname'])
        ) {
            $this->markTestSkipped('Missing mobilization reporting schema');
        }
    }

    protected function requireDistributionSchema(): void
    {
        if (!$this->tableHasColumns('hhm_distribution', ['dis_id', 'dp_id', 'collected_nets', 'collected_date'])) {
            $this->markTestSkipped('Missing distribution reporting schema');
        }
    }
}
