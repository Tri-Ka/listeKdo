-- Adresse e-mail obligatoire (inscription, profil, fenêtre pour les anciens comptes), qui sert aussi à se
-- connecter et à recevoir un lien de nouveau mot de passe. Confirmée par un code à 6 chiffres envoyé par e-mail
-- (seule son empreinte est gardée). Sans ces colonnes, rien ne change sur le site (email_enabled()).
ALTER TABLE `liste_user`
  ADD `email` VARCHAR(190) NULL DEFAULT NULL,
  ADD `email_verified_at` DATETIME NULL DEFAULT NULL,
  ADD `email_code` CHAR(40) NULL DEFAULT NULL,
  ADD `email_code_expires` DATETIME NULL DEFAULT NULL,
  ADD `email_code_tries` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD INDEX `email` (`email`);
