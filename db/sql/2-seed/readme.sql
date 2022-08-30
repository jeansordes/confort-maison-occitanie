-- !!! DONT DELETE THIS FILE !!!
-- this file is required in order for the pg.dockerfile to work
-- because we need to have at least one file in the 'COPY' instruction
-- and so, in order for the developer to play with the project even before
-- adding a dump.sql here, we need to have at least a file
-- (the other option would be to find how to do conditional COPY in dockerfile
-- but on the internet I couldn't find any solution, but if you find one,
-- feel free to implement it here. It's just that I didn't think it
-- was worth the effort. Keeping this empty file here is sufficiant)

-- If you put a dump file, put this at the beginning of your dump file :

-- drop database if exists cwapgwkr_confort_maison_occitanie;
-- create database cwapgwkr_confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
-- use cwapgwkr_confort_maison_occitanie;
-- set names utf8mb4;