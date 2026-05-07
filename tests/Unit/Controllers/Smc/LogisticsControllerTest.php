<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Logistics;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Logistics Controller
 * 
 * Tests the SMC logistics controller methods in isolation
 */
class LogisticsControllerTest extends SmcTestCase
{
    public function testIssueCreationAndUpdates(): void
    {
        $this->requireSchema([
            'smc_logistics_issues' => ['issue_id', 'periodid', 'dpid', 'product_code', 'product_name', 'primary_qty', 'secondary_qty'],
        ]);

        $controller = new Logistics();

        $bulk = [[
            'periodid' => 1,
            'dpid' => 100,
            'product_code' => 'PRD-' . uniqid(),
            'product_name' => 'Product 1',
            'primary_qty' => 1,
            'secondary_qty' => 2,
        ]];

        $created = $controller->CreateBulkIssue($bulk);
        $this->assertSame(1, $created);

        $issueRow = $this->getDb()->DataTable('SELECT issue_id FROM smc_logistics_issues ORDER BY issue_id DESC LIMIT 1');
        $this->assertNotEmpty($issueRow);
        $issueId = (int) $issueRow[0]['issue_id'];
        $this->recordCleanup('smc_logistics_issues', 'issue_id', $issueId);

        $updated = $controller->UpdateSingleIssue($issueId, 3, 4);
        $this->assertTrue($updated);

        $processed = $controller->ProcessBulkIssue([[
            'periodid' => 1,
            'issue_id' => $issueId,
            'dpid' => 100,
            'product_code' => $bulk[0]['product_code'],
            'product_name' => $bulk[0]['product_name'],
            'primary_qty' => 5,
            'secondary_qty' => 6,
        ]]);
        $this->assertSame(1, $processed);
    }

    public function testIssueQueriesAndInventoryAvailability(): void
    {
        $this->requireSchema([
            'smc_logistics_issues' => ['issue_id', 'periodid', 'dpid', 'product_code', 'product_name', 'primary_qty', 'secondary_qty'],
            'sys_geo_codex' => ['dpid', 'lgaid', 'geo_value'],
            'smc_period' => ['periodid', 'title'],
            'smc_inventory_central' => ['inventory_id', 'product_code', 'product_name', 'location_type', 'location_id', 'secondary_qty'],
            'smc_cms_location' => ['location_id', 'cms_name'],
        ]);

        $controller = new Logistics();
        $geo = $this->seedGeoHierarchy('LOG');
        $this->seedGeoCodexDp($geo);

        $periodId = $this->seedPeriod('Period A');

        $this->seedLogisticsIssue([
            'periodid' => $periodId,
            'dpid' => $geo['dpid'],
            'product_code' => 'PRD-' . uniqid(),
            'product_name' => 'Product X',
            'primary_qty' => 1,
            'secondary_qty' => 2,
        ]);

        $issues = $controller->GetIssueByPeriod($periodId, $geo['lgaid']);
        $this->assertNotEmpty($issues);

        $cmsId = $this->seedCmsLocation([
            'cms_name' => 'CMS',
            'level' => 'state',
            'address' => 'Address',
            'poc' => 'POC',
            'poc_phone' => '08000000000',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->seedInventoryCentral([
            'product_code' => 'PRD-' . uniqid(),
            'product_name' => 'Stock',
            'location_type' => 'CMS',
            'location_id' => $cmsId,
            'batch' => 'B1',
            'expiry' => '2099-12-31',
            'rate' => 1.0,
            'unit' => 'pack',
            'primary_qty' => 1,
            'secondary_qty' => 10,
        ]);

        $available = $controller->getInvAvailableBalance();
        $this->assertNotEmpty($available);
    }
}
