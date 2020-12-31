use confort_maison_occitanie;

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial 1
select new_user('commercial', 'Peter', 'Parker', 'peter_parker@yopmail.com') into @com1;
update user_account set password_hash = '$2y$12$U3EnKlIrojdabF8s4z70Ne2rZB9yvzqYH/IzNNUMUVTqG3sGy7dRS' where id_user = @com1;
-- commercial 2
select new_user('commercial', 'Bruce', 'Banner', 'bruce_banner@yopmail.com') into @com2;
-- client 1
insert into user(prenom, nom_famille) values ('Tony', 'Stark');
set @cli1 = last_insert_id();
insert into appartenance_client(id_client, id_commercial) values (@cli1, @com1);
-- client 2
insert into user(prenom, nom_famille) values ('Bruce', 'Wayne');
set @cli2 = last_insert_id();
insert into appartenance_client(id_client, id_commercial) values (@cli2, @com1);
-- client 3
insert into user(prenom, nom_famille) values ('Steve', 'Rogers');
set @cli3 = last_insert_id();
insert into appartenance_client(id_client, id_commercial) values (@cli3, @com2);
-- fournisseur
select new_user('fournisseur', 'Charles', 'Xavier', 'charles_xavier@yopmail.com') into @fournisseur_representant;
insert into societe(nom_societe, id_contact) values ('X-men construction', @fournisseur_representant);
set @fournisseur = last_insert_id();

insert into produit(nom_produit, id_fournisseur) values ('Isolation des combles',@fournisseur);
set @prod1 = last_insert_id();
insert into produit(nom_produit, id_fournisseur) values ('Crepis sur la facade', @fournisseur);
set @prod2 = last_insert_id();

insert into projet(id_client, id_produit) values (@cli1,@prod1);
insert into projet(id_client, id_produit) values (@cli1, @prod2);
insert into projet(id_client, id_produit) values (@cli3, @prod2);
