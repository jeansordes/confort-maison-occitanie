<?php

// { <nom_requete> : { sql: string, args: array<string> } }
function getSqlQueryString($key)
{
    return [
        'infos_commercial' => "select id, prenom, nom_famille from commerciaux where id = :uid",
        'clients_commercial' => "select * from clients_w_nb_projets where id_commercial = :id_commercial",
        'tous_commerciaux' => "select * from commerciaux",
        'new_commercial' => "select new_user('commercial', :prenom, :nom_famille, :email) new_uid",
        'last_settings_update' => "select last_time_settings_changed from user_account where id_user = :uid",
        'update_pwd' => 'update user_account set password_hash = :new_password_hash where id_user = :uid',
        'account_infos_from_uid' => "select last_time_settings_changed, user_role, primary_email from user_account where id_user = :uid",
        'account_infos_from_email' => 'select id_user, primary_email, user_role, password_hash from user_account where primary_email = :email',
        'uid_from_primary_email' => 'select id_user from user_account where primary_email = :email',
        'new_client' => 'insert into user(prenom, nom_famille, civilite, adresse, code_postal, ville, pays, tel1, tel2) values (:prenom, :nom_famille, :civilite, :adresse, :code_postal, :ville, :pays, :tel1, :tel2)',
        'new_email' => 'insert into user_emails(email_string, id_user) values (:email, :uid)',
        'tous_clients' => "select * from clients_w_nb_projets",
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
        $db->exec("SET SESSION time_zone = '+2:00'");
    } catch (\Exception $e) {
        throw $e;
    }

    return $db;
}
