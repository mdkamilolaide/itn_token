<?php

namespace Tests\Integration\Devices;

use System\Devices;

class DeviceAllocationTest extends DeviceTestCase
{
    /**
     * Test device allocation to mobilizer
     */
    public function testAllocateDeviceToMobilizer()
    {
        $this->requireDeviceSchema();

        $devices = new Devices();
        $deviceId = 'DEV-' . uniqid();

        $result = $devices->RegisterDevice('Test Device', $deviceId, 'ANDROID');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $row = $result[0];
        $this->assertSame($deviceId, $row['device_id']);
        $this->assertNotEmpty($row['serial_no']);
        $this->recordSerialCleanup($row['serial_no']);
        $this->recordDeviceIdCleanup($deviceId);

        $lookup = $devices->CheckDevice($deviceId);
        $this->assertNotEmpty($lookup);
        $this->assertSame($deviceId, $lookup[0]['device_id']);
    }

    /**
     * Test device reallocation workflow
     */
    public function testReallocateDevice()
    {
        $this->requireDeviceSchema();

        $devices = new Devices();
        $deviceId = 'DEV-' . uniqid();

        $first = $devices->RegisterDevice('Device A', $deviceId, 'ANDROID');
        $this->assertNotEmpty($first);

        $second = $devices->RegisterDevice('Device B', $deviceId, 'ANDROID');
        $this->assertSame($first[0]['serial_no'], $second[0]['serial_no']);
        $this->assertSame($deviceId, $second[0]['device_id']);

        $this->recordSerialCleanup($first[0]['serial_no']);
        $this->recordDeviceIdCleanup($deviceId);
    }

    /**
     * Test cannot allocate unavailable device
     */
    public function testCannotAllocateUnavailableDevice()
    {
        $this->requireDeviceSchema();

        $devices = new Devices();
        $deviceId = 'DEV-' . uniqid();

        $devices->RegisterDevice('Unavailable Device', $deviceId, 'ANDROID');
        $duplicate = $devices->RegisterDevice('Unavailable Device', $deviceId, 'ANDROID');

        $this->assertNotEmpty($duplicate);
        $this->assertSame($deviceId, $duplicate[0]['device_id']);
        $this->recordSerialCleanup($duplicate[0]['serial_no']);
        $this->recordDeviceIdCleanup($deviceId);
    }

    /**
     * Test device allocation history tracking
     */
    public function testDeviceAllocationHistory()
    {
        $this->requireDeviceSchema(['serial_no', 'imei1', 'imei2', 'phone_serial', 'sim_network', 'sim_serial']);

        $deviceId = 'DEV-' . uniqid();
        $id = $this->insertRow('sys_device_registry', [
            'device_name' => 'History Device',
            'device_id' => $deviceId,
            'guid' => md5(uniqid('', true)),
            'device_type' => 'ANDROID',
            'serial_no' => 'SRL' . random_int(100, 999),
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
            'active' => 1,
        ]);
        $this->recordDeviceIdCleanup($deviceId);

        $serialRow = $this->getDb()->DataTable("SELECT serial_no FROM sys_device_registry WHERE device_id = '{$deviceId}'");
        $this->assertNotEmpty($serialRow);
        $serial = $serialRow[0]['serial_no'];
        $this->recordSerialCleanup($serial);

        $devices = new Devices();
        $devices->UpdateDeviceWithSerial('111', '222', 'PHN', 'MTN', 'SIM123', $serial);

        $updated = $this->getDb()->DataTable("SELECT imei1, imei2, phone_serial, sim_network, sim_serial FROM sys_device_registry WHERE serial_no = '{$serial}'");
        $this->assertNotEmpty($updated);
        $this->assertSame('111', $updated[0]['imei1']);
        $this->assertSame('222', $updated[0]['imei2']);
        $this->assertSame('PHN', $updated[0]['phone_serial']);
        $this->assertSame('MTN', $updated[0]['sim_network']);
        $this->assertSame('SIM123', $updated[0]['sim_serial']);
    }

    private function requireDeviceSchema(array $extra = []): void
    {
        $required = array_merge([
            'device_id',
            'device_name',
            'guid',
            'device_type',
            'serial_no',
            'created',
            'updated',
        ], $extra);

        if (!$this->tableHasColumns('sys_device_registry', $required)) {
            $this->markTestSkipped('Device registry schema not available');
        }
    }
}
