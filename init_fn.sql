use confort_maison_occitanie;

create or replace function create_commercial(
    p_prenom text character set utf8mb4,
    p_nom_famille text character set utf8mb4,
    p_email text character set utf8mb4
) returns int(11)
begin
    declare v_user_role int(11);
    declare v_new_uid int(11);
    select id into v_user_role from enum_user_role where description = 'commercial';

    insert into user(prenom, nom_famille, email) values (p_prenom, p_nom_famille, p_email);
    set v_new_uid = last_insert_id();
    insert into user_account(user_id, password_hash, user_role) values (v_new_uid, '', v_user_role);
    return v_new_uid;
end;
