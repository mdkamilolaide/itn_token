<?php

namespace Tests\Integration\Training;

use Training\Training;

class TrainingAttendanceTest extends TrainingTestCase
{
    public function testAddAttendanceAndList(): void
    {
        $this->requireTrainingSchema();
        $this->requireSessionSchema();
        $this->requireAttendanceSchema();
        $this->requireParticipantDetailsSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('Attend');
        $roleId = 1;
        $this->seedRole($roleId);

        $userId = random_int(2000, 2999);
        $this->seedUser($userId, $roleId, 'ward', $geo['wardid']);

        $trainingId = $this->seedTrainingRow('Training D', 'ward', $geo['wardid']);
        $participantId = $this->seedParticipant($trainingId, $userId);
        $sessionId = $this->seedSessionRow($trainingId, 'Session A');

        $attendanceId = $training->AddAttendance($sessionId, $participantId, 'clock-in', 1, date('Y-m-d H:i:s'), '7.1', '9.2', $userId, '1.0');
        $this->assertNotEmpty($attendanceId);
        $this->recordCleanup('tra_attendant', 'attendant_id', $attendanceId);

        $list = $training->getAttendanceList($sessionId);
        $this->assertNotEmpty($list);

        $excel = $training->ExcelGetAttendanceList($sessionId, 'ward', $geo['wardid']);
        $payload = json_decode($excel, true);
        $this->assertNotEmpty($payload);
        $this->assertSame('Attendance List', $payload[0]['sheetName']);

        $count = $training->ExcelCountAttendanceList($sessionId, 'ward', $geo['wardid']);
        $this->assertSame(1, (int) $count);
    }

    public function testBulkAttendanceInsertsRecords(): void
    {
        $this->requireTrainingSchema();
        $this->requireSessionSchema();
        $this->requireAttendanceSchema();

        $training = new Training();
        $geo = $this->seedGeoHierarchy('AttendBulk');
        $roleId = 1;
        $this->seedRole($roleId);

        $userId = random_int(3000, 3999);
        $this->seedUser($userId, $roleId, 'ward', $geo['wardid']);

        $trainingId = $this->seedTrainingRow('Training E', 'ward', $geo['wardid']);
        $participantId = $this->seedParticipant($trainingId, $userId);
        $sessionId = $this->seedSessionRow($trainingId, 'Session B');

        $count = $training->AddAttendancebulk([
            [
                'session_id' => $sessionId,
                'participant_id' => $participantId,
                'at_type' => 'clock-in',
                'bio_auth' => 1,
                'collected' => date('Y-m-d H:i:s'),
                'longitude' => '7.1',
                'latitude' => '9.2',
                'userid' => $userId,
                'app_version' => '1.0',
            ],
        ]);

        $this->assertSame(1, $count);

        $rows = $this->getDb()->DataTable("SELECT attendant_id FROM tra_attendant WHERE session_id = {$sessionId}");
        $this->assertNotEmpty($rows);
        $this->recordCleanup('tra_attendant', 'attendant_id', $rows[0]['attendant_id']);
    }
}
