<?php
require_once __DIR__ . '/vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

global $connexion_string;
$connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . " " . $_ENV['db_name'];
echo $connexion_string . "\n";

function runFile($filename)
{
    global $connexion_string;
    echo "--- $filename ---\n";
    $tmpString = file_get_contents(__DIR__ . '/sql/' . $filename);
    $tmpString = str_replace(':cmo_db_name', $_ENV['db_name'], $tmpString);

    $temp = tmpfile();
    fwrite($temp, $tmpString);
    $res = exec($connexion_string . ' -e "source ' . stream_get_meta_data($temp)['uri'] . '"');
    echo $res;
    fclose($temp);

    echo "\n";
}

runFile('init_struct_fn_data.sql');
runFile('dummy_data.sql');

// empty the "uploads" folder
foreach (glob(__DIR__ . "/uploads/*") as $file) {
    if (strpos($file, '.gitkeep') == false) {
        unlink($file);
    }
}

// pitié, ne pas supprimer ce commentaire : https://stackoverflow.com/a/55953794/5736301