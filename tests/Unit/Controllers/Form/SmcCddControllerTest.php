<?php

namespace Tests\Unit\Controllers\Form;

use Form\SmcCdd;

/**
 * Unit Test: SMC CDD Form Controller
 * 
 * Tests the SMC Community Drug Distributor form controller methods in isolation
 */
class SmcCddControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new SmcCdd();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'lgaid', 'wardid', 'dpid', 'periodid', 'userid', 'day', 'latitude', 'longitude',
            'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb',
            'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb', 'ma', 'mb', 'na', 'nb', 'oa', 'ob',
            'pa', 'pb', 'q', 'r', 's', 'domain', 'app_version', 'capture_date'];
        $this->requireFormSchema('mo_smc_supervisor_cdd', $columns);

        $controller = new SmcCdd();
        $geo = $this->seedGeoHierarchy('CDD');
        $uid = 'CDD-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'periodid' => 1,
            'userid' => 6,
            'day' => 1,
            'latitude' => '1.5',
            'longitude' => '2.6',
            'aa' => '1',
            'ab' => '2',
            'ba' => '3',
            'bb' => '4',
            'ca' => '5',
            'cb' => '6',
            'da' => '7',
            'db' => '8',
            'ea' => '9',
            'eb' => '10',
            'fa' => '11',
            'fb' => '12',
            'ga' => '13',
            'gb' => '14',
            'ha' => '15',
            'hb' => '16',
            'ia' => '17',
            'ib' => '18',
            'ja' => '19',
            'jb' => '20',
            'ka' => '21',
            'kb' => '22',
            'la' => '23',
            'lb' => '24',
            'ma' => '25',
            'mb' => '26',
            'na' => '27',
            'nb' => '28',
            'oa' => '29',
            'ob' => '30',
            'pa' => '31',
            'pb' => '32',
            'q' => '33',
            'r' => '34',
            's' => '35',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-06-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame(0, (int) $result[0]['id']);

        $this->recordCleanup('mo_smc_supervisor_cdd', 'uid', $uid);
        $this->recordCleanup('mo_smc_supervisor_cdd', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT q, r FROM mo_smc_supervisor_cdd WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('33', (string) $rows[0]['q']);
        $this->assertSame('34', (string) $rows[0]['r']);
    }
}
