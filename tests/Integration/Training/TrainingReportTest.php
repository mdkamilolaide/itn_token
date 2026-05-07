<?php

namespace Tests\Integration\Training;

use Training\Training;

class TrainingReportTest extends TrainingTestCase
{
    public function testParticipantExportsAndCounts(): void
    {
        $this->requireTrainingSchema();
        $this->requireParticipantSchema();
        $this->requireParticipantDetailsSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Report');
        $roleId = 1;
        $this->seedRole($roleId);

        $userId = random_int(2000, 2999);
        $this->seedUser($userId, $roleId, 'ward', $geo['wardid']);

        $trainingId = $this->seedTrainingRow('Training F', 'ward', $geo['wardid']);
        $this->seedParticipant($trainingId, $userId);

        $payload = json_decode($training->ExcelGetParticipantList($trainingId), true);
        $this->assertNotEmpty($payload);
        $this->assertSame('Participant List', $payload[0]['sheetName']);
        $this->assertNotEmpty($payload[0]['data']);

        $count = $training->ExcelCountParticipantList($trainingId);
        $this->assertSame(1, (int) $count);
    }

    public function testGetParticipantsListByWard(): void
    {
        $this->requireTrainingSchema();
        $this->requireParticipantSchema();
        $this->requireParticipantDetailsSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('List');
        $roleId = 1;
        $this->seedRole($roleId);

        $userId = random_int(3000, 3999);
        $this->seedUser($userId, $roleId, 'ward', $geo['wardid']);

        $trainingId = $this->seedTrainingRow('Training G', 'ward', $geo['wardid']);
        $this->seedParticipant($trainingId, $userId);

        $rows = $training->getParticipantsList($trainingId, 'ward', $geo['wardid']);
        $this->assertNotEmpty($rows);
        $this->assertSame('user.' . $userId, $rows[0]['loginid']);
    }

    public function testDashboardCounts(): void
    {
        if (!$this->tableHasColumns('tra_training', ['trainingid']) || !$this->tableHasColumns('tra_session', ['sessionid'])) {
            $this->markTestSkipped('Missing dashboard schema');
        }

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Dash');

        $trainingId = $this->seedTrainingRow('Training H', 'ward', $geo['wardid']);
        $this->seedSessionRow($trainingId, 'Session C');

        $trainCount = $training->DashCountTraining();
        $this->assertNotEmpty($trainCount);

        $activeCount = $training->DashCountActive();
        $this->assertNotEmpty($activeCount);

        $sessionCount = $training->DashCountSession();
        $this->assertNotEmpty($sessionCount);
    }
}
