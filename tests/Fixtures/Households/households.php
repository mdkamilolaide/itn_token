<?php

/**
 * Household Fixture Data
 * 
 * Sample household data for testing registration and distribution
 * Usage: $households = require(__DIR__ . '/households.php');
 */

return [
    'household_1' => [
        'hhid' => 'HH-2025-0001',
        'hh_head_name' => 'John Doe',
        'hh_head_phone' => '08012345678',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'lga_id' => 3001,
        'lga_name' => 'Ado',
        'ward_id' => 4001,
        'ward_name' => 'Akpoge',
        'settlement_name' => 'Akpoge Village',
        'address' => '12 Main Street',
        'num_children_under5' => 3,
        'num_pregnant_women' => 1,
        'household_size' => 6,
        'status' => 'ACTIVE',
        'registration_date' => '2025-01-15',
        'registered_by' => 'mobilizer.ado'
    ],
    
    'household_2' => [
        'hhid' => 'HH-2025-0002',
        'hh_head_name' => 'Jane Smith',
        'hh_head_phone' => '08023456789',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'lga_id' => 3002,
        'lga_name' => 'Agatu',
        'ward_id' => 4002,
        'ward_name' => 'Apa',
        'settlement_name' => 'Apa Town',
        'address' => '45 Market Road',
        'num_children_under5' => 2,
        'num_pregnant_women' => 0,
        'household_size' => 5,
        'status' => 'ACTIVE',
        'registration_date' => '2025-01-16',
        'registered_by' => 'mobilizer.agatu'
    ],
    
    'household_3' => [
        'hhid' => 'HH-2025-0003',
        'hh_head_name' => 'Ahmed Ibrahim',
        'hh_head_phone' => '08034567890',
        'state_id' => 2002,
        'state_name' => 'Kano',
        'lga_id' => 3003,
        'lga_name' => 'Ajingi',
        'ward_id' => 4003,
        'ward_name' => 'Ajingi Central',
        'settlement_name' => 'Ajingi',
        'address' => '78 Palace Avenue',
        'num_children_under5' => 4,
        'num_pregnant_women' => 2,
        'household_size' => 8,
        'status' => 'ACTIVE',
        'registration_date' => '2025-01-17',
        'registered_by' => 'mobilizer.kano'
    ],
    
    'household_incomplete' => [
        'hhid' => 'HH-2025-0004',
        'hh_head_name' => 'Mary Johnson',
        'hh_head_phone' => '08045678901',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'lga_id' => 3001,
        'lga_name' => 'Ado',
        'ward_id' => 4001,
        'ward_name' => 'Akpoge',
        'settlement_name' => 'Akpoge Village',
        'address' => null,
        'num_children_under5' => null,
        'num_pregnant_women' => null,
        'household_size' => null,
        'status' => 'INCOMPLETE',
        'registration_date' => '2025-01-18',
        'registered_by' => 'mobilizer.ado'
    ],
    
    'household_inactive' => [
        'hhid' => 'HH-2024-9999',
        'hh_head_name' => 'Old Household',
        'hh_head_phone' => '08099999999',
        'state_id' => 2001,
        'state_name' => 'Benue',
        'lga_id' => 3001,
        'lga_name' => 'Ado',
        'ward_id' => 4001,
        'ward_name' => 'Akpoge',
        'settlement_name' => 'Old Village',
        'address' => 'Inactive Address',
        'num_children_under5' => 0,
        'num_pregnant_women' => 0,
        'household_size' => 0,
        'status' => 'INACTIVE',
        'registration_date' => '2024-06-01',
        'registered_by' => 'old.mobilizer'
    ]
];
