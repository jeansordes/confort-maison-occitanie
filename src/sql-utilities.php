<?php

// { <nom_requete> : { sql: string, args: array<string> } }
function getSqlQueryString($key)
{
    return [
        'infos_commercial' => "select id_personne, prenom, nom_famille from commerciaux where id_personne = :uid",
        'clients_commercial' => "select * from clients_w_nb_dossiers where id_commercial = :id_commercial",
        'tous_commerciaux' => "select * from commerciaux",
        'new_commercial' => "select new_user('commercial', :email, '', :prenom, :nom_famille) new_uid",
        'last_settings_update' => "select last_user_update from utilisateurs where id_utilisateur = :uid",
        'update_pwd' => 'update utilisateurs set password_hash = :new_password_hash where id_utilisateur = :uid',
        'account_infos_from_uid' => "select last_user_update, user_role, primary_email from utilisateurs where id_utilisateur = :uid",
        'account_infos_from_email' => 'select id_utilisateur, primary_email, user_role, password_hash from utilisateurs where primary_email = :email',
        'uid_from_primary_email' => 'select id_utilisateur from utilisateurs where primary_email = :email',
        'new_client' => 'select new_client(:prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2)',
        'new_email' => 'insert into user_emails(email_string, id_user) values (:email, :uid)',
        'tous_clients' => "select * from clients_w_nb_dossiers",
        'infos_client' => 'select id_personne, prenom, nom_famille from clients where id_personne = :id_client and id_commercial = :id_commercial',
        'dossiers_client' => 'select * from dossiers_enriched where id_client = :id_client and id_commercial = :id_commercial',
        'new_dossier' => 'insert into dossiers (id_client, id_produit) values (:id_client, :id_produit)',
        'tous_produits' => 'select * from produits',
        'infos_dossier' => 'select * from dossiers_enriched where id_dossier = :id_dossier',
        'fichiers_dossier' => 'select ff.* from fichiers ff, fichiers_dossier fp where ff.id_fichier = fp.id_fichier and fp.id_dossier = :id_dossier',
        'tous_fournisseurs' => 'select * from fournisseurs',
        'new_fichier_dossier' => 'select new_fichier_dossier(:file_name, :mime_type, :id_dossier)',
        'new_fichier_produit' => 'select new_fichier_produit(:file_name, :mime_type, :id_produit)',
        'check_mime_type' => 'select description from _enum_mime_type where description = :mime_type',
        'get_file' => 'select * from fichiers where file_name = :file_name',
        'new_comment_client' => 'update clients_des_commerciaux set commentaire_commercial = :comment where id_client = :id_client',
        'get_comment_client' => 'select commentaire_commercial from clients_des_commerciaux where id_client = :id_client',
        '' => '',
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
        // $db->exec("SET SESSION time_zone = 'Europe/Paris'");
    } catch (\Exception $e) {
        throw $e;
    }

    return $db;
}
