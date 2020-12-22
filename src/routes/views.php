<?php

$app->get('/commerciaux', function ($request, $response, $args) {
    return $this->view->render('views/commerciaux.html.twig');
});

$app->get('/commerciaux/new', function ($request, $response, $args) {
    return $this->view->render('views/commerciaux-new.html.twig');
});

$app->post('/commerciaux/new', function ($request, $response, $args) {
    if (empty($_POST['email']) or empty($_POST['prenom']) or empty($_POST['nom_famille'])) {
        throw new Exception("Il manque un des arguments suivants : " . join(', ', ['email', 'prenom', 'nom_famille']));
    }

    // créer le compte
    $db = getPDO();
    $req = $db->prepare('select create_commercial(:prenom, :nom_famille, :email) new_uid');
    $req->execute([
        'prenom' => $_POST['prenom'],
        'nom_famille' => $_POST['nom_famille'],
        'email' => $_POST['email'],
    ]);
    $new_uid = $req->fetch()['new_uid'];

    // envoyer un email à l'email renseigné
    $req = $db->prepare("select last_time_settings_changed from user_account_enriched where user_id = :new_uid");
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
