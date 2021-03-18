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
        $commerciaux = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
        // récupérer tous les fournisseurs
        $fournisseurs = $db->query(getSqlQueryString('tous_fournisseurs'))->fetchAll();
        // récupérer tous les dossiers
        return $response->write($this->view->render('roles/admin/default.html.twig', ['commerciaux' => $commerciaux, 'fournisseurs' => $fournisseurs]));
    });
    $app->get('/new-commercial', function (Request $request, Response $response, array $args): Response {
        return $response->write($this->view->render('roles/admin/new-commercial.html.twig', $_GET));
    });
    $app->post('/new-commercial', function (Request $request, Response $response, array $args) {
        $missing_fields_message = get_form_missing_fields_message(['email', 'prenom', 'nom_famille'], $_POST);
        if ($missing_fields_message) {
            alert($missing_fields_message, 3);
            return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
        }

        // créer le compte (1 : user, 2 : email, 3 : account)
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('new_commercial'));
        $req->execute([
            'prenom' => $_POST['prenom'],
            'nom_famille' => $_POST['nom_famille'],
            'email' => $_POST['email'],
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
                'emails/email-new-commercial.html.twig',
                ['url' => 'http://' . $_SERVER["SERVER_NAME"] . '/?action=init_password&token=' . $jwt]
            )
        );

        alert("Le compte du commercial <b>" . $_POST['prenom'] . " " . $_POST['nom_famille'] . " ("
            . $_POST['email'] . ") a bien été créé, et un email lui a été envoyé</b>", 1);
        return $response->withRedirect($request->getUri()->getPath());
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
