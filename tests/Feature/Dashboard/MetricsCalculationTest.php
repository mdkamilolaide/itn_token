<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

/**
 * Dashboard metrics and aggregation calculations
 */
class MetricsCalculationTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/dashboard/distribution.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/eolin.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    public function testDistributionTopSummaryAndLocationAggregates(): void
    {
        $geo = $this->getGeoIds();
        $distribution = new \Dashboard\Distribution();

        $topSummary = $distribution->TopSummary();
        $this->assertRowsHaveColumns($topSummary, [
            'household_mobilized',
            'household_redeemed',
            'familysize_mobilized',
            'familysize_redeemed',
            'net_issued',
            'net_redeemed',
        ]);

        $lgaAggregate = $distribution->LgaAggregateByLocation();
        $this->assertRowsHaveColumns($lgaAggregate, [
            'id',
            'title',
            'household_mobilized',
            'household_redeemed',
            'familysize_mobilized',
            'familysize_redeemed',
            'net_issued',
            'net_redeemed',
        ]);

        $wardAggregate = $distribution->WardAggregateByLocation($geo['lgaid']);
        $this->assertRowsHaveColumns($wardAggregate, [
            'id',
            'title',
            'household_mobilized',
            'household_redeemed',
            'familysize_mobilized',
            'familysize_redeemed',
            'net_issued',
            'net_redeemed',
        ]);

        $dpAggregate = $distribution->DpAggregateByLocation($geo['wardid']);
        $this->assertRowsHaveColumns($dpAggregate, [
            'id',
            'title',
            'household_mobilized',
            'household_redeemed',
            'familysize_mobilized',
            'familysize_redeemed',
            'net_issued',
            'net_redeemed',
        ]);
    }

    public function testDistributionDateAggregates(): void
    {
        $geo = $this->getGeoIds();
        $date = $this->getSampleDate('hhm_distribution', 'collected_date');

        $distribution = new \Dashboard\Distribution();

        $topByDate = $distribution->TopAggregateByDate();
        $this->assertRowsHaveColumns($topByDate, [
            'title',
            'household_redeemed',
            'net_redeemed',
            'familysize_redeemed',
        ]);

        $lgaByDate = $distribution->LgaAggregateByDate($date);
        $this->assertRowsHaveColumns($lgaByDate, [
            'id',
            'title',
            'household_redeemed',
            'net_redeemed',
            'familysize_redeemed',
        ]);

        $wardByDate = $distribution->WardAggregateByDate($date, $geo['lgaid']);
        $this->assertRowsHaveColumns($wardByDate, [
            'id',
            'title',
            'household_redeemed',
            'net_redeemed',
            'familysize_redeemed',
        ]);

        $dpByDate = $distribution->DpAggregateByDate($date, $geo['wardid']);
        $this->assertRowsHaveColumns($dpByDate, [
            'id',
            'title',
            'household_redeemed',
            'net_redeemed',
            'familysize_redeemed',
        ]);
    }

    public function testEolinSummaries(): void
    {
        $geo = $this->getGeoIds();
        $eolin = new \Dashboard\Eolin();

        $topMobilization = $eolin->TopSummaryMobilization();
        $this->assertRowsHaveColumns($topMobilization, ['total_household', 'total_net']);

        $lgaMobilization = $eolin->LgaSummaryMobilization();
        $this->assertRowsHaveColumns($lgaMobilization, ['lgaid', 'lga', 'total_household', 'total_net']);

        $wardMobilization = $eolin->WardSummaryMobilization($geo['lgaid']);
        $this->assertRowsHaveColumns($wardMobilization, ['wardid', 'ward', 'total_household', 'total_net']);

        $dpMobilization = $eolin->DpSummaryMobilization($geo['wardid']);
        $this->assertRowsHaveColumns($dpMobilization, ['dpid', 'dp', 'total_household', 'total_net']);

        $topDistribution = $eolin->TopSummaryDistribution();
        $this->assertRowsHaveColumns($topDistribution, ['total_household', 'total_net']);

        $lgaDistribution = $eolin->LgaSummaryDistribution();
        $this->assertRowsHaveColumns($lgaDistribution, ['lgaid', 'lga', 'total_household', 'total_net']);

        $wardDistribution = $eolin->WardSummaryDistribution($geo['lgaid']);
        $this->assertRowsHaveColumns($wardDistribution, ['wardid', 'ward', 'total_household', 'total_net']);

        $dpDistribution = $eolin->DpSummaryDistribution($geo['wardid']);
        $this->assertRowsHaveColumns($dpDistribution, ['dpid', 'dp', 'total_household', 'total_net']);
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

    private function getSampleDate(string $table, string $column): string
    {
        $db = $this->getDb();
        $date = $this->safeSelectValue($db, "SELECT DATE($column) AS val FROM $table WHERE $column IS NOT NULL LIMIT 1");
        return $date ?: date('Y-m-d');
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
