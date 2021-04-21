alter table fichiers
    add column in_trash int(1) default 0;

select de.id_commercial, de.id_fournisseur, fd.id_dossier, ff.* from fichiers_dossier fd, fichiers ff, dossiers_enriched de where fd.id_fichier = :id_fichier and de.id_dossier = fd.id_dossier and ff.id_fichier = fd.id_fichier

select 'Patch done';