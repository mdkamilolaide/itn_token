<?php

namespace Tests\Integration\Training;

use Training\Training;

class TrainingSessionTest extends TrainingTestCase
{
    public function testTrainingLifecycleCreateUpdateToggle(): void
    {
        $this->requireTrainingSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Train');

        $id = $training->CreateTraining('Intro', 'ward', $geo['wardid'], 'Desc', '2099-01-01', '2099-01-02');
        $this->assertNotEmpty($id);
        $this->recordCleanup('tra_training', 'trainingid', $id);

        $training->UpdateTraining('Updated', 'ward', $geo['wardid'], 'Desc2', '2099-02-01', '2099-02-02', $id);
        $row = $this->getDb()->DataTable("SELECT title FROM tra_training WHERE trainingid = {$id}");
        $this->assertNotEmpty($row);
        $this->assertSame('Updated', $row[0]['title']);

        $training->ToggleTraining($id);
        $toggle = $this->getDb()->DataTable("SELECT active FROM tra_training WHERE trainingid = {$id}");
        $this->assertNotEmpty($toggle);
        $this->assertSame('0', (string) $toggle[0]['active']);
    }

    public function testAddParticipantsAndRemove(): void
    {
        $this->requireTrainingSchema();
        $this->requireParticipantSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Participants');
        $roleId = 1;
        $this->seedRole($roleId);

        $userA = random_int(2000, 2999);
        $userB = random_int(3000, 3999);
        $this->seedUser($userA, $roleId, 'ward', $geo['wardid']);
        $this->seedUser($userB, $roleId, 'ward', $geo['wardid']);

        $trainingId = $this->seedTrainingRow('Training A', 'ward', $geo['wardid']);

        $added = $training->AddParticipants($trainingId, [$userA, $userB]);
        $this->assertSame(2, $added);

        $rows = $this->getDb()->DataTable("SELECT participant_id FROM tra_participants WHERE trainingid = {$trainingId}");
        $this->assertCount(2, $rows);

        $removed = $training->RemoveParticipant($trainingId, [$rows[0]['participant_id']]);
        $this->assertSame(1, $removed);

        $remaining = $this->getDb()->DataTable("SELECT participant_id FROM tra_participants WHERE trainingid = {$trainingId}");
        $this->assertCount(1, $remaining);
    }

    public function testAddParticipantsByGroupSkipsDuplicates(): void
    {
        $this->requireTrainingSchema();
        $this->requireParticipantSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Group');
        $roleId = 1;
        $this->seedRole($roleId);

        $userA = random_int(4000, 4999);
        $userB = random_int(5000, 5999);
        $this->seedUser($userA, $roleId, 'ward', $geo['wardid'], 'team');
        $this->seedUser($userB, $roleId, 'ward', $geo['wardid'], 'team');

        $trainingId = $this->seedTrainingRow('Training B', 'ward', $geo['wardid']);

        $first = $training->AddParticipantsByGroup($trainingId, 'team');
        $second = $training->AddParticipantsByGroup($trainingId, 'team');

        $this->assertSame(2, $first);
        $this->assertSame(0, $second);

        $rows = $this->getDb()->DataTable("SELECT participant_id FROM tra_participants WHERE trainingid = {$trainingId}");
        $this->assertCount(2, $rows);
    }

    public function testSessionCreateUpdateDelete(): void
    {
        $this->requireTrainingSchema();
        $this->requireSessionSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Session');
        $trainingId = $this->seedTrainingRow('Training C', 'ward', $geo['wardid']);

        $sessionId = $training->CreateSession($trainingId, 'Session 1', '2099-03-01');
        $this->assertNotEmpty($sessionId);
        $this->recordCleanup('tra_session', 'sessionid', $sessionId);

        $training->UpdateSession($trainingId, 'Session 2', '2099-03-02', $sessionId);
        $session = $this->getDb()->DataTable("SELECT title FROM tra_session WHERE sessionid = {$sessionId}");
        $this->assertNotEmpty($session);
        $this->assertSame('Session 2', $session[0]['title']);

        $training->DeleteSession($sessionId);
        $deleted = $this->getDb()->DataTable("SELECT sessionid FROM tra_session WHERE sessionid = {$sessionId}");
        $this->assertSame([], $deleted);
    }
}
