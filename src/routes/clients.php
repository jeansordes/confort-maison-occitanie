<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/clients', function (Request $request, Response $response, array $args) {
    if ($_SESSION['current_user']['user_role'] == 'admin') {
        throw new Exception("Vous êtes admin, vous devez <a href='/commerciaux'>sélectionner un commercial</a> pour voir ses clients");
    } else {
        return $response->withRedirect('/commerciaux/' . $_SESSION['current_user']['uid'] . '/clients');
    }
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin', 'commercial'])($req, $res, $next));

$app->get('/commerciaux/{idCommercial}/clients', function (Request $request, Response $response, array $args) {
    // vérifier que le numéro du commercial est bon
    $db = getPDO();
    $req = $db->prepare("select id from user where id = :uid and user_role = 'commercial'");
    $req->execute(['uid' => $args['idCommercial']]);
    if ($req->rowCount() == 1) {
        // affiche tous les clients du commercial en question
        $req = $db->prepare("select u.* from user u, projet p where p.id_client = u.id and p.id_commercial = :id_commercial");
        $req->execute(['id_commercial' => $args['idCommercial']]);
        $clients = $req->fetchAll();
        return $this->view->render('views/clients/clients.html.twig', ['id_commercial' => $args['idCommercial'], 'clients' => $clients]);
    } else {
        throw new Exception("Numéro de commercial inconnu");
    }
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin', 'commercial'])($req, $res, $next));

$app->get('/new', function (Request $request, Response $response, array $args) {
    return $this->view->render('src/views/clients/clients-new.html.twig');
})->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['admin', 'commercial'])($req, $res, $next));
