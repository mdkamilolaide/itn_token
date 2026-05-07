<?php

/**
 * User fixtures for testing
 * Returns sample user data for test scenarios
 */

return [
    'admin' => [
        'userid' => 1001,
        'loginid' => 'admin.user',
        'username' => 'System Admin',
        'password' => 'Admin@2026',
        'roleid' => 1,
        'role' => 'System Administrator',
        'geo_level' => 'state',
        'geo_level_id' => 10,
        'location' => 'BENUE',
        'active' => 1,
    ],
    'ict4d_staff' => [
        'userid' => 1002,
        'loginid' => 'ict4d.benue',
        'username' => 'ICT4D Staff',
        'password' => 'Admin@2026',
        'roleid' => 2,
        'role' => 'ICT4D Staff',
        'geo_level' => 'state',
        'geo_level_id' => 10,
        'location' => 'BENUE',
        'active' => 1,
    ],
    'field_mobilizer' => [
        'userid' => 1003,
        'loginid' => 'mobilizer.ado',
        'username' => 'Field Mobilizer',
        'password' => 'Admin@2026',
        'roleid' => 10,
        'role' => 'HH Mobilizer',
        'geo_level' => 'ward',
        'geo_level_id' => 1000,
        'location' => 'Akpoge/Ogbilolo',
        'active' => 1,
    ],
    'inactive_user' => [
        'userid' => 9999,
        'loginid' => 'inactive.user',
        'username' => 'Inactive User',
        'password' => 'TestPass123',
        'roleid' => 10,
        'role' => 'HH Mobilizer',
        'geo_level' => 'ward',
        'geo_level_id' => 1000,
        'location' => 'Akpoge/Ogbilolo',
        'active' => 0,
    ],
];
