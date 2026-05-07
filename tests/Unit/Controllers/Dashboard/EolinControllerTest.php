<?php

namespace Tests\Unit\Controllers\Dashboard;

use Dashboard\Eolin;

/**
 * Unit Test: Dashboard EOLIN Controller
 * 
 * Tests the EOLIN dashboard controller methods in isolation
 */
class EolinControllerTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetEolinTrends(): void
    {
        $this->requireEolinSchema();

        $controller = new Eolin();
        $geo = $this->seedGeoHierarchy('EolinDist');

        $this->seedDistribution([
            'dp_id' => $geo['dpid'],
            'eolin_bring_old_net' => 1,
            'eolin_total_old_net' => 3,
        ]);

        $summary = $controller->TopSummaryDistribution();
        $this->assertNotEmpty($summary);

        $lga = $controller->LgaSummaryDistribution();
        $this->assertNotEmpty($lga);

        $ward = $controller->WardSummaryDistribution($geo['lgaid']);
        $this->assertNotEmpty($ward);

        $dp = $controller->DpSummaryDistribution($geo['wardid']);
        $this->assertNotEmpty($dp);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
