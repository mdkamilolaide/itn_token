<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\SmcMaster;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Master Controller
 * 
 * Tests the SMC master controller methods in isolation
 */
class SmcMasterControllerTest extends SmcTestCase
{
    public function testMasterLookups(): void
    {
        $this->requireSchema([
            'smc_commodity' => ['product_id', 'product_code', 'name'],
            'sms_reasons' => ['reason', 'category'],
            'smc_period' => ['periodid', 'title', 'active'],
        ]);

        $controller = new SmcMaster();

        $this->seedCommodity([
            'product_code' => 'PRD-' . uniqid(),
            'name' => 'Commodity',
            'description' => 'Desc',
            'min_age' => 3,
            'max_age' => 59,
            'extension_age' => 0,
        ]);

        $this->seedReason([
            'reason' => 'Fever',
            'category' => 'medical',
        ]);

        $activePeriod = $this->seedPeriod('Active Visit', 1);
        $this->assertGreaterThan(0, $activePeriod);

        $commodity = $controller->GetCommodity();
        $this->assertNotEmpty($commodity);

        $reasons = $controller->GetReasons();
        $this->assertNotEmpty($reasons);

        $periods = $controller->GetPeriodActive();
        $this->assertNotEmpty($periods);
    }

    public function testHouseholdChildAndLocationLists(): void
    {
        $this->requireSchema([
            'smc_child_household' => ['hhid', 'dpid', 'hh_token', 'hoh_name', 'hoh_phone'],
            'smc_child' => ['child_id', 'dpid', 'beneficiary_id', 'name'],
            'smc_drug_administration' => ['beneficiary_id', 'periodid', 'collected_date', 'dpid'],
            'smc_period' => ['periodid', 'title'],
            'sys_geo_codex' => ['dpid', 'geo_level'],
            'ms_geo_dp' => ['dpid', 'dp'],
            'smc_cms_location' => ['location_id', 'cms_name'],
            'ms_geo_lga' => ['LgaId', 'Fullname'],
        ]);

        $controller = new SmcMaster();
        $geo = $this->seedGeoHierarchy('MASTER');
        $this->seedGeoCodexDp($geo);

        $token = 'HH-' . uniqid();
        $this->seedHousehold([
            'dpid' => $geo['dpid'],
            'hh_token' => $token,
            'hoh_name' => 'Head',
            'hoh_phone' => '08000000000',
        ]);

        $this->seedChild([
            'dpid' => $geo['dpid'],
            'hh_token' => $token,
            'beneficiary_id' => 'BEN-' . uniqid(),
            'name' => 'Child',
            'gender' => 'male',
            'dob' => '2020-01-01',
        ]);

        $households = $controller->GetMasterHousehold($geo['dpid']);
        $this->assertNotEmpty($households);

        $children = $controller->GetMasterChild($geo['dpid']);
        $this->assertNotEmpty($children);

        $cmsId = $this->seedCmsLocation([
            'cms_name' => 'CMS',
            'level' => 'state',
            'address' => 'Address',
            'poc' => 'POC',
            'poc_phone' => '08000000000',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->assertGreaterThan(0, $cmsId);

        $cms = $controller->GetCmsLocations();
        $this->assertNotEmpty($cms);

        $facilities = $controller->GetFacilityLocations($geo['lgaid']);
        $this->assertNotEmpty($facilities);
    }

    public function testCddLeadAndConveyors(): void
    {
        $this->requireSchema([
            'usr_login' => ['userid', 'loginid', 'roleid', 'geo_level', 'geo_level_id'],
            'usr_identity' => ['userid', 'first', 'last', 'middle'],
            'usr_role' => ['roleid', 'title'],
        ]);

        $controller = new SmcMaster();
        $geo = $this->seedGeoHierarchy('CDD');
        $this->seedRole(54, 'CDD Lead');
        $this->seedRole(55, 'Conveyor');

        $this->seedUser(9101, 'dp', $geo['dpid'], 54);
        $this->seedUser(9102, 'dp', $geo['dpid'], 55);

        $cdd = $controller->GetCddLead($geo['dpid']);
        $this->assertNotEmpty($cdd);

        $conveyors = $controller->GetConveyors();
        $this->assertNotEmpty($conveyors);
    }
}
