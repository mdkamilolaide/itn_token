<?php

namespace Tests\Integration\Devices;

use Tests\TestCase;

abstract class DeviceTestCase extends TestCase
{
    protected string $projectRoot;
    private array $cleanupSerials = [];
    private array $cleanupDeviceIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/common.php';
        require_once $this->projectRoot . '/lib/controller/system/devices.cont.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();
        if ($this->tableHasColumns('sys_device_registry', ['serial_no'])) {
            foreach ($this->cleanupSerials as $serial) {
                $db->executeTransaction('DELETE FROM sys_device_registry WHERE serial_no = ?', [$serial]);
            }
        }
        if ($this->tableHasColumns('sys_device_registry', ['device_id'])) {
            foreach ($this->cleanupDeviceIds as $deviceId) {
                $db->executeTransaction('DELETE FROM sys_device_registry WHERE device_id = ?', [$deviceId]);
            }
        }

        parent::tearDown();
    }

    protected function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    protected function recordSerialCleanup(string $serial): void
    {
        $this->cleanupSerials[] = $serial;
    }

    protected function recordDeviceIdCleanup(string $deviceId): void
    {
        $this->cleanupDeviceIds[] = $deviceId;
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
}
