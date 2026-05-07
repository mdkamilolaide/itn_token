<?php

namespace Tests\Unit\Controllers\Training;

use Training\Training;

require_once __DIR__ . '/TrainingTestCase.php';

/**
 * Unit Test: Training Controller
 * 
 * Tests the training controller methods in isolation
 */
class TrainingControllerTest extends TrainingTestCase
{
    public function testTrainingCrudToggleAndLists(): void
    {
        $this->requireSchema([
            'tra_training' => ['trainingid', 'title', 'geo_location', 'location_id', 'guid', 'active', 'description', 'start_date', 'end_date', 'participant_count'],
            'sys_geo_level' => ['geo_level', 'geo_value', 'geo_table'],
            'tra_session' => ['sessionid', 'trainingid', 'title', 'guid', 'session_date'],
        ]);

        $controller = new Training();
        $this->seedGeoLevel('ward', 3, 'ms_geo_ward');

        $trainingId = $controller->CreateTraining('Training 1', 'ward', 1, 'Desc', '2099-01-01', '2099-01-02');
        $this->assertIsNumeric($trainingId);
        $this->recordCleanup('tra_training', 'trainingid', $trainingId);

        $updated = $controller->UpdateTraining('Training 1 Updated', 'ward', 1, 'Desc 2', '2099-01-03', '2099-01-04', $trainingId);
        $this->assertTrue((bool) $updated);

        $toggleOff = $controller->ToggleTraining($trainingId);
        $this->assertTrue((bool) $toggleOff);
        $toggleOn = $controller->ToggleTraining($trainingId);
        $this->assertTrue((bool) $toggleOn);

        $generic = $controller->getGenericTraining('ward', 1);
        $this->assertNotEmpty($generic);

        $filtered = $controller->getFilteredTraining('ward');
        $this->assertNotEmpty($filtered);

        $sessionId = $controller->CreateSession($trainingId, 'Session 1', '2099-01-05');
        $this->assertIsNumeric($sessionId);
        $this->recordCleanup('tra_session', 'sessionid', $sessionId);

        $sessions = $controller->getGenericSession($trainingId);
        $this->assertNotEmpty($sessions);
    }

    public function testDashboardCounts(): void
    {
        $this->requireSchema([
            'tra_training' => ['trainingid', 'active'],
            'tra_session' => ['sessionid', 'trainingid'],
        ]);

        $controller = new Training();

        $trainingId = $this->seedTraining([
            'title' => 'Training',
            'geo_location' => 'ward',
            'location_id' => 1,
            'guid' => md5(uniqid('', true)),
            'active' => 1,
            'description' => 'Desc',
            'start_date' => '2099-01-01',
            'end_date' => '2099-01-02',
            'participant_count' => 0,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $this->seedSession([
            'trainingid' => $trainingId,
            'title' => 'Session',
            'guid' => md5(uniqid('', true)),
            'session_date' => '2099-01-05',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $trainings = $controller->DashCountTraining();
        $this->assertNotEmpty($trainings);

        $active = $controller->DashCountActive();
        $this->assertNotEmpty($active);

        $sessions = $controller->DashCountSession();
        $this->assertNotEmpty($sessions);
    }
}
