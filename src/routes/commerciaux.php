<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/commerciaux', function (Request $request, Response $response, array $args): Response {
    $db = getPDO();
    $commerciaux = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
    console_log($commerciaux);
    return $response->write($this->view->render('views/commerciaux/commerciaux.html.twig', ['commerciaux' => $commerciaux]));
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin'])($req, $res, $next));
// remarque : on execute la fonction pour avoir une trace dans debug_backtrace()
// car simplement passer un objet ne laisserait aucune trace dans debug_backtrace()

$app->group('/commerciaux/new', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        return $response->write($this->view->render('views/commerciaux/commerciaux-new.html.twig', $_GET));
    });

    $app->post('', function (Request $request, Response $response, array $args) {
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

        // envoyer un email à l'email renseigné
        $req = $db->prepare(getSqlQueryString('last_settings_update'));
        $req->execute(['uid' => $new_uid]);
        $last_time_settings_changed = $req->fetch()['last_time_settings_changed'];

        $jwt = jwt_encode([
            "last_time_settings_changed" => $last_time_settings_changed,
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
        return $response->withRedirect('/commerciaux');
    });
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin'])($req, $res, $next));
