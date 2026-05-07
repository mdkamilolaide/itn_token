<?php

namespace Tests\Integration\Smc;

use Tests\TestCase;
use Smc\Dashboard;

/**
 * Database-driven integration tests for SMC Dashboard controller
 * Tests use real database records for realistic validation
 * 
 * Database State Used:
 * - 21,042 children in smc_child
 * - 6,811 drug administrations
 * - 23,716 ICC collections
 * - 52 periods
 * - Geo: LGAs 119-134, Wards 2000+, DPs 3000+
 */
class SmcDashboardTransactionsTest extends TestCase
{
    private Dashboard $dashboard;
    
    // Real database geo IDs for testing
    private const LGA_ADO = 119;
    private const LGA_GBOKO = 123;
    private const LGA_BURUKU = 122;
    
    private const WARD_ID = 2000;   // In LGA 119 (ADO)
    private const WARD_ID_2 = 2010; // Another ward
    
    private const DP_ID = 3298;  // Has 391 children
    private const DP_ID_2 = 3335; // Has 283 children

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboard = new Dashboard();
    }

    // ==================== INSTANTIATION TESTS ====================
    
    public function testDashboardInstantiation(): void
    {
        $this->assertInstanceOf(Dashboard::class, $this->dashboard);
    }

    // ==================== CHILD LIST SUMMARY TESTS ====================
    
    public function testChildListLgaSummaryWithNoFilters(): void
    {
        $result = $this->dashboard->ChildListLgaSummary();
        
        $this->assertIsArray($result);
        // May be empty if no children registered, but should return array
    }
    
    public function testChildListLgaSummaryWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ChildListLgaSummary($startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testChildListLgaSummaryWithEmptyDates(): void
    {
        $result = $this->dashboard->ChildListLgaSummary('', '');
        
        $this->assertIsArray($result);
    }
    
    public function testChildListWardSummaryWithValidLga(): void
    {
        $result = $this->dashboard->ChildListWardSummary(self::LGA_ADO);
        
        $this->assertIsArray($result);
    }
    
    public function testChildListWardSummaryWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ChildListWardSummary(self::LGA_GBOKO, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testChildListWardSummaryWithNonExistentLga(): void
    {
        $result = $this->dashboard->ChildListWardSummary(999999);
        
        $this->assertIsArray($result);
        // Should return empty for non-existent LGA
    }
    
    public function testChildListDpSummaryWithValidWard(): void
    {
        $result = $this->dashboard->ChildListDpSummary(self::WARD_ID);
        
        $this->assertIsArray($result);
    }
    
    public function testChildListDpSummaryWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ChildListDpSummary(self::WARD_ID, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testChildListDpSummaryWithNonExistentWard(): void
    {
        $result = $this->dashboard->ChildListDpSummary(999999);
        
        $this->assertIsArray($result);
    }

    // ==================== DRUG ADMINISTRATION TESTS ====================
    
    public function testDrugAdminListLgaWithNoFilters(): void
    {
        $result = $this->dashboard->DrugAdminListLga();
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListLgaWithPeriodList(): void
    {
        $periodList = '1,2,3';
        
        $result = $this->dashboard->DrugAdminListLga($periodList);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListLgaWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->DrugAdminListLga('', $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListLgaWithAllFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->DrugAdminListLga($periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListWardWithValidLga(): void
    {
        $result = $this->dashboard->DrugAdminListWard(self::LGA_ADO);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListWardWithPeriodAndDates(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->DrugAdminListWard(self::LGA_GBOKO, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListWardWithNonExistentLga(): void
    {
        $result = $this->dashboard->DrugAdminListWard(999999);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListDpWithValidWard(): void
    {
        $result = $this->dashboard->DrugAdminListDp(self::WARD_ID);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListDpWithPeriodAndDates(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->DrugAdminListDp(self::WARD_ID, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testDrugAdminListDpWithNonExistentWard(): void
    {
        $result = $this->dashboard->DrugAdminListDp(999999);
        
        $this->assertIsArray($result);
    }

    // ==================== REFERRAL LIST TESTS ====================
    
    public function testReferralListLgaWithNoFilters(): void
    {
        $result = $this->dashboard->ReferralListLga();
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListLgaWithPeriodList(): void
    {
        $periodList = '1,2,3';
        
        $result = $this->dashboard->ReferralListLga($periodList);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListLgaWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ReferralListLga('', $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListWardWithValidLga(): void
    {
        $result = $this->dashboard->ReferralListWard(self::LGA_ADO);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListWardWithFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ReferralListWard(self::LGA_GBOKO, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListWardWithNonExistentLga(): void
    {
        $result = $this->dashboard->ReferralListWard(999999);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListDpWithValidWard(): void
    {
        $result = $this->dashboard->ReferralListDp(self::WARD_ID);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListDpWithFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->ReferralListDp(self::WARD_ID, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testReferralListDpWithNonExistentWard(): void
    {
        $result = $this->dashboard->ReferralListDp(999999);
        
        $this->assertIsArray($result);
    }

    // ==================== ICC LIST TESTS ====================
    
    public function testIccListLgaWithNoFilters(): void
    {
        $result = $this->dashboard->IccListLga();
        
        $this->assertIsArray($result);
    }
    
    public function testIccListLgaWithPeriodList(): void
    {
        $periodList = '1,2,3';
        
        $result = $this->dashboard->IccListLga($periodList);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListLgaWithDateRange(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->IccListLga('', $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListLgaWithAllFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->IccListLga($periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListWardWithValidLga(): void
    {
        $result = $this->dashboard->IccListWard(self::LGA_ADO);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListWardWithFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->IccListWard(self::LGA_GBOKO, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListWardWithNonExistentLga(): void
    {
        $result = $this->dashboard->IccListWard(999999);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListDpWithValidWard(): void
    {
        $result = $this->dashboard->IccListDp(self::WARD_ID);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListDpWithFilters(): void
    {
        $periodList = '1,2';
        $startDate = '2024-01-01';
        $endDate = '2025-12-31';
        
        $result = $this->dashboard->IccListDp(self::WARD_ID, $periodList, $startDate, $endDate);
        
        $this->assertIsArray($result);
    }
    
    public function testIccListDpWithNonExistentWard(): void
    {
        $result = $this->dashboard->IccListDp(999999);
        
        $this->assertIsArray($result);
    }

    // ==================== EDGE CASE TESTS ====================
    
    public function testMethodsWithZeroId(): void
    {
        $childWard = $this->dashboard->ChildListWardSummary(0);
        $childDp = $this->dashboard->ChildListDpSummary(0);
        $drugWard = $this->dashboard->DrugAdminListWard(0);
        $drugDp = $this->dashboard->DrugAdminListDp(0);
        $refWard = $this->dashboard->ReferralListWard(0);
        $refDp = $this->dashboard->ReferralListDp(0);
        $iccWard = $this->dashboard->IccListWard(0);
        $iccDp = $this->dashboard->IccListDp(0);
        
        $this->assertIsArray($childWard);
        $this->assertIsArray($childDp);
        $this->assertIsArray($drugWard);
        $this->assertIsArray($drugDp);
        $this->assertIsArray($refWard);
        $this->assertIsArray($refDp);
        $this->assertIsArray($iccWard);
        $this->assertIsArray($iccDp);
    }
    
    public function testMethodsWithNegativeId(): void
    {
        $childWard = $this->dashboard->ChildListWardSummary(-1);
        $drugWard = $this->dashboard->DrugAdminListWard(-1);
        $refWard = $this->dashboard->ReferralListWard(-1);
        $iccWard = $this->dashboard->IccListWard(-1);
        
        $this->assertIsArray($childWard);
        $this->assertIsArray($drugWard);
        $this->assertIsArray($refWard);
        $this->assertIsArray($iccWard);
    }
    
    public function testMethodsWithInvalidDateFormats(): void
    {
        // Invalid date strings should be handled gracefully
        $result = $this->dashboard->ChildListLgaSummary('invalid-date', 'also-invalid');
        
        $this->assertIsArray($result);
    }
    
    public function testMethodsWithFutureDates(): void
    {
        $startDate = '2030-01-01';
        $endDate = '2030-12-31';
        
        $result = $this->dashboard->ChildListLgaSummary($startDate, $endDate);
        
        $this->assertIsArray($result);
        // Future dates should return empty results
    }
    
    public function testMethodsWithReversedDateRange(): void
    {
        // End date before start date
        $startDate = '2025-12-31';
        $endDate = '2024-01-01';
        
        $result = $this->dashboard->ChildListLgaSummary($startDate, $endDate);
        
        $this->assertIsArray($result);
    }

    // ==================== MULTIPLE LGA TESTS ====================
    
    public function testMultipleLgasForChildSummary(): void
    {
        $lgas = [self::LGA_ADO, self::LGA_GBOKO, self::LGA_BURUKU];
        
        foreach ($lgas as $lgaId) {
            $result = $this->dashboard->ChildListWardSummary($lgaId);
            $this->assertIsArray($result, "Failed for LGA $lgaId");
        }
    }
    
    public function testMultipleLgasForDrugAdmin(): void
    {
        $lgas = [self::LGA_ADO, self::LGA_GBOKO, self::LGA_BURUKU];
        
        foreach ($lgas as $lgaId) {
            $result = $this->dashboard->DrugAdminListWard($lgaId);
            $this->assertIsArray($result, "Failed for LGA $lgaId");
        }
    }
    
    public function testMultipleLgasForIcc(): void
    {
        $lgas = [self::LGA_ADO, self::LGA_GBOKO, self::LGA_BURUKU];
        
        foreach ($lgas as $lgaId) {
            $result = $this->dashboard->IccListWard($lgaId);
            $this->assertIsArray($result, "Failed for LGA $lgaId");
        }
    }

    // ==================== PERFORMANCE TESTS ====================
    
    public function testLgaSummaryPerformance(): void
    {
        $startTime = microtime(true);
        
        $this->dashboard->ChildListLgaSummary();
        $this->dashboard->DrugAdminListLga();
        $this->dashboard->ReferralListLga();
        $this->dashboard->IccListLga();
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // All LGA summaries should complete in under 10 seconds
        $this->assertLessThan(10.0, $duration, 'LGA summary queries took too long');
    }
    
    public function testWardSummaryPerformance(): void
    {
        $startTime = microtime(true);
        
        $this->dashboard->ChildListWardSummary(self::LGA_ADO);
        $this->dashboard->DrugAdminListWard(self::LGA_ADO);
        $this->dashboard->ReferralListWard(self::LGA_ADO);
        $this->dashboard->IccListWard(self::LGA_ADO);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // All ward summaries should complete in under 5 seconds
        $this->assertLessThan(5.0, $duration, 'Ward summary queries took too long');
    }
    
    public function testDpSummaryPerformance(): void
    {
        $startTime = microtime(true);
        
        $this->dashboard->ChildListDpSummary(self::WARD_ID);
        $this->dashboard->DrugAdminListDp(self::WARD_ID);
        $this->dashboard->ReferralListDp(self::WARD_ID);
        $this->dashboard->IccListDp(self::WARD_ID);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // All DP summaries should complete in under 5 seconds
        $this->assertLessThan(5.0, $duration, 'DP summary queries took too long');
    }
}
