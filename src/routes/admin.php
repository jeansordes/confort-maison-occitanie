<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'commercial.php';
require_once 'fournisseur.php';

$app->group('/admin', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $db = getPDO();
        // récupérer tous les commerciaux
        $commerciaux_from_db = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
        $commerciaux = [];
        foreach ($commerciaux_from_db as $commercial) {
            $commerciaux[$commercial['id_personne']] = $commercial;
        }
        // récupérer tous les fournisseurs
        $fournisseurs_from_db = $db->query(getSqlQueryString('tous_fournisseurs'))->fetchAll();
        $fournisseurs = [];
        foreach ($fournisseurs_from_db as $fournisseur) {
            $fournisseurs[$fournisseur['id_personne']] = $fournisseur;
        }
        // récupérer tous les clients
        $clients_from_db = $db->query(getSqlQueryString('tous_clients'))->fetchAll();
        $clients = [];
        foreach ($clients_from_db as $client) {
            $clients[$client['id_personne']] = $client;
        }
        // récupérer tous les etats_dossier
        $etats_from_db = $db->query(getSqlQueryString('tous_etats_dossier'))->fetchAll();
        $etats_dossier = [];
        foreach ($etats_from_db as $etat) {
            $etats_dossier[$etat['id_enum_etat']] = $etat['description'];
        }
        // récupérer tous les dossiers
        $dossiers = $db->query(getSqlQueryString('tous_dossiers'))->fetchAll();
        return $response->write($this->view->render('roles/admin/default.html.twig', [
            'commerciaux' => $commerciaux,
            'fournisseurs' => $fournisseurs,
            'dossiers' => $dossiers,
            'clients' => $clients,
            'etats_dossier' => $etats_dossier,
        ]));
    });
    $app->get('/new-user', function (Request $request, Response $response, array $args): Response {
        return $response->write($this->view->render('roles/admin/new-user.html.twig', $_GET));
    });
    $app->post('/new-user', function (Request $request, Response $response, array $args) {
        $missing_fields_message = get_form_missing_fields_message(['email', 'user_type'], $_POST);
        if ($missing_fields_message) {
            alert($missing_fields_message, 3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }
        
        // vérifier que l'email n'est pas déjà utilisé par un autre compte commercial/fournisseur
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_email_from_users'));
        $req->execute(['email' => $_POST['email']]);
        if ($req->rowCount() > 0) {
            alert('Cette adresse email est déjà utilisée',3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }

        // créer le compte (1 : user, 2 : account)
        $req = $db->prepare(getSqlQueryString('new_user'));
        $req->execute([
            'user_type' => $_POST['user_type'],
            'email' => $_POST['email'],
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
        $req = $db->prepare(getSqlQueryString('last_settings_update'));
        $req->execute(['uid' => $new_uid]);
        $last_user_update = $req->fetch()['last_user_update'];

        $jwt = jwt_encode([
            "last_user_update" => $last_user_update,
            "uid" => $new_uid,
        ], 60 * 24);
        sendEmail(
            $_POST['email'],
            "Confort maison occitanie : Votre compte vient d'être créé",
            $this->view->render(
                'emails/email-new-user.html.twig',
                ['url' => 'http://' . $_SERVER["SERVER_NAME"] . '/?action=init_password&token=' . $jwt]
            )
        );

        alert("Le compte du commercial <b>" . $_POST['prenom'] . " " . $_POST['nom_famille'] . " ("
            . $_POST['email'] . ") a bien été créé, et un email lui a été envoyé</b>", 1);
        return $response->withRedirect($request->getUri()->getPath() . '/..');
    });

    $app->group('/etats-dossiers', function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            // récupérer les états de dossier possible
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('tous_etats_dossier'));
            $req->execute(['id_commercial' => $_SESSION['current_user']['uid']]);
            $etats = $req->fetchAll();

            return $response->write($this->view->render('roles/admin/etats-dossiers.html.twig', [
                'etats' => $etats
            ]));
        });

        $app->post('/new-etat', function (Request $request, Response $response, array $args): Response {
            $missing_fields_message = get_form_missing_fields_message(['description'], $_POST);
            if ($missing_fields_message) {
                alert($missing_fields_message, 3);
                return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
            }

            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_etat_dossier'));
            $req->execute(['description' => $_POST['description']]);

            alert('Le nouvel état "' . $_POST['description'] . '" a bien été créé', 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });

        $app->group('/{etatDossier}', function (App $app) {
            $app->get('/supprimer', function (Request $request, Response $response, array $args): Response {
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('supprimer_etat_dossier'));
                $req->execute(['description' => $args['etatDossier']]);

                alert('L\'état "' . $args['etatDossier'] . '" a bien été supprimé', 1);
                return $response->withRedirect($request->getUri()->getPath() . '/../..');
            });
        });
    });

    $app->group('/co/{idCommercial}', routesCommercial());

    $app->group('/f/{idFournisseur}', routesFournisseur());
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin'])($req, $res, $next));
