<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Inventory;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Inventory Controller
 * 
 * Tests the SMC inventory controller methods in isolation
 */
class InventoryControllerTest extends SmcTestCase
{
    public function testInboundOutboundShipments(): void
    {
        $this->requireSchema([
            'smc_inventory_inbound' => ['product_code', 'product_name', 'location_type', 'location_id', 'batch', 'expiry'],
            'smc_inventory_outbound' => ['product_code', 'product_name', 'location_type', 'location_id', 'batch', 'expiry'],
        ]);

        $controller = new Inventory();

        $payload = [[
            'product_code' => 'PRD-' . uniqid(),
            'product_name' => 'Test Product',
            'location_id' => 1,
            'batch' => 'B1',
            'expiry_date' => '2099-12-31',
            'rate' => 1.5,
            'unit' => 'pack',
            'primary_qty' => 10,
            'secondary_qty' => 20,
            'userid' => 1,
        ]];

        $inbound = $controller->CmsInboundShipment($payload);
        $this->assertSame(1, count($inbound));
        $this->recordCleanup('smc_inventory_inbound', 'product_code', $payload[0]['product_code']);

        $outbound = $controller->CmsOutboundShipment($payload);
        $this->assertSame(1, count($outbound));
        $this->recordCleanup('smc_inventory_outbound', 'product_code', $payload[0]['product_code']);
    }

    public function testFacilityShipmentAndInventoryQueries(): void
    {
        $this->requireSchema([
            'smc_inventory_central' => ['inventory_id', 'product_code', 'product_name', 'location_type', 'location_id', 'batch', 'expiry', 'secondary_qty'],
            'smc_cms_location' => ['location_id', 'cms_name'],
        ]);

        $controller = new Inventory();

        $cmsId = $this->seedCmsLocation([
            'cms_name' => 'Central Store',
            'level' => 'state',
            'address' => 'Address',
            'poc' => 'POC',
            'poc_phone' => '08000000000',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $productCode = 'PRD-' . uniqid();
        $this->seedInventoryCentral([
            'product_code' => $productCode,
            'product_name' => 'CMS Product',
            'location_type' => 'cms',
            'location_id' => $cmsId,
            'batch' => 'B1',
            'expiry' => '2099-12-31',
            'rate' => 1.5,
            'unit' => 'pack',
            'primary_qty' => 5,
            'secondary_qty' => 10,
        ]);

        $cmsInventory = $controller->GetCmsInventory();
        $this->assertNotEmpty($cmsInventory);

        $facilityInbound = $controller->FacilityInboundShipment(
            $productCode,
            'Facility Product',
            101,
            'B2',
            '2099-12-31',
            1.0,
            'unit',
            2,
            4,
            1
        );
        $this->assertIsNumeric($facilityInbound);
        $this->recordCleanup('smc_inventory_inbound', 'product_code', $productCode);
    }
}
