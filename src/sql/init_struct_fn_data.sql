drop database if exists :cmo_db_name;
create database :cmo_db_name default character set utf8mb4 collate utf8mb4_general_ci;
use :cmo_db_name;

create or replace table _enum_user_role (
    description varchar(50) not null primary key
);

create or replace table _enum_statut_societe (
    description varchar(50) not null primary key
);

create or replace table _enum_mime_type (
    description varchar(50) not null primary key comment 'application/pdf ou image/png (https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types)'
);

create table _enum_phases_dossier (
    description varchar(50) primary key
);

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
    civilite enum('mr', 'mme', '') default null comment 'alter table user change civilite civilite enum(''mr'', ''mme'', ''nouvelle_civilite'') not null;',
    nom_entreprise text default null,
    numero_entreprise text default null,
    est_un_particulier boolean default 1,
    id_coordonnees int(11) default null,
    email varchar(200) default null check (email REGEXP '^[A-Z0-9._%\\-+]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$'),
    constraint
        foreign key (id_coordonnees) references coordonnees(id_coordonnees)
);

create or replace table utilisateurs (
    id_utilisateur int(11) primary key not null,
    last_user_update time not null default current_timestamp(),
    user_role varchar(50) not null,
    password_hash text not null,
    constraint
        foreign key (user_role) references _enum_user_role(description),
        foreign key (id_utilisateur) references personnes(id_personne)
);

create or replace table clients_des_commerciaux (
    id_client int(11) primary key not null,
    id_commercial int(11) not null,
    infos_client_supplementaires text default null,
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

create or replace table template_formulaire_produit (
    id_template int(11) not null auto_increment primary key,
    nom_template text not null
);

create or replace table produits (
    id_produit int(11) not null auto_increment primary key,
    nom_produit text not null,
    id_fournisseur int(11) not null references utilisateurs(id_utilisateur),
    description_produit text default null,
    id_workflow int(11) default null references workflows(id_workflow),
    id_template_formulaire int(11) default null references template_formulaire_produit(id_template)
);

create or replace table workflows (
    id_workflow int(11) not null auto_increment primary key,
    nom_workflow text not null,
    id_fournisseur int(11) not null references utilisateurs(id_utilisateur)
);

create or replace table etats_workflow (
    id_etat int(11) not null auto_increment primary key,
    description varchar(50) not null,
    order_etat int(11) not null,
    role_responsable_etape varchar(50) default 'commercial' references _enum_user_role(description),
    phase_etape varchar(50) default 'normal' not null references _enum_phases_dossier(description),
    id_workflow int(11) not null references workflows(id_workflow)
);

create or replace table _enum_input_type (
    description varchar(50) not null primary key
);

create or replace table input_template_formulaire_produit (
    id_input int(11) not null auto_increment primary key,
    id_template int(11) not null references template_formulaire_produit(id_template),
    input_type varchar(50) not null,
    input_description text not null,
    input_choices text default null,
    input_html_attributes text default null,
    input_order int(11) not null
);

create or replace table dossiers (
    id_dossier int(11) not null auto_increment primary key,
    id_client int(11) not null,
    id_produit int(11) not null,
    commentaire text default null,
    etat_workflow_dossier int(11) default null references etats_workflow(id_etat),
    constraint
        foreign key (id_client) references clients_des_commerciaux (id_client),
        foreign key (id_produit) references produits(id_produit)
);

create or replace table reponses_formulaire_produit (
    id_reponse int(11) not null auto_increment primary key,
    id_dossier int(11) not null references dossiers(id_dossier),
    id_input int(11) not null references input_template_formulaire_produit(id_input),
    value_reponse text not null default ''
);

create or replace table fichiers (
    id_fichier int(11) not null auto_increment primary key,
    file_name text not null,
    updated_at timestamp not null default current_timestamp(),
    mime_type varchar(50) not null,
    fichier_in_trash boolean default 0,
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

create or replace table logs_dossiers
(
    id_log         int(11)     not null auto_increment primary key,
    id_dossier     int(11)     not null,
    id_utilisateur int(11)     not null,
    nom_action     varchar(50) not null,
    desc_action    text default null,
    date_heure timestamp not null default current_timestamp(),
    constraint
        foreign key (id_dossier) references dossiers (id_dossier),
        foreign key (id_utilisateur) references utilisateurs (id_utilisateur)
);

delimiter $$
create or replace function new_user (
    p_role varchar(50),
    p_email text,
    p_password_hash text,
    p_nom_entreprise text,
    p_numero_entreprise text,
    p_est_un_particulier boolean,
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
    insert into personnes(prenom, nom_famille, civilite, id_coordonnees, email, nom_entreprise, numero_entreprise, est_un_particulier)
        values (p_prenom, p_nom_famille, p_civilite, @id_coordonnees, p_email, p_nom_entreprise, p_numero_entreprise, p_est_un_particulier);
    set @v_uid = last_insert_id();
    insert into utilisateurs(id_utilisateur, user_role, password_hash) values (@v_uid, p_role, p_password_hash);
    return @v_uid;
end
$$

$$
create or replace function new_client(
    p_id_commercial int(11),
    p_nom_entreprise text,
    p_numero_entreprise text,
    p_est_un_particulier boolean,
    p_prenom text,
    p_nom_famille text,
    p_civilite text,
    p_adresse text,
    p_code_postal text,
    p_ville text,
    p_pays text,
    p_tel1 text,
    p_tel2 text,
    p_email varchar(200)
) returns int(11) begin
    insert into coordonnees(adresse, code_postal, ville, pays, tel1, tel2)
        values (p_adresse, p_code_postal, p_ville, p_pays, p_tel1, p_tel2);
    set @id_coordonnees = last_insert_id();
    insert into personnes(prenom, nom_famille, civilite, id_coordonnees, email, nom_entreprise, numero_entreprise, est_un_particulier)
        values (p_prenom, p_nom_famille, p_civilite, @id_coordonnees, nullif(p_email, ''), p_nom_entreprise, p_numero_entreprise, p_est_un_particulier);
    set @id_client = last_insert_id();
    insert into clients_des_commerciaux(id_client, id_commercial) values (@id_client, p_id_commercial);
    return @id_client;
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

$$
create or replace function new_dossier(
    p_id_client int(11),
    p_id_produit int(11)
) returns int(11) begin
    select a.id_etat, a.description into @id_etat_initial, @initial_dossier_etat
    from etats_workflow a, produits b
    where a.id_workflow = b.id_workflow
      and b.id_produit = p_id_produit
    order by a.order_etat limit 1;
    insert into dossiers(id_client, id_produit, etat_workflow_dossier) values (p_id_client, p_id_produit, @id_etat_initial);
    set @id_dossier = last_insert_id();
    select id_commercial into @id_commercial from clients_des_commerciaux where id_client = p_id_client;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (@id_dossier, @id_commercial, 'Initialisation état du dossier', concat('État du dossier : ', @initial_dossier_etat));
    return @id_dossier;
end
$$

$$
create or replace function update_etat_dossier(
    p_id_dossier int(11),
    p_id_nouvel_etat int(11),
    p_id_author int(11)
) returns int(11) begin
    update dossiers set etat_workflow_dossier = p_id_nouvel_etat where id_dossier = p_id_dossier;
    select description into @nouvel_etat from etats_workflow where id_etat = p_id_nouvel_etat;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action)
        values (p_id_dossier, p_id_author, 'Changement de l''état du dossier', concat('État du dossier : ', @nouvel_etat));
    return p_id_dossier;
end
$$

$$
create or replace function new_etat_workflow(
    p_id_workflow int(11),
    p_description varchar(50)
) returns int(11) begin
    select count(*) into @new_order_etat from etats_workflow where id_workflow = p_id_workflow;
    insert into etats_workflow(description, id_workflow, order_etat) values (p_description, p_id_workflow, @new_order_etat);
    return p_id_workflow;
end
$$

create or replace view clients as
    select
        a.*,
        u.*,
        coalesce((select count(p.id_dossier) nb_dossiers
            from dossiers p where u.id_personne = p.id_client group by p.id_client),0) nb_dossiers
        from personnes u, clients_des_commerciaux a, (
            select id_personne from personnes, utilisateurs
            except select id_utilisateur from utilisateurs
        ) t where u.id_personne = t.id_personne and a.id_client = u.id_personne;

create or replace view commerciaux as
    select
        (select count(*) from clients_des_commerciaux where id_commercial = u.id_personne) nb_clients,
        (select count(*) from dossiers d, clients_des_commerciaux cc
            where d.id_client = cc.id_client and cc.id_commercial = u.id_personne) nb_dossiers,
        u.*
    from personnes u, utilisateurs a
    where u.id_personne = a.id_utilisateur
        and a.user_role = 'commercial';

create or replace view fournisseurs as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'fournisseur';

create or replace view admins as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'admin';

create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur, d.role_responsable_etape, d.phase_etape
    from clients_des_commerciaux a, dossiers b, produits c, etats_workflow d
    where a.id_client = b.id_client and b.id_produit = c.id_produit and d.id_etat = b.etat_workflow_dossier
    order by d.phase_etape desc;

create or replace view fichiers_enriched as
    select a.*, b.id_dossier
    from fichiers a, fichiers_dossier b, fichiers_produit c
    where a.id_fichier = b.id_fichier or a.id_fichier = c.id_fichier;

create or replace view logs_enriched as
    select l.*, p.*,
    nullif((select u.user_role from utilisateurs u where p.id_personne = u.id_utilisateur),'client') personne_role
    from logs_dossiers l, personnes p where p.id_personne = l.id_utilisateur
    order by date_heure;

insert into _enum_statut_societe(description) values ('autoentrepreneur');
insert into _enum_statut_societe(description) values ('entreprise');
insert into _enum_statut_societe(description) values ('vendeur à domicile');

insert into _enum_user_role(description) values ('admin');
insert into _enum_user_role(description) values ('commercial');
insert into _enum_user_role(description) values ('fournisseur');

insert into _enum_mime_type(description) values ('image/png');
insert into _enum_mime_type(description) values ('image/jpeg');
insert into _enum_mime_type(description) values ('image/gif');
insert into _enum_mime_type(description) values ('application/pdf');

insert into _enum_phases_dossier(description) values ('normal');
insert into _enum_phases_dossier(description) values ('archivé');

insert into _enum_input_type(description) values ('text');
insert into _enum_input_type(description) values ('textarea');
insert into _enum_input_type(description) values ('options_radio');
insert into _enum_input_type(description) values ('options_checkbox');
insert into _enum_input_type(description) values ('date');
insert into _enum_input_type(description) values ('tel');
insert into _enum_input_type(description) values ('email');
insert into _enum_input_type(description) values ('number');
insert into _enum_input_type(description) values ('html');

insert into template_formulaire_produit(nom_template) values ('Template par défaut');

insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Adresse du lieu d''exploitation',0);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Code postal du lieu d''exploitation',1);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Ville du lieu d''exploitation',2);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'tel','Numéro de téléphone personnel de l''exploitant',3);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order, input_html_attributes) values (1, 'number','Puissance souscrite (en kVa)',4,'min="0" step="1"');
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_choices, input_order) values (1, 'options_radio','Type de contrat','Formule bleue;Formule jaune;Formule verte',5);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_choices, input_order) values (1, 'options_checkbox','Type client','PME;Crée depuis moins de 2 ans;Plusieurs dirigeants',6);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'date','Date de signature du contrat précédent',7);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_html_attributes, input_order) values (1, 'html','Script de test','<script>console.log("script du template correctement chargé")</script>',7);

select 'Query done';