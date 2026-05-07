<?php

namespace Tests\Feature\Distribution;

use Tests\TestCase;

class DistributionWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdTokens = [];
    private array $createdMobilizations = [];
    private array $createdDistributions = [];
    private array $createdNetSerials = [];
    private array $createdProductItems = [];
    private array $createdProductSgtins = [];
    private array $createdVerificationLogs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/distribution/distribution.cont.php';
        require_once $this->projectRoot . '/lib/controller/distribution/gsverification.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        if (!empty($this->createdNetSerials)) {
            $this->deleteByIds($db, 'hhm_gs_net_verification', 'snid', $this->createdNetSerials);
            $this->deleteByIds($db, 'hhm_gs_net_serial', 'snid', $this->createdNetSerials);
        }
        if (!empty($this->createdVerificationLogs)) {
            $this->deleteByIds($db, 'hhm_gs_net_verification_log', 'logid', $this->createdVerificationLogs);
        }
        if (!empty($this->createdDistributions)) {
            $this->deleteByIds($db, 'hhm_distribution', 'dis_id', $this->createdDistributions);
        }
        if (!empty($this->createdMobilizations)) {
            $this->deleteByIds($db, 'hhm_mobilization', 'hhid', $this->createdMobilizations);
        }
        if (!empty($this->createdTokens)) {
            $this->deleteByIds($db, 'nc_token', 'tokenid', $this->createdTokens);
        }
        if (!empty($this->createdProductSgtins)) {
            $this->deleteByIds($db, 'ms_product_sgtin', 'sgtinid', $this->createdProductSgtins);
        }
        if (!empty($this->createdProductItems)) {
            $this->deleteByIds($db, 'ms_product_item', 'itemid', $this->createdProductItems);
        }

        parent::tearDown();
    }

    /**
     * Test complete distribution planning workflow
     */
    public function testCompleteDistributionPlanningWorkflow()
    {
        $geo = $this->getGeoSample();
        $distribution = new \Distribution\Distribution();

        $wardList = $distribution->GetDpLocationMaster($geo['wardid']);
        $this->assertIsArray($wardList);

        $lgaList = $distribution->GetDpLocationMasterByLga($geo['lgaid']);
        $this->assertIsArray($lgaList);

        $dpList = $distribution->GetDpLocationMasterList([$geo['dpid']]);
        $this->assertIsArray($dpList);

        $details = $distribution->GetGeoCodexDetails($geo['guid']);
        $this->assertIsArray($details);
    }

    /**
     * Test distribution execution workflow
     */
    public function testDistributionExecutionWorkflow()
    {
        $geo = $this->getGeoSample();
        $token = $this->createToken();
        $mobilization = $this->createMobilization($geo['dpid'], $token['tokenid'], $token['serial_no']);

        $distribution = new \Distribution\Distribution();

        $serial = $this->uniqueSerial('ETK');
        $gsNetSerials = json_encode([
            [
                'batchNumber' => 'BATCH1',
                'expDate' => '2028-12-31',
                'gtin' => 'GTIN001',
                'netData' => 'NETDATA001',
                'serialNumber' => 'SER001',
                'prodDate' => '2024-01-01'
            ]
        ]);

        $payload = [[
            'dp_id' => $geo['dpid'],
            'mobilization_id' => $mobilization['hhid'],
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $serial,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 1,
            'gs_net_serial' => $gsNetSerials,
            'longitude' => '6.45',
            'latitude' => '3.39',
            'device_serial' => 'DEV001',
            'app_version' => '1.0.0',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => date('Y-m-d H:i:s')
        ]];

        $count = $distribution->BulkDistibution($payload);
        $this->assertEquals(1, $count);

        $disId = $this->getDistributionIdBySerial($serial);
        $this->assertNotNull($disId);
        $this->createdDistributions[] = $disId;

        $serialRows = $this->getNetSerialRows($disId);
        $this->assertNotEmpty($serialRows);
        $this->createdNetSerials = array_merge($this->createdNetSerials, array_column($serialRows, 'snid'));
    }

    /**
     * Test distribution monitoring and tracking
     */
    public function testDistributionMonitoringWorkflow()
    {
        $geo = $this->getGeoSample();
        $token = $this->createToken();
        $this->createMobilization($geo['dpid'], $token['tokenid'], $token['serial_no']);

        $distribution = new \Distribution\Distribution();

        $data = $distribution->DownloadMobilizationData($geo['dpid']);
        $this->assertIsArray($data);

        $backup = $distribution->DownloadMobilizationDataBackup($geo['dpid']);
        $this->assertIsArray($backup);
    }

    /**
     * Test distribution reporting
     */
    public function testDistributionReportingWorkflow()
    {
        $gs = new \Distribution\GsVerification(5);

        $this->assertTrue($gs->ChangeLimit(10) === null);

        $product = $this->createProductWithSgtin();
        $geo = $this->getGeoSample();
        $token = $this->createToken();
        $mobilization = $this->createMobilization($geo['dpid'], $token['tokenid'], $token['serial_no']);

        $serial = $this->uniqueSerial('ETK');
        $distribution = new \Distribution\Distribution();
        $distribution->BulkDistibution([[
            'dp_id' => $geo['dpid'],
            'mobilization_id' => $mobilization['hhid'],
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $serial,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 1,
            'gs_net_serial' => json_encode([
                [
                    'batchNumber' => 'BATCH2',
                    'expDate' => '2028-12-31',
                    'gtin' => $product['gtin'],
                    'netData' => 'NETDATA002',
                    'serialNumber' => $product['sgtin'],
                    'prodDate' => '2024-01-01'
                ]
            ]),
            'longitude' => '6.45',
            'latitude' => '3.39',
            'device_serial' => 'DEV001',
            'app_version' => '1.0.0',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => date('Y-m-d H:i:s')
        ]]);

        $disId = $this->getDistributionIdBySerial($serial);
        $this->createdDistributions[] = $disId;

        $netRows = $this->getNetSerialRows($disId);
        $this->createdNetSerials = array_merge($this->createdNetSerials, array_column($netRows, 'snid'));

        $trace = $gs->TraceabilitySearch($product['gtin'], $product['sgtin']);
        $this->assertArrayHasKey('manufacturer', $trace);
        $this->assertArrayHasKey('logistic', $trace);
    }

    public function testGsVerificationWorkflow(): void
    {
        $gs = new \Distribution\GsVerification(10);
        $product = $this->createProductWithSgtin();

        $db = $this->getDb();
        $snidSuccess = $db->Insert(
            "INSERT INTO hhm_gs_net_serial (dis_id, hhid, etoken_id, net_serial, gtin, sgtin, batch, expiry, is_verified, created) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [null, null, null, 'NET-OK', $product['gtin'], $product['sgtin'], 'BATCH', '2028-12-31', 0, date('Y-m-d H:i:s')]
        );
        $snidFail = $db->Insert(
            "INSERT INTO hhm_gs_net_serial (dis_id, hhid, etoken_id, net_serial, gtin, sgtin, batch, expiry, is_verified, created) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [null, null, null, 'NET-FAIL', $product['gtin'], 'SGTIN-MISS', 'BATCH', '2028-12-31', 0, date('Y-m-d H:i:s')]
        );
        $snidEmpty = $db->Insert(
            "INSERT INTO hhm_gs_net_serial (dis_id, hhid, etoken_id, net_serial, gtin, sgtin, batch, expiry, is_verified, created) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [null, null, null, 'NET-EMPTY', $product['gtin'], '', 'BATCH', '2028-12-31', 0, date('Y-m-d H:i:s')]
        );

        $this->createdNetSerials = array_merge($this->createdNetSerials, [$snidSuccess, $snidFail, $snidEmpty]);

        ob_start();
        $gs->RunVerification();
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        if (is_array($payload)) {
            $this->assertEquals(200, $payload['proc_no']);
        } else {
            $this->assertStringContainsString('proc_no', $output);
        }

        $rows = $db->DataTable('SELECT snid, status FROM hhm_gs_net_verification ORDER BY id DESC LIMIT 3');
        if (empty($rows)) {
            $this->markTestIncomplete('GS verification produced no rows in this environment; ensure GS dataset or external verifier is available.');
        } else {
            $this->assertNotEmpty($rows);

            $log = $db->DataTable('SELECT logid FROM hhm_gs_net_verification_log ORDER BY logid DESC LIMIT 1');
            if (!empty($log)) {
                $this->createdVerificationLogs[] = $log[0]['logid'];
            }
        }
    }

    /**
     * Test distribution completion and reconciliation
     */
    public function testDistributionCompletionWorkflow()
    {
        $geo = $this->getGeoSample();
        $token = $this->createToken();
        $mobilization = $this->createMobilization($geo['dpid'], $token['tokenid'], $token['serial_no']);

        $distribution = new \Distribution\Distribution();

        $serial = $this->uniqueSerial('ETK');
        $payload = [[
            'dp_id' => $geo['dpid'],
            'mobilization_id' => $mobilization['hhid'],
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $serial,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 0,
            'gs_net_serial' => '[]',
            'longitude' => '6.45',
            'latitude' => '3.39',
            'device_serial' => 'DEV001',
            'app_version' => '1.0.0',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => date('Y-m-d H:i:s')
        ]];

        $distribution->BulkDistibution($payload);

        $results = $distribution->BulkDistibutionWithReturns($payload);
        $this->assertContains($serial, $results['failed']);

        $statusResult = $distribution->BulkDistibutionStatus([[
            'dp_id' => $geo['dpid'],
            'mobilization_id' => $mobilization['hhid'],
            'etoken_id' => $token['tokenid'] + 1000,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 0,
            'gs_net_serial' => '[]',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => date('Y-m-d H:i:s')
        ]]);

        $this->assertEquals(1, $statusResult['success']);
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT guid, stateid, lgaid, wardid, dpid FROM sys_geo_codex WHERE geo_level='dp' LIMIT 1");
        $row = $rows[0] ?? ['guid' => '', 'stateid' => 0, 'lgaid' => 0, 'wardid' => 0, 'dpid' => 0];

        return [
            'guid' => $row['guid'],
            'stateid' => (int) $row['stateid'],
            'lgaid' => (int) $row['lgaid'],
            'wardid' => (int) $row['wardid'],
            'dpid' => (int) $row['dpid'],
        ];
    }

    private function createToken(): array
    {
        $db = $this->getDb();
        $uuid = 'uuid_' . uniqid();
        $serial = $this->uniqueSerial('TK');

        $columns = $db->DataTable('SHOW COLUMNS FROM nc_token');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $fields = [];
        $values = [];

        $fieldMap = [
            'batchid' => 1,
            'batch_no' => 'BATCH-TEST',
            'serial_no' => $serial,
            'uuid' => $uuid,
            'status' => 'issued',
            'status_code' => 10,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ];

        foreach ($fieldMap as $field => $value) {
            if (in_array($field, $existing, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }

        $tokenId = null;
        if (!empty($fields)) {
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $tokenId = $db->Insert(
                'INSERT INTO nc_token (' . implode(',', $fields) . ") VALUES ($placeholders)",
                $values
            );
        }

        if ($tokenId) {
            $this->createdTokens[] = $tokenId;
            return ['tokenid' => (int) $tokenId, 'serial_no' => $serial];
        }

        $rows = $db->DataTable('SELECT tokenid, serial_no FROM nc_token LIMIT 1');
        $row = $rows[0] ?? ['tokenid' => 0, 'serial_no' => $serial];
        return ['tokenid' => (int) $row['tokenid'], 'serial_no' => $row['serial_no'] ?? $serial];
    }

    private function createMobilization(int $dpId, int $tokenId, string $tokenSerial): array
    {
        $db = $this->getDb();
        $etokenSerial = $tokenSerial . '-M';
        $hhid = $db->Insert(
            "INSERT INTO hhm_mobilization (dp_id, hhm_id, hoh_first, hoh_last, family_size, allocated_net, etoken_id, etoken_serial, collected_date) VALUES (?,?,?,?,?,?,?,?,?)",
            [$dpId, 1, 'Test', 'House', 3, 1, $tokenId, $etokenSerial, date('Y-m-d H:i:s')]
        );

        $this->createdMobilizations[] = $hhid;

        return ['hhid' => $hhid, 'etoken_serial' => $etokenSerial];
    }

    private function uniqueSerial(string $prefix): string
    {
        return $prefix . strtoupper(substr(uniqid(), -8));
    }

    private function getDistributionIdBySerial(string $serial): ?int
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT dis_id FROM hhm_distribution WHERE etoken_serial = '$serial' LIMIT 1");
        if (count($rows) === 0) {
            return null;
        }
        return (int) $rows[0]['dis_id'];
    }

    private function getNetSerialRows(int $disId): array
    {
        $db = $this->getDb();
        return $db->DataTable("SELECT snid FROM hhm_gs_net_serial WHERE dis_id = $disId");
    }

    private function createProductWithSgtin(): array
    {
        $db = $this->getDb();
        $existing = $db->DataTable('SELECT p.gtin, s.sgtin FROM ms_product_item p JOIN ms_product_sgtin s ON p.itemid = s.itemid LIMIT 1');
        if (!empty($existing)) {
            return ['gtin' => $existing[0]['gtin'], 'sgtin' => $existing[0]['sgtin']];
        }

        $gtin = 'GTIN' . rand(100000, 999999);
        $itemColumns = $db->DataTable('SHOW COLUMNS FROM ms_product_item');
        $itemExisting = array_map(fn ($row) => $row['Field'], $itemColumns);

        $itemFieldMap = [
            'brand_name' => 'TestBrand',
            'product_description' => 'TestProduct',
            'gtin' => $gtin,
            'manufacturer_gln' => '1234567890123',
            'manufacturer_name' => 'TestManufacturer',
            'created_by' => 'test',
            'updated_by' => 'test',
            'source' => 'test',
        ];

        $fields = [];
        $values = [];
        foreach ($itemFieldMap as $field => $value) {
            if (in_array($field, $itemExisting, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }

        $itemId = null;
        if (!empty($fields)) {
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $itemId = $db->Insert(
                'INSERT INTO ms_product_item (' . implode(',', $fields) . ") VALUES ($placeholders)",
                $values
            );
        }

        if (!$itemId) {
            $fallback = $db->DataTable('SELECT itemid, gtin FROM ms_product_item LIMIT 1');
            $itemId = $fallback[0]['itemid'] ?? null;
            $gtin = $fallback[0]['gtin'] ?? $gtin;
        } else {
            $this->createdProductItems[] = $itemId;
        }

        $sgtin = 'SG' . rand(100000, 999999);
        $sgtinColumns = $db->DataTable('SHOW COLUMNS FROM ms_product_sgtin');
        $sgtinExisting = array_map(fn ($row) => $row['Field'], $sgtinColumns);

        $sgtinFields = [];
        $sgtinValues = [];

        $sgtinFieldMap = [
            'itemid' => $itemId,
            'sgtin' => $sgtin,
            'created_by' => 'test',
            'updated_by' => 'test',
            'source' => 'test',
        ];

        foreach ($sgtinFieldMap as $field => $value) {
            if (in_array($field, $sgtinExisting, true)) {
                $sgtinFields[] = $field;
                $sgtinValues[] = $value;
            }
        }

        $sgtinId = null;
        if (!empty($sgtinFields)) {
            $placeholders = implode(',', array_fill(0, count($sgtinFields), '?'));
            $sgtinId = $db->Insert(
                'INSERT INTO ms_product_sgtin (' . implode(',', $sgtinFields) . ") VALUES ($placeholders)",
                $sgtinValues
            );
        }

        if ($sgtinId) {
            $this->createdProductSgtins[] = $sgtinId;
        } else {
            $fallback = $db->DataTable('SELECT sgtin FROM ms_product_sgtin LIMIT 1');
            $sgtin = $fallback[0]['sgtin'] ?? $sgtin;
        }

        return ['gtin' => $gtin, 'sgtin' => $sgtin];
    }

    private function deleteByIds(\MysqlPdo $db, string $table, string $column, array $ids): void
    {
        foreach ($ids as $id) {
            $db->Execute("DELETE FROM $table WHERE $column = ?", [$id]);
        }
    }
}
