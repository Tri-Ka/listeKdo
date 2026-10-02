-- Liste privée : visible seulement par son propriétaire (et les gestionnaires d'une liste secondaire).
-- Les amis ne la voient plus (ni dans leur colonne d'amis, ni dans leurs notifications), le lien affiche « Liste privée ».
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne : l'option est simplement masquée dans « Mon profil ».

ALTER TABLE `liste_user` ADD `is_private` tinyint(1) NOT NULL DEFAULT 0;
