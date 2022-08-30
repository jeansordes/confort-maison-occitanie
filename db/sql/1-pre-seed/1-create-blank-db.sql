drop database if exists cwapgwkr_confort_maison_occitanie;
create database cwapgwkr_confort_maison_occitanie default character set utf8mb4 collate utf8mb4_general_ci;
use cwapgwkr_confort_maison_occitanie;
set names utf8mb4;

SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP VIEW IF EXISTS `admins`;
CREATE TABLE `admins` (`id_personne` int(11), `prenom` text, `nom_famille` text, `civilite` enum('mr','mme',''), `nom_entreprise` text, `numero_entreprise` text, `est_un_particulier` tinyint(1), `id_coordonnees` int(11), `email` varchar(200));

DROP VIEW IF EXISTS `clients`;
CREATE TABLE `clients` (`id_client` int(11), `id_commercial` int(11), `infos_client_supplementaires` text, `id_personne` int(11), `prenom` text, `nom_famille` text, `civilite` enum('mr','mme',''), `nom_entreprise` text, `numero_entreprise` text, `est_un_particulier` tinyint(1), `id_coordonnees` int(11), `email` varchar(200), `nb_dossiers` bigint(21));

DROP TABLE IF EXISTS `clients_des_commerciaux`;
CREATE TABLE `clients_des_commerciaux` (
  `id_client` int(11) NOT NULL,
  `id_commercial` int(11) NOT NULL,
  `infos_client_supplementaires` text DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  KEY `id_commercial` (`id_commercial`),
  CONSTRAINT `clients_des_commerciaux_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `personnes` (`id_personne`),
  CONSTRAINT `clients_des_commerciaux_ibfk_2` FOREIGN KEY (`id_commercial`) REFERENCES `personnes` (`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP VIEW IF EXISTS `commerciaux`;
CREATE TABLE `commerciaux` (`nb_clients` bigint(21), `nb_dossiers` bigint(21), `id_personne` int(11), `prenom` text, `nom_famille` text, `civilite` enum('mr','mme',''), `nom_entreprise` text, `numero_entreprise` text, `est_un_particulier` tinyint(1), `id_coordonnees` int(11), `email` varchar(200));

DROP TABLE IF EXISTS `coordonnees`;
CREATE TABLE `coordonnees` (
  `id_coordonnees` int(11) NOT NULL AUTO_INCREMENT,
  `adresse` text DEFAULT NULL,
  `code_postal` text DEFAULT NULL,
  `ville` text DEFAULT NULL,
  `pays` text DEFAULT NULL,
  `tel1` text DEFAULT NULL,
  `tel2` text DEFAULT NULL,
  PRIMARY KEY (`id_coordonnees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `dossiers`;
CREATE TABLE `dossiers` (
  `id_dossier` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `etat_workflow_dossier` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_dossier`),
  KEY `id_client` (`id_client`),
  KEY `id_produit` (`id_produit`),
  CONSTRAINT `dossiers_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients_des_commerciaux` (`id_client`),
  CONSTRAINT `dossiers_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP VIEW IF EXISTS `dossiers_enriched`;
CREATE TABLE `dossiers_enriched` (`id_commercial` int(11), `nom_produit` text, `id_dossier` int(11), `id_client` int(11), `id_produit` int(11), `commentaire` text, `etat_workflow_dossier` int(11), `date_creation` timestamp /* mariadb-5.3 */, `id_fournisseur` int(11), `role_responsable_etape` varchar(50), `phase_etape` varchar(50));


DROP TABLE IF EXISTS `employes`;
CREATE TABLE `employes` (
  `id_employe` int(11) NOT NULL,
  `id_societe` int(11) NOT NULL,
  PRIMARY KEY (`id_employe`),
  KEY `id_societe` (`id_societe`),
  CONSTRAINT `employes_ibfk_1` FOREIGN KEY (`id_employe`) REFERENCES `personnes` (`id_personne`),
  CONSTRAINT `employes_ibfk_2` FOREIGN KEY (`id_societe`) REFERENCES `societes` (`id_societe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `etats_workflow`;
CREATE TABLE `etats_workflow` (
  `id_etat` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(50) NOT NULL,
  `order_etat` int(11) NOT NULL,
  `role_responsable_etape` varchar(50) DEFAULT 'commercial',
  `phase_etape` varchar(50) NOT NULL DEFAULT 'normal',
  `id_workflow` int(11) NOT NULL,
  PRIMARY KEY (`id_etat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `fichiers`;
CREATE TABLE `fichiers` (
  `id_fichier` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `mime_type` varchar(50) NOT NULL,
  `in_trash` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_fichier`),
  KEY `mime_type` (`mime_type`),
  CONSTRAINT `fichiers_ibfk_1` FOREIGN KEY (`mime_type`) REFERENCES `_enum_mime_type` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `fichiers_dossier`;
CREATE TABLE `fichiers_dossier` (
  `id_dossier` int(11) NOT NULL,
  `id_fichier` int(11) NOT NULL,
  PRIMARY KEY (`id_dossier`,`id_fichier`),
  KEY `id_fichier` (`id_fichier`),
  CONSTRAINT `fichiers_dossier_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossiers` (`id_dossier`),
  CONSTRAINT `fichiers_dossier_ibfk_2` FOREIGN KEY (`id_fichier`) REFERENCES `fichiers` (`id_fichier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP VIEW IF EXISTS `fichiers_enriched`;
CREATE TABLE `fichiers_enriched` (`id_fichier` int(11), `file_name` text, `updated_at` timestamp, `mime_type` varchar(50), `in_trash` tinyint(1), `id_dossier` int(11));


DROP TABLE IF EXISTS `fichiers_produit`;
CREATE TABLE `fichiers_produit` (
  `id_produit` int(11) NOT NULL,
  `id_fichier` int(11) NOT NULL,
  PRIMARY KEY (`id_produit`,`id_fichier`),
  KEY `id_fichier` (`id_fichier`),
  CONSTRAINT `fichiers_produit_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  CONSTRAINT `fichiers_produit_ibfk_2` FOREIGN KEY (`id_fichier`) REFERENCES `fichiers` (`id_fichier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP VIEW IF EXISTS `fournisseurs`;
CREATE TABLE `fournisseurs` (`id_personne` int(11), `prenom` text, `nom_famille` text, `civilite` enum('mr','mme',''), `nom_entreprise` text, `numero_entreprise` text, `est_un_particulier` tinyint(1), `id_coordonnees` int(11), `email` varchar(200));


DROP TABLE IF EXISTS `input_template_formulaire_produit`;
CREATE TABLE `input_template_formulaire_produit` (
  `id_input` int(11) NOT NULL AUTO_INCREMENT,
  `id_template` int(11) NOT NULL,
  `input_type` varchar(50) NOT NULL,
  `input_description` text NOT NULL,
  `input_choices` text DEFAULT NULL,
  `input_html_attributes` text DEFAULT NULL,
  `input_order` int(11) NOT NULL,
  PRIMARY KEY (`id_input`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `logs_dossiers`;
CREATE TABLE `logs_dossiers` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_dossier` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `nom_action` varchar(50) NOT NULL,
  `desc_action` text DEFAULT NULL,
  `date_heure` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `logs_dossiers_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossiers` (`id_dossier`),
  CONSTRAINT `logs_dossiers_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP VIEW IF EXISTS `logs_enriched`;
CREATE TABLE `logs_enriched` (`id_log` int(11), `id_dossier` int(11), `id_utilisateur` int(11), `nom_action` varchar(50), `desc_action` text, `date_heure` timestamp, `id_personne` int(11), `prenom` text, `nom_famille` text, `civilite` enum('mr','mme',''), `nom_entreprise` text, `numero_entreprise` text, `est_un_particulier` tinyint(1), `id_coordonnees` int(11), `email` varchar(200), `personne_role` varchar(50));


DROP TABLE IF EXISTS `personnes`;
CREATE TABLE `personnes` (
  `id_personne` int(11) NOT NULL AUTO_INCREMENT,
  `prenom` text DEFAULT NULL,
  `nom_famille` text DEFAULT NULL,
  `civilite` enum('mr','mme','') DEFAULT NULL COMMENT 'alter table user change civilite civilite enum(''mr'', ''mme'', ''nouvelle_civilite'') not null;',
  `nom_entreprise` text DEFAULT NULL,
  `numero_entreprise` text DEFAULT NULL,
  `est_un_particulier` tinyint(1) DEFAULT 1,
  `id_coordonnees` int(11) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL CHECK (`email` regexp '^[A-Z0-9._%\\-+]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$'),
  PRIMARY KEY (`id_personne`),
  KEY `id_coordonnees` (`id_coordonnees`),
  CONSTRAINT `personnes_ibfk_1` FOREIGN KEY (`id_coordonnees`) REFERENCES `coordonnees` (`id_coordonnees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `produits`;
CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL AUTO_INCREMENT,
  `nom_produit` text NOT NULL,
  `id_fournisseur` int(11) NOT NULL,
  `description_produit` text DEFAULT NULL,
  `id_workflow` int(11) DEFAULT NULL,
  `id_template_formulaire` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `reponses_formulaire_produit`;
CREATE TABLE `reponses_formulaire_produit` (
  `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
  `id_dossier` int(11) NOT NULL,
  `id_input` int(11) NOT NULL,
  `value_reponse` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id_reponse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `societes`;
CREATE TABLE `societes` (
  `id_societe` int(11) NOT NULL AUTO_INCREMENT,
  `nom_societe` text NOT NULL,
  `numero_societe` text DEFAULT NULL,
  `id_representant` int(11) NOT NULL,
  `id_coordonnees_entreprise` int(11) DEFAULT NULL,
  `statut_societe` varchar(50) DEFAULT NULL,
  `commentaire_admin` text DEFAULT NULL,
  PRIMARY KEY (`id_societe`),
  KEY `id_representant` (`id_representant`),
  KEY `id_coordonnees_entreprise` (`id_coordonnees_entreprise`),
  KEY `statut_societe` (`statut_societe`),
  CONSTRAINT `societes_ibfk_1` FOREIGN KEY (`id_representant`) REFERENCES `personnes` (`id_personne`),
  CONSTRAINT `societes_ibfk_2` FOREIGN KEY (`id_coordonnees_entreprise`) REFERENCES `coordonnees` (`id_coordonnees`),
  CONSTRAINT `societes_ibfk_3` FOREIGN KEY (`statut_societe`) REFERENCES `_enum_statut_societe` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `template_formulaire_produit`;
CREATE TABLE `template_formulaire_produit` (
  `id_template` int(11) NOT NULL AUTO_INCREMENT,
  `id_fournisseur` int(11) NOT NULL,
  `nom_template` text NOT NULL,
  PRIMARY KEY (`id_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `last_user_update` time NOT NULL DEFAULT current_timestamp(),
  `user_role` varchar(50) NOT NULL,
  `password_hash` text NOT NULL,
  PRIMARY KEY (`id_utilisateur`),
  KEY `user_role` (`user_role`),
  CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`user_role`) REFERENCES `_enum_user_role` (`description`),
  CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `personnes` (`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `workflows`;
CREATE TABLE `workflows` (
  `id_workflow` int(11) NOT NULL AUTO_INCREMENT,
  `nom_workflow` text NOT NULL,
  `id_fournisseur` int(11) NOT NULL,
  PRIMARY KEY (`id_workflow`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `_enum_input_type`;
CREATE TABLE `_enum_input_type` (
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `_enum_mime_type`;
CREATE TABLE `_enum_mime_type` (
  `description` varchar(50) NOT NULL COMMENT 'application/pdf ou image/png (https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types)',
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `_enum_phases_dossier`;
CREATE TABLE `_enum_phases_dossier` (
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `_enum_statut_societe`;
CREATE TABLE `_enum_statut_societe` (
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `_enum_user_role`;
CREATE TABLE `_enum_user_role` (
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `admins`;
CREATE VIEW `admins` AS select `u`.`id_personne` AS `id_personne`,`u`.`prenom` AS `prenom`,`u`.`nom_famille` AS `nom_famille`,`u`.`civilite` AS `civilite`,`u`.`nom_entreprise` AS `nom_entreprise`,`u`.`numero_entreprise` AS `numero_entreprise`,`u`.`est_un_particulier` AS `est_un_particulier`,`u`.`id_coordonnees` AS `id_coordonnees`,`u`.`email` AS `email` from (`personnes` `u` join `utilisateurs` `a`) where `u`.`id_personne` = `a`.`id_utilisateur` and `a`.`user_role` = 'admin';

DROP TABLE IF EXISTS `clients`;
CREATE VIEW `clients` AS select `a`.`id_client` AS `id_client`,`a`.`id_commercial` AS `id_commercial`,`a`.`infos_client_supplementaires` AS `infos_client_supplementaires`,`u`.`id_personne` AS `id_personne`,`u`.`prenom` AS `prenom`,`u`.`nom_famille` AS `nom_famille`,`u`.`civilite` AS `civilite`,`u`.`nom_entreprise` AS `nom_entreprise`,`u`.`numero_entreprise` AS `numero_entreprise`,`u`.`est_un_particulier` AS `est_un_particulier`,`u`.`id_coordonnees` AS `id_coordonnees`,`u`.`email` AS `email`,coalesce((select count(`p`.`id_dossier`) AS `nb_dossiers` from `dossiers` `p` where `u`.`id_personne` = `p`.`id_client` group by `p`.`id_client`),0) AS `nb_dossiers` from ((`personnes` `u` join `clients_des_commerciaux` `a`) join (select `personnes`.`id_personne` AS `id_personne` from (`personnes` join `utilisateurs`) except select `utilisateurs`.`id_utilisateur` AS `id_utilisateur` from `utilisateurs`) `t`) where `u`.`id_personne` = `t`.`id_personne` and `a`.`id_client` = `u`.`id_personne`;

DROP TABLE IF EXISTS `commerciaux`;
CREATE VIEW `commerciaux` AS select (select count(0) from `clients_des_commerciaux` where `clients_des_commerciaux`.`id_commercial` = `u`.`id_personne`) AS `nb_clients`,(select count(0) from (`dossiers` `d` join `clients_des_commerciaux` `cc`) where `d`.`id_client` = `cc`.`id_client` and `cc`.`id_commercial` = `u`.`id_personne`) AS `nb_dossiers`,`u`.`id_personne` AS `id_personne`,`u`.`prenom` AS `prenom`,`u`.`nom_famille` AS `nom_famille`,`u`.`civilite` AS `civilite`,`u`.`nom_entreprise` AS `nom_entreprise`,`u`.`numero_entreprise` AS `numero_entreprise`,`u`.`est_un_particulier` AS `est_un_particulier`,`u`.`id_coordonnees` AS `id_coordonnees`,`u`.`email` AS `email` from (`personnes` `u` join `utilisateurs` `a`) where `u`.`id_personne` = `a`.`id_utilisateur` and `a`.`user_role` = 'commercial';

DROP TABLE IF EXISTS `dossiers_enriched`;
CREATE VIEW `dossiers_enriched` AS select `a`.`id_commercial` AS `id_commercial`,`c`.`nom_produit` AS `nom_produit`,`b`.`id_dossier` AS `id_dossier`,`b`.`id_client` AS `id_client`,`b`.`id_produit` AS `id_produit`,`b`.`commentaire` AS `commentaire`,`b`.`etat_workflow_dossier` AS `etat_workflow_dossier`,(select `l`.`date_heure` from `logs_dossiers` `l` where `l`.`id_dossier` = `b`.`id_dossier` order by `l`.`date_heure` desc limit 1) AS `date_creation`,`c`.`id_fournisseur` AS `id_fournisseur`,`d`.`role_responsable_etape` AS `role_responsable_etape`,`d`.`phase_etape` AS `phase_etape` from (((`clients_des_commerciaux` `a` join `dossiers` `b`) join `produits` `c`) join `etats_workflow` `d`) where `a`.`id_client` = `b`.`id_client` and `b`.`id_produit` = `c`.`id_produit` and `d`.`id_etat` = `b`.`etat_workflow_dossier` order by `d`.`phase_etape` desc;

DROP TABLE IF EXISTS `fichiers_enriched`;
CREATE VIEW `fichiers_enriched` AS select `a`.`id_fichier` AS `id_fichier`,`a`.`file_name` AS `file_name`,`a`.`updated_at` AS `updated_at`,`a`.`mime_type` AS `mime_type`,`a`.`in_trash` AS `in_trash`,`b`.`id_dossier` AS `id_dossier` from ((`fichiers` `a` join `fichiers_dossier` `b`) join `fichiers_produit` `c`) where `a`.`id_fichier` = `b`.`id_fichier` or `a`.`id_fichier` = `c`.`id_fichier`;

DROP TABLE IF EXISTS `fournisseurs`;
CREATE VIEW `fournisseurs` AS select `u`.`id_personne` AS `id_personne`,`u`.`prenom` AS `prenom`,`u`.`nom_famille` AS `nom_famille`,`u`.`civilite` AS `civilite`,`u`.`nom_entreprise` AS `nom_entreprise`,`u`.`numero_entreprise` AS `numero_entreprise`,`u`.`est_un_particulier` AS `est_un_particulier`,`u`.`id_coordonnees` AS `id_coordonnees`,`u`.`email` AS `email` from (`personnes` `u` join `utilisateurs` `a`) where `u`.`id_personne` = `a`.`id_utilisateur` and `a`.`user_role` = 'fournisseur';

DROP TABLE IF EXISTS `logs_enriched`;
CREATE VIEW `logs_enriched` AS select `l`.`id_log` AS `id_log`,`l`.`id_dossier` AS `id_dossier`,`l`.`id_utilisateur` AS `id_utilisateur`,`l`.`nom_action` AS `nom_action`,`l`.`desc_action` AS `desc_action`,`l`.`date_heure` AS `date_heure`,`p`.`id_personne` AS `id_personne`,`p`.`prenom` AS `prenom`,`p`.`nom_famille` AS `nom_famille`,`p`.`civilite` AS `civilite`,`p`.`nom_entreprise` AS `nom_entreprise`,`p`.`numero_entreprise` AS `numero_entreprise`,`p`.`est_un_particulier` AS `est_un_particulier`,`p`.`id_coordonnees` AS `id_coordonnees`,`p`.`email` AS `email`,nullif((select `u`.`user_role` from `utilisateurs` `u` where `p`.`id_personne` = `u`.`id_utilisateur`),'client') AS `personne_role` from (`logs_dossiers` `l` join `personnes` `p`) where `p`.`id_personne` = `l`.`id_utilisateur` order by `l`.`date_heure`;

DROP TABLE IF EXISTS `_enum_input_type`;
CREATE TABLE `_enum_input_type` (
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `_enum_user_role` VALUES ('admin'),('commercial'),('fournisseur');
INSERT INTO `_enum_statut_societe` VALUES ('autoentrepreneur'),('entreprise'),('vendeur à domicile');
INSERT INTO `_enum_phases_dossier` VALUES ('archivé'),('normal');
INSERT INTO `_enum_mime_type` VALUES ('application/pdf'),('image/gif'),('image/jpeg'),('image/png');
INSERT INTO `_enum_input_type` VALUES ('date'),('email'),('html'),('number'),('options_checkbox'),('options_radio'),('tel'),('text'),('textarea');