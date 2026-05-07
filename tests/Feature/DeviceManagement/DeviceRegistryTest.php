<?php

namespace Tests\Feature\DeviceManagement;

use Tests\TestCase;

/**
 * Device registry and lifecycle tests.
 */
class DeviceRegistryTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/system/devices.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';

        $this->ensureDeviceRegistrySchema();
    }

    public function testRegisterAndCheckDevice(): void
    {
        $deviceId = $this->uniqueDeviceId();
        $devices = new \System\Devices();

        $created = $devices->RegisterDevice('Unit Test Device', $deviceId, 'Mobile Phone');

        $this->assertIsArray($created);
        $this->assertNotEmpty($created);
        $this->assertEquals($deviceId, $created[0]['device_id']);
        $this->assertNotEmpty($created[0]['serial_no']);

        $checked = $devices->CheckDevice($deviceId);
        $this->assertIsArray($checked);
        $this->assertNotEmpty($checked);
        $this->assertEquals($deviceId, $checked[0]['device_id']);

        $this->cleanupDevice($deviceId);
    }

    public function testToggleActiveStatus(): void
    {
        $deviceId = $this->uniqueDeviceId();
        $devices = new \System\Devices();

        $created = $devices->RegisterDevice('Toggle Device', $deviceId, 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $devices->ToggleActive($serial);
        $active = $this->getDeviceField($serial, 'active');
        $this->assertEquals(1, (int) $active);

        $devices->ToggleActive($serial);
        $active = $this->getDeviceField($serial, 'active');
        $this->assertEquals(0, (int) $active);

        $this->cleanupDevice($deviceId);
    }

    public function testBulkToggleActive(): void
    {
        $devices = new \System\Devices();

        $first = $devices->RegisterDevice('Bulk Toggle 1', $this->uniqueDeviceId(), 'Mobile Phone');
        $second = $devices->RegisterDevice('Bulk Toggle 2', $this->uniqueDeviceId(), 'Mobile Phone');

        $serials = [$first[0]['serial_no'], $second[0]['serial_no']];

        $count = $devices->BulkToggleActive($serials);
        $this->assertEquals(2, $count);

        foreach ($serials as $serial) {
            $this->assertEquals(1, (int) $this->getDeviceField($serial, 'active'));
        }

        $devices->BulkToggleActive($serials);
        foreach ($serials as $serial) {
            $this->assertEquals(0, (int) $this->getDeviceField($serial, 'active'));
        }

        $this->cleanupSerials($serials);
    }

    public function testUpdateDeviceWithSerial(): void
    {
        $devices = new \System\Devices();
        $created = $devices->RegisterDevice('Update Device', $this->uniqueDeviceId(), 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $updated = $devices->UpdateDeviceWithSerial('IMEI1', 'IMEI2', 'PHONE123', 'MTN', 'SIM123', $serial);
        $this->assertTrue((bool) $updated);

        $this->assertEquals('IMEI1', $this->getDeviceField($serial, 'imei1'));
        $this->assertEquals('IMEI2', $this->getDeviceField($serial, 'imei2'));
        $this->assertEquals('PHONE123', $this->getDeviceField($serial, 'phone_serial'));
        $this->assertEquals('MTN', $this->getDeviceField($serial, 'sim_network'));
        $this->assertEquals('SIM123', $this->getDeviceField($serial, 'sim_serial'));

        $this->cleanupSerials([$serial]);
    }

    public function testBulkUpdateAndDelete(): void
    {
        $devices = new \System\Devices();

        $first = $devices->RegisterDevice('Bulk Update 1', $this->uniqueDeviceId(), 'Mobile Phone');
        $second = $devices->RegisterDevice('Bulk Update 2', $this->uniqueDeviceId(), 'Mobile Phone');

        $payload = [
            [
                'imei1' => 'IMEI1A',
                'imei2' => 'IMEI2A',
                'phone_serial' => 'PHONEA',
                'sim_network' => 'AIRTEL',
                'sim_serial' => 'SIMA',
                'serial_no' => $first[0]['serial_no'],
            ],
            [
                'imei1' => 'IMEI1B',
                'imei2' => 'IMEI2B',
                'phone_serial' => 'PHONEB',
                'sim_network' => 'GLO',
                'sim_serial' => 'SIMB',
                'serial_no' => $second[0]['serial_no'],
            ],
        ];

        $count = $devices->BulkUpdateDeviceWithSerial($payload);
        $this->assertEquals(2, $count);

        $this->assertEquals('IMEI1A', $this->getDeviceField($first[0]['serial_no'], 'imei1'));
        $this->assertEquals('IMEI1B', $this->getDeviceField($second[0]['serial_no'], 'imei1'));

        $deleted = $devices->BulkDelete([$first[0]['serial_no'], $second[0]['serial_no']]);
        $this->assertEquals(2, $deleted);

        $this->assertEmpty($this->getDeviceRow($first[0]['serial_no']));
        $this->assertEmpty($this->getDeviceRow($second[0]['serial_no']));
    }

    private function uniqueDeviceId(): string
    {
        return 'dev_' . uniqid('', true);
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function ensureDeviceRegistrySchema(): void
    {
        $db = $this->getDb();
        $columns = $db->DataTable('SHOW COLUMNS FROM sys_device_registry');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $additions = [];
        if (!in_array('device_id', $existing, true)) {
            $additions[] = 'ADD COLUMN device_id varchar(50) DEFAULT NULL';
        }
        if (!in_array('guid', $existing, true)) {
            $additions[] = 'ADD COLUMN guid varchar(50) DEFAULT NULL';
        }
        if (!in_array('device_type', $existing, true)) {
            $additions[] = 'ADD COLUMN device_type varchar(50) DEFAULT NULL';
        }
        if (!in_array('imei1', $existing, true)) {
            $additions[] = 'ADD COLUMN imei1 varchar(100) DEFAULT NULL';
        }
        if (!in_array('imei2', $existing, true)) {
            $additions[] = 'ADD COLUMN imei2 varchar(100) DEFAULT NULL';
        }
        if (!in_array('phone_serial', $existing, true)) {
            $additions[] = 'ADD COLUMN phone_serial varchar(50) DEFAULT NULL';
        }
        if (!in_array('sim_network', $existing, true)) {
            $additions[] = 'ADD COLUMN sim_network varchar(50) DEFAULT NULL';
        }
        if (!in_array('sim_serial', $existing, true)) {
            $additions[] = 'ADD COLUMN sim_serial varchar(50) DEFAULT NULL';
        }
        if (!in_array('active', $existing, true)) {
            $additions[] = 'ADD COLUMN active tinyint(1) DEFAULT 0';
        }
        if (!in_array('updated', $existing, true)) {
            $additions[] = 'ADD COLUMN updated datetime DEFAULT NULL';
        }

        if (count($additions) > 0) {
            $db->Execute('ALTER TABLE sys_device_registry ' . implode(', ', $additions), []);
        }
    }

    private function getDeviceField(string $serial, string $field): ?string
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT $field FROM sys_device_registry WHERE serial_no = '$serial' LIMIT 1");
        if (count($rows) === 0) {
            return null;
        }

        return (string) $rows[0][$field];
    }

    private function getDeviceRow(string $serial): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT * FROM sys_device_registry WHERE serial_no = '$serial' LIMIT 1");
        return $rows ?: [];
    }

    private function cleanupDevice(string $deviceId): void
    {
        $db = $this->getDb();
        $db->Execute("DELETE FROM sys_device_registry WHERE device_id = ?", [$deviceId]);
    }

    private function cleanupSerials(array $serials): void
    {
        $db = $this->getDb();
        foreach ($serials as $serial) {
            $db->Execute("DELETE FROM sys_device_registry WHERE serial_no = ?", [$serial]);
        }
    }
}
