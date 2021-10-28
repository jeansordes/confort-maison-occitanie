<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'id_fournisseur.php';

/**
 * Renvoie soit le produit soit une exception dans le cas où l'utilisateur n'a pas le droit de consulter le produit
 */
function is_user_allowed__workflow($id_workflow)
{
    // récupérer infos sur produit
    $db = get_pdo();
    $req = $db->prepare(get_sql_query_string('get_workflow'));
    $req->execute(['id_workflow' => $id_workflow]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce workflow n'existe pas");
    }
    $workflow = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au produit
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if ($role == 'commercial' || ($role == 'fournisseur' && $uid != $workflow['id_fournisseur'])) {
        return new \Exception("Vous n'avez pas la permission d'accéder à ce produit");
    }
    return $workflow;
}

# /workflow
function routesWorkflow()
{
    return function (App $app) {
        $app->group('/{id_workflow}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                $workflow = is_user_allowed__workflow($args['id_workflow']);
                if ($workflow instanceof \Exception) throw $workflow;

                // Récupérer fournisseur
                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('get_fournisseur'));
                $req->execute(['uid' => $workflow['id_fournisseur']]);
                $fournisseur = $req->fetch();

                // Récupérer véritable workflow
                $workflow = ['nom_workflow' => 'Workflow par défaut', 'id_workflow' => 0];
                $req = $db->prepare(get_sql_query_string('get_workflow'));
                $req->execute(['id_workflow' => $args['id_workflow']]);
                $workflow = $req->fetch();

                // Récupérer les états
                $req = $db->prepare(get_sql_query_string('get_etats_where_workflow'));
                $req->execute(['id_workflow' => $args['id_workflow']]);
                $etats = $req->fetchAll();

                // Récupérer les roles (pour role_responsable_etape)
                $req = $db->query(get_sql_query_string('tous_roles'));
                $roles = $req->fetchAll();

                // Récupérer les roles (pour role_responsable_etape)
                $req = $db->query(get_sql_query_string('tous_phases'));
                $phases = $req->fetchAll();

                return $response->write($this->view->render('fournisseur/id-workflow.html.twig', [
                    'workflow' => $workflow,
                    'etats' => $etats,
                    'roles' => $roles,
                    'phases' => $phases,
                    'fournisseur' => $fournisseur,
                ]));
            });

            $app->post('', function (Request $request, Response $response, array $args): Response {
                $workflow = is_user_allowed__workflow($args['id_workflow']);
                if ($workflow instanceof \Exception) throw $workflow;

                $db = get_pdo();
                // update nom du workflow
                if ($_POST['nom_workflow'] != $workflow['id_workflow']) {
                    $req = $db->prepare(get_sql_query_string('update_workflow'));
                    $req->execute([
                        'id_workflow' => $workflow['id_workflow'],
                        'nom_workflow' => $_POST['nom_workflow'],
                    ]);
                }

                // update les etats du workflow
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

                return $response->withRedirect($request->getUri()->getPath());
            });

            $app->post('/new-etat', function (Request $request, Response $response, array $args): Response {
                $workflow = is_user_allowed__workflow($args['id_workflow']);
                if ($workflow instanceof \Exception) throw $workflow;

                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('new_etat_workflow'));
                $req->execute([
                    'description' => $_POST['description'],
                    'id_workflow' => $args['id_workflow'],
                ]);

                alert('Un état a bien été créé', 1);
                return $response->withRedirect($request->getUri()->getPath() . '/..');
            });

            $app->get('/{id_etat}/supprimer-etat', function (Request $request, Response $response, array $args): Response {
                $workflow = is_user_allowed__workflow($args['id_workflow']);
                if ($workflow instanceof \Exception) throw $workflow;

                // supprimer l'état
                $db = get_pdo();
                $req = $db->prepare(get_sql_query_string('supprimer_etat_workflow'));
                $req->execute(['id_etat' => $args['id_etat']]);

                // récupérer la liste des états
                $req = $db->prepare(get_sql_query_string('get_etats_where_workflow'));
                $req->execute(['id_workflow' => $args['id_workflow']]);
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

$app->group('/workflow', routesWorkflow())->add(fn ($req, $res, $next) => logged_in_slim_middleware(['fournisseur', 'admin'])($req, $res, $next));
