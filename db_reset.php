<?php
require_once __DIR__ . '/vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');
require_once __DIR__ . '/src/sql-utilities.php';

runFile('init_struct_fn_data.sql');
runFile('create_dummy_data.sql');

// empty the "uploads" folder
foreach (glob(__DIR__ . "/uploads/*") as $file) {
    if (strpos($file, '.gitkeep') == false) {
        unlink($file);
    }
}
