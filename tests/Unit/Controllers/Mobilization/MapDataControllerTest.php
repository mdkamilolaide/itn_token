<?php

namespace Tests\Unit\Controllers\Mobilization;

use Mobilization\MapData;

/**
 * Unit Test: Map Data Controller
 * 
 * Tests the map data controller methods in isolation
 */
class MapDataControllerTest extends MobilizationTestCase
{
    public function testGetDpDataFiltersByDpAndDate(): void
    {
        $this->requireMapSchema();

        $controller = new MapData();
        $geo = $this->seedGeoHierarchy('DP');
        $this->seedDefaultSettings([
            'stateid' => $geo['stateid'],
        ]);

        $mobilizerId = random_int(2000, 2999);
        $loginId = 'mob.' . $mobilizerId;
        $this->seedUser($mobilizerId, $loginId, 'dp', $geo['dpid']);

        $this->seedMobilization([
            'hhid' => random_int(70000, 79999),
            'dp_id' => $geo['dpid'],
            'hhm_id' => $mobilizerId,
            'hoh_first' => 'A',
            'hoh_last' => 'B',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'M',
            'family_size' => 3,
            'allocated_net' => 1,
            'longitude' => '10.1',
            'Latitude' => '11.2',
            'etoken_serial' => 'ET-' . uniqid(),
            'collected_date' => '2099-03-10',
        ]);

        $otherGeo = $this->seedGeoHierarchy('DP2');
        $this->seedMobilization([
            'hhid' => random_int(80000, 89999),
            'dp_id' => $otherGeo['dpid'],
            'hhm_id' => $mobilizerId,
            'hoh_first' => 'C',
            'hoh_last' => 'D',
            'hoh_phone' => '08000000001',
            'hoh_gender' => 'F',
            'family_size' => 4,
            'allocated_net' => 2,
            'longitude' => '10.1',
            'Latitude' => '11.2',
            'etoken_serial' => 'ET-' . uniqid(),
            'collected_date' => '2099-03-10',
        ]);

        $result = $controller->GetDpData($geo['wardid'], $geo['dpid'], '2099-03-10');
        $this->assertCount(1, $result['mob_data']);
    }

    public function testGetTestAllDataReturnsDefaultMap(): void
    {
        $this->requireMapSchema();

        $controller = new MapData();
        $geo = $this->seedGeoHierarchy('TestAll');

        $mobilizerId = random_int(5000, 5999);
        $loginId = 'mob.' . $mobilizerId;
        $this->seedUser($mobilizerId, $loginId, 'dp', $geo['dpid']);

        $this->seedMobilization([
            'hhid' => random_int(110000, 119999),
            'dp_id' => $geo['dpid'],
            'hhm_id' => $mobilizerId,
            'hoh_first' => 'A',
            'hoh_last' => 'B',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'M',
            'family_size' => 3,
            'allocated_net' => 1,
            'longitude' => '10.1',
            'Latitude' => '11.2',
            'etoken_serial' => 'ET-' . uniqid(),
            'collected_date' => '2099-06-10',
        ]);

        $result = $controller->GetTestAllData();
        $this->assertNotEmpty($result['mob_data']);
        $this->assertSame('12', (string) $result['map']['zoom']);
    }

    private function requireMapSchema(): void
    {
        $mobColumns = ['hhid', 'dp_id', 'hhm_id', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'longitude', 'Latitude', 'etoken_serial', 'collected_date'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];
        $geoColumns = ['dpid', 'wardid', 'lgaid', 'stateid'];
        $stateColumns = ['StateId', 'Fullname', 'longitude', 'latitude'];
        $settingsColumns = ['stateid'];

        if (!$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
            || !$this->tableHasColumns('ms_geo_state', $stateColumns)
            || !$this->tableHasColumns('sys_default_settings', $settingsColumns)
        ) {
            $this->markTestSkipped('Map data schema not available');
        }
    }
}
