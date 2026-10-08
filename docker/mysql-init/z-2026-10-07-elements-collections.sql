-- Liens et archivage individuel des éléments de collection.
ALTER TABLE `liste_item`
  ADD `link` text,
  ADD `received_at` datetime DEFAULT NULL;
