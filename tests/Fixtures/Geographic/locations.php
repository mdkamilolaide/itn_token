<?php

/**
 * Geographic location fixtures for testing
 * Predefined locations for consistent testing
 */

return [
    'benue_state' => [
        'id' => 10,
        'geo_level' => 'state',
        'geo_level_id' => 7,
        'title' => 'BENUE',
        'geo_string' => 'Benue',
    ],
    'kano_state' => [
        'id' => 20,
        'geo_level' => 'state',
        'geo_level_id' => 10,
        'title' => 'KANO',
        'geo_string' => 'Kano',
    ],
    'ado_lga' => [
        'id' => 100,
        'geo_level' => 'lga',
        'geo_level_id' => 119,
        'title' => 'ADO',
        'geo_string' => 'BENUE > ADO',
    ],
    'akpoge_ward' => [
        'id' => 1000,
        'geo_level' => 'ward',
        'geo_level_id' => 2000,
        'title' => 'Akpoge/Ogbilolo',
        'geo_string' => 'BENUE > ADO > Akpoge/Ogbilolo',
    ],
    'apa_ward' => [
        'id' => 1001,
        'geo_level' => 'ward',
        'geo_level_id' => 2001,
        'title' => 'Apa',
        'geo_string' => 'BENUE > ADO > Apa',
    ],
];
