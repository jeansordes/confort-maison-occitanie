DELIMITER $$
create or replace function new_dossier(
    p_id_client int(11),
    p_id_produit int(11)
) returns int(11) begin
    insert into dossiers(id_client, id_produit, etat_dossier) values (p_id_client, p_id_produit, 1);
    set @id_dossier = last_insert_id();
    select description into @initial_dossier_etat from _enum_etats_dossier where id_enum_etat = 1;
    select id_commercial into @id_commercial from clients_des_commerciaux where id_client = p_id_client;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (@id_dossier, @id_commercial, 'Initialisation état du dossier', concat('« ',@initial_dossier_etat,' »'));
    return @id_dossier;
end
$$