<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Renvoie soit le produit soit une exception dans le cas où l'utilisateur n'a pas le droit de consulter le produit
 */
function is_user_allowed__form_template($idTemplate)
{
    // récupérer infos sur produit
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('get_template_formulaire'));
    $req->execute(['id_template' => $idTemplate]);
    if ($req->rowCount() == 0) {
        throw new \Exception("Ce produit n'existe pas");
    }
    $template = $req->fetch();
    // vérifier si on doit empêcher la personne d'accéder au produit
    $role = $_SESSION['current_user']['user_role'];
    $uid = $_SESSION['current_user']['uid'];
    if ($role == 'commercial' || ($role == 'fournisseur' && $uid != $template['id_fournisseur'])) {
        return new \Exception("Vous n'avez pas la permission d'accéder à ce produit");
    }
    return $template;
}

# /workflow
function routesFormTemplate()
{
    return function (App $app) {
        # /{idFormTemplate}
        $app->group('/{idFormTemplate}', function (App $app) {
            $app->get('', function (Request $request, Response $response, array $args): Response {
                $template = is_user_allowed__form_template($args['idFormTemplate']);
                if ($template instanceof \Exception) throw $template;

                // récupérer infos du fournisseur
                $db = getPDO();
                $req = $db->prepare(getSqlQueryString('get_fournisseur'));
                $req->execute(['uid' => $template['id_fournisseur']]);
                $fournisseur = $req->fetch();

                // lister les inputs pour pouvoir les afficher
                $req = $db->prepare(getSqlQueryString('get_inputs_formulaire_where_id_template'));
                $req->execute(['id_template' => $args['idFormTemplate']]);
                $formulaire_inputs = $req->fetchAll();
                foreach ($formulaire_inputs as $k => $input) {
                    if (in_array($input['input_type'], ['options_radio', 'options_checkbox'])) {
                        $formulaire_inputs[$k]['input_choices'] = explode(';', $input['input_choices']);
                    }
                }

                // lister les types de champs possibles (_enum_input_type)
                $input_types = $db->query(getSqlQueryString('tous_input_types'))->fetchAll();

                return $response->write($this->view->render('fournisseur/id-formulaire.html.twig', [
                    'fournisseur' => $fournisseur,
                    'formulaire' => $template,
                    'formulaire_inputs' => $formulaire_inputs,
                    'input_types' => $input_types,
                ]));
            });

            $app->post('', function (Request $request, Response $response, array $args): Response {
                $template = is_user_allowed__form_template($args['idFormTemplate']);
                if ($template instanceof \Exception) throw $template;

                // pour chaque input
                $db = getPDO();
                foreach ($_POST['inputs'] as $key => $value) {
                    if ($key == 'new') {
                        // Add new input
                        foreach ($value as $new_key => $new_value) {
                            $req = $db->prepare(getSqlQueryString('new_input_formulaire'));
                            $req->execute([
                                'id_template' => $args['idFormTemplate'],
                                'input_type' => empty($new_value['input_type']) ? '' : $new_value['input_type'],
                                'input_description' => empty($new_value['input_description']) ? '' : $new_value['input_description'],
                                'input_html_attributes' => empty($new_value['input_html_attributes']) ? '' : $new_value['input_html_attributes'],
                                'input_choices' => empty($new_value['input_choices']) ? '' : join(';', $new_value['input_choices']),
                            ]);
                        }
                    } else {
                        // Update input
                        $req = $db->prepare(getSqlQueryString('update_template_input'));
                        $req->execute([
                            'id_input' => $key,
                            'input_type' => empty($value['input_type']) ? '' : $value['input_type'],
                            'input_description' => empty($value['input_description']) ? '' : $value['input_description'],
                            'input_html_attributes' => empty($value['input_html_attributes']) ? '' : $value['input_html_attributes'],
                            'input_choices' => empty($value['input_choices']) ? '' : join(';', $value['input_choices']),
                        ]);
                    }
                }
                $req = $db->prepare(getSqlQueryString('update_template_name'));
                $req->execute([
                    'id_template' => $args['idFormTemplate'],
                    'nom_template' => $_POST['nom_template'],
                ]);


                return $response->withRedirect($request->getUri()->getPath());
            });
        });
    };
};

$app->group('/form-template', routesFormTemplate())->add(fn ($req, $res, $next) => loggedInSlimMiddleware(['fournisseur', 'admin'])($req, $res, $next));
