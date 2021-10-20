ALTER TABLE template_formulaire_produit
    ADD id_fournisseur int(11) not null references utilisateurs(id_utilisateur);

delimiter $$
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

select 'Patch done';