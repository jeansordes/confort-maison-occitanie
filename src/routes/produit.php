<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Renvoie soit le produit soit une exception dans le cas où l'utilisateur n'a pas le droit de consulter le produit
 */
function is_user_allowed__produit($idProduit)
{
    // récupérer infos sur produit
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('get_produit'));
    $req->execute(['id_produit' => $idProduit]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce produit n'existe pas");
    }
    $produit = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au produit
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if ($role == 'commercial' || ($role == 'fournisseur' && $uid != $produit['id_fournisseur'])) {
        return new \Exception("Vous n'avez pas la permission d'accéder à ce produit");
    }
    return $produit;
}

# /{idProduit}
$app->group('/p/{idProduit}', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        $produit = is_user_allowed__produit($args['idProduit']);
        if ($produit instanceof \Exception) throw $produit;

        // vérifier que le numéro du fournisseur est bon + récupérer ses infos
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_fournisseur'));
        $req->execute(['uid' => $produit['id_fournisseur']]);
        if ($req->rowCount() != 1) {
            throw new Exception("Numéro de fournisseur inconnu");
        }
        $fournisseur = $req->fetch();

        // Récupérer les états
        $req = $db->prepare(getSqlQueryString('get_etats_where_produit'));
        $req->execute(['id_produit' => $args['idProduit']]);
        $etats = $req->fetchAll();

        return $response->write($this->view->render('fournisseur/id-produit.html.twig', [
            'fournisseur' => $fournisseur,
            'produit' => $produit,
            'etats' => $etats,
        ]));
    });

    $app->post('', function (Request $request, Response $response, array $args): Response {
        $produit = is_user_allowed__produit($args['idProduit']);
        if ($produit instanceof \Exception) throw $produit;

        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('update_produit'));
        $req->execute($_POST);
        alert("Vos modifications ont bien été prises en compte", 1);

        return $response->withRedirect($request->getUri()->getPath());
    });

    $app->post('/new-etat', function (Request $request, Response $response, array $args): Response {
        $produit = is_user_allowed__produit($args['idProduit']);
        if ($produit instanceof \Exception) throw $produit;

        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('new_etat_produit'));
        $req->execute([
            'description' => $_POST['description'],
            'id_produit' => $args['idProduit'],
        ]);

        alert('Un état a bien été créé', 1);
        return $response->withRedirect($request->getUri()->getPath() . '/..');
    });

    $app->post('/etats-produit', function (Request $request, Response $response, array $args): Response {
        $produit = is_user_allowed__produit($args['idProduit']);
        if ($produit instanceof \Exception) throw $produit;

        $db = getPDO();
        foreach ($_POST['id_etat'] as $key => $value) {
            $req = $db->prepare(getSqlQueryString('update_etat_produit'));
            $req->execute([
                'id_etat' => $value,
                'description' => $_POST['description'][$key],
                'order_etat' => $_POST['order_etat'][$key],
            ]);
        }
        alert('Vos modifications ont bien été enregistrés', 1);

        return $response->withRedirect($request->getUri()->getPath() . '/..');
    });

    $app->get('/{idEtat}/supprimer-etat', function (Request $request, Response $response, array $args): Response {
        $produit = is_user_allowed__produit($args['idProduit']);
        if ($produit instanceof \Exception) throw $produit;

        // supprimer l'état
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('supprimer_etat_produit'));
        $req->execute(['id_etat' => $args['idEtat']]);

        // récupérer la liste des états
        $req = $db->prepare(getSqlQueryString('get_etats_where_produit'));
        $req->execute(['id_produit' => $args['idProduit']]);
        $etats = $req->fetchAll();

        // les renuméroter
        foreach ($etats as $key => $value) {
            $req = $db->prepare(getSqlQueryString('update_etat_produit'));
            $req->execute([
                'id_etat' => $value['id_etat'],
                'description' => $value['description'],
                'order_etat' => $key,
            ]);
        }

        return $response->withRedirect($request->getUri()->getPath() . '/../..');
    });
});
