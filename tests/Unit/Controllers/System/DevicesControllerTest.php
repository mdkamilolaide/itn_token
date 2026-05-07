<?php

namespace Tests\Unit\Controllers\System;

use System\Devices;

require_once __DIR__ . '/SystemTestCase.php';

/**
 * Unit Test: Devices System Controller
 * 
 * Tests the devices system controller methods in isolation
 */
class DevicesControllerTest extends SystemTestCase
{
    public function testRegisterDeviceCreatesSerialAndReusesExisting(): void
    {
        $this->requireSchema([
            'sys_device_registry' => ['id', 'device_name', 'device_id', 'guid', 'device_type', 'serial_no', 'created', 'updated'],
        ]);

        $controller = new Devices();

        $deviceId = 'DEV-' . uniqid();
        $created = $controller->RegisterDevice('Device A', $deviceId, 'android');
        $this->assertNotEmpty($created);
        $this->assertSame($deviceId, $created[0]['device_id']);
        $this->recordCleanup('sys_device_registry', 'id', $created[0]['id']);
        $this->recordCleanup('sys_device_registry', 'serial_no', $created[0]['serial_no']);
        $this->recordCleanup('sys_device_registry', 'device_id', $deviceId);

        $existing = $controller->RegisterDevice('Device A', $deviceId, 'android');
        $this->assertNotEmpty($existing);
        $this->assertSame($created[0]['id'], $existing[0]['id']);
    }

    public function testToggleActiveBulkAndDelete(): void
    {
        $this->requireSchema([
            'sys_device_registry' => ['id', 'serial_no', 'device_id', 'active'],
        ]);

        $controller = new Devices();

        $serialA = 'SER-' . uniqid();
        $serialB = 'SER-' . uniqid();

        $this->seedDevice([
            'device_name' => 'Device A',
            'device_id' => 'ID-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'android',
            'serial_no' => $serialA,
            'active' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->seedDevice([
            'device_name' => 'Device B',
            'device_id' => 'ID-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'android',
            'serial_no' => $serialB,
            'active' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $toggle = $controller->ToggleActive($serialA);
        $this->assertTrue((bool) $toggle);

        $bulkToggle = $controller->BulkToggleActive([$serialA, $serialB]);
        $this->assertSame(2, $bulkToggle);

        $deleted = $controller->BulkDelete([$serialA, $serialB]);
        $this->assertSame(2, $deleted);
    }

    public function testUpdateDeviceSerialFields(): void
    {
        $this->requireSchema([
            'sys_device_registry' => ['id', 'serial_no', 'imei1', 'imei2', 'phone_serial', 'sim_network', 'sim_serial'],
        ]);

        $controller = new Devices();

        $serial = 'SER-' . uniqid();
        $this->seedDevice([
            'device_name' => 'Device C',
            'device_id' => 'ID-' . uniqid(),
            'guid' => md5(uniqid('', true)),
            'device_type' => 'android',
            'serial_no' => $serial,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $updated = $controller->UpdateDeviceWithSerial('IMEI1', 'IMEI2', 'PHONE', 'NETWORK', 'SIM', $serial);
        $this->assertTrue((bool) $updated);

        $bulk = [[
            'imei1' => 'I1',
            'imei2' => 'I2',
            'phone_serial' => 'P1',
            'sim_network' => 'Net',
            'sim_serial' => 'Sim',
            'serial_no' => $serial,
        ]];
        $bulkUpdated = $controller->BulkUpdateDeviceWithSerial($bulk);
        $this->assertSame(1, $bulkUpdated);
    }
}
