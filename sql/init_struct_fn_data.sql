drop database if exists cwapgwkr_confort_maison_occitanie;
create database cwapgwkr_confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
use cwapgwkr_confort_maison_occitanie;

create or replace table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role(description) values ('admin');
insert into _enum_user_role(description) values ('commercial');
insert into _enum_user_role(description) values ('fournisseur');

create or replace table _enum_statut_societe (
    description varchar(50) not null primary key
);
insert into _enum_statut_societe(description) values ('autoentrepreneur');
insert into _enum_statut_societe(description) values ('entreprise');
insert into _enum_statut_societe(description) values ('vendeur à domicile');

-- https:$$developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
-- application/pdf ou image/png
create or replace table _enum_mime_type (
    description varchar(50) not null primary key
);
insert into _enum_mime_type(description) values ('image/png');
insert into _enum_mime_type(description) values ('image/jpeg');
insert into _enum_mime_type(description) values ('image/gif');
insert into _enum_mime_type(description) values ('application/pdf');

create or replace table _enum_etat_dossier (
    description varchar(50) not null primary key
);
insert into _enum_etat_dossier(description) values ('proposition commerciale');
insert into _enum_etat_dossier(description) values ('commande validée par le client');
insert into _enum_etat_dossier(description) values ('validation commande par le fournisseur');
insert into _enum_etat_dossier(description) values ('installation planifiée');
insert into _enum_etat_dossier(description) values ('instalée');

create or replace table coordonnees (
    id_coordonnees int(11) not null auto_increment primary key,
    adresse text default null,
    code_postal text default null,
    ville text default null,
    pays text default null,
    tel1 text default null,
    tel2 text default null
);

create or replace table personnes (
    id_personne int(11) not null auto_increment primary key,
    prenom text default null,
    nom_famille text default null,
    civilite enum('mr', 'mme') default null,
    -- alter table user change civilite civilite enum('mr', 'mme', 'nouvelle_civilite') not null;
    id_coordonnees int(11) default null,
    constraint
        foreign key (id_coordonnees) references coordonnees(id_coordonnees)
);

create or replace table user_emails (
    -- varchar(200) https:$$stackoverflow.com/a/8242609
    -- 200 instead of 255, cause it stores 4 bytes per character, so 4*255 = 1020
    -- and on planethoster, the maximum limit is 1000 bytes for a primary key
    email_string varchar(200) not null primary key check (email_string REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$'),
    id_user int(11) not null,
    constraint
        foreign key (id_user) references personnes(id_personne)
);

create or replace table utilisateurs (
    id_utilisateur int(11) primary key not null,
    last_user_update time not null default current_timestamp(),
    user_role varchar(50) not null,
    primary_email varchar(200) not null,
    password_hash text not null,
    commentaire_admin text default null,
    constraint
        foreign key (user_role) references _enum_user_role(description),
        foreign key (id_utilisateur) references personnes(id_personne),
        foreign key (primary_email) references user_emails(email_string)
);

create or replace table clients_des_commerciaux (
    id_client int(11) primary key not null,
    id_commercial int(11) not null,
    commentaire_commercial text default null,
    constraint
        foreign key (id_client) references personnes(id_personne),
        foreign key (id_commercial) references personnes(id_personne)
);

create or replace table societes (
    id_societe int(11) not null auto_increment primary key,
    nom_societe text not null,
    numero_societe text,
    id_representant int(11) not null,
    id_coordonnees_entreprise int(11) default null,
    statut_societe varchar(50),
    commentaire_admin text default null,
    constraint
        foreign key (id_representant) references personnes(id_personne),
        foreign key (id_coordonnees_entreprise) references coordonnees(id_coordonnees),
        foreign key (statut_societe) references _enum_statut_societe(description)
);

create or replace table employes (
    id_employe int(11) primary key not null,
    id_societe int(11) not null,
    constraint
        foreign key (id_employe) references personnes(id_personne),
        foreign key (id_societe) references societes(id_societe)
);

create or replace table produits (
    id_produit int(11) not null auto_increment primary key,
    nom_produit text not null,
    id_fournisseur int(11) not null,
    description_produit text default null,
    constraint
        foreign key (id_fournisseur) references societes(id_societe)
);

create or replace table dossiers (
    id_dossier int(11) not null auto_increment primary key,
    id_client int(11) not null,
    id_produit int(11) not null,
    commentaire text default null,
    date_creation timestamp not null default current_timestamp(),
    constraint
        foreign key (id_client) references clients_des_commerciaux (id_client),
        foreign key (id_produit) references produits(id_produit)
);

create or replace table fichiers (
    id_fichier int(11) not null auto_increment primary key,
    file_name text not null,
    updated_at timestamp not null default current_timestamp(),
    mime_type varchar(50) not null,
    constraint
        foreign key (mime_type) references _enum_mime_type(description)
);

create or replace table fichiers_produit (
    id_produit int(11) not null,
    id_fichier int(11) not null,
    constraint
        primary key (id_produit, id_fichier),
        foreign key (id_produit) references produits(id_produit),
        foreign key (id_fichier) references fichiers(id_fichier)
);

create or replace table fichiers_dossier (
    id_dossier int(11) not null,
    id_fichier int(11) not null,
    constraint
        primary key (id_dossier, id_fichier),
        foreign key (id_dossier) references dossiers(id_dossier),
        foreign key (id_fichier) references fichiers(id_fichier)
);

create or replace table avancement_dossier (
    id_avancement int(11) not null auto_increment primary key,
    id_dossier int(11) not null,
    date_heure timestamp not null default current_timestamp(),
    etat_dossier varchar(50) not null,
    commentaire_avancement text default null,
    id_auteur int(11) not null,
    constraint
        foreign key (id_dossier) references dossiers(id_dossier),
        foreign key (etat_dossier) references _enum_etat_dossier(description),
        foreign key (id_auteur) references personnes(id_personne)
);

DELIMITER $$
create or replace function new_user (
    p_role varchar(50),
    p_email text,
    p_password_hash text,
    p_prenom text,
    p_nom_famille text
) returns int(11) begin
    insert into personnes(prenom, nom_famille) values (p_prenom, p_nom_famille);
    set @v_uid = last_insert_id();
    insert into user_emails(email_string, id_user) values (p_email, @v_uid);
    insert into utilisateurs(id_utilisateur, user_role, primary_email, password_hash) values (@v_uid, p_role, p_email, p_password_hash);
    return @v_uid;
end
$$

$$
create or replace function new_client(
    p_prenom text,
    p_nom_famille text,
    p_civilite text,
    p_adresse text,
    p_code_postal text,
    p_ville text,
    p_pays text,
    p_tel1 text,
    p_tel2 text
) returns int(11) begin
    insert into coordonnees(adresse, code_postal, ville, pays, tel1, tel2)
        values (p_adresse, p_code_postal, p_ville, p_pays, p_tel1, p_tel2);
    set @id_coordonnees = last_insert_id();
    insert into personnes(prenom, nom_famille, civilite, id_coordonnees)
        values (p_prenom, p_nom_famille, p_civilite, @id_coordonnees);
    return last_insert_id();
end
$$

$$
create or replace function new_fichier_dossier(
    p_filename text,
    p_file_mime_type text,
    p_project_id int(11)
) returns int(11) begin
    insert into fichiers(file_name, mime_type) values (p_filename, p_file_mime_type);
    set @id_fichier = last_insert_id();
    insert into fichiers_dossier(id_dossier, id_fichier)
        values (p_project_id, @id_fichier);
    return @id_fichier;
end
$$

$$
create or replace function new_fichier_produit(
    p_filename text,
    p_file_mime_type text,
    p_produit_id int(11)
) returns int(11) begin
    insert into fichiers(file_name, mime_type) values (p_filename, p_file_mime_type);
    set @id_fichier = last_insert_id();
    insert into fichiers_produit(id_produit, id_fichier)
        values (p_produit_id, @id_fichier);
    return @id_fichier;
end
$$

create or replace view clients as
    select
        a.id_commercial,
        u.*,
        coalesce((select count(p.id_dossier) nb_dossiers
            from dossiers p where u.id_personne = p.id_client group by p.id_client),0) nb_dossiers
        from personnes u, clients_des_commerciaux a, (
            select id_personne from personnes, utilisateurs
            except select id_utilisateur from utilisateurs
        ) t where u.id_personne = t.id_personne and a.id_client = u.id_personne;

create or replace view commerciaux as
    select
        a.commentaire_admin,
        (select count(*) from clients_des_commerciaux where id_commercial = u.id_personne) nb_clients,
        (select count(*) from dossiers d, clients_des_commerciaux cc
            where d.id_client = cc.id_client and cc.id_commercial = u.id_personne) nb_dossiers,
        u.*
    from personnes u, utilisateurs a
    where u.id_personne = a.id_utilisateur
        and a.user_role = 'commercial';

create or replace view fournisseurs as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'fournisseur';

create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*
    from clients_des_commerciaux a, dossiers b, produits c
    where a.id_client = b.id_client and b.id_produit = c.id_produit;

create or replace view fichiers_enriched as
    select a.*, b.id_dossier
    from fichiers a, fichiers_dossier b, fichiers_produit c
    where a.id_fichier = b.id_fichier or a.id_fichier = c.id_fichier;

select '';