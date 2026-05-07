<?php

namespace Tests\Unit\Controllers\System;

use System\General;

require_once __DIR__ . '/SystemTestCase.php';

/**
 * Unit Test: General System Controller
 * 
 * Tests the general system controller methods in isolation
 */
class GeneralControllerTest extends SystemTestCase
{
    public function testBankAndGeoLists(): void
    {
        $this->requireSchema([
            'sys_bank_code' => ['bank_code', 'bank_name'],
            'ms_geo_state' => ['StateId', 'Fullname'],
            'ms_geo_lga' => ['LgaId', 'Fullname', 'StateId'],
            'ms_geo_ward' => ['wardid', 'ward', 'lgaid'],
            'ms_geo_dp' => ['dpid', 'dp', 'wardid'],
            'ms_geo_cluster' => ['clusterid', 'cluster', 'lgaid'],
        ]);

        $controller = new General();
        $geo = $this->seedGeoHierarchy('GEN');

        $this->insertRow('sys_bank_code', [
            'bank_code' => '001',
            'bank_name' => 'Test Bank',
        ]);
        $this->recordCleanup('sys_bank_code', 'bank_code', '001');

        $banks = $controller->GetBankList();
        $this->assertNotEmpty($banks);

        $states = $controller->GetStateList();
        $this->assertNotEmpty($states);

        $lgas = $controller->GetLgaList($geo['stateid']);
        $this->assertNotEmpty($lgas);

        $thisLga = $controller->GetThisLgaList($geo['lgaid']);
        $this->assertNotEmpty($thisLga);

        $wards = $controller->GetWardList($geo['lgaid']);
        $this->assertNotEmpty($wards);

        $dps = $controller->GetDpList($geo['wardid']);
        $this->assertNotEmpty($dps);

        $dpsByLga = $controller->GetDpListByLga($geo['lgaid']);
        $this->assertNotEmpty($dpsByLga);

        $clusters = $controller->GetClusterList($geo['lgaid']);
        $this->assertNotEmpty($clusters);
    }

    public function testCommunityAndAllGeoQueries(): void
    {
        $this->requireSchema([
            'ms_geo_comm' => ['comid', 'community', 'dpid', 'wardid'],
            'sys_default_settings' => ['id', 'stateid', 'state', 'title'],
            'ms_geo_state' => ['StateId', 'Fullname'],
            'ms_geo_lga' => ['LgaId', 'Fullname', 'StateId'],
            'ms_geo_ward' => ['wardid', 'ward', 'lgaid'],
            'ms_geo_dp' => ['dpid', 'dp', 'wardid'],
            'ms_geo_cluster' => ['clusterid', 'cluster', 'lgaid'],
        ]);

        $controller = new General();
        $geo = $this->seedGeoHierarchy('COMM');
        $this->seedDefaultSettings($geo['stateid']);

        $communities = $controller->GetCommunityList($geo['dpid']);
        $this->assertNotEmpty($communities);

        $communitiesByWard = $controller->GetCommunityListByWard($geo['wardid']);
        $this->assertNotEmpty($communitiesByWard);

        $communitiesByLga = $controller->GetCommunityListByLga($geo['lgaid']);
        $this->assertNotEmpty($communitiesByLga);

        $allLga = $controller->GetAllLga();
        $this->assertNotEmpty($allLga);

        $allWard = $controller->GetAllWard();
        $this->assertNotEmpty($allWard);

        $allDp = $controller->GetAllDp();
        $this->assertNotEmpty($allDp);

        $allCluster = $controller->GetAllCluster();
        $this->assertNotEmpty($allCluster);
    }

    public function testActivityLogAndBadgeKey(): void
    {
        $this->requireSchema([
            'usr_user_activity' => ['userid', 'platform', 'module', 'description', 'result', 'ip', 'created'],
            'sys_default_settings' => ['id', 'id_key'],
        ]);

        $activityId = General::LogActivity(1, 'web', 'test', 'description', 'success', '1.2', '3.4');
        $this->assertIsNumeric($activityId);
        if ($activityId) {
            $this->recordCleanup('usr_user_activity', 'id', $activityId);
        }

        $this->insertRow('sys_default_settings', [
            'id' => 1,
            'id_key' => 'KEY-123',
        ]);
        $this->recordCleanup('sys_default_settings', 'id', 1);

        $key = General::GetIdBadgeKey();
        $this->assertSame('KEY-123', $key);
    }
}
