create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur, d.role_responsable_etape, d.phase_etape
    from clients_des_commerciaux a, dossiers b, produits c, etats_workflow d
    where a.id_client = b.id_client and b.id_produit = c.id_produit and d.id_etat = b.etat_workflow_dossier
    order by d.phase_etape desc;
    
select 'Patch done';