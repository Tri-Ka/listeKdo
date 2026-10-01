-- Question secrète pour récupérer son mot de passe (« Mot de passe oublié ? »).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans ces colonnes, le site fonctionne : le lien « Mot de passe oublié ? » est simplement masqué.

ALTER TABLE `liste_user`
  ADD `secret_question` VARCHAR(255) DEFAULT NULL,
  ADD `secret_answer` VARCHAR(255) DEFAULT NULL,
  ADD `secret_fails` TINYINT(3) NOT NULL DEFAULT 0,
  ADD `secret_locked_until` DATETIME DEFAULT NULL;
