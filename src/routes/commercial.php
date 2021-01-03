<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

# /commercial
$app->group('/commercial', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $db = getPDO();
        // vérifier que le numéro du commercial est bon + récupérer ses infos
        $req = $db->prepare(getSqlQueryString('infos_commercial'));
        $req->execute(['uid' => $_SESSION['current_user']['uid']]);
        if ($req->rowCount() != 1) {
            throw new Exception("Numéro de commercial inconnu");
        }
        $commercial = $req->fetch();
        // affiche tous les clients du commercial en question
        $req = $db->prepare(getSqlQueryString('clients_commercial'));
        $req->execute(['id_commercial' => $_SESSION['current_user']['uid']]);
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
            // récupérer les informations du client idClient
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('infos_client'));
            $req->execute(['id_client' => $args['idClient'], 'id_commercial' => $_SESSION['current_user']['uid']]);
            $client = $req->fetch();
            // récupérer les contrats du client idClient
            $req = $db->prepare(getSqlQueryString('projets_client'));
            $req->execute(['id_client' => $args['idClient'], 'id_commercial' => $_SESSION['current_user']['uid']]);
            $projets = $req->fetchAll();
            return $response->write($this->view->render('roles/commercial/id-client.html.twig', ['client' => $client, 'projets' => $projets]));
        });
        # /edit
        $app->get('/edit', function (Request $request, Response $response, array $args): Response {
            return $response->write('en construction');
        });
        $app->post('/edit', function (Request $request, Response $response, array $args): Response {
            return $response->withRedirect($request->getUri()->getPath());
        });
        # /new-projet
        $app->get('/new-projet', function (Request $request, Response $response, array $args): Response {
            $db = getPDO();
            $req = $db->query(getSqlQueryString("tous_produits"));
            $produits = $req->fetchAll();
            return $response->write($this->view->render('roles/commercial/new-projet.html.twig', ['produits' => $produits]));
        });
        $app->post('/new-projet', function (Request $request, Response $response, array $args): Response {
            if (empty($_POST['id_produit'])) {
                alert("Vous devez selectionner un produit", 3);
                return $response->withRedirect($request->getUri()->getPath());
            }
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_projet'));
            $req->execute(['id_client' => $args['idClient'], 'id_produit' => $_POST['id_produit']]);
            alert("Le projet a bien été créé", 1);
            return $response->withRedirect($request->getUri()->getPath());
        });
        # /{idProjet}
        $app->group('/{idProjet}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                // récupérer infos sur projet
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('infos_projet'));
                $req->execute(['id_projet' => $args['idProjet']]);
                $projet = $req->fetch();
                // récupérer liste des fichiers
                $req = $db->prepare(getSqlQueryString('fichiers_projet'));
                $req->execute(['id_projet' => $args['idProjet']]);
                $fichiers = $req->fetchAll();
                return $response->write($this->view->render(
                    'roles/commercial/id-projet.html.twig',
                    ['projet' => $projet, 'fichiers' => $fichiers]
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
                return $response->write('en construction');
            });
            $app->post('/new-fichier', function (Request $request, Response $response, array $args): Response {
                return $response->withRedirect($request->getUri()->getPath());
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
});
