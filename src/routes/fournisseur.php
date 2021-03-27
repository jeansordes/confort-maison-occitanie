<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

function getFournisseurId($args)
{
    return $_SESSION['current_user']['user_role'] == 'fournisseur' ? $_SESSION['current_user']['uid'] : $args['idFournisseur'];
}

# /fournisseur
function routesFournisseur()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $idFournisseur = getFournisseurId($args);
            $db = getPDO();
            // vérifier que le numéro du fournisseur est bon + récupérer ses infos
            $req = $db->prepare(getSqlQueryString('infos_fournisseur'));
            $req->execute(['uid' => $idFournisseur]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de fournisseur inconnu");
            }
            $fournisseur = $req->fetch();
            // affiche tous les produits du fournisseur en question
            $req = $db->prepare(getSqlQueryString('produits_fournisseur'));
            $req->execute(['id_fournisseur' => $idFournisseur]);
            $produits = $req->fetchAll();
            return $response->write($this->view->render('roles/fournisseur/default.html.twig', [
                'fournisseur' => $fournisseur,
                'produits' => $produits,
            ]));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $missing_fields_message = get_form_missing_fields_message(['id_produit', 'nom_produit', 'desc_produit'], $_POST);
            if ($missing_fields_message) {
                alert($missing_fields_message, 3);
                return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
            }
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_produit'));
            $req->execute($_POST);
            alert('Le produit #' . $_POST['id_produit'] . ' a bien été mis à jour', 1);

            return $response->withRedirect($request->getUri()->getPath());
        });

        # /new-produit
        $app->get('/new-produit', function (Request $request, Response $response, array $args): Response {
            console_log($request->getUri()->getPath());
            return $response->write($this->view->render('roles/fournisseur/new-produit.html.twig', $_GET));
        });
        $app->post('/new-produit', function (Request $request, Response $response, array $args): Response {
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_produit'));
            $req->execute([
                "nom_produit" => $_POST["nom_produit"],
                "description_produit" => $_POST["description_produit"],
                "id_fournisseur" => $args
            ]);
            if (!empty($_POST["email"])) {
                $produit_uid = $db->lastInsertId();
                $req = $db->prepare(getSqlQueryString('new_email'));
                $req->execute([
                    "email" => $_POST["email"],
                    "uid" => $produit_uid,
                ]);
            }
            alert('Produit ajouté avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });
    };
};

$app->group('/fournisseur', routesFournisseur())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['fournisseur', 'admin'])($req, $res, $next));
