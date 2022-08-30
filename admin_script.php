<?php
echo 'This script is disabled for now';
// The SQL folder has been moved, so the script won't work
exit;

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

function save_db_dump($archive_path)
{
    try {
        $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        $dump->start($archive_path . '/db_dump.sql');
        // Replace all the occurences of the database name by :cmo_db_name
        $tmp_string = file_get_contents($archive_path . '/db_dump.sql');
        $tmp_string = str_replace($_ENV['DB_NAME'], ':cmo_db_name', $tmp_string);
        file_put_contents($archive_path . '/db_dump.sql', $tmp_string);
    } catch (\Exception $e) {
        echo 'mysqldump-php error: ' . $e->getMessage();
    }
}

function restore_last_db_dump()
{
    $folder_list = glob('archives/archive-*');

    // get last folder in alphabetical order
    $i = 1;
    do {
        $folder = $folder_list[count($folder_list) - $i];
        $i++;
    } while (is_file($folder) && $i < count($folder_list));

    // execute db_dump.sql
    run_file('wipe_database.sql');
    run_file('db_dump.sql', __DIR__ . '/' . $folder);
    run_file('init_fn.sql');

    return $folder;
}

$scripts = [
    [
        'create archive (db_dump.sql only)',
        function () {
            $archive_path = 'archives/archive-' . date('Y_m_d-H_i_s', time());
            mkdir($archive_path);

            save_db_dump($archive_path);
        }
    ],
    [
        'create archive (db_dump.sql + uploads folder)',
        function () {
            $archive_path = 'archives/archive-' . date('Y_m_d-H_i_s', time());
            mkdir($archive_path);

            // dump database
            save_db_dump($archive_path);

            // copy upload folder
            recurse_copy('uploads', $archive_path, 'uploads');
        }
    ],
    [
        'add fictitious data',
        function () {
            run_file('add_fictitious_data.sql');
        }
    ],
    [
        'apply patch.sql only',
        function () {
            run_file('patch.sql');
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
            run_file('wipe_database.sql');
        }
    ],
    [
        'DANGER ZONE : wipe clean everything (db + uploads)',
        function () {
            run_file('wipe_database.sql');
            run_file('init_struct.sql');
            run_file('init_fn.sql');

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
                delete_non_empty_folder('uploads');
            }
            recurse_copy($folder . '/uploads', 'uploads');
            
            echo $folder . " restored\n";
        }
    ],
];

$keep_running = true;
while ($keep_running) {
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
        $keep_running = false;
    } else {
        fscanf(STDIN, "%d\n", $option); // reads number from STDIN
    }
    $option--;
    if ($option >= 0 && $option < count($scripts)) {
        $scripts[$option][1]();
        echo "\n";
    } else if ($option == count($scripts)) {
        echo "Alright, bye :)\n";
        $keep_running = false;
    } else {
        echo "Uuuh ... I'm not sure I understand what you want ô_o\n";
    }
}
