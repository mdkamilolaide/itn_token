<?php

/**
 * PHPUnit Bootstrap File
 * 
 * This file is loaded before running tests.
 * It sets up autoloading and any required global configuration.
 */

// Composer autoload (includes PHPUnit). Try project root vendor first, then lib/vendor fallback.
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../lib/vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

if (!$autoloadLoaded) {
    fwrite(STDERR, "Composer autoload not found. Run composer install or ensure vendor/autoload.php exists.\n");
    exit(1);
}

// Polyfill getallheaders() for CLI/testing environments
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (0 === strpos($name, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

// Application autoload
require_once __DIR__ . '/../lib/autoload.php';

// Seed minimal fixtures into the test database for Feature tests
if (php_sapi_name() === 'cli' && defined('TEST_BASE_DIR')) {
    try {
        $db = GetMysqlDatabase();
        // Helper to check if table has any rows
        $hasRows = function(string $sql) use ($db) {
            $rows = [];
            try { $rows = $db->DataTable($sql); } catch (Exception $e) { return false; }
            return count($rows) > 0;
        };

        if (!$hasRows('SELECT LgaId AS val FROM ms_geo_lga LIMIT 1')) {
            $db->Execute("INSERT INTO ms_geo_lga (LgaId, Fullname) VALUES (?, ?)", [1, 'TEST LGA']);
        }
        if (!$hasRows('SELECT wardid AS val FROM ms_geo_ward LIMIT 1')) {
            $db->Execute("INSERT INTO ms_geo_ward (wardid, ward) VALUES (?, ?)", [1, 'TEST WARD']);
        }
        if (!$hasRows('SELECT dpid AS val FROM ms_geo_dp LIMIT 1')) {
            $db->Execute("INSERT INTO ms_geo_dp (dpid, dp) VALUES (?, ?)", [1, 'TEST DP']);
        }
        if (!$hasRows('SELECT comid AS val FROM ms_geo_comm LIMIT 1')) {
            $db->Execute("INSERT INTO ms_geo_comm (comid, community) VALUES (?, ?)", [1, 'TEST COMMUNITY']);
        }
        if (!$hasRows('SELECT userid AS val FROM usr_login LIMIT 1')) {
            $db->Execute("INSERT INTO usr_login (userid, loginid, guid, platform, priority, geo_level, geo_level_id) VALUES (?, ?, ?, ?, ?, ?, ?)", [1, 'admin', 'test-guid', 'mobile', 1, 'state', 1]);
        }
        if (!$hasRows('SELECT periodid AS val FROM smc_period LIMIT 1')) {
            $db->Execute("INSERT INTO smc_period (periodid, title) VALUES (?, ?)", [1, 'TEST PERIOD']);
        }
        // Additional minimal fixtures
        if (!$hasRows('SELECT id AS val FROM sys_default_settings WHERE id = 1 LIMIT 1')) {
            $db->Execute("INSERT INTO sys_default_settings (id, stateid) VALUES (?, ?)", [1, 1]);
        }
        if (!$hasRows('SELECT bank_code AS val FROM sys_bank_code LIMIT 1')) {
            $db->Execute("INSERT INTO sys_bank_code (bank_code, bank_name) VALUES (?, ?)", ['001', 'TEST BANK']);
        }
        if (!$hasRows('SELECT geo_level_id AS val FROM sys_geo_codex LIMIT 1')) {
            $db->Execute("INSERT INTO sys_geo_codex (geo_level, geo_level_id, stateid, lgaid, wardid) VALUES (?, ?, ?, ?, ?)", ['dp', 1, 1, 1, 1]);
        }
        if (!$hasRows('SELECT trainingid AS val FROM tra_training LIMIT 1')) {
            $db->Execute("INSERT INTO tra_training (trainingid, title) VALUES (?, ?)", [1, 'TEST TRAINING']);
        }

        // Load fixtures files if they exist
        $fixturesDir = __DIR__ . '/Fixtures';
        // Geographic
        if (file_exists($fixturesDir . '/Geographic/locations.php')) {
            $locations = require $fixturesDir . '/Geographic/locations.php';
            foreach ($locations as $key => $loc) {
                try {
                    if (($loc['geo_level'] ?? '') === 'state') {
                        if (!$hasRows("SELECT id AS val FROM ms_state WHERE id = " . intval($loc['id']) . " LIMIT 1")) {
                            $db->Execute("INSERT INTO ms_state (id, name) VALUES (?, ?)", [intval($loc['id']), $loc['title']]);
                        }
                    } elseif (($loc['geo_level'] ?? '') === 'lga') {
                        if (!$hasRows("SELECT LgaId AS val FROM ms_geo_lga WHERE LgaId = " . intval($loc['id']) . " LIMIT 1")) {
                            $db->Execute("INSERT INTO ms_geo_lga (LgaId, Fullname) VALUES (?, ?)", [intval($loc['id']), $loc['title']]);
                        }
                    } elseif (($loc['geo_level'] ?? '') === 'ward') {
                        if (!$hasRows("SELECT wardid AS val FROM ms_geo_ward WHERE wardid = " . intval($loc['id']) . " LIMIT 1")) {
                            $db->Execute("INSERT INTO ms_geo_ward (wardid, ward) VALUES (?, ?)", [intval($loc['id']), $loc['title']]);
                        }
                    }
                } catch (Exception $e) { /* ignore per-row */ }
            }
        }

        // Users
        if (file_exists($fixturesDir . '/Users/users.php')) {
            $users = require $fixturesDir . '/Users/users.php';
            foreach ($users as $k => $u) {
                try {
                    $userid = intval($u['userid'] ?? 0);
                    $loginid = $u['loginid'] ?? null;
                    if ($userid && $loginid) {
                        if (!$hasRows("SELECT userid AS val FROM usr_login WHERE userid = $userid LIMIT 1")) {
                            $db->Execute("INSERT INTO usr_login (userid, loginid, guid, platform, priority, geo_level, geo_level_id, roleid, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [$userid, $loginid, $loginid . '-guid', 'mobile', 1, $u['geo_level'] ?? 'state', $u['geo_level_id'] ?? 1, $u['roleid'] ?? 1, $u['active'] ?? 1]);
                        }
                        if (!$hasRows("SELECT userid AS val FROM usr_identity WHERE userid = $userid LIMIT 1")) {
                            $db->Execute("INSERT INTO usr_identity (userid, `first`, `last`) VALUES (?, ?, ?)", [$userid, $u['username'] ?? $loginid, '']);
                        }
                        // finance/security simple ensures rows exist
                        if (!$hasRows("SELECT userid AS val FROM usr_finance WHERE userid = $userid LIMIT 1")) {
                            $db->Execute("INSERT INTO usr_finance (userid, account_no, bank_name) VALUES (?, ?, ?)", [$userid, '0000000000', $u['role'] ?? '']);
                        }
                        if (!$hasRows("SELECT userid AS val FROM usr_security WHERE userid = $userid LIMIT 1")) {
                            $db->Execute("INSERT INTO usr_security (userid) VALUES (?)", [$userid]);
                        }
                    }
                } catch (Exception $e) { /* ignore per-row */ }
            }
        }

        // Trainings
        if (file_exists($fixturesDir . '/Training/trainings.php')) {
            $tr = require $fixturesDir . '/Training/trainings.php';
            foreach ($tr as $key => $t) {
                try {
                    $trainingId = $t['training_id'] ?? null;
                    if ($trainingId) {
                        if (!$hasRows("SELECT trainingid AS val FROM tra_training WHERE trainingid = '" . $trainingId . "' LIMIT 1")) {
                            $db->Execute("INSERT INTO tra_training (trainingid, title, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)", [$trainingId, $t['training_name'] ?? $trainingId, $t['status'] ?? 'PLANNED', $t['start_date'] ?? null, $t['end_date'] ?? null]);
                        }
                        // participants
                        if (!empty($t['participants'])) {
                            foreach ($t['participants'] as $p) {
                                $loginid = $p['userid'] ?? null;
                                if ($loginid) {
                                    $rows = $db->DataTable("SELECT userid FROM usr_login WHERE loginid = '" . $loginid . "' LIMIT 1");
                                    $userid = (int) ($rows[0]['userid'] ?? 0);
                                    if ($userid) {
                                        // Insert participant if not exists
                                        if (!$hasRows("SELECT id AS val FROM tra_participants WHERE trainingid = '" . $trainingId . "' AND userid = $userid LIMIT 1")) {
                                            $db->Execute("INSERT INTO tra_participants (trainingid, userid, attendance) VALUES (?, ?, ?)", [$trainingId, $userid, $p['attendance'] ?? 'PRESENT']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) { /* ignore per-row */ }
            }
        }

        // Netcards
        if (file_exists($fixturesDir . '/Netcards/netcards.php')) {
            $nc = require $fixturesDir . '/Netcards/netcards.php';
            foreach ($nc as $k => $n) {
                try {
                    $serial = $n['serial_number'] ?? ($n['netcard_id'] ?? null);
                    if ($serial) {
                        if (!$hasRows("SELECT ncid AS val FROM nc_netcard WHERE uuid = '" . $serial . "' LIMIT 1")) {
                            $db->Execute("INSERT INTO nc_netcard (uuid, active, location, geo_level, geo_level_id, status, created) VALUES (?, ?, ?, ?, ?, ?, ?)", [$serial, 1, strtoupper($n['location'] ?? 'WAREHOUSE'), $n['location'] ?? null, $n['location'] === 'WARD' ? ($n['location_value'] ?? 1) : ($n['location'] === 'LGA' ? ($n['location_value'] ?? 1) : 100), $n['status'] ?? 'AVAILABLE', date('Y-m-d H:i:s')]);
                        }
                    }
                } catch (Exception $e) { /* ignore per-row */ }
            }
        }
    } catch (Exception $e) {
        // If seeding fails, don't break tests; tests will report missing data explicitly
        fwrite(STDERR, "Fixture seeding warning: " . $e->getMessage() . "\n");
    }
}

// Application autoload
require_once __DIR__ . '/../lib/autoload.php';

// Register controller namespace autoloader for Dashboard, Distribution, Form, etc.
spl_autoload_register(function ($class) {
    // Handle namespaced controllers (e.g., Dashboard\Distribution)
    $parts = explode('\\', $class);
    if (count($parts) === 2) {
        $namespace = strtolower($parts[0]); // dashboard, distribution, form, etc.
        $className = strtolower($parts[1]);  // distribution, enetcard, etc.
        
        $controllerPath = __DIR__ . "/../lib/controller/{$namespace}/{$className}.cont.php";
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            return;
        }
    }
});

// Register Tests namespace autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Define base directory for tests
define('TEST_BASE_DIR', __DIR__);
define('APP_BASE_DIR', dirname(__DIR__));

// Set up environment variables for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Temporary: record included files at shutdown for coverage/usage analysis
register_shutdown_function(function () {
    $outDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($outDir)) {
        @mkdir($outDir, 0777, true);
    }
    $outFile = $outDir . DIRECTORY_SEPARATOR . 'included_files.json';
    $files = array_values(get_included_files());
    $files = array_map(function ($p) { return str_replace('\\', '/', $p); }, $files);
    @file_put_contents($outFile, json_encode($files, JSON_PRETTY_PRINT));
});

