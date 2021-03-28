<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'commercial.php';

function routesDossier()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $idCommercial = getCommercialId($args);
            // récupérer infos sur dossier
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('get_dossier'));
            $req->execute(['id_dossier' => $args['idDossier']]);
            $dossier = $req->fetch();
            // récupérer infos sur commercial
            $req = $db->prepare(getSqlQueryString('get_commercial'));
            $req->execute(['uid' => $idCommercial]);
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
            $req = $db->prepare(getSqlQueryString('fichiers_dossier'));
            $req->execute(['id_dossier' => $args['idDossier']]);
            $fichiers = $req->fetchAll();
            // récupérer les états possibles d'un dossier
            $etats = $db->query(getSqlQueryString('tous_etats_dossier'))->fetchAll();
            // récupérer liste des logs
            $req = $db->prepare(getSqlQueryString('get_logs_dossiers'));
            $req->execute(['id_dossier' => $args['idDossier']]);
            $logs = $req->fetchAll();

            return $response->write($this->view->render(
                'roles/commercial/id-dossier.html.twig',
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
            $uploadFolder = realpath(__DIR__ . '/../../uploads');

            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('fichiers_dossier'));
            $req->execute(['id_dossier' => $args['idDossier']]);
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
            $missing_fields_message = get_form_missing_fields_message(['etat'], $_POST);
            if ($missing_fields_message) {
                alert($missing_fields_message, 3);
                return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
            }
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('edit_etat'));
            $req->execute(['new_value' => $_POST['etat'], 'id_dossier' => $args['idDossier']]);
            $req = $db->prepare(getSqlQueryString('get_etats_dossier'));
            $req->execute(['id_enum_etat' => $_POST['etat']]);
            $newEtatText = $req->fetch()['description'];
            // ajouter dans les logs
            $req = $db->prepare(getSqlQueryString('new_dossier_log'));
            $req->execute([
                'id_dossier' => $args['idDossier'],
                'id_author' => $_SESSION['current_user']['uid'],
                'nom_action' => 'Changement état du dossier',
                'desc_action' => "« " . $newEtatText . " »",
            ]);

            alert("L'état du dossier a bien été mis à jour", 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });
        # /new-fichier
        $app->get('/new-fichier', function (Request $request, Response $response, array $args): Response {
            return $response->write($this->view->render('roles/commercial/dossier/new-file.html.twig'));
        });
        $app->post('/new-fichier', function (Request $request, Response $response, array $args): Response {
            require_once 'files_access.php';
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
            // if PDF creates a preview
            if ($mime_type == 'application/pdf') {
                console_log('koala');
            }

            // register file in the DB
            $req = $db->prepare(getSqlQueryString('new_fichier_dossier'));
            $req->execute([
                "file_name" => $filename,
                "mime_type" => $mime_type,
                "id_dossier" => $args['idDossier'],
            ]);
            return $response->write('uploaded ' . $filename . '<br/>');
        });
    };
}