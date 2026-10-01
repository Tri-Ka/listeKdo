-- Ajoute le petit mot du propriétaire, affiché en bas de sa liste.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne : le texte par défaut du thème s'affiche et le champ est masqué.

ALTER TABLE `liste_user` ADD `message` VARCHAR(500) DEFAULT NULL;
