<?php
require_once __DIR__ . '/vendor/autoload.php';
// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

$connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . " --force " . $_ENV['db_name'];
echo $connexion_string;

echo "\n--- init db structure ---\n";
$res = exec($connexion_string . ' -e "source ./sql/init_struct_fn_data.sql"');
echo $res;
echo "\n--- add dummy data ---\n";

$res = exec($connexion_string . ' -e "source ./sql/dummy_data.sql"');
echo $res;
