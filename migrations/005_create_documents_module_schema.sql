-- =====================================================================
-- Migration: 005_create_documents_module_schema.sql
-- Modul: Dokumente / DMS (Paperless-Style) & Eigentümerportal-Vorbereitung
-- =====================================================================

-- 1. Tabelle: documents (Zentrales Dokument-Objekt)
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `doc_type` VARCHAR(32) NOT NULL DEFAULT 'MISC',         -- PLAN, MINUTES, REGULATION, INVOICE, CONTRACT, PROTOCOL, CERTIFICATE, CORRESPONDENCE, STATEMENT, MISC
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',          -- active, archived, draft, trash
  `document_date` DATE NULL,                               -- Rechnungsdatum, Protokolldatum etc.
  `valid_from` DATE NULL,                                  -- Gültigkeitsbeginn
  `valid_until` DATE NULL,                                 -- Gültigkeitsablauf
  `reference_number` VARCHAR(100) NULL,                    -- Aktenzeichen, Rechnungs-Nr, Beleg-Nr
  `visibility` VARCHAR(32) NOT NULL DEFAULT 'internal',    -- internal, public, owner_portal, tenant_portal, admin_only
  `created_by` INT UNSIGNED NULL,                          -- Benutzer-ID des Erstellers
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_doc_type` (`doc_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_visibility` (`visibility`),
  INDEX `idx_doc_date` (`document_date`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle: document_versions (Versionierung & Dateiablage)
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT UNSIGNED NOT NULL,
  `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `original_filename` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(128) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `file_hash` VARCHAR(64) NOT NULL,                        -- SHA-256 Prüfsumme zur Integritätsprüfung & Deduplizierung
  `page_count` INT UNSIGNED NULL,
  `change_notes` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_doc_version` (`document_id`, `version_number`),
  INDEX `idx_doc_id` (`document_id`),
  INDEX `idx_file_hash` (`file_hash`),
  CONSTRAINT `fk_doc_versions_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelle: document_tags (Schlagworte mit Farb-Badges im Paperless-Stil)
CREATE TABLE IF NOT EXISTS `document_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `slug` VARCHAR(64) NOT NULL,
  `color` VARCHAR(32) NOT NULL DEFAULT '#0d6efd',          -- Hex Badge-Farbe (z. B. #dc3545, #198754)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_tag_slug` (`slug`),
  INDEX `idx_tag_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelle: document_tag_assignments (Verknüpfung Dokument <-> Tag)
CREATE TABLE IF NOT EXISTS `document_tag_assignments` (
  `document_id` INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`document_id`, `tag_id`),
  INDEX `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_tag_assign_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tag_assign_tag` FOREIGN KEY (`tag_id`) REFERENCES `document_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabelle: document_relations (Eigentümerportal-Vorbereitung / Polymorphe Verknüpfungen)
CREATE TABLE IF NOT EXISTS `document_relations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT UNSIGNED NOT NULL,
  `target_type` VARCHAR(64) NOT NULL,                      -- property (Liegenschaft), unit (Einheit), case (Vorgang), contact, user, contract
  `target_id` VARCHAR(64) NOT NULL,                        -- ID des Zielobjekts (z. B. Liegenschafts-Nr., Einheit-ID, Vorgang-ID)
  `target_label` VARCHAR(255) NULL,                        -- Lesbare Bezeichnung (z. B. "WEG Mozartstr. 12", "Einheit 3. OG", "Vorgang #104")
  `relation_role` VARCHAR(64) NOT NULL DEFAULT 'attachment', -- attachment, contract, statement, protocol, invoice, plan
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_doc_relation_target` (`target_type`, `target_id`),
  INDEX `idx_doc_relation_doc` (`document_id`),
  CONSTRAINT `fk_doc_relations_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabelle: document_activity_logs (Audit-Trail)
CREATE TABLE IF NOT EXISTS `document_activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(64) NOT NULL,                           -- create, update, version_add, download, archive, restore, delete
  `details` TEXT NULL,                                     -- JSON mit Metadaten oder geänderten Feldern
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_doc_log_doc` (`document_id`),
  INDEX `idx_doc_log_action` (`action`),
  INDEX `idx_doc_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Initial-Tags (Klassische DMS-Kategorien)
INSERT IGNORE INTO `document_tags` (`name`, `slug`, `color`) VALUES
('Wichtig', 'wichtig', '#dc3545'),
('Vertrag', 'vertrag', '#0d6efd'),
('Rechnung', 'rechnung', '#198754'),
('WEG-Protokoll', 'weg-protokoll', '#6f42c1'),
('Bauplan', 'bauplan', '#fd7e14'),
('Eigentümerversammlung', 'eigentuemerversammlung', '#20c997'),
('Versicherung', 'versicherung', '#0dcaf0'),
('Beschluss', 'beschluss', '#e83e8c');
