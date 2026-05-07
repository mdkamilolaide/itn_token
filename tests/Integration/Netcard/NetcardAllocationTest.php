<?php

namespace Tests\Integration\Netcard;

use Netcard\NetcardTrans;

class NetcardAllocationTest extends NetcardTestCase
{
    public function testAllocateNetcardToUser(): void
    {
        $this->requireAllocationSchema();

        $trans = new NetcardTrans();
        $geo = $this->seedGeoHierarchy('Alloc');
        $mobilizerId = random_int(2000, 2999);
        $requesterId = random_int(3000, 3999);
        $this->seedUser($mobilizerId, $geo['wardid']);
        $this->seedUser($requesterId, $geo['wardid']);

        $this->seedNetcards(2, [
            'location' => 'ward',
            'location_value' => 60,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'stateid' => $geo['stateid'],
        ]);

        $trans->WardToHHMobilizer(2, $geo['wardid'], $mobilizerId, $requesterId);

        $assigned = $this->getDb()->DataTable("SELECT ncid FROM nc_netcard WHERE mobilizer_userid = {$mobilizerId} AND location_value = 40");
        $this->assertCount(2, $assigned);

        $allocation = $this->getDb()->DataTable("SELECT total, a_type, origin, destination FROM nc_netcard_allocation WHERE destination_userid = {$mobilizerId} ORDER BY atid DESC LIMIT 1");
        $this->assertNotEmpty($allocation);
        $this->assertSame(2, (int) $allocation[0]['total']);
        $this->assertSame('forward', $allocation[0]['a_type']);
        $this->assertSame('ward', $allocation[0]['origin']);
        $this->assertSame('mobilizer', $allocation[0]['destination']);
    }

    public function testBulkNetcardAllocation(): void
    {
        $this->requireAllocationSchema();

        $trans = new NetcardTrans();
        $geo = $this->seedGeoHierarchy('Bulk');
        $mobilizerA = random_int(4000, 4999);
        $mobilizerB = random_int(5000, 5999);
        $requesterId = random_int(6000, 6999);
        $this->seedUser($mobilizerA, $geo['wardid']);
        $this->seedUser($mobilizerB, $geo['wardid']);
        $this->seedUser($requesterId, $geo['wardid']);

        $this->seedNetcards(3, [
            'location' => 'ward',
            'location_value' => 60,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
        ]);

        $result = $trans->BulkAllocationTransfer([
            ['total' => 1, 'wardid' => $geo['wardid'], 'mobilizerid' => $mobilizerA, 'userid' => $requesterId],
            ['total' => 2, 'wardid' => $geo['wardid'], 'mobilizerid' => $mobilizerB, 'userid' => $requesterId],
        ]);

        $this->assertSame(2, $result);

        $assignedA = $this->getDb()->DataTable("SELECT ncid FROM nc_netcard WHERE mobilizer_userid = {$mobilizerA} AND location_value = 40");
        $assignedB = $this->getDb()->DataTable("SELECT ncid FROM nc_netcard WHERE mobilizer_userid = {$mobilizerB} AND location_value = 40");
        $this->assertCount(1, $assignedA);
        $this->assertCount(2, $assignedB);
    }

    public function testNetcardReallocation(): void
    {
        $this->requireReverseSchema();

        $trans = new NetcardTrans();
        $geo = $this->seedGeoHierarchy('Reverse');
        $mobilizerId = random_int(7000, 7999);
        $requesterId = random_int(8000, 8999);
        $this->seedUser($mobilizerId, $geo['wardid']);
        $this->seedUser($requesterId, $geo['wardid']);

        $this->seedNetcards(2, [
            'location' => 'mobilizer',
            'location_value' => 40,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'mobilizer_userid' => $mobilizerId,
            'device_serial' => 'DEV-' . uniqid('', true),
        ]);

        $total = $trans->DirectReverseAllocation(2, $mobilizerId, $requesterId);
        $this->assertSame(2, $total);

        $returned = $this->getDb()->DataTable("SELECT ncid FROM nc_netcard WHERE mobilizer_userid IS NULL AND location_value = 60");
        $this->assertCount(2, $returned);

        $log = $this->getDb()->DataTable("SELECT amount FROM nc_netcard_allocation_online WHERE hhm_id = {$mobilizerId} AND requester_id = {$requesterId} ORDER BY id DESC LIMIT 1");
        $this->assertNotEmpty($log);
        $this->assertSame(2, (int) $log[0]['amount']);
    }

    public function testInsufficientStockAllocation(): void
    {
        $this->requireAllocationSchema();

        $trans = new NetcardTrans();
        $geo = $this->seedGeoHierarchy('Short');
        $mobilizerId = random_int(9000, 9999);
        $requesterId = random_int(10000, 10999);
        $this->seedUser($mobilizerId, $geo['wardid']);
        $this->seedUser($requesterId, $geo['wardid']);

        $this->seedNetcards(1, [
            'location' => 'ward',
            'location_value' => 60,
            'geo_level' => 'ward',
            'geo_level_id' => $geo['wardid'],
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
        ]);

        $trans->WardToHHMobilizer(3, $geo['wardid'], $mobilizerId, $requesterId);

        $assigned = $this->getDb()->DataTable("SELECT ncid FROM nc_netcard WHERE mobilizer_userid = {$mobilizerId} AND location_value = 40");
        $this->assertCount(1, $assigned);

        $allocation = $this->getDb()->DataTable("SELECT total FROM nc_netcard_allocation WHERE destination_userid = {$mobilizerId} ORDER BY atid DESC LIMIT 1");
        $this->assertNotEmpty($allocation);
        $this->assertSame(3, (int) $allocation[0]['total']);
    }
}
