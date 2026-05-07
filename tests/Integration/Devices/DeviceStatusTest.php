<?php

namespace Tests\Integration\Devices;

use System\Devices;

class DeviceStatusTest extends DeviceTestCase
{
    /**
     * Test device status transitions
     */
    public function testDeviceStatusTransitions()
    {
        $this->requireDeviceSchema();

        $serial = 'SRL' . random_int(1000, 9999);
        $this->insertRow('sys_device_registry', [
            'device_name' => 'Status Device',
            'device_id' => 'DEV-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'ANDROID',
            'serial_no' => $serial,
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordSerialCleanup($serial);

        $devices = new Devices();
        $devices->ToggleActive($serial);
        $row = $this->getDb()->DataTable("SELECT active FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertNotEmpty($row);
        $this->assertSame(0, (int) $row[0]['active']);

        $devices->ToggleActive($serial);
        $row2 = $this->getDb()->DataTable("SELECT active FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertSame(1, (int) $row2[0]['active']);
    }

    /**
     * Test device activation
     */
    public function testDeviceActivation()
    {
        $this->requireDeviceSchema();

        $serial = 'SRL' . random_int(1000, 9999);
        $this->insertRow('sys_device_registry', [
            'device_name' => 'Activate Device',
            'device_id' => 'DEV-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'ANDROID',
            'serial_no' => $serial,
            'active' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordSerialCleanup($serial);

        $devices = new Devices();
        $devices->ToggleActive($serial);
        $row = $this->getDb()->DataTable("SELECT active FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertSame(1, (int) $row[0]['active']);
    }

    /**
     * Test device deactivation
     */
    public function testDeviceDeactivation()
    {
        $this->requireDeviceSchema();

        $serial = 'SRL' . random_int(1000, 9999);
        $this->insertRow('sys_device_registry', [
            'device_name' => 'Deactivate Device',
            'device_id' => 'DEV-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'ANDROID',
            'serial_no' => $serial,
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordSerialCleanup($serial);

        $devices = new Devices();
        $devices->ToggleActive($serial);
        $row = $this->getDb()->DataTable("SELECT active FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertSame(0, (int) $row[0]['active']);
    }

    /**
     * Test invalid status transition
     */
    public function testInvalidStatusTransition()
    {
        $this->requireDeviceSchema();

        $serials = [];
        for ($i = 0; $i < 2; $i++) {
            $serial = 'SRL' . random_int(1000, 9999);
            $serials[] = $serial;
            $this->insertRow('sys_device_registry', [
                'device_name' => 'Bulk Device',
                'device_id' => 'DEV-' . uniqid(),
                'guid' => md5(uniqid('', true)),
                'device_type' => 'ANDROID',
                'serial_no' => $serial,
                'active' => 1,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]);
            $this->recordSerialCleanup($serial);
        }

        $devices = new Devices();
        $count = $devices->BulkToggleActive($serials);
        $this->assertSame(2, $count);

        $rows = $this->getDb()->DataTable("SELECT active FROM sys_device_registry WHERE serial_no IN ('{$serials[0]}','{$serials[1]}')");
        $this->assertCount(2, $rows);
    }

    public function testBulkDeleteRemovesDevices(): void
    {
        $this->requireDeviceSchema();

        $serials = [];
        for ($i = 0; $i < 2; $i++) {
            $serial = 'SRL' . random_int(1000, 9999);
            $serials[] = $serial;
            $this->insertRow('sys_device_registry', [
                'device_name' => 'Delete Device',
                'device_id' => 'DEV-' . uniqid(),
                'guid' => md5(uniqid('', true)),
                'device_type' => 'ANDROID',
                'serial_no' => $serial,
                'active' => 1,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]);
        }

        $devices = new Devices();
        $deleted = $devices->BulkDelete($serials);
        $this->assertSame(2, $deleted);

        $rows = $this->getDb()->DataTable("SELECT serial_no FROM sys_device_registry WHERE serial_no IN ('{$serials[0]}','{$serials[1]}')");
        $this->assertCount(0, $rows);
    }

    public function testBulkUpdateDeviceWithSerial(): void
    {
        $this->requireDeviceSchema(['imei1', 'imei2', 'phone_serial', 'sim_network', 'sim_serial']);

        $serial = 'SRL' . random_int(1000, 9999);
        $this->insertRow('sys_device_registry', [
            'device_name' => 'Bulk Update Device',
            'device_id' => 'DEV-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'ANDROID',
            'serial_no' => $serial,
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordSerialCleanup($serial);

        $devices = new Devices();
        $count = $devices->BulkUpdateDeviceWithSerial([
            [
                'serial_no' => $serial,
                'imei1' => '111',
                'imei2' => '222',
                'phone_serial' => 'PHN',
                'sim_network' => 'GLO',
                'sim_serial' => 'SIM456',
            ]
        ]);
        $this->assertSame(1, $count);

        $row = $this->getDb()->DataTable("SELECT imei1, imei2, phone_serial, sim_network, sim_serial FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertNotEmpty($row);
        $this->assertSame('111', $row[0]['imei1']);
        $this->assertSame('222', $row[0]['imei2']);
        $this->assertSame('PHN', $row[0]['phone_serial']);
        $this->assertSame('GLO', $row[0]['sim_network']);
        $this->assertSame('SIM456', $row[0]['sim_serial']);
    }

    private function requireDeviceSchema(array $extra = []): void
    {
        $required = array_merge([
            'device_id',
            'device_name',
            'guid',
            'device_type',
            'serial_no',
            'active',
            'created',
            'updated',
        ], $extra);

        if (!$this->tableHasColumns('sys_device_registry', $required)) {
            $this->markTestSkipped('Device registry schema not available');
        }
    }
}
