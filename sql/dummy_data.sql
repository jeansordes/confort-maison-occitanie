use cwapgwkr_confort_maison_occitanie;

-- admin account (admin_cmo@yopmail.com:admin)
select new_user('admin', 'admin_cmo@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', null, null);

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial 1
set @com1 = new_user('commercial','peter_parker@yopmail.com','$2y$12$U3EnKlIrojdabF8s4z70Ne2rZB9yvzqYH/IzNNUMUVTqG3sGy7dRS','Peter','Parker');
-- commercial 2
set @com2 = new_user('commercial', 'bruce_banner@yopmail.com', '', 'Bruce', 'Banner');
-- client 1
insert into personnes(prenom, nom_famille) values ('Tony', 'Stark');
set @cli1 = last_insert_id();
insert into clients_des_commerciaux(id_client, id_commercial) values (@cli1, @com1);
-- client 2
insert into personnes(prenom, nom_famille) values ('Bruce', 'Wayne');
set @cli2 = last_insert_id();
insert into clients_des_commerciaux(id_client, id_commercial) values (@cli2, @com1);
-- client 3
insert into personnes(prenom, nom_famille) values ('Steve', 'Rogers');
set @cli3 = last_insert_id();
insert into clients_des_commerciaux(id_client, id_commercial) values (@cli3, @com2);
-- fournisseur
set @fournisseur_representant = new_user('fournisseur', 'charles_xavier@yopmail.com', '', 'Charles', 'Xavier');
insert into societes(nom_societe, id_representant) values ('X-men construction', @fournisseur_representant);
set @fournisseur = last_insert_id();

insert into produits(nom_produit, id_fournisseur) values ('Isolation des combles',@fournisseur);
set @prod1 = last_insert_id();
insert into produits(nom_produit, id_fournisseur) values ('Crepis sur la facade', @fournisseur);
set @prod2 = last_insert_id();

insert into dossiers(id_client, id_produit) values (@cli1,@prod1);
insert into dossiers(id_client, id_produit) values (@cli1, @prod2);
insert into dossiers(id_client, id_produit) values (@cli3, @prod2);

select '';