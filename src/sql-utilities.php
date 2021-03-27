<?php

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
        'tous_etats_dossier' => 'select * from _enum_etats_dossier order by id_enum_etat',
        // infos
        'infos_commercial' => 'select id_personne, prenom, nom_famille from commerciaux where id_personne = :uid',
        'infos_fournisseur' => 'select id_personne, prenom, nom_famille from fournisseurs where id_personne = :uid',
        'infos_client' => 'select cl.*, co.* from clients cl, coordonnees co where cl.id_personne = :id_client and co.id_coordonnees = cl.id_coordonnees',
        'infos_dossier' => 'select * from dossiers_enriched where id_dossier = :id_dossier',
        // new
        'new_user' => "select new_user(:user_type, :email, '', :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2) new_uid",
        'new_client' => 'select new_client(:id_commercial, :prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2, :email)',
        'new_dossier' => 'select new_dossier(:id_commercial, :id_client, :id_produit)',
        'new_fichier_dossier' => 'select new_fichier_dossier(:file_name, :mime_type, :id_dossier)',
        'new_fichier_produit' => 'select new_fichier_produit(:file_name, :mime_type, :id_produit)',
        'new_produit' => 'insert into produits(id_fournisseur, description_produit, nom_produit) values (:id_fournisseur, :description_produit, :nom_produit)',
        'new_etat_dossier' => 'insert into _enum_etats_dossier(description) values (:description)',
        'new_dossier_log' => 'insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (:id_dossier, :id_author, :nom_action, :desc_action)',
        // get
        'get_file' => 'select * from fichiers where file_name = :file_name',
        'get_produit' => 'select * from produits where id_produit = :id_produit',
        'get_etats_dossier' => 'select description from _enum_etats_dossier where id_enum_etat = :id_enum_etat',
        'get_logs_dossiers' => 'select * from logs_enriched where id_dossier = :id_dossier order by date_heure desc',
        'get_email' => 'select email from personnes where email = :email',
        // update
        'update_produit' => 'update produits set nom_produit = :nom_produit, description_produit = :desc_produit where id_produit = :id_produit',
        'update_pwd' => 'update utilisateurs set password_hash = :new_password_hash where id_utilisateur = :uid',
        'update_personne' => 'update personnes set prenom = :prenom, nom_famille = :nom_famille, civilite = :civilite, email = nullif(:email, \'\') where id_personne = :id_personne',
        'update_coordonnees' => 'update coordonnees set adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays, tel1 = :tel1, tel2 = :tel2 where id_coordonnees = (select id_coordonnees from personnes where id_personne = :id_personne)',
        // autre
        'produits_fournisseur' => 'select * from produits where id_fournisseur = :id_fournisseur',
        'clients_commercial' => 'select * from clients where id_commercial = :id_commercial',
        'last_settings_update' => 'select last_user_update from utilisateurs where id_utilisateur = :uid',
        'account_infos_from_uid' => 'select u.last_user_update, u.user_role, p.email from utilisateurs u, personnes p where u.id_utilisateur = :uid and u.id_utilisateur = p.id_personne',
        'account_infos_from_email' => 'select id_utilisateur, email, user_role, password_hash from utilisateurs u, personnes p where u.id_utilisateur = p.id_personne and p.email = :email',
        'uid_from_primary_email' => 'select id_personne from personnes where email = :email',
        'dossiers_client' => 'select * from dossiers_enriched where id_client = :id_client and id_commercial = :id_commercial order by date_creation desc',
        'fichiers_dossier' => 'select ff.* from fichiers ff, fichiers_dossier fp where ff.id_fichier = fp.id_fichier and fp.id_dossier = :id_dossier',
        'check_mime_type' => 'select description from _enum_mime_type where description = :mime_type',
        'count_file' => 'select count(*) from fichiers where file_name = :file_name',
        'supprimer_etat_dossier' => 'delete from _enum_etats_dossier where description = :description',
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
