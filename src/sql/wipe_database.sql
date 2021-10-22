drop database if exists :cmo_db_name;
create database :cmo_db_name default character set utf8mb4 collate utf8mb4_general_ci;
use :cmo_db_name;
select 'Wipe Done';