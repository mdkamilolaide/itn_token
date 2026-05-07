<?php

namespace Tests\Feature\DataExport;

use Tests\TestCase;

/**
 * Data export tests for participants and bank verification exports.
 */
class DataExportParticipantsTest extends TestCase
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

    public function testParticipantsExportByGeoLevel(): void
    {
        $trainingId = $this->getTrainingId();
        $geo = $this->getGeoIds();

        $reporting = new \Reporting\Reporting();

        $statePayload = $reporting->ListParticipants($trainingId, 'state', $geo['stateid']);
        $this->assertExportPayload($statePayload, 'Participants');

        $lgaPayload = $reporting->ListParticipants($trainingId, 'lga', $geo['lgaid']);
        $this->assertExportPayload($lgaPayload, 'Participants');

        $wardPayload = $reporting->ListParticipants($trainingId, 'ward', $geo['wardid']);
        $this->assertExportPayload($wardPayload, 'Participants');

        $invalidPayload = $reporting->ListParticipants($trainingId, 'invalid', 0);
        $this->assertExportPayload($invalidPayload, 'Participants');
    }

    public function testBankVerificationExportByGeoLevel(): void
    {
        $trainingId = $this->getTrainingId();
        $geo = $this->getGeoIds();

        $reporting = new \Reporting\Reporting();

        $statePayload = $reporting->ListBankVerification($trainingId, 'state', $geo['stateid']);
        $this->assertExportPayload($statePayload, 'Verification Status');

        $lgaPayload = $reporting->ListBankVerification($trainingId, 'lga', $geo['lgaid']);
        $this->assertExportPayload($lgaPayload, 'Verification Status');

        $wardPayload = $reporting->ListBankVerification($trainingId, 'ward', $geo['wardid']);
        $this->assertExportPayload($wardPayload, 'Verification Status');

        $invalidPayload = $reporting->ListBankVerification($trainingId, 'invalid', 0);
        $this->assertExportPayload($invalidPayload, 'Verification Status');
    }

    public function testUncapturedUsersExportByGeoLevel(): void
    {
        $trainingId = $this->getTrainingId();
        $geo = $this->getGeoIds();

        $reporting = new \Reporting\Reporting();

        $statePayload = $reporting->ListUncapturedUsers($trainingId, 'state', $geo['stateid']);
        $this->assertExportPayload($statePayload, 'Uncaptured');

        $lgaPayload = $reporting->ListUncapturedUsers($trainingId, 'lga', $geo['lgaid']);
        $this->assertExportPayload($lgaPayload, 'Uncaptured');

        $wardPayload = $reporting->ListUncapturedUsers($trainingId, 'ward', $geo['wardid']);
        $this->assertExportPayload($wardPayload, 'Uncaptured');

        $invalidPayload = $reporting->ListUncapturedUsers($trainingId, 'invalid', 0);
        $this->assertExportPayload($invalidPayload, 'Uncaptured');
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

    private function assertExportPayload(string $payload, string $expectedSheet): void
    {
        $data = json_decode($payload, true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals($expectedSheet, $data[0]['sheetName']);
        $this->assertIsArray($data[0]['data']);
    }
}
