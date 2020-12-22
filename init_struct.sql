drop database if exists confort_maison_occitanie;
create database confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
use confort_maison_occitanie;

drop table if exists user;
create table user (
    id int(11) not null auto_increment primary key,
    prenom text character set utf8mb4 default null,
    nom_famille text character set utf8mb4 default null,
    civilite enum('mr', 'mme') default null,
    -- alter table user change civilite civilite enum('mr', 'mme', 'nouvelle_civilite') not null;
    adresse text character set utf8mb4 default null,
    code_postal text character set utf8mb4 default null,
    ville text character set utf8mb4 default null,
    pays text character set utf8mb4 default null,
    tel1 text character set utf8mb4 default null,
    tel2 text character set utf8mb4 default null
);

drop table if exists user_emails;
create table user_emails (
    email_id int(11) not null primary key auto_increment,
    email_string text character set utf8mb4 not null unique check (email_string REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$'),
    user_id int(11) not null,
    constraint foreign key (user_id) references user(id)
);

-- 'commercial', 'client', 'fournisseur', 'admin'
drop table if exists enum_user_role;
create table enum_user_role (
    id int(11) primary key not null auto_increment,
    description text character set utf8mb4 not null
);

drop table if exists user_account;
create table user_account (
    primary_email_id int(11) primary key not null,
    password_hash text character set utf8mb4 not null,
    user_role int(11) not null,
    last_time_settings_changed timestamp not null default current_timestamp(),
    constraint
        foreign key (primary_email_id) references user_emails(email_id),
        foreign key (user_role) references enum_user_role(id)
);

-- 'autoentrepreneur', 'entreprise', 'vendeur à domicile'
drop table if exists enum_statut_societe;
create table enum_statut_societe (
    id int(11) not null primary key auto_increment,
    description text character set utf8mb4 not null
);

drop table if exists societe;
create table societe (
    id int(11) not null primary key,
    nom_societe text character set utf8mb4 not null,
    numero_societe text character set utf8mb4 not null,
    id_contact int(11) not null,
    statut_societe int(11) not null,
    constraint
        foreign key (id_contact) references user(id),
        foreign key (statut_societe) references enum_statut_societe(id)
);

drop table if exists projet;
create table projet (
    id int(11) not null auto_increment primary key,
    id_commercial int(11) not null,
    id_client int(11) not null,
    nom_projet text character set utf8mb4 not null,
    description_projet text character set utf8mb4 default null,
    constraint
        foreign key (id_commercial) references user(id),
        foreign key (id_client) references user(id)
);

drop table if exists enum_mime_type;
create table enum_mime_type (
    id int(11) not null primary key auto_increment,
    description text character set utf8mb4 not null
);
-- https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
-- application/pdf ou image/png

drop table if exists fichiers;
create table fichiers (
    id int(11) not null auto_increment primary key,
    id_projet int(11) not null,
    file_url text character set utf8mb4 not null,
    updated_at timestamp not null default current_timestamp(),
    mime_type int(11) not null,
    constraint
        foreign key (id_projet) references projet(id),
        foreign key (mime_type) references enum_mime_type(id)
);

drop table if exists enum_etat_projet;
create table enum_etat_projet (
    id int(11) not null primary key auto_increment,
    description text character set utf8mb4 not null
);
-- 'proposition commerciale', 'commande validée par le client',
-- 'validation commande par le fournisseur', 'installation planifiée', 'instalée'

drop table if exists avancement_projet;
create table avancement_projet (
    id int(11) not null auto_increment primary key,
    id_projet int(11) not null,
    date_heure timestamp not null default current_timestamp(),
    etat_projet int(11) not null,
    commentaire_avancement text character set utf8mb4 default null,
    id_auteur int(11) not null,
    constraint
        foreign key (id_projet) references projet(id),
        foreign key (etat_projet) references enum_etat_projet(id),
        foreign key (id_auteur) references user(id)
);

--
-- Fonctions et vues
--

create or replace function create_commercial(
    p_prenom text character set utf8mb4,
    p_nom_famille text character set utf8mb4,
    p_email text character set utf8mb4
) returns int(11)
begin
    declare v_user_role int(11);
    declare v_new_uid int(11);
    select id into v_user_role from enum_user_role where description = 'commercial';

    insert into user(prenom, nom_famille) values (p_prenom, p_nom_famille);
    set v_new_uid = last_insert_id();
    insert into user_emails(email_string, user_id) values (p_email, v_new_uid);
    insert into user_account(primary_email_id, password_hash, user_role) values (last_insert_id(), '', v_user_role);
    return v_new_uid;
end;

create or replace view user_account_enriched as
    select a.*, e.email_string email_string, r.description user_role_description, e.user_id user_id
    from user_account a, user_emails e, enum_user_role r
    where a.primary_email_id = e.email_id and r.id = a.user_role;