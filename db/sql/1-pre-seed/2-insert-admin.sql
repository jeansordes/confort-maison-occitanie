use cwapgwkr_confort_maison_occitanie;

-- admin_cmo@yopmail.com:adminadmin

INSERT IGNORE INTO `coordonnees` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL);

INSERT IGNORE INTO `personnes` (`id_personne`, `prenom`, `nom_famille`, `civilite`, `nom_entreprise`, `numero_entreprise`, `est_un_particulier`, `id_coordonnees`, `email`) VALUES
(1,	NULL,	NULL,	NULL,	'Administrateur',	NULL,	0,	1,	'admin_cmo@yopmail.com');

INSERT IGNORE INTO `utilisateurs` (`id_utilisateur`, `last_user_update`, `user_role`, `password_hash`) VALUES
(1,	'17:01:40',	'admin',	'$2y$12$zNlOyjiF6iiMzzZF3DDyv.nEtJy00GnxIH9lKVuTzk3M2GOtGazxq');