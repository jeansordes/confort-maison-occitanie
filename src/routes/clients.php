<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/clients', function (Request $request, Response $response, array $args): Response {
    if ($_SESSION['current_user']['user_role'] == 'commercial') {
        return $response->withRedirect('/commerciaux/' . $_SESSION['current_user']['uid'] . '/clients');
    }

    $db = getPDO();
    // vérifier que le numéro du commercial est bon + récupérer ses infos
    $res = $db->query(getSqlQueryString('tous_clients'));
    $clients = $res->fetchAll();
    return $response->write($this->view->render('views/clients/clients.html.twig', ['clients' => $clients]));
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin', 'commercial'])($req, $res, $next));

$app->group('/clients/new', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        return $response->write($this->view->render('views/clients/clients-new.html.twig', $_GET));
    });
    $app->post('', function (Request $request, Response $response, array $args): Response {
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('new_client'));
        $req->execute([
            "prenom" => $_POST["prenom"],
            "nom_famille" => $_POST["nom_famille"],
            "civilite" => $_POST["civilite"],
            "adresse" => $_POST["adresse"],
            "code_postal" => $_POST["code_postal"],
            "ville" => $_POST["ville"],
            "pays" => $_POST["pays"],
            "tel1" => $_POST["tel1"],
            "tel2" => $_POST["tel2"],
        ]);
        if (!empty($_POST["email"])) {
            $client_uid = $db->lastInsertId();
            $req = $db->prepare(getSqlQueryString('new_email'));
            $req->execute([
                "email" => $_POST["email"],
                "uid" => $client_uid,
            ]);
        }
        return $response->withRedirect('/clients');
    });
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['commercial'])($req, $res, $next));

$app->get('/commerciaux/{idCommercial}/clients', function (Request $request, Response $response, array $args): Response {
    $db = getPDO();
    // vérifier que le numéro du commercial est bon + récupérer ses infos
    $req = $db->prepare(getSqlQueryString('infos_commercial'));
    $req->execute(['uid' => $args['idCommercial']]);
    if ($req->rowCount() == 1) {
        $commercial = $req->fetch();
        // affiche tous les clients du commercial en question
        $req = $db->prepare(getSqlQueryString('clients_commercial'));
        $req->execute(['id_commercial' => $args['idCommercial']]);
        $clients = $req->fetchAll();
        return $response->write($this->view->render('views/clients/clients.html.twig', ['commercial' => $commercial, 'clients' => $clients]));
    }
    throw new Exception("Numéro de commercial inconnu");
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin', 'commercial'])($req, $res, $next));
