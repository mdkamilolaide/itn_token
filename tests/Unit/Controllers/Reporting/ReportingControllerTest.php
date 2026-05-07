<?php

namespace Tests\Unit\Controllers\Reporting;

use Reporting\Reporting;

require_once __DIR__ . '/ReportingTestCase.php';

/**
 * Unit Test: Reporting Controller
 * 
 * Tests the reporting controller methods in isolation
 */
class ReportingControllerTest extends ReportingTestCase
{
    public function testListUncapturedUsersHandlesInvalidGeoLevel(): void
    {
        $participantColumns = ['trainingid', 'userid'];
        $trainingColumns = ['trainingid', 'title', 'active'];
        $loginColumns = ['userid', 'loginid', 'geo_level', 'geo_level_id', 'roleid'];
        $identityColumns = ['userid', 'first', 'last'];
        $geoColumns = ['geo_level', 'geo_level_id', 'stateid', 'lgaid', 'wardid', 'geo_string'];
        if (!$this->tableHasColumns('tra_participants', $participantColumns)
            || !$this->tableHasColumns('tra_training', $trainingColumns)
            || !$this->tableHasColumns('usr_login', $loginColumns)
            || !$this->tableHasColumns('usr_identity', $identityColumns)
            || !$this->tableHasColumns('sys_geo_codex', $geoColumns)
        ) {
            $this->markTestSkipped('Uncaptured schema not available');
        }

        $reporting = new Reporting();
        $geo = $this->seedGeoHierarchy('Uncap');
        $this->seedGeoCodexWard($geo);

        $userId = random_int(2000, 2999);
        $this->seedUser($userId, 'ward', $geo['wardid']);
        $this->insertRow('usr_identity', [
            'userid' => $userId,
            'first' => null,
            'last' => null,
        ]);

        $trainingId = $this->seedTraining('Training 2');
        $this->seedParticipant($trainingId, $userId);

        $json = $reporting->ListUncapturedUsers($trainingId, 'invalid', $geo['wardid']);
        $data = json_decode($json, true);
        $this->assertSame('Uncaptured', $data[0]['sheetName']);
        $this->assertSame([], $data[0]['data']);
    }
}
