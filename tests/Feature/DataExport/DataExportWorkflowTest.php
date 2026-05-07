<?php

namespace Tests\Feature\DataExport;

use Tests\TestCase;

class DataExportWorkflowTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/reporting/reporting.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
        require_once $this->projectRoot . '/lib/vendor/autoload.php';
    }

    /**
     * Test complete data export workflow
     */
    public function testCompleteDataExportWorkflow()
    {
        $reporting = new \Reporting\Reporting();
        $trainingId = $this->getTrainingId();
        $geo = $this->getGeoIds();

        $payload = $reporting->ListParticipants($trainingId, 'state', $geo['stateid']);
        $data = $this->assertExportPayload($payload, 'Participants');

        $this->assertIsArray($data[0]['data']);
    }

    /**
     * Test CSV data export
     */
    public function testCSVExportWorkflow()
    {
        $reporting = new \Reporting\Reporting();
        $geo = $this->getGeoIds();

        $payload = $reporting->ListMobilizationByLga('state', $geo['stateid']);
        $data = $this->assertExportPayload($payload, 'Mobilization by LGA');

        $csv = $this->convertSheetToCsv($data[0]['data']);
        $this->assertIsString($csv);
    }

    /**
     * Test Excel data export
     */
    public function testExcelExportWorkflow()
    {
        $reporting = new \Reporting\Reporting();
        $geo = $this->getGeoIds();

        $payload = $reporting->ListDistributionByLga('state', $geo['stateid']);
        $data = $this->assertExportPayload($payload, 'Distribution by LGA');

        $this->assertIsArray($data[0]['data']);
    }

    /**
     * Test data filtering before export
     */
    public function testDataFilteringExportWorkflow()
    {
        $reporting = new \Reporting\Reporting();
        $trainingId = $this->getTrainingId();
        $geo = $this->getGeoIds();

        $statePayload = $reporting->ListUncapturedUsers($trainingId, 'state', $geo['stateid']);
        $this->assertExportPayload($statePayload, 'Uncaptured');

        $invalidPayload = $reporting->ListUncapturedUsers($trainingId, 'invalid', 0);
        $this->assertExportPayload($invalidPayload, 'Uncaptured');
    }

    /**
     * Test export permissions and access control
     */
    public function testExportAccessControlWorkflow()
    {
        $geo = $this->getGeoIds();
        $trainingId = $this->getTrainingId();

        $response = $this->runExportEndpoint('401', [
            'tid' => $trainingId,
            'gl' => 'state',
            'glid' => $geo['stateid'],
        ], $this->buildToken());

        $data = json_decode($response, true);
        $this->assertIsArray($data);
        $this->assertEquals('Participants', $data[0]['sheetName']);

        $this->expectException(\Firebase\JWT\ExpiredException::class);

        $this->runExportEndpoint('401', [
            'tid' => $trainingId,
            'gl' => 'state',
            'glid' => $geo['stateid'],
        ], $this->buildToken(['exp' => time() - 3600]));
    }

    private function getTrainingId(): int
    {
        $db = $this->getDb();
        return (int) ($this->safeSelectValue($db, 'SELECT trainingid AS val FROM tra_training LIMIT 1') ?? 0);
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

    private function assertExportPayload(string $payload, string $expectedSheet): array
    {
        $data = json_decode($payload, true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals($expectedSheet, $data[0]['sheetName']);
        $this->assertIsArray($data[0]['data']);

        return $data;
    }

    private function convertSheetToCsv(array $sheet): string
    {
        $lines = [];
        foreach ($sheet as $row) {
            $values = array_map(function ($value) {
                return '"' . str_replace('"', '""', (string) $value) . '"';
            }, $row);
            $lines[] = implode(',', $values);
        }

        return implode("\n", $lines);
    }

    private function buildToken(array $overrides = []): string
    {
        require $this->projectRoot . '/lib/config.php';

        $payload = array_merge([
            'iss' => $issuer_claim,
            'aud' => $audience_claim,
            'iat' => $issuedat_claim->getTimestamp(),
            'nbf' => $issuedat_claim->getTimestamp(),
            'exp' => $expire_claim,
            'user_id' => 1,
            'login_id' => 'admin',
            'fullname' => 'Admin User',
            'geo_level' => 'state',
            'geo_level_id' => 1,
            'system_privilege' => json_encode([]),
            'user_change_password' => 0,
            'priority' => 1,
            'role' => 'Administrator',
        ], $overrides);

        $secretKey = file_get_contents($this->projectRoot . '/lib/privateKey.pem');
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS512');
    }

    private function runExportEndpoint(string $qid, array $params, string $token): string
    {
        require $this->projectRoot . '/lib/config.php';

        $_GET = ['qid' => $qid] + $params;
        $_POST = [];
        $_REQUEST = $_GET;
        $_COOKIE[$secret_code_token] = $token;

        $prevReporting = error_reporting();
        error_reporting($prevReporting & ~E_WARNING);

        ob_start();
        try {
            include $this->projectRoot . '/services.export.php';
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            error_reporting($prevReporting);
            throw $e;
        }

        error_reporting($prevReporting);

        return $output;
    }
}
