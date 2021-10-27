<?php
require_once __DIR__ . '/src/sql-utilities.php';
require_once __DIR__ . '/src/utilities.php';

use Ifsnop\Mysqldump as IMysqldump;

// This file is to be runned on the admin 
function del_tree($dir)
{
    if (is_dir($dir)) {

        $files = array_diff(scandir($dir), ['.', '..', '.gitkeep']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? del_tree("$dir/$file") : unlink("$dir/$file");
        }
        if (count(array_diff(scandir($dir), ['.', '..'])) == 0) {
            rmdir($dir);
        }
    }
}

function save_db_dump($archivePath)
{
    try {
        $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . $_ENV['db_name'], $_ENV['db_username'], $_ENV['db_password']);
        $dump->start($archivePath . '/db_dump.sql');
        // Replace all the occurences of the database name by :cmo_db_name
        $tmpString = file_get_contents($archivePath . '/db_dump.sql');
        $tmpString = str_replace($_ENV['db_name'], ':cmo_db_name', $tmpString);
        file_put_contents($archivePath . '/db_dump.sql', $tmpString);
    } catch (\Exception $e) {
        echo 'mysqldump-php error: ' . $e->getMessage();
    }
}

function restore_last_db_dump()
{
    $folderList = glob('archives/archive-*');

    // get last folder in alphabetical order
    $i = 1;
    do {
        $folder = $folderList[count($folderList) - $i];
        $i++;
    } while (is_file($folder) && $i < count($folderList));

    // execute db_dump.sql
    runFile('wipe_database.sql');
    runFile('db_dump.sql', __DIR__ . '/' . $folder);
    runFile('init_fn.sql');

    return $folder;
}

$scripts = [
    [
        'create archive (db_dump.sql only)',
        function () {
            $archivePath = 'archives/archive-' . date('Y_m_d-H_i_s', time());
            mkdir($archivePath);

            save_db_dump($archivePath);
        }
    ],
    [
        'create archive (db_dump.sql + uploads folder)',
        function () {
            $archivePath = 'archives/archive-' . date('Y_m_d-H_i_s', time());
            mkdir($archivePath);

            // dump database
            save_db_dump($archivePath);

            // copy upload folder
            recurseCopy('uploads', $archivePath, 'uploads');
        }
    ],
    [
        'add fictitious data',
        function () {
            runFile('add_fictitious_data.sql');
        }
    ],
    [
        'apply patch.sql only',
        function () {
            runFile('patch.sql');
        }
    ],
    [
        'restore last db_dump.sql',
        function () {
            $folder = restore_last_db_dump();
            echo $folder . " restored\n";
        }
    ],
    [
        'clear the TWIG cache folder',
        function () {
            del_tree(__DIR__ . "/src/templates/cache");
            echo "The cache has been cleared";
        }
    ],
    [
        'DANGER ZONE : empty the "uploads" folder',
        function () {
            del_tree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
    [
        'DANGER ZONE : wipe clean db',
        function () {
            runFile('wipe_database.sql');
        }
    ],
    [
        'DANGER ZONE : wipe clean everything (db + uploads)',
        function () {
            runFile('wipe_database.sql');
            runFile('init_struct.sql');
            runFile('init_fn.sql');

            del_tree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
    [
        'DANGER ZONE : wipe clean everything (db + uploads), then restore last archive',
        function () {
            $folder = restore_last_db_dump();
            
            // replace "uploads" with a copy from the archive
            if (is_dir('uploads')) {
                deleteNonEmptyFolder('uploads');
            }
            recurseCopy($folder . '/uploads', 'uploads');
            
            echo $folder . " restored\n";
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
