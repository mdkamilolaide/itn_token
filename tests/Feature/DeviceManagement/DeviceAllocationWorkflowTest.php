<?php

namespace Tests\Feature\DeviceManagement;

use Tests\TestCase;

class DeviceAllocationWorkflowTest extends TestCase
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

    /**
     * Test complete device allocation workflow from request to deployment
     */
    public function testCompleteDeviceAllocationWorkflow()
    {
        $devices = new \System\Devices();
        $deviceId = $this->uniqueDeviceId();

        $created = $devices->RegisterDevice('Workflow Device', $deviceId, 'Mobile Phone');
        $this->assertNotEmpty($created);

        $serial = $created[0]['serial_no'];
        $this->assertNotEmpty($serial);

        $devices->UpdateDeviceWithSerial('IMEI-ONE', 'IMEI-TWO', 'PHONE-SN', 'MTN', 'SIM-SN', $serial);
        $devices->ToggleActive($serial);

        $this->setDeviceConnection($serial, 'MOB001');

        $checked = $devices->CheckDevice($deviceId);
        $this->assertNotEmpty($checked);
        $this->assertEquals('MOB001', $this->getDeviceField($serial, 'connected_loginid'));
        $this->assertEquals(1, (int) $this->getDeviceField($serial, 'active'));

        $this->clearDeviceConnection($serial);
        $devices->ToggleActive($serial);

        $this->assertEquals(0, (int) $this->getDeviceField($serial, 'active'));

        $this->cleanupDevice($deviceId);
    }

    /**
     * Test mobilizer can request device and receive allocation
     */
    public function testMobilizerDeviceRequestWorkflow()
    {
        $devices = new \System\Devices();
        $deviceId = $this->uniqueDeviceId();

        $created = $devices->RegisterDevice('Request Device', $deviceId, 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $this->setDeviceConnection($serial, 'MOB002');
        $checked = $devices->CheckDevice($deviceId);
        $this->assertNotEmpty($checked);
        $this->assertEquals('MOB002', $this->getDeviceField($serial, 'connected_loginid'));

        $this->cleanupDevice($deviceId);
    }

    /**
     * Test device deployment and activation
     */
    public function testDeviceDeploymentWorkflow()
    {
        $devices = new \System\Devices();
        $deviceId = $this->uniqueDeviceId();

        $created = $devices->RegisterDevice('Deploy Device', $deviceId, 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $devices->UpdateDeviceWithSerial('IMEI-DEP-1', 'IMEI-DEP-2', 'PHONE-DEP', 'AIRTEL', 'SIM-DEP', $serial);
        $devices->ToggleActive($serial);

        $this->assertEquals(1, (int) $this->getDeviceField($serial, 'active'));
        $this->assertEquals('IMEI-DEP-1', $this->getDeviceField($serial, 'imei1'));

        $this->cleanupDevice($deviceId);
    }

    /**
     * Test device tracking and monitoring
     */
    public function testDeviceTrackingWorkflow()
    {
        $devices = new \System\Devices();
        $deviceId = $this->uniqueDeviceId();

        $created = $devices->RegisterDevice('Track Device', $deviceId, 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $this->setDeviceConnection($serial, 'MOBTRACK');
        $checked = $devices->CheckDevice($deviceId);
        $this->assertNotEmpty($checked);
        $this->assertEquals('MOBTRACK', $this->getDeviceField($serial, 'connected_loginid'));
        $this->assertNotEmpty($this->getDeviceField($serial, 'connected'));

        $this->cleanupDevice($deviceId);
    }

    /**
     * Test device return and deactivation
     */
    public function testDeviceReturnWorkflow()
    {
        $devices = new \System\Devices();
        $deviceId = $this->uniqueDeviceId();

        $created = $devices->RegisterDevice('Return Device', $deviceId, 'Mobile Phone');
        $serial = $created[0]['serial_no'];

        $devices->ToggleActive($serial);
        $this->setDeviceConnection($serial, 'MOBRETURN');

        $this->clearDeviceConnection($serial);
        $devices->ToggleActive($serial);

        $this->assertEquals(0, (int) $this->getDeviceField($serial, 'active'));
        $this->assertNull($this->getDeviceField($serial, 'connected_loginid'));

        $this->cleanupDevice($deviceId);
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

        return $rows[0][$field] !== null ? (string) $rows[0][$field] : null;
    }

    private function setDeviceConnection(string $serial, string $loginId): void
    {
        $db = $this->getDb();
        $db->Execute(
            "UPDATE sys_device_registry SET connected = ?, connected_loginid = ? WHERE serial_no = ?",
            [date('Y-m-d H:i:s'), $loginId, $serial]
        );
    }

    private function clearDeviceConnection(string $serial): void
    {
        $db = $this->getDb();
        $db->Execute(
            "UPDATE sys_device_registry SET connected = NULL, connected_loginid = NULL WHERE serial_no = ?",
            [$serial]
        );
    }

    private function cleanupDevice(string $deviceId): void
    {
        $db = $this->getDb();
        $db->Execute("DELETE FROM sys_device_registry WHERE device_id = ?", [$deviceId]);
    }
}
