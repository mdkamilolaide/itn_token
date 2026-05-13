<?php
/**
 * One-shot extractor for services.export.php (28 qid handlers).
 * Mirrors the approach used for services.data.php.
 *
 * Output:
 *   - lib/api/export/{domain}/{slug}.php per qid
 *   - lib/api/export/_routes.php  manifest
 *
 * Slugs come from a hand-curated map (cleaner than auto-slug for 28 entries).
 */

$root  = dirname(__DIR__);
$src   = file_get_contents($root . '/services.export.php');
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

// Hand-curated qid => "domain/slug" map. Cleaner than auto-slug for 28 entries.
$routes = [
    '001' => 'users/user-list',

    '101' => 'activity/participant-list',
    '102' => 'activity/attendance-list',

    '301' => 'mobilization/mobilization-data',

    '401' => 'reporting/activity-participants',
    '402' => 'reporting/activity-bank-verification',
    '403' => 'reporting/activity-uncaptured-users',
    '501' => 'reporting/mobilization-by-lga',
    '502' => 'reporting/mobilization-by-dp',
    '503' => 'reporting/mobilization-date-by-lga',
    '504' => 'reporting/mobilization-date-by-dp',
    '505' => 'reporting/mobilization-date-range-by-lga',
    '601' => 'reporting/distribution-by-lga',
    '602' => 'reporting/distribution-by-dp',
    '603' => 'reporting/distribution-date-by-lga',
    '604' => 'reporting/distribution-date-range-by-lga',
    '605' => 'reporting/distribution-date-by-dp',
    '606' => 'reporting/distribution-date-range-by-dp',

    '701' => 'forms/inine-a',
    '702' => 'forms/inine-b',
    '703' => 'forms/inine-c',
    '704' => 'forms/five-revisit',
    '705' => 'forms/end-pro-one',
    '706' => 'forms/cdd',
    '707' => 'forms/hfw',

    '801' => 'smc/drug-administration',
    '802' => 'smc/referral',
    '803' => 'smc/icc-cdd',
];

if (count($handlers) !== count($routes)) {
    fwrite(STDERR, "Extracted " . count($handlers) . " handlers but route map has " . count($routes) . " entries.\n");
    fwrite(STDERR, "Extracted qids: " . implode(' ', array_keys($handlers)) . "\n");
    exit(1);
}

$outDir = $root . '/lib/api/export';

// Bodies sit inside the `} else { ... }` block (depth 1), so they were indented
// one level deeper than the services.data.php handlers — we still strip the
// outer 8-space indent (4 spaces from `else { ... }` + 4 from `if {}`).
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
    $clean = preg_replace('/^        /m', '', $clean);  // outdent 8 spaces (depth 2)
    $clean = rtrim($clean, " \t\n\r") . "\n";
    file_put_contents($abs, "<?php\n" . $clean);
}

// Write _routes.php
$lines = [
    "<?php",
    "/*",
    " *  qid => relative path under lib/api/export/",
    " *  Edit by hand to rename a handler.",
    " */",
    "return [",
];
foreach ($routes as $q => $path) {
    $lines[] = sprintf("    %-7s => %s,", "'$q'", "'$path.php'");
}
$lines[] = "];";
file_put_contents($outDir . '/_routes.php', implode("\n", $lines) . "\n");

echo "Extracted " . count($handlers) . " export handlers.\n";
