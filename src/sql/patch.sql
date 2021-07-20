delimiter $$
create or replace function new_user (
    p_role varchar(50),
    p_email text,
    p_password_hash text,
    p_nom_entreprise text,
    p_numero_entreprise text,
    p_est_un_particulier boolean,
    p_prenom text,
    p_nom_famille text,
    p_civilite text,
    p_adresse text,
    p_code_postal text,
    p_ville text,
    p_pays text,
    p_tel1 text,
    p_tel2 text
) returns int(11) begin
    insert into coordonnees(adresse, code_postal, ville, pays, tel1, tel2)
        values (p_adresse, p_code_postal, p_ville, p_pays, p_tel1, p_tel2);
    set @id_coordonnees = last_insert_id();
    insert into personnes(prenom, nom_famille, civilite, id_coordonnees, email, nom_entreprise, numero_entreprise, est_un_particulier)
        values (p_prenom, p_nom_famille, p_civilite, @id_coordonnees, p_email, p_nom_entreprise, p_numero_entreprise, p_est_un_particulier);
    set @v_uid = last_insert_id();
    insert into utilisateurs(id_utilisateur, user_role, password_hash) values (@v_uid, p_role, p_password_hash);
    return @v_uid;
end
$$

$$
create or replace function new_client(
    p_id_commercial int(11),
    p_nom_entreprise text,
    p_numero_entreprise text,
    p_est_un_particulier boolean,
    p_prenom text,
    p_nom_famille text,
    p_civilite text,
    p_adresse text,
    p_code_postal text,
    p_ville text,
    p_pays text,
    p_tel1 text,
    p_tel2 text,
    p_email varchar(200)
) returns int(11) begin
    insert into coordonnees(adresse, code_postal, ville, pays, tel1, tel2)
        values (p_adresse, p_code_postal, p_ville, p_pays, p_tel1, p_tel2);
    set @id_coordonnees = last_insert_id();
    insert into personnes(prenom, nom_famille, civilite, id_coordonnees, email, nom_entreprise, numero_entreprise, est_un_particulier)
        values (p_prenom, p_nom_famille, p_civilite, @id_coordonnees, nullif(p_email, ''), p_nom_entreprise, p_numero_entreprise, p_est_un_particulier);
    set @id_client = last_insert_id();
    insert into clients_des_commerciaux(id_client, id_commercial) values (@id_client, p_id_commercial);
    return @id_client;
end
$$

$$
create or replace function new_fichier_dossier(
    p_filename text,
    p_file_mime_type text,
    p_project_id int(11)
) returns int(11) begin
    insert into fichiers(file_name, mime_type) values (p_filename, p_file_mime_type);
    set @id_fichier = last_insert_id();
    insert into fichiers_dossier(id_dossier, id_fichier)
        values (p_project_id, @id_fichier);
    return @id_fichier;
end
$$

$$
create or replace function new_fichier_produit(
    p_filename text,
    p_file_mime_type text,
    p_produit_id int(11)
) returns int(11) begin
    insert into fichiers(file_name, mime_type) values (p_filename, p_file_mime_type);
    set @id_fichier = last_insert_id();
    insert into fichiers_produit(id_produit, id_fichier)
        values (p_produit_id, @id_fichier);
    return @id_fichier;
end
$$

$$
create or replace function new_dossier(
    p_id_client int(11),
    p_id_produit int(11)
) returns int(11) begin
    select a.id_etat, a.description into @id_etat_initial, @initial_dossier_etat
    from etats_workflow a, produits b
    where a.id_workflow = b.id_workflow
      and b.id_produit = p_id_produit
    order by a.order_etat limit 1;
    insert into dossiers(id_client, id_produit, etat_workflow_dossier) values (p_id_client, p_id_produit, @id_etat_initial);
    set @id_dossier = last_insert_id();
    select id_commercial into @id_commercial from clients_des_commerciaux where id_client = p_id_client;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action) values (@id_dossier, @id_commercial, 'Initialisation état du dossier', concat('État du dossier : ', @initial_dossier_etat));
    return @id_dossier;
end
$$

$$
create or replace function update_etat_dossier(
    p_id_dossier int(11),
    p_id_nouvel_etat int(11),
    p_id_author int(11)
) returns int(11) begin
    update dossiers set etat_workflow_dossier = p_id_nouvel_etat where id_dossier = p_id_dossier;
    select description into @nouvel_etat from etats_workflow where id_etat = p_id_nouvel_etat;
    insert into logs_dossiers(id_dossier, id_utilisateur, nom_action, desc_action)
        values (p_id_dossier, p_id_author, 'Changement de l''état du dossier', concat('État du dossier : ', @nouvel_etat));
    return p_id_dossier;
end
$$

$$
create or replace function new_etat_workflow(
    p_id_workflow int(11),
    p_description varchar(50)
) returns int(11) begin
    select count(*) into @new_order_etat from etats_workflow where id_workflow = p_id_workflow;
    insert into etats_workflow(description, id_workflow, order_etat) values (p_description, p_id_workflow, @new_order_etat);
    return p_id_workflow;
end
$$

create or replace view clients as
    select
        a.*,
        u.*,
        coalesce((select count(p.id_dossier) nb_dossiers
            from dossiers p where u.id_personne = p.id_client group by p.id_client),0) nb_dossiers
        from personnes u, clients_des_commerciaux a, (
            select id_personne from personnes, utilisateurs
            except select id_utilisateur from utilisateurs
        ) t where u.id_personne = t.id_personne and a.id_client = u.id_personne;

create or replace view commerciaux as
    select
        (select count(*) from clients_des_commerciaux where id_commercial = u.id_personne) nb_clients,
        (select count(*) from dossiers d, clients_des_commerciaux cc
            where d.id_client = cc.id_client and cc.id_commercial = u.id_personne) nb_dossiers,
        u.*
    from personnes u, utilisateurs a
    where u.id_personne = a.id_utilisateur
        and a.user_role = 'commercial';

create or replace view fournisseurs as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'fournisseur';

create or replace view admins as
    select u.* from personnes u, utilisateurs a where u.id_personne = a.id_utilisateur and user_role = 'admin';

create or replace view dossiers_enriched as
    select a.id_commercial, c.nom_produit, b.*,
           (select date_heure from logs_dossiers l where l.id_dossier = b.id_dossier order by date_heure desc limit 1) date_creation,
           c.id_fournisseur, d.role_responsable_etape, d.phase_etape
    from clients_des_commerciaux a, dossiers b, produits c, etats_workflow d
    where a.id_client = b.id_client and b.id_produit = c.id_produit and d.id_etat = b.etat_workflow_dossier
    order by d.phase_etape desc;

create or replace view fichiers_enriched as
    select a.*, b.id_dossier
    from fichiers a, fichiers_dossier b, fichiers_produit c
    where a.id_fichier = b.id_fichier or a.id_fichier = c.id_fichier;

create or replace view logs_enriched as
    select l.*, p.*,
    nullif((select u.user_role from utilisateurs u where p.id_personne = u.id_utilisateur),'client') personne_role
    from logs_dossiers l, personnes p where p.id_personne = l.id_utilisateur
    order by date_heure;
    
select 'Patch done';