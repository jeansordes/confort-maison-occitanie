<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'dossier.php';

function getCommercialId($args)
{
    return $_SESSION['current_user']['user_role'] == 'commercial' ? $_SESSION['current_user']['uid'] : $args['idCommercial'];
}

# /commercial
function routesCommercial()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $idCommercial = getCommercialId($args);
            $db = getPDO();
            // vérifier que le numéro du commercial est bon + récupérer ses infos
            $req = $db->prepare(getSqlQueryString('get_commercial'));
            $req->execute(['uid' => $idCommercial]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de commercial inconnu");
            }
            $commercial = $req->fetch();
            $commercial['emailReadOnly'] = true;
            // affiche tous les clients du commercial en question
            $req = $db->prepare(getSqlQueryString('clients_commercial'));
            $req->execute(['id_commercial' => $idCommercial]);
            $clients = $req->fetchAll();
            return $response->write($this->view->render('roles/commercial/default.html.twig', [
                'commercial' => $commercial,
                'clients' => $clients,
            ]));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $idCommercial = getCommercialId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_personne_noemail'));
            $req->execute([
                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "id_personne" => $idCommercial,
            ]);
            $req = $db->prepare(getSqlQueryString('update_coordonnees'));
            $req->execute([
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
                "id_personne" => $idCommercial,
            ]);
            alert('Informations modifiés avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath());
        });
        # /new-client
        $app->get('/new-client', function (Request $request, Response $response, array $args): Response {
            console_log($request->getUri()->getPath());
            return $response->write($this->view->render('roles/commercial/new-client.html.twig', ['client' => $_GET]));
        });
        $app->post('/new-client', function (Request $request, Response $response, array $args): Response {
            $idCommercial = getCommercialId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('get_email'));
            $req->execute(["email" => $_POST["email"]]);
            if ($req->rowCount() > 0) {
                alert("Cet email est déjà pris", 3);
                return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
            }

            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_client'));
            $req->execute([
                "id_commercial" => $idCommercial,
                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
                "email" => $_POST["email"],
            ]);
            alert('Client ajouté avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });
        # /{idClient}
        $app->group('/{idClient}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                $idCommercial = getCommercialId($args);
                // récupérer les informations du client idClient
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('get_client'));
                $req->execute(['id_client' => $args['idClient']]);
                $client = $req->fetch();
                // récupérer les infos du commercial
                $req = $db->prepare(getSqlQueryString('get_commercial'));
                $req->execute(['uid' => $idCommercial]);
                $commercial = $req->fetch();
                // récupérer les contrats du client idClient
                $req = $db->prepare(getSqlQueryString('dossiers_client'));
                $req->execute(['id_client' => $args['idClient'], 'id_commercial' => $idCommercial]);
                $dossiers = $req->fetchAll();
                return $response->write($this->view->render(
                    'roles/commercial/id-client.html.twig',
                    [
                        'client' => array_merge($client, $_GET),
                        'dossiers' => $dossiers,
                        'commercial' => $commercial,
                    ]
                ));
            });
            $app->post('', function (Request $request, Response $response, array $args): Response {
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('update_personne'));
                $req->execute([
                    "prenom" => $_POST["prenom"],
                    "nom_famille" => $_POST["nom_famille"],
                    "civilite" => $_POST["civilite"],
                    "email" => $_POST["email"],
                    "id_personne" => $args['idClient'],
                ]);
                $req = $db->prepare(getSqlQueryString('update_coordonnees'));
                $req->execute([
                    "adresse" => $_POST["adresse"],
                    "code_postal" => $_POST["code_postal"],
                    "ville" => $_POST["ville"],
                    "pays" => $_POST["pays"],
                    "tel1" => $_POST["tel1"],
                    "tel2" => $_POST["tel2"],
                    "id_personne" => $args['idClient'],
                ]);
                alert('Client modifié avec succès 👍', 1);
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
                $idCommercial = getCommercialId($args);
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('new_dossier'));
                $req->execute(['id_client' => $args['idClient'], 'id_produit' => $_POST['id_produit'], 'id_commercial' => $idCommercial]);
                alert("Le dossier a bien été créé", 1);
                $idDossier = $req->fetchColumn();
                return $response->withRedirect($request->getUri()->getPath() . '/../' . $idDossier);
            });
            # /{idDossier}
            $app->group('/{idDossier}', routesDossier());
        });
    };
};

$app->group('/commercial', routesCommercial())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial', 'admin'])($req, $res, $next));
