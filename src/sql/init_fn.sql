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

$$
create or replace function new_input_formulaire(
    p_id_template int(11),
    p_input_type varchar(50),
    p_input_description text,
    p_input_choices text,
    p_input_html_attributes text
) returns int(11) begin
    select input_order + 1 into @order_new_value from input_template_formulaire_produit where id_template = p_id_template order by input_order desc limit 1;
    insert into input_template_formulaire_produit(
        id_template,
        input_type,
        input_description,
        input_choices,
        input_html_attributes,
        input_order
    ) values (
        p_id_template,
        p_input_type,
        p_input_description,
        p_input_choices,
        p_input_html_attributes,
        @order_new_value
    );
    return last_insert_id();
end
$$

select 'Functions created';