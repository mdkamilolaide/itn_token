<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

/**
 * Dashboard rendering and data display tests
 */
class DashboardDisplayTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/dashboard/enetcard.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/mobilization.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    public function testEnetcardTopSummaryAndDrilldowns(): void
    {
        $geo = $this->getGeoIds();
        $enetcard = new \Dashboard\Enetcard();

        $topSummary = $enetcard->TopSummary();
        $this->assertRowsHaveColumns($topSummary, [
            'total',
            'state',
            'lga',
            'ward',
            'mobilizer_online',
            'mobilizer_pending',
            'mobilizer_wallet',
            'beneficiary',
        ]);

        $lgaSummary = $enetcard->TopLgaSummary();
        $this->assertRowsHaveColumns($lgaSummary, [
            'LgaId',
            'lga',
            'lga_total',
            'lga_balance',
            'ward',
            'mob_online',
            'mob_pending',
            'wallet',
            'beneficiary',
        ]);

        $wardSummary = $enetcard->TopWardSummary($geo['lgaid']);
        $this->assertRowsHaveColumns($wardSummary, [
            'wardid',
            'ward',
            'ward_total',
            'ward_balance',
            'mob_online',
            'mob_pending',
            'wallet',
            'beneficiary',
        ]);

        $mobilizerSummary = $enetcard->TopMobilizerSummary($geo['wardid']);
        $this->assertRowsHaveColumns($mobilizerSummary, [
            'userid',
            'mobilizer',
            'total',
            'mob_online',
            'mob_pending',
            'wallet',
            'beneficiary',
        ]);
    }

    public function testMobilizationTopSummaryAndDateAggregates(): void
    {
        $geo = $this->getGeoIds();
        $date = $this->getSampleDate('hhm_mobilization', 'collected_date');

        $mobilization = new \Dashboard\Mobilization();

        $topSummary = $mobilization->TopSummary();
        $this->assertRowsHaveColumns($topSummary, ['households', 'netcards', 'family_size']);

        $byDate = $mobilization->TopSummaryByDate();
        $this->assertRowsHaveColumns($byDate, ['title', 'households', 'netcards', 'family_size']);

        $lgaByDate = $mobilization->LgaAggregateByDate($date);
        $this->assertRowsHaveColumns($lgaByDate, ['title', 'households', 'netcards', 'family_size', 'lgaid']);

        $wardByDate = $mobilization->WardAggregateByDate($date, $geo['lgaid']);
        $this->assertRowsHaveColumns($wardByDate, ['title', 'households', 'netcards', 'family_size', 'wardid']);

        $dpByDate = $mobilization->DpAggregateByDate($date, $geo['wardid']);
        $this->assertRowsHaveColumns($dpByDate, ['title', 'households', 'netcards', 'family_size', 'dpid']);
    }

    public function testMobilizationLocationAggregates(): void
    {
        $geo = $this->getGeoIds();
        $mobilization = new \Dashboard\Mobilization();

        $topByLocation = $mobilization->TopSummaryByLocation();
        $this->assertRowsHaveColumns($topByLocation, ['title', 'households', 'netcards', 'family_size', 'lgaid']);

        $wardByLocation = $mobilization->WardAggregateByLocation($geo['lgaid']);
        $this->assertRowsHaveColumns($wardByLocation, ['title', 'households', 'netcards', 'family_size', 'wardid']);

        $dpByLocation = $mobilization->DpAggregateByLocation($geo['wardid']);
        $this->assertRowsHaveColumns($dpByLocation, ['title', 'households', 'netcards', 'family_size', 'dpid']);
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
