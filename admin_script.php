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
        'create archive.zip (db_dump + uploads)',
        function () {
            // dump database
            try {
                $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . $_ENV['db_name'], $_ENV['db_username'], $_ENV['db_password']);
                $dump->start('archives/' . 'db_dump.sql');
            } catch (\Exception $e) {
                echo 'mysqldump-php error: ' . $e->getMessage();
            }

            // create zip file with the dump + uploads
            // Initialize archive object
            $zip = new ZipArchive();
            $zip->open('archives/archive-' . date('Y_m_d-H_i_s', time()) . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $rootPath = realpath('uploads');
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);

                    // Add current file to archive
                    $zip->addFile($filePath, 'uploads/' . $relativePath);
                }
            }
            // add last db_dump SQL file
            $zip->addFile('archives/db_dump.sql', 'db_dump.sql');

            // Zip archive will be created only after closing object
            $zip->close();

            // remove tmp sql file
            unlink('archives/db_dump.sql');
        }
    ],
    [
        'drop database + rebuild it',
        function () {
            runFile('wipe_database.sql');
            runFile('init_struct.sql');
            runFile('init_fn.sql');
            runFile('init_data.sql');
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
        'DANGER ZONE : delete everything (db + uploads), then rebuild a virgin database',
        function () {
            runFile('wipe_database.sql');
            runFile('init_struct.sql');
            runFile('init_fn.sql');
            runFile('init_data.sql');
            runFile('create_admin.sql');
            runFile('create_dummy_data.sql');

            delTree(__DIR__ . '/uploads');
            echo "The /uploads folder is now empty\n";
        }
    ],
    [
        'DANGER ZONE : delete everything (db + uploads), then restore last archive',
        function () {
            $fileList = glob('archives/*.zip');

            // get last file in alphabetical order
            $i = 1;
            do {
                $file = $fileList[count($fileList) - $i];
                $i++;
            } while (!is_file($file) && $i < count($fileList));

            // unzip
            $extraction_folder = 'tmp_extraction_folder/';
            mkdir($extraction_folder);
            $zip = new ZipArchive;
            if ($zip->open($file) !== TRUE) {
                throw new Exception("⚠ Impossible d'ouvrir $file (fichier ZIP attendu)");
            }
            $zip->extractTo(realpath($extraction_folder));
            $zip->close();

            // move "uploads"
            if (is_dir('uploads')) {
                deleteNonEmptyFolder('uploads');
            }
            rename($extraction_folder . '/uploads', 'uploads');

            // execute db_dump.sql
            runFile('wipe_database.sql');
            runFile('db_dump.sql', __DIR__ . '/' . $extraction_folder);
            runFile('init_fn.sql');

            deleteNonEmptyFolder($extraction_folder);
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
