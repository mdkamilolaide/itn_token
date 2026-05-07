<?php

namespace Tests\Unit\Controllers\Form;

use Form\INineB;

/**
 * Unit Test: ININE-B Form Controller
 * 
 * Tests the ININE-B form controller methods in isolation
 */
class IninebControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new INineB();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'wardid', 'lgaid', 'dpid', 'comid', 'userid', 'latitude', 'longitude', 'supervisor',
            'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb',
            'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb', 'ma', 'mb', 'na', 'nb', 'oa', 'ob',
            'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_i9b', $columns);

        $controller = new INineB();
        $geo = $this->seedGeoHierarchy('I9B');
        $uid = 'I9B-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'dpid' => $geo['dpid'],
            'comid' => $geo['comid'],
            'userid' => 4,
            'latitude' => '1.3',
            'longitude' => '2.4',
            'sp' => 'Supervisor',
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
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-04-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame('0', (string) $result[0]['id']);

        $this->recordCleanup('mo_form_i9b', 'uid', $uid);
        $this->recordCleanup('mo_form_i9b', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT supervisor, oa FROM mo_form_i9b WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('Supervisor', $rows[0]['supervisor']);
        $this->assertSame('29', (string) $rows[0]['oa']);
    }
}
