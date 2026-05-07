<?php

namespace Tests\Unit\Controllers\Form;

use Form\INineC;

/**
 * Unit Test: ININE-C Form Controller
 * 
 * Tests the ININE-C form controller methods in isolation
 */
class IninecControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new INineC();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsRows(): void
    {
        $columns = ['uid', 'wardid', 'lgaid', 'userid', 'latitude', 'longitude', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_i9c', $columns);

        $controller = new INineC();
        $geo = $this->seedGeoHierarchy('I9C');
        $uid = 'I9C-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'userid' => 5,
            'latitude' => '1.4',
            'longitude' => '2.5',
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-05-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame('0', (string) $result[0]['id']);

        $this->recordCleanup('mo_form_i9c', 'uid', $uid);
        $this->recordCleanup('mo_form_i9c', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT aa, ah FROM mo_form_i9c WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('1', (string) $rows[0]['aa']);
        $this->assertSame('8', (string) $rows[0]['ah']);
    }
}
