<?php

/**
 * Training Fixture Data
 * 
 * Sample training program and session data for testing
 * Usage: $trainings = require(__DIR__ . '/trainings.php');
 */

return [
    'training_active' => [
        'training_id' => 'TRN-2025-001',
        'training_name' => 'Mobilizer Training - Batch 1',
        'training_type' => 'MOBILIZER',
        'venue' => 'Benue State Training Center',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'start_date' => '2025-01-20',
        'end_date' => '2025-01-24',
        'duration_days' => 5,
        'status' => 'ONGOING',
        'capacity' => 50,
        'enrolled' => 48,
        'attended' => 45,
        'completed' => 0,
        'trainer_id' => 'trainer.benue',
        'coordinator_id' => 'coordinator.benue',
        'created_by' => 'admin.user',
        'created_date' => '2025-01-10'
    ],
    
    'training_planned' => [
        'training_id' => 'TRN-2025-002',
        'training_name' => 'Distributor Training - Kano',
        'training_type' => 'DISTRIBUTOR',
        'venue' => 'Kano State Auditorium',
        'state_id' => 2002,
        'state_name' => 'Kano',
        'start_date' => '2025-02-05',
        'end_date' => '2025-02-08',
        'duration_days' => 4,
        'status' => 'PLANNED',
        'capacity' => 60,
        'enrolled' => 35,
        'attended' => 0,
        'completed' => 0,
        'trainer_id' => 'trainer.kano',
        'coordinator_id' => 'coordinator.kano',
        'created_by' => 'admin.user',
        'created_date' => '2025-01-15'
    ],
    
    'training_completed' => [
        'training_id' => 'TRN-2024-050',
        'training_name' => 'TOT - Training of Trainers',
        'training_type' => 'TOT',
        'venue' => 'Federal Training Institute',
        'state_id' => null,
        'state_name' => 'National',
        'start_date' => '2024-12-10',
        'end_date' => '2024-12-14',
        'duration_days' => 5,
        'status' => 'COMPLETED',
        'capacity' => 30,
        'enrolled' => 28,
        'attended' => 27,
        'completed' => 25,
        'trainer_id' => 'master.trainer',
        'coordinator_id' => 'national.coordinator',
        'created_by' => 'admin.user',
        'created_date' => '2024-11-20',
        'completed_date' => '2024-12-14'
    ],
    
    'training_with_participants' => [
        'training_id' => 'TRN-2025-003',
        'training_name' => 'Data Quality Training',
        'training_type' => 'DATA_QUALITY',
        'venue' => 'Ado Conference Hall',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'start_date' => '2025-01-22',
        'end_date' => '2025-01-23',
        'duration_days' => 2,
        'status' => 'ONGOING',
        'capacity' => 25,
        'enrolled' => 24,
        'attended' => 22,
        'completed' => 0,
        'trainer_id' => 'trainer.benue',
        'coordinator_id' => 'coordinator.ado',
        'created_by' => 'admin.user',
        'created_date' => '2025-01-15',
        'participants' => [
            ['userid' => 'mobilizer.ado', 'attendance' => 'PRESENT', 'score' => null],
            ['userid' => 'mobilizer.agatu', 'attendance' => 'PRESENT', 'score' => null],
            ['userid' => 'distributor.ado', 'attendance' => 'ABSENT', 'score' => null]
        ]
    ],
    
    'training_cancelled' => [
        'training_id' => 'TRN-2025-099',
        'training_name' => 'Cancelled Training',
        'training_type' => 'OTHER',
        'venue' => 'TBD',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'start_date' => '2025-01-15',
        'end_date' => '2025-01-17',
        'duration_days' => 3,
        'status' => 'CANCELLED',
        'capacity' => 40,
        'enrolled' => 10,
        'attended' => 0,
        'completed' => 0,
        'trainer_id' => null,
        'coordinator_id' => 'coordinator.benue',
        'created_by' => 'admin.user',
        'created_date' => '2025-01-05',
        'notes' => 'Cancelled due to insufficient enrollment'
    ]
];
