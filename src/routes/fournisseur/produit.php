<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Renvoie soit le produit soit une exception dans le cas où l'utilisateur n'a pas le droit de consulter le produit
 */
function is_user_allowed__produit($id_produit)
{
    // récupérer infos sur produit
    $db = get_pdo();
    $req = $db->prepare(get_sql_query_string('get_produit'));
    $req->execute(['id_produit' => $id_produit]);
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

# /workflow
function routesProduit()
{
    return function (App $app) {
        # /{id_produit}
        $app->group('/{id_produit}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                $produit = is_user_allowed__produit($args['id_produit']);
                if ($produit instanceof \Exception) throw $produit;

                // vérifier que le numéro du fournisseur est bon + récupérer ses infos
                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('get_fournisseur'));
                $req->execute(['uid' => $produit['id_fournisseur']]);
                if ($req->rowCount() != 1) {
                    throw new Exception("Numéro de fournisseur inconnu");
                }
                $fournisseur = $req->fetch();

                // récupérer tous les etats_dossier
                $etats_from_db = $db->query(get_sql_query_string('tous_etats_workflow'))->fetchAll();
                $etats_dossier = [];
                foreach ($etats_from_db as $etat) {
                    $etats_dossier[$etat['id_etat']] = $etat['description'];
                }

                // Récupérer les roles (pour role_responsable_etape)
                $req = $db->query(get_sql_query_string('tous_roles'));
                $roles = $req->fetchAll();

                // Récupérer les roles (pour role_responsable_etape)
                $req = $db->query(get_sql_query_string('tous_phases'));
                $phases = $req->fetchAll();

                // Récupérer les dossiers de ce produit
                $req = $db->prepare(get_sql_query_string('tous_dossiers_where_produit'));
                $req->execute(['id_produit' => $args['id_produit']]);
                $dossiers = $req->fetchAll();
                console_log($dossiers);
                
                // lister tous les templates de formulaire possible
                $req = $db->query(get_sql_query_string('get_formulaires_where_id_fournisseur'));
                $req->execute(['id_produit' => $produit['id_fournisseur']]);

                $templates = $req->fetchAll();

                // lister tous les workflows possible
                $req = $db->prepare(get_sql_query_string('get_workflows_where_id_fournisseur'));
                $req->execute(['id_fournisseur' => $produit['id_fournisseur']]);
                $workflows = $req->fetchAll();

                return $response->write($this->view->render('fournisseur/id-produit.html.twig', [
                    'fournisseur' => $fournisseur,
                    'produit' => $produit,
                    'etats_dossier' => $etats_dossier,
                    'dossiers' => $dossiers,
                    'roles' => $roles,
                    'phases' => $phases,
                    'templates' => $templates,
                    'workflows' => $workflows,
                ]));
            });

            $app->post('', function (Request $request, Response $response, array $args): Response {
                $produit = is_user_allowed__produit($args['id_produit']);
                if ($produit instanceof \Exception) throw $produit;

                // ATTENTION : cette route ne gère qu'une partie des formulaires présent sur la route GET
                // pour les états, il faut aller sur (POST) /etats-produit
                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('update_produit'));
                $req->execute($_POST);
                alert("Vos modifications ont bien été prises en compte", 1);

                return $response->withRedirect($request->getUri()->getPath());
            });

            $app->post('/etats-produit', function (Request $request, Response $response, array $args): Response {
                $produit = is_user_allowed__produit($args['id_produit']);
                if ($produit instanceof \Exception) throw $produit;

                $db = get_pdo();
                foreach ($_POST['id_etat'] as $key => $value) {
                    $req = $db->prepare(get_sql_query_string('update_etat_workflow'));
                    $req->execute([
                        'id_etat' => $value,
                        'description' => $_POST['description'][$key],
                        'order_etat' => $_POST['order_etat'][$key],
                        'phase_etape' => $_POST['phase_etape'][$key],
                        'role_responsable_etape' => $_POST['role_responsable_etape'][$key],
                    ]);
                }
                alert('Vos modifications ont bien été enregistrées', 1);

                return $response->withRedirect($request->getUri()->getPath() . '/..');
            });

            $app->get('/{id_etat}/supprimer-etat', function (Request $request, Response $response, array $args): Response {
                $produit = is_user_allowed__produit($args['id_produit']);
                if ($produit instanceof \Exception) throw $produit;

                // supprimer l'état
                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('supprimer_etat_workflow'));
                $req->execute(['id_etat' => $args['id_etat']]);

                // récupérer la liste des états
                $req = $db->prepare(get_sql_query_string('get_etats_workflow_where_produit'));
                $req->execute(['id_produit' => $args['id_produit']]);
                $etats = $req->fetchAll();

                // les renuméroter
                foreach ($etats as $key => $value) {
                    $req = $db->prepare(get_sql_query_string('update_etat_workflow'));
                    $req->execute([
                        'id_etat' => $value['id_etat'],
                        'description' => $value['description'],
                        'order_etat' => $key,
                        'phase_etape' => $value['phase_etape'],
                        'role_responsable_etape' => $value['role_responsable_etape'],
                    ]);
                }

                return $response->withRedirect($request->getUri()->getPath() . '/../..');
            });
        });
    };
};

$app->group('/p', routesProduit())->add(fn ($req, $res, $next) => logged_in_slim_middleware(['fournisseur', 'admin'])($req, $res, $next));