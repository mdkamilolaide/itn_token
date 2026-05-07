<?php

declare(strict_types=1);

namespace Tests\Integration\Netcard;

use PHPUnit\Framework\TestCase;

// Load required dependencies
require_once __DIR__ . '/../../../lib/mysql.min.php';
require_once __DIR__ . '/../../../lib/controller/netcard/netcardTrans.cont.php';

use Netcard\NetcardTrans;

/**
 * Netcard transaction integration tests executed against live data.
 *
 * Uses representative IDs from the seeded Benue dataset; adjust the
 * constants below if fixtures change. Assertions focus on structure and
 * non-negative counts rather than hard-coded totals to keep the suite
 * resilient to data churn.
 *
 * @group netcard-transactions
 * @group database-reads
 */
class NetcardTransactionsTest extends TestCase
{
    // Sample IDs from seeded data (update if fixtures change)
    private const STATE_ID = 7;   // Benue
    private const LGA_ID = 119;   // BENUE > ADO
    private const WARD_ID = 2000; // BENUE > ADO > Akpoge/Ogbilolo

    // Sample netcard IDs from database
    private const SAMPLE_NETCARD_IDS = [1, 2, 3, 4, 5];

    // Location values (status codes)
    private const LOCATION_STATE = 100;
    private const LOCATION_LGA = 80;
    private const LOCATION_WARD = 60;
    private const LOCATION_MOBILIZER = 40;

    protected function tearDown(): void
    {
        gc_collect_cycles();
        parent::tearDown();
    }

    // ==========================================
    // INSTANTIATION & BASIC TESTS
    // ==========================================

    public function testNetcardTransInstantiation(): void
    {
        $trans = new NetcardTrans();
        $this->assertInstanceOf(NetcardTrans::class, $trans);
    }

    public function testNetcardTransHasPublicProperties(): void
    {
        $trans = new NetcardTrans();

        $this->assertTrue(property_exists($trans, 'LastError'));
        $this->assertTrue(property_exists($trans, 'LastErrorCode'));
    }

    // ==========================================
    // COUNT & BALANCE TESTS (READ-ONLY)
    // ==========================================

    public function testGetCountByLocation(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetCountByLocation();

        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('total', $result[0]);
            $this->assertArrayHasKey('location', $result[0]);
        }
    }

    public function testCountLocationState(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->CountLocationState();

        $this->assertTrue(is_int($result) || is_string($result));
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountLocationLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->CountLocationLga();

        $this->assertTrue(is_int($result) || is_string($result));
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountLocationWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->CountLocationWard();

        $this->assertTrue(is_int($result) || is_string($result));
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    // ==========================================
    // BALANCE TESTS BY GEO LEVEL
    // ==========================================

    public function testThisCountStateBalance(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->ThisCountStateBalance();

        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('total', $result[0]);
        }
    }

    public function testThisCountLgaBalanceWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->ThisCountLgaBalance(self::LGA_ID);

        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('total', $result[0]);
            $this->assertGreaterThanOrEqual(0, $result[0]['total']);
        }
    }

    public function testThisCountLgaBalanceWithNonExistentLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->ThisCountLgaBalance(99999);

        $this->assertIsArray($result);
        // Should return empty or 0 for non-existent LGA
    }

    public function testThisCountWardBalanceWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->ThisCountWardBalance(self::WARD_ID);

        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('total', $result[0]);
            $this->assertGreaterThanOrEqual(0, $result[0]['total']);
        }
    }

    public function testThisCountWardBalanceWithNonExistentWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->ThisCountWardBalance(99999);

        $this->assertIsArray($result);
    }

    public function testThisCountHHMobilizerBalanceWithUserId(): void
    {
        $trans = new NetcardTrans();
        // Sample user ID that should exist in seeded data
        $result = $trans->ThisCountHHMobilizerBalance(1);

        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('total', $result[0]);
        }
    }

    // ==========================================
    // LIST & REPORTING METHODS
    // ==========================================

    public function testGetCountLgaList(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetCountLgaList();

        $this->assertIsArray($result);
        // May be empty if no netcards at LGA level yet
    }

    public function testGetCountWardListWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetCountWardList(self::LGA_ID);

        $this->assertIsArray($result);
    }

    public function testGetCountHhmListWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetCountHhmList(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetLgaLevelMobilizersBalances(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetLgaLevelMobilizersBalances();

        $this->assertIsArray($result);
    }

    public function testGetWardLevelMobilizersBalancesWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetWardLevelMobilizersBalances(self::LGA_ID);

        $this->assertIsArray($result);
    }

    public function testGetWardListAndBalancesWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetWardListAndBalances(self::LGA_ID);

        $this->assertIsArray($result);
    }

    // ==========================================
    // MOVEMENT HISTORY TESTS
    // ==========================================

    public function testGetMovementTopHistoryWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementTopHistory(self::LGA_ID, 5);

        $this->assertIsArray($result);
    }

    public function testGetMovementTopHistoryWithDifferentCounts(): void
    {
        $trans = new NetcardTrans();
        $counts = [1, 5, 10, 20];

        foreach ($counts as $count) {
            $result = $trans->GetMovementTopHistory(self::LGA_ID, $count);
            $this->assertIsArray($result);
        }
    }

    public function testGetMovementListHistoryWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementListHistory(self::LGA_ID, 30);

        $this->assertIsArray($result);
    }

    public function testGetMovementListHistoryWithDifferentLimits(): void
    {
        $trans = new NetcardTrans();
        $limits = [5, 10, 30, 50];

        foreach ($limits as $limit) {
            $result = $trans->GetMovementListHistory(self::LGA_ID, $limit);
            $this->assertIsArray($result);
        }
    }

    public function testGetMovementDashboardBalancesWithValidLga(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementDashboardBalances(self::LGA_ID);

        $this->assertIsArray($result);
    }

    // ==========================================
    // MOBILIZER TESTS
    // ==========================================

    public function testGetMobilizersListWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMobilizersList(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetCombinedMobilizerBalanceWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetCombinedMobilizerBalance(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetOfflineMobilizerBalanceWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetOfflineMobilizerBalance(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetOnlineMobilizerBalanceWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetOnlineMobilizerBalance(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetcAllMobilizerBalanceWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetcAllMobilizerBalance(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetMobilizerBalanceBySupervisorWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMobilizerBalanceBySupervisor(self::WARD_ID);

        $this->assertIsArray($result);
    }

    // ==========================================
    // ALLOCATION HISTORY TESTS
    // ==========================================

    public function testGetAllocationTransferHistoryListWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetAllocationTransferHistoryList(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetAllocationReverseHistoryListWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetAllocationReverseHistoryList(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testGetAllocationDirectReverseListWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetAllocationDirectReverseList(self::WARD_ID);

        $this->assertIsArray($result);
    }

    public function testCombinedBalanceForAppWithValidWard(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->CombinedBalanceForApp(self::WARD_ID);

        $this->assertIsArray($result);
    }

    // ==========================================
    // EDGE CASES & BOUNDARY CONDITIONS
    // ==========================================

    public function testBalanceMethodsWithZeroId(): void
    {
        $trans = new NetcardTrans();

        $result1 = $trans->ThisCountLgaBalance(0);
        $this->assertIsArray($result1);

        $result2 = $trans->ThisCountWardBalance(0);
        $this->assertIsArray($result2);

        $result3 = $trans->ThisCountHHMobilizerBalance(0);
        $this->assertIsArray($result3);
    }

    public function testBalanceMethodsWithNegativeId(): void
    {
        $trans = new NetcardTrans();

        $result1 = $trans->ThisCountLgaBalance(-1);
        $this->assertIsArray($result1);

        $result2 = $trans->ThisCountWardBalance(-1);
        $this->assertIsArray($result2);
    }

    public function testListMethodsWithInvalidIds(): void
    {
        $trans = new NetcardTrans();

        $result1 = $trans->GetCountWardList(99999);
        $this->assertIsArray($result1);

        $result2 = $trans->GetCountHhmList(99999);
        $this->assertIsArray($result2);

        $result3 = $trans->GetWardLevelMobilizersBalances(99999);
        $this->assertIsArray($result3);
    }

    public function testMovementHistoryWithZeroCount(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementTopHistory(self::LGA_ID, 0);

        $this->assertIsArray($result);
    }

    public function testMovementHistoryWithNegativeCount(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementTopHistory(self::LGA_ID, -1);

        $this->assertIsArray($result);
    }

    public function testMovementHistoryWithVeryLargeCount(): void
    {
        $trans = new NetcardTrans();
        $result = $trans->GetMovementTopHistory(self::LGA_ID, 10000);

        $this->assertIsArray($result);
    }

    // ==========================================
    // DATABASE STATE VERIFICATION
    // ==========================================

    public function testLocationCountsAreConsistent(): void
    {
        $trans = new NetcardTrans();

        $stateCount = (int) $trans->CountLocationState();
        $lgaCount   = (int) $trans->CountLocationLga();
        $wardCount  = (int) $trans->CountLocationWard();
        $totalCount = (int) $trans->CountAllNetcard();

        // All counts should be non-negative
        $this->assertGreaterThanOrEqual(0, $stateCount);
        $this->assertGreaterThanOrEqual(0, $lgaCount);
        $this->assertGreaterThanOrEqual(0, $wardCount);

        // state + lga + ward are a subset of all locations (mobilizer/pending/beneficiary also exist)
        $this->assertLessThanOrEqual($totalCount, $stateCount + $lgaCount + $wardCount);

        // The authoritative check: sum of GetCountByLocation() must equal CountAllNetcard()
        $counts = $trans->GetCountByLocation();
        $sumByLocation = 0;
        foreach ($counts as $r) {
            $sumByLocation += (int) $r['total'];
        }
        $this->assertEquals($totalCount, $sumByLocation);
    }

    // ==========================================
    // REAL GEO IDS FROM DATABASE
    // ==========================================

    public function testMultipleLgaIdsFromDatabase(): void
    {
        $trans = new NetcardTrans();
        $lgaIds = [119, 120, 121, 122, 123]; // Sample LGA IDs from seeded data

        foreach ($lgaIds as $lgaId) {
            $result = $trans->ThisCountLgaBalance($lgaId);
            $this->assertIsArray($result);
        }
    }

    public function testMultipleWardIdsFromDatabase(): void
    {
        $trans = new NetcardTrans();
        $wardIds = [2000, 2001, 2002, 2003, 2004]; // Sample ward IDs from seeded data

        foreach ($wardIds as $wardId) {
            $result = $trans->ThisCountWardBalance($wardId);
            $this->assertIsArray($result);
        }
    }

    // ==========================================
    // PERFORMANCE TESTS
    // ==========================================

    public function testMultipleSequentialReads(): void
    {
        $trans = new NetcardTrans();

        // Simulate dashboard loading multiple counts
        for ($i = 0; $i < 10; $i++) {
            $trans->CountAllNetcard();
            $trans->CountLocationState();
            $trans->CountLocationLga();
        }

        $this->assertTrue(true); // Should complete without timeout
    }

    public function testComplexQueryPerformance(): void
    {
        $trans = new NetcardTrans();

        // Test methods that do complex joins
        $result1 = $trans->GetMovementListHistory(self::LGA_ID, 50);
        $result2 = $trans->GetWardListAndBalances(self::LGA_ID);
        $result3 = $trans->GetLgaLevelMobilizersBalances();

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertIsArray($result3);
    }
}
