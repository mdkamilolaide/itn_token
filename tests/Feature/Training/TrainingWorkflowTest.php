<?php

namespace Tests\Feature\Training;

use Tests\TestCase;

class TrainingWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdTrainingIds = [];
    private array $createdSessionIds = [];
    private array $createdParticipantIds = [];
    private array $createdAttendanceIds = [];
    private array $updatedUserGroups = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/training/training.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        if (!empty($this->createdAttendanceIds)) {
            $ids = array_filter(array_map('intval', $this->createdAttendanceIds));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM tra_attendant WHERE attendant_id IN (' . implode(',', $ids) . ')', []);
            }
        }

        if (!empty($this->createdParticipantIds)) {
            $ids = array_filter(array_map('intval', $this->createdParticipantIds));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM tra_participants WHERE participant_id IN (' . implode(',', $ids) . ')', []);
            }
        }

        if (!empty($this->createdSessionIds)) {
            $ids = array_filter(array_map('intval', $this->createdSessionIds));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM tra_session WHERE sessionid IN (' . implode(',', $ids) . ')', []);
            }
        }

        if (!empty($this->createdTrainingIds)) {
            $ids = array_filter(array_map('intval', $this->createdTrainingIds));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM tra_training WHERE trainingid IN (' . implode(',', $ids) . ')', []);
            }
        }

        if (!empty($this->updatedUserGroups) && $this->tableHasColumns('usr_login', ['user_group'])) {
            foreach ($this->updatedUserGroups as $userid => $group) {
                $db->executeTransaction('UPDATE usr_login SET user_group = ? WHERE userid = ?', [$group, $userid]);
            }
        }

        parent::tearDown();
    }

    /**
     * Test complete training program workflow
     */
    public function testCompleteTrainingProgramWorkflow()
    {
        if (!$this->tableHasColumns('tra_training', ['trainingid', 'title', 'geo_location', 'location_id'])) {
            $this->markTestSkipped('Training tables missing');
        }

        $training = new \Training\Training();
        $geo = $this->getGeoSample();
        $users = $this->getUserIds(2);
        if (count($users) === 0) {
            $this->markTestSkipped('No users available for training workflow');
        }

        $trainingId = $training->CreateTraining(
            'Training ' . uniqid(),
            $geo['level'],
            $geo['location_id'],
            'Test training',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 day'))
        );
        $this->assertNotFalse($trainingId);
        $this->createdTrainingIds[] = (int) $trainingId;

        $updated = $training->UpdateTraining(
            'Training Updated',
            $geo['level'],
            $geo['location_id'],
            'Updated',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+2 days')),
            $trainingId
        );
        $this->assertTrue((bool) $updated);

        $sessionId = $training->CreateSession($trainingId, 'Session 1', date('Y-m-d'));
        $this->assertNotFalse($sessionId);
        $this->createdSessionIds[] = (int) $sessionId;

        $participantCount = $training->AddParticipants($trainingId, $users);
        $this->assertEquals(count($users), $participantCount);
        $this->createdParticipantIds = array_merge($this->createdParticipantIds, $this->getParticipantIds($trainingId, $users));

        $attendanceId = $training->AddAttendance(
            $sessionId,
            $this->createdParticipantIds[0],
            'clock-in',
            1,
            date('Y-m-d H:i:s'),
            0,
            0,
            $users[0],
            '1.0'
        );
        if ($attendanceId) {
            $this->createdAttendanceIds[] = (int) $attendanceId;
        }

        $this->assertIsArray($training->getGenericTraining($geo['level'], $geo['location_id']));
        $this->assertIsArray($training->getFilteredTraining($geo['level']));
        $this->assertIsArray($training->getGenericSession($trainingId));
        $this->assertIsArray($training->getParticipantsList($trainingId, $geo['level'], $geo['location_id']));
        $this->assertIsArray($training->getAttendanceList($sessionId));
        $this->assertNotEmpty($training->ExcelGetParticipantList($trainingId));
        $this->assertIsNumeric($training->ExcelCountParticipantList($trainingId));
        $this->assertNotEmpty($training->ExcelGetAttendanceList($sessionId));
        $this->assertIsNumeric($training->ExcelCountAttendanceList($sessionId));

        $this->assertIsArray($training->DashCountTraining());
        $this->assertIsArray($training->DashCountActive());
        $this->assertIsArray($training->DashCountSession());

        $this->assertTrue((bool) $training->ToggleTraining($trainingId));
    }

    /**
     * Test training enrollment and registration
     */
    public function testTrainingEnrollmentWorkflow()
    {
        if (!$this->tableHasColumns('tra_participants', ['participant_id', 'trainingid', 'userid'])) {
            $this->markTestSkipped('Training participant tables missing');
        }

        $training = new \Training\Training();
        $geo = $this->getGeoSample();
        $users = $this->getUserIds(2);
        if (count($users) === 0) {
            $this->markTestSkipped('No users available for training enrollment');
        }

        $trainingId = $this->createTraining($geo);

        $this->assertEquals(count($users), $training->AddParticipants($trainingId, $users));
        $this->createdParticipantIds = array_merge($this->createdParticipantIds, $this->getParticipantIds($trainingId, $users));

        if ($this->tableHasColumns('usr_login', ['user_group'])) {
            $groupName = 'group-' . strtolower(substr(uniqid(), -6));
            $this->setUserGroup($users[0], $groupName);
            $added = $training->AddParticipantsByGroup($trainingId, $groupName);
            $this->assertIsInt($added);
        } else {
            $this->assertTrue(true);
        }

        $duplicate = $training->getParticipantDuplicate();
        $this->assertIsArray($duplicate);

        if (!empty($this->createdParticipantIds)) {
            $removed = $training->RemoveParticipant($trainingId, [$this->createdParticipantIds[0]]);
            $this->assertEquals(1, $removed);
        }
    }

    /**
     * Test training session execution
     */
    public function testTrainingSessionWorkflow()
    {
        if (!$this->tableHasColumns('tra_session', ['sessionid', 'trainingid', 'title'])) {
            $this->markTestSkipped('Training session tables missing');
        }

        $training = new \Training\Training();
        $geo = $this->getGeoSample();
        $trainingId = $this->createTraining($geo);

        $sessionId = $training->CreateSession($trainingId, 'Session A', date('Y-m-d'));
        $this->assertNotFalse($sessionId);
        $this->createdSessionIds[] = (int) $sessionId;

        $this->assertIsArray($training->getGenericSession($trainingId));

        $updated = $training->UpdateSession($trainingId, 'Session A Updated', date('Y-m-d', strtotime('+1 day')), $sessionId);
        $this->assertTrue((bool) $updated);

        $deleted = $training->DeleteSession($sessionId);
        $this->assertTrue((bool) $deleted);
    }

    /**
     * Test attendance tracking
     */
    public function testAttendanceTrackingWorkflow()
    {
        if (!$this->tableHasColumns('tra_attendant', ['attendant_id', 'session_id', 'participant_id'])) {
            $this->markTestSkipped('Training attendance tables missing');
        }

        $training = new \Training\Training();
        $geo = $this->getGeoSample();
        $users = $this->getUserIds(1);
        if (count($users) === 0) {
            $this->markTestSkipped('No users available for attendance workflow');
        }

        $trainingId = $this->createTraining($geo);
        $sessionId = $training->CreateSession($trainingId, 'Session B', date('Y-m-d'));
        $this->createdSessionIds[] = (int) $sessionId;

        $training->AddParticipants($trainingId, $users);
        $participantIds = $this->getParticipantIds($trainingId, $users);
        $this->createdParticipantIds = array_merge($this->createdParticipantIds, $participantIds);

        $attendanceId = $training->AddAttendance(
            $sessionId,
            $participantIds[0],
            'clock-in',
            1,
            date('Y-m-d H:i:s'),
            0,
            0,
            $users[0],
            '1.0'
        );
        if ($attendanceId) {
            $this->createdAttendanceIds[] = (int) $attendanceId;
        }

        $bulk = $training->AddAttendancebulk([
            [
                'session_id' => $sessionId,
                'participant_id' => $participantIds[0],
                'at_type' => 'clock-out',
                'bio_auth' => 0,
                'collected' => date('Y-m-d H:i:s'),
                'longitude' => 0,
                'latitude' => 0,
                'userid' => $users[0],
                'app_version' => '1.0',
            ]
        ]);
        $this->assertEquals(1, $bulk);

        $this->assertIsArray($training->getAttendanceList($sessionId));
        $this->assertNotEmpty($training->ExcelGetAttendanceList($sessionId));
        $this->assertIsNumeric($training->ExcelCountAttendanceList($sessionId));
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SHOW COLUMNS FROM ' . $table);
        if (count($rows) === 0) {
            return false;
        }
        $existing = array_map(fn ($row) => $row['Field'], $rows);
        foreach ($columns as $column) {
            if (!in_array($column, $existing, true)) {
                return false;
            }
        }
        return true;
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }
        return $rows[0]['val'] ?? null;
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $level = $this->safeSelectValue($db, "SELECT geo_level AS val FROM sys_geo_level ORDER BY geo_value ASC LIMIT 1") ?? 'state';
        $locationId = 0;

        if ($level === 'state') {
            $locationId = (int) ($this->safeSelectValue($db, 'SELECT stateid AS val FROM ms_geo_state LIMIT 1') ?? 0);
        } elseif ($level === 'lga') {
            $locationId = (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 0);
        } elseif ($level === 'ward') {
            $locationId = (int) ($this->safeSelectValue($db, 'SELECT wardid AS val FROM ms_geo_ward LIMIT 1') ?? 0);
        } elseif ($level === 'dp') {
            $locationId = (int) ($this->safeSelectValue($db, 'SELECT dpid AS val FROM ms_geo_dp LIMIT 1') ?? 0);
        }

        if ($locationId === 0) {
            $locationId = (int) ($this->safeSelectValue($db, 'SELECT LgaId AS val FROM ms_geo_lga LIMIT 1') ?? 1);
            $level = 'lga';
        }

        return [
            'level' => $level,
            'location_id' => $locationId ?: 1,
        ];
    }

    private function getUserIds(int $limit): array
    {
        $db = $this->getDb();
        $rows = $db->DataTable('SELECT userid FROM usr_login LIMIT ' . $limit);
        if (count($rows) === 0) {
            return [];
        }
        return array_values(array_map(fn ($row) => (int) $row['userid'], $rows));
    }

    private function createTraining(array $geo): int
    {
        $training = new \Training\Training();
        $trainingId = $training->CreateTraining(
            'Training ' . uniqid(),
            $geo['level'],
            $geo['location_id'],
            'Test training',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 day'))
        );
        $this->createdTrainingIds[] = (int) $trainingId;
        return (int) $trainingId;
    }

    private function getParticipantIds(int $trainingId, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $db = $this->getDb();
        $list = implode(',', array_map('intval', $userIds));
        $rows = $db->DataTable("SELECT participant_id FROM tra_participants WHERE trainingid = $trainingId AND userid IN ($list)");
        return array_values(array_map(fn ($row) => (int) $row['participant_id'], $rows));
    }

    private function setUserGroup(int $userid, string $groupName): void
    {
        $db = $this->getDb();
        $current = $this->safeSelectValue($db, "SELECT user_group AS val FROM usr_login WHERE userid = $userid");
        $this->updatedUserGroups[$userid] = $current;
        $db->executeTransaction('UPDATE usr_login SET user_group = ? WHERE userid = ?', [$groupName, $userid]);
    }
}
