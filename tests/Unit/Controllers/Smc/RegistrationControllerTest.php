<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Registration;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Registration Controller
 * 
 * Tests the SMC registration controller methods in isolation
 */
class RegistrationControllerTest extends SmcTestCase
{
    public function testHouseholdCrudAndBulk(): void
    {
        $this->requireSchema([
            'smc_child_household' => ['hhid', 'dpid', 'hh_token', 'hoh_name', 'hoh_phone', 'created', 'updated', 'longitude', 'latitude', 'user_id'],
        ]);

        $controller = new Registration();
        $geo = $this->seedGeoHierarchy('REG');

        $token = 'HH-' . uniqid();
        $householdId = $controller->CreateHousehold($token, 'Head Name', '08000000000');
        $this->assertIsNumeric($householdId);
        $this->recordCleanup('smc_child_household', 'hhid', $householdId);
        $this->recordCleanup('smc_child_household', 'hh_token', $token);

        $updated = $controller->UpdateHousehold($token, 'Updated Name', '08000000001', $householdId);
        $this->assertTrue((bool) $updated);

        $bulk = [[
            'dpid' => $geo['dpid'],
            'hh_token' => 'HH-' . uniqid(),
            'hoh' => 'Bulk Head',
            'phone' => '08000000002',
            'longitude' => '1.1',
            'latitude' => '2.2',
            'user_id' => 1,
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'created' => date('Y-m-d H:i:s'),
        ]];

        $bulkResult = $controller->CreateHouseholdBulk($bulk);
        $this->assertSame([$bulk[0]['hh_token']], $bulkResult);

        $bulkUpdate = [[
            'hh_token' => $bulk[0]['hh_token'],
            'hoh' => 'Bulk Updated',
            'phone' => '08000000003',
        ]];
        $updatedBulk = $controller->UpdateHouseholdBulk($bulkUpdate);
        $this->assertSame([$bulk[0]['hh_token']], $updatedBulk);

        $deleted = $controller->DeleteHousehold($householdId);
        $this->assertTrue((bool) $deleted);
    }

    public function testChildBulkOperations(): void
    {
        $this->requireSchema([
            'smc_child' => ['child_id', 'hh_token', 'beneficiary_id', 'dpid', 'name', 'gender', 'dob', 'created', 'updated', 'longitude', 'latitude', 'user_id'],
        ]);

        $controller = new Registration();

        $bulk = [[
            'hh_token' => 'HH-' . uniqid(),
            'beneficiary_id' => 'BEN-' . uniqid(),
            'dpid' => 100,
            'name' => 'Child',
            'gender' => 'male',
            'dob' => '2020-01-01',
            'longitude' => '1.1',
            'latitude' => '2.2',
            'user_id' => 1,
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'created' => date('Y-m-d H:i:s'),
        ]];

        $created = $controller->CreateChildBulk($bulk);
        $this->assertSame([$bulk[0]['beneficiary_id']], $created);

        $update = [[
            'beneficiary_id' => $bulk[0]['beneficiary_id'],
            'name' => 'Child Updated',
            'gender' => 'female',
            'dob' => '2020-02-01',
        ]];

        $updated = $controller->UpdateChildBulk($update);
        $this->assertSame([$bulk[0]['beneficiary_id']], $updated);
    }
}
