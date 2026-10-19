-- =====================================================================
-- Migration: 012_create_boating_school_payroll_reports.sql
-- Modul: Bootsfahrschule (boating_school) - Reporting & Fahrlehrer-Payroll
-- =====================================================================
-- Hinweis: Idempotente DDL-Befehle für MySQL 8.
-- 1. bs_instructors: pay_rate_type, pay_rate_amount, payroll_external_code
-- 2. bs_appointments: instructor_payout_status, instructor_payout_amount, instructor_payout_note
-- 3. Berechtigungen für Reporting & Payroll
-- =====================================================================

-- 1. Tabelle: bs_instructors um Payroll-Felder ergänzen
ALTER TABLE `bs_instructors`
    ADD COLUMN IF NOT EXISTS `pay_rate_type` ENUM('per_hour','per_appointment') NOT NULL DEFAULT 'per_hour' AFTER `license_number`,
    ADD COLUMN IF NOT EXISTS `pay_rate_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `pay_rate_type`,
    ADD COLUMN IF NOT EXISTS `payroll_external_code` VARCHAR(64) NULL AFTER `pay_rate_amount`;

ALTER TABLE `bs_instructors`
    ADD INDEX IF NOT EXISTS `idx_bs_inst_payroll_code` (`payroll_external_code`);

-- 2. Tabelle: bs_appointments um Payroll-Abrechnungsstatus & Betrag ergänzen
ALTER TABLE `bs_appointments`
    ADD COLUMN IF NOT EXISTS `instructor_payout_status` ENUM('open','exported','paid') NOT NULL DEFAULT 'open' AFTER `price`,
    ADD COLUMN IF NOT EXISTS `instructor_payout_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `instructor_payout_status`,
    ADD COLUMN IF NOT EXISTS `instructor_payout_note` VARCHAR(255) NULL AFTER `instructor_payout_amount`;

ALTER TABLE `bs_appointments`
    ADD INDEX IF NOT EXISTS `idx_bs_app_payout_status` (`instructor_payout_status`),
    ADD INDEX IF NOT EXISTS `idx_bs_app_inst_payout` (`instructor_id`, `date`, `instructor_payout_status`);

-- 3. Standard-Berechtigungen (falls RBAC-Tabellen existieren)
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`, `created_at`) VALUES
('boating.reports.view', 'Berichte & Auswertungen', 'Berichte, Auswertungen & Kennzahlen der Bootsfahrschule einsehen', NOW()),
('boating.payroll.manage', 'Fahrlehrer-Payroll verwalten', 'Fahrlehrer-Payroll verwalten, Beträge anpassen und Lohn-Exporte durchführen', NOW());
