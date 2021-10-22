<?php
require_once __DIR__ . '/src/sql-utilities.php';
require_once __DIR__ . '/src/utilities.php';

use Ifsnop\Mysqldump as IMysqldump;

// This file is to be runned on the admin 
function delTree($dir)
{
    $files = array_diff(scandir($dir), ['.', '..', '.gitkeep']);

    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    if (count(array_diff(scandir($dir), ['.', '..'])) == 0) {
        rmdir($dir);
    }
}

$scripts = [
    [
        'create archive (db + uploads)',
        function () {
            $archivePath = 'archives/archive-' . date('Y_m_d-H_i_s', time());
            mkdir($archivePath);

            // dump database
            try {
                $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . $_ENV['db_name'], $_ENV['db_username'], $_ENV['db_password']);
                $dump->start($archivePath . '/db_dump.sql');
            } catch (\Exception $e) {
                echo 'mysqldump-php error: ' . $e->getMessage();
            }

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
        'DANGER ZONE : wipe clean everything (db + uploads)',
        function () {
            runFile('wipe_database.sql');
            runFile('init_struct.sql');
            runFile('init_fn.sql');

            delTree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
    [
        'DANGER ZONE : wipe clean everything (db + uploads), then restore last archive',
        function () {
            $folderList = glob('archives/archive-*');

            // get last folder in alphabetical order
            $i = 1;
            do {
                $folder = $folderList[count($folderList) - $i];
                $i++;
            } while (is_file($folder) && $i < count($folderList));

            echo $folder . "\n";

            // move "uploads"
            if (is_dir('uploads')) {
                deleteNonEmptyFolder('uploads');
            }
            rename($folder . '/uploads', 'uploads');

            // execute db_dump.sql
            runFile('wipe_database.sql');
            runFile('db_dump.sql', __DIR__ . '/' . $folder);
            runFile('init_fn.sql');
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
