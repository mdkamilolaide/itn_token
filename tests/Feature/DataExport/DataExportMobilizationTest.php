<?php

namespace Tests\Feature\DataExport;

use Tests\TestCase;

/**
 * Data export tests for mobilization exports.
 */
class DataExportMobilizationTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/reporting/reporting.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    public function testMobilizationExports(): void
    {
        $geo = $this->getGeoIds();
        $dateRange = $this->getDateRange('hhm_mobilization', 'collected_date');

        $reporting = new \Reporting\Reporting();

        $lgaPayload = $reporting->ListMobilizationByLga('state', $geo['stateid']);
        $this->assertExportPayload($lgaPayload, 'Mobilization by LGA');

        $dpPayload = $reporting->ListMobilizationByDp('state', $geo['stateid']);
        $this->assertExportPayload($dpPayload, 'Mobilization by DP');

        $dateLgaPayload = $reporting->ListDateMobilizationByLga($dateRange['start'], 'state', $geo['stateid']);
        $this->assertExportPayload($dateLgaPayload, 'Date Mobilization');

        $dateRangePayload = $reporting->ListDateRangeMobilizationByLga($dateRange['start'], $dateRange['end'], 'state', $geo['stateid']);
        $this->assertExportPayload($dateRangePayload, 'Date Mobilization');

        $dateDpPayload = $reporting->ListDateMobilizationByDp($dateRange['start'], 'state', $geo['stateid']);
        $this->assertExportPayload($dateDpPayload, 'Date Mobilization');
    }

    private function getGeoIds(): array
    {
        $db = $this->getDb();

        return [
            'stateid' => (int) ($this->safeSelectValue($db, 'SELECT stateid AS val FROM ms_geo_state LIMIT 1') ?? 0),
            'lgaid' => (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 0),
            'wardid' => (int) ($this->safeSelectValue($db, 'SELECT wardid AS val FROM ms_geo_ward LIMIT 1') ?? 0),
        ];
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

    private function assertExportPayload(string $payload, string $expectedSheet): void
    {
        $data = json_decode($payload, true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals($expectedSheet, $data[0]['sheetName']);
        $this->assertIsArray($data[0]['data']);
    }
}
