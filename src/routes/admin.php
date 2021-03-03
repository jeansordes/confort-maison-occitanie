<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'commercial.php';

$app->group('/admin', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $db = getPDO();
        $commerciaux = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
        $fournisseurs = $db->query(getSqlQueryString('tous_fournisseurs'))->fetchAll();
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
    
    $app->group('/co/{idCommercial}', routesCommercial());

    $app->get('/f/{idFournisseur}', function (Request $request, Response $response, array $args): Response {
        return $response->write('en construction');
    });
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin'])($req, $res, $next));
