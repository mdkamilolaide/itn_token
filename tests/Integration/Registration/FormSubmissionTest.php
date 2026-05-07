<?php

namespace Tests\Integration\Registration;

use Smc\Registration;

class FormSubmissionTest extends RegistrationTestCase
{
    public function testBulkHouseholdRegistrationPersistsRows(): void
    {
        $this->requireHouseholdSchema([
            'dpid',
            'hh_token',
            'hoh_name',
            'hoh_phone',
            'longitude',
            'latitude',
            'user_id',
            'device_serial',
            'app_version',
            'created',
            'updated',
        ]);

        $registration = new Registration();
        $geo = $this->seedGeoHierarchy('House');

        $bulk = [
            [
                'dpid' => $geo['dpid'],
                'hh_token' => 'HH-' . uniqid('', true),
                'hoh' => 'Sam Doe',
                'phone' => '07000000001',
                'longitude' => '7.0001',
                'latitude' => '9.0001',
                'user_id' => 1,
                'device_serial' => 'DEV-' . uniqid('', true),
                'app_version' => '1.0',
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'dpid' => $geo['dpid'],
                'hh_token' => 'HH-' . uniqid('', true),
                'hoh' => 'Ann Doe',
                'phone' => '07000000002',
                'longitude' => '7.0002',
                'latitude' => '9.0002',
                'user_id' => 1,
                'device_serial' => 'DEV-' . uniqid('', true),
                'app_version' => '1.0',
                'created' => date('Y-m-d H:i:s'),
            ],
        ];

        $result = $registration->CreateHouseholdBulk($bulk);
        $this->assertCount(2, $result);

        foreach ($bulk as $row) {
            $this->recordCleanup('smc_child_household', 'hh_token', $row['hh_token']);
        }

        $tokens = array_map(static fn ($row) => "'{$row['hh_token']}'", $bulk);
        $rows = $this->getDb()->DataTable('SELECT hh_token FROM smc_child_household WHERE hh_token IN (' . implode(',', $tokens) . ')');
        $this->assertCount(2, $rows);
    }

    public function testBulkChildRegistrationPersistsRows(): void
    {
        $this->requireChildSchema([
            'hh_token',
            'beneficiary_id',
            'dpid',
            'name',
            'gender',
            'dob',
            'longitude',
            'latitude',
            'user_id',
            'device_serial',
            'app_version',
            'created',
            'updated',
        ]);

        $registration = new Registration();
        $geo = $this->seedGeoHierarchy('Child');

        $bulk = [
            [
                'hh_token' => 'HH-' . uniqid('', true),
                'beneficiary_id' => 'BEN-' . uniqid('', true),
                'dpid' => $geo['dpid'],
                'name' => 'Child One',
                'gender' => 'female',
                'dob' => '2020-01-01',
                'longitude' => '7.1001',
                'latitude' => '9.1001',
                'user_id' => 1,
                'device_serial' => 'DEV-' . uniqid('', true),
                'app_version' => '1.0',
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'hh_token' => 'HH-' . uniqid('', true),
                'beneficiary_id' => 'BEN-' . uniqid('', true),
                'dpid' => $geo['dpid'],
                'name' => 'Child Two',
                'gender' => 'male',
                'dob' => '2019-05-05',
                'longitude' => '7.1002',
                'latitude' => '9.1002',
                'user_id' => 1,
                'device_serial' => 'DEV-' . uniqid('', true),
                'app_version' => '1.0',
                'created' => date('Y-m-d H:i:s'),
            ],
        ];

        $result = $registration->CreateChildBulk($bulk);
        $this->assertCount(2, $result);

        foreach ($bulk as $row) {
            $this->recordCleanup('smc_child', 'beneficiary_id', $row['beneficiary_id']);
        }

        $ids = array_map(static fn ($row) => "'{$row['beneficiary_id']}'", $bulk);
        $rows = $this->getDb()->DataTable('SELECT beneficiary_id FROM smc_child WHERE beneficiary_id IN (' . implode(',', $ids) . ')');
        $this->assertCount(2, $rows);
    }
}
