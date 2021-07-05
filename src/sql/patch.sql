create or replace table template_formulaire_produit (
    id_template int(11) not null auto_increment primary key,
    nom_template text not null
);

create or replace table _enum_input_type (
    description varchar(50) not null primary key
);
insert into _enum_input_type(description) values ('text');
insert into _enum_input_type(description) values ('textarea');
insert into _enum_input_type(description) values ('options_radio');
insert into _enum_input_type(description) values ('options_checkbox');
insert into _enum_input_type(description) values ('date');
insert into _enum_input_type(description) values ('tel');
insert into _enum_input_type(description) values ('email');
insert into _enum_input_type(description) values ('number');
insert into _enum_input_type(description) values ('html');

create or replace table input_template_formulaire_produit (
    id_input int(11) not null auto_increment primary key,
    id_template int(11) not null references template_formulaire_produit(id_template),
    input_type varchar(50) not null,
    input_description text not null,
    input_choices text default null,
    input_html_attributes text default null,
    input_order int(11) not null
);

alter table produits add column
    id_template_formulaire int(11) default 1 references template_formulaire_produit(id_template);

alter table produits add column
    id_workflow int(11) default null references workflows(id_workflow);

alter table dossiers add column
    etat_workflow_dossier int(11) default null references etats_workflow(id_etat);

create or replace table reponses_formulaire_produit (
    id_reponse int(11) not null auto_increment primary key,
    id_dossier int(11) not null references dossiers(id_dossier),
    id_input int(11) not null references formulaire_produit(id_input),
    value_reponse text not null default ''
);

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

-- Changer la contrainte de personnes.email (il y avait un bug)
alter table personnes add column email_tmp varchar(200) default null;
update personnes set email_tmp = email;
alter table personnes drop column email;
alter table personnes add column email varchar(200) default null check (email REGEXP '^[A-Z0-9._%\\-+]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$');
update personnes set email = email_tmp;
alter table personnes drop column email_tmp;

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

select 'Patch done';