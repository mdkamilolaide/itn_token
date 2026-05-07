<?php

namespace Tests\Unit\Controllers\Dataset;

use Dataset\Pbi;

/**
 * Unit Test: Power BI Dataset Controller
 * 
 * Tests the Power BI dataset controller methods in isolation
 */
class PbiControllerTest extends DatasetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGenerateDataset(): void
    {
        $this->requireGeoSchema();

        $controller = new Pbi();
        $geo = $this->seedGeoHierarchy('PBI');

        $data = $controller->GeoLocationSet();
        $this->assertNotEmpty($data);
        $this->assertSame((string) $geo['stateid'], (string) $data[0]['stateid']);
    }

    public function testValidateDatasetFormat(): void
    {
        $this->requireGsSchema();

        $controller = new Pbi();
        $data = $controller->gs_scanned_list();
        $this->assertIsArray($data);

        $data = $controller->gs_verification_list();
        $this->assertIsArray($data);
    }

    public function testExportDataset(): void
    {
        $this->requireSummarySchema();

        $controller = new Pbi();
        $geo = $this->seedGeoHierarchy('Summary');

        $this->seedMobilization([
            'dp_id' => $geo['dpid'],
            'family_size' => 3,
            'allocated_net' => 2,
            'collected_date' => '2099-02-02 00:00:00',
        ]);
        $this->seedDistribution([
            'dp_id' => $geo['dpid'],
            'collected_nets' => 2,
            'collected_date' => '2099-02-02 00:00:00',
        ]);
        $sgtinId = $this->insertRow('ms_product_sgtin', [
            'sgtin' => 'SGTIN-1',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($sgtinId) {
            $this->recordCleanup('ms_product_sgtin', 'sgtinid', $sgtinId);
        }

        $summary = $controller->gs_summary_data();
        $this->assertNotEmpty($summary);
        $this->assertArrayHasKey('total_net_master', $summary[0]);
        $this->assertArrayHasKey('household_mobilized', $summary[0]);
        $this->assertArrayHasKey('collected_nets', $summary[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
