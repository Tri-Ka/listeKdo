-- Cadeau à plusieurs : un participant peut dire « Je le prends » (c'est lui qui achète le cadeau).
-- Un seul acheteur par idée. À exécuter une seule fois dans phpMyAdmin, base datcharrye, onglet SQL.
ALTER TABLE `liste_participation`
  ADD `buyer` TINYINT(1) NOT NULL DEFAULT 0;
