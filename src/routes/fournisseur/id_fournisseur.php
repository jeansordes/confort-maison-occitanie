<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

function getFournisseurId($args)
{
    return $_SESSION['current_user']['user_role'] == 'fournisseur' ? $_SESSION['current_user']['uid'] : $args['id_fournisseur'];
}

# /fournisseur
function routes_fournisseur()
{
    return function (App $app) {
        $app->get('', function (Request $request, Response $response, array $args): Response {
            $id_fournisseur = getFournisseurId($args);
            $db = get_pdo();
            // vérifier que le numéro du fournisseur est bon + récupérer ses infos
            $req = $db->prepare(get_sql_query_string('get_fournisseur'));
            $req->execute(['uid' => $id_fournisseur]);
            if ($req->rowCount() != 1) {
                throw new Exception("Numéro de fournisseur inconnu");
            }
            $fournisseur = $req->fetch();
            $fournisseur['emailReadOnly'] = true;
            // affiche tous les produits du fournisseur en question
            $req = $db->prepare(get_sql_query_string('tous_produits_fournisseur'));
            $req->execute(['id_fournisseur' => $id_fournisseur]);
            $produits = $req->fetchAll();
            // lister tous les dossiers du fournisseur
            $req = $db->prepare(get_sql_query_string('tous_dossiers_fournisseur'));
            $req->execute(['id_fournisseur' => $id_fournisseur]);
            $dossiers = $req->fetchAll();

            $array2merge = getDossierUtilities();
            unset($array2merge['commerciaux']);

            // Récupérer les workflows
            $req = $db->prepare(get_sql_query_string('get_workflows_where_id_fournisseur'));
            $req->execute(['id_fournisseur' => $id_fournisseur]);
            $workflows = $req->fetchAll();

            // Récupérer les formulaires
            $req = $db->prepare(get_sql_query_string('get_formulaires_where_id_fournisseur'));
            $req->execute(['id_fournisseur' => $id_fournisseur]);
            $formulaires = $req->fetchAll();

            return $response->write($this->view->render('fournisseur/id-fournisseur.html.twig', array_merge($array2merge, [
                'fournisseur' => $fournisseur,
                'produits' => $produits,
                'dossiers' => $dossiers,
                'workflows' => $workflows,
                'formulaires' => $formulaires,
            ])));
        });
        $app->post('', function (Request $request, Response $response, array $args): Response {
            $id_fournisseur = getFournisseurId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('update_personne_noemail'));
            $req->execute([
                'nom_entreprise' => $_POST['nom_entreprise'],
                'numero_entreprise' => $_POST['numero_entreprise'],
                'est_un_particulier' => $_POST['est_un_particulier'] ? 1 : 0,

                "prenom" => $_POST["prenom"],
                "nom_famille" => $_POST["nom_famille"],
                "civilite" => $_POST["civilite"],
                "id_personne" => $id_fournisseur,
            ]);
            $req = $db->prepare(get_sql_query_string('update_coordonnees'));
            $req->execute([
                "adresse" => $_POST["adresse"],
                "code_postal" => $_POST["code_postal"],
                "ville" => $_POST["ville"],
                "pays" => $_POST["pays"],
                "tel1" => $_POST["tel1"],
                "tel2" => $_POST["tel2"],
                "id_personne" => $id_fournisseur,
            ]);
            alert('Informations modifiés avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath());
        });

        # /new workflow
        $app->post('/new-workflow', function (Request $request, Response $response, array $args): Response {
            $id_fournisseur = getFournisseurId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('new_workflow'));
            $req->execute([
                "nom_workflow" => $_POST["nom_workflow"],
                "id_fournisseur" => $id_fournisseur,
            ]);
            alert('Workflow ajouté avec succès 👍', 1);
            return $response->withRedirect($request->getUri()->getPath() . '/..');
        });

        # /new produit
        $app->post('/new-produit', function (Request $request, Response $response, array $args): Response {
            $id_fournisseur = getFournisseurId($args);
            $db = get_pdo();
            $req = $db->prepare(get_sql_query_string('new_produit'));
            $req->execute([
                "nom_produit" => $_POST["nom_produit"],
                "description_produit" => $_POST["description_produit"],
                "id_fournisseur" => $id_fournisseur,
            ]);
            if (!empty($_POST["email"])) {
                $produit_uid = $db->lastInsertId();
                $req = $db->prepare(get_sql_query_string('new_email'));
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

$app->group('/fournisseur', routes_fournisseur())->add(fn ($req, $res, $next) => logged_in_slim_middleware(['fournisseur', 'admin'])($req, $res, $next));
