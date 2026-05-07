<?php

namespace Tests\Unit\Controllers\Form;

use Form\SmcHfw;

/**
 * Unit Test: SMC HFW Form Controller
 * 
 * Tests the SMC Health Facility Worker form controller methods in isolation
 */
class SmcHfwControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new SmcHfw();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'lgaid', 'wardid', 'dpid', 'periodid', 'userid', 'day', 'latitude', 'longitude',
            'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb',
            'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb',
            'm1a', 'm1b', 'm2a', 'm2b', 'm3a', 'm3b', 'm4a', 'm4b',
            'n1a', 'n1b', 'n2a', 'n2b', 'n3a', 'n3b', 'n4a', 'n4b',
            'n5a', 'n5b', 'n6a', 'n6b', 'o1a', 'o1b', 'o2a', 'o2b',
            'o3a', 'o3b', 'pa', 'pb', 'q1a', 'q1b', 'q2a', 'q2b',
            'ra', 'rb', 's', 't', 'v', 'domain', 'app_version', 'capture_date'];
        $this->requireFormSchema('mo_smc_supervisor_hfw', $columns);

        $controller = new SmcHfw();
        $geo = $this->seedGeoHierarchy('HFW');
        $uid = 'HFW-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'periodid' => 1,
            'userid' => 7,
            'day' => 2,
            'latitude' => '1.6',
            'longitude' => '2.7',
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
            'm1a' => '25',
            'm1b' => '26',
            'm2a' => '27',
            'm2b' => '28',
            'm3a' => '29',
            'm3b' => '30',
            'm4a' => '31',
            'm4b' => '32',
            'n1a' => '33',
            'n1b' => '34',
            'n2a' => '35',
            'n2b' => '36',
            'n3a' => '37',
            'n3b' => '38',
            'n4a' => '39',
            'n4b' => '40',
            'n5a' => '41',
            'n5b' => '42',
            'n6a' => '43',
            'n6b' => '44',
            'o1a' => '45',
            'o1b' => '46',
            'o2a' => '47',
            'o2b' => '48',
            'o3a' => '49',
            'o3b' => '50',
            'pa' => '51',
            'pb' => '52',
            'q1a' => '53',
            'q1b' => '54',
            'q2a' => '55',
            'q2b' => '56',
            'ra' => '57',
            'rb' => '58',
            's' => '59',
            't' => '60',
            'v' => '61',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-07-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame(0, (int) $result[0]['id']);

        $this->recordCleanup('mo_smc_supervisor_hfw', 'uid', $uid);
        $this->recordCleanup('mo_smc_supervisor_hfw', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT q1a, v FROM mo_smc_supervisor_hfw WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('53', (string) $rows[0]['q1a']);
        $this->assertSame('61', (string) $rows[0]['v']);
    }
}
