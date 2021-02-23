<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

# /commercial
function routesCommercial()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $idCommercial = $_SESSION['current_user']['user_role'] == 'commercial' ? $_SESSION['current_user']['uid'] : $args['idCommercial'];
            $db = getPDO();
            // vérifier que le numéro du commercial est bon + récupérer ses infos
            $req = $db->prepare(getSqlQueryString('infos_commercial'));
            $req->execute(['uid' => $idCommercial]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de commercial inconnu");
            }
            $commercial = $req->fetch();
            // affiche tous les clients du commercial en question
            $req = $db->prepare(getSqlQueryString('clients_commercial'));
            $req->execute(['id_commercial' => $idCommercial]);
            $clients = $req->fetchAll();
            return $response->write($this->view->render('roles/commercial/default.html.twig', ['commercial' => $commercial, 'clients' => $clients]));
        });
        # /settings
        $app->get('/settings', function (Request $request, Response $response, array $args): Response {
            return $response->write('en construction');
        });
        $app->post('/settings', function (Request $request, Response $response, array $args): Response {
            return $response->write('en construction');
        });
        # /new-client
        $app->get('/new-client', function (Request $request, Response $response, array $args): Response {
            console_log($request->getUri()->getPath());
            return $response->write($this->view->render('roles/commercial/new-client.html.twig', $_GET));
        });
        $app->post('/new-client', function (Request $request, Response $response, array $args): Response {
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_client'));
            $req->execute([
                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
            ]);
            if (!empty($_POST["email"])) {
                $client_uid = $db->lastInsertId();
                $req = $db->prepare(getSqlQueryString('new_email'));
                $req->execute([
                    "email" => $_POST["email"],
                    "uid" => $client_uid,
                ]);
            }
            alert('Client ajouté avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath());
        });
        # /{idClient}
        $app->group('/{idClient}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                $idCommercial = $_SESSION['current_user']['user_role'] == 'commercial' ? $_SESSION['current_user']['uid'] : $args['idCommercial'];
                // récupérer les informations du client idClient
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('infos_client'));
                $req->execute(['id_client' => $args['idClient'], 'id_commercial' => $idCommercial]);
                $client = $req->fetch();
                // récupérer les contrats du client idClient
                $req = $db->prepare(getSqlQueryString('dossiers_client'));
                $req->execute(['id_client' => $args['idClient'], 'id_commercial' => $idCommercial]);
                $dossiers = $req->fetchAll();
                return $response->write($this->view->render(
                    'roles/commercial/id-client.html.twig',
                    ['client' => $client, 'dossiers' => $dossiers]
                ));
            });
            # /edit
            $app->get('/edit', function (Request $request, Response $response, array $args): Response {
                return $response->write('en construction');
            });
            $app->post('/edit', function (Request $request, Response $response, array $args): Response {
                return $response->withRedirect($request->getUri()->getPath());
            });
            # /new-dossier
            $app->get('/new-dossier', function (Request $request, Response $response, array $args): Response {
                return $response->write($this->view->render('roles/commercial/dossier/new-dossier.html.twig', ['produits' => getPDO()->query(getSqlQueryString("tous_produits"))->fetchAll()]));
            });
            $app->post('/new-dossier', function (Request $request, Response $response, array $args): Response {
                if (empty($_POST['id_produit'])) {
                    alert("Vous devez selectionner un produit", 3);
                    return $response->withRedirect($request->getUri()->getPath());
                }
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('new_dossier'));
                $req->execute(['id_client' => $args['idClient'], 'id_produit' => $_POST['id_produit']]);
                alert("Le dossier a bien été créé", 1);
                return $response->withRedirect($request->getUri()->getPath());
            });
            # /{iddossier}
            $app->group('/{iddossier}', function (App $app) {
                $app->get('', function (Request $request, Response $response, array $args): Response {
                    // récupérer infos sur dossier
                    $db = getPDO();
                    $req = $db->prepare(getSqlQueryString('infos_dossier'));
                    $req->execute(['id_dossier' => $args['iddossier']]);
                    $dossier = $req->fetch();
                    // récupérer liste des fichiers
                    $req = $db->prepare(getSqlQueryString('fichiers_dossier'));
                    $req->execute(['id_dossier' => $args['iddossier']]);
                    $fichiers = $req->fetchAll();
                    return $response->write($this->view->render(
                        'roles/commercial/id-dossier.html.twig',
                        ['dossier' => $dossier, 'fichiers' => $fichiers]
                    ));
                });
                # /edit
                $app->get('/edit', function (Request $request, Response $response, array $args): Response {
                    return $response->write('en construction');
                });
                $app->post('/edit', function (Request $request, Response $response, array $args): Response {
                    return $response->withRedirect($request->getUri()->getPath());
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

                    // register file in the DB
                    $req = $db->prepare(getSqlQueryString('new_fichier_dossier'));
                    $req->execute([
                        "file_name" => $filename,
                        "mime_type" => $mime_type,
                        "id_dossier" => $args['iddossier'],
                    ]);
                    return $response->write('uploaded ' . $filename . '<br/>');
                });
                # /{idFichier}
                $app->group('/{idFichier}', function (App $app) {
                    $app->get('', function (Request $request, Response $response, array $args): Response {
                        return $response->write('en construction');
                    });
                    $app->post('', function (Request $request, Response $response, array $args): Response {
                        return $response->withRedirect($request->getUri()->getPath());
                    });
                    # /update
                    $app->get('/update', function (Request $request, Response $response, array $args): Response {
                        return $response->write('en construction');
                    });
                    $app->post('/update', function (Request $request, Response $response, array $args): Response {
                        return $response->withRedirect($request->getUri()->getPath());
                    });
                });
            });
        });
    };
};

$app->group('/commercial', routesCommercial())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial', 'admin'])($req, $res, $next));
