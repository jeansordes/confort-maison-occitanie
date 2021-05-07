alter table etats_produit add column
    role_responsable_etape varchar(50) default 'commercial' references _enum_user_role(description);

create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur, d.role_responsable_etape
    from clients_des_commerciaux a, dossiers b, produits c, etats_produit d
    where a.id_client = b.id_client and b.id_produit = c.id_produit and d.id_etat = b.etat_dossier;

create table _enum_phases_dossier (
    description varchar(50) primary key
);
insert into _enum_phases_dossier(description) values ('normal');
insert into _enum_phases_dossier(description) values ('archivé');

alter table etats_produit add column
    phase_etape varchar(50) default 'normal' not null references _enum_phases_dossier(description);

alter table clients_des_commerciaux change commentaire_commercial
    infos_client_supplementaires text default null;

create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur, d.role_responsable_etape, d.phase_etape
    from clients_des_commerciaux a, dossiers b, produits c, etats_produit d
    where a.id_client = b.id_client and b.id_produit = c.id_produit and d.id_etat = b.etat_dossier
    order by d.phase_etape desc;

select 'Patch done';