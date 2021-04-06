create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur
    from clients_des_commerciaux a, dossiers b, produits c
    where a.id_client = b.id_client and b.id_produit = c.id_produit;

select 'Patch done';