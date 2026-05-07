<?php

namespace Tests\Unit\Controllers\Netcard;

use Netcard\NetcardTrans;

/**
 * Unit Test: Netcard Transaction Controller
 * 
 * Tests the netcard transaction controller methods in isolation
 */
class NetcardTransControllerTest extends NetcardTestCase
{
    public function testNetcardAllocation(): void
    {
        $allocColumns = ['atid', 'userid', 'total', 'a_type', 'origin', 'origin_id', 'destination_userid', 'created'];
        $orderColumns = ['orderid', 'hhm_id', 'requester_id', 'total_order', 'device_serial', 'status', 'created'];
        $onlineColumns = ['id', 'hhm_id', 'requester_id', 'amount', 'created'];
        if (!$this->tableHasColumns('nc_netcard_allocation', $allocColumns)
            || !$this->tableHasColumns('nc_netcard_allocation_order', $orderColumns)
            || !$this->tableHasColumns('nc_netcard_allocation_online', $onlineColumns)
            || !$this->tableHasColumns('usr_login', ['userid', 'loginid', 'geo_level', 'geo_level_id'])
            || !$this->tableHasColumns('usr_identity', ['userid', 'first', 'last'])
        ) {
            $this->markTestSkipped('Allocation schema not available');
        }

        $trans = new NetcardTrans();
        $geo = $this->seedGeoHierarchy('Alloc');

        $hhmId = random_int(2000, 2999);
        $requesterId = random_int(3000, 3999);
        $this->seedUser($hhmId, $geo['wardid']);
        $this->seedUser($requesterId, $geo['wardid']);

        $atid = $this->insertRow('nc_netcard_allocation', [
            'userid' => $requesterId,
            'total' => 1,
            'a_type' => 'forward',
            'origin' => 'ward',
            'origin_id' => $geo['wardid'],
            'destination_userid' => $hhmId,
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_allocation', 'atid', $atid);

        $orderId = $this->insertRow('nc_netcard_allocation_order', [
            'hhm_id' => $hhmId,
            'requester_id' => $requesterId,
            'total_order' => 2,
            'device_serial' => 'DEV-2',
            'status' => 'pending',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_allocation_order', 'orderid', $orderId);

        $onlineId = $this->insertRow('nc_netcard_allocation_online', [
            'hhm_id' => $hhmId,
            'requester_id' => $requesterId,
            'amount' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('nc_netcard_allocation_online', 'id', $onlineId);

        $this->assertNotEmpty($trans->GetAllocationTransferHistoryList($geo['wardid']));
        $this->assertNotEmpty($trans->GetAllocationReverseHistoryList($geo['wardid']));
        $this->assertNotEmpty($trans->GetAllocationDirectReverseList($geo['wardid']));
    }
}
