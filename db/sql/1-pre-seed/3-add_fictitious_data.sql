use cwapgwkr_confort_maison_occitanie;
set names utf8mb4;

-- admin account (admin_cmo@yopmail.com:admin)
select new_user('admin', 'admin_cmo@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'Administrateur', null, 0, null, null, null, null, null, null, null, null, null);

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial 1
set @com1 = new_user('commercial','peter_parker@yopmail.com','$2y$12$U3EnKlIrojdabF8s4z70Ne2rZB9yvzqYH/IzNNUMUVTqG3sGy7dRS', 'Spiderman Co.', null, 0, 'Peter','Parker', 'mr', null, null, null, null, null, null);
-- commercial 2
set @com2 = new_user('commercial', 'bruce_banner@yopmail.com', '', 'Hulk Co.', null, 0, 'Bruce', 'Banner', 'mr', null, null, null, null, null, null);
-- client 1
set @cli1 = new_client(@com1, 'X-Men Academy', null, 0, 'Charles', 'Xavier', 'mr', null, null, null, null, null, null, null);
-- client 2
set @cli2 = new_client(@com1, 'Batman Co.', null, 0, 'Bruce', 'Wayne', 'mr', null, null, null, null, null, null, null);
-- client 3
set @cli3 = new_client(@com2, 'Captain America', null, 0, 'Steve', 'Rogers', 'mr', null, null, null, null, null, null, null);
-- fournisseur
set @fournisseur_representant = new_user('fournisseur', 'tony_stark@yopmail.com', '', 'Stark Industries', null, 0, 'Tony', 'Stark', 'mr', null, null, null, null, null, null);
insert into societes(nom_societe, id_representant) values ('Stark Industries', @fournisseur_representant);

-- Création d'un workflow par défaut
insert into workflows (nom_workflow, id_fournisseur) values ('Workflow par défaut', @fournisseur_representant);
set @id_default_workflow = last_insert_id();

insert into etats_workflow(description, order_etat, id_workflow) values ('Projet créé', 0, @id_default_workflow);
insert into etats_workflow(description, order_etat, id_workflow) values ('Dossier à compléter', 1, @id_default_workflow);
insert into etats_workflow(description, order_etat, id_workflow, phase_etape) values ('Cloturé', 2, @id_default_workflow, 'archivé');

-- Création des produits
insert into produits(nom_produit, description_produit, id_fournisseur, id_workflow) values ('Isolation des combles','C''est un choix de rénovation énergétique à prioriser. En effet, jusqu''à 30 % des pertes de chaleur se font par la toiture. Cette isolation est donc celle qui permet de faire le plus d''économies d''énergie pour un faible coût',@fournisseur_representant, @id_default_workflow);
set @prod1 = last_insert_id();

insert into produits(nom_produit, description_produit, id_fournisseur, id_workflow) values ('Crepis sur la facade','Il apporte une deuxième jeunesse aux maisons anciennes ou revêtit avec élégance une habitation neuve, protège le batiment des intempéries et du temps, apporte la touche finale à l''esthétique de la maison : le crépi est le revêtement de façade le plus utilisé en France, loin devant la peinture et le bardage.', @fournisseur_representant, @id_default_workflow);
set @prod2 = last_insert_id();

select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);
select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);
select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);
select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);

insert into template_formulaire_produit(nom_template, id_fournisseur) values ('Template par défaut', @fournisseur_representant);
update produits set id_template_formulaire = last_insert_id() where id_produit in (@prod1, @prod2);

insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Adresse du lieu d''exploitation',0);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Code postal du lieu d''exploitation',1);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'text','Ville du lieu d''exploitation',2);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'tel','Numéro de téléphone personnel de l''exploitant',3);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order, input_html_attributes) values (1, 'number','Puissance souscrite (en kVa)',4,'min="0" step="1"');
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_choices, input_order) values (1, 'options_radio','Type de contrat','Formule bleue★Formule jaune★Formule verte',5);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_choices, input_order) values (1, 'options_checkbox','Type client','PME★Crée depuis moins de 2 ans★Plusieurs dirigeants',6);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_order) values (1, 'date','Date de signature du contrat précédent',7);
insert into input_template_formulaire_produit(id_template, input_type, input_description, input_html_attributes, input_order) values (1, 'html','Script de test','<script>console.log("script du template correctement chargé")</script>',8);


select 'Données fictives ajoutés';