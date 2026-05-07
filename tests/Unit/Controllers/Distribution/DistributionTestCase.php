<?php

namespace Tests\Unit\Controllers\Distribution;

use Tests\TestCase;

abstract class DistributionTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_state' => ['StateId' => []],
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['dpid' => [], 'guid' => []],
        'nc_token' => ['tokenid' => [], 'serial_no' => [], 'uuid' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['etoken_serial' => [], 'dis_id' => []],
        'hhm_gs_net_serial' => ['snid' => []],
        'hhm_gs_net_verification' => ['snid' => []],
        'hhm_gs_net_verification_log' => ['id' => []],
        'ms_product_item' => ['itemid' => []],
        'ms_product_sgtin' => ['sgtinid' => []],
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
        require_once $this->projectRoot . '/lib/controller/distribution/distribution.cont.php';
        require_once $this->projectRoot . '/lib/controller/distribution/gsverification.cont.php';
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
        $guid = md5(uniqid('', true));

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

        $this->insertRow('sys_geo_codex', [
            'guid' => $guid,
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'geo_level_id' => $dpId,
            'geo_level' => 'dp',
            'geo_value' => "{$prefix} DP",
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            'is_gsone' => 0,
        ]);
        $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);
        $this->recordCleanup('sys_geo_codex', 'guid', $guid);

        return [
            'stateid' => $stateId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'dpid' => $dpId,
            'guid' => $guid,
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
        ];
    }

    protected function seedToken(array $data): array
    {
        $tokenId = $this->insertRow('nc_token', $data);
        if ($tokenId) {
            $this->recordCleanup('nc_token', 'tokenid', $tokenId);
        }
        if (isset($data['serial_no'])) {
            $this->recordCleanup('nc_token', 'serial_no', $data['serial_no']);
        }
        if (isset($data['uuid'])) {
            $this->recordCleanup('nc_token', 'uuid', $data['uuid']);
        }
        return ['tokenid' => (int) $tokenId];
    }

    protected function seedMobilization(array $data): void
    {
        $this->insertRow('hhm_mobilization', $data);
        if (isset($data['hhid'])) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $data['hhid']);
        }
    }

    protected function seedDistribution(array $data): int
    {
        $id = $this->insertRow('hhm_distribution', $data);
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_distribution', 'etoken_serial', $data['etoken_serial']);
        }
        if ($id) {
            $this->recordCleanup('hhm_distribution', 'dis_id', $id);
        }
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_distribution', 'etoken_serial', $data['etoken_serial']);
        }
        if ($id) {
            $this->recordCleanup('hhm_distribution', 'dis_id', $id);
        }
        return (int) $id;
    }

    protected function seedGsNetSerial(array $data): int
    {
        $id = $this->insertRow('hhm_gs_net_serial', $data);
        if ($id) {
            $this->recordCleanup('hhm_gs_net_serial', 'snid', $id);
        }
        return (int) $id;
    }

    protected function seedVerificationLog(int $id): void
    {
        $this->recordCleanup('hhm_gs_net_verification_log', 'id', $id);
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

    protected function seedProductItem(array $data): int
    {
        $id = $this->insertRow('ms_product_item', $data);
        if ($id) {
            $this->recordCleanup('ms_product_item', 'itemid', $id);
        }
        return (int) $id;
    }

    protected function seedProductSgtin(array $data): int
    {
        $id = $this->insertRow('ms_product_sgtin', $data);
        if ($id) {
            $this->recordCleanup('ms_product_sgtin', 'sgtinid', $id);
        }
        return (int) $id;
    }
}
