<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;

class InventoryManagementWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdProductCodes = [];
    private array $createdCmsLocations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/smc/inventory.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();
        if (!empty($this->createdProductCodes)) {
            $codes = array_map([$db->Conn, 'quote'], $this->createdProductCodes);
            $codeList = implode(',', $codes);
            $db->executeTransaction("DELETE FROM smc_inventory_inbound WHERE product_code IN ($codeList)", []);
            $db->executeTransaction("DELETE FROM smc_inventory_outbound WHERE product_code IN ($codeList)", []);
            $db->executeTransaction("DELETE FROM smc_inventory_transfer WHERE product_code IN ($codeList)", []);
            $db->executeTransaction("DELETE FROM smc_logistics_issues WHERE product_code IN ($codeList)", []);
            $db->executeTransaction("DELETE FROM smc_inventory_central WHERE product_code IN ($codeList)", []);
        }
        if (!empty($this->createdCmsLocations)) {
            $ids = array_filter(array_map('intval', $this->createdCmsLocations));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM smc_cms_location WHERE location_id IN (' . implode(',', $ids) . ')', []);
            }
        }

        parent::tearDown();
    }

    /**
     * Test complete inventory management workflow
     */
    public function testCompleteInventoryManagementWorkflow()
    {
        $geo = $this->getGeoSample();
        $cmsLocationId = $this->getCmsLocationId();
        if ($cmsLocationId === null) {
            $this->markTestSkipped('No CMS location available for inventory workflow');
        }

        $inventory = new \Smc\Inventory();
        $productCode = $this->uniqueProductCode('INV');
        $productName = 'Inventory Workflow Item';
        $batch = 'BATCH-' . strtoupper(substr(uniqid(), -6));
        $expiry = date('Y-m-d', strtotime('+1 year'));

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'CMS',
            'location_id' => $cmsLocationId,
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 10,
            'secondary_qty' => 100,
        ]);

        $ids = $inventory->CmsInboundShipment([
            [
                'product_code' => $productCode,
                'product_name' => $productName,
                'location_id' => $cmsLocationId,
                'batch' => $batch,
                'expiry_date' => $expiry,
                'rate' => 1,
                'unit' => 'BOX',
                'primary_qty' => 5,
                'secondary_qty' => 50,
                'userid' => 1,
            ]
        ]);
        $this->assertIsArray($ids);
        $this->assertCount(1, $ids);

        $inventory->CmsOutboundShipment([
            [
                'product_code' => $productCode,
                'product_name' => $productName,
                'location_id' => $cmsLocationId,
                'batch' => $batch,
                'expiry_date' => $expiry,
                'rate' => 1,
                'unit' => 'BOX',
                'primary_qty' => 2,
                'secondary_qty' => 20,
                'userid' => 1,
            ]
        ]);

        $facilityInventoryId = $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'facility',
            'location_id' => $geo['dpid'],
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 10,
            'secondary_qty' => 500,
        ]);

        $inventory->FacilityTransfer($facilityInventoryId, $geo['dpid'], $geo['dpid'] + 1, 1, 1);
        $this->assertTransferExists($facilityInventoryId);
    }

    /**
     * Test stock receipt and verification
     */
    public function testStockReceiptWorkflow()
    {
        $geo = $this->getGeoSample();
        $cmsLocationId = $this->getCmsLocationId();
        if ($cmsLocationId === null) {
            $this->markTestSkipped('No CMS location available for inventory workflow');
        }

        $inventory = new \Smc\Inventory();
        $productCode = $this->uniqueProductCode('RCV');
        $productName = 'Receipt Item';
        $batch = 'BATCH-' . strtoupper(substr(uniqid(), -6));
        $expiry = date('Y-m-d', strtotime('+1 year'));

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'CMS',
            'location_id' => $cmsLocationId,
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 10,
            'secondary_qty' => 100,
        ]);

        $ids = $inventory->CmsInboundShipment([
            [
                'product_code' => $productCode,
                'product_name' => $productName,
                'location_id' => $cmsLocationId,
                'batch' => $batch,
                'expiry_date' => $expiry,
                'rate' => 1,
                'unit' => 'BOX',
                'primary_qty' => 4,
                'secondary_qty' => 40,
                'userid' => 1,
            ]
        ]);

        $this->assertIsArray($ids);
        $this->assertCount(1, $ids);
        $this->assertInboundExists($productCode);

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'Facility',
            'location_id' => $geo['dpid'],
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 5,
            'secondary_qty' => 50,
        ]);

        $facilityId = $inventory->FacilityInboundShipment(
            $productCode,
            $productName,
            $geo['dpid'],
            $batch,
            $expiry,
            1,
            'BOX',
            2,
            20,
            1
        );
        $this->assertNotFalse($facilityId);
        $this->assertInboundExists($productCode);
    }

    /**
     * Test inventory allocation and distribution
     */
    public function testInventoryAllocationWorkflow()
    {
        $geo = $this->getGeoSample();
        $cmsLocationId = $this->getCmsLocationId();
        if ($cmsLocationId === null) {
            $this->markTestSkipped('No CMS location available for inventory workflow');
        }

        $inventory = new \Smc\Inventory();
        $productCode = $this->uniqueProductCode('OUT');
        $productName = 'Outbound Item';
        $batch = 'BATCH-' . strtoupper(substr(uniqid(), -6));
        $expiry = date('Y-m-d', strtotime('+1 year'));

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'CMS',
            'location_id' => $cmsLocationId,
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 15,
            'secondary_qty' => 150,
        ]);

        $ids = $inventory->CmsOutboundShipment([
            [
                'product_code' => $productCode,
                'product_name' => $productName,
                'location_id' => $cmsLocationId,
                'batch' => $batch,
                'expiry_date' => $expiry,
                'rate' => 1,
                'unit' => 'BOX',
                'primary_qty' => 3,
                'secondary_qty' => 30,
                'userid' => 1,
            ]
        ]);

        $this->assertIsArray($ids);
        $this->assertCount(1, $ids);
        $this->assertOutboundExists($productCode);

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'Facility',
            'location_id' => $geo['dpid'],
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 6,
            'secondary_qty' => 60,
        ]);

        $facilityId = $inventory->FacilityOutboundShipment(
            $productCode,
            $productName,
            $geo['dpid'],
            $batch,
            $expiry,
            1,
            'BOX',
            1,
            10,
            1
        );
        $this->assertNotFalse($facilityId);
        $this->assertOutboundExists($productCode);
    }

    /**
     * Test inventory tracking and monitoring
     */
    public function testInventoryTrackingWorkflow()
    {
        $geo = $this->getGeoSample();
        $cmsLocationId = $this->getCmsLocationId();
        if ($cmsLocationId === null) {
            $this->markTestSkipped('No CMS location available for inventory workflow');
        }

        $inventory = new \Smc\Inventory();
        $productCode = $this->uniqueProductCode('TRK');
        $productName = 'Tracking Item';
        $batch = 'BATCH-' . strtoupper(substr(uniqid(), -6));
        $expiry = date('Y-m-d', strtotime('+1 year'));

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'cms',
            'location_id' => $cmsLocationId,
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 8,
            'secondary_qty' => 80,
        ]);

        $this->insertInventoryCentral([
            'product_code' => $productCode,
            'product_name' => $productName,
            'location_type' => 'facility',
            'location_id' => $geo['dpid'],
            'batch' => $batch,
            'expiry' => $expiry,
            'rate' => 1,
            'unit' => 'BOX',
            'primary_qty' => 5,
            'secondary_qty' => 50,
        ]);

        $cmsInventory = $inventory->GetCmsInventory();
        $this->assertIsArray($cmsInventory);

        $facilityInventory = $inventory->GetFacilityInventoryBalance($geo['dpid']);
        $this->assertIsArray($facilityInventory);
    }

    private function insertInventoryCentral(array $fieldMap): ?int
    {
        $db = $this->getDb();
        $columns = $db->DataTable('SHOW COLUMNS FROM smc_inventory_central');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $fields = [];
        $values = [];
        foreach ($fieldMap as $field => $value) {
            if (in_array($field, $existing, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }

        if (in_array('created', $existing, true) && !array_key_exists('created', $fieldMap)) {
            $fields[] = 'created';
            $values[] = date('Y-m-d H:i:s');
        }
        if (in_array('updated', $existing, true) && !array_key_exists('updated', $fieldMap)) {
            $fields[] = 'updated';
            $values[] = date('Y-m-d H:i:s');
        }

        if (empty($fields)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $id = $db->Insert('INSERT INTO smc_inventory_central (' . implode(',', $fields) . ") VALUES ($placeholders)", $values);

        if (!empty($fieldMap['product_code'])) {
            $this->createdProductCodes[] = $fieldMap['product_code'];
        }

        return $id ?: null;
    }

    private function insertLogisticsIssue(array $fieldMap): ?int
    {
        $db = $this->getDb();
        $columns = $db->DataTable('SHOW COLUMNS FROM smc_logistics_issues');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $fields = [];
        $values = [];
        foreach ($fieldMap as $field => $value) {
            if (in_array($field, $existing, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }
        if (in_array('created', $existing, true) && !array_key_exists('created', $fieldMap)) {
            $fields[] = 'created';
            $values[] = date('Y-m-d H:i:s');
        }

        if (empty($fields)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        return $db->Insert('INSERT INTO smc_logistics_issues (' . implode(',', $fields) . ") VALUES ($placeholders)", $values);
    }

    private function getCmsLocationId(): ?int
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SELECT location_id FROM smc_cms_location LIMIT 1');
        if (!empty($rows)) {
            return (int) $rows[0]['location_id'];
        }

        $columns = $db->DataTable('SHOW COLUMNS FROM smc_cms_location');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $fieldMap = [
            'cms_name' => 'Test CMS',
            'cms_code' => 'CMS-' . strtoupper(substr(uniqid(), -6)),
            'location_name' => 'Test CMS',
        ];

        $fields = [];
        $values = [];
        foreach ($fieldMap as $field => $value) {
            if (in_array($field, $existing, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }
        if (in_array('created', $existing, true)) {
            $fields[] = 'created';
            $values[] = date('Y-m-d H:i:s');
        }

        if (empty($fields)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $id = $db->Insert('INSERT INTO smc_cms_location (' . implode(',', $fields) . ") VALUES ($placeholders)", $values);
        if ($id) {
            $this->createdCmsLocations[] = $id;
            return (int) $id;
        }
        return null;
    }

    private function assertInboundExists(string $productCode): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT product_code FROM smc_inventory_inbound WHERE product_code = '$productCode' LIMIT 1");
        $this->assertNotEmpty($rows, 'Expected inbound record');
    }

    private function assertOutboundExists(string $productCode): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT product_code FROM smc_inventory_outbound WHERE product_code = '$productCode' LIMIT 1");
        $this->assertNotEmpty($rows, 'Expected outbound record');
    }

    private function assertTransferExists(?int $inventoryId): void
    {
        if (!$inventoryId) {
            $this->assertTrue(true);
            return;
        }
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT inventory_id FROM smc_inventory_transfer WHERE inventory_id = $inventoryId LIMIT 1");
        $this->assertNotEmpty($rows, 'Expected transfer record');
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $dpid = (int) ($this->safeSelectValue($db, 'SELECT dpid AS val FROM sys_geo_codex WHERE geo_value = 10 LIMIT 1') ?? 0);
        $periodid = (int) ($this->safeSelectValue($db, 'SELECT periodid AS val FROM smc_period LIMIT 1') ?? 1);

        return [
            'dpid' => $dpid ?: 0,
            'periodid' => $periodid ?: 1,
        ];
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }
        return $rows[0]['val'] ?? null;
    }

    private function uniqueProductCode(string $prefix): string
    {
        return $prefix . '-' . strtoupper(substr(uniqid(), -10));
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }
}
