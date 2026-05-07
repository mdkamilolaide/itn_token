<?php

namespace Tests\Integration\Dashboard;

use Tests\TestCase;

abstract class DashboardTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanup = [
        'ms_geo_lga' => ['LgaId' => []],
        'ms_geo_ward' => ['wardid' => []],
        'ms_geo_dp' => ['dpid' => []],
        'sys_geo_codex' => ['dpid' => []],
        'hhm_mobilization' => ['hhid' => []],
        'hhm_distribution' => ['etoken_serial' => []],
        'nc_netcard' => ['ncid' => [], 'netcard_id' => [], 'serial_number' => []],
        'usr_login' => ['userid' => []],
        'usr_identity' => ['userid' => []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
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
                if (empty($values)) {
                    continue;
                }
                if (!$this->columnExists($table, $column)) {
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

    protected function recordCleanup(string $table, string $column, $value): void
    {
        if (!array_key_exists($table, $this->cleanup)) {
            return;
        }
        if (!array_key_exists($column, $this->cleanup[$table])) {
            return;
        }
        $this->cleanup[$table][$column][] = $value;
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
        return array_map(fn ($row) => $row['Field'], $rows);
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

    protected function seedGeoHierarchy(string $prefix): array
    {
        $lgaId = random_int(5100, 5999);
        $wardId = random_int(6100, 6999);
        $dpId = random_int(7100, 7999);

        $lga = ['LgaId' => $lgaId, 'Fullname' => "{$prefix} LGA"];
        $ward = ['wardid' => $wardId, 'ward' => "{$prefix} Ward", 'lgaid' => $lgaId];
        $dp = ['dpid' => $dpId, 'dp' => "{$prefix} DP", 'wardid' => $wardId];

        $this->insertRow('ms_geo_lga', $lga);
        $this->insertRow('ms_geo_ward', $ward);
        $this->insertRow('ms_geo_dp', $dp);

        $this->recordCleanup('ms_geo_lga', 'LgaId', $lgaId);
        $this->recordCleanup('ms_geo_ward', 'wardid', $wardId);
        $this->recordCleanup('ms_geo_dp', 'dpid', $dpId);

        $codex = [
            'geo_level' => 'dp',
            'geo_level_id' => $dpId,
            'dpid' => $dpId,
            'lgaid' => $lgaId,
            'wardid' => $wardId,
            'title' => "{$prefix} DP",
            'geo_string' => "{$prefix}-{$lgaId}-{$wardId}",
            'geo_value' => "{$prefix} DP",
        ];
        $this->insertRow('sys_geo_codex', $codex);
        $this->recordCleanup('sys_geo_codex', 'dpid', $dpId);

        return ['lgaid' => $lgaId, 'wardid' => $wardId, 'dpid' => $dpId];
    }

    protected function seedMobilization(array $data): void
    {
        $this->insertRow('hhm_mobilization', $data);
        if (isset($data['hhid'])) {
            $this->recordCleanup('hhm_mobilization', 'hhid', $data['hhid']);
        }
    }

    protected function seedDistribution(array $data): void
    {
        $this->insertRow('hhm_distribution', $data);
        if (isset($data['etoken_serial'])) {
            $this->recordCleanup('hhm_distribution', 'etoken_serial', $data['etoken_serial']);
        }
    }

    protected function seedNetcard(array $data): void
    {
        $id = $this->insertRow('nc_netcard', $data);

        if (isset($data['ncid'])) {
            $this->recordCleanup('nc_netcard', 'ncid', $data['ncid']);
        } elseif ($id) {
            $this->recordCleanup('nc_netcard', 'ncid', $id);
        }

        if (isset($data['netcard_id'])) {
            $this->recordCleanup('nc_netcard', 'netcard_id', $data['netcard_id']);
        }
        if (isset($data['serial_number'])) {
            $this->recordCleanup('nc_netcard', 'serial_number', $data['serial_number']);
        }
    }

    protected function seedUser(int $userid, string $loginid): void
    {
        $login = [
            'userid' => $userid,
            'loginid' => $loginid,
            'username' => $loginid,
            'pwd' => password_hash('TestPass123', PASSWORD_BCRYPT),
            'roleid' => 1,
            'geo_level' => 'ward',
            'geo_level_id' => 0,
            'user_group' => 'test',
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ];
        $identity = [
            'userid' => $userid,
            'first' => 'Test',
            'middle' => 'User',
            'last' => 'Account',
        ];

        $this->insertRow('usr_login', $login);
        $this->insertRow('usr_identity', $identity);
        $this->recordCleanup('usr_login', 'userid', $userid);
        $this->recordCleanup('usr_identity', 'userid', $userid);
    }
}
