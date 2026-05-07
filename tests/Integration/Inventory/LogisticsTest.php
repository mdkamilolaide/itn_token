<?php

namespace Tests\Integration\Inventory;

use Smc\Inventory;

class LogisticsTest extends InventoryTestCase
{
    public function testFacilityTransferCreatesTransferRecord(): void
    {
        $this->requireTransferSchema();

        $inventory = new Inventory();

        $centralId = $this->seedCentralInventory([
            'product_code' => 'PT',
            'product_name' => 'Product Transfer',
            'location_type' => 'facility',
            'location_id' => 100,
            'batch' => 'BT',
            'expiry' => '2030-01-01',
            'rate' => 1,
            'unit' => 'unit',
            'primary_qty' => 2,
            'secondary_qty' => 100,
        ]);

        $result = $inventory->FacilityTransfer($centralId, 100, 200, 1, 1);
        $this->assertNull($result);

        $rows = $this->getDb()->DataTable("SELECT inventory_id FROM smc_inventory_transfer WHERE inventory_id = {$centralId}");
        $this->assertNotEmpty($rows);

        $this->recordCleanup('smc_inventory_transfer', 'inventory_id', $centralId);
    }

    public function testFacilityTransferReturnsFalseWhenMissingInventory(): void
    {
        $this->requireTransferSchema();

        $inventory = new Inventory();
        $result = $inventory->FacilityTransfer(999999, 100, 200, 1, 1);
        $this->assertFalse($result);
    }

    private function seedCentralInventory(array $data): int
    {
        $defaults = [
            'product_code' => 'P0',
            'product_name' => 'Product',
            'location_type' => 'facility',
            'location_id' => 1,
            'batch' => 'B0',
            'expiry' => '2030-01-01',
            'rate' => 1,
            'unit' => 'unit',
            'primary_qty' => 1,
            'secondary_qty' => 50,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ];
        $payload = array_merge($defaults, $data);
        $id = $this->insertRow('smc_inventory_central', $payload);
        if ($id) {
            $this->recordCleanup('smc_inventory_central', 'inventory_id', $id);
        }
        return (int) $id;
    }

    private function requireTransferSchema(): void
    {
        $centralCols = ['inventory_id', 'product_code', 'product_name', 'location_type', 'location_id', 'batch', 'expiry', 'rate', 'unit', 'primary_qty', 'secondary_qty'];
        $transferCols = ['inventory_id', 'source_facility_id', 'destination_facility_id', 'product_code', 'product_name', 'batch', 'expiry', 'rate', 'unit', 'primary_qty', 'secondary_qty'];

        if (!$this->tableHasColumns('smc_inventory_central', $centralCols)
            || !$this->tableHasColumns('smc_inventory_transfer', $transferCols)
        ) {
            $this->markTestSkipped('Inventory transfer schema not available');
        }
    }
}
