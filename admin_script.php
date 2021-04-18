<?php
require_once __DIR__ . '/src/sql-utilities.php';

$scripts = [
    [
        'drop database + rebuild it',
        function () {
            runFile('init_struct_fn_data.sql');
        }
    ],
    [
        'drop database + rebuild it + add admin only',
        function () {
            runFile('create_admin.sql');
        }
    ],
    [
        'drop database + rebuild it + add admin only + dummy data',
        function () {
            runFile('create_dummy_data.sql');
        }
    ],
    [
        'apply patch0001.sql only',
        function () {
            runFile('patch0001.sql');
        }
    ],
    [
        'empty the "uploads" folder',
        function () {
            // empty the "uploads" folder
            foreach (glob(__DIR__ . "/uploads/*") as $file) {
                if (strpos($file, '.gitkeep') == false) {
                    unlink($file);
                }
            }
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
    fscanf(STDIN, "%d\n", $option); // reads number from STDIN
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
