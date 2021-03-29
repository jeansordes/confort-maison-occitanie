<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

# /{idClient}
$app->group('/cl/{idClient}', function (App $app) {
    $app->get('', function (Request $request, Response $response, array $args): Response {
        // récupérer les informations du client idClient
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_client'));
        $req->execute(['id_client' => $args['idClient']]);
        if ($req->rowCount() == 0) {
            throw new Exception("Ce client n'existe pas");
        }
        $client = $req->fetch();
        // vérifier si le demandeur a le droit de consulter ce client
        if ($_SESSION['current_user']['user_role'] == 'fournisseur') {
            //     si fournisseur, vérifier qu'il y a des dossiers à afficher
            // récupérer les contrats du client idClient
            $req = $db->prepare(getSqlQueryString('tous_dossiers_client_filtre_fournisseur'));
            $req->execute(['id_client' => $args['idClient'], 'id_fournisseur' => $_SESSION['current_user']['uid']]);
            if ($req->rowCount() == 0) {
                throw new Exception("Vous n'avez pas la permission d'accéder à ce client");
            }
            $dossiers = $req->fetchAll();
            $client['readOnly'] = true;
        } else if (
            ($_SESSION['current_user']['user_role'] == 'commercial'
                && $client['id_commercial'] == $_SESSION['current_user']['uid'])
            || $_SESSION['current_user']['user_role'] == 'admin'
        ) {
            //     commercial propriétaire du client OU admin
            // récupérer les contrats du client idClient
            $req = $db->prepare(getSqlQueryString('tous_dossiers_client'));
            $req->execute(['id_client' => $args['idClient']]);
            $dossiers = $req->fetchAll();
        } else {
            throw new Exception("Vous n'avez pas la permission d'accéder à ce client");
        }

        // récupérer les infos du commercial
        $req = $db->prepare(getSqlQueryString('get_commercial'));
        $req->execute(['uid' => $client['id_commercial']]);
        $commercial = $req->fetch();
        return $response->write($this->view->render(
            'commercial/id-client.html.twig',
            [
                'client' => array_merge($client, $_GET),
                'dossiers' => $dossiers,
                'commercial' => $commercial,
            ]
        ));
    });
    $app->post('', function (Request $request, Response $response, array $args): Response {
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('update_personne'));
        $req->execute([
            "prenom" => $_POST["prenom"],
            "nom_famille" => $_POST["nom_famille"],
            "civilite" => $_POST["civilite"],
            "email" => $_POST["email"],
            "id_personne" => $args['idClient'],
        ]);
        $req = $db->prepare(getSqlQueryString('update_coordonnees'));
        $req->execute([
            "adresse" => $_POST["adresse"],
            "code_postal" => $_POST["code_postal"],
            "ville" => $_POST["ville"],
            "pays" => $_POST["pays"],
            "tel1" => $_POST["tel1"],
            "tel2" => $_POST["tel2"],
            "id_personne" => $args['idClient'],
        ]);
        alert('Client modifié avec succès 👍', 1);
        return $response->withRedirect($request->getUri()->getPath());
    });
    # /new-dossier
    $app->get('/new-dossier', function (Request $request, Response $response, array $args): Response {
        // get client
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('get_client'));
        $req->execute(['id_client' => $args['idClient']]);
        $client = $req->fetch();
        // get commercial
        $req = $db->prepare(getSqlQueryString('get_commercial'));
        $req->execute(['uid' => $client['id_commercial']]);
        $commercial = $req->fetch();
        return $response->write($this->view->render('dossier/new-dossier.html.twig', [
            'produits' => getPDO()->query(getSqlQueryString("tous_produits"))->fetchAll(),
            'client' => $client,
            'commercial' => $commercial,
        ]));
    });
    $app->post('/new-dossier', function (Request $request, Response $response, array $args): Response {
        if (empty($_POST['id_produit'])) {
            alert("Vous devez selectionner un produit", 3);
            return $response->withRedirect($request->getUri()->getPath());
        }
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('new_dossier'));
        $req->execute(['id_client' => $args['idClient'], 'id_produit' => $_POST['id_produit']]);
        alert("Le dossier a bien été créé", 1);
        $idDossier = $req->fetchColumn();
        return $response->withRedirect('/d/' . $idDossier);
    });
});
