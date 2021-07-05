use :cmo_db_name;

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial 1
set @com1 = new_user('commercial','peter_parker@yopmail.com','$2y$12$U3EnKlIrojdabF8s4z70Ne2rZB9yvzqYH/IzNNUMUVTqG3sGy7dRS', 'Spiderman Co.', null, 0, 'Peter','Parker', 'mr', null, null, null, null, null, null);
-- commercial 2
set @com2 = new_user('commercial', 'bruce_banner@yopmail.com', '', 'Hulk Co.', null, 0, 'Bruce', 'Banner', 'mr', null, null, null, null, null, null);
-- client 1
set @cli1 = new_client(@com1, 'Stark Industries', null, 0, 'Tony', 'Stark', 'mr', null, null, null, null, null, null, null);
-- client 2
set @cli2 = new_client(@com1, 'Batman Co.', null, 0, 'Bruce', 'Wayne', 'mr', null, null, null, null, null, null, null);
-- client 3
set @cli3 = new_client(@com2, 'Captain America', null, 0, 'Steve', 'Rogers', 'mr', null, null, null, null, null, null, null);
-- fournisseur
set @fournisseur_representant = new_user('fournisseur', 'charles_xavier@yopmail.com', '', 'X-men', null, 0, 'Charles', 'Xavier', 'mr', null, null, null, null, null, null);
insert into societes(nom_societe, id_representant) values ('X-men construction', @fournisseur_representant);

insert into produits(nom_produit, description_produit, id_fournisseur) values ('Isolation des combles','C''est un choix de rénovation énergétique à prioriser. En effet, jusqu''à 30 % des pertes de chaleur se font par la toiture. Cette isolation est donc celle qui permet de faire le plus d''économies d''énergie pour un faible coût',@fournisseur_representant);
set @prod1 = last_insert_id();
insert into etats_produit(description, order_etat, id_produit) values ('P1 projet créé', 0, @prod1);
insert into etats_produit(description, order_etat, id_produit) values ('P1 dossier à compléter', 1, @prod1);
insert into etats_produit(description, order_etat, id_produit, phase_etape) values ('P1 cloturé', 2, @prod1, 'archivé');

insert into produits(nom_produit, description_produit, id_fournisseur) values ('Crepis sur la facade','Il apporte une deuxième jeunesse aux maisons anciennes ou revêtit avec élégance une habitation neuve, protège le batiment des intempéries et du temps, apporte la touche finale à l''esthétique de la maison : le crépi est le revêtement de façade le plus utilisé en France, loin devant la peinture et le bardage.', @fournisseur_representant);
set @prod2 = last_insert_id();
insert into etats_produit(description, order_etat, id_produit) values ('P2 projet créé', 0, @prod2);
insert into etats_produit(description, order_etat, id_produit) values ('P2 dossier à compléter', 1, @prod2);
insert into etats_produit(description, order_etat, id_produit, phase_etape) values ('P2 cloturé', 2, @prod2, 'archivé');

select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);

insert into workflows (nom_workflow, id_fournisseur) values ('Workflow par défaut', @fournisseur_representant);
set @id_last_workflow = last_insert_id();

insert into etats_workflow(description, order_etat, id_workflow) values ('Projet créé', 0, @id_last_workflow);
insert into etats_workflow(description, order_etat, id_workflow) values ('Dossier à compléter', 1, @id_last_workflow);
insert into etats_workflow(description, order_etat, id_workflow, phase_etape) values ('Cloturé', 2, @id_last_workflow, 'archivé');

-- update workflow id pour TOUS les produits (comme il n'y a pas de WHERE)
update produits set id_workflow = @id_last_workflow;

select 'Query done';