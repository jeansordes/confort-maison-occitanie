<?php

use MyApp\EditableException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function send_email($app, $response, $to = [], $bcc = [], $subject, $body)
{
    (new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
    if (!empty($_ENV['APP_MODE']) && $_ENV['APP_MODE'] == 'dev') {
        die($app->view->render('base.html.twig', [
            'title' => $subject,
            'body' => '<div class="alert alert-warning">Vous êtes en mode "dev" '
                . 'ce que vous voyez actuellement est le mail qu\'on aurait envoyé en mode "prod"</div>'
                . (empty($to) ? '' : '<div class="border p-3 rounded mb-3">À : ' . join(', ', $to) . '</div>')
                . (empty($bcc) ? '' : '<div class="border p-3 rounded mb-3">Cci : ' . join(', ', $bcc) . '</div>')
                . '<div class="border p-3 rounded mb-3">Objet : ' . $subject . '</div>'
                . '<div class=" border p-3 rounded">' . $body . '</div>',
        ]));
    } else {
        // envoyer un email à l'adresse renseignée
        $mail = new PHPMailer();

        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = $_ENV['EMAIL_SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['EMAIL_USERNAME'];
        $mail->Password   = $_ENV['EMAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['EMAIL_SMTP_PORT'];

        // NO OUTPUT
        $mail->SMTPDebug = false;
        $mail->do_debug = 0;

        //Recipients
        $mail->setFrom($_ENV['EMAIL_USERNAME']);

        foreach ($to as $dest) {
            $mail->addAddress($dest);
        }
        foreach ($bcc as $dest) {
            $mail->addBCC($dest);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->CharSet = 'UTF-8';

        if (!$mail->send()) {
            throw new Exception("<p>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>"
                . json_encode(['TO' => $to, 'SUBJECT' => $subject, 'BODY' => $body]));
        }
    }
}

function get_a_random_string($length = 18, $keyspace = '')
{
    $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str = '';
    $keyspace = empty($keyspace) ? $base62 : $keyspace;
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

function console_log($payload)
{
    echo '<script>console.log(' . json_encode($payload) . ')</script>';
}

function jwt_encode($payload, $expire_minutes)
{
    if (array_key_exists('iat', $payload) or array_key_exists('exp', $payload)) {
        throw new \Exception("Attention, il ne faut pas mettre 'iat' et 'exp' dans le payload, c'est géré automatiquement");
    }
    $iat = time();
    $exp = $iat + 60 * $expire_minutes;
    return Firebase\JWT\JWT::encode(array_merge([
        "iat" => $iat,
        "exp" => $exp,
    ], $payload), $_ENV['JWT_KEY']);
}

function jwt_decode($token)
{
    return (array) Firebase\JWT\JWT::decode($token, $_ENV['JWT_KEY'], array('HS256'));
}

/**
 * @param string $message The message to be displayed
 * @param int $meaning_code 0 = info, 1 = success, 2 = warning, 3 = danger
 */
function alert($message, $meaning_code)
{
    $meaning_switch = ['alert-info', 'alert-success', 'alert-warning', 'alert-danger'];

    $_SESSION['session_alert'] = [
        'message' => $message,
        'meaning' => $meaning_switch[$meaning_code]
    ];
}

function logged_in_slim_middleware(array $allowed_roles)
{
    global $_allowed_roles;
    $_allowed_roles = $allowed_roles;

    return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        global $_allowed_roles;
        global $_internal_exception;
        if (!empty($_SESSION["current_user"]) && in_array($_SESSION["current_user"]["user_role"], $_allowed_roles)) {
            return $next($request, $response);
        } else {
            $origin = debug_backtrace(1)[0];
            console_log($origin);
            $e = new EditableException("Vous devez être <b>" . join(' ou ', $_allowed_roles) . "</b> pour pouvoir visualiser cette page", 0, $_internal_exception);
            $e->setFile($origin['file']);
            $e->setLine($origin['line']);
            throw $e;
        }
    };
}

function array_special_join(string $glue, string $last_item_glue, array $array)
{
    if (count($array) == 1) return $array[0];
    $last_item = array_pop($array);
    return join($glue, $array) . $last_item_glue . $last_item;
}

function get_form_missing_fields_message(array $keys, array $arr)
{
    $diff_keys = [];
    foreach ($keys as $key) {
        if (empty($arr[$key])) {
            $diff_keys[] = $key;
        }
    }
    return il_manque_les_champs($diff_keys);
}

function il_manque_les_champs($fields)
{
    if (count($fields) == 0) return null;
    if (count($fields) == 1) return 'Il manque le champs <b>' . $fields[0] . '</b>';
    if (count($fields) > 1) return 'Il manque les champs <b>' . array_special_join('</b>, <b>', '</b> et <b>', $fields) . '</b>';
}

function array_to_url_encoding($array)
{
    return join('&', array_map((fn ($k, $v) => $k . '=' . $v), array_keys($array), $array));
}

function date_dernier_fichier_dossier($id_dossier)
{
    // récupérer dans la BDD fichiers (updated_at)
    $db = get_pdo();
    $req = $db->prepare(get_sql_query_string('get_last_fichier_dossier'));
    $req->execute(['id_dossier' => $id_dossier]);
    $date = $req->fetch();
    return empty($date) ? null : $date['updated_at'];
}

function get_liste_destinataires_notifications_dossier($dossier)
{
    // Tout le monde sauf les Admins, et sauf auteur de l'action
    $liste = [];
    $db = get_pdo();
    if ($_SESSION['current_user']['user_role'] != 'fournisseur') {
        // Si ce n'est pas le fournisseur, le rajouté à la liste
        $req = $db->prepare(get_sql_query_string('get_fournisseur'));
        $req->execute(['uid' => $dossier['id_fournisseur']]);
        $liste[] = $req->fetch()['email'];
    }
    if ($_SESSION['current_user']['user_role'] != 'commercial') {
        // Si ce n'est pas le commercial, le rajouter à la liste
        $req = $db->prepare(get_sql_query_string('get_commercial'));
        $req->execute(['uid' => $dossier['id_commercial']]);
        $liste[] = $req->fetch()['email'];
    }
    return $liste;
}

function delete_non_empty_folder($path)
{
    if (is_dir($path) === true)
    {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file)
        {
            delete_non_empty_folder(realpath($path) . '/' . $file);
        }

        return rmdir($path);
    }

    else if (is_file($path) === true)
    {
        return unlink($path);
    }

    return false;
}

function recurse_copy(
    string $sourceDirectory,
    string $destinationDirectory,
    string $childFolder = ''
): void {
    $directory = opendir($sourceDirectory);

    if (is_dir($destinationDirectory) === false) {
        mkdir($destinationDirectory);
    }

    if ($childFolder !== '') {
        if (is_dir("$destinationDirectory/$childFolder") === false) {
            mkdir("$destinationDirectory/$childFolder");
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                recurse_copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            } else {
                copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            }
        }

        closedir($directory);

        return;
    }

    while (($file = readdir($directory)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_dir("$sourceDirectory/$file") === true) {
            recurse_copy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
        else {
            copy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
    }

    closedir($directory);
}