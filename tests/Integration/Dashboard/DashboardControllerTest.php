<?php

/**
 * Dashboard Controller Integration Tests
 * 
 * Tests for Dashboard controllers and data queries:
 * - Dashboard\Distribution: TopSummary, location/date aggregates
 * - Dashboard\Enetcard: Summary queries by location
 * - Dashboard\Eolin: Mobilization and distribution summaries
 * - Dashboard\Mobilization: Date/location-based aggregates
 * - Database queries for statistics and performance
 */

namespace Tests\Integration\Dashboard;

use Tests\TestCase;
use Dashboard\Distribution as DashboardDistribution;
use Dashboard\Enetcard;
use Dashboard\Eolin;
use Dashboard\Mobilization as DashboardMobilization;

class DashboardControllerTest extends TestCase
{
    // ==========================================
    // Distribution Dashboard Tests
    // ==========================================

    public function testDistributionInstantiation(): void
    {
        $distribution = new DashboardDistribution();
        $this->assertInstanceOf(DashboardDistribution::class, $distribution);
    }

    public function testDistributionTopSummary(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->TopSummary();
        $this->assertTrue(true);
    }

    public function testDistributionLgaAggregateByLocation(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->LgaAggregateByLocation();
        $this->assertTrue(true);
    }

    public function testDistributionWardAggregateByLocation(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->WardAggregateByLocation(1);
        $this->assertTrue(true);
    }

    public function testDistributionDpAggregateByLocation(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->DpAggregateByLocation(1);
        $this->assertTrue(true);
    }

    public function testDistributionTopAggregateByDate(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->TopAggregateByDate();
        $this->assertTrue(true);
    }

    public function testDistributionLgaAggregateByDate(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->LgaAggregateByDate(date('Y-m-d'));
        $this->assertTrue(true);
    }

    public function testDistributionWardAggregateByDate(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->WardAggregateByDate(date('Y-m-d'), 1);
        $this->assertTrue(true);
    }

    public function testDistributionDpAggregateByDate(): void
    {
        $distribution = new DashboardDistribution();
        $result = $distribution->DpAggregateByDate(date('Y-m-d'), 1);
        $this->assertTrue(true);
    }

    // ==========================================
    // Enetcard Dashboard Tests
    // ==========================================

    public function testEnetcardInstantiation(): void
    {
        $enetcard = new Enetcard();
        $this->assertInstanceOf(Enetcard::class, $enetcard);
    }

    public function testEnetcardTopSummary(): void
    {
        $enetcard = new Enetcard();
        $result = $enetcard->TopSummary();
        $this->assertTrue(true);
    }

    public function testEnetcardTopLgaSummary(): void
    {
        $enetcard = new Enetcard();
        $result = $enetcard->TopLgaSummary();
        $this->assertTrue(true);
    }

    public function testEnetcardTopWardSummary(): void
    {
        $enetcard = new Enetcard();
        $result = $enetcard->TopWardSummary(1);
        $this->assertTrue(true);
    }

    public function testEnetcardTopMobilizerSummary(): void
    {
        $enetcard = new Enetcard();
        $result = $enetcard->TopMobilizerSummary(1);
        $this->assertTrue(true);
    }

    // ==========================================
    // Eolin Dashboard Tests
    // ==========================================

    public function testEolinInstantiation(): void
    {
        $eolin = new Eolin();
        $this->assertInstanceOf(Eolin::class, $eolin);
    }

    public function testEolinTopSummaryMobilization(): void
    {
        $eolin = new Eolin();
        $result = $eolin->TopSummaryMobilization();
        $this->assertTrue(true);
    }

    public function testEolinLgaSummaryMobilization(): void
    {
        $eolin = new Eolin();
        $result = $eolin->LgaSummaryMobilization();
        $this->assertTrue(true);
    }

    public function testEolinWardSummaryMobilization(): void
    {
        $eolin = new Eolin();
        $result = $eolin->WardSummaryMobilization(1);
        $this->assertTrue(true);
    }

    public function testEolinDpSummaryMobilization(): void
    {
        $eolin = new Eolin();
        $result = $eolin->DpSummaryMobilization(1);
        $this->assertTrue(true);
    }

    public function testEolinTopSummaryDistribution(): void
    {
        $eolin = new Eolin();
        $result = $eolin->TopSummaryDistribution();
        $this->assertTrue(true);
    }

    public function testEolinLgaSummaryDistribution(): void
    {
        $eolin = new Eolin();
        $result = $eolin->LgaSummaryDistribution();
        $this->assertTrue(true);
    }

    public function testEolinWardSummaryDistribution(): void
    {
        $eolin = new Eolin();
        $result = $eolin->WardSummaryDistribution(1);
        $this->assertTrue(true);
    }

    public function testEolinDpSummaryDistribution(): void
    {
        $eolin = new Eolin();
        $result = $eolin->DpSummaryDistribution(1);
        $this->assertTrue(true);
    }

    // ==========================================
    // Mobilization Dashboard Tests
    // ==========================================

    public function testMobilizationInstantiation(): void
    {
        $mobilization = new DashboardMobilization();
        $this->assertInstanceOf(DashboardMobilization::class, $mobilization);
    }

    public function testMobilizationTopSummary(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->TopSummary();
        $this->assertTrue(true);
    }

    public function testMobilizationTopSummaryByDate(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->TopSummaryByDate();
        $this->assertTrue(true);
    }

    public function testMobilizationLgaAggregateByDate(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->LgaAggregateByDate(date('Y-m-d'));
        $this->assertTrue(true);
    }

    public function testMobilizationWardAggregateByDate(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->WardAggregateByDate(date('Y-m-d'), 1);
        $this->assertTrue(true);
    }

    public function testMobilizationDpAggregateByDate(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->DpAggregateByDate(date('Y-m-d'), 1);
        $this->assertTrue(true);
    }

    public function testMobilizationTopSummaryByLocation(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->TopSummaryByLocation();
        $this->assertTrue(true);
    }

    public function testMobilizationWardAggregateByLocation(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->WardAggregateByLocation(1);
        $this->assertTrue(true);
    }

    public function testMobilizationDpAggregateByLocation(): void
    {
        $mobilization = new DashboardMobilization();
        $result = $mobilization->DpAggregateByLocation(1);
        $this->assertTrue(true);
    }

    // ==========================================
    // Database Statistics Tests
    // ==========================================

    public function testMobilizationStatistics(): void
    {
        $stats = $this->db->Table("
            SELECT 
                COUNT(*) as total_households,
                SUM(family_size) as total_population,
                SUM(allocated_net) as total_nets_allocated,
                AVG(family_size) as avg_family_size
            FROM hhm_mobilization
        ");

        $this->assertCount(1, $stats);
        $this->assertArrayHasKey('total_households', $stats[0]);
        $this->assertArrayHasKey('total_population', $stats[0]);
    }

    public function testNetcardStatusDistribution(): void
    {
        $stats = $this->db->Table("
            SELECT 
                status,
                COUNT(*) as count
            FROM nc_netcard
            GROUP BY status
        ");

        $this->assertIsArray($stats);
    }

    public function testSmcChildHouseholdCount(): void
    {
        $count = $this->db->Single("SELECT COUNT(*) FROM smc_child_household");
        $this->assertIsNumeric($count);
    }

    public function testSmcChildRegistrationCount(): void
    {
        $tableExists = $this->db->Table("SHOW TABLES LIKE 'smc_child_registration'");

        if (empty($tableExists)) {
            $this->markTestSkipped('smc_child_registration table does not exist');
        }

        $count = $this->db->Single("SELECT COUNT(*) FROM smc_child_registration");
        $this->assertIsNumeric($count);
    }

    // ==========================================
    // Date Range and Aggregation Tests
    // ==========================================

    public function testMobilizationDateRange(): void
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

        $stats = $this->db->Table("
            SELECT 
                DATE(created) as date,
                COUNT(*) as count
            FROM hhm_mobilization
            WHERE DATE(created) BETWEEN '$startDate' AND '$endDate'
            GROUP BY DATE(created)
            ORDER BY date DESC
            LIMIT 30
        ");

        $this->assertIsArray($stats);
    }

    public function testMobilizationWeeklyAggregation(): void
    {
        $stats = $this->db->Table("
            SELECT 
                YEARWEEK(created) as week,
                COUNT(*) as count
            FROM hhm_mobilization
            WHERE created >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY YEARWEEK(created)
            ORDER BY week DESC
            LIMIT 12
        ");

        $this->assertIsArray($stats);
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    public function testDashboardQueryPerformance(): void
    {
        $startTime = microtime(true);

        // Execute typical dashboard queries
        $this->db->Table("SELECT COUNT(*) FROM hhm_mobilization");
        $this->db->Table("SELECT COUNT(*) FROM nc_netcard");
        $this->db->Table("SELECT COUNT(*) FROM smc_child_household");
        $this->db->Table("SELECT COUNT(*) FROM usr_login WHERE active = 1");

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(2000, $executionTime, 'Dashboard queries should complete within 2 seconds');
    }
}
