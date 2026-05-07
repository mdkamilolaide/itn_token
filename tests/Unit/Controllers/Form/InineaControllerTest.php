<?php

namespace Tests\Unit\Controllers\Form;

use Form\INineA;

/**
 * Unit Test: ININE-A Form Controller
 * 
 * Tests the ININE-A form controller methods in isolation
 */
class InineaControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new INineA();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'wardid', 'lgaid', 'comid', 'userid', 'latitude', 'longitude', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_i9a', $columns);

        $controller = new INineA();
        $geo = $this->seedGeoHierarchy('I9A');
        $uid = 'I9A-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => 3,
            'latitude' => '1.2',
            'longitude' => '2.3',
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'ai' => '9',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-03-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame('0', (string) $result[0]['id']);

        $this->recordCleanup('mo_form_i9a', 'uid', $uid);
        $this->recordCleanup('mo_form_i9a', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT aa, ai FROM mo_form_i9a WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('1', (string) $rows[0]['aa']);
        $this->assertSame('9', (string) $rows[0]['ai']);
    }
}
