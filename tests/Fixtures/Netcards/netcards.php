<?php

/**
 * Netcard Fixture Data
 * 
 * Sample netcard data for testing netcard allocation and tracking
 * Usage: $netcards = require(__DIR__ . '/netcards.php');
 */

return [
    'netcard_available' => [
        'netcard_id' => 'NC-2025-0001',
        'serial_number' => 'NC0001234567',
        'batch_number' => 'BATCH-2025-01',
        'network_provider' => 'MTN',
        'phone_number' => '07012345678',
        'status' => 'AVAILABLE',
        'activation_status' => 'NOT_ACTIVATED',
        'allocated_to' => null,
        'allocation_date' => null,
        'location' => 'WAREHOUSE',
        'balance' => 0.00,
        'expiry_date' => '2026-01-01'
    ],
    
    'netcard_allocated' => [
        'netcard_id' => 'NC-2025-0002',
        'serial_number' => 'NC0002345678',
        'batch_number' => 'BATCH-2025-01',
        'network_provider' => 'MTN',
        'phone_number' => '07023456789',
        'status' => 'ALLOCATED',
        'activation_status' => 'ACTIVATED',
        'allocated_to' => 'mobilizer.ado',
        'allocation_date' => '2025-01-10',
        'location' => 'FIELD',
        'balance' => 500.00,
        'expiry_date' => '2026-01-01'
    ],
    
    'netcard_in_use' => [
        'netcard_id' => 'NC-2025-0003',
        'serial_number' => 'NC0003456789',
        'batch_number' => 'BATCH-2025-01',
        'network_provider' => 'AIRTEL',
        'phone_number' => '08034567890',
        'status' => 'IN_USE',
        'activation_status' => 'ACTIVATED',
        'allocated_to' => 'distributor.ado',
        'allocation_date' => '2025-01-05',
        'location' => 'FIELD',
        'balance' => 250.00,
        'expiry_date' => '2026-01-01'
    ],
    
    'netcard_expired' => [
        'netcard_id' => 'NC-2024-9999',
        'serial_number' => 'NC9999999999',
        'batch_number' => 'BATCH-2024-12',
        'network_provider' => 'GLO',
        'phone_number' => '08199999999',
        'status' => 'EXPIRED',
        'activation_status' => 'DEACTIVATED',
        'allocated_to' => 'old.mobilizer',
        'allocation_date' => '2024-06-01',
        'location' => 'RETURNED',
        'balance' => 0.00,
        'expiry_date' => '2024-12-31'
    ],
    
    'netcard_blocked' => [
        'netcard_id' => 'NC-2025-0004',
        'serial_number' => 'NC0004567890',
        'batch_number' => 'BATCH-2025-01',
        'network_provider' => '9MOBILE',
        'phone_number' => '08145678901',
        'status' => 'BLOCKED',
        'activation_status' => 'BLOCKED',
        'allocated_to' => null,
        'allocation_date' => null,
        'location' => 'WAREHOUSE',
        'balance' => 0.00,
        'expiry_date' => '2026-01-01',
        'notes' => 'Reported lost/stolen'
    ],
    
    'netcard_bulk_1' => [
        'netcard_id' => 'NC-2025-0101',
        'serial_number' => 'NC0101234567',
        'batch_number' => 'BATCH-2025-02',
        'network_provider' => 'MTN',
        'phone_number' => '07091234567',
        'status' => 'AVAILABLE',
        'activation_status' => 'NOT_ACTIVATED',
        'allocated_to' => null,
        'allocation_date' => null,
        'location' => 'WAREHOUSE',
        'balance' => 0.00,
        'expiry_date' => '2026-02-01'
    ],
    
    'netcard_bulk_2' => [
        'netcard_id' => 'NC-2025-0102',
        'serial_number' => 'NC0102345678',
        'batch_number' => 'BATCH-2025-02',
        'network_provider' => 'MTN',
        'phone_number' => '07092345678',
        'status' => 'AVAILABLE',
        'activation_status' => 'NOT_ACTIVATED',
        'allocated_to' => null,
        'allocation_date' => null,
        'location' => 'WAREHOUSE',
        'balance' => 0.00,
        'expiry_date' => '2026-02-01'
    ]
];
