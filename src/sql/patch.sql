create or replace table etats_produit (
    id_etat int(11) not null auto_increment primary key,
    description varchar(50) not null,
    order_etat int(11) not null,
    id_produit int(11) not null,
    constraint
        foreign key (id_produit) references produits(id_produit)
);

alter table dossiers drop constraint dossiers_ibfk_1;
alter table dossiers modify etat_dossier int(11) not null;
alter table dossiers add constraint foreign key (etat_dossier) references etats_produit(id_etat);

drop table _enum_etats_dossier;

delimiter $$
create or replace function new_dossier(
    p_id_client int(11),
    p_id_produit int(11)
) returns int(11) begin
    insert into dossiers(id_client, id_produit, etat_dossier) values (p_id_client, p_id_produit, 1);
    set @id_dossier = last_insert_id();
    select description into @initial_dossier_etat from etats_produit where id_produit = p_id_produit order by order_etat limit 1;
    select id_commercial into @id_commercial from clients_des_commerciaux where id_client = p_id_client;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (@id_dossier, @id_commercial, 'Initialisation état du dossier', concat('État initial du dossier : ',@initial_dossier_etat));
    return @id_dossier;
end
$$

insert into etats_produit(description, order_etat, id_produit) values ('projet créé', 0, 1);
insert into etats_produit(description, order_etat, id_produit) values ('dossier à compléter', 1, 1);
insert into etats_produit(description, order_etat, id_produit) values ('cloturé', 2, 1);
insert into etats_produit(description, order_etat, id_produit) values ('projet créé', 0, 2);
insert into etats_produit(description, order_etat, id_produit) values ('dossier à compléter', 1, 2);
insert into etats_produit(description, order_etat, id_produit) values ('cloturé', 2, 2);

select 'Patch done';