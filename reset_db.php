<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/sql-utilities.php';
// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

global $db;
global $connexion_string;
$db = getPDO();
$connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . " " . $_ENV['db_name'];

function runFile($filename) {
    global $db;
    global $connexion_string;
    echo "\n--- $filename ---\n";
    $reqString = file_get_contents(__DIR__ . '/sql/' . $filename);
    $reqString = str_replace('cmo_db_name', $_ENV['db_name'], $reqString);
    if ($db->query($reqString)) {
        echo 'Success';
    } else {
        echo "Something went wrong, second method used\n";
        $res = exec($connexion_string . ' -e "source ./sql/init_struct_fn_data.sql"');
        echo $res;
    }
}

runFile('init_struct_fn_data.sql');
runFile('dummy_data.sql');
