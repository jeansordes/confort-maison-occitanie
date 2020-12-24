use confort_maison_occitanie;

-- https://www.sporcle.com/games/knhall27/superheroes-real-names-dc--marvel/results
insert into user(prenom, nom_famille, user_role) values ('Peter', 'Parker', 'commercial');
insert into user(prenom, nom_famille, user_role) values ('Bruce', 'Banner', 'commercial');
insert into user(prenom, nom_famille, user_role) values ('Tony', 'Stark', 'client');
insert into user(prenom, nom_famille, user_role) values ('Bruce', 'Wayne', 'client');
insert into user(prenom, nom_famille, user_role) values ('Steve', 'Rogers', 'client');
insert into user(prenom, nom_famille, user_role) values ('Charles', 'Xavier', 'client');

insert into projet(id_commercial, id_client, nom_projet)
values (
    (select id from user where prenom = 'Peter' and nom_famille = 'Parker'),
    (select id from user where prenom = 'Tony' and nom_famille = 'Stark'),
    'Isolation des combles');

insert into projet(id_commercial, id_client, nom_projet)
values (
    (select id from user where prenom = 'Peter' and nom_famille = 'Parker'),
    (select id from user where prenom = 'Tony' and nom_famille = 'Stark'),
    'Crepis sur la facade');

insert into projet(id_commercial, id_client, nom_projet)
values (
    (select id from user where prenom = 'Bruce' and nom_famille = 'Banner'),
    (select id from user where prenom = 'Steve' and nom_famille = 'Rogers'),
    'Crepis sur la facade');
