create or replace view logs_enriched as
    select l.*, p.*
    from logs_dossiers l, personnes p where p.id_personne = l.id_utilisateur
    order by date_heure;

select 'Patch done';