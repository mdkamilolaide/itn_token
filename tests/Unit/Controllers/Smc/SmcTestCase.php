<?php

namespace Tests\Unit\Controllers\Smc;

use Tests\TestCase;

abstract class SmcTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['geo_level_id' => [], 'dpid' => [], 'guid' => []],
        'usr_role' => ['roleid' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
        'smc_period' => ['periodid' => []],
        'smc_child_household' => ['hhid' => [], 'hh_token' => []],
        'smc_child' => ['child_id' => [], 'beneficiary_id' => []],
        'smc_drug_administration' => ['adm_id' => [], 'uid' => []],
        'smc_referer_record' => ['ref_id' => []],
        'smc_process_setting' => ['pointer' => []],
        'smc_commodity' => ['product_id' => [], 'product_code' => []],
        'sms_reasons' => ['reason' => []],
        'smc_cms_location' => ['location_id' => []],
        'smc_logistics_issues' => ['issue_id' => []],
        'smc_inventory_central' => ['inventory_id' => [], 'product_code' => []],
        'smc_inventory_inbound' => ['product_code' => []],
        'smc_inventory_outbound' => ['product_code' => []],
        'smc_icc_issue' => ['issue_id' => []],
        'smc_icc_collection' => ['issue_id' => [], 'download_id' => []],
        'smc_icc_reconcile' => ['issue_id' => []],
        'smc_icc_download_log' => ['download_id' => []],
        'smc_icc_push' => ['issue_id' => []],
        'smc_icc_unlock' => ['issue_id' => []],
        'smc_icc_receive' => ['uid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/smc/dashboard.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/drugadmin.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/icc.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/inventory.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/logistics.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/period.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/registration.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/reporting.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/smcdatatable.cont.php';
        require_once $this->projectRoot . '/lib/controller/smc/smcmaster.cont.php';
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

    protected function seedGeoCodexDp(array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'dpid'])) {
            return;
        }
        $guid = md5(uniqid('', true));
        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => 'dp',
            'geo_level_id' => $geo['dpid'],
            'geo_value' => 10,
            'title' => 'DP',
            'geo_string' => 'DP-' . $geo['dpid'],
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $geo['dpid']);
        $this->recordCleanup('sys_geo_codex', 'dpid', $geo['dpid']);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);
    }

    protected function seedGeoCodexWard(array $geo): void
    {
        if (!$this->tableHasColumns('sys_geo_codex', ['geo_level', 'geo_level_id', 'wardid'])) {
            return;
        }
        $guid = md5(uniqid('', true));
        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'geo_value' => 'ward',
            'title' => 'WARD',
            'geo_string' => 'WARD-' . $geo['wardid'],
        ]);
        $this->recordCleanup('sys_geo_codex', 'geo_level_id', $geo['wardid']);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);
    }

    protected function seedRole(int $roleId, string $title): void
    {
        if (!$this->tableHasColumns('usr_role', ['roleid', 'title'])) {
            return;
        }
        $this->insertRow('usr_role', [
            'roleid' => $roleId,
            'title' => $title,
        ]);
        $this->recordCleanup('usr_role', 'roleid', $roleId);
    }

    protected function seedUser(int $userId, string $geoLevel, int $geoLevelId, int $roleId = 1): void
    {
        if ($this->tableHasColumns('usr_login', ['userid', 'loginid', 'pwd'])) {
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
            $this->recordCleanup('usr_login', 'userid', $userId);
        }

        if ($this->tableHasColumns('usr_identity', ['userid', 'first', 'last'])) {
            $this->insertRow('usr_identity', [
                'userid' => $userId,
                'first' => 'Test',
                'middle' => 'User',
                'last' => 'Account',
                'gender' => 'M',
                'email' => 'user@example.com',
                'phone' => '08000000000',
            ]);
            $this->recordCleanup('usr_identity', 'userid', $userId);
        }
    }

    protected function seedPeriod(string $title, int $active = 0, ?string $start = null, ?string $end = null): int
    {
        $start = $start ?? date('Y-m-d');
        $end = $end ?? date('Y-m-d', strtotime('+5 days'));
        $id = $this->insertRow('smc_period', [
            'title' => $title,
            'start_date' => $start,
            'end_date' => $end,
            'active' => $active,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $this->recordCleanup('smc_period', 'periodid', $id);
        }
        return (int) $id;
    }

    protected function seedProcessSetting(string $pointer, string $val): void
    {
        $this->insertRow('smc_process_setting', [
            'pointer' => $pointer,
            'val' => $val,
        ]);
        $this->recordCleanup('smc_process_setting', 'pointer', $pointer);
    }

    protected function seedHousehold(array $data): int
    {
        $id = $this->insertRow('smc_child_household', $data);
        if ($id) {
            $this->recordCleanup('smc_child_household', 'hhid', $id);
        }
        if (isset($data['hh_token'])) {
            $this->recordCleanup('smc_child_household', 'hh_token', $data['hh_token']);
        }
        return (int) $id;
    }

    protected function seedChild(array $data): int
    {
        $id = $this->insertRow('smc_child', $data);
        if ($id) {
            $this->recordCleanup('smc_child', 'child_id', $id);
        }
        if (isset($data['beneficiary_id'])) {
            $this->recordCleanup('smc_child', 'beneficiary_id', $data['beneficiary_id']);
        }
        return (int) $id;
    }

    protected function seedDrugAdmin(array $data): int
    {
        $id = $this->insertRow('smc_drug_administration', $data);
        if ($id) {
            $this->recordCleanup('smc_drug_administration', 'adm_id', $id);
        }
        if (isset($data['uid'])) {
            $this->recordCleanup('smc_drug_administration', 'uid', $data['uid']);
        }
        return (int) $id;
    }

    protected function seedReferral(array $data): int
    {
        $id = $this->insertRow('smc_referer_record', $data);
        if ($id) {
            $this->recordCleanup('smc_referer_record', 'ref_id', $id);
        }
        return (int) $id;
    }

    protected function seedCommodity(array $data): int
    {
        $id = $this->insertRow('smc_commodity', $data);
        if ($id) {
            $this->recordCleanup('smc_commodity', 'product_id', $id);
        }
        if (isset($data['product_code'])) {
            $this->recordCleanup('smc_commodity', 'product_code', $data['product_code']);
        }
        return (int) $id;
    }

    protected function seedReason(array $data): void
    {
        $this->insertRow('sms_reasons', $data);
        if (isset($data['reason'])) {
            $this->recordCleanup('sms_reasons', 'reason', $data['reason']);
        }
    }

    protected function seedCmsLocation(array $data): int
    {
        $id = $this->insertRow('smc_cms_location', $data);
        if ($id) {
            $this->recordCleanup('smc_cms_location', 'location_id', $id);
        }
        return (int) $id;
    }

    protected function seedInventoryCentral(array $data): int
    {
        $id = $this->insertRow('smc_inventory_central', $data);
        if ($id) {
            $this->recordCleanup('smc_inventory_central', 'inventory_id', $id);
        }
        if (isset($data['product_code'])) {
            $this->recordCleanup('smc_inventory_central', 'product_code', $data['product_code']);
        }
        return (int) $id;
    }

    protected function seedLogisticsIssue(array $data): int
    {
        $id = $this->insertRow('smc_logistics_issues', $data);
        if ($id) {
            $this->recordCleanup('smc_logistics_issues', 'issue_id', $id);
        }
        return (int) $id;
    }

    protected function seedIccIssue(array $data): int
    {
        $id = $this->insertRow('smc_icc_issue', $data);
        if ($id) {
            $this->recordCleanup('smc_icc_issue', 'issue_id', $id);
        }
        return (int) $id;
    }

    protected function seedIccCollection(array $data): int
    {
        $id = $this->insertRow('smc_icc_collection', $data);
        if (isset($data['issue_id'])) {
            $this->recordCleanup('smc_icc_collection', 'issue_id', $data['issue_id']);
        }
        if (isset($data['download_id'])) {
            $this->recordCleanup('smc_icc_collection', 'download_id', $data['download_id']);
        }
        return (int) $id;
    }

    protected function seedIccReconcile(array $data): int
    {
        $id = $this->insertRow('smc_icc_reconcile', $data);
        if (isset($data['issue_id'])) {
            $this->recordCleanup('smc_icc_reconcile', 'issue_id', $data['issue_id']);
        }
        return (int) $id;
    }
}
