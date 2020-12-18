drop database if exists confort_maison_occitanie;
create database confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
use confort_maison_occitanie;

drop table if exists user;
create table user (
    id int(11) not null auto_increment,
    prenom text character set utf8mb4 default null,
    nom_famille text character set utf8mb4 default null,
    civilite enum('mr', 'mme') default null,
    -- alter table user change civilite civilite enum('mr', 'mme', 'nouvelle_civilite') not null;
    adresse text character set utf8mb4 default null,
    code_postal text character set utf8mb4 default null,
    ville text character set utf8mb4 default null,
    pays text character set utf8mb4 default null,
    tel1 text character set utf8mb4 default null,
    tel2 text character set utf8mb4 default null,
    email text character set utf8mb4 not null unique check (email like '%@%.%'),
    constraint
        primary key (id)
);

-- 'commercial', 'client', 'fournisseur', 'admin'
drop table if exists enum_user_role;
create table enum_user_role (
    id int(11) primary key not null auto_increment,
    description text character set utf8mb4 not null
);

drop table if exists user_account;
create table user_account (
    user_id int(11) not null,
    password_hash text character set utf8mb4 not null,
    user_role int(11) not null,
    last_time_settings_changed timestamp not null default current_timestamp(),
    constraint
        primary key (user_id),
        foreign key (user_id) references user(id),
        foreign key (user_role) references enum_user_role(id)
);

drop table if exists enum_statut_societe;
create table enum_statut_societe (
    id int(11) not null primary key auto_increment,
    description text character set utf8mb4 not null
);

drop table if exists societe;
create table societe (
    id int(11) not null,
    nom_societe text character set utf8mb4 not null,
    numero_societe text character set utf8mb4 not null,
    id_contact int(11) not null,
    statut_societe int(11) not null,
    constraint
        primary key (id),
        foreign key (id_contact) references user(id),
        foreign key (statut_societe) references enum_statut_societe(id)
);

drop table if exists projet;
create table projet (
    id int(11) not null auto_increment,
    id_commercial int(11) not null,
    id_client int(11) not null,
    nom_projet text character set utf8mb4 not null,
    description_projet text character set utf8mb4 default null,
    constraint
        primary key (id),
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
    id int(11) not null auto_increment,
    id_projet int(11) not null,
    file_url text character set utf8mb4 not null,
    updated_at timestamp not null default current_timestamp(),
    mime_type int(11) not null,
    constraint
        primary key (id),
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
    id int(11) not null auto_increment,
    id_projet int(11) not null,
    date_heure timestamp not null default current_timestamp(),
    etat_projet int(11) not null,
    commentaire_avancement text character set utf8mb4 default null,
    id_auteur int(11) not null,
    constraint
        primary key (id),
        foreign key (id_projet) references projet(id),
        foreign key (etat_projet) references enum_etat_projet(id),
        foreign key (id_auteur) references user(id)
);