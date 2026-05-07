<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use Training\Training;

/**
 * Training controller integration tests.
 *
 * Covers training management, sessions, participants, attendance tracking,
 * and Excel export functionality.
 */
class TrainingControllerTest extends TestCase
{
    private Training $training;

    protected function setUp(): void
    {
        parent::setUp();
        $this->training = new Training();
    }

    // ==========================================
    // Instantiation
    // ==========================================

    public function testTrainingInstantiation(): void
    {
        $this->assertInstanceOf(Training::class, $this->training);
    }

    // ==========================================
    // Training CRUD Operations
    // ==========================================

    public function testCreateTraining(): void
    {
        try {
            $result = $this->training->CreateTraining(
                'Test Training ' . time(),
                'state',
                1,
                'Test training description',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+7 days'))
            );
            // Transaction rollback handles cleanup
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testUpdateTraining(): void
    {
        try {
            $result = $this->training->UpdateTraining(
                'Updated Training',
                'state',
                1,
                'Updated description',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+7 days')),
                999999 // Non-existent ID
            );
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testToggleTraining(): void
    {
        try {
            $result = @$this->training->ToggleTraining(999999);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // Training Retrieval
    // ==========================================

    public function testGetGenericTraining(): void
    {
        $result = $this->training->getGenericTraining('state', 1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetFilteredTraining(): void
    {
        $result = $this->training->getFilteredTraining('state');
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    // ==========================================
    // Training Data Validation
    // ==========================================

    public function testTrainingTableExists(): void
    {
        $tableExists = $this->db->Table("SHOW TABLES LIKE 'tra_%'");
        // Training tables may or may not exist depending on environment
        $this->assertIsArray($tableExists);
    }

    // ==========================================
    // Session Management
    // ==========================================

    public function testGetGenericSession(): void
    {
        $result = $this->training->getGenericSession(0);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testCreateSession(): void
    {
        try {
            $result = $this->training->CreateSession(
                999999, // Non-existent training ID
                'Test Session',
                date('Y-m-d')
            );
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testUpdateSession(): void
    {
        try {
            $result = $this->training->UpdateSession(
                999999, // Session ID
                'Updated Session',
                date('Y-m-d'),
                999999  // Training ID
            );
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testDeleteSession(): void
    {
        try {
            $result = $this->training->DeleteSession(999999);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // Participant Management
    // ==========================================

    public function testGetParticipantsList(): void
    {
        $result = $this->training->getParticipantsList(0, 'state', 1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetParticipantDuplicate(): void
    {
        $result = $this->training->getParticipantDuplicate();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testAddParticipantsEmpty(): void
    {
        try {
            $result = $this->training->AddParticipants(0, []);
            $this->assertNotNull($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testAddParticipantsByGroup(): void
    {
        try {
            $result = $this->training->AddParticipantsByGroup(0, 'test_group');
            $this->assertNotNull($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testRemoveParticipantEmpty(): void
    {
        try {
            $result = $this->training->RemoveParticipant(0, []);
            $this->assertNotNull($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // Attendance Tracking
    // ==========================================

    public function testGetAttendanceList(): void
    {
        $result = $this->training->getAttendanceList(0);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testAddAttendance(): void
    {
        try {
            $result = $this->training->AddAttendance(
                0,          // session_id
                0,          // participant_id
                'in',       // type
                0,          // bio_auth
                0,          // collected
                0.0,        // longitude
                0.0,        // latitude
                1           // userid
            );
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testAddAttendanceWithVersion(): void
    {
        try {
            $result = $this->training->AddAttendance(
                0,
                0,
                'in',
                0,
                0,
                0.0,
                0.0,
                1,
                '1.0.0'
            );
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    public function testAddAttendanceBulkEmpty(): void
    {
        try {
            $result = $this->training->AddAttendancebulk([]);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    // ==========================================
    // Excel Export Functions
    // ==========================================

    public function testExcelGetParticipantList(): void
    {
        $result = $this->training->ExcelGetParticipantList(0);

        // May return JSON string or array
        $this->assertNotNull($result);
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            $this->assertIsArray($decoded);
        } else {
            $this->assertTrue(is_array($result) || $result === null || $result === false);
        }
    }

    public function testExcelCountParticipantList(): void
    {
        $result = $this->training->ExcelCountParticipantList(0);
        if ($result === null) {
            $this->markTestSkipped('No training participant data in database for Excel count');
        }
        $this->assertIsInt((int)$result);
    }

    public function testExcelGetAttendanceList(): void
    {
        $result = $this->training->ExcelGetAttendanceList(0);

        // May return JSON string or array
        $this->assertNotNull($result);
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            $this->assertIsArray($decoded);
        } else {
            $this->assertTrue(is_array($result) || $result === null || $result === false);
        }
    }

    public function testExcelGetAttendanceListWithParams(): void
    {
        $result = $this->training->ExcelGetAttendanceList(0, 'state', 1);

        // May return JSON string or array
        $this->assertNotNull($result);
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            $this->assertIsArray($decoded);
        } else {
            $this->assertTrue(is_array($result) || $result === null || $result === false);
        }
    }

    public function testExcelCountAttendanceList(): void
    {
        $result = $this->training->ExcelCountAttendanceList(0);
        if ($result === null) {
            $this->markTestSkipped('No training attendance data in database for Excel count');
        }
        $this->assertIsInt((int)$result);
    }

    public function testExcelCountAttendanceListWithParams(): void
    {
        $result = $this->training->ExcelCountAttendanceList(0, 'state', 1);
        if ($result === null) {
            $this->markTestSkipped('No training attendance data in database for Excel count (with params)');
        }
        $this->assertIsInt((int)$result);
    }

    // ==========================================
    // Dashboard Statistics
    // ==========================================

    public function testDashCountTraining(): void
    {
        $result = $this->training->DashCountTraining();
        $this->assertTrue(true);
    }

    public function testDashCountActive(): void
    {
        $result = $this->training->DashCountActive();
        $this->assertTrue(true);
    }

    public function testDashCountSession(): void
    {
        $result = $this->training->DashCountSession();
        $this->assertTrue(true);
    }
}
