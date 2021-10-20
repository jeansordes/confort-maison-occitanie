<?php
require_once __DIR__ . '/../vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');

// { <nom_requete> : { sql: string, args: array<string> } }
function getSqlQueryString($key, $filters = [])
{
    $filter_str = "";

    return [
        // tous
        'tous_admins' => buildSelectQuery("admins"),
        'tous_commerciaux' => buildSelectQuery("commerciaux"),
        'tous_produits' => buildSelectQuery("produits"),
        'tous_dossiers' => buildSelectQuery("dossiers_enriched"),
        'tous_clients' => buildSelectQuery("clients"),
        'tous_fournisseurs' => buildSelectQuery("fournisseurs"),
        'tous_roles' => buildSelectQuery('_enum_user_role'),
        'tous_phases' => buildSelectQuery('_enum_phases_dossier'),
        'tous_templates' => buildSelectQuery("template_formulaire_produit"),
        'tous_produits_fournisseur' => buildSelectQuery("produits", [], ["id_fournisseur = :id_fournisseur"]),
        'tous_dossiers_fournisseur' => buildSelectQuery("dossiers_enriched", [], ["id_fournisseur = :id_fournisseur"]),
        'tous_dossiers_client' => buildSelectQuery(
            'dossiers_enriched',
            [],
            ['id_client = :id_client'],
            'order by date_creation desc'
        ),
        'tous_dossiers_client_filtre_fournisseur' => buildSelectQuery(
            'dossiers_enriched',
            [],
            ['id_client = :id_client', 'id_fournisseur = :id_fournisseur'],
            'order by date_creation desc'
        ),
        'tous_fichiers_dossier' => buildSelectQuery(
            'fichiers ff, fichiers_dossier fp',
            ['ff.*'],
            ['ff.id_fichier = fp.id_fichier', 'fp.id_dossier = :id_dossier', 'ff.in_trash = :in_trash']
        ),
        'tous_dossiers_where_produit' => buildSelectQuery('dossiers_enriched', [], ['id_produit = :id_produit']),
        'tous_dossiers_commercial' => buildSelectQuery(
            'dossiers_enriched',
            [],
            ['id_commercial = :id_commercial'],
            'order by date_creation desc'
        ),
        'tous_etats_workflow' => buildSelectQuery('etats_workflow'),
        'tous_input_types' => buildSelectQuery('_enum_input_type'),
        // new
        'new_user' => 'select new_user(:user_type, :email, \'\', :nom_entreprise, :numero_entreprise, :est_un_particulier, :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2) new_uid',
        'new_client' => 'select new_client(:id_commercial, :nom_entreprise, :numero_entreprise, :est_un_particulier, :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2, :email)',
        'new_dossier' => 'select new_dossier(:id_client, :id_produit)',
        'new_fichier_dossier' => 'select new_fichier_dossier(:file_name, :mime_type, :id_dossier)',
        'new_fichier_produit' => 'select new_fichier_produit(:file_name, :mime_type, :id_produit)',
        'new_produit' => 'insert into produits(id_fournisseur, description_produit, nom_produit) values (:id_fournisseur, :description_produit, :nom_produit)',
        'new_workflow' => 'insert into workflows (id_fournisseur, nom_workflow) values (:id_fournisseur, :nom_workflow)',
        'new_etat_workflow' => 'select new_etat_workflow(:id_workflow, :description)',
        'new_dossier_log' => 'insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (:id_dossier, :id_author, :nom_action, :desc_action)',
        'new_input_formulaire' => 'select new_input_formulaire(:id_template, :input_type, :input_description, :input_choices, :input_html_attributes)',
        // get
        'get_commercial' => 'select c.*, co.* from commerciaux c, coordonnees co where co.id_coordonnees = c.id_coordonnees and id_personne = :uid',
        'get_fournisseur' => 'select f.*, c.* from fournisseurs f, coordonnees c where f.id_coordonnees = c.id_coordonnees and id_personne = :uid',
        'get_client' => 'select cl.*, co.* from clients cl, coordonnees co where cl.id_personne = :id_client and co.id_coordonnees = cl.id_coordonnees',
        'get_dossier' => 'select * from dossiers_enriched where id_dossier = :id_dossier',
        'get_dossier_from_fichier' => 'select de.id_commercial, de.id_fournisseur, fd.id_dossier, ff.* from fichiers_dossier fd, fichiers ff, dossiers_enriched de where fd.id_fichier = :id_fichier and de.id_dossier = fd.id_dossier and ff.id_fichier = fd.id_fichier',
        'get_produit' => 'select * from produits where id_produit = :id_produit',
        'get_logs_dossiers' => 'select * from logs_enriched where id_dossier = :id_dossier order by date_heure desc',
        'get_email' => 'select email from personnes where email = :email',
        'get_user_from_email' => 'select p.email from personnes p, utilisateurs u where p.id_personne = u.id_utilisateur and p.email = :email',
        'get_etats_where_workflow' => buildSelectQuery('etats_workflow', [], ['id_workflow = :id_workflow'], 'order by order_etat'),
        'get_etats_workflow_where_produit' =>  buildSelectQuery('etats_workflow a, produits b', [], ['b.id_produit = :id_produit', 'b.id_workflow = a.id_workflow'], 'order by a.order_etat'),
        'get_etat_workflow' => 'select * from etats_workflow where id_etat = :id_etat',
        'get_reponses_formulaire_dossier' => buildSelectQuery('reponses_formulaire_produit', [], ['id_dossier = :id_dossier']),
        'get_inputs_formulaire' => 'select b.* from produits a, input_template_formulaire_produit b where a.id_produit = :id_produit and b.id_template = a.id_template_formulaire order by b.input_order',
        'get_inputs_formulaire_where_id_template' => 'select * from input_template_formulaire_produit where id_template = :id_template order by input_order',
        'get_workflow' => buildSelectQuery("workflows", [], ['id_workflow = :id_workflow']),
        'get_workflows_where_id_fournisseur' => buildSelectQuery("workflows", [], ['id_fournisseur = :id_fournisseur']),
        'get_formulaires_where_id_fournisseur' => buildSelectQuery("template_formulaire_produit", [], ['id_fournisseur = :id_fournisseur']),
        'get_template_formulaire' => buildSelectQuery("template_formulaire_produit", [], ['id_template = :id_template']),
        'get_last_fichier_dossier' => 'select a.* from fichiers a, fichiers_dossier b where a.id_fichier = b.id_fichier and b.id_dossier = :id_dossier order by a.updated_at desc limit 1',
        // update
        'update_produit' => 'update produits set nom_produit = :nom_produit, description_produit = :description_produit, id_template_formulaire = nullif(:id_template_formulaire,\'\'), id_workflow = nullif(:id_workflow,\'\') where id_produit = :id_produit',
        'update_pwd' => 'update utilisateurs set password_hash = :new_password_hash where id_utilisateur = :uid',
        'update_personne' => 'update personnes set nom_entreprise = :nom_entreprise, numero_entreprise = :numero_entreprise, est_un_particulier = :est_un_particulier, prenom = :prenom, nom_famille = :nom_famille, civilite = :civilite, email = nullif(:email, \'\') where id_personne = :id_personne',
        'update_personne_noemail' => 'update personnes set nom_entreprise = :nom_entreprise, numero_entreprise = :numero_entreprise, est_un_particulier = :est_un_particulier, prenom = :prenom, nom_famille = :nom_famille, civilite = :civilite where id_personne = :id_personne',
        'update_coordonnees' => 'update coordonnees set adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays, tel1 = :tel1, tel2 = :tel2 where id_coordonnees = (select id_coordonnees from personnes where id_personne = :id_personne)',
        'update_etat_dossier' => 'select update_etat_dossier(:id_dossier, :id_nouvel_etat, :id_author)',
        'update_etat_workflow' => 'update etats_workflow set description = :description, order_etat = :order_etat, role_responsable_etape = :role_responsable_etape, phase_etape = :phase_etape where id_etat = :id_etat',
        'update_workflow' => 'update workflows set nom_workflow = :nom_workflow where id_workflow = :id_workflow',
        'update_client' => 'update clients_des_commerciaux set infos_client_supplementaires = :infos_client_supplementaires where id_client = :id_client',
        'update_template_input' => 'update input_template_formulaire_produit set input_type = :input_type, input_description = :input_description, input_choices = :input_choices, input_html_attributes = :input_html_attributes where id_input = :id_input',
        'update_template_name' => 'update template_formulaire_produit set nom_template = :nom_template where id_template = :id_template',
        // autre
        'toggle_fichier_trash' => 'update fichiers set in_trash = ((-1 * in_trash) + 1) where id_fichier = :id_fichier',
        'supprimer_etat_workflow' => 'delete from etats_workflow where id_etat = :id_etat',
        'clients_commercial' => 'select * from clients where id_commercial = :id_commercial',
        'last_settings_update' => 'select last_user_update from utilisateurs where id_utilisateur = :uid',
        'account_infos_from_uid' => 'select u.last_user_update, u.user_role, p.email from utilisateurs u, personnes p where u.id_utilisateur = :uid and u.id_utilisateur = p.id_personne',
        'account_infos_from_email' => 'select * from utilisateurs u, personnes p where u.id_utilisateur = p.id_personne and p.email = :email',
        'uid_from_primary_email' => 'select id_personne from personnes where email = :email',
        'check_mime_type' => 'select description from _enum_mime_type where description = :mime_type',
        'count_file' => 'select count(*) from fichiers where file_name = :file_name',
    ][$key] . $filter_str;
}

function filtersToWhereClause($filters) {
    $output = [];
    foreach ($filters as $filter_name => $filter) {
        $output[] = $filter_name . ' in (' . join(',', $filter) . ')';
    }
    return '(' . join(') and (', $output) . ')';
}

function buildSelectQuery($table, $fields = [], $where = [], $options = "")
{
    return 'select ' . (count($fields) > 0 ? join(", ", $fields) : "*") . " from " . $table . (count($where) > 0 ? (" where " . join(" and ", $where)) : "") . ' ' . $options;
}

function getPDO()
{
    try {
        $db = new \PDO(
            'mysql:host=localhost;dbname=' . $_ENV['db_name'] . ';charset=utf8mb4',
            $_ENV['db_username'],
            $_ENV['db_password'],
        );
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // Attention, la ligne suivante ne marche que si les timezone sont installés sur la machine
        // https://dev.mysql.com/downloads/timezones.html
        // mais par défaut, il vaut mieux ne rien mettre et simplement laisser MySQL se caller sur la timezone de l'OS
        // $db->exec('SET SESSION time_zone = 'Europe/Paris'');
    } catch (\Exception $e) {
        throw $e;
    }

    return $db;
}

function runFile($filename, $sqlDir = __DIR__ . '/sql/')
{
    $connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . ' ' . $_ENV['db_name'] . ' --default-character-set=utf8';
    // echo $connexion_string . "\n";

    echo "--- $filename ---\n";
    $tmpString = file_get_contents($sqlDir . $filename);
    $tmpString = str_replace(':cmo_db_name', $_ENV['db_name'], $tmpString);

    $temp = tmpfile();
    fwrite($temp, $tmpString);
    $res = exec($connexion_string . ' -e "source ' . stream_get_meta_data($temp)['uri'] . '"');
    echo $res;
    fclose($temp);

    echo "\n";
}
