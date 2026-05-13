<?php

/*
 *  Page Loader
 *
 *  Routes ?module=…&submodule=… to the matching template under pages/.
 *  Dispatch is a static array literal so opcache compiles it once and each
 *  request resolves the include path with two O(1) hash lookups.
 */

$module    = CleanData('module');
$submodule = CleanData('submodule');

$routes = [
    'sample' => [
        'submodules' => [
            'sample'    => 'pages/sample.php',
            'table'     => 'pages/sample/table.php',
            'datatable' => 'pages/sample/datatable.php',
        ],
        // Unknown sample submodule renders nothing (legacy behavior).
        'default' => null,
    ],
    'dashboard' => [
        'submodules' => [
            'home' => 'pages/dashboard/home.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'admin' => [
        'submodules' => [
            'log'       => 'pages/admin/log.php',
            'provision' => 'pages/admin/provision.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'device' => [
        'submodules' => [
            'registry'   => 'pages/device/registry.php',
            'loginlog'   => 'pages/device/loginlog.php',
            'allocation' => 'pages/device/deviceallocation.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'users' => [
        'submodules' => [
            ''      => 'pages/users/dashboard.php',
            'list'  => 'pages/users/list.php',
            'group' => 'pages/users/group.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'activity' => [
        'submodules' => [
            ''          => 'pages/activity/dashboard.php',
            'reporting' => 'pages/activity/dashboard.php',
            'list'      => 'pages/activity/list.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'monitoring' => [
        'submodules' => [
            '' => 'pages/monitoring/home.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'reporting' => [
        'submodules' => [
            '' => 'pages/reporting/home.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'netcard' => [
        'submodules' => [
            ''           => 'pages/netcard/dashboard.php',
            'movement'   => 'pages/netcard/movement.php',
            'allocation' => 'pages/netcard/allocation.php',
            'unlock'     => 'pages/netcard/unlock.php',
            'pushed'     => 'pages/netcard/pushed.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'eolin' => [
        'submodules' => [
            '' => 'pages/netcard/dashboard.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'mobilization' => [
        'submodules' => [
            ''          => 'pages/mobilization/dashboard.php',
            'dashboard' => 'pages/mobilization/dashboard.php',
            'list'      => 'pages/mobilization/dashboard.php',
            'reporting' => 'pages/mobilization/dashboard.php',
            'map'       => 'pages/mobilization/map.php',
            'microlist' => 'pages/mobilization/microlist.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'distribution' => [
        'submodules' => [
            ''            => 'pages/distribution/dashboard.php',
            'reporting'   => 'pages/distribution/dashboard.php',
            'dplist'      => 'pages/distribution/dplist.php',
            'list'        => 'pages/distribution/list.php',
            'unredeemnet' => 'pages/distribution/unredeemnet.php',
        ],
        'default' => 'pages/home/pagenotfound.php',
    ],
    'smc' => [
        'submodules' => [
            'visit'                         => 'pages/smc/visit.php',
            'drugadministration'            => 'pages/smc/visit.php',
            'cohorttracking'                => 'pages/smc/visit.php',
            'balance'                       => 'pages/smc/visit.php',
            'logisticsallocation'           => 'pages/smc/logistics/allocation.php',
            'logisticsavailabilitycheck'    => 'pages/smc/logistics/availabilitycheck.php',
            'logisticsinboundwarehouse'     => 'pages/smc/logistics/inboundwarehouse.php',
            'logisticsstockbatchmanagement' => 'pages/smc/logistics/stockbatchmanagement.php',
            'logisticsshipment'             => 'pages/smc/logistics/shipment.php',
            'logisticsmovement'             => 'pages/smc/logistics/movement.php',
        ],
        // smc falls back to its own dashboard, not pagenotfound.
        'default' => 'pages/smc/dashboard.php',
    ],
];

if (array_key_exists($module, $routes)) {
    $route = $routes[$module];
    $file  = array_key_exists($submodule, $route['submodules'])
        ? $route['submodules'][$submodule]
        : $route['default'];
} elseif ($module !== '') {
    $file = 'pages/home/pagenotfound.php';
} else {
    $file = 'pages/home/home.php';
}

if ($file !== null) {
    if (file_exists($file)) {
        include $file;
    } else {
        include 'pages/home/pagenotfound.php';
    }
}
