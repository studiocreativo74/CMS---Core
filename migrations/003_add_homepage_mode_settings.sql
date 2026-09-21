-- Title: Startseiten-Modus & Modul-Routing Settings
-- Description: Hinterlegt Konfigurationsschlüssel für Startseiten-Modus (CMS-Blöcke vs. Modul-Startseite) in der Tabelle settings
-- Version: 1.0.0

INSERT INTO `settings` (`key`, `value`) VALUES
  ('homepage_mode', 'blocks'),
  ('homepage_module_key', ''),
  ('homepage_module_route', '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
