-- Title: Modul-Kompatibilität (requires_core)
-- Description: Erweitert die Tabelle modules um die Spalte requires_core zur Prüfung von Versionsanforderungen
-- Version: 1.0.0

ALTER TABLE `modules`
  ADD COLUMN `requires_core` VARCHAR(50) NULL DEFAULT NULL AFTER `version`;
