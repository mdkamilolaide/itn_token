<?php
/**
 * One-shot extractor for services.table.php (24 qid handlers).
 * Mirrors the approach used for services.data.php and services.export.php.
 *
 * Output:
 *   - lib/api/table/{domain}/{slug}.php per qid
 *   - lib/api/table/_routes.php  manifest
 *
 * Slugs come from a hand-curated map.
 */

$root  = dirname(__DIR__);
$src   = file_get_contents($root . '/services.table.php');
$tokens = token_get_all($src);

// State machine identical in structure to the services.data.php extractor.
$state           = 'scan';
$parenDepth      = 0;
$braceDepth      = 0;
$bodyBraceTarget = 0;
$bodyStart       = null;
$qid             = null;
$flagCleanData   = false;
$flagQidArg      = false;
$offset          = 0;
$handlers        = [];

foreach ($tokens as $tok) {
    $text = is_array($tok) ? $tok[1] : $tok;
    $id   = is_array($tok) ? $tok[0] : null;
    $len  = strlen($text);

    if ($state === 'scan') {
        if ($id === T_IF || $id === T_ELSEIF) {
            $state         = 'inIf';
            $qid           = null;
            $flagCleanData = false;
            $flagQidArg    = false;
        } elseif ($text === '{') {
            $braceDepth++;
        } elseif ($text === '}') {
            $braceDepth--;
        }
    } elseif ($state === 'inIf') {
        if ($text === '(') {
            $parenDepth = 1;
            $state = 'inCond';
        } elseif ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
            // skip
        } else {
            $state = 'scan';
        }
    } elseif ($state === 'inCond') {
        if ($text === '(') {
            $parenDepth++;
        } elseif ($text === ')') {
            $parenDepth--;
            if ($parenDepth === 0) {
                $state = ($qid !== null) ? 'awaitOpen' : 'scan';
            }
        } else {
            if ($id === T_STRING && $text === 'CleanData') {
                $flagCleanData = true;
                $flagQidArg    = false;
            } elseif ($id === T_CONSTANT_ENCAPSED_STRING) {
                $stripped = substr($text, 1, -1);
                if ($flagCleanData && !$flagQidArg) {
                    if ($stripped === 'qid') {
                        $flagQidArg = true;
                    } else {
                        $flagCleanData = false;
                    }
                } elseif ($flagQidArg && $qid === null) {
                    $qid = $stripped;
                }
            }
        }
    } elseif ($state === 'awaitOpen') {
        if ($text === '{') {
            $bodyStart = $offset + 1;
            $bodyBraceTarget = $braceDepth;
            $braceDepth++;
            $state = 'inBody';
        } elseif ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
            // skip
        } else {
            $state = 'scan';
        }
    } elseif ($state === 'inBody') {
        if ($text === '{') {
            $braceDepth++;
        } elseif ($text === '}') {
            $braceDepth--;
            if ($braceDepth === $bodyBraceTarget) {
                $bodyEnd = $offset;
                $body    = substr($src, $bodyStart, $bodyEnd - $bodyStart);
                $handlers[$qid] = $body;
                $state = 'scan';
            }
        }
    }

    $offset += $len;
}

// Hand-curated qid => "domain/slug" map.
$routes = [
    // ----- users ------------------------------------------------------------
    '001' => 'users/user-list',
    '002' => 'users/group-list',

    // ----- activity / training ---------------------------------------------
    '101' => 'activity/training-list',
    '102' => 'activity/participant-list',
    '103' => 'activity/attendance-list',

    // ----- netcard ---------------------------------------------------------
    '201' => 'netcard/movement-list',
    '202' => 'netcard/allocation-forward',
    '203' => 'netcard/allocation-order',
    '204' => 'netcard/allocation-online',
    '205' => 'netcard/unused-pushed',

    // ----- mobilization ----------------------------------------------------
    '301' => 'mobilization/list',

    // ----- distribution ----------------------------------------------------
    '401' => 'distribution/list',
    '402' => 'distribution/unredeemed',

    // ----- system (admin activity + device registry) -----------------------
    '501' => 'system/user-activity-log',
    '601' => 'system/device-registry',
    '602' => 'system/device-login',

    // ----- smc -------------------------------------------------------------
    '701' => 'smc/child-registry',
    '702' => 'smc/drug-administration',
    '703' => 'smc/referral',
    '704' => 'smc/icc-old',
    '705' => 'smc/icc-balances',
    '706' => 'smc/icc',

    // ----- smc logistics ---------------------------------------------------
    '801' => 'smc-logistics/issue',
    '802' => 'smc-logistics/inbound',
];

if (count($handlers) !== count($routes)) {
    fwrite(STDERR, "Extracted " . count($handlers) . " handlers but route map has " . count($routes) . " entries.\n");
    fwrite(STDERR, "Extracted qids: " . implode(' ', array_keys($handlers)) . "\n");
    exit(1);
}

$outDir = $root . '/lib/api/table';

// Bodies sit at depth 1 (top-level if/elseif chain), so they're indented 4 spaces.
// Same setup as services.data.php's extractor.
foreach ($handlers as $q => $body) {
    if (!isset($routes[$q])) {
        fwrite(STDERR, "Unmapped qid: $q\n");
        exit(1);
    }
    $relPath = $routes[$q] . '.php';
    $abs     = $outDir . '/' . $relPath;
    $dir     = dirname($abs);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $clean = preg_replace('/^[ \t]*\n/', '', $body);
    $clean = preg_replace('/^    /m', '', $clean);  // outdent 4 spaces (depth 1)
    $clean = rtrim($clean, " \t\n\r") . "\n";
    file_put_contents($abs, "<?php\n" . $clean);
}

// Write _routes.php
$lines = [
    "<?php",
    "/*",
    " *  qid => relative path under lib/api/table/",
    " *  Edit by hand to rename a handler.",
    " */",
    "return [",
];
foreach ($routes as $q => $path) {
    $lines[] = sprintf("    %-7s => %s,", "'$q'", "'$path.php'");
}
$lines[] = "];";
file_put_contents($outDir . '/_routes.php', implode("\n", $lines) . "\n");

echo "Extracted " . count($handlers) . " table handlers.\n";
