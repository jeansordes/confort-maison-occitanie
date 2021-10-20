insert into _enum_statut_societe(description) values ('autoentrepreneur');
insert into _enum_statut_societe(description) values ('entreprise');
insert into _enum_statut_societe(description) values ('vendeur à domicile');

insert into _enum_user_role(description) values ('admin');
insert into _enum_user_role(description) values ('commercial');
insert into _enum_user_role(description) values ('fournisseur');

insert into _enum_mime_type(description) values ('image/png');
insert into _enum_mime_type(description) values ('image/jpeg');
insert into _enum_mime_type(description) values ('image/gif');
insert into _enum_mime_type(description) values ('application/pdf');

insert into _enum_phases_dossier(description) values ('normal');
insert into _enum_phases_dossier(description) values ('archivé');

insert into _enum_input_type(description) values ('text');
insert into _enum_input_type(description) values ('textarea');
insert into _enum_input_type(description) values ('options_radio');
insert into _enum_input_type(description) values ('options_checkbox');
insert into _enum_input_type(description) values ('date');
insert into _enum_input_type(description) values ('tel');
insert into _enum_input_type(description) values ('email');
insert into _enum_input_type(description) values ('number');
insert into _enum_input_type(description) values ('html');

select 'Enum types added';