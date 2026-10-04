-- Visite guidée : date de fin par compte, pour ne la proposer qu'à la première connexion.
ALTER TABLE `liste_user` ADD `onboarding_seen_at` datetime DEFAULT NULL;
