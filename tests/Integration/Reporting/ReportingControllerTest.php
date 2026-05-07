<?php

namespace Tests\Integration;

use Tests\TestCase;
use Reporting\Reporting;

/**
 * Reporting controller integration tests.
 *
 * Covers:
 * - Database-driven report queries (mobilization, netcards, activity)
 * - Export-style queries for bulk data pulls
 * - Controller list/report endpoints to ensure they do not throw
 */
class ReportingControllerTest extends TestCase
{
    private Reporting $reporting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporting = new Reporting();
    }

    // ==========================================
    // Instantiation
    // ==========================================

    public function testReportingInstantiation(): void
    {
        $this->assertInstanceOf(Reporting::class, $this->reporting);
    }

    // ==========================================
    // Report Queries
    // ==========================================

    public function testMobilizationReportQuery(): void
    {
        $report = $this->db->Table(
            "\n            SELECT \n                dp_id,\n                COUNT(*) as households,\n                SUM(family_size) as population,\n                SUM(allocated_net) as nets\n            FROM hhm_mobilization\n            GROUP BY dp_id\n            LIMIT 20\n        "
        );

        $this->assertIsArray($report);
    }

    public function testNetcardReportQuery(): void
    {
        $report = $this->db->Table(
            "\n            SELECT \n                status,\n                location,\n                COUNT(*) as count\n            FROM nc_netcard\n            GROUP BY status, location\n        "
        );

        $this->assertIsArray($report);
    }

    public function testUserActivityReportQuery(): void
    {
        $tableExists = $this->db->Table("SHOW TABLES LIKE 'sys_user_activity'");

        if (empty($tableExists)) {
            $this->markTestSkipped('sys_user_activity table does not exist');
        }

        $report = $this->db->Table(
            "\n            SELECT \n                uid,\n                module,\n                COUNT(*) as actions\n            FROM sys_user_activity\n            GROUP BY uid, module\n            LIMIT 20\n        "
        );

        $this->assertIsArray($report);
    }

    public function testSmcRegistrationReportQuery(): void
    {
        $tableExists = $this->db->Table("SHOW TABLES LIKE 'smc_child_registration'");

        if (empty($tableExists)) {
            $this->markTestSkipped('smc_child_registration table does not exist');
        }

        $report = $this->db->Single("SELECT COUNT(*) FROM smc_child_registration");

        $this->assertIsNumeric($report);
    }

    public function testDateBasedReportQuery(): void
    {
        $report = $this->db->Table(
            "\n            SELECT \n                DATE(created) as date,\n                COUNT(*) as count\n            FROM hhm_mobilization\n            WHERE created >= DATE_SUB(NOW(), INTERVAL 30 DAY)\n            GROUP BY DATE(created)\n            ORDER BY date DESC\n        "
        );

        $this->assertIsArray($report);
    }

    public function testGeographicReportQuery(): void
    {
        $report = $this->db->Table(
            "\n            SELECT \n                g.title as location,\n                g.geo_level,\n                COUNT(m.id) as households\n            FROM sys_geo_codex g\n            LEFT JOIN hhm_mobilization m ON g.dpid = m.dp_id\n            WHERE g.geo_level = 'dp'\n            GROUP BY g.dpid, g.title, g.geo_level\n            LIMIT 20\n        "
        );

        $this->assertIsArray($report);
    }

    // ==========================================
    // Export Queries
    // ==========================================

    public function testMobilizationExportQuery(): void
    {
        $data = $this->db->Table(
            "\n            SELECT \n                id,\n                dp_id,\n                hoh_first,\n                hoh_last,\n                hoh_phone,\n                family_size,\n                allocated_net,\n                etoken_serial,\n                created\n            FROM hhm_mobilization\n            LIMIT 100\n        "
        );

        $this->assertIsArray($data);

        if (!empty($data)) {
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('hoh_first', $data[0]);
            $this->assertArrayHasKey('family_size', $data[0]);
        }
    }

    public function testNetcardExportQuery(): void
    {
        $data = $this->db->Table(
            "\n            SELECT \n                id,\n                uuid,\n                active,\n                location,\n                status,\n                geo_level\n            FROM nc_netcard\n            LIMIT 100\n        "
        );

        $this->assertIsArray($data);
    }

    // ==========================================
    // Controller List/Report Methods (should not throw)
    // ==========================================

    public function testListParticipants(): void
    {
        try {
            $this->reporting->ListParticipants(0, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListParticipants threw: ' . $e->getMessage());
        }
    }

    public function testListBankVerification(): void
    {
        try {
            $this->reporting->ListBankVerification(0, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListBankVerification threw: ' . $e->getMessage());
        }
    }

    public function testListUncapturedUsers(): void
    {
        try {
            $this->reporting->ListUncapturedUsers(0, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListUncapturedUsers threw: ' . $e->getMessage());
        }
    }

    public function testListMobilizationByLga(): void
    {
        try {
            $this->reporting->ListMobilizationByLga('state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListMobilizationByLga threw: ' . $e->getMessage());
        }
    }

    public function testListMobilizationByDp(): void
    {
        try {
            $this->reporting->ListMobilizationByDp('state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListMobilizationByDp threw: ' . $e->getMessage());
        }
    }

    public function testListDateMobilizationByLga(): void
    {
        try {
            $this->reporting->ListDateMobilizationByLga(date('Y-m-d'), 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateMobilizationByLga threw: ' . $e->getMessage());
        }
    }

    public function testListDateRangeMobilizationByLga(): void
    {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        try {
            $this->reporting->ListDateRangeMobilizationByLga($startDate, $endDate, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateRangeMobilizationByLga threw: ' . $e->getMessage());
        }
    }

    public function testListDateMobilizationByDp(): void
    {
        try {
            $this->reporting->ListDateMobilizationByDp(date('Y-m-d'), 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateMobilizationByDp threw: ' . $e->getMessage());
        }
    }

    public function testListDistributionByLga(): void
    {
        try {
            $this->reporting->ListDistributionByLga('state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDistributionByLga threw: ' . $e->getMessage());
        }
    }

    public function testListDistributionByDp(): void
    {
        try {
            $this->reporting->ListDistributionByDp('state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDistributionByDp threw: ' . $e->getMessage());
        }
    }

    public function testListDateDistributionByLga(): void
    {
        try {
            $this->reporting->ListDateDistributionByLga(date('Y-m-d'), 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateDistributionByLga threw: ' . $e->getMessage());
        }
    }

    public function testListDateRangeDistributionByLga(): void
    {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        try {
            $this->reporting->ListDateRangeDistributionByLga($startDate, $endDate, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateRangeDistributionByLga threw: ' . $e->getMessage());
        }
    }

    public function testListDateDistributionByDp(): void
    {
        try {
            $this->reporting->ListDateDistributionByDp(date('Y-m-d'), 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateDistributionByDp threw: ' . $e->getMessage());
        }
    }

    public function testListDateRangeDistributionByDp(): void
    {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        try {
            $this->reporting->ListDateRangeDistributionByDp($startDate, $endDate, 'state', 1);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('ListDateRangeDistributionByDp threw: ' . $e->getMessage());
        }
    }
}
