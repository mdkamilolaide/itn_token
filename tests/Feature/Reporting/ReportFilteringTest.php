<?php

namespace Tests\Feature\Reporting;

use Tests\TestCase;

class ReportFilteringTest extends TestCase
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

    public function testReportingFiltersWithGeoLevels(): void
    {
        $geo = $this->getGeoSample();
        $report = new \Reporting\Reporting();

        $this->assertExcelPayload(
            $report->ListMobilizationByLga('state', $geo['stateid']),
            'Mobilization by LGA'
        );
        $this->assertExcelPayload(
            $report->ListMobilizationByLga('lga', $geo['lgaid']),
            'Mobilization by LGA'
        );

        $this->assertExcelPayload(
            $report->ListDistributionByDp('ward', $geo['wardid']),
            'Distribution by DP'
        );
    }

    public function testReportingFiltersWithDates(): void
    {
        $geo = $this->getGeoSample();
        $date = $this->getSampleDate();
        $report = new \Reporting\Reporting();

        $this->assertExcelPayload(
            $report->ListDateMobilizationByLga($date, 'lga', $geo['lgaid']),
            'Date Mobilization'
        );
        $this->assertExcelPayload(
            $report->ListDateMobilizationByDp($date, 'ward', $geo['wardid']),
            'Date Mobilization'
        );

        $this->assertExcelPayload(
            $report->ListDateDistributionByLga($date, 'lga', $geo['lgaid']),
            'Date Distribution'
        );
        $this->assertExcelPayload(
            $report->ListDateDistributionByDp($date, 'ward', $geo['wardid']),
            'Date Distribution'
        );
    }

    private function assertExcelPayload(string $payload, string $expectedSheet): void
    {
        $decoded = json_decode($payload, true);
        $this->assertIsArray($decoded, $payload);
        $this->assertEquals($expectedSheet, $decoded[0]['sheetName'] ?? null);
        $this->assertArrayHasKey('data', $decoded[0]);
        $this->assertIsArray($decoded[0]['data']);
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT stateid, lgaid, wardid FROM sys_geo_codex WHERE geo_level='dp' LIMIT 1");
        $row = $rows[0] ?? ['stateid' => 1, 'lgaid' => 1, 'wardid' => 1];

        return [
            'stateid' => (int) ($row['stateid'] ?? 1),
            'lgaid' => (int) ($row['lgaid'] ?? 1),
            'wardid' => (int) ($row['wardid'] ?? 1),
        ];
    }

    private function getSampleDate(): string
    {
        return date('Y-m-d');
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }
}