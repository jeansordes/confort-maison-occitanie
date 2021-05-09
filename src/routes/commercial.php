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
            // récupérer dossier du commercial
            $req = $db->prepare(getSqlQueryString('tous_dossiers_commercial'));
            $req->execute(['id_commercial' => $idCommercial]);
            $dossiers = $req->fetchAll();

            $req = $db->prepare(getSqlQueryString('clients_commercial'));
            $req->execute(['id_commercial' => $idCommercial]);
            $clients_from_db = $req->fetchAll();
            $clients = [];
            foreach ($clients_from_db as $client) {
                $clients[$client['id_personne']] = $client;
            }

            // récupérer tous les etats_dossier
            $etats_from_db = $db->query(getSqlQueryString('tous_etats_produit'))->fetchAll();
            $etats_dossier = [];
            foreach ($etats_from_db as $etat) {
                $etats_dossier[$etat['id_etat']] = $etat['description'];
            }

            return $response->write($this->view->render('commercial/id-commercial.html.twig', array_merge([
                'commercial' => $commercial,
                'dossiers' => $dossiers,
                'clients' => $clients,
                'etats_dossier' => $etats_dossier,
            ])));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $idCommercial = getCommercialId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_personne_noemail'));
            $req->execute([
                'nom_entreprise' => $_POST['nom_entreprise'],
                'numero_entreprise' => $_POST['numero_entreprise'],
                'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

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
            $idCommercial = getCommercialId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('get_commercial'));
            $req->execute(['uid' => $idCommercial]);
            $commercial = $req->fetch();
            return $response->write($this->view->render('commercial/new-client.html.twig', ['client' => $_GET, 'commercial' => $commercial]));
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
                
                'nom_entreprise' => $_POST['nom_entreprise'],
                'numero_entreprise' => $_POST['numero_entreprise'],
                'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

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
    };
};

$app->group('/commercial', routesCommercial())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial', 'admin'])($req, $res, $next));
