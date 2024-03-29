<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

# /{id_client}
$app->group('/cl/{id_client}', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        // récupérer les informations du client id_client
        $db = get_pdo();
        $req = $db->prepare(get_sql_query_string('get_client'));
        $req->execute(['id_client' => $args['id_client']]);
        if ($req->rowCount() == 0) {
            throw new Exception("Ce client n'existe pas");
        }
        $client = $req->fetch();
        // vérifier si le demandeur a le droit de consulter ce client
        if ($_SESSION['current_user']['user_role'] == 'fournisseur') {
            //     si fournisseur, vérifier qu'il y a des dossiers à afficher
            // récupérer les contrats du client id_client
            $req = $db->prepare(get_sql_query_string('tous_dossiers_client_filtre_fournisseur'));
            $req->execute(['id_client' => $args['id_client'], 'id_fournisseur' => $_SESSION['current_user']['uid']]);
            if ($req->rowCount() == 0) {
                throw new Exception("Vous n'avez pas la permission d'accéder à ce client");
            }
            $dossiers = $req->fetchAll();
            $client['readOnly'] = true;
        } else if (
            ($_SESSION['current_user']['user_role'] == 'commercial'
                && $client['id_commercial'] == $_SESSION['current_user']['uid'])
            || $_SESSION['current_user']['user_role'] == 'admin'
        ) {
            //     commercial propriétaire du client OU admin
            // récupérer les contrats du client id_client
            $req = $db->prepare(get_sql_query_string('tous_dossiers_client'));
            $req->execute(['id_client' => $args['id_client']]);
            $dossiers = $req->fetchAll();
        } else {
            throw new Exception("Vous n'avez pas la permission d'accéder à ce client");
        }

        // récupérer les infos du commercial
        $req = $db->prepare(get_sql_query_string('get_commercial'));
        $req->execute(['uid' => $client['id_commercial']]);
        $commercial = $req->fetch();
        return $response->write($this->view->render(
            'commercial/id-client.html.twig',
            [
                'client' => array_merge($client, $_GET),
                'dossiers' => $dossiers,
                'commercial' => $commercial,
            ]
        ));
    });

    $app->post('', function (Request $request, Response $response, array $args): Response {
        $db = get_pdo();
        $req = $db->prepare(get_sql_query_string('update_personne'));
        $req->execute([
            'nom_entreprise' => $_POST['nom_entreprise'],
            'numero_entreprise' => $_POST['numero_entreprise'],
            'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

            "prenom" => $_POST["prenom"],
            "nom_famille" => $_POST["nom_famille"],
            "civilite" => $_POST["civilite"],
            "email" => $_POST["email"],
            "id_personne" => $args['id_client'],
        ]);
        $req = $db->prepare(get_sql_query_string('update_coordonnees'));
        $req->execute([
            "adresse" => $_POST["adresse"],
            "code_postal" => $_POST["code_postal"],
            "ville" => $_POST["ville"],
            "pays" => $_POST["pays"],
            "tel1" => $_POST["tel1"],
            "tel2" => $_POST["tel2"],
            "id_personne" => $args['id_client'],
        ]);
        $req = $db->prepare(get_sql_query_string('update_client'));
        $req->execute([
            "infos_client_supplementaires" => $_POST["infos_client_supplementaires"],
            "id_client" => $args["id_client"],
        ]);

        alert('Client modifié avec succès 👍', 1);
        return $response->withRedirect($request->getUri()->getPath());
    })->add(fn ($req, $res, $next) => logged_in_slim_middleware(['commercial', 'admin'])($req, $res, $next));

    # /new-dossier
    $app->get('/new-dossier', function (Request $request, Response $response, array $args): Response {
        // get client
        $db = get_pdo();
        $req = $db->prepare(get_sql_query_string('get_client'));
        $req->execute(['id_client' => $args['id_client']]);
        $client = $req->fetch();
        // get commercial
        $req = $db->prepare(get_sql_query_string('get_commercial'));
        $req->execute(['uid' => $client['id_commercial']]);
        $commercial = $req->fetch();
        return $response->write($this->view->render('dossier/new-dossier.html.twig', [
            'produits' => get_pdo()->query(get_sql_query_string("tous_produits"))->fetchAll(),
            'client' => $client,
            'commercial' => $commercial,
        ]));
    })->add(fn ($req, $res, $next) => logged_in_slim_middleware(['commercial', 'admin'])($req, $res, $next));

    $app->post('/new-dossier', function (Request $request, Response $response, array $args): Response {
        if (empty($_POST['id_produit'])) {
            alert("Vous devez selectionner un produit", 3);
            return $response->withRedirect($request->getUri()->getPath());
        }
        $db = get_pdo();
        $req = $db->prepare(get_sql_query_string('new_dossier'));
        $req->execute(['id_client' => $args['id_client'], 'id_produit' => $_POST['id_produit']]);
        alert("Le dossier a bien été créé", 1);
        $id_dossier = $req->fetchColumn();
        return $response->withRedirect('/d/' . $id_dossier);
    })->add(fn ($req, $res, $next) => logged_in_slim_middleware(['commercial', 'admin'])($req, $res, $next));
})->add(fn ($req, $res, $next) => logged_in_slim_middleware(['commercial', 'admin', 'fournisseur'])($req, $res, $next));
