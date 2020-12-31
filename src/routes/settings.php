<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/password-edit', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        throw new Exception("Vous devez être connecté pour accéder à cette page");
    }
    return $response->write($this->view->render('settings/password-edit.html.twig', ['email' => $_SESSION['current_user']['email']]));
});

$app->post('/password-edit', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        throw new Exception("Vous devez être connecté pour accéder à cette page");
    }
    // vérifier que le mot de passe a bien été rentré
    if ($_POST['password1'] != $_POST['password2']) {
        alert('😕 Les deux mots de passes rentrées ne concordent pas, veuillez réessayer', 2);
        return $response->withRedirect($request->getUri()->getPath());
    } else if (strlen($_POST['password1']) < 8) {
        alert('Votre mot de passe doit contenir au moins 8 caractères', 2);
        return $response->withRedirect($request->getUri()->getPath());
    } else {
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('update_pwd'));
        $req->execute([
            "uid" => $_SESSION["current_user"]["uid"],
            "new_password_hash" => password_hash($_POST['password1'], PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
        alert("👍 Votre mot de passe a été modifié avec succès", 1);
        return $response->withRedirect('/');
    }
});
