<?php
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'commercial.php';

/**
 * Renvoie soit le dossier soit une exception dans le cas où l'utilisateur n'a pas le droit de consulter le dossier
 */
function is_user_allowed__dossier($idDossier)
{
    // récupérer infos sur dossier
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('get_dossier'));
    $req->execute(['id_dossier' => $idDossier]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce dossier n'existe pas");
    }
    $dossier = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au dossier
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if (($role == 'commercial' && $uid != $dossier['id_commercial']) || ($role == 'fournisseur' && $uid != $dossier['id_fournisseur'])) {
        return new \Exception("Vous n'avez pas la permission d'accéder à ce dossier");
    }
    return $dossier;
}

# /{idDossier}
$app->group('/d/{idDossier}', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        // récupérer infos sur commercial
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_commercial'));
        $req->execute(['uid' => $dossier['id_commercial']]);
        $commercial = $req->fetch();
        // récupérer infos sur fournisseur
        $req = $db->prepare(getSqlQueryString('get_fournisseur'));
        $req->execute(['uid' => $dossier['id_fournisseur']]);
        $fournisseur = $req->fetch();
        // récupérer infos sur client
        $req = $db->prepare(getSqlQueryString('get_client'));
        $req->execute(['id_client' => $dossier['id_client']]);
        $client = $req->fetch();
        // récupérer liste des fichiers
        $req = $db->prepare(getSqlQueryString('tous_fichiers_dossier'));
        $req->execute(['id_dossier' => $args['idDossier'], 'in_trash' => 0]);
        $fichiers = $req->fetchAll();
        // récupérer les états possibles d'un dossier
        $req = $db->prepare(getSqlQueryString('get_etats_where_produit'));
        $req->execute(['id_produit' => $dossier['id_produit']]);
        $etats = $req->fetchAll();
        // récupérer liste des logs
        $req = $db->prepare(getSqlQueryString('get_logs_dossiers'));
        $req->execute(['id_dossier' => $args['idDossier']]);
        $logs = $req->fetchAll();

        return $response->write($this->view->render(
            'dossier/id-dossier.html.twig',
            [
                'dossier' => $dossier,
                'fichiers' => $fichiers,
                'commercial' => $commercial,
                'fournisseur' => $fournisseur,
                'client' => $client,
                'etats' => $etats,
                'logs' => $logs,
            ]
        ));
    });
    # /zip
    $app->get('/zip', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        $uploadFolder = realpath(__DIR__ . '/../../uploads');

        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('tous_fichiers_dossier'));
        $req->execute(['id_dossier' => $args['idDossier'], 'in_trash' => 0]);
        $fichiers = $req->fetchAll();

        // création du fichier zip
        // https://www.virendrachandak.com/techtalk/how-to-create-a-zip-file-using-php/
        $zip = new ZipArchive;
        $zipFilename = bin2hex(random_bytes(5)) . '.zip';
        if ($zip->open($uploadFolder . '/' . $zipFilename, ZipArchive::CREATE) === TRUE) {

            if (count($fichiers) < 1) {
                $zip->addFromString('readme.txt', 'Ce dossier ne contient aucun fichier');
            } else {
                foreach ($fichiers as $fichier) {
                    $zip->addFile($uploadFolder . '/' . $fichier['file_name'], $fichier['file_name']);
                }
            }

            // All files are added, so close the zip file.
            $zip->close();
        }

        // download du fichier zip
        // https://stackoverflow.com/questions/35994416/slim-3-framework-how-to-download-file
        $file = $uploadFolder . '/' . $zipFilename;
        $res = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment;filename="' . basename($file) . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        readfile($file);

        // suppression du fichier zip
        unlink($file);

        return $res;
    });
    # /changer-etat
    $app->post('/changer-etat', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        $missing_fields_message = get_form_missing_fields_message(['etat'], $_POST);
        if ($missing_fields_message) {
            alert($missing_fields_message, 3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }
        // vérifier que l'état correspond bien au produit
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_etat_produit'));
        $req->execute(['id_etat' => $_POST['etat']]);
        if ($req->rowCount() == 0) {
            alert("État inconnu", 3);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        }

        // update etat dossier
        $req = $db->prepare(getSqlQueryString('update_etat_dossier'));
        $req->execute([
            'id_nouvel_etat' => $_POST['etat'],
            'id_dossier' => $args['idDossier'],
            'id_author' => $_SESSION['current_user']['uid'],
        ]);

        alert("L'état du dossier a bien été mis à jour", 1);
        return $response->withRedirect($request->getUri()->getPath() . '/..');
    });
    # /new-fichier
    $app->get('/new-fichier', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;
        // get client
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_client'));
        $req->execute(['id_client' => $dossier['id_client']]);
        $client = $req->fetch();
        // get commercial
        $req = $db->prepare(getSqlQueryString('get_commercial'));
        $req->execute(['uid' => $dossier['id_commercial']]);
        $commercial = $req->fetch();
        return $response->write($this->view->render('dossier/new-file.html.twig', [
            'dossier' => $dossier,
            'client' => $client,
            'commercial' => $commercial,
        ]));
    });
    $app->post('/new-fichier', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        require_once 'fichiers.php';
        $directory = $this->get('upload_directory');

        $uploadedFile = $request->getUploadedFiles()['file'];
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new \Exception("Il y a eu une erreur dans l'upload, veuillez reéssayer");
        }
        $filename = moveUploadedFile($directory, $uploadedFile);
        $mime_type = mime_content_type($directory . DIRECTORY_SEPARATOR . $filename);
        // check mime type
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('check_mime_type'));
        $req->execute(['mime_type' => $mime_type]);
        if ($req->rowCount() == 0) {
            throw new \Exception("Ce type de fichier n'est pas accepté");
        }
        // Preview creation
        $img_data = new Imagick($directory . "/" . $filename . ($mime_type == 'application/pdf' ? "[0]" : ''));
        $img_data->setResolution("300", "300");
        $img_data->setImageFormat("png");
        $img_data->thumbnailImage(300, 300, true);
        file_put_contents($directory . "/preview/" . $filename . ".png", $img_data, FILE_USE_INCLUDE_PATH);

        // register file in the DB
        $req = $db->prepare(getSqlQueryString('new_fichier_dossier'));
        $req->execute([
            "file_name" => $filename,
            "mime_type" => $mime_type,
            "id_dossier" => $args['idDossier'],
        ]);
        return $response->write('uploaded ' . $filename . '<br/>');
    });

    $app->get('/corbeille', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;
        
        // récupérer infos sur commercial
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_commercial'));
        $req->execute(['uid' => $dossier['id_commercial']]);
        $commercial = $req->fetch();
        // récupérer infos sur client
        $req = $db->prepare(getSqlQueryString('get_client'));
        $req->execute(['id_client' => $dossier['id_client']]);
        $client = $req->fetch();
        // récupérer liste des fichiers
        $req = $db->prepare(getSqlQueryString('tous_fichiers_dossier'));
        $req->execute(['id_dossier' => $args['idDossier'], 'in_trash' => 1]);
        $fichiers = $req->fetchAll();

        return $response->write($this->view->render(
            'dossier/corbeille.html.twig',
            [
                'dossier' => $dossier,
                'fichiers' => $fichiers,
                'commercial' => $commercial,
                'client' => $client,
            ]
        ));
    });
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial', 'admin', 'fournisseur'])($req, $res, $next));
