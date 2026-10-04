-- Idées suggérées par les amis : une idée ajoutée par un ami sur la liste de quelqu'un d'autre.
-- Le propriétaire de la liste ne la voit jamais ; ses autres amis la voient, la réservent, la commentent.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne et le bouton « Suggérer une idée » est masqué.

ALTER TABLE `liste_noel` ADD `suggested_by` int(11) DEFAULT NULL, ADD INDEX `suggested_by` (`suggested_by`);
