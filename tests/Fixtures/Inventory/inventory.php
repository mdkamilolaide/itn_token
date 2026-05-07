<?php

/**
 * Inventory Fixture Data
 * 
 * Sample inventory/stock data for testing
 * Usage: $inventory = require(__DIR__ . '/inventory.php');
 */

return [
    'item_smc_tablets' => [
        'item_id' => 'INV-SMC-001',
        'item_name' => 'SMC Tablets (Sulphadoxine-Pyrimethamine)',
        'item_type' => 'MEDICINE',
        'category' => 'SMC',
        'unit' => 'TABLET',
        'quantity_in_stock' => 50000,
        'quantity_allocated' => 15000,
        'quantity_available' => 35000,
        'minimum_stock_level' => 10000,
        'reorder_level' => 20000,
        'unit_price' => 12.50,
        'batch_number' => 'BATCH-SMC-2025-01',
        'expiry_date' => '2026-12-31',
        'location' => 'CENTRAL_WAREHOUSE',
        'status' => 'AVAILABLE'
    ],
    
    'item_vitamin_a' => [
        'item_id' => 'INV-VIT-001',
        'item_name' => 'Vitamin A Capsules (200,000 IU)',
        'item_type' => 'SUPPLEMENT',
        'category' => 'NUTRITION',
        'unit' => 'CAPSULE',
        'quantity_in_stock' => 100000,
        'quantity_allocated' => 25000,
        'quantity_available' => 75000,
        'minimum_stock_level' => 20000,
        'reorder_level' => 40000,
        'unit_price' => 5.00,
        'batch_number' => 'BATCH-VITA-2025-01',
        'expiry_date' => '2027-06-30',
        'location' => 'CENTRAL_WAREHOUSE',
        'status' => 'AVAILABLE'
    ],
    
    'item_low_stock' => [
        'item_id' => 'INV-MOS-001',
        'item_name' => 'Mosquito Nets (LLIN)',
        'item_type' => 'EQUIPMENT',
        'category' => 'MALARIA_PREVENTION',
        'unit' => 'PIECE',
        'quantity_in_stock' => 8000,
        'quantity_allocated' => 6000,
        'quantity_available' => 2000,
        'minimum_stock_level' => 5000,
        'reorder_level' => 10000,
        'unit_price' => 35.00,
        'batch_number' => 'BATCH-LLIN-2024-12',
        'expiry_date' => null,
        'location' => 'REGIONAL_WAREHOUSE_NORTH',
        'status' => 'LOW_STOCK'
    ],
    
    'item_expired' => [
        'item_id' => 'INV-SMC-999',
        'item_name' => 'SMC Tablets (Expired)',
        'item_type' => 'MEDICINE',
        'category' => 'SMC',
        'unit' => 'TABLET',
        'quantity_in_stock' => 5000,
        'quantity_allocated' => 0,
        'quantity_available' => 0,
        'minimum_stock_level' => 0,
        'reorder_level' => 0,
        'unit_price' => 12.50,
        'batch_number' => 'BATCH-SMC-2023-12',
        'expiry_date' => '2024-12-31',
        'location' => 'QUARANTINE',
        'status' => 'EXPIRED'
    ],
    
    'item_out_of_stock' => [
        'item_id' => 'INV-INS-001',
        'item_name' => 'Insecticide Spray',
        'item_type' => 'EQUIPMENT',
        'category' => 'VECTOR_CONTROL',
        'unit' => 'LITER',
        'quantity_in_stock' => 0,
        'quantity_allocated' => 0,
        'quantity_available' => 0,
        'minimum_stock_level' => 500,
        'reorder_level' => 1000,
        'unit_price' => 25.00,
        'batch_number' => null,
        'expiry_date' => null,
        'location' => 'CENTRAL_WAREHOUSE',
        'status' => 'OUT_OF_STOCK'
    ],
    
    'item_supplies' => [
        'item_id' => 'INV-SUP-001',
        'item_name' => 'Data Collection Forms',
        'item_type' => 'SUPPLIES',
        'category' => 'STATIONERY',
        'unit' => 'BUNDLE',
        'quantity_in_stock' => 200,
        'quantity_allocated' => 50,
        'quantity_available' => 150,
        'minimum_stock_level' => 50,
        'reorder_level' => 100,
        'unit_price' => 15.00,
        'batch_number' => 'FORMS-2025-01',
        'expiry_date' => null,
        'location' => 'CENTRAL_WAREHOUSE',
        'status' => 'AVAILABLE'
    ]
];
