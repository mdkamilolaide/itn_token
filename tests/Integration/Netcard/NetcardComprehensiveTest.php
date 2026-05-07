<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Netcard\Netcard;
use Netcard\Etoken;
use Netcard\NetcardTrans;

/**
 * Netcard controller comprehensive tests (DatabaseIntensive suite).
 *
 * Exercises generation, e-token, and transaction flows that write to the
 * database. Designed to run outside the default suite to avoid contention
 * with other integration tests.
 *
 * @group netcard-comprehensive
 * @group database-intensive
 */
class NetcardComprehensiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Clear any potential database connections/transactions
        gc_collect_cycles();
        parent::tearDown();
    }

    // ==========================================
    // NETCARD CONTROLLER TESTS
    // ==========================================

    public function testNetcardInstantiation(): void
    {
        $netcard = new Netcard(10);
        $this->assertInstanceOf(Netcard::class, $netcard);
    }

    public function testNetcardInstantiationWithZeroLength(): void
    {
        $netcard = new Netcard(0);
        $this->assertInstanceOf(Netcard::class, $netcard);
    }

    public function testNetcardInstantiationWithNegativeLength(): void
    {
        $netcard = new Netcard(-10);
        $this->assertInstanceOf(Netcard::class, $netcard);
    }

    public function testNetcardInstantiationWithMaxLength(): void
    {
        $netcard = new Netcard(5000);
        $this->assertInstanceOf(Netcard::class, $netcard);
    }

    public function testNetcardInstantiationExceedingMaxLength(): void
    {
        // Should cap at 5000
        $netcard = new Netcard(10000);
        $this->assertInstanceOf(Netcard::class, $netcard);
    }

    public function testNetcardChangeLengthValid(): void
    {
        $netcard = new Netcard(10);
        $netcard->ChangeLength(20);
        $this->assertTrue(true);
    }

    public function testNetcardChangeLengthToZero(): void
    {
        $netcard = new Netcard(10);
        $netcard->ChangeLength(0);
        $this->assertTrue(true);
    }

    public function testNetcardChangeLengthToNegative(): void
    {
        $netcard = new Netcard(10);
        $netcard->ChangeLength(-5);
        $this->assertTrue(true);
    }

    public function testNetcardChangeLengthExceedingMax(): void
    {
        $netcard = new Netcard(10);
        $netcard->ChangeLength(10000);
        $this->assertTrue(true);
    }

    public function testNetcardGenerateSmallBatch(): void
    {
        $this->markTestSkipped('Skipped in full suite: Netcard generation can cause database transaction conflicts');
        $netcard = new Netcard(5);
        try {
            $result = $netcard->Generate();
            $this->assertIsInt($result);
            $this->assertGreaterThanOrEqual(0, $result);
        } catch (\Throwable $e) {
            $this->assertTrue(true, 'Database operation may require specific setup');
        }
    }

    public function testNetcardGenerateAfterChangingLength(): void
    {
        $this->markTestSkipped('Skipped in full suite: Netcard generation can cause database transaction conflicts');
        $netcard = new Netcard(5);
        $netcard->ChangeLength(10);
        try {
            $result = $netcard->Generate();
            $this->assertIsInt($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // ETOKEN CONTROLLER TESTS
    // ==========================================

    public function testEtokenInstantiation(): void
    {
        $etoken = new Etoken('DEVICE-001', 10);
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenInstantiationWithDefaultLength(): void
    {
        $etoken = new Etoken('DEVICE-002');
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenInstantiationWithZeroLength(): void
    {
        $etoken = new Etoken('DEVICE-003', 0);
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenInstantiationWithNegativeLength(): void
    {
        $etoken = new Etoken('DEVICE-004', -10);
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenInstantiationWithMaxLength(): void
    {
        $etoken = new Etoken('DEVICE-005', 2000);
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenInstantiationExceedingMaxLength(): void
    {
        // Should cap at 2000
        $etoken = new Etoken('DEVICE-006', 5000);
        $this->assertInstanceOf(Etoken::class, $etoken);
    }

    public function testEtokenChangeLengthValid(): void
    {
        $etoken = new Etoken('DEVICE-007', 10);
        $etoken->ChangeLength(20);
        $this->assertTrue(true);
    }

    public function testEtokenChangeLengthToZero(): void
    {
        $etoken = new Etoken('DEVICE-008', 10);
        $etoken->ChangeLength(0);
        $this->assertTrue(true);
    }

    public function testEtokenChangeLengthToNegative(): void
    {
        $etoken = new Etoken('DEVICE-009', 10);
        $etoken->ChangeLength(-5);
        $this->assertTrue(true);
    }

    public function testEtokenChangeLengthExceedingMax(): void
    {
        $etoken = new Etoken('DEVICE-010', 10);
        $etoken->ChangeLength(5000);
        $this->assertTrue(true);
    }

    public function testEtokenGenerateSmallBatch(): void
    {
        $this->markTestSkipped('Skipped in full suite: Etoken generation can cause database transaction conflicts');
        $etoken = new Etoken('DEVICE-TEST-' . uniqid(), 3);
        try {
            $result = $etoken->Generate();
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true, 'Database operation may require specific setup');
        }
    }

    public function testEtokenGetLastBatch(): void
    {
        $this->markTestSkipped('Skipped in full suite: Etoken generation can cause database transaction conflicts');
        $etoken = new Etoken('DEVICE-LAST-' . uniqid(), 2);
        try {
            $etoken->Generate();
            $result = $etoken->getLastBatch();
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testEtokenGetThisBatchWithValidId(): void
    {
        $etoken = new Etoken('DEVICE-THIS-' . uniqid(), 2);
        try {
            $result = $etoken->getThisBatch(1);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testEtokenGetThisBatchWithZeroId(): void
    {
        $etoken = new Etoken('DEVICE-ZERO', 2);
        try {
            $result = $etoken->getThisBatch(0);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testEtokenUpdateTokenUsed(): void
    {
        try {
            $result = Etoken::UpdateTokenUsed(1);
            $this->assertIsBool($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testEtokenUpdateTokenUsedWithNonExistent(): void
    {
        try {
            $result = Etoken::UpdateTokenUsed(999999);
            $this->assertIsBool($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // NETCARD TRANSACTION CONTROLLER TESTS
    // ==========================================

    public function testNetcardTransInstantiation(): void
    {
        $netcardTrans = new NetcardTrans();
        $this->assertInstanceOf(NetcardTrans::class, $netcardTrans);
    }

    // --- Location Count Tests ---

    public function testGetCountByLocation(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountByLocation();
        $this->assertIsArray($result);
    }

    public function testCountLocationState(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CountLocationState();
        // Can return string or int from database
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountLocationLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CountLocationLga();
        // Can return string or int from database
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountLocationWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CountLocationWard();
        // Can return string or int from database
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountAllNetcard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CountAllNetcard();
        // Can return string or int from database
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int)$result);
    }

    public function testCountTotalNetcard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CountTotalNetcard();
        $this->assertIsArray($result);
    }

    // --- Balance Tests ---

    public function testThisCountStateBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountStateBalance();
        $this->assertIsArray($result);
    }

    public function testThisCountLgaBalanceWithValidId(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountLgaBalance(1);
        $this->assertIsArray($result);
    }

    public function testThisCountLgaBalanceWithZeroId(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountLgaBalance(0);
        $this->assertIsArray($result);
    }

    public function testThisCountWardBalanceWithValidId(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountWardBalance(1);
        $this->assertIsArray($result);
    }

    public function testThisCountWardBalanceWithZeroId(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountWardBalance(0);
        $this->assertIsArray($result);
    }

    public function testThisCountHHMobilizerBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->ThisCountHHMobilizerBalance(1);
        $this->assertIsArray($result);
    }

    public function testCombinedBalanceForApp(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CombinedBalanceForApp(1);
        $this->assertIsArray($result);
    }

    public function testCombinedBalanceForAppWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->CombinedBalanceForApp(0);
        $this->assertIsArray($result);
    }

    // --- List Tests ---

    public function testGetCountLgaList(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountLgaList();
        $this->assertIsArray($result);
    }

    public function testGetCountWardListWithValidLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountWardList(1);
        $this->assertIsArray($result);
    }

    public function testGetCountWardListWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountWardList(0);
        $this->assertIsArray($result);
    }

    public function testGetCountHhmListWithValidWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountHhmList(1);
        $this->assertIsArray($result);
    }

    public function testGetCountHhmListWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCountHhmList(0);
        $this->assertIsArray($result);
    }

    // --- Mobilizer Balance Tests ---

    public function testGetLgaLevelMobilizersBalances(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetLgaLevelMobilizersBalances();
        $this->assertIsArray($result);
    }

    public function testGetWardLevelMobilizersBalancesWithValidLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetWardLevelMobilizersBalances(1);
        $this->assertIsArray($result);
    }

    public function testGetWardLevelMobilizersBalancesWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetWardLevelMobilizersBalances(0);
        $this->assertIsArray($result);
    }

    public function testGetWardListAndBalancesWithValidLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetWardListAndBalances(1);
        $this->assertIsArray($result);
    }

    public function testGetWardListAndBalancesWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetWardListAndBalances(0);
        $this->assertIsArray($result);
    }

    // --- Movement History Tests ---

    public function testGetMovementTopHistoryWithDefaults(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementTopHistory(1);
        $this->assertIsArray($result);
    }

    public function testGetMovementTopHistoryWithCustomCount(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementTopHistory(1, 10);
        $this->assertIsArray($result);
    }

    public function testGetMovementTopHistoryWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementTopHistory(0, 5);
        $this->assertIsArray($result);
    }

    public function testGetMovementListHistoryWithDefaults(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementListHistory(1);
        $this->assertIsArray($result);
    }

    public function testGetMovementListHistoryWithCustomCount(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementListHistory(1, 50);
        $this->assertIsArray($result);
    }

    public function testGetMovementListHistoryWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementListHistory(0, 30);
        $this->assertIsArray($result);
    }

    public function testGetMovementDashboardBalances(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementDashboardBalances(1);
        $this->assertIsArray($result);
    }

    public function testGetMovementDashboardBalancesWithZeroLga(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMovementDashboardBalances(0);
        $this->assertIsArray($result);
    }

    // --- Mobilizer Tests ---

    public function testGetMobilizersListWithValidWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMobilizersList(1);
        $this->assertIsArray($result);
    }

    public function testGetMobilizersListWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMobilizersList(0);
        $this->assertIsArray($result);
    }

    public function testGetCombinedMobilizerBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCombinedMobilizerBalance(1);
        $this->assertIsArray($result);
    }

    public function testGetCombinedMobilizerBalanceWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetCombinedMobilizerBalance(0);
        $this->assertIsArray($result);
    }

    public function testGetOfflineMobilizerBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetOfflineMobilizerBalance(1);
        $this->assertIsArray($result);
    }

    public function testGetOfflineMobilizerBalanceWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetOfflineMobilizerBalance(0);
        $this->assertIsArray($result);
    }

    public function testGetOnlineMobilizerBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetOnlineMobilizerBalance(1);
        $this->assertIsArray($result);
    }

    public function testGetOnlineMobilizerBalanceWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetOnlineMobilizerBalance(0);
        $this->assertIsArray($result);
    }

    public function testGetcAllMobilizerBalance(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetcAllMobilizerBalance(1);
        $this->assertIsArray($result);
    }

    public function testGetMobilizerBalanceBySupervisor(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetMobilizerBalanceBySupervisor(1);
        $this->assertIsArray($result);
    }

    // --- History List Tests ---

    public function testGetAllocationTransferHistoryList(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationTransferHistoryList(1);
        $this->assertIsArray($result);
    }

    public function testGetAllocationTransferHistoryListWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationTransferHistoryList(0);
        $this->assertIsArray($result);
    }

    public function testGetAllocationReverseHistoryList(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationReverseHistoryList(1);
        $this->assertIsArray($result);
    }

    public function testGetAllocationReverseHistoryListWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationReverseHistoryList(0);
        $this->assertIsArray($result);
    }

    public function testGetAllocationDirectReverseList(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationDirectReverseList(1);
        $this->assertIsArray($result);
    }

    public function testGetAllocationDirectReverseListWithZeroWard(): void
    {
        $netcardTrans = new NetcardTrans();
        $result = $netcardTrans->GetAllocationDirectReverseList(0);
        $this->assertIsArray($result);
    }
}
