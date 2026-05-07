<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Period;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Period Controller
 * 
 * Tests the SMC period/cycle controller methods in isolation
 */
class PeriodControllerTest extends SmcTestCase
{
    public function testCreateUpdateActivateAndList(): void
    {
        $this->requireSchema([
            'smc_period' => ['periodid', 'title', 'start_date', 'end_date', 'active'],
        ]);

        $controller = new Period();

        $periodId = $controller->Create('Visit 1', '2099-01-01', '2099-01-05');
        $this->assertIsNumeric($periodId);
        $this->recordCleanup('smc_period', 'periodid', $periodId);

        $updated = $controller->Update('Visit 1 Updated', '2099-01-02', '2099-01-06', $periodId);
        $this->assertTrue((bool) $updated);

        $controller->Activate($periodId);
        $activeRow = $this->getDb()->DataTable("SELECT active FROM smc_period WHERE periodid = {$periodId}");
        $this->assertSame('1', (string) $activeRow[0]['active']);

        $list = $controller->GetList();
        $this->assertNotEmpty($list);

        $deleted = $controller->Delete($periodId);
        $this->assertTrue((bool) $deleted);
    }
}
