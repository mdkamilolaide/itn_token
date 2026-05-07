<?php

namespace Tests\Unit\Controllers\Mobilization;

use Mobilization\Mobilization;

/**
 * Unit Test: Mobilization Controller
 * 
 * Tests the mobilization controller methods in isolation
 */
class MobilizationControllerTest extends MobilizationTestCase
{
    public function testBulkMobilizationPersistsAndUpdatesTokenNetcard(): void
    {
        $this->requireBulkSchema();

        $controller = new Mobilization();
        $geo = $this->seedGeoHierarchy('Bulk');

        $tokenId = $this->seedToken([
            'uuid' => md5(uniqid('', true)),
            'status' => 'new',
            'status_code' => 0,
        ]);

        $uuid = md5(uniqid('', true));
        $this->seedNetcard([
            'uuid' => $uuid,
            'active' => 1,
            'location_value' => 40,
            'status' => 'issued',
        ]);

        $payload = [[
            'dp_id' => $geo['dpid'],
            'comid' => $geo['comid'],
            'hm_id' => 1,
            'co_hm_id' => 0,
            'hoh_first' => 'First',
            'hoh_last' => 'Last',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'M',
            'family_size' => 4,
            'hod_mother' => 'Yes',
            'sleeping_space' => 1,
            'adult_female' => 1,
            'adult_male' => 1,
            'children' => 1,
            'allocated_net' => 1,
            'location_description' => 'Location',
            'longitude' => '1.1',
            'latitude' => '2.2',
            'netcards' => $uuid,
            'etoken_id' => $tokenId,
            'etoken_serial' => 'ET-' . uniqid(),
            'etoken_pin' => '1234',
            'collected_date' => '2099-01-01',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'eolin_have_old_net' => 1,
            'eolin_total_old_net' => 2,
        ]];

        $count = $controller->BulkMobilization($payload);
        $this->assertSame(1, $count);

        $rows = $this->getDb()->DataTable("SELECT etoken_serial, eolin_have_old_net FROM hhm_mobilization WHERE etoken_serial = '{$payload[0]['etoken_serial']}'");
        $this->assertNotEmpty($rows);

        $tokenRows = $this->getDb()->DataTable("SELECT status, status_code FROM nc_token WHERE tokenid = {$tokenId}");
        $this->assertSame('used', $tokenRows[0]['status']);
        $this->assertSame('5', (string) $tokenRows[0]['status_code']);

        $netRows = $this->getDb()->DataTable("SELECT location_value FROM nc_netcard WHERE uuid = '{$uuid}'");
        $this->assertSame('20', (string) $netRows[0]['location_value']);
    }

    public function testBulkMobilizationReturnsOneWhenAllDuplicates(): void
    {
        $this->requireBulkSchema();

        $controller = new Mobilization();
        $geo = $this->seedGeoHierarchy('Dup');

        $tokenId = $this->seedToken([
            'uuid' => md5(uniqid('', true)),
            'status' => 'used',
            'status_code' => 5,
        ]);

        $serial = 'ET-' . uniqid();
        $this->seedMobilization([
            'hhid' => random_int(10000, 19999),
            'dp_id' => $geo['dpid'],
            'comid' => $geo['comid'],
            'hhm_id' => 1,
            'co_hhm_id' => 0,
            'hoh_first' => 'First',
            'hoh_last' => 'Last',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'M',
            'family_size' => 4,
            'allocated_net' => 1,
            'location_description' => 'Location',
            'longitude' => '1.1',
            'Latitude' => '2.2',
            'netcards' => '',
            'etoken_id' => $tokenId,
            'etoken_serial' => $serial,
            'etoken_pin' => '1234',
            'collected_date' => '2099-01-01',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $payload = [[
            'dp_id' => $geo['dpid'],
            'comid' => $geo['comid'],
            'hm_id' => 1,
            'co_hm_id' => 0,
            'hoh_first' => 'First',
            'hoh_last' => 'Last',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'M',
            'family_size' => 4,
            'allocated_net' => 1,
            'location_description' => 'Location',
            'longitude' => '1.1',
            'latitude' => '2.2',
            'netcards' => '',
            'etoken_id' => $tokenId,
            'etoken_serial' => $serial,
            'etoken_pin' => '1234',
            'collected_date' => '2099-01-01',
        ]];

        $count = $controller->BulkMobilization($payload);
        $this->assertSame(1, $count);
    }


    public function testConfirmDownloadCoversAllBranches(): void
    {
        $this->requireConfirmSchema();

        $controller = new Mobilization();
        $userId = random_int(3000, 3999);
        $device = 'DEV-2';

        $ncid = $this->seedNetcard([
            'uuid' => md5(uniqid('', true)),
            'active' => 1,
            'location_value' => 35,
        ]);

        $downloadId = md5(uniqid('', true));
        $this->insertRow('nc_netcard_download', [
            'download_id' => $downloadId,
            'device_id' => $device,
            'userid' => $userId,
            'total' => 1,
            'netcard_list' => json_encode([$ncid]),
            'status' => 'pending',
            'is_confirmed' => 0,
            'is_destroyed' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_download', 'download_id', $downloadId);

        $success = $controller->ConfirmDownload($userId, $device, $downloadId);
        $this->assertSame('success', $success['status']);

        $netRow = $this->getDb()->DataTable("SELECT location_value FROM nc_netcard WHERE ncid = {$ncid}");
        $this->assertSame('30', (string) $netRow[0]['location_value']);

        $confirmed = $controller->ConfirmDownload($userId, $device, $downloadId);
        $this->assertSame('success', $confirmed['status']);
        $this->assertSame('Download already confirmed', $confirmed['message']);

        $destroyedId = md5(uniqid('', true));
        $this->insertRow('nc_netcard_download', [
            'download_id' => $destroyedId,
            'device_id' => $device,
            'userid' => $userId,
            'total' => 0,
            'netcard_list' => '[]',
            'status' => 'destroyed',
            'is_confirmed' => 1,
            'is_destroyed' => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_download', 'download_id', $destroyedId);

        $destroyed = $controller->ConfirmDownload($userId, $device, $destroyedId);
        $this->assertSame('destroyed', $destroyed['status']);

        $missing = $controller->ConfirmDownload($userId, $device, 'missing');
        $this->assertSame('error', $missing['status']);
        $this->assertSame('Download not found', $missing['message']);
    }

    public function testGetPendingReverseOrderReturnsPendingRows(): void
    {
        if (!$this->tableHasColumns('nc_netcard_allocation_order', ['orderid', 'total_order', 'status', 'hhm_id', 'device_serial', 'created'])) {
            $this->markTestSkipped('Allocation order schema not available');
        }

        $controller = new Mobilization();
        $orderId = $this->insertRow('nc_netcard_allocation_order', [
            'total_order' => 2,
            'status' => 'pending',
            'hhm_id' => 99,
            'device_serial' => 'DEV-3',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_allocation_order', 'orderid', $orderId);

        $rows = $controller->GetPendingReverseOrder(99, 'DEV-3');
        $this->assertNotEmpty($rows);
    }


    public function testGetReceiptHeaderReturnsDefaults(): void
    {
        if (!$this->tableHasColumns('sys_default_settings', ['id', 'logo', 'receipt_header'])) {
            $this->markTestSkipped('Default settings schema not available');
        }

        $controller = new Mobilization();

        $existing = $this->getDb()->DataTable('SELECT id FROM sys_default_settings WHERE id = 1');
        if (empty($existing)) {
            $this->seedDefaultSettings([
                'id' => 1,
                'logo' => 'logo.png',
                'receipt_header' => 'Header',
            ]);
        }

        $rows = $controller->GetReceiptHeader();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('receipt_header', $rows[0]);
    }




    private function requireBulkSchema(): void
    {
        $mobColumns = ['dp_id', 'comid', 'hhm_id', 'co_hhm_id', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'longitude', 'Latitude', 'netcards', 'etoken_id', 'etoken_serial', 'etoken_pin', 'collected_date', 'hod_mother', 'sleeping_space', 'adult_female', 'adult_male', 'children', 'device_serial', 'app_version', 'eolin_have_old_net', 'eolin_total_old_net'];
        $tokenColumns = ['tokenid', 'uuid', 'status', 'status_code', 'updated'];
        $netcardColumns = ['ncid', 'uuid', 'active', 'location_value', 'status', 'location', 'utid', 'beneficiaryid', 'updated'];

        if (!$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('nc_token', $tokenColumns)
            || !$this->tableHasColumns('nc_netcard', $netcardColumns)
        ) {
            $this->markTestSkipped('Bulk mobilization schema not available');
        }
    }

    private function requireDownloadSchema(): void
    {
        $netcardColumns = ['ncid', 'uuid', 'active', 'location_value', 'mobilizer_userid', 'device_serial'];
        $downloadColumns = ['download_id', 'device_id', 'userid', 'total', 'netcard_list', 'created', 'updated'];
        $userColumns = ['userid', 'geo_level', 'geo_level_id'];
        $geoColumns = ['geo_level', 'geo_level_id', 'net_capping'];

        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)
            || !$this->tableHasColumns('nc_netcard_download', $downloadColumns)
            || !$this->tableHasColumns('usr_login', $userColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
        ) {
            $this->markTestSkipped('Download schema not available');
        }
    }

    private function requireConfirmSchema(): void
    {
        $netcardColumns = ['ncid', 'location_value', 'device_serial'];
        $downloadColumns = ['download_id', 'device_id', 'userid', 'total', 'netcard_list', 'status', 'is_confirmed', 'is_destroyed', 'updated'];

        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)
            || !$this->tableHasColumns('nc_netcard_download', $downloadColumns)
        ) {
            $this->markTestSkipped('Confirm download schema not available');
        }
    }

    private function requireDetailsSchema(): void
    {
        $mobColumns = ['hhid', 'dp_id', 'hhm_id', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'longitude', 'Latitude', 'netcards', 'etoken_id', 'etoken_serial', 'etoken_pin', 'collected_date', 'created'];
        $geoColumns = ['dpid', 'title', 'geo_string'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('Mobilization details schema not available');
        }
    }

    private function requireExcelSchema(): void
    {
        $mobColumns = ['hhid', 'dp_id', 'hhm_id', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'longitude', 'Latitude', 'netcards', 'etoken_serial', 'collected_date'];
        $geoColumns = ['dpid', 'geo_string', 'geo_level', 'geo_level_id'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last', 'phone'];

        if (!$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('Excel schema not available');
        }
    }

    private function requireDashSchema(): void
    {
        $netcardColumns = ['ncid', 'active', 'location_value', 'stateid', 'lgaid', 'wardid'];
        $mobColumns = ['hhid', 'dp_id', 'family_size', 'allocated_net', 'collected_date'];
        $geoColumns = ['dpid', 'geo_level', 'stateid', 'lgaid', 'wardid'];

        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)
            || !$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
        ) {
            $this->markTestSkipped('Dash summary schema not available');
        }
    }

    private function requireMicroSchema(): void
    {
        $mobColumns = ['hhid', 'dp_id', 'family_size', 'allocated_net'];
        $dpColumns = ['dpid', 'dp', 'wardid'];
        $wardColumns = ['wardid', 'ward', 'lgaid'];
        $lgaColumns = ['LgaId', 'Fullname'];

        if (!$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('ms_geo_dp', $dpColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
        ) {
            $this->markTestSkipped('Micro-positioning schema not available');
        }
    }
}
