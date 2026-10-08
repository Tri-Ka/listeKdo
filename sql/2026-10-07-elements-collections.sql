-- Liens et archivage individuel des éléments de collection.
-- À exécuter une seule fois dans phpMyAdmin, base datcharrye, onglet SQL.
ALTER TABLE `liste_item`
  ADD `link` text,
  ADD `received_at` datetime DEFAULT NULL;
