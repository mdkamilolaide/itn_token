<?php

namespace Tests\Integration\SMC;

use Tests\TestCase;

abstract class SMCTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'smc_period' => ['periodid' => []],
        'smc_commodity' => ['product_id' => []],
        'sms_reasons' => ['id' => []],
        'smc_child_household' => ['hhid' => [], 'hh_token' => []],
        'smc_child' => ['child_id' => [], 'beneficiary_id' => []],
        'smc_cms_location' => ['location_id' => []],
        'smc_logistics_transporter' => ['transporter_id' => []],
        'smc_icc_issue' => ['issue_id' => []],
        'smc_icc_collection' => ['id' => [], 'issue_id' => []],
        'smc_icc_download_log' => ['id' => []],
        'smc_icc_push' => ['push_id' => []],
        'smc_icc_reconcile' => ['recon_id' => []],
        'usr_role' => ['roleid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['geo_level_id' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/smc/smcmaster.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/smcdatatable.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/icc.cont.php';
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

    protected function seedPeriod(string $title): int
    {
        $columns = $this->getColumns('smc_period');
        $titleColumn = in_array('title', $columns, true) ? 'title' : (in_array('period_name', $columns, true) ? 'period_name' : null);
        if ($titleColumn === null) {
            $this->markTestSkipped('Missing smc_period title column');
        }

        $id = $this->insertRow('smc_period', [
            $titleColumn => $title,
            'active' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('smc_period', 'periodid', $id);
        }
        return (int) $id;
    }

    protected function seedHousehold(string $token, int $dpid): int
    {
        $id = $this->insertRow('smc_child_household', [
            'dpid' => $dpid,
            'hh_token' => $token,
            'hoh_name' => 'Test HOH',
            'hoh_phone' => '07000000000',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('smc_child_household', 'hhid', $id);
        }
        $this->recordCleanup('smc_child_household', 'hh_token', $token);
        return (int) $id;
    }

    protected function seedChild(string $beneficiaryId, string $hhToken, int $dpid): int
    {
        $id = $this->insertRow('smc_child', [
            'hh_token' => $hhToken,
            'beneficiary_id' => $beneficiaryId,
            'dpid' => $dpid,
            'name' => 'Child',
            'gender' => 'female',
            'dob' => '2020-01-01',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('smc_child', 'child_id', $id);
        }
        $this->recordCleanup('smc_child', 'beneficiary_id', $beneficiaryId);
        return (int) $id;
    }

    protected function requireMasterSchema(): void
    {
        if (!$this->tableHasColumns('smc_child_household', ['hhid', 'dpid', 'hh_token'])) {
            $this->markTestSkipped('Missing smc_child_household schema');
        }
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'dpid'])) {
            $this->markTestSkipped('Missing sys_geo_codex schema');
        }
    }

    protected function requireChildSumSchema(): void
    {
        if (!$this->tableHasColumns('smc_child_household', ['hhid', 'hh_token'])
            || !$this->tableHasColumns('smc_child', ['child_id', 'hh_token'])
        ) {
            $this->markTestSkipped('Missing child summary schema');
        }
    }

    protected function requireIccIssueSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_issue', ['issue_id'])) {
            $this->markTestSkipped('Missing smc_icc_issue schema');
        }
    }

    protected function requireIccCollectionSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_collection', ['issue_id'])) {
            $this->markTestSkipped('Missing smc_icc_collection schema');
        }
    }

    protected function requireIccDownloadSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_collection', ['issue_id', 'download_id', 'status_code', 'is_download_confirm'])
            || !$this->tableHasColumns('smc_icc_download_log', ['issue_id', 'cdd_lead_id'])
        ) {
            $this->markTestSkipped('Missing ICC download schema');
        }
    }

    protected function requireIccAcceptanceSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_collection', ['issue_id', 'is_accepted', 'status_code'])
            || !$this->tableHasColumns('smc_icc_issue', ['issue_id', 'confirmation'])
        ) {
            $this->markTestSkipped('Missing ICC acceptance schema');
        }
    }

    protected function requireIccReturnSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_collection', ['issue_id', 'returned_qty', 'returned_partial', 'status_code'])) {
            $this->markTestSkipped('Missing ICC return schema');
        }
    }

    protected function requireIccReconcileSchema(): void
    {
        $reconcileColumns = [
            'issue_id',
            'cdd_lead_id',
            'drug',
            'used_qty',
            'full_qty',
            'partial_qty',
            'wasted_qty',
            'loss_qty',
            'loss_reason',
            'receiver_id',
            'device_serial',
            'app_version',
            'reconcile_date',
        ];
        if (!$this->tableHasColumns('smc_icc_reconcile', $reconcileColumns)
            || !$this->tableHasColumns('smc_icc_collection', ['issue_id', 'status_code'])
        ) {
            $this->markTestSkipped('Missing ICC reconcile schema');
        }
    }

    protected function requireIccPushSchema(): void
    {
        if (!$this->tableHasColumns('smc_icc_collection', ['issue_id', 'status_code', 'qty', 'download_date', 'is_download_confirm', 'download_confirm_date', 'updated'])
            || !$this->tableHasColumns('smc_icc_push', ['periodid', 'dpid', 'issue_id', 'cdd_lead_id', 'drug', 'qty', 'device_id', 'version', 'created'])
        ) {
            $this->markTestSkipped('Missing ICC push schema');
        }
    }

    protected function requireMasterChildSchema(): void
    {
        if (!$this->tableHasColumns('smc_child', ['beneficiary_id', 'dpid', 'hh_token'])
            || !$this->tableHasColumns('smc_drug_administration', ['beneficiary_id', 'periodid', 'collected_date', 'dpid'])
            || !$this->tableHasColumns('smc_period', ['periodid', 'title'])
        ) {
            $this->markTestSkipped('Missing master child schema');
        }
    }
}
