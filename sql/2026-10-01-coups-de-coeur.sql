-- Ajoute les « coups de cœur » (favoris) du propriétaire d'une liste.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne mais le bouton cœur affiche une erreur.

ALTER TABLE `liste_noel` ADD `favorite` TINYINT(1) NOT NULL DEFAULT 0;
