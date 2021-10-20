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

function getDossierUtilities()
{
    $db = getPDO();
    // récupérer tous les commerciaux
    $commerciaux_from_db = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
    $commerciaux = [];
    foreach ($commerciaux_from_db as $commercial) {
        $commerciaux[$commercial['id_personne']] = $commercial;
    }
    // récupérer tous les clients
    $clients_from_db = $db->query(getSqlQueryString('tous_clients'))->fetchAll();
    $clients = [];
    foreach ($clients_from_db as $client) {
        $clients[$client['id_personne']] = $client;
    }
    // récupérer tous les etats_dossier
    $etats_from_db = $db->query(getSqlQueryString('tous_etats_workflow'))->fetchAll();
    $etats_dossier = [];
    foreach ($etats_from_db as $etat) {
        $etats_dossier[$etat['id_etat']] = $etat['description'];
    }
    // récupérer tous les etats_workflow
    $etats_from_db = $db->query(getSqlQueryString('tous_etats_workflow'))->fetchAll();
    $etats_workflow = [];
    foreach ($etats_from_db as $etat) {
        $etats_workflow[$etat['id_etat']] = $etat['description'];
    }

    return [
        'commerciaux' => $commerciaux,
        'clients' => $clients,
        'etats_dossier' => $etats_dossier,
        'etats_workflow' => $etats_workflow,
    ];
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

        // récupérer id_workflow du produit
        $req = $db->prepare(getSqlQueryString('get_etats_workflow_where_produit'));
        $req->execute(['id_produit' => $dossier['id_produit']]);
        $etats = $req->fetchAll();

        // récupérer liste des logs
        $req = $db->prepare(getSqlQueryString('get_logs_dossiers'));
        $req->execute(['id_dossier' => $args['idDossier']]);
        $logs = $req->fetchAll();

        $events = [];
        foreach ($logs as $log) {
            $tmp = ['type' => 'log', 'object' => $log];
            if (empty($events[$log['date_heure']])) {
                $events[$log['date_heure']] = [$tmp];
            } else {
                $events[$log['date_heure']][] = $tmp;
            }
        }
        foreach ($fichiers as $fichier) {
            $tmp = ['type' => 'fichier', 'object' => $fichier];
            if (empty($events[$fichier['updated_at']])) {
                $events[$fichier['updated_at']] = [$tmp];
            } else {
                $events[$fichier['updated_at']][] = $tmp;
            }
        }
        krsort($events);

        // récupérer le formulaire
        $req = $db->prepare(getSqlQueryString('get_inputs_formulaire'));
        $req->execute(['id_produit' => $dossier['id_produit']]);
        $formulaire_inputs = $req->fetchAll();
        foreach ($formulaire_inputs as $k => $input) {
            $formulaire_inputs[$k]['input_name'] = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z\s]/', '', $input['input_description'])));
            if (in_array($input['input_type'], ['options_radio', 'options_checkbox'])) {
                $formulaire_inputs[$k]['input_choices'] = explode(';', $input['input_choices']);
            }
        }

        // récupérer les réponses du formulaire
        $req = $db->prepare(getSqlQueryString('get_reponses_formulaire_dossier'));
        $req->execute(['id_dossier' => $args['idDossier']]);
        $reponses_formulaire = $req->fetchAll();

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
                'events' => $events,
                'formulaire_inputs' => $formulaire_inputs,
                'reponses_formulaire' => $reponses_formulaire,
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
        $req = $db->prepare(getSqlQueryString('get_etat_workflow'));
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

        // envoyer une notification aux personnes concernés
        sendEmail(
            $this,
            $response,
            [],
            get_liste_destinataires_notifications_dossier($dossier),
            'Un dossier qui vous concerne a changé d\'état',
            $this->view->render(
                'emails/notification-chgmt-dossier.html.twig',
                ['url' => 'http://' . $_SERVER["HTTP_HOST"] . '/d/' . $dossier['id_dossier']]
            ),
        );

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

        // récupérer la date d'upload du dernier fichier avant qu'on upload un nouveau fichier
        $date_dernier_fichier = date_dernier_fichier_dossier($dossier['id_dossier']);

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

        switch ($img_data->getImageOrientation()) {
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $img_data->rotateimage("#fff", 180); // rotate 180 degrees
                break;
            case imagick::ORIENTATION_RIGHTTOP:
                $img_data->rotateimage("#fff", 90); // rotate 90 degrees CW
                break;
            case imagick::ORIENTATION_LEFTBOTTOM:
                $img_data->rotateimage("#fff", -90); // rotate 90 degrees CCW
                break;
        }
        // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
        $img_data->setImageOrientation(imagick::ORIENTATION_TOPLEFT);

        $size = 600;
        $img_data->setResolution("$size", "$size");
        $img_data->setImageFormat("png");
        $img_data->thumbnailImage($size, $size, true, true);
        file_put_contents($directory . "/preview/" . $filename . ".png", $img_data, FILE_USE_INCLUDE_PATH);

        // register file in the DB
        $req = $db->prepare(getSqlQueryString('new_fichier_dossier'));
        $req->execute([
            "file_name" => $filename,
            "mime_type" => $mime_type,
            "id_dossier" => $args['idDossier'],
        ]);

        // récupérer la date d'upload du nouveau fichier
        $date_nouveau_fichier = new DateTime(date_dernier_fichier_dossier($dossier['id_dossier']));
        // si c'est le premier fichier du dossier, alors envoyer directement
        // OU si la date actuelle et la date du dernier fichier sont suffisament espacées (2 minutes) alors envoyer une notification
        if (empty($date_dernier_fichier) || (abs($date_nouveau_fichier->getTimestamp() - (new DateTime($date_dernier_fichier))->getTimestamp()) / 60) > 2) {
            // envoyer une notification aux personnes concernés
            sendEmail(
                $this,
                $response,
                [],
                get_liste_destinataires_notifications_dossier($dossier),
                'Un nouveau fichier a été ajouté à un dossier qui vous concerne',
                $this->view->render(
                    'emails/notification-chgmt-dossier.html.twig',
                    ['url' => 'http://' . $_SERVER["HTTP_HOST"] . '/d/' . $dossier['id_dossier']]
                ),
            );
        }
        return $response->write('uploaded ' . $filename . '<br/>');
    });

    $app->post('/new-comment', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        if (empty($_POST['comment'])) {
            alert('Vous ne pouvez pas créer un commentaire vide', 2);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        }
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('new_dossier_log'));
        $req->execute([
            'id_dossier' => $args['idDossier'],
            'id_author' => $_SESSION['current_user']['uid'],
            'nom_action' => 'Nouveau commentaire',
            'desc_action' => $_POST['comment'],
        ]);

        // envoyer une notification aux personnes concernés
        sendEmail(
            $this,
            $response,
            [],
            get_liste_destinataires_notifications_dossier($dossier),
            'Un nouveau commentaire a été ajouté à un dossier qui vous concerne',
            $this->view->render(
                'emails/notification-chgmt-dossier.html.twig',
                ['url' => 'http://' . $_SERVER["HTTP_HOST"] . '/d/' . $dossier['id_dossier']]
            ),
        );

        alert('Votre commentaire a bien été enregistré', 1);
        return $response->withRedirect($request->getUri()->getPath() . '/..');
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

    $app->post('/form', function (Request $request, Response $response, array $args): Response {
        $dossier = is_user_allowed__dossier($args['idDossier']);
        if ($dossier instanceof \Exception) throw $dossier;

        echo '<pre>'; print_r($_POST);
        exit();
    });
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial', 'admin', 'fournisseur'])($req, $res, $next));
