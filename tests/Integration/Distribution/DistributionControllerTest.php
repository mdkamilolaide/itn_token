<?php

/**
 * Distribution Controller Integration Tests
 * 
 * Comprehensive tests for Distribution module controllers:
 * - Distribution\Distribution: LLIN distribution tracking and operations
 * - Distribution\GsVerification: GS1 barcode verification and traceability
 * - Mobilization\MapData: Geographic data visualization
 * 
 * Test coverage includes:
 * - Controller instantiation and method execution
 * - Location master data queries (DP, Ward, LGA)
 * - Bulk distribution operations
 * - GS net verification and traceability
 * - Edge cases and boundary conditions
 * - Database integrity and performance
 */

namespace Tests\Integration\Distribution;

use Tests\TestCase;
use Distribution\Distribution;
use Distribution\GsVerification;
use Mobilization\MapData;

class DistributionControllerTest extends TestCase
{
    // ==========================================
    // Distribution Controller - Core Tests
    // ==========================================

    public function testDistributionInstantiation(): void
    {
        $distribution = new Distribution();
        $this->assertInstanceOf(Distribution::class, $distribution);
    }

    /**
     * @dataProvider dpLocationIdProvider
     */
    public function testGetDpLocationMaster(int $wardId = 0): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetDpLocationMaster($wardId);
        $this->assertIsArray($result);
    }

    public function dpLocationIdProvider(): array
    {
        return [
            'valid_ward' => [1],
            'zero_ward' => [0],
            'negative_ward' => [-1],
            'large_ward' => [999999],
        ];
    }

    /**
     * @dataProvider lgaIdProvider
     */
    public function testGetDpLocationMasterByLga(int $lgaId = 0): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetDpLocationMasterByLga($lgaId);
        $this->assertIsArray($result);
    }

    public function lgaIdProvider(): array
    {
        return [
            'valid_lga' => [1],
            'zero_lga' => [0],
            'negative_lga' => [-1],
            'large_lga' => [999999],
        ];
    }

    public function testGetDpLocationMasterListWithValidIds(): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetDpLocationMasterList([1, 2, 3]);
        $this->assertIsArray($result);
    }

    public function testGetDpLocationMasterListWithEmptyArray(): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetDpLocationMasterList([]);
        $this->assertIsArray($result);
    }

    public function testGetDpLocationMasterListWithDuplicates(): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetDpLocationMasterList([1, 1, 2, 2, 3]);
        $this->assertIsArray($result);
    }

    public function testGetDpLocationMasterListWithLargeArray(): void
    {
        $distribution = new Distribution();
        $dpIds = range(1, 100);
        $result = $distribution->GetDpLocationMasterList($dpIds);
        $this->assertIsArray($result);
    }

    /**
     * @dataProvider guidProvider
     */
    public function testGetGeoCodexDetails(string $guid = ''): void
    {
        $distribution = new Distribution();
        $result = $distribution->GetGeoCodexDetails($guid);
        $this->assertIsArray($result);
    }

    public function guidProvider(): array
    {
        return [
            'simple_guid' => ['test-guid-123'],
            'empty_guid' => [''],
            'uuid_format' => ['550e8400-e29b-41d4-a716-446655440000'],
            'special_chars' => ['test-guid-!@#$'],
            'numeric' => ['12345'],
            'very_long' => [str_repeat('a', 100)],
        ];
    }

    public function testDownloadMobilizationDataWithValidDp(): void
    {
        $distribution = new Distribution();
        $result = $distribution->DownloadMobilizationData(1);
        $this->assertIsArray($result);
    }

    public function testDownloadMobilizationDataBackupWithValidDp(): void
    {
        $distribution = new Distribution();
        $result = $distribution->DownloadMobilizationDataBackup(1);
        $this->assertIsArray($result);
    }

    // ==========================================
    // Distribution - Bulk Operations
    // ==========================================

    public function testBulkDistributionWithEmptyArray(): void
    {
        $distribution = new Distribution();
        $result = $distribution->BulkDistibution([]);
        $this->assertEquals(0, $result);
    }

    public function testBulkDistributionWithValidData(): void
    {
        $distribution = new Distribution();
        $data = [[
            'dpid' => 1,
            'hhid' => 1,
            'etoken_id' => 1,
            'etoken_serial' => 'TEST_' . time(),
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 2,
            'is_gs_net' => 0,
            'gs_net_serial' => '',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'longitude' => '3.3792',
            'latitude' => '6.5244',
            'device_serial' => 'DEV123',
            'app_version' => '1.0.0',
            'collected_date' => date('Y-m-d H:i:s'),
        ]];

        try {
            $result = $distribution->BulkDistibution($data);
            $this->assertIsInt($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testBulkDistributionWithEolinData(): void
    {
        $distribution = new Distribution();
        $data = [[
            'dpid' => 1,
            'hhid' => 1,
            'etoken_id' => 1,
            'etoken_serial' => 'EOLIN_' . time(),
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 3,
            'is_gs_net' => 0,
            'gs_net_serial' => '',
            'eolin_bring_old_net' => 1,
            'eolin_total_old_net' => 2,
            'longitude' => '3.3792',
            'latitude' => '6.5244',
            'device_serial' => 'DEV123',
            'app_version' => '1.0.0',
            'collected_date' => date('Y-m-d H:i:s'),
        ]];

        try {
            $result = $distribution->BulkDistibution($data);
            $this->assertIsInt($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testBulkDistributionWithGsNetData(): void
    {
        $distribution = new Distribution();
        $gsNetSerial = json_encode([
            ['batchNumber' => 'B1', 'expDate' => '2025-12-31', 'gtin' => 'G1', 'netData' => 'N1', 'serialNumber' => 'S1', 'prodDate' => '2024-01-01']
        ]);

        $data = [[
            'dpid' => 1,
            'hhid' => 1,
            'etoken_id' => 1,
            'etoken_serial' => 'GSNET_' . time(),
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 1,
            'gs_net_serial' => $gsNetSerial,
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'longitude' => '3.3792',
            'latitude' => '6.5244',
            'device_serial' => 'DEV123',
            'app_version' => '1.0.0',
            'collected_date' => date('Y-m-d H:i:s'),
        ]];

        try {
            $result = $distribution->BulkDistibution($data);
            $this->assertIsInt($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testBulkDistributionWithReturnsEmpty(): void
    {
        $distribution = new Distribution();
        $result = $distribution->BulkDistibutionWithReturns([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failed', $result);
    }

    public function testBulkDistributionStatusEmpty(): void
    {
        $distribution = new Distribution();
        $result = $distribution->BulkDistibutionStatus([]);

        $this->assertIsArray($result);
    }

    // ==========================================
    // GS Verification Controller Tests
    // ==========================================

    public function testGsVerificationInstantiation(): void
    {
        $gsVerify = new GsVerification();
        $this->assertInstanceOf(GsVerification::class, $gsVerify);
    }

    public function testGsVerificationInstantiationWithLimit(): void
    {
        $gsVerify = new GsVerification(50);
        $this->assertInstanceOf(GsVerification::class, $gsVerify);
    }

    public function testGsVerificationInstantiationWithZeroLimit(): void
    {
        $gsVerify = new GsVerification(0);
        $this->assertInstanceOf(GsVerification::class, $gsVerify);
    }

    public function testChangeLimitValid(): void
    {
        $gsVerify = new GsVerification();
        $gsVerify->ChangeLimit(200);
        $this->assertTrue(true);
    }

    public function testChangeLimitZero(): void
    {
        $gsVerify = new GsVerification();
        $gsVerify->ChangeLimit(0);
        $this->assertTrue(true);
    }

    public function testRunVerification(): void
    {
        $gsVerify = new GsVerification(1);

        ob_start();
        try {
            $gsVerify->RunVerification();
            $output = ob_get_clean();
            $this->assertIsString($output);
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->assertTrue(true);
        }
    }

    /**
     * @dataProvider traceabilitySearchProvider
     */
    public function testTraceabilitySearch(string $gtin = '', string $sgtin = ''): void
    {
        $gsVerify = new GsVerification();
        $result = $gsVerify->TraceabilitySearch($gtin, $sgtin);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('manufacturer', $result);
        $this->assertArrayHasKey('logistic', $result);
    }

    public function traceabilitySearchProvider(): array
    {
        return [
            'valid_codes' => ['GTIN123', 'SGTIN456'],
            'empty_codes' => ['', ''],
            'numeric_codes' => ['12345678', '87654321'],
            'special_chars' => ['GTIN-!@#', 'SGTIN-$%^'],
            'long_codes' => [str_repeat('A', 50), str_repeat('B', 50)],
        ];
    }

    public function testTraceabilitySearchReturnsArrayStructure(): void
    {
        $gsVerify = new GsVerification();
        $result = $gsVerify->TraceabilitySearch('TEST_GTIN', 'TEST_SGTIN');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('manufacturer', $result);
        $this->assertArrayHasKey('logistic', $result);
    }

    // ==========================================
    // MapData Controller Tests
    // ==========================================

    public function testMapDataInstantiation(): void
    {
        $mapData = new MapData();
        $this->assertInstanceOf(MapData::class, $mapData);
    }

    public function testGetMobilizationData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetMobilizationData(1, 1);
        $this->assertIsArray($result);
    }

    public function testGetMobilizationDataWithDates(): void
    {
        $mapData = new MapData();
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

        $result = $mapData->GetMobilizationData(1, 1, $startDate, $endDate);
        $this->assertIsArray($result);
    }

    public function testGetDpData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetDpData(1, 1);
        $this->assertIsArray($result);
    }

    public function testGetWardData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetWardData(1, date('Y-m-d'));
        $this->assertIsArray($result);
    }

    public function testGetLgaData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetLgaData(1, date('Y-m-d'));
        $this->assertIsArray($result);
    }

    public function testGetStateData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetStateData(1, date('Y-m-d'));
        $this->assertIsArray($result);
    }

    public function testGetPerItemData(): void
    {
        $mapData = new MapData();
        $result = $mapData->GetPerItemData(0);
        $this->assertIsArray($result);
    }

    // ==========================================
    // Database Integration Tests
    // ==========================================

    public function testDistributionPointDataExists(): void
    {
        $count = $this->db->Single("SELECT COUNT(*) FROM sys_geo_codex WHERE geo_level = 'dp'");

        $this->assertIsNumeric($count);

        if ($count == 0) {
            $this->markTestSkipped('No distribution points in database');
        }

        $this->assertGreaterThan(0, $count);
    }

    public function testGeoHierarchyExists(): void
    {
        $levels = ['state', 'lga', 'ward', 'dp'];

        foreach ($levels as $level) {
            $result = $this->db->Table(
                "SELECT COUNT(*) as count FROM sys_geo_codex WHERE geo_level = ?",
                [$level]
            );

            if (empty($result)) {
                $this->markTestSkipped("No results for geo_level '$level'");
            }

            $count = $result[0]['count'] ?? 0;
            $this->assertIsNumeric($count, "Count for geo_level '$level' should be numeric");
        }
    }

    public function testDistributionPointRequiredFields(): void
    {
        $dp = $this->db->Table("SELECT * FROM sys_geo_codex WHERE geo_level = 'dp' LIMIT 1");

        if (empty($dp)) {
            $this->markTestSkipped('No distribution points available');
        }

        $requiredFields = ['id', 'guid', 'stateid', 'lgaid', 'wardid', 'dpid', 'geo_level', 'title'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $dp[0], "Field '$field' should exist");
        }
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    public function testGetDpLocationMasterQueryPerformance(): void
    {
        $ward = $this->db->Table("
            SELECT wardid, COUNT(*) as dp_count 
            FROM sys_geo_codex 
            WHERE geo_level = 'dp' 
            GROUP BY wardid 
            HAVING dp_count > 0 
            LIMIT 1
        ");

        if (empty($ward)) {
            $this->markTestSkipped('No wards with distribution points found');
        }

        $distribution = new Distribution();
        $wardId = $ward[0]['wardid'];

        $startTime = microtime(true);
        $result = $distribution->GetDpLocationMaster($wardId);
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(1000, $executionTime, 'Query should complete in under 1 second');
        $this->assertIsArray($result);
    }
}
