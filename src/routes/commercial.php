<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'dossier.php';

function getCommercialId($args)
{
    return $_SESSION['current_user']['user_role'] == 'commercial' ? $_SESSION['current_user']['uid'] : $args['id_commercial'];
}

# /commercial
function routes_commercial()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $id_commercial = getCommercialId($args);
            $db = get_pdo();
            // vérifier que le numéro du commercial est bon + récupérer ses infos
            $req = $db->prepare(get_sql_query_string('get_commercial'));
            $req->execute(['uid' => $id_commercial]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de commercial inconnu");
            }
            $commercial = $req->fetch();
            $commercial['emailReadOnly'] = true;
            // récupérer dossier du commercial
            $req = $db->prepare(get_sql_query_string('tous_dossiers_commercial'));
            $req->execute(['id_commercial' => $id_commercial]);
            $dossiers = $req->fetchAll();

            $req = $db->prepare(get_sql_query_string('clients_commercial'));
            $req->execute(['id_commercial' => $id_commercial]);
            $clients_from_db = $req->fetchAll();
            $clients = [];
            foreach ($clients_from_db as $client) {
                $clients[$client['id_personne']] = $client;
            }

            // récupérer tous les etats_dossier
            $etats_from_db = $db->query(get_sql_query_string('tous_etats_workflow'))->fetchAll();
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
            $id_commercial = getCommercialId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('update_personne_noemail'));
            $req->execute([
                'nom_entreprise' => $_POST['nom_entreprise'],
                'numero_entreprise' => $_POST['numero_entreprise'],
                'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "id_personne" => $id_commercial,
            ]);
            $req = $db->prepare(get_sql_query_string('update_coordonnees'));
            $req->execute([
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
                "id_personne" => $id_commercial,
            ]);
            alert('Informations modifiés avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath());
        });
        # /new-client
        $app->get('/new-client', function (Request $request, Response $response, array $args): Response {
            $id_commercial = getCommercialId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('get_commercial'));
            $req->execute(['uid' => $id_commercial]);
            $commercial = $req->fetch();
            return $response->write($this->view->render('commercial/new-client.html.twig', ['client' => $_GET, 'commercial' => $commercial]));
        });
        $app->post('/new-client', function (Request $request, Response $response, array $args): Response {
            $id_commercial = getCommercialId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('get_email'));
            $req->execute(["email" => $_POST["email"]]);
            if ($req->rowCount() > 0) {
                alert("Cet email est déjà pris", 3);
                return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
            }

            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('new_client'));
            $req->execute([
                "id_commercial" => $id_commercial,
                
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

$app->group('/commercial', routes_commercial())->add(fn ($req, $res, $next) => logged_in_slim_middleware(['commercial', 'admin'])($req, $res, $next));
