<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Dashboard;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Dashboard Controller
 * 
 * Tests the SMC dashboard controller methods in isolation
 */
class DashboardControllerTest extends SmcTestCase
{
    public function testIccListSummaries(): void
    {
        $this->requireSchema([
            'smc_icc_collection' => ['issue_id', 'periodid', 'dpid', 'cdd_lead_id', 'drug', 'qty', 'total_qty', 'status_code', 'issue_date', 'calculated_used', 'calculated_partial'],
            'smc_icc_reconcile' => ['issue_id', 'wasted_qty', 'loss_qty'],
            'smc_period' => ['periodid', 'title'],
            'sys_geo_codex' => ['dpid', 'geo_level'],
            'ms_geo_lga' => ['LgaId', 'Fullname'],
            'ms_geo_ward' => ['wardid', 'ward'],
            'ms_geo_dp' => ['dpid', 'dp'],
        ]);

        $controller = new Dashboard();
        $geo = $this->seedGeoHierarchy('ICC');
        $this->seedGeoCodexDp($geo);

        $periodId = $this->seedPeriod('Period 1');
        $issueId = random_int(1000, 2000);

        $this->seedIccCollection([
            'issue_id' => $issueId,
            'periodid' => $periodId,
            'dpid' => $geo['dpid'],
            'cdd_lead_id' => 10,
            'drug' => 'SPAQ 1',
            'qty' => 5,
            'total_qty' => 5,
            'status_code' => 10,
            'calculated_used' => 3,
            'calculated_partial' => 1,
            'issue_date' => date('Y-m-d'),
        ]);

        if ($this->tableHasColumns('smc_icc_reconcile', ['issue_id', 'wasted_qty', 'loss_qty'])) {
            $this->seedIccReconcile([
                'issue_id' => $issueId,
                'wasted_qty' => 0,
                'loss_qty' => 0,
            ]);
        }

        $lga = $controller->IccListLga((string) $periodId, '', '');
        $this->assertNotEmpty($lga);
        $this->assertSame('5', (string) $lga[0]['issued']);

        $ward = $controller->IccListWard($geo['lgaid'], (string) $periodId, '', '');
        $this->assertNotEmpty($ward);

        $dp = $controller->IccListDp($geo['wardid'], (string) $periodId, '', '');
        $this->assertNotEmpty($dp);
    }
}
