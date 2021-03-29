use :cmo_db_name;

-- admin account (admin_cmo@yopmail.com:admin)
select new_user('admin', 'admin_cmo@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'Administrateur', null, null, null, null, null, null, null, null);

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial 1
set @com1 = new_user('commercial','peter_parker@yopmail.com','$2y$12$U3EnKlIrojdabF8s4z70Ne2rZB9yvzqYH/IzNNUMUVTqG3sGy7dRS','Peter','Parker', 'mr', null, null, null, null, null, null);
-- commercial 2
set @com2 = new_user('commercial', 'bruce_banner@yopmail.com', '', 'Bruce', 'Banner', 'mr', null, null, null, null, null, null);
-- client 1
set @cli1 = new_client(@com1, 'Tony', 'Stark', 'mr', null, null, null, null, null, null, null);
-- client 2
set @cli2 = new_client(@com1, 'Bruce', 'Wayne', 'mr', null, null, null, null, null, null, null);
-- client 3
set @cli3 = new_client(@com2, 'Steve', 'Rogers', 'mr', null, null, null, null, null, null, null);
-- fournisseur
set @fournisseur_representant = new_user('fournisseur', 'charles_xavier@yopmail.com', '', 'Charles', 'Xavier', 'mr', null, null, null, null, null, null);
insert into societes(nom_societe, id_representant) values ('X-men construction', @fournisseur_representant);

insert into produits(nom_produit, description_produit, id_fournisseur) values ('Isolation des combles','C''est un choix de rénovation énergétique à prioriser. En effet, jusqu''à 30 % des pertes de chaleur se font par la toiture. Cette isolation est donc celle qui permet de faire le plus d''économies d''énergie pour un faible coût',@fournisseur_representant);
set @prod1 = last_insert_id();

insert into produits(nom_produit, description_produit, id_fournisseur) values ('Crepis sur la facade','Il apporte une deuxième jeunesse aux maisons anciennes ou revêtit avec élégance une habitation neuve, protège le batiment des intempéries et du temps, apporte la touche finale à l''esthétique de la maison : le crépi est le revêtement de façade le plus utilisé en France, loin devant la peinture et le bardage.', @fournisseur_representant);
set @prod2 = last_insert_id();

select new_dossier(@cli1, @prod1);
select new_dossier(@cli1, @prod2);
select new_dossier(@cli3, @prod2);

select 'Query done';