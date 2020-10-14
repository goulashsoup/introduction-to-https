<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

if(count($argv) < 2) {
    echo 'ERROR: Dateiname nicht angegeben!';
    exit(1);
}

$filename = $argv[1];

$glossaryEntries = [];

try {
    $glossaryEntries = file($filename, FILE_SKIP_EMPTY_LINES);
} catch(Exception $e) {
    echo 'ERROR: '.$e->getMessage();
    exit(1);
}

echo 'Entries:'.PHP_EOL;

asort($glossaryEntries);

foreach($glossaryEntries as $entry) {
    if(trim($entry) === "") {
        continue;
    }
    echo "    $entry".PHP_EOL;
}
