<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/commerciaux', function (Request $request, Response $response, array $args) {
    $db = getPDO();
    $commerciaux = $db->query("select * from user_w_role where user_role = 'commercial'")->fetchAll();
    console_log($commerciaux);
    return $this->view->render('views/commerciaux/commerciaux.html.twig', ['commerciaux' => $commerciaux]);
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin'])($req, $res, $next));
// remarque : on execute la fonction pour avoir une trace dans debug_backtrace()
// car simplement passer un objet ne laisserait aucune trace dans debug_backtrace()

$app->group('/commerciaux/new', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args) {
        return $this->view->render('views/commerciaux/commerciaux-new.html.twig');
    });

    $app->post('', function (Request $request, Response $response, array $args) {
        if (empty($_POST['email']) or empty($_POST['prenom']) or empty($_POST['nom_famille'])) {
            throw new Exception("Il manque un des arguments suivants : " . join(', ', ['email', 'prenom', 'nom_famille']));
        }

        // créer le compte (1 : user, 2 : email, 3 : account)
        $db = getPDO();
        $req = $db->prepare("select nouvel_utilisateur('commercial', :prenom, :nom_famille, :email) new_uid;");
        $req->execute([
            'prenom' => $_POST['prenom'],
            'nom_famille' => $_POST['nom_famille'],
            'email' => $_POST['email'],
        ]);
        $new_uid = $req->fetch()['new_uid'];

        // envoyer un email à l'email renseigné
        $req = $db->prepare("select last_time_settings_changed from user_account where user_id = :new_uid");
        $req->execute(['new_uid' => $new_uid]);
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
