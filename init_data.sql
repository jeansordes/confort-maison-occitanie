use confort_maison_occitanie;
-- init enum_user_role
insert into confort_maison_occitanie.enum_user_role(id, description) values (1, 'admin');
insert into confort_maison_occitanie.enum_user_role(id, description) values (2, 'commercial');
insert into confort_maison_occitanie.enum_user_role(id, description) values (3, 'client');
insert into confort_maison_occitanie.enum_user_role(id, description) values (4, 'fournisseur');

-- admin account (admin:admin)
insert into confort_maison_occitanie.user (id, prenom, email) values (1, 'admin','admin_cmo@yopmail.com');
insert into confort_maison_occitanie.user_account (user_id, password_hash, user_role) values (1, '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 1);