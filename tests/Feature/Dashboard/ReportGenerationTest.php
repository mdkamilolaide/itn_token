<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

/**
 * Report generation tests for SMC dashboard reports
 */
class ReportGenerationTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/smc/dashboard.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    public function testChildListSummaries(): void
    {
        $geo = $this->getGeoIds();
        $dateRange = $this->getDateRange('smc_child', 'created');

        $dashboard = new \Smc\Dashboard();

        $lgaSummary = $dashboard->ChildListLgaSummary();
        $this->assertRowsHaveColumns($lgaSummary, ['id', 'title', 'total', 'male', 'female']);

        $lgaSummaryFiltered = $dashboard->ChildListLgaSummary($dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($lgaSummaryFiltered, ['id', 'title', 'total', 'male', 'female']);

        $wardSummary = $dashboard->ChildListWardSummary($geo['lgaid']);
        $this->assertRowsHaveColumns($wardSummary, ['id', 'title', 'total', 'male', 'female']);

        $dpSummary = $dashboard->ChildListDpSummary($geo['wardid']);
        $this->assertRowsHaveColumns($dpSummary, ['id', 'title', 'total', 'male', 'female']);
    }

    public function testDrugAdministrationSummaries(): void
    {
        $geo = $this->getGeoIds();
        $dateRange = $this->getDateRange('smc_drug_administration', 'collected_date');
        $periodList = $this->getPeriodList();

        $dashboard = new \Smc\Dashboard();

        $lgaSummary = $dashboard->DrugAdminListLga();
        $this->assertRowsHaveColumns($lgaSummary, ['id', 'title', 'total', 'eligible', 'non_eligible', 'referral', 'spaq1', 'spaq2']);

        $lgaFiltered = $dashboard->DrugAdminListLga($periodList, $dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($lgaFiltered, ['id', 'title', 'total', 'eligible', 'non_eligible', 'referral', 'spaq1', 'spaq2']);

        $wardSummary = $dashboard->DrugAdminListWard($geo['lgaid']);
        $this->assertRowsHaveColumns($wardSummary, ['id', 'title', 'total', 'eligible', 'non_eligible', 'referral', 'spaq1', 'spaq2']);

        $dpSummary = $dashboard->DrugAdminListDp($geo['wardid']);
        $this->assertRowsHaveColumns($dpSummary, ['id', 'title', 'total', 'eligible', 'non_eligible', 'referral', 'spaq1', 'spaq2']);
    }

    public function testReferralSummaries(): void
    {
        $geo = $this->getGeoIds();
        $dateRange = $this->getDateRange('smc_drug_administration', 'collected_date');
        $periodList = $this->getPeriodList();

        $dashboard = new \Smc\Dashboard();

        $lgaSummary = $dashboard->ReferralListLga();
        $this->assertRowsHaveColumns($lgaSummary, ['id', 'title', 'total', 'referred', 'attended']);

        $lgaFiltered = $dashboard->ReferralListLga($periodList, $dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($lgaFiltered, ['id', 'title', 'total', 'referred', 'attended']);

        $wardSummary = $dashboard->ReferralListWard($geo['lgaid']);
        $this->assertRowsHaveColumns($wardSummary, ['id', 'title', 'total', 'referred', 'attended']);

        $dpSummary = $dashboard->ReferralListDp($geo['wardid']);
        $this->assertRowsHaveColumns($dpSummary, ['id', 'title', 'total', 'referred', 'attended']);
    }

    public function testIccSummaries(): void
    {
        $geo = $this->getGeoIds();
        $dateRange = $this->getDateRange('smc_icc_collection', 'issue_date');
        $periodList = $this->getPeriodList();

        $dashboard = new \Smc\Dashboard();

        $lgaDeleted = $dashboard->IccListLga_deleted();
        $this->assertRowsHaveColumns($lgaDeleted, [
            'id', 'title', 'count_facility', 'count_team', 'drug', 'issue', 'full_return', 'partial_return', 'wasted_return', 'used'
        ]);

        $wardDeleted = $dashboard->IccListWard_deleted($geo['lgaid']);
        $this->assertRowsHaveColumns($wardDeleted, [
            'id', 'title', 'count_facility', 'count_team', 'drug', 'issue', 'full_return', 'partial_return', 'wasted_return', 'used'
        ]);

        $dpDeleted = $dashboard->IccListDp_deleted($geo['wardid']);
        $this->assertRowsHaveColumns($dpDeleted, [
            'id', 'title', 'count_facility', 'count_team', 'drug', 'issue', 'full_return', 'partial_return', 'wasted_return', 'used'
        ]);

        $lgaSummary = $dashboard->IccListLga($periodList, $dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($lgaSummary, [
            'id', 'title', 'period', 'drug', 'count_facility', 'count_team', 'issued', 'pending', 'total_issued',
            'confirmed', 'accepted', 'returned', 'reconciled', 'administered', 'redosed', 'wasted', 'loss'
        ]);

        $wardSummary = $dashboard->IccListWard($geo['lgaid'], $periodList, $dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($wardSummary, [
            'id', 'title', 'period', 'drug', 'count_facility', 'count_team', 'issued', 'total_issued', 'pending',
            'confirmed', 'accepted', 'returned', 'reconciled', 'administered', 'redosed', 'wasted', 'loss'
        ]);

        $dpSummary = $dashboard->IccListDp($geo['wardid'], $periodList, $dateRange['start'], $dateRange['end']);
        $this->assertRowsHaveColumns($dpSummary, [
            'id', 'title', 'period', 'drug', 'count_facility', 'count_team', 'issued', 'total_issued', 'pending',
            'confirmed', 'accepted', 'returned', 'reconciled', 'administered', 'redosed', 'wasted', 'loss'
        ]);
    }

    private function getGeoIds(): array
    {
        $db = $this->getDb();

        $lgaid = (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 0);
        $wardid = (int) ($this->safeSelectValue($db, 'SELECT wardid AS val FROM ms_geo_ward LIMIT 1') ?? 0);
        $dpid = (int) ($this->safeSelectValue($db, 'SELECT dpid AS val FROM ms_geo_dp LIMIT 1') ?? 0);

        return [
            'lgaid' => $lgaid ?: 0,
            'wardid' => $wardid ?: 0,
            'dpid' => $dpid ?: 0,
        ];
    }

    private function getPeriodList(): string
    {
        $db = $this->getDb();
        $periodId = (int) ($this->safeSelectValue($db, 'SELECT periodid AS val FROM smc_period LIMIT 1') ?? 0);
        return $periodId ? (string) $periodId : '1';
    }

    private function getDateRange(string $table, string $column): array
    {
        $db = $this->getDb();
        $start = $this->safeSelectValue($db, "SELECT DATE(MIN($column)) AS val FROM $table WHERE $column IS NOT NULL");
        $end = $this->safeSelectValue($db, "SELECT DATE(MAX($column)) AS val FROM $table WHERE $column IS NOT NULL");

        return [
            'start' => $start ?: date('Y-m-d'),
            'end' => $end ?: date('Y-m-d'),
        ];
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }

        return $rows[0]['val'] ?? null;
    }

    private function assertRowsHaveColumns(array $rows, array $columns): void
    {
        $this->assertIsArray($rows);

        if (count($rows) === 0) {
            $this->assertTrue(true);
            return;
        }

        foreach ($columns as $column) {
            $this->assertArrayHasKey($column, $rows[0]);
        }
    }
}
