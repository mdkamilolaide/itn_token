<?php

namespace Tests\Integration\Distribution;

use Distribution\GsVerification;

class DistributionVerificationTest extends DistributionTestCase
{
    public function testVerificationCreatesLogsAndUpdatesSerials(): void
    {
        $this->requireVerificationSchema();

        $geo = $this->seedGeoHierarchy('Verify');
        $token = $this->seedToken([
            'uuid' => md5(uniqid('', true)),
            'serial_no' => 'TOK' . random_int(1000, 9999),
        ]);

        $hhid = 'HH-' . uniqid();
        $etokenSerial = 'ET-' . uniqid();
        $this->seedMobilization([
            'hhid' => $hhid,
            'dp_id' => $geo['dpid'],
            'hoh_first' => 'Jane',
            'hoh_last' => 'Doe',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'allocated_net' => 2,
            'location_description' => 'Test Location',
            'netcards' => 2,
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $etokenSerial,
            'etoken_pin' => '1234',
            'collected_date' => '2099-08-01',
            'longitude' => '0',
            'latitude' => '0',
        ]);

        $disId = $this->seedDistribution([
            'dp_id' => $geo['dpid'],
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $etokenSerial,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 1,
            'gs_net_serial' => '[]',
            'longitude' => '0',
            'latitude' => '0',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => '2099-08-01',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $validSgtin = 'SGTIN-VALID-' . uniqid();
        $this->insertRow('ms_product_sgtin', [
            'sgtin' => $validSgtin,
            'gtin' => 'GTIN-VALID',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $snidSuccess = $this->seedGsNetSerial([
            'dis_id' => $disId,
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'net_serial' => 'NETDATA1',
            'gtin' => 'GTIN-VALID',
            'sgtin' => $validSgtin,
            'batch' => 'B1',
            'expiry' => '2030-01-01',
            'is_verified' => 0,
        ]);
        $snidFailed = $this->seedGsNetSerial([
            'dis_id' => $disId,
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'net_serial' => 'NETDATA2',
            'gtin' => 'GTIN-FAIL',
            'sgtin' => 'SGTIN-NOTFOUND',
            'batch' => 'B2',
            'expiry' => '2030-01-02',
            'is_verified' => 0,
        ]);
        $snidEmpty = $this->seedGsNetSerial([
            'dis_id' => $disId,
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'net_serial' => 'NETDATA3',
            'gtin' => 'GTIN-EMPTY',
            'sgtin' => '',
            'batch' => 'B3',
            'expiry' => '2030-01-03',
            'is_verified' => 0,
        ]);

        $verifier = new GsVerification(10);
        ob_start();
        $verifier->RunVerification();
        ob_end_clean();

        $rows = $this->getDb()->DataTable("SELECT snid, status FROM hhm_gs_net_verification WHERE snid IN ({$snidSuccess}, {$snidFailed}, {$snidEmpty})");
        $this->assertCount(3, $rows);

        $serials = $this->getDb()->DataTable("SELECT is_verified FROM hhm_gs_net_serial WHERE snid IN ({$snidSuccess}, {$snidFailed}, {$snidEmpty})");
        $this->assertCount(3, $serials);
        foreach ($serials as $serial) {
            $this->assertSame(1, (int) $serial['is_verified']);
        }

        $log = $this->getDb()->DataTable('SELECT id FROM hhm_gs_net_verification_log ORDER BY id DESC LIMIT 1');
        $this->assertNotEmpty($log);
        $this->recordCleanup('hhm_gs_net_verification_log', 'id', $log[0]['id']);

        $this->recordCleanup('hhm_gs_net_verification', 'snid', $snidSuccess);
        $this->recordCleanup('hhm_gs_net_verification', 'snid', $snidFailed);
        $this->recordCleanup('hhm_gs_net_verification', 'snid', $snidEmpty);
    }

    public function testTraceabilitySearchReturnsManufacturerAndLogistics(): void
    {
        $this->requireTraceabilitySchema();

        $geo = $this->seedGeoHierarchy('Trace');
        $token = $this->seedToken([
            'uuid' => md5(uniqid('', true)),
            'serial_no' => 'TOK' . random_int(1000, 9999),
        ]);

        $itemId = $this->insertRow('ms_product_item', [
            'brand_name' => 'Brand',
            'product_description' => 'Desc',
            'manufacturer_name' => 'Maker',
            'gtin' => 'GTIN-TRACE',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($itemId) {
            $this->recordCleanup('ms_product_item', 'itemid', $itemId);
        }

        $sgtinId = $this->insertRow('ms_product_sgtin', [
            'sgtin' => 'SGTIN-TRACE',
            'gtin' => 'GTIN-TRACE',
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($sgtinId) {
            $this->recordCleanup('ms_product_sgtin', 'sgtinid', $sgtinId);
        }

        $hhid = 'HH-' . uniqid();
        $etokenSerial = 'ET-' . uniqid();
        $this->seedMobilization([
            'hhid' => $hhid,
            'dp_id' => $geo['dpid'],
            'hoh_first' => 'Jane',
            'hoh_last' => 'Doe',
            'hoh_phone' => '08000000000',
            'hoh_gender' => 'female',
            'family_size' => 4,
            'allocated_net' => 2,
            'location_description' => 'Test Location',
            'netcards' => 2,
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $etokenSerial,
            'etoken_pin' => '1234',
            'collected_date' => '2099-08-01',
            'longitude' => '0',
            'latitude' => '0',
        ]);

        $disId = $this->seedDistribution([
            'dp_id' => $geo['dpid'],
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'etoken_serial' => $etokenSerial,
            'recorder_id' => 1,
            'distributor_id' => 1,
            'collected_nets' => 1,
            'is_gs_net' => 1,
            'gs_net_serial' => '[]',
            'longitude' => '0',
            'latitude' => '0',
            'device_serial' => 'DEV',
            'app_version' => '1.0',
            'eolin_bring_old_net' => 0,
            'eolin_total_old_net' => 0,
            'collected_date' => '2099-08-01',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $snid = $this->seedGsNetSerial([
            'dis_id' => $disId,
            'hhid' => $hhid,
            'etoken_id' => $token['tokenid'],
            'net_serial' => 'NETDATA1',
            'gtin' => 'GTIN-TRACE',
            'sgtin' => 'SGTIN-TRACE',
            'batch' => 'B1',
            'expiry' => '2030-01-01',
            'is_verified' => 1,
        ]);
        $this->recordCleanup('hhm_gs_net_serial', 'snid', $snid);

        $verifier = new GsVerification();
        $result = $verifier->TraceabilitySearch('GTIN-TRACE', 'SGTIN-TRACE');

        $this->assertNotEmpty($result['manufacturer']);
        $this->assertNotEmpty($result['logistic']);
    }

    private function requireVerificationSchema(): void
    {
        $gsSerialColumns = ['snid', 'gtin', 'sgtin', 'is_verified'];
        $gsVerifyColumns = ['snid', 'sgtin', 'status', 'note', 'created'];
        $gsLogColumns = ['id', 'total_verification', 'description', 'created'];
        $sgtinColumns = ['sgtinid', 'sgtin'];

        if (!$this->tableHasColumns('hhm_gs_net_serial', $gsSerialColumns)
            || !$this->tableHasColumns('hhm_gs_net_verification', $gsVerifyColumns)
            || !$this->tableHasColumns('hhm_gs_net_verification_log', $gsLogColumns)
            || !$this->tableHasColumns('ms_product_sgtin', $sgtinColumns)
        ) {
            $this->markTestSkipped('GS verification schema not available');
        }
    }

    private function requireTraceabilitySchema(): void
    {
        $productColumns = ['itemid', 'brand_name', 'product_description', 'manufacturer_name', 'gtin'];
        $sgtinColumns = ['sgtinid', 'sgtin', 'gtin'];
        $gsSerialColumns = ['snid', 'dis_id', 'gtin', 'sgtin'];
        $mobColumns = ['hhid', 'dp_id', 'etoken_id', 'etoken_serial', 'family_size'];
        $distColumns = ['dis_id', 'dp_id', 'hhid', 'etoken_id', 'etoken_serial', 'collected_nets'];
        $geoColumns = ['dpid', 'geo_level'];

        if (!$this->tableHasColumns('ms_product_item', $productColumns)
            || !$this->tableHasColumns('ms_product_sgtin', $sgtinColumns)
            || !$this->tableHasColumns('hhm_gs_net_serial', $gsSerialColumns)
            || !$this->tableHasColumns('hhm_mobilization', $mobColumns)
            || !$this->tableHasColumns('hhm_distribution', $distColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
        ) {
            $this->markTestSkipped('Traceability schema not available');
        }
    }
}
