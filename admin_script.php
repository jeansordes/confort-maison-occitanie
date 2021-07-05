<?php
require_once __DIR__ . '/src/sql-utilities.php';

// This file is to be runned on the admin 
function delTree($dir)
{
    $files = array_diff(scandir($dir), ['.', '..', '.gitkeep']);

    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    if (count(array_diff(scandir($dir), ['.', '..'])) == 0){
        rmdir($dir);
    }
}

$scripts = [
    [
        'drop database + rebuild it',
        function () {
            runFile('init_struct_fn_data.sql');
        }
    ],
    [
        'add admin account to DB',
        function () {
            runFile('create_admin.sql');
        }
    ],
    [
        'add dummy data in the DB (admin not included)',
        function () {
            runFile('create_dummy_data.sql');
        }
    ],
    [
        'apply patch.sql only',
        function () {
            runFile('patch.sql');
        }
    ],
    [
        'empty the "uploads" folder',
        function () {
            delTree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
    [
        'clear the TWIG cache folder',
        function () {
            delTree(__DIR__ . "/src/templates/cache");
            echo "The cache has been cleared";
        }
    ],
    [
        'DANGER ZONE : delete, then rebuild everything (db + uploads)',
        function () {
            runFile('init_struct_fn_data.sql');
            runFile('create_admin.sql');
            runFile('create_dummy_data.sql');
            
            delTree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
];

$keepRunning = true;
while ($keepRunning) {
    $option = 0;
    echo "Which script do you want to run ?\n";
    $i = 1;
    foreach ($scripts as $script) {
        echo $i . ". " . $script[0] . "\n";
        $i++;
    }
    echo (count($scripts) + 1) . ". Exit this script\n";

    // $line = trim(fgets(STDIN)); // reads one line from STDIN
    if (!empty($argv[1])) {
        $option = $argv[1];
        $keepRunning = false;
    } else {
        fscanf(STDIN, "%d\n", $option); // reads number from STDIN
    }
    $option--;
    if ($option >= 0 && $option < count($scripts)) {
        $scripts[$option][1]();
        echo "\n";
    } else if ($option == count($scripts)) {
        echo "Alright, bye :)\n";
        $keepRunning = false;
    } else {
        echo "Uuuh ... I'm not sure I understand what you want ô_o\n";
    }
}
