<?php
require_once __DIR__ . '/../vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');

// { <nom_requete> : { sql: string, args: array<string> } }
function getSqlQueryString($key)
{
    return [
        // tous
        'tous_commerciaux' => 'select * from commerciaux',
        'tous_produits' => 'select * from produits',
        'tous_dossiers' => 'select * from dossiers_enriched',
        'tous_clients' => 'select * from clients',
        'tous_fournisseurs' => 'select * from fournisseurs',
        'tous_etats_produit' => 'select * from etats_produit',
        'tous_produits_fournisseur' => 'select * from produits where id_fournisseur = :id_fournisseur',
        'tous_dossiers_fournisseur' => 'select * from dossiers_enriched where id_fournisseur = :id_fournisseur',
        'tous_dossiers_client' => 'select * from dossiers_enriched where id_client = :id_client order by date_creation desc',
        'tous_dossiers_client_filtre_fournisseur' => 'select * from dossiers_enriched where id_client = :id_client and id_fournisseur = :id_fournisseur order by date_creation desc',
        'tous_fichiers_dossier' => 'select ff.* from fichiers ff, fichiers_dossier fp where ff.id_fichier = fp.id_fichier and fp.id_dossier = :id_dossier and ff.in_trash = :in_trash',
        // new
        'new_user' => "select new_user(:user_type, :email, '', :nom_entreprise, :numero_entreprise, :est_un_particulier, :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2) new_uid",
        'new_client' => 'select new_client(:id_commercial, :nom_entreprise, :numero_entreprise, :est_un_particulier, :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2, :email)',
        'new_dossier' => 'select new_dossier(:id_client, :id_produit)',
        'new_fichier_dossier' => 'select new_fichier_dossier(:file_name, :mime_type, :id_dossier)',
        'new_fichier_produit' => 'select new_fichier_produit(:file_name, :mime_type, :id_produit)',
        'new_produit' => 'insert into produits(id_fournisseur, description_produit, nom_produit) values (:id_fournisseur, :description_produit, :nom_produit)',
        'new_etat_produit' => 'select new_etat_produit(:id_produit, :description)',
        'new_dossier_log' => 'insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (:id_dossier, :id_author, :nom_action, :desc_action)',
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
        'get_etats_where_produit' => 'select * from etats_produit where id_produit = :id_produit order by order_etat',
        'get_etat_produit' => 'select * from etats_produit where id_etat = :id_etat',
        // update
        'update_produit' => 'update produits set nom_produit = :nom_produit, description_produit = :description_produit where id_produit = :id_produit',
        'update_pwd' => 'update utilisateurs set password_hash = :new_password_hash where id_utilisateur = :uid',
        'update_personne' => 'update personnes set nom_entreprise = :nom_entreprise, numero_entreprise = :numero_entreprise, est_un_particulier = :est_un_particulier, prenom = :prenom, nom_famille = :nom_famille, civilite = :civilite, email = nullif(:email, \'\') where id_personne = :id_personne',
        'update_personne_noemail' => 'update personnes set nom_entreprise = :nom_entreprise, numero_entreprise = :numero_entreprise, est_un_particulier = :est_un_particulier, prenom = :prenom, nom_famille = :nom_famille, civilite = :civilite where id_personne = :id_personne',
        'update_coordonnees' => 'update coordonnees set adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays, tel1 = :tel1, tel2 = :tel2 where id_coordonnees = (select id_coordonnees from personnes where id_personne = :id_personne)',
        'update_etat_dossier' => 'select update_etat_dossier(:id_dossier, :id_nouvel_etat, :id_author)',
        'update_etat_produit' => 'update etats_produit set description = :description, order_etat = :order_etat where id_etat = :id_etat',
        // autre
        'toggle_fichier_trash' => 'update fichiers set in_trash = ((-1 * in_trash) + 1) where id_fichier = :id_fichier',
        'supprimer_etat_produit' => 'delete from etats_produit where id_etat = :id_etat',
        'clients_commercial' => 'select * from clients where id_commercial = :id_commercial',
        'last_settings_update' => 'select last_user_update from utilisateurs where id_utilisateur = :uid',
        'account_infos_from_uid' => 'select u.last_user_update, u.user_role, p.email from utilisateurs u, personnes p where u.id_utilisateur = :uid and u.id_utilisateur = p.id_personne',
        'account_infos_from_email' => 'select * from utilisateurs u, personnes p where u.id_utilisateur = p.id_personne and p.email = :email',
        'uid_from_primary_email' => 'select id_personne from personnes where email = :email',
        'check_mime_type' => 'select description from _enum_mime_type where description = :mime_type',
        'count_file' => 'select count(*) from fichiers where file_name = :file_name',
        'edit_etat' => 'update dossiers set etat_dossier = :new_value where id_dossier = :id_dossier',
    ][$key];
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

function runFile($filename)
{
    $connexion_string = "mysql --user=" . $_ENV['db_username'] . " -p" . $_ENV['db_password'] . " " . $_ENV['db_name'] . ' --default-character-set=utf8';
    // echo $connexion_string . "\n";
    
    echo "--- $filename ---\n";
    $tmpString = file_get_contents(__DIR__ . '/sql/' . $filename);
    $tmpString = str_replace(':cmo_db_name', $_ENV['db_name'], $tmpString);

    $temp = tmpfile();
    fwrite($temp, $tmpString);
    $res = exec($connexion_string . ' -e "source ' . stream_get_meta_data($temp)['uri'] . '"');
    echo $res;
    fclose($temp);

    echo "\n";
}