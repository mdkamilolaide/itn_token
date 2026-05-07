<?php

/**
 * Device Fixture Data
 * 
 * Sample device data for testing device allocation and tracking
 * Usage: $devices = require(__DIR__ . '/devices.php');
 */

return [
    'device_available' => [
        'deviceid' => 'TAB-001',
        'device_type' => 'TABLET',
        'brand' => 'Samsung',
        'model' => 'Galaxy Tab A8',
        'serial_number' => 'SN12345678',
        'imei' => '123456789012345',
        'status' => 'AVAILABLE',
        'condition' => 'GOOD',
        'purchase_date' => '2024-12-01',
        'warranty_expiry' => '2025-12-01',
        'assigned_to' => null,
        'assigned_date' => null,
        'location' => 'WAREHOUSE'
    ],
    
    'device_allocated' => [
        'deviceid' => 'TAB-002',
        'device_type' => 'TABLET',
        'brand' => 'Samsung',
        'model' => 'Galaxy Tab A8',
        'serial_number' => 'SN23456789',
        'imei' => '234567890123456',
        'status' => 'ALLOCATED',
        'condition' => 'GOOD',
        'purchase_date' => '2024-12-01',
        'warranty_expiry' => '2025-12-01',
        'assigned_to' => 'mobilizer.ado',
        'assigned_date' => '2025-01-10',
        'location' => 'FIELD'
    ],
    
    'device_in_use' => [
        'deviceid' => 'TAB-003',
        'device_type' => 'TABLET',
        'brand' => 'Samsung',
        'model' => 'Galaxy Tab A8',
        'serial_number' => 'SN34567890',
        'imei' => '345678901234567',
        'status' => 'IN_USE',
        'condition' => 'GOOD',
        'purchase_date' => '2024-12-01',
        'warranty_expiry' => '2025-12-01',
        'assigned_to' => 'distributor.ado',
        'assigned_date' => '2025-01-05',
        'location' => 'FIELD'
    ],
    
    'device_maintenance' => [
        'deviceid' => 'TAB-004',
        'device_type' => 'TABLET',
        'brand' => 'Samsung',
        'model' => 'Galaxy Tab A8',
        'serial_number' => 'SN45678901',
        'imei' => '456789012345678',
        'status' => 'MAINTENANCE',
        'condition' => 'FAIR',
        'purchase_date' => '2024-11-01',
        'warranty_expiry' => '2025-11-01',
        'assigned_to' => null,
        'assigned_date' => null,
        'location' => 'WORKSHOP',
        'notes' => 'Screen replacement required'
    ],
    
    'device_damaged' => [
        'deviceid' => 'TAB-005',
        'device_type' => 'TABLET',
        'brand' => 'Samsung',
        'model' => 'Galaxy Tab A7',
        'serial_number' => 'SN56789012',
        'imei' => '567890123456789',
        'status' => 'DAMAGED',
        'condition' => 'POOR',
        'purchase_date' => '2024-06-01',
        'warranty_expiry' => '2025-06-01',
        'assigned_to' => null,
        'assigned_date' => null,
        'location' => 'WAREHOUSE',
        'notes' => 'Water damage - beyond repair'
    ],
    
    'phone_available' => [
        'deviceid' => 'PHN-001',
        'device_type' => 'PHONE',
        'brand' => 'Nokia',
        'model' => 'G21',
        'serial_number' => 'SN67890123',
        'imei' => '678901234567890',
        'status' => 'AVAILABLE',
        'condition' => 'GOOD',
        'purchase_date' => '2024-12-15',
        'warranty_expiry' => '2025-12-15',
        'assigned_to' => null,
        'assigned_date' => null,
        'location' => 'WAREHOUSE'
    ]
];
