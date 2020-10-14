<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

function isSourceNum($num) {
    return preg_match('/\d+\.\z/', $num);
}

if(count($argv) < 2) {
    echo 'ERROR: Dateiname nicht angegeben!';
    exit(1);
}

$filename = $argv[1];

$glossaryEntries = [];

try {
    $lines = file($filename, FILE_SKIP_EMPTY_LINES);
} catch(Exception $e) {
    echo 'ERROR: '.$e->getMessage();
    exit(1);
}

$sources = [];

$duplicates = [];

$startRefRead = false;
foreach($lines as $entry) {
    $trimmedEntry = trim($entry);
    if($trimmedEntry === '## B. Sources') {
        $startRefRead = true;

        continue;
    }

    if($startRefRead && $trimmedEntry !== '') {
        $num = explode(' ', $trimmedEntry, 2)[0];
        if(!isSourceNum($num)) {
            break;
        }

        if(isset($sources[$trimmedEntry])) {
            $duplicates[] = $trimmedEntry;
        }

        $sources[$trimmedEntry] = '';
    }
}

$refs = [];
$nums = [];
$duplNums = [];
$missingNums = [];

foreach($sources as $source => $n) {
    $sourceLineParts = explode(' ', $source, 2);
    if(!$sourceLineParts || count($sourceLineParts) !== 2) {
        continue;
    }

    $num = $sourceLineParts[0];
    if(!isSourceNum($num)) {
        echo 'ERROR: Already checked, you fucked it up!';

        exit(1);
    }

    $num = (int) explode('.', $num, 2)[0];
    $prev = $num - 1;
    if($prev !== 0 && !isset($nums[$prev])) {
        $missingNums[] = $prev;
    }

    if(isset($nums[$num])) {
        $duplNums[] = $num;
    }
    $nums[$num] = '';

    $refParts = explode('#', $sourceLineParts[1]);
    if(!$refParts || count($refParts) < 1) {
        continue;
    }

    $ref = $refParts[0];
    if(count($refParts) > 1) {
        $ref .= ')';
    }

    $dividedUrl = explode('](', $ref, 2);
    if(!$dividedUrl || count($dividedUrl) !== 2) {
        continue;
    }

    $urlPart = $dividedUrl[1];
    if(isset($refs[$urlPart]) && strlen($ref) > strlen($refs[$urlPart])) {
        continue;
    }

    $refs[$urlPart] = $ref;
}

echo 'Number of Source: '.count($nums).PHP_EOL.PHP_EOL;

if(count($missingNums) > 0) {
    echo 'Missing Nums:'.PHP_EOL;
    foreach($missingNums as $n) {
        echo "    $n".PHP_EOL;
    }

    echo PHP_EOL.PHP_EOL;
}

if(count($duplicates) > 0) {
    echo 'Duplicates Sources:'.PHP_EOL;
    foreach($duplicates as $dupl) {
        echo "     $dupl".PHP_EOL;
    }

    echo PHP_EOL.PHP_EOL;
}

if(count($duplNums) > 0) {
    echo 'Dupl Nums:'.PHP_EOL;
    foreach($duplNums as $dn) {
        echo "    $dn".PHP_EOL;
    }

    echo PHP_EOL.PHP_EOL;
}

if(count($refs) <= 0) {
    echo 'WTF? No Refs!'.PHP_EOL;

    exit(1);
}

echo 'References:'.PHP_EOL;

natcasesort($refs);

foreach($refs as $r) {
    echo "    - $r".PHP_EOL.PHP_EOL;
}
