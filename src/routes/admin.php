<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'commercial.php';
require_once 'fournisseur/id_fournisseur.php';

$app->group('/admin', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $db = get_pdo();
        // récupérer tous les commerciaux
        $commerciaux_from_db = $db->query(get_sql_query_string('tous_commerciaux'))->fetchAll();
        $commerciaux = [];
        foreach ($commerciaux_from_db as $commercial) {
            $commerciaux[$commercial['id_personne']] = $commercial;
        }
        // récupérer tous les fournisseurs
        $fournisseurs_from_db = $db->query(get_sql_query_string('tous_fournisseurs'))->fetchAll();
        $fournisseurs = [];
        foreach ($fournisseurs_from_db as $fournisseur) {
            $fournisseurs[$fournisseur['id_personne']] = $fournisseur;
        }
        // récupérer tous les clients
        $clients_from_db = $db->query(get_sql_query_string('tous_clients'))->fetchAll();
        $clients = [];
        foreach ($clients_from_db as $client) {
            $clients[$client['id_personne']] = $client;
        }
        // récupérer tous les admins
        $admins_from_db = $db->query(get_sql_query_string('tous_admins'))->fetchAll();
        $admins = [];
        foreach ($admins_from_db as $admin) {
            $admins[$admin['id_personne']] = $admin;
        }
        // récupérer tous les etats_dossier
        $etats_from_db = $db->query(get_sql_query_string('tous_etats_workflow'))->fetchAll();
        $etats_dossier = [];
        foreach ($etats_from_db as $etat) {
            $etats_dossier[$etat['id_etat']] = $etat['description'];
        }

        // TODO début de système de pagination
        console_log(filters_to_where_clause($_GET));

        // récupérer tous les dossiers
        $dossiers = $db->query(get_sql_query_string('tous_dossiers'))->fetchAll();

        return $response->write($this->view->render('admin/admin-panel_main.html.twig', [
            'admins' => $admins,
            'commerciaux' => $commerciaux,
            'fournisseurs' => $fournisseurs,
            'dossiers' => $dossiers,
            'clients' => $clients,
            'etats_dossier' => $etats_dossier,
        ]));
    });
    $app->get('/new-user', function (Request $request, Response $response, array $args): Response {
        return $response->write($this->view->render('admin/new-user.html.twig', ['user' => $_GET]));
    });
    $app->post('/new-user', function (Request $request, Response $response, array $args) {
        $missing_fields_message = get_form_missing_fields_message(['email', 'user_type'], $_POST);
        if ($missing_fields_message) {
            alert($missing_fields_message, 3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }

        // vérifier que l'email n'est pas déjà utilisé par un autre compte commercial/fournisseur
        $db = get_pdo();
        $req = $db->prepare(get_sql_query_string('get_user_from_email'));
        $req->execute(['email' => $_POST['email']]);
        if ($req->rowCount() > 0) {
            alert('Cette adresse email est déjà utilisée', 3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }

        // créer le compte (1 : user, 2 : account)
        $req = $db->prepare(get_sql_query_string('new_user'));
        $req->execute([
            'user_type' => $_POST['user_type'],
            'email' => $_POST['email'],

            'nom_entreprise' => $_POST['nom_entreprise'],
            'numero_entreprise' => $_POST['numero_entreprise'],
            'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

            'prenom' => $_POST['prenom'],
            'nom_famille' => $_POST['nom_famille'],
            "civilite" => $_POST["civilite"],
            "adresse" => $_POST["adresse"],
            "code_postal" => $_POST["code_postal"],
            "ville" => $_POST["ville"],
            "pays" => $_POST["pays"],
            "tel1" => $_POST["tel1"],
            "tel2" => $_POST["tel2"],
        ]);
        $new_uid = $req->fetch()['new_uid'];

        // envoyer un email à l'adresse renseignée
        $req = $db->prepare(get_sql_query_string('last_settings_update'));
        $req->execute(['uid' => $new_uid]);
        $last_user_update = $req->fetch()['last_user_update'];

        $jwt = jwt_encode([
            "last_user_update" => $last_user_update,
            "uid" => $new_uid,
        ], 60 * 24);
        send_email(
            $this,
            $response,
            [$_POST['email']],
            [],
            "Confort maison occitanie : Votre compte vient d'être créé",
            $this->view->render(
                'emails/email-new-user.html.twig',
                ['url' => 'http://' . $_SERVER["HTTP_HOST"] . '/?action=init_password&token=' . $jwt]
            )
        );

        alert("Le compte du " . $_POST['user_type'] . " <b>" . $_POST['prenom'] . " " . $_POST['nom_famille'] . " ("
            . $_POST['email'] . ") a bien été créé, et un email lui a été envoyé</b>", 1);
        return $response->withRedirect($request->getUri()->getPath() . '/..');
    });

    $app->group('/co/{id_commercial}', routes_commercial());

    $app->group('/f/{id_fournisseur}', routes_fournisseur());
})->add(fn ($req, $res, $next) => logged_in_slim_middleware(['admin'])($req, $res, $next));
