<?php

namespace Tests\Unit\Controllers\Form;

use Form\FiveRevisit;

/**
 * Unit Test: Five Revisit Form Controller
 * 
 * Tests the five revisit form controller methods in isolation
 */
class FiveRevisitControllerTest extends FormTestCase
{
    public function testBulkSaveReturnsEmptyForInvalidPayload(): void
    {
        $controller = new FiveRevisit();

        $result = $controller->BulkSave([]);
        $this->assertSame([], $result);
        $this->assertSame('Invalid bulk data', $controller->ErrorMessage);
    }

    public function testBulkSavePersistsWithOptionalEtokenFieldsMissing(): void
    {
        $columns = ['uid', 'wardid', 'dpid', 'lgaid', 'comid', 'userid', 'latitude', 'longitude', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'aj', 'etoken_serial', 'etoken_uuid', 'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_five_revisit', $columns);

        $controller = new FiveRevisit();
        $geo = $this->seedGeoHierarchy('Five');
        $uid = 'FIVE-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => 2,
            'latitude' => '1.1',
            'longitude' => '2.2',
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'ai' => '9',
            'aj' => '10',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-02-01',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame(0, (int) $result[0]['id']);

        $this->recordCleanup('mo_form_five_revisit', 'uid', $uid);
        $this->recordCleanup('mo_form_five_revisit', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT etoken_serial, etoken_uuid FROM mo_form_five_revisit WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame('', (string) $rows[0]['etoken_serial']);
        $this->assertSame('', (string) $rows[0]['etoken_uuid']);
    }

    public function testBulkSavePersistsEtokenFieldsWhenProvided(): void
    {
        $columns = ['uid', 'wardid', 'dpid', 'lgaid', 'comid', 'userid', 'latitude', 'longitude', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'aj', 'etoken_serial', 'etoken_uuid', 'domain', 'app_version', 'capture_date', 'created'];
        $this->requireFormSchema('mo_form_five_revisit', $columns);

        $controller = new FiveRevisit();
        $geo = $this->seedGeoHierarchy('FiveToken');
        $uid = 'FIVE-' . uniqid();

        $payload = [[
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => 2,
            'latitude' => '1.1',
            'longitude' => '2.2',
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'ai' => '9',
            'aj' => '10',
            'etoken_serial' => 'ET-' . uniqid(),
            'etoken_uuid' => md5(uniqid('', true)),
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-02-02',
        ]];

        $result = $controller->BulkSave($payload);
        $this->assertCount(1, $result);
        $this->assertSame($uid, $result[0]['uid']);
        $this->assertNotSame(0, (int) $result[0]['id']);

        $this->recordCleanup('mo_form_five_revisit', 'uid', $uid);
        $this->recordCleanup('mo_form_five_revisit', 'id', $result[0]['id']);

        $rows = $this->getDb()->DataTable("SELECT etoken_serial, etoken_uuid FROM mo_form_five_revisit WHERE uid = '{$uid}'");
        $this->assertNotEmpty($rows);
        $this->assertSame($payload[0]['etoken_serial'], $rows[0]['etoken_serial']);
        $this->assertSame($payload[0]['etoken_uuid'], $rows[0]['etoken_uuid']);
    }
}
