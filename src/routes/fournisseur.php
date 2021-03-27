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
            $req = $db->prepare(getSqlQueryString('get_fournisseur'));
            $req->execute(['uid' => $idFournisseur]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de fournisseur inconnu");
            }
            $fournisseur = $req->fetch();
            $fournisseur['emailReadOnly'] = true;
            // affiche tous les produits du fournisseur en question
            $req = $db->prepare(getSqlQueryString('tous_produits_fournisseur'));
            $req->execute(['id_fournisseur' => $idFournisseur]);
            $produits = $req->fetchAll();
            // lister tous les dossiers du fournisseur
            $req = $db->prepare(getSqlQueryString('tous_dossiers_fournisseur'));
            $req->execute(['id_fournisseur' => $idFournisseur]);
            $dossiers = $req->fetchAll();
            // récupérer tous les commerciaux
            $commerciaux_from_db = $db->query(getSqlQueryString('tous_commerciaux'))->fetchAll();
            $commerciaux = [];
            foreach ($commerciaux_from_db as $commercial) {
                $commerciaux[$commercial['id_personne']] = $commercial;
            }
            // récupérer tous les clients
            $clients_from_db = $db->query(getSqlQueryString('tous_clients'))->fetchAll();
            $clients = [];
            foreach ($clients_from_db as $client) {
                $clients[$client['id_personne']] = $client;
            }
            // récupérer tous les etats_dossier
            $etats_from_db = $db->query(getSqlQueryString('tous_etats_dossier'))->fetchAll();
            $etats_dossier = [];
            foreach ($etats_from_db as $etat) {
                $etats_dossier[$etat['id_enum_etat']] = $etat['description'];
            }
            return $response->write($this->view->render('roles/fournisseur/default.html.twig', [
                'fournisseur' => $fournisseur,
                'commerciaux' => $commerciaux,
                'produits' => $produits,
                'dossiers' => $dossiers,
                'clients' => $clients,
                'etats_dossier' => $etats_dossier,
            ]));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $idFournisseur = getFournisseurId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_personne_noemail'));
            $req->execute([
                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "id_personne" => $idFournisseur,
            ]);
            $req = $db->prepare(getSqlQueryString('update_coordonnees'));
            $req->execute([
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
                "id_personne" => $idFournisseur,
            ]);
            alert('Informations modifiés avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath());
        });

        # /new-produit
        $app->get('/new-produit', function (Request $request, Response $response, array $args): Response {
            console_log($request->getUri()->getPath());
            return $response->write($this->view->render('roles/fournisseur/new-produit.html.twig', $_GET));
        });
        $app->post('/new-produit', function (Request $request, Response $response, array $args): Response {
            $idFournisseur = getFournisseurId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_produit'));
            $req->execute([
                "nom_produit" => $_POST["nom_produit"],
                "description_produit" => $_POST["description_produit"],
                "id_fournisseur" => $idFournisseur,
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

        # /{idProduit}
        $app->post('/{idProduit}', function (Request $request, Response $response, array $args): Response {
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_produit'));
            $req->execute($_POST);
            alert('Le produit #' . $_POST['id_produit'] . ' a bien été mis à jour', 1);

            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });
    };
};

$app->group('/fournisseur', routesFournisseur())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['fournisseur', 'admin'])($req, $res, $next));
