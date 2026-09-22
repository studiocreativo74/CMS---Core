-- =====================================================================
-- Migration: 006_create_owner_portal_schema.sql
-- Modul: Eigentümerportal & Hausverwaltung (Phase 1)
-- =====================================================================

-- 1. Tabelle: properties (Liegenschaften / Liegenschafts-Stammdaten)
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `street` VARCHAR(255) NULL,
    `zip` VARCHAR(32) NULL,
    `city` VARCHAR(128) NULL,
    `country` VARCHAR(128) NULL DEFAULT 'Deutschland',
    `external_ref` VARCHAR(64) NULL,                       -- z. B. interne Verwaltungs-Nr. / WEG-Nummer
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_prop_name` (`name`),
    INDEX `idx_prop_ext_ref` (`external_ref`),
    INDEX `idx_prop_city` (`city`, `zip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle: units (Wohn-, Gewerbe- und Funktionseinheiten)
CREATE TABLE IF NOT EXISTS `units` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL,
    `unit_number` VARCHAR(64) NOT NULL,                    -- z. B. "WE 01", "3. OG links"
    `floor` VARCHAR(32) NULL,                              -- z. B. "EG", "1. OG", "DG", "UG"
    `size_sqm` DECIMAL(8,2) NULL,                          -- Wohn-/Nutzfläche in qm
    `mea` DECIMAL(10,4) NULL,                              -- Miteigentumsanteil (z. B. 125.5000 / 1000)
    `type` VARCHAR(32) NOT NULL DEFAULT 'apartment',       -- apartment, commercial, parking, storage, garden, other
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_unit_prop` (`property_id`),
    INDEX `idx_unit_num` (`unit_number`),
    INDEX `idx_unit_type` (`type`),
    CONSTRAINT `fk_units_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelle: unit_users (Zuordnung von Eigentümern, Mietern, Beiräten zu Einheiten)
CREATE TABLE IF NOT EXISTS `unit_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `relation_type` VARCHAR(32) NOT NULL DEFAULT 'owner',  -- owner, tenant, advisory_board, property_manager, external
    `valid_from` DATE NULL,
    `valid_to` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_uu_unit` (`unit_id`),
    INDEX `idx_uu_user` (`user_id`),
    INDEX `idx_uu_rel` (`relation_type`),
    INDEX `idx_uu_valid` (`valid_from`, `valid_to`),
    CONSTRAINT `fk_uu_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelle: cases (Vorgänge / Gebäudeakte, Versammlungen, Schadenfälle)
CREATE TABLE IF NOT EXISTS `cases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL,
    `unit_id` INT UNSIGNED NULL,                           -- Optional: Falls an einer bestimmten Einheit aufgetreten
    `case_type` VARCHAR(32) NOT NULL,                      -- property (Gebäudeakte), meeting (Versammlungen), damage (Schadenfälle)
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'new',           -- new, in_progress, waiting_external, resolved, closed
    `priority` VARCHAR(32) NOT NULL DEFAULT 'normal',      -- low, normal, high, urgent
    `creator_user_id` INT UNSIGNED NULL,                   -- Wer hat den Vorgang angelegt
    `assigned_user_id` INT UNSIGNED NULL,                  -- Zuständiger Sachbearbeiter / Verwalter
    `meeting_date` DATETIME NULL,                          -- Speziell für MeetingCase (Versammlungsdatum)
    `damage_location` VARCHAR(255) NULL,                   -- Speziell für DamageCase (Ort des Schadens, z. B. Keller, Dach)
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_case_prop` (`property_id`),
    INDEX `idx_case_unit` (`unit_id`),
    INDEX `idx_case_type` (`case_type`),
    INDEX `idx_case_status` (`status`),
    INDEX `idx_case_prio` (`priority`),
    INDEX `idx_case_creator` (`creator_user_id`),
    CONSTRAINT `fk_cases_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabelle: case_messages (Vorgangs-Kommunikation / Nachrichten-Historie)
CREATE TABLE IF NOT EXISTS `case_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `message` TEXT NOT NULL,
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0,          -- 1 = Nur für Verwaltung sichtbar; 0 = Auch für Eigentümer/Mieter sichtbar
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cm_case` (`case_id`),
    INDEX `idx_cm_user` (`user_id`),
    CONSTRAINT `fk_cm_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Berechtigungen (Permissions) für das Eigentümerportal einpflegen
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`, `created_at`, `updated_at`) VALUES
('portal.view', 'Portal Zugriff', 'Zugriff auf das Eigentümer- & Mieterportal', NOW(), NOW()),
('portal.properties.manage', 'Liegenschaften verwalten', 'Anlegen, Bearbeiten und Löschen von Liegenschaften', NOW(), NOW()),
('portal.units.manage', 'Einheiten verwalten', 'Verwaltung von Einheiten und Nutzer-Zuweisungen', NOW(), NOW()),
('portal.cases.manage', 'Cases verwalten', 'Verwaltung von Gebäudeakten, Versammlungen und Schäden', NOW(), NOW()),
('portal.damage.create', 'Schaden melden', 'Möglichkeit zur Erfassung neuer Schadensmeldungen im Portal', NOW(), NOW()),
('portal.documents.view', 'Portal Dokumente ansehen', 'Einsicht in Liegenschafts- und Einheiten-Dokumente', NOW(), NOW());

-- 7. Rollen anlegen, falls noch nicht vorhanden
INSERT IGNORE INTO `roles` (`key`, `name`, `description`, `created_at`, `updated_at`) VALUES
('property_manager', 'Hausverwalter', 'Verwaltung von Liegenschaften, Einheiten, Versammlungen und Schäden', NOW(), NOW()),
('advisory_board', 'Verwaltungsbeirat', 'Beiratsmitglied mit erweiterten Einsichtsrechten in Versammlungen und Dokumente', NOW(), NOW()),
('owner', 'Eigentümer', 'Wohnungseigentümer mit Zugriff auf eigene Einheiten, Abrechnungen und Versammlungsakten', NOW(), NOW()),
('tenant', 'Mieter', 'Mieter mit Zugriff auf die gemietete Einheit und Schadensmeldung', NOW(), NOW()),
('external', 'Dienstleister / Handwerker', 'Externer Dienstleister für zugewiesene Schadenfälle und Aufträge', NOW(), NOW());

-- 8. Berechtigungen den Rollen zuweisen
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r, `permissions` p
WHERE r.`key` IN ('admin', 'superadmin', 'property_manager') 
  AND p.`key` IN ('portal.view', 'portal.properties.manage', 'portal.units.manage', 'portal.cases.manage', 'portal.damage.create', 'portal.documents.view');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r, `permissions` p
WHERE r.`key` IN ('advisory_board', 'owner', 'tenant') 
  AND p.`key` IN ('portal.view', 'portal.damage.create', 'portal.documents.view');
