create or replace view admins as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'admin';

select 'Patch done';