<?php
require_once __DIR__ . '/vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');
require_once __DIR__ . '/src/sql-utilities.php';

runFile('patch0001.sql');

// empty the "uploads" folder
foreach (glob(__DIR__ . "/uploads/*") as $file) {
    if (strpos($file, '.gitkeep') == false) {
        unlink($file);
    }
}
