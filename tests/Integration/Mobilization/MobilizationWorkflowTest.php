<?php

namespace Tests\Integration\Mobilization;

use Mobilization\Mobilization;

class MobilizationWorkflowTest extends MobilizationTestCase
{
    public function testBulkMobilizationCreatesRecordsAndUpdatesAssets(): void
    {
        $this->requireMobilizationSchema();

        $mobilization = new Mobilization();
        $geo = $this->seedGeoHierarchy('Workflow');

        $userId = random_int(2000, 2999);
        $this->seedUser($userId, 'mob.' . $userId);

        $tokenId = $this->seedToken(md5(uniqid('', true)), 'TOK' . random_int(100, 999));
        $netUuid = md5(uniqid('', true));
        $this->seedNetcard([
            'uuid' => $netUuid,
            'mobilizer_userid' => $userId,
            'device_serial' => 'DEV',
        ]);

        $bulk = [[
            'dp_id' => $geo['dpid'],
            'comid' => 4001,
            'hm_id' => $userId,
            'co_hm_id' => 0,
            'hoh_first' => 'Jane',
            'hoh_last' => 'Doe',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'hod_mother' => 'Mary Doe',
            'sleeping_space' => 2,
            'adult_female' => 2,
            'adult_male' => 2,
            'children' => 2,
            'allocated_net' => 1,
            'location_description' => 'Household',
            'longitude' => '7.111',
            'latitude' => '9.222',
            'netcards' => $netUuid,
            'etoken_id' => $tokenId,
            'etoken_serial' => 'ET-' . uniqid(),
            'etoken_pin' => '12345',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'collected_date' => '2099-10-02',
            'eolin_have_old_net' => 1,
            'eolin_total_old_net' => 1,
        ]];

        $result = $mobilization->BulkMobilization($bulk);
        $this->assertSame(1, $result);

        $mobilized = $this->getDb()->DataTable("SELECT etoken_serial FROM hhm_mobilization WHERE etoken_serial = '{$bulk[0]['etoken_serial']}'");
        $this->assertNotEmpty($mobilized);

        $netRow = $this->getDb()->DataTable("SELECT location_value, status FROM nc_netcard WHERE uuid = '{$netUuid}'");
        $this->assertSame(20, (int) $netRow[0]['location_value']);
        $this->assertStringContainsString('beneficiary', $netRow[0]['status']);

        $tokenRow = $this->getDb()->DataTable("SELECT status, status_code FROM nc_token WHERE tokenid = {$tokenId}");
        $this->assertSame('used', $tokenRow[0]['status']);
        $this->assertSame(5, (int) $tokenRow[0]['status_code']);
    }

    public function testBulkMobilizationReturnsOneWhenAllDuplicates(): void
    {
        $this->requireMobilizationSchema();

        $mobilization = new Mobilization();
        $geo = $this->seedGeoHierarchy('Duplicate');

        $tokenId = $this->seedToken(md5(uniqid('', true)), 'TOK' . random_int(100, 999));
        $serial = 'ET-' . uniqid();

        $bulk = [[
            'dp_id' => $geo['dpid'],
            'comid' => 4001,
            'hm_id' => 1,
            'co_hm_id' => 0,
            'hoh_first' => 'Jane',
            'hoh_last' => 'Doe',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'allocated_net' => 1,
            'location_description' => 'Household',
            'longitude' => '7.111',
            'latitude' => '9.222',
            'netcards' => '',
            'etoken_id' => $tokenId,
            'etoken_serial' => $serial,
            'etoken_pin' => '12345',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'collected_date' => '2099-10-02',
            'eolin_have_old_net' => 0,
            'eolin_total_old_net' => 0,
        ]];

        $first = $mobilization->BulkMobilization($bulk);
        $this->assertSame(1, $first);

        $second = $mobilization->BulkMobilization($bulk);
        $this->assertSame(1, $second);
    }
}
