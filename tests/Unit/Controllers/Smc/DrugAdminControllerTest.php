<?php

namespace Tests\Unit\Controllers\Smc;

use Smc\DrugAdmin;

require_once __DIR__ . '/SmcTestCase.php';

/**
 * Unit Test: SMC Drug Administration Controller
 * 
 * Tests the SMC drug administration controller methods in isolation
 */
class DrugAdminControllerTest extends SmcTestCase
{
    public function testBulkSaveAndRedose(): void
    {
        $this->requireSchema([
            'smc_drug_administration' => ['uid', 'periodid', 'dpid', 'beneficiary_id', 'is_eligible', 'not_eligible_reason', 'is_refer', 'issue_id', 'redose_issue_id', 'drug', 'drug_qty', 'redose_count', 'redose_reason', 'user_id', 'longitude', 'latitude', 'device_serial', 'app_version', 'collected_date', 'updated'],
        ]);

        $controller = new DrugAdmin();

        $payload = [[
            'periodid' => 1,
            'uid' => 'UID-' . uniqid(),
            'dpid' => 1001,
            'beneficiary_id' => 'BEN-' . uniqid(),
            'is_eligible' => 1,
            'not_eligible_reason' => '',
            'is_refer' => 0,
            'drug' => 'SPAQ 1',
            'drug_qty' => 1,
            'redose_count' => 0,
            'redose_reason' => '',
            'user_id' => 55,
            'longitude' => '1.1',
            'latitude' => '2.2',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'collected_date' => date('Y-m-d'),
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertSame([$payload[0]['beneficiary_id']], $result);

        $rows = $this->getDb()->DataTable("SELECT uid, redose_count FROM smc_drug_administration WHERE uid = '{$payload[0]['uid']}'");
        $this->assertNotEmpty($rows);

        $redose = [[
            'uid' => $payload[0]['uid'],
            'redose_count' => 1,
            'redose_reason' => 'spit',
            'redose_issue_id' => 0,
        ]];
        $redoseResult = $controller->BulkRedose($redose);
        $this->assertSame([$payload[0]['uid']], $redoseResult);

        $updated = $this->getDb()->DataTable("SELECT redose_count, redose_reason FROM smc_drug_administration WHERE uid = '{$payload[0]['uid']}'");
        $this->assertSame('1', (string) $updated[0]['redose_count']);
        $this->assertSame('spit', (string) $updated[0]['redose_reason']);
    }
}
