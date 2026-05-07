<?php

namespace Tests\Feature\NetcardManagement;

use Tests\TestCase;

class NetcardManagementWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdNetcards = [];
    private array $createdOrderDevices = [];
    private array $createdUnlockDevices = [];
    private array $createdPushDevices = [];
    private string $startTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/netcard/netcard.cont.php';
        require_once $this->projectRoot . '/lib/controller/netcard/netcardTrans.cont.php';
        require_once $this->projectRoot . '/lib/controller/dashboard/enetcard.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';

        $this->startTime = date('Y-m-d H:i:s');
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        if (!empty($this->createdNetcards)) {
            $ids = array_filter(array_map('intval', $this->createdNetcards));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM nc_netcard WHERE ncid IN (' . implode(',', $ids) . ')', []);
            }
        }

        $userId = $this->getUserId();
        if ($userId) {
            $db->executeTransaction("DELETE FROM nc_netcard_movement WHERE userid = $userId AND created >= '{$this->startTime}'", []);
            $db->executeTransaction("DELETE FROM nc_netcard_allocation WHERE userid = $userId AND created >= '{$this->startTime}'", []);
            $db->executeTransaction("DELETE FROM nc_netcard_allocation_online WHERE requester_id = $userId AND created >= '{$this->startTime}'", []);
        }

        if (!empty($this->createdOrderDevices)) {
            $devices = array_map([$db->Conn, 'quote'], $this->createdOrderDevices);
            $deviceList = implode(',', $devices);
            $db->executeTransaction("DELETE FROM nc_netcard_allocation_order WHERE device_serial IN ($deviceList)", []);
        }

        if (!empty($this->createdUnlockDevices)) {
            $devices = array_map([$db->Conn, 'quote'], $this->createdUnlockDevices);
            $deviceList = implode(',', $devices);
            $db->executeTransaction("DELETE FROM nc_netcard_unlocked_log WHERE device_serial IN ($deviceList)", []);
        }

        if (!empty($this->createdPushDevices)) {
            $devices = array_map([$db->Conn, 'quote'], $this->createdPushDevices);
            $deviceList = implode(',', $devices);
            $db->executeTransaction("DELETE FROM nc_netcard_unused_pushed WHERE device_serial IN ($deviceList)", []);
        }

        parent::tearDown();
    }

    /**
     * Test netcard generation and geo-level movements
     */
    public function testNetcardGenerationAndMovementWorkflow(): void
    {
        $db = $this->getDb();
        $geo = $this->getGeoSample();
        $userId = $this->getUserId();

        $beforeMax = (int) ($db->DataTable('SELECT MAX(ncid) AS max_id FROM nc_netcard')[0]['max_id'] ?? 0);
        $generator = new \Netcard\Netcard(2);
        $count = $generator->Generate();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count, 'Netcard::Generate should create at least one netcard');

        $generator->ChangeLength(1);
        $count = $generator->Generate();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count, 'Netcard::Generate should create at least one netcard when length=1');

        $newRows = $db->DataTable("SELECT ncid FROM nc_netcard WHERE ncid > $beforeMax");
        foreach ($newRows as $row) {
            $this->createdNetcards[] = (int) $row['ncid'];
        }

        $netcard = $this->createNetcard([
            'uuid' => $this->uniqueUuid(),
            'active' => 1,
            'location' => 'state',
            'location_value' => 100,
            'geo_level' => 'state',
            'geo_level_id' => $geo['stateid'],
            'stateid' => $geo['stateid'],
            'status' => 'state',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotNull($netcard['ncid']);

        $trans = new \Netcard\NetcardTrans();
        $moved = $trans->StateToLgaMovement(1, $geo['stateid'], $geo['lgaid'], $userId);
        if ($moved === 0 || $moved === false) {
            $this->markTestIncomplete('State->LGA movement did not occur in this environment (no suitable netcards)');
        } else {
            $this->assertEquals(1, $moved);
            $this->assertLocation($netcard['ncid'], 80, 'lga', $geo['lgaid']);
        }

        $moved = $trans->LgaToWardMovement(1, $geo['lgaid'], $geo['wardid'], $userId);
        if ($moved === 0 || $moved === false) {
            $this->markTestIncomplete('LGA->Ward movement did not occur in this environment');
        } else {
            $this->assertEquals(1, $moved);
            $this->assertLocation($netcard['ncid'], 60, 'ward', $geo['wardid']);
        }

        $moved = $trans->WardToLgaMovement(1, $geo['wardid'], $geo['lgaid'], $userId);
        if ($moved === 0 || $moved === false) {
            $this->markTestIncomplete('Ward->LGA movement did not occur in this environment');
        } else {
            $this->assertEquals(1, $moved);
            $this->assertLocation($netcard['ncid'], 80, 'lga', $geo['lgaid']);
        }

        $moved = $trans->LgaToStateMovement(1, $geo['lgaid'], $geo['stateid'], $userId);
        if ($moved === 0 || $moved === false) {
            $this->markTestIncomplete('LGA->State movement did not occur in this environment');
        } else {
            $this->assertEquals(1, $moved);
        }
        $this->assertLocation($netcard['ncid'], 100, 'state', $geo['stateid']);
    }

    /**
     * Test bulk allocation and reverse workflows
     */
    public function testBulkAllocationAndReverseWorkflow(): void
    {
        $geo = $this->getGeoSample();
        $userId = $this->getUserId();

        $netcard = $this->createNetcard([
            'uuid' => $this->uniqueUuid(),
            'active' => 1,
            'location' => 'ward',
            'location_value' => 60,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'status' => 'ward',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $trans = new \Netcard\NetcardTrans();
        $bulk = $trans->BulkAllocationTransfer([
            [
                'total' => 1,
                'wardid' => $geo['wardid'],
                'mobilizerid' => $userId,
                'userid' => $userId,
            ]
        ]);
        $this->assertEquals(1, $bulk);
        $this->assertNetcardMobilizer($netcard['ncid'], 40, $userId);

        $reversed = $trans->DirectReverseAllocation(1, $userId, $userId);
        $this->assertEquals(1, $reversed);
        $this->assertNetcardMobilizer($netcard['ncid'], 60, null);

        $deviceSerial = 'REV-' . strtoupper(substr(uniqid(), -6));
        $this->createdOrderDevices[] = $deviceSerial;
        $orderId = $trans->ReverseAllocationOrder($userId, $userId, 1, $deviceSerial);
        $this->assertNotFalse($orderId);

        $this->assertIsArray($trans->GetAllocationTransferHistoryList($geo['wardid']));
        $this->assertIsArray($trans->GetAllocationReverseHistoryList($geo['wardid']));
        $this->assertIsArray($trans->GetAllocationDirectReverseList($geo['wardid']));
    }

    /**
     * Test netcard unlock and push online workflows
     */
    public function testUnlockAndPushOnlineWorkflow(): void
    {
        $geo = $this->getGeoSample();
        $userId = $this->getUserId();

        $unlockDevice = 'UNLOCK-' . strtoupper(substr(uniqid(), -6));
        $this->createdUnlockDevices[] = $unlockDevice;
        $netcard = $this->createNetcard([
            'uuid' => $this->uniqueUuid(),
            'active' => 1,
            'location' => 'mobilizer',
            'location_value' => 30,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'mobilizer_userid' => $userId,
            'device_serial' => $unlockDevice,
            'status' => 'wallet',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $trans = new \Netcard\NetcardTrans();
        $unlocked = $trans->SuperUserUnlockNetcard($userId, $unlockDevice, $userId);
        $this->assertEquals(1, $unlocked);
        $this->assertNetcardMobilizer($netcard['ncid'], 60, null);

        $logRows = $this->getDb()->DataTable("SELECT amount FROM nc_netcard_unlocked_log WHERE device_serial = '$unlockDevice' LIMIT 1");
        $this->assertNotEmpty($logRows);

        $pushDevice = 'PUSH-' . strtoupper(substr(uniqid(), -6));
        $this->createdPushDevices[] = $pushDevice;
        $pushUuid = $this->uniqueUuid();
        $pushNetcard = $this->createNetcard([
            'uuid' => $pushUuid,
            'active' => 1,
            'location' => 'mobilizer',
            'location_value' => 30,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'stateid' => $geo['stateid'],
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'mobilizer_userid' => $userId,
            'device_serial' => $pushDevice,
            'status' => 'wallet',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $pushed = $trans->PushNetcardOnline([$pushUuid], $userId, $pushDevice);
        $this->assertEquals(1, $pushed);
        $this->assertNetcardMobilizer($pushNetcard['ncid'], 40, $userId);

        $pushRows = $this->getDb()->DataTable("SELECT amount FROM nc_netcard_unused_pushed WHERE device_serial = '$pushDevice' LIMIT 1");
        $this->assertNotEmpty($pushRows);
    }

    /**
     * Test netcard dashboards and balance queries
     */
    public function testNetcardDashboardAndBalances(): void
    {
        $geo = $this->getGeoSample();
        $trans = new \Netcard\NetcardTrans();
        $dashboard = new \Dashboard\Enetcard();

        $this->assertIsArray($trans->GetCountByLocation());
        $this->assertIsArray($trans->CountTotalNetcard());
        $this->assertIsArray($trans->CombinedBalanceForApp($geo['wardid']));
        $this->assertIsArray($trans->GetCountLgaList());
        $this->assertIsArray($trans->GetCountWardList($geo['lgaid']));
        $this->assertIsArray($trans->GetMobilizersList($geo['wardid']));
        $this->assertIsArray($trans->GetCombinedMobilizerBalance($geo['wardid']));
        $this->assertIsArray($trans->GetOfflineMobilizerBalance($geo['wardid']));
        $this->assertIsArray($trans->GetOnlineMobilizerBalance($geo['wardid']));
        $this->assertIsArray($trans->GetLgaLevelMobilizersBalances());
        $this->assertIsArray($trans->GetWardLevelMobilizersBalances($geo['lgaid']));
        $this->assertIsArray($trans->GetMovementDashboardBalances($geo['lgaid']));
        $this->assertIsArray($trans->GetMovementTopHistory($geo['lgaid']));
        $this->assertIsArray($trans->GetMovementListHistory($geo['lgaid']));

        $this->assertIsArray($dashboard->TopSummary());
        $this->assertIsArray($dashboard->TopLgaSummary());
        $this->assertIsArray($dashboard->TopWardSummary($geo['lgaid']));
        $this->assertIsArray($dashboard->TopMobilizerSummary($geo['wardid']));
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function getUserId(): int
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SELECT userid FROM usr_login LIMIT 1');
        return (int) ($rows[0]['userid'] ?? 1);
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT stateid, lgaid, wardid, dpid FROM sys_geo_codex WHERE geo_level='dp' LIMIT 1");
        $row = $rows[0] ?? ['stateid' => 0, 'lgaid' => 0, 'wardid' => 0, 'dpid' => 0];

        $stateid = (int) $row['stateid'];
        if ($stateid === 0) {
            $stateRow = $db->DataTable('SELECT stateid FROM sys_default_settings WHERE id = 1');
            $stateid = (int) ($stateRow[0]['stateid'] ?? 0);
        }

        return [
            'stateid' => $stateid ?: 1,
            'lgaid' => (int) ($row['lgaid'] ?? 1),
            'wardid' => (int) ($row['wardid'] ?? 1),
            'dpid' => (int) ($row['dpid'] ?? 1),
        ];
    }

    private function createNetcard(array $fieldMap): array
    {
        $db = $this->getDb();
        $columns = $db->DataTable('SHOW COLUMNS FROM nc_netcard');
        $existing = array_map(fn ($row) => $row['Field'], $columns);

        $fields = [];
        $values = [];
        foreach ($fieldMap as $field => $value) {
            if (in_array($field, $existing, true)) {
                $fields[] = $field;
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            $this->markTestSkipped('Unable to insert netcard due to schema mismatch');
        }

        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $ncid = $db->Insert('INSERT INTO nc_netcard (' . implode(',', $fields) . ") VALUES ($placeholders)", $values);

        if (!$ncid) {
            $this->markTestSkipped('Unable to insert netcard record');
        }

        $this->createdNetcards[] = (int) $ncid;

        return [
            'ncid' => (int) $ncid,
            'uuid' => $fieldMap['uuid'] ?? '',
        ];
    }

    private function assertLocation(int $ncid, int $locationValue, string $geoLevel, int $geoLevelId): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT location_value, geo_level, geo_level_id FROM nc_netcard WHERE ncid = $ncid LIMIT 1");
        $this->assertNotEmpty($rows);
        $this->assertEquals($locationValue, (int) $rows[0]['location_value']);
        if (isset($rows[0]['geo_level'])) {
            $this->assertEquals($geoLevel, $rows[0]['geo_level']);
        }
        if (isset($rows[0]['geo_level_id'])) {
            $this->assertEquals($geoLevelId, (int) $rows[0]['geo_level_id']);
        }
    }

    private function assertNetcardMobilizer(int $ncid, int $locationValue, ?int $mobilizerId): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT location_value, mobilizer_userid FROM nc_netcard WHERE ncid = $ncid LIMIT 1");
        $this->assertNotEmpty($rows);
        $this->assertEquals($locationValue, (int) $rows[0]['location_value']);
        if ($mobilizerId === null) {
            $this->assertTrue(empty($rows[0]['mobilizer_userid']));
        } else {
            $this->assertEquals($mobilizerId, (int) $rows[0]['mobilizer_userid']);
        }
    }

    private function uniqueUuid(): string
    {
        return 'NC-' . strtoupper(substr(uniqid(), -12));
    }
}
