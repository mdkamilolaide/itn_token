<?php

namespace Tests\Unit\Controllers\Form;

use Form\EndProcess;

/**
 * Unit Test: End Process Form Controller
 * 
 * Tests the end process form controller methods in isolation
 */
class EndProcessControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new EndProcess();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'wardid', 'lgaid', 'comid', 'userid', 'latitude', 'longitude', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'aj', 'ak', 'al', 'am', 'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_end_process', $columns);

        $controller = new EndProcess();
        $geo = $this->seedGeoHierarchy('End');
        $uid = 'END-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => 1,
            'latitude' => '1.0',
            'longitude' => '2.0',
            'aa' => 'yes',
            'ab' => 'no',
            'ac' => '1',
            'ad' => '2',
            'ae' => '3',
            'af' => '4',
            'ag' => '5',
            'ah' => '6',
            'ai' => '7',
            'aj' => '8',
            'ak' => '9',
            'al' => '10',
            'am' => '11',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame('0', (string) $result[0]['id']);

        $this->recordCleanup('mo_form_end_process', 'uid', $uid);
        $this->recordCleanup('mo_form_end_process', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT aa, domain FROM mo_form_end_process WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('yes', $rows[0]['aa']);
        $this->assertSame('test', $rows[0]['domain']);
    }
}
