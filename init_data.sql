use confort_maison_occitanie;
-- init enum_user_role
insert into enum_user_role(description) values ('admin');
insert into enum_user_role(description) values ('commercial');
insert into enum_user_role(description) values ('client');
insert into enum_user_role(description) values ('fournisseur');

-- init enum_statut_societe
insert into enum_statut_societe(description) values ('autoentrepreneur');
insert into enum_statut_societe(description) values ('entreprise');
insert into enum_statut_societe(description) values ('vendeur à domicile');

-- init enum_etat_projet
insert into enum_etat_projet(description) values ('proposition commerciale');
insert into enum_etat_projet(description) values ('commande validée par le client');
insert into enum_etat_projet(description) values ('validation commande par le fournisseur');
insert into enum_etat_projet(description) values ('installation planifiée');
insert into enum_etat_projet(description) values ('instalée');

-- admin account (admin:admin)
insert into user () values ();
insert into user_emails (email_string, user_id) values ('admin_cmo@yopmail.com', 1);
insert into user_account (primary_email_id, password_hash, user_role) values (1, '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 1);