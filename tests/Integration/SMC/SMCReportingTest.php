<?php

namespace Tests\Integration\SMC;

use Smc\SmcMaster;

class SMCReportingTest extends SMCTestCase
{
    public function testSmcMasterListsReasonsAndPeriodsWhenAvailable(): void
    {
        $master = new SmcMaster();

        if ($this->tableHasColumns('sms_reasons', ['reason', 'category'])) {
            $id = $this->insertRow('sms_reasons', [
                'reason' => 'Reason',
                'category' => 'Category',
                'created' => date('Y-m-d H:i:s'),
            ]);
            if ($id) {
                $this->recordCleanup('sms_reasons', 'id', $id);
            }
            $this->assertNotEmpty($master->GetReasons());
        } else {
            $this->markTestSkipped('Missing sms_reasons reason/category columns');
        }

        if ($this->tableHasColumns('smc_period', ['periodid', 'title', 'active'])) {
            $periodId = $this->seedPeriod('Period 1');
            $this->assertNotEmpty($periodId);
            $this->assertNotEmpty($master->GetPeriodActive());
            $this->assertNotEmpty($master->GetAllPeriods());
        } else {
            $this->markTestSkipped('Missing smc_period title/active columns');
        }
    }

    public function testSmcMasterListsCommoditiesWhenAvailable(): void
    {
        $master = new SmcMaster();

        if ($this->tableHasColumns('smc_commodity', ['product_id', 'name', 'product_code', 'min_age', 'max_age', 'extension_age'])) {
            $id = $this->insertRow('smc_commodity', [
                'name' => 'Commodity',
                'description' => 'Desc',
                'product_code' => 'P1',
                'min_age' => 3,
                'max_age' => 59,
                'extension_age' => 0,
                'created' => date('Y-m-d H:i:s'),
            ]);
            if ($id) {
                $this->recordCleanup('smc_commodity', 'product_id', $id);
            }
            $this->assertNotEmpty($master->GetCommodity());
        } else {
            $this->markTestSkipped('Missing smc_commodity columns');
        }
    }
}
