<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once "./src/sql-utilities.php";
// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

$db = getPDO();
$req = $db->prepare(file_get_contents("./sql/init_struct_fn_data.sql"));
if ($req->execute()) {
    echo "Success : DB structure init is a success";
} else {
    echo "Fail : DB structure init failed";
}
echo "\n";

$req = $db->prepare(file_get_contents("./sql/dummy_data.sql"));
if ($req->execute()) {
    echo "Success : dummy data added";
} else {
    echo "Fail : dummy data not added";
}