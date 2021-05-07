delimiter $$
create or replace function new_dossier(
    p_id_client int(11),
    p_id_produit int(11)
) returns int(11) begin
    select id_etat, description into @id_etat_initial, @initial_dossier_etat from etats_produit where id_produit = p_id_produit order by order_etat limit 1;
    insert into dossiers(id_client, id_produit, etat_dossier) values (p_id_client, p_id_produit, @id_etat_initial);
    set @id_dossier = last_insert_id();
    select id_commercial into @id_commercial from clients_des_commerciaux where id_client = p_id_client;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (@id_dossier, @id_commercial, 'Initialisation état du dossier', concat('État du dossier : ', @initial_dossier_etat));
    return @id_dossier;
end
$$

alter table etats_produit change phase_etape
    phase_etape varchar(50) default 'normal' not null;

select 'Patch done';