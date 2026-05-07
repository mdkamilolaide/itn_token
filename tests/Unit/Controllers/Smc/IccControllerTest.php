<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\Icc;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC ICC Controller
 * 
 * Tests the SMC Integrated Community Case management controller methods in isolation
 */
class IccControllerTest extends SmcTestCase
{
    public function testBulkIssueAndDownloadFlow(): void
    {
        $this->requireSchema([
            'smc_icc_issue' => ['issue_id', 'uid', 'dpid', 'issuer_id', 'cdd_lead_id', 'periodid', 'issue_date', 'issue_drug', 'drug_qty'],
            'smc_icc_collection' => ['issue_id', 'periodid', 'dpid', 'cdd_lead_id', 'drug', 'qty', 'total_qty', 'issue_date', 'status_code', 'is_accepted'],
            'smc_icc_download_log' => ['download_id', 'issue_id', 'cdd_lead_id'],
        ]);

        $controller = new Icc();

        $payload = [[
            'uid' => 'UID-' . uniqid(),
            'dpid' => 111,
            'issuer_id' => 501,
            'cdd_lead_id' => 601,
            'periodid' => 1,
            'issue_date' => date('Y-m-d'),
            'issue_drug' => 'SPAQ 1',
            'drug_qty' => 10,
            'device_serial' => 'DEV',
            'app_version' => '1.0',
        ]];

        $result = $controller->BulkIccIssue($payload);
        $this->assertSame([['uid' => $payload[0]['uid']]], $result);

        $issue = $this->getDb()->DataTable("SELECT issue_id FROM smc_icc_issue WHERE uid = '{$payload[0]['uid']}'");
        $this->assertNotEmpty($issue);
        $issueId = (int) $issue[0]['issue_id'];
        $this->recordCleanup('smc_icc_issue', 'issue_id', $issueId);
        $this->recordCleanup('smc_icc_collection', 'issue_id', $issueId);

        $collection = $this->getDb()->DataTable("SELECT issue_id, status_code FROM smc_icc_collection WHERE issue_id = {$issueId}");
        $this->assertNotEmpty($collection);
        if ($this->tableHasColumns('smc_icc_collection', ['status_code'])) {
            $this->getDb()->executeTransaction('UPDATE smc_icc_collection SET status_code = 10 WHERE issue_id = ?', [$issueId]);
        }

        $balance = $controller->IccDownloadBalance(1, 601, 'DEV-1', '1.0');
        $this->assertIsArray($balance);
        $this->assertNotEmpty($balance);

        $downloadId = $balance[0]['download_id'] ?? null;
        $this->assertNotEmpty($downloadId);
        $this->recordCleanup('smc_icc_collection', 'download_id', $downloadId);
        $this->recordCleanup('smc_icc_download_log', 'download_id', $downloadId);

        $confirmed = $controller->ConfirmDownload($downloadId, 601, $issueId);
        $this->assertTrue($confirmed);

        $repeat = $controller->ConfirmDownload($downloadId, 601, $issueId);
        $this->assertTrue($repeat);
    }

    public function testAcceptanceAndRejection(): void
    {
        $this->requireSchema([
            'smc_icc_issue' => ['issue_id', 'confirmation', 'confirmation_note'],
            'smc_icc_collection' => ['issue_id', 'status_code'],
        ]);

        $controller = new Icc();

        $issueId = $this->seedIccIssue([
            'uid' => 'UID-' . uniqid(),
            'dpid' => 123,
            'issuer_id' => 700,
            'cdd_lead_id' => 701,
            'periodid' => 1,
            'issue_date' => date('Y-m-d'),
            'issue_drug' => 'SPAQ 1',
            'drug_qty' => 5,
        ]);

        $this->seedIccCollection([
            'issue_id' => $issueId,
            'periodid' => 1,
            'dpid' => 123,
            'cdd_lead_id' => 701,
            'drug' => 'SPAQ 1',
            'qty' => 5,
            'total_qty' => 5,
            'status_code' => 30,
            'issue_date' => date('Y-m-d'),
        ]);

        $accepted = $controller->AcceptanceAccept($issueId);
        $this->assertTrue($accepted);

        $acceptedRow = $this->getDb()->DataTable("SELECT confirmation FROM smc_icc_issue WHERE issue_id = {$issueId}");
        $this->assertSame('1', (string) $acceptedRow[0]['confirmation']);

        $rejectId = $this->seedIccIssue([
            'uid' => 'UID-' . uniqid(),
            'dpid' => 124,
            'issuer_id' => 702,
            'cdd_lead_id' => 703,
            'periodid' => 1,
            'issue_date' => date('Y-m-d'),
            'issue_drug' => 'SPAQ 2',
            'drug_qty' => 2,
        ]);

        $this->seedIccCollection([
            'issue_id' => $rejectId,
            'periodid' => 1,
            'dpid' => 124,
            'cdd_lead_id' => 703,
            'drug' => 'SPAQ 2',
            'qty' => 2,
            'total_qty' => 2,
            'status_code' => 30,
            'issue_date' => date('Y-m-d'),
        ]);

        $rejected = $controller->AcceptanceReject($rejectId, 'damaged');
        $this->assertTrue($rejected);

        $rejectedRow = $this->getDb()->DataTable("SELECT confirmation FROM smc_icc_issue WHERE issue_id = {$rejectId}");
        $this->assertSame('-1', (string) $rejectedRow[0]['confirmation']);
    }
}
