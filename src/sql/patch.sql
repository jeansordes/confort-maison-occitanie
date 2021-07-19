create or replace view logs_enriched as
    select l.*, p.*, nullif((select u.user_role from utilisateurs u where p.id_personne = u.id_utilisateur),'client') personne_role
    from logs_dossiers l, personnes p where p.id_personne = l.id_utilisateur
    order by date_heure;
    
select 'Patch done';