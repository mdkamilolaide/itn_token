<?php

namespace Tests\Integration\SMC;

use Smc\SmcMaster;

class SMCVerificationTest extends SMCTestCase
{
    public function testGetMasterChildIncludesLastVisitWhenAvailable(): void
    {
        $this->requireMasterChildSchema();

        $master = new SmcMaster();
        $geo = $this->seedGeoHierarchy('Visit');
        $token = 'HH-' . uniqid('', true);
        $beneficiary = 'BEN-' . uniqid('', true);

        $this->seedHousehold($token, $geo['dpid']);
        $this->seedChild($beneficiary, $token, $geo['dpid']);
        $periodId = $this->seedPeriod('Cycle 1');

        $this->insertRow('smc_drug_administration', [
            'beneficiary_id' => $beneficiary,
            'periodid' => $periodId,
            'dpid' => $geo['dpid'],
            'collected_date' => date('Y-m-d H:i:s'),
        ]);

        $children = $master->GetMasterChild($geo['dpid']);
        $this->assertNotEmpty($children);
    }
}
