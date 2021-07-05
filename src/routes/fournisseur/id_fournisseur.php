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

            $array2merge = getDossierUtilities();
            unset($array2merge['commerciaux']);

            // Récupérer les workflows
            $req = $db->prepare(getSqlQueryString('get_workflows_where_id_fournisseur'));
            $req->execute(['id_fournisseur' => $idFournisseur]);
            $workflows = $req->fetchAll();

            return $response->write($this->view->render('fournisseur/id-fournisseur.html.twig', array_merge($array2merge, [
                'fournisseur' => $fournisseur,
                'produits' => $produits,
                'dossiers' => $dossiers,
                'workflows' => $workflows,
            ])));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $idFournisseur = getFournisseurId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('update_personne_noemail'));
            $req->execute([
                'nom_entreprise' => $_POST['nom_entreprise'],
                'numero_entreprise' => $_POST['numero_entreprise'],
                'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

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

        # /new workflow
        $app->post('/new-workflow', function (Request $request, Response $response, array $args): Response {
            $idFournisseur = getFournisseurId($args);
            $db = getPDO();
            $req = $db->prepare(getSqlQueryString('new_workflow'));
            $req->execute([
                "nom_workflow" => $_POST["nom_workflow"],
                "id_fournisseur" => $idFournisseur,
            ]);
            alert('Workflow ajouté avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });

        # /new produit
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
    };
};

$app->group('/fournisseur', routesFournisseur())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['fournisseur', 'admin'])($req, $res, $next));
