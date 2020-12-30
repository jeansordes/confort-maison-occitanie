use confort_maison_occitanie;

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
-- commercial
select nouvel_utilisateur('commercial', 'Peter', 'Parker', 'peter_bruce@yopmail.com') into @com1;
select nouvel_utilisateur('commercial', 'Bruce', 'Banner', 'bruce_banner@yopmail.com') into @com2;
-- client
insert into user(prenom, nom_famille) values ('Tony', 'Stark');
set @cli1 = last_insert_id();
insert into user(prenom, nom_famille) values ('Bruce', 'Wayne');
set @cli2 = last_insert_id();
insert into user(prenom, nom_famille) values ('Steve', 'Rogers');
set @cli3 = last_insert_id();
-- fournisseur
select nouvel_utilisateur('fournisseur', 'Charles', 'Xavier', 'charles_xavier@yopmail.com') into @fournisseur_representant;
insert into societe(nom_societe, id_contact) values ('X-men construction', @fournisseur_representant);
set @fournisseur = last_insert_id();

insert into produit(nom_produit, id_fournisseur) values ('Isolation des combles',@fournisseur);
set @prod1 = last_insert_id();
insert into produit(nom_produit, id_fournisseur) values ('Crepis sur la facade', @fournisseur);
set @prod2 = last_insert_id();

insert into projet(id_commercial, id_client, id_produit) values (@com1,@cli1,@prod1);
insert into projet(id_commercial, id_client, id_produit) values (@com1,@cli1, @prod2);
insert into projet(id_commercial, id_client, id_produit) values (@com2, @cli3, @prod2);
