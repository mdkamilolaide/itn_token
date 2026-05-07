<?php

/**
 * Forms Fixture Data
 * 
 * Sample form data and structures for testing form submission workflows
 * Usage: $forms = require(__DIR__ . '/forms.php');
 */

return [
    'registration_form' => [
        'form_id' => 'FORM-REG-001',
        'form_name' => 'Household Registration Form',
        'form_type' => 'REGISTRATION',
        'version' => '2.0',
        'status' => 'ACTIVE',
        'fields' => [
            'hh_head_name' => ['type' => 'text', 'required' => true, 'label' => 'Household Head Name'],
            'hh_head_phone' => ['type' => 'phone', 'required' => true, 'label' => 'Phone Number'],
            'num_children_under5' => ['type' => 'number', 'required' => true, 'label' => 'Children Under 5'],
            'num_pregnant_women' => ['type' => 'number', 'required' => true, 'label' => 'Pregnant Women'],
            'address' => ['type' => 'textarea', 'required' => false, 'label' => 'Address']
        ]
    ],
    
    'distribution_form' => [
        'form_id' => 'FORM-DIST-001',
        'form_name' => 'Distribution Record Form',
        'form_type' => 'DISTRIBUTION',
        'version' => '1.5',
        'status' => 'ACTIVE',
        'fields' => [
            'household_id' => ['type' => 'text', 'required' => true, 'label' => 'Household ID'],
            'items_distributed' => ['type' => 'number', 'required' => true, 'label' => 'Items Distributed'],
            'recipient_name' => ['type' => 'text', 'required' => true, 'label' => 'Recipient Name'],
            'recipient_signature' => ['type' => 'signature', 'required' => false, 'label' => 'Signature'],
            'notes' => ['type' => 'textarea', 'required' => false, 'label' => 'Notes']
        ]
    ],
    
    'monitoring_form' => [
        'form_id' => 'FORM-MON-001',
        'form_name' => 'Monitoring Visit Form',
        'form_type' => 'MONITORING',
        'version' => '1.0',
        'status' => 'ACTIVE',
        'fields' => [
            'household_id' => ['type' => 'text', 'required' => true, 'label' => 'Household ID'],
            'visit_type' => ['type' => 'select', 'required' => true, 'label' => 'Visit Type', 
                             'options' => ['FOLLOW_UP', 'VERIFICATION', 'SPOT_CHECK']],
            'findings' => ['type' => 'textarea', 'required' => true, 'label' => 'Findings'],
            'issues_identified' => ['type' => 'checkbox', 'required' => false, 'label' => 'Issues Identified'],
            'photos' => ['type' => 'file', 'required' => false, 'label' => 'Photos']
        ]
    ],
    
    'form_submission_valid' => [
        'submission_id' => 'SUB-2025-0001',
        'form_id' => 'FORM-REG-001',
        'submitted_by' => 'mobilizer.ado',
        'submission_date' => '2025-01-16 14:30:00',
        'status' => 'SUBMITTED',
        'data' => [
            'hh_head_name' => 'John Doe',
            'hh_head_phone' => '08012345678',
            'num_children_under5' => 3,
            'num_pregnant_women' => 1,
            'address' => '12 Main Street'
        ],
        'validation_status' => 'VALID',
        'gps_coordinates' => ['lat' => 7.123456, 'lon' => 8.654321]
    ],
    
    'form_submission_invalid' => [
        'submission_id' => 'SUB-2025-0002',
        'form_id' => 'FORM-REG-001',
        'submitted_by' => 'mobilizer.ado',
        'submission_date' => '2025-01-16 15:45:00',
        'status' => 'REJECTED',
        'data' => [
            'hh_head_name' => '',
            'hh_head_phone' => 'invalid',
            'num_children_under5' => -1,
            'num_pregnant_women' => null,
            'address' => ''
        ],
        'validation_status' => 'INVALID',
        'validation_errors' => [
            'hh_head_name' => 'Required field is empty',
            'hh_head_phone' => 'Invalid phone number format',
            'num_children_under5' => 'Value must be 0 or greater'
        ]
    ],
    
    'form_submission_draft' => [
        'submission_id' => 'SUB-2025-0003',
        'form_id' => 'FORM-REG-001',
        'submitted_by' => 'mobilizer.agatu',
        'submission_date' => '2025-01-17 10:00:00',
        'status' => 'DRAFT',
        'data' => [
            'hh_head_name' => 'Jane Smith',
            'hh_head_phone' => '08023456789',
            'num_children_under5' => null,
            'num_pregnant_women' => null,
            'address' => null
        ],
        'validation_status' => 'INCOMPLETE'
    ]
];
