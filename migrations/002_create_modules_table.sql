-- Title: Modulverwaltung Tabelle
-- Description: Erstellt die Tabelle `modules` zur Registrierung, Aktivierung und Steuerung modularer Erweiterungen
-- Version: 1.0.0

CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `installed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`key`),
  INDEX `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
