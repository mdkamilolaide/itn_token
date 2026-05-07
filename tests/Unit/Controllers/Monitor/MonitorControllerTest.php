<?php

namespace Tests\Unit\Controllers\Monitor;

use Monitor\Monitor;

/**
 * Unit Test: Monitor Controller
 * 
 * Tests the monitoring controller methods in isolation
 */
class MonitorControllerTest extends MonitorTestCase
{

    public function testEeFormInineAExportsData(): void
    {
        $this->requireInineASchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('I9A');
        $userId = random_int(2000, 2999);
        $this->seedUser($userId, 'user.' . $userId);

        $uid = 'I9A-' . uniqid();
        $this->insertRow('mo_form_i9a', [
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => $userId,
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'ai' => '9',
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-02',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_form_i9a', 'uid', $uid);

        $json = $monitor->EeFormInineA();
        $data = json_decode($json, true);
        $this->assertSame('Form-i9a', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }

    public function testEeFormInineBExportsData(): void
    {
        $this->requireInineBSchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('I9B');
        $userId = random_int(3000, 3999);
        $this->seedUser($userId, 'user.' . $userId);

        $uid = 'I9B-' . uniqid();
        $this->insertRow('mo_form_i9b', [
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'dpid' => $geo['dpid'],
            'comid' => $geo['comid'],
            'userid' => $userId,
            'supervisor' => 'Supervisor',
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
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-03',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_form_i9b', 'uid', $uid);

        $json = $monitor->EeFormInineB();
        $data = json_decode($json, true);
        $this->assertSame('Form-i9b', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }

    public function testEeFormInineCExportsData(): void
    {
        $this->requireInineCSchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('I9C');
        $userId = random_int(4000, 4999);
        $this->seedUser($userId, 'user.' . $userId);

        $uid = 'I9C-' . uniqid();
        $this->insertRow('mo_form_i9c', [
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'lgaid' => $geo['lgaid'],
            'userid' => $userId,
            'aa' => '1',
            'ab' => '2',
            'ac' => '3',
            'ad' => '4',
            'ae' => '5',
            'af' => '6',
            'ag' => '7',
            'ah' => '8',
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-04',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_form_i9c', 'uid', $uid);

        $json = $monitor->EeFormInineC();
        $data = json_decode($json, true);
        $this->assertSame('Form-i9c', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }

    public function testEeFormFiveRevisitExportsData(): void
    {
        $this->requireRevisitSchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('REV');
        $userId = random_int(5000, 5999);
        $this->seedUser($userId, 'user.' . $userId);

        $uid = 'REV-' . uniqid();
        $this->insertRow('mo_form_five_revisit', [
            'uid' => $uid,
            'wardid' => $geo['wardid'],
            'dpid' => $geo['dpid'],
            'lgaid' => $geo['lgaid'],
            'comid' => $geo['comid'],
            'userid' => $userId,
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
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-05',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_form_five_revisit', 'uid', $uid);

        $json = $monitor->EeFormFiveRevisit();
        $data = json_decode($json, true);
        $this->assertSame('Revisit', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }


    public function testEeFormSmcSupervisoryCddExportsData(): void
    {
        $this->requireSmcCddSchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('CDD');
        $userId = random_int(7000, 7999);
        $this->seedUser($userId, 'user.' . $userId);
        $periodId = $this->seedSmcPeriod('Visit 1');

        $uid = 'CDD-' . uniqid();
        $this->insertRow('mo_smc_supervisor_cdd', [
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'periodid' => $periodId,
            'userid' => $userId,
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
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-07',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_smc_supervisor_cdd', 'uid', $uid);

        $json = $monitor->EeFormSmcSupervisoryCdd();
        $data = json_decode($json, true);
        $this->assertSame('SMC-Supervisory-CDD', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }

    public function testEeFormSmcSupervisoryHfwExportsData(): void
    {
        $this->requireSmcHfwSchema();

        $monitor = new Monitor();
        $geo = $this->seedGeoHierarchy('HFW');
        $userId = random_int(8000, 8999);
        $this->seedUser($userId, 'user.' . $userId);
        $periodId = $this->seedSmcPeriod('Visit 2');

        $uid = 'HFW-' . uniqid();
        $this->insertRow('mo_smc_supervisor_hfw', [
            'uid' => $uid,
            'lgaid' => $geo['lgaid'],
            'wardid' => $geo['wardid'],
            'periodid' => $periodId,
            'userid' => $userId,
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
            'latitude' => '1.1',
            'longitude' => '2.2',
            'domain' => 'test',
            'app_version' => '1.0',
            'capture_date' => '2099-01-08',
            'created' => date('Y-m-d H:i:s'),
        ]);
        $this->recordCleanup('mo_smc_supervisor_hfw', 'uid', $uid);

        $json = $monitor->EeFormSmcSupervisoryHfw();
        $data = json_decode($json, true);
        $this->assertSame('SMC-Supervisory-HFW', $data[0]['sheetName']);
        $this->assertNotEmpty($data[0]['data']);
    }

    private function requireStatusSchema(): void
    {
        $tables = [
            'mo_form_end_process',
            'mo_form_five_revisit',
            'mo_form_i9a',
            'mo_form_i9b',
            'mo_form_i9c',
            'mo_smc_supervisor_cdd',
            'mo_smc_supervisor_hfw',
        ];
        foreach ($tables as $table) {
            if (empty($this->getColumns($table))) {
                $this->markTestSkipped('Form status schema not available');
            }
        }
    }

    private function requireInineASchema(): void
    {
        $formColumns = ['uid', 'wardid', 'lgaid', 'comid', 'userid', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $commColumns = ['comid', 'community'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_form_i9a', $formColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_comm', $commColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('I9a export schema not available');
        }
    }

    private function requireInineBSchema(): void
    {
        $formColumns = ['uid', 'wardid', 'lgaid', 'dpid', 'comid', 'userid', 'supervisor', 'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb', 'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb', 'ma', 'mb', 'na', 'nb', 'oa', 'ob', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $dpColumns = ['dpid', 'dp'];
        $commColumns = ['comid', 'community'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_form_i9b', $formColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_dp', $dpColumns)
            || !$this->tableHasColumns('ms_geo_comm', $commColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('I9b export schema not available');
        }
    }

    private function requireInineCSchema(): void
    {
        $formColumns = ['uid', 'wardid', 'lgaid', 'userid', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_form_i9c', $formColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('I9c export schema not available');
        }
    }

    private function requireRevisitSchema(): void
    {
        $formColumns = ['uid', 'wardid', 'dpid', 'lgaid', 'comid', 'userid', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'aj', 'etoken_serial', 'etoken_uuid', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $dpColumns = ['dpid', 'dp'];
        $commColumns = ['comid', 'community'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_form_five_revisit', $formColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_dp', $dpColumns)
            || !$this->tableHasColumns('ms_geo_comm', $commColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('Revisit export schema not available');
        }
    }

    private function requireEndProcessSchema(): void
    {
        $formColumns = ['uid', 'wardid', 'lgaid', 'comid', 'userid', 'aa', 'ab', 'ac', 'ad', 'ae', 'af', 'ag', 'ah', 'ai', 'aj', 'ak', 'al', 'am', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $commColumns = ['comid', 'community'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_form_end_process', $formColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('ms_geo_comm', $commColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('End process export schema not available');
        }
    }

    private function requireSmcCddSchema(): void
    {
        $formColumns = ['uid', 'lgaid', 'wardid', 'periodid', 'userid', 'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb', 'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb', 'ma', 'mb', 'na', 'nb', 'oa', 'ob', 'pa', 'pb', 'q', 'r', 's', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $periodColumns = ['periodid', 'title'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_smc_supervisor_cdd', $formColumns)
            || !$this->tableHasColumns('smc_period', $periodColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('SMC CDD export schema not available');
        }
    }

    private function requireSmcHfwSchema(): void
    {
        $formColumns = ['uid', 'lgaid', 'wardid', 'periodid', 'userid', 'aa', 'ab', 'ba', 'bb', 'ca', 'cb', 'da', 'db', 'ea', 'eb', 'fa', 'fb', 'ga', 'gb', 'ha', 'hb', 'ia', 'ib', 'ja', 'jb', 'ka', 'kb', 'la', 'lb', 'm1a', 'm1b', 'm2a', 'm2b', 'm3a', 'm3b', 'm4a', 'm4b', 'n1a', 'n1b', 'n2a', 'n2b', 'n3a', 'n3b', 'n4a', 'n4b', 'n5a', 'n5b', 'n6a', 'n6b', 'o1a', 'o1b', 'o2a', 'o2b', 'o3a', 'o3b', 'pa', 'pb', 'q1a', 'q1b', 'q2a', 'q2b', 'ra', 'rb', 's', 't', 'v', 'latitude', 'longitude', 'domain', 'app_version', 'capture_date', 'created'];
        $periodColumns = ['periodid', 'title'];
        $lgaColumns = ['LgaId', 'Fullname'];
        $wardColumns = ['wardid', 'ward'];
        $loginColumns = ['userid', 'loginid'];
        $identityColumns = ['userid', 'first', 'middle', 'last'];

        if (!$this->tableHasColumns('mo_smc_supervisor_hfw', $formColumns)
            || !$this->tableHasColumns('smc_period', $periodColumns)
            || !$this->tableHasColumns('ms_geo_lga', $lgaColumns)
            || !$this->tableHasColumns('ms_geo_ward', $wardColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
        ) {
            $this->markTestSkipped('SMC HFW export schema not available');
        }
    }
}
