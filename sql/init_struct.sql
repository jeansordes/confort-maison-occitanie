drop database if exists confort_maison_occitanie;
create database confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
use confort_maison_occitanie;

-- 'commercial', 'client', 'fournisseur', 'admin'
create or replace table enum_user_role (
    description varchar(50) not null primary key
);

-- 'autoentrepreneur', 'entreprise', 'vendeur à domicile'
create or replace table enum_statut_societe (
    description varchar(50) not null primary key
);

-- https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
-- application/pdf ou image/png
create or replace table enum_mime_type (
    description varchar(50) not null primary key
);

-- 'proposition commerciale', 'commande validée par le client',
-- 'validation commande par le fournisseur', 'installation planifiée', 'instalée'
create or replace table enum_etat_projet (
    description varchar(50) not null primary key
);

create or replace table user (
    id int(11) not null auto_increment primary key,
    prenom text default null,
    nom_famille text default null,
    civilite enum('mr', 'mme') default null,
    -- alter table user change civilite civilite enum('mr', 'mme', 'nouvelle_civilite') not null;
    adresse text default null,
    code_postal text default null,
    ville text default null,
    pays text default null,
    tel1 text default null,
    tel2 text default null,
    user_role varchar(50) not null,
    constraint
        foreign key (user_role) references enum_user_role(description)
);

create or replace table user_emails (
    -- varchar(255) https://stackoverflow.com/a/8242609
    email_string varchar(255) not null primary key check (email_string REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$'),
    user_id int(11) not null,
    constraint
        foreign key (user_id) references user(id)
);

create or replace table user_account (
    user_id int(11) primary key not null,
    password_hash text not null default '',
    last_time_settings_changed timestamp not null default current_timestamp(),
    primary_email varchar(255) not null,
    constraint
        foreign key (user_id) references user(id),
        foreign key (primary_email) references user_emails(email_string)
);

create or replace table societe (
    id int(11) not null primary key,
    nom_societe text not null,
    numero_societe text not null,
    id_contact int(11) not null,
    statut_societe varchar(50) not null,
    constraint
        foreign key (id_contact) references user(id),
        foreign key (statut_societe) references enum_statut_societe(description)
);

create or replace table projet (
    id int(11) not null auto_increment primary key,
    id_commercial int(11) not null,
    id_client int(11) not null,
    nom_projet text not null,
    description_projet text default null,
    constraint
        foreign key (id_commercial) references user(id),
        foreign key (id_client) references user(id)
);

create or replace table fichiers (
    id int(11) not null auto_increment primary key,
    id_projet int(11) not null,
    file_url text not null,
    updated_at timestamp not null default current_timestamp(),
    mime_type varchar(50) not null,
    constraint
        foreign key (id_projet) references projet(id),
        foreign key (mime_type) references enum_mime_type(description)
);

create or replace table avancement_projet (
    id int(11) not null auto_increment primary key,
    id_projet int(11) not null,
    date_heure timestamp not null default current_timestamp(),
    etat_projet varchar(50) not null,
    commentaire_avancement text default null,
    id_auteur int(11) not null,
    constraint
        foreign key (id_projet) references projet(id),
        foreign key (etat_projet) references enum_etat_projet(description),
        foreign key (id_auteur) references user(id)
);
