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
    id_fournisseur int(11) not null references utilisateurs(id_utilisateur),
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
    in_trash boolean default 0,
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

select 'Tables created';

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

select 'Views created';

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

select 'Enum types added';