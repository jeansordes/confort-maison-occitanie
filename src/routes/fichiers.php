<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

require_once __DIR__ . "/../sql-utilities.php";

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploadedFile file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $path_infos = pathinfo($uploadedFile->getClientFilename());

    if (strlen($path_infos['filename']) > 200) {
        $filename = substr($path_infos['filename'], 0, strpos(wordwrap($path_infos['filename'], 200), "\n"));
    } else {
        $filename = $path_infos['filename'];
    }
    do {
        // see http://php.net/manual/en/function.random-bytes.php
        $filename .= '-' . bin2hex(random_bytes(5)) . '.' . $path_infos['extension'];

        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('count_file'));
        $req->execute(['file_name' => $filename]);
        $count = $req->fetchColumn();
        // vérifier que le nom de fichier n'existe pas déjà en BDD
    } while ($count > 0);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

$app->get('/f/{idFichier}/toggle-trash', function (Request $request, Response $response, array $args): Response {
    // récupérer infos sur dossier
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('get_dossier_from_fichier'));
    $req->execute(['id_fichier' => $args['idFichier']]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce dossier n'existe pas");
    }
    $dossier = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au fichier
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if (($role == 'commercial' && $uid != $dossier['id_commercial']) || ($role == 'fournisseur' && $uid != $dossier['id_fournisseur'])) {
        throw new \Exception("Vous n'avez pas la permission d'accéder à ce fichier");
    }

    // changer l'état du fichier
    $req = $db->prepare(getSqlQueryString('toggle_fichier_trash'));
    $req->execute(['id_fichier' => $args['idFichier']]);

    // ajouter l'événement aux logs
    $req = $db->prepare(getSqlQueryString('new_dossier_log'));
    $req->execute([
        'id_dossier' => $dossier['id_dossier'],
        'id_author' => $_SESSION['current_user']['uid'],
        'nom_action' => $dossier['fichier_in_trash'] == 0 ? 'Suppression d\'un fichier' : 'Fichier restauré depuis la corbeille',
        'desc_action' => "« " . $dossier['file_name'] . " »",
    ]);

    alert("Le fichier a bien été " . ($dossier['fichier_in_trash'] == 0 ? "mis à la corbeille" : "restauré"), 1);

    return $response->withRedirect('/d/' . $dossier['id_dossier']);
});

$app->get('/f/{idFichier}/rotate-{orientation:left|right}', function (Request $request, Response $response, array $args): Response {

    // récupérer infos sur dossier
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('get_dossier_from_fichier'));
    $req->execute(['id_fichier' => $args['idFichier']]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce dossier n'existe pas");
    }
    $dossier = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au fichier
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if (($role == 'commercial' && $uid != $dossier['id_commercial']) || ($role == 'fournisseur' && $uid != $dossier['id_fournisseur'])) {
        throw new \Exception("Vous n'avez pas la permission d'accéder à ce fichier");
    }

    // faire la rotation de l'image SI C'EST UNE IMAGE
    if ($dossier['mime_type'] != 'application/pdf') {
        $imagick = new \Imagick(realpath(__DIR__ . '/../../uploads/' . $dossier['file_name']));
        $imagick->rotateimage('#fff', $args['orientation'] == 'left' ? "-90" : "90");
        file_put_contents(__DIR__ . "/../../uploads/" . $dossier['file_name'], $imagick, FILE_USE_INCLUDE_PATH);
    }

    // faire la rotation de l'aperçu de l'image
    $imagick = new \Imagick(realpath(__DIR__ . '/../../uploads/preview/' . $dossier['file_name'] . '.png'));
    $imagick->rotateimage('#fff', $args['orientation'] == 'left' ? "-90" : "90");
    file_put_contents(__DIR__ . "/../../uploads/preview/" . $dossier['file_name'] . '.png', $imagick, FILE_USE_INCLUDE_PATH);

    return $response->withRedirect('/d/' . $dossier['id_dossier']);
});
