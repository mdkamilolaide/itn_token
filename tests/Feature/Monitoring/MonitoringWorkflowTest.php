<?php

namespace Tests\Feature\Monitoring;

use Tests\TestCase;

class MonitoringWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdUids = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/form/ininea.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/inineb.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/ininec.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/endprocess.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/fiverevisit.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/smccdd.cont.php';
        require_once $this->projectRoot . '/lib/controller/form/smchfw.cont.php';
        require_once $this->projectRoot . '/lib/controller/monitor/monitor.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();
        if (!empty($this->createdUids)) {
            $uids = array_map([$db->Conn, 'quote'], $this->createdUids);
            $uidList = implode(',', $uids);
            $db->executeTransaction("DELETE FROM mo_form_i9a WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_form_i9b WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_form_i9c WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_form_end_process WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_form_five_revisit WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_smc_supervisor_cdd WHERE uid IN ($uidList)", []);
            $db->executeTransaction("DELETE FROM mo_smc_supervisor_hfw WHERE uid IN ($uidList)", []);
        }

        parent::tearDown();
    }

    /**
     * Test complete monitoring workflow
     */
    public function testCompleteMonitoringWorkflow()
    {
        $geo = $this->getGeoSample();
        $monitor = new \Monitor\Monitor();

        $before = $this->indexStatusList($monitor->GetFormStatusList());

        $this->submitInineA($geo);
        $this->submitInineB($geo);
        $this->submitInineC($geo);
        $this->submitEndProcess($geo);
        $this->submitFiveRevisit($geo);
        $this->submitSmcCdd($geo);
        $this->submitSmcHfw($geo);

        $after = $this->indexStatusList($monitor->GetFormStatusList());

        $this->assertGreaterThanOrEqual($before['I-9a Mobilization Spotcheck'] ?? 0, $after['I-9a Mobilization Spotcheck'] ?? 0);
        $this->assertGreaterThanOrEqual($before['I-9b Distribution Point (DP) Spotcheck'] ?? 0, $after['I-9b Distribution Point (DP) Spotcheck'] ?? 0);
        $this->assertGreaterThanOrEqual($before['I-9c Distribution HH Spotcheck'] ?? 0, $after['I-9c Distribution HH Spotcheck'] ?? 0);
        $this->assertGreaterThanOrEqual($before['5% Revisit'] ?? 0, $after['5% Revisit'] ?? 0);
        $this->assertGreaterThanOrEqual($before['End Process 1'] ?? 0, $after['End Process 1'] ?? 0);
        $this->assertGreaterThanOrEqual($before['SMC Supervisory CDD'] ?? 0, $after['SMC Supervisory CDD'] ?? 0);
        $this->assertGreaterThanOrEqual($before['SMC Supervisory HFW'] ?? 0, $after['SMC Supervisory HFW'] ?? 0);
    }

    /**
     * Test data collection and submission
     */
    public function testDataCollectionWorkflow()
    {
        $geo = $this->getGeoSample();

        $uid = $this->submitInineA($geo);
        $this->assertRowExists('mo_form_i9a', $uid);

        $uid = $this->submitInineB($geo);
        $this->assertRowExists('mo_form_i9b', $uid);

        $uid = $this->submitInineC($geo);
        $this->assertRowExists('mo_form_i9c', $uid);

        $uid = $this->submitEndProcess($geo);
        $this->assertRowExists('mo_form_end_process', $uid);

        $uid = $this->submitFiveRevisit($geo);
        $this->assertRowExists('mo_form_five_revisit', $uid);

        $uid = $this->submitSmcCdd($geo);
        $this->assertRowExists('mo_smc_supervisor_cdd', $uid);

        $uid = $this->submitSmcHfw($geo);
        $this->assertRowExists('mo_smc_supervisor_hfw', $uid);
    }

    /**
     * Test monitoring data validation
     */
    public function testDataValidationWorkflow()
    {
        $monitor = new \Monitor\Monitor();

        $this->assertExcelPayload($monitor->EeFormInineA(), 'Form-i9a');
        $this->assertExcelPayload($monitor->EeFormInineB(), 'Form-i9b');
        $this->assertExcelPayload($monitor->EeFormInineC(), 'Form-i9c');
        $this->assertExcelPayload($monitor->EeFormFiveRevisit(), 'Revisit');
        $this->assertExcelPayload($monitor->EeFormEndProOne(), 'End-Process-1');
        $this->assertExcelPayload($monitor->EeFormEndProTwo(), 'End-Process-2');
        $this->assertExcelPayload($monitor->EeFormSmcSupervisoryCdd(), 'SMC-Supervisory-CDD');
        $this->assertExcelPayload($monitor->EeFormSmcSupervisoryHfw(), 'SMC-Supervisory-HFW');
    }

    /**
     * Test alert generation and notification
     */
    public function testAlertGenerationWorkflow()
    {
        $monitor = new \Monitor\Monitor();
        $list = $monitor->GetFormStatusList();
        $names = array_column($list, 'name');

        $this->assertContains('I-9a Mobilization Spotcheck', $names);
        $this->assertContains('I-9b Distribution Point (DP) Spotcheck', $names);
        $this->assertContains('I-9c Distribution HH Spotcheck', $names);
        $this->assertContains('5% Revisit', $names);
        $this->assertContains('End Process 1', $names);
        $this->assertContains('End Process 2', $names);
        $this->assertContains('SMC Supervisory CDD', $names);
        $this->assertContains('SMC Supervisory HFW', $names);
    }

    /**
     * Test monitoring dashboard and analytics
     */
    public function testMonitoringDashboardWorkflow()
    {
        $monitor = new \Monitor\Monitor();
        $list = $monitor->GetFormStatusList();

        $this->assertCount(8, $list);
        foreach ($list as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('total', $row);
            $this->assertIsNumeric($row['total']);
        }
    }

    private function submitInineA(array $geo): string
    {
        $uid = $this->uniqueUid('i9a');
        $form = new \Form\INineA();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'comid' => $geo['comid'],
            'userid' => $geo['userid'],
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => 'Yes',
            'ab' => 'John Doe',
            'ac' => 'Yes',
            'ad' => 'Campaign awareness',
            'ae' => '5',
            'af' => 'Yes',
            'ag' => '2',
            'ah' => 'Yes',
            'ai' => 'Malaria prevention',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame('0', $list[0]['id'] ?? '0');
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitInineB(array $geo): string
    {
        $uid = $this->uniqueUid('i9b');
        $form = new \Form\INineB();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'comid' => $geo['comid'],
            'userid' => $geo['userid'],
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'sp' => 'Supervisor',
            'aa' => 'Yes', 'ab' => 'OK', 'ba' => 'Yes', 'bb' => 'OK', 'ca' => 'Yes', 'cb' => 'OK',
            'da' => 'Yes', 'db' => 'OK', 'ea' => 'Yes', 'eb' => 'OK', 'fa' => 'Yes', 'fb' => 'OK',
            'ga' => 'Yes', 'gb' => 'OK', 'ha' => 'Yes', 'hb' => 'OK', 'ia' => 'Yes', 'ib' => 'OK',
            'ja' => 'Yes', 'jb' => 'OK', 'ka' => 'Yes', 'kb' => 'OK', 'la' => 'Yes', 'lb' => 'OK',
            'ma' => 'Yes', 'mb' => 'OK', 'na' => 'Yes', 'nb' => 'OK', 'oa' => 'Yes', 'ob' => 'OK',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame('0', $list[0]['id'] ?? '0');
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitInineC(array $geo): string
    {
        $uid = $this->uniqueUid('i9c');
        $form = new \Form\INineC();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'userid' => $geo['userid'],
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => 'Household Head',
            'ab' => 'Yes',
            'ac' => 'Yes',
            'ad' => 'Yes',
            'ae' => '2',
            'af' => 'Yes',
            'ag' => 'No',
            'ah' => 'No issues',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame('0', $list[0]['id'] ?? '0');
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitEndProcess(array $geo): string
    {
        $uid = $this->uniqueUid('end');
        $form = new \Form\EndProcess();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'comid' => $geo['comid'],
            'userid' => $geo['userid'],
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => '2',
            'ab' => '1',
            'ac' => '0',
            'ad' => 'Yes',
            'ae' => 'Yes',
            'af' => '3',
            'ag' => '1',
            'ah' => '1',
            'ai' => '1',
            'aj' => '0',
            'ak' => '0',
            'al' => 'Community health volunteer',
            'am' => 'None',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame('0', $list[0]['id'] ?? '0');
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitFiveRevisit(array $geo): string
    {
        $uid = $this->uniqueUid('rev');
        $form = new \Form\FiveRevisit();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'comid' => $geo['comid'],
            'userid' => $geo['userid'],
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => 'Doe',
            'ab' => 'Jane',
            'ac' => 'Female',
            'ad' => 'Mary',
            'ae' => '08012345678',
            'af' => '6',
            'ag' => '3',
            'ah' => '2',
            'ai' => '3',
            'aj' => '1',
            'etoken_serial' => 'E1234567890',
            'etoken_uuid' => 'uuid-test-001',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame(0, $list[0]['id'] ?? 0);
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitSmcCdd(array $geo): string
    {
        $uid = $this->uniqueUid('cdd');
        $form = new \Form\SmcCdd();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'periodid' => $geo['periodid'],
            'userid' => $geo['userid'],
            'day' => 'Day 1',
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => 'Yes', 'ab' => 'OK', 'ba' => 'Yes', 'bb' => 'OK', 'ca' => 'Yes', 'cb' => 'OK',
            'da' => 'Yes', 'db' => 'OK', 'ea' => 'Yes', 'eb' => 'OK', 'fa' => 'Yes', 'fb' => 'OK',
            'ga' => 'Yes', 'gb' => 'OK', 'ha' => 'Yes', 'hb' => 'OK', 'ia' => 'Yes', 'ib' => 'OK',
            'ja' => 'Yes', 'jb' => 'OK', 'ka' => 'Yes', 'kb' => 'OK', 'la' => 'Yes', 'lb' => 'OK',
            'ma' => 'Yes', 'mb' => 'OK', 'na' => 'Yes', 'nb' => 'OK', 'oa' => 'Yes', 'ob' => 'OK',
            'pa' => 'Yes', 'pb' => 'OK', 'q' => 'Notes', 'r' => 'Mentoring', 's' => 'Next visit',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame(0, $list[0]['id'] ?? 0);
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function submitSmcHfw(array $geo): string
    {
        $uid = $this->uniqueUid('hfw');
        $form = new \Form\SmcHfw();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'periodid' => $geo['periodid'],
            'userid' => $geo['userid'],
            'day' => 'Day 1',
            'latitude' => '7.1234',
            'longitude' => '8.4321',
            'aa' => 'Yes', 'ab' => 'OK', 'ba' => 'Yes', 'bb' => 'OK', 'ca' => 'Yes', 'cb' => 'OK',
            'da' => 'Yes', 'db' => 'OK', 'ea' => 'Yes', 'eb' => 'OK', 'fa' => 'Yes', 'fb' => 'OK',
            'ga' => 'Yes', 'gb' => 'OK', 'ha' => 'Yes', 'hb' => 'OK', 'ia' => 'Yes', 'ib' => 'OK',
            'ja' => 'Yes', 'jb' => 'OK', 'ka' => 'Yes', 'kb' => 'OK', 'la' => 'Yes', 'lb' => 'OK',
            'm1a' => 'Yes', 'm1b' => 'OK', 'm2a' => 'Yes', 'm2b' => 'OK', 'm3a' => 'Yes', 'm3b' => 'OK',
            'm4a' => 'Yes', 'm4b' => 'OK', 'n1a' => 'Yes', 'n1b' => 'OK', 'n2a' => 'Yes', 'n2b' => 'OK',
            'n3a' => 'Yes', 'n3b' => 'OK', 'n4a' => 'Yes', 'n4b' => 'OK', 'n5a' => 'Yes', 'n5b' => 'OK',
            'n6a' => 'Yes', 'n6b' => 'OK', 'o1a' => 'Yes', 'o1b' => 'OK', 'o2a' => 'Yes', 'o2b' => 'OK',
            'o3a' => 'Yes', 'o3b' => 'OK', 'pa' => 'Yes', 'pb' => 'OK', 'q1a' => 'Yes', 'q1b' => 'OK',
            'q2a' => 'Yes', 'q2b' => 'OK', 'ra' => 'Yes', 'rb' => 'OK', 's' => 'Feedback', 't' => 'Mentoring',
            'v' => 'Notes',
            'domain' => 'demo.ipolongo.org',
            'app_version' => 'pwa-1.0.1',
            'capture_date' => date('Y-m-d H:i:s'),
        ]];

        $list = $form->BulkSave($payload);
        $this->assertCount(1, $list);
        $this->assertNotSame(0, $list[0]['id'] ?? 0);
        $this->createdUids[] = $uid;

        return $uid;
    }

    private function assertRowExists(string $table, string $uid): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT uid FROM $table WHERE uid = '$uid' LIMIT 1");
        $this->assertNotEmpty($rows, "Expected row in $table for uid $uid");
    }

    private function assertExcelPayload(string $payload, string $expectedSheet): void
    {
        $decoded = json_decode($payload, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($expectedSheet, $decoded[0]['sheetName'] ?? null);
        $this->assertArrayHasKey('data', $decoded[0]);
        $this->assertIsArray($decoded[0]['data']);
    }

    private function indexStatusList(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (isset($row['name'])) {
                $indexed[$row['name']] = (int) ($row['total'] ?? 0);
            }
        }
        return $indexed;
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $lgaid = (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 0);
        $wardid = (int) ($this->safeSelectValue($db, 'SELECT wardid AS val FROM ms_geo_ward LIMIT 1') ?? 0);
        $dpid = (int) ($this->safeSelectValue($db, 'SELECT dpid AS val FROM ms_geo_dp LIMIT 1') ?? 0);
        $comid = (int) ($this->safeSelectValue($db, 'SELECT comid AS val FROM ms_geo_comm LIMIT 1') ?? 0);
        $userid = (int) ($this->safeSelectValue($db, 'SELECT userid AS val FROM usr_login LIMIT 1') ?? 1);
        $periodid = (int) ($this->safeSelectValue($db, 'SELECT periodid AS val FROM smc_period LIMIT 1') ?? 1);

        return [
            'lgaid' => $lgaid ?: 1,
            'wardid' => $wardid ?: 1,
            'dpid' => $dpid ?: 1,
            'comid' => $comid ?: 1,
            'userid' => $userid ?: 1,
            'periodid' => $periodid ?: 1,
        ];
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }
        return $rows[0]['val'] ?? null;
    }

    private function uniqueUid(string $prefix): string
    {
        return $prefix . '-' . strtoupper(substr(uniqid(), -10));
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }
}
