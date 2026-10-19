-- =====================================================================
-- Migration: 011_create_boating_school_notifications.sql
-- Modul: Bootsfahrschule (boating_school) - E-Mail-Kommunikation,
-- Vorlagen & Cron-Erinnerungen
-- =====================================================================
-- Hinweis: Gemäß Vorgaben nicht automatisch per PHP ausführen,
-- sondern bei Bedarf manuell in phpMyAdmin ausführen.
-- =====================================================================

-- 1. Tabelle bs_appointments um Spalte für Terminerinnerungs-Timestamp erweitern
ALTER TABLE `bs_appointments`
    ADD COLUMN IF NOT EXISTS `reminder_sent_at` DATETIME NULL AFTER `updated_at`,
    ADD INDEX IF NOT EXISTS `idx_bs_app_reminder` (`reminder_sent_at`, `status`, `date`);

-- 2. Tabelle bs_customers sicherstellen: Benachrichtigungs-Präferenzen
ALTER TABLE `bs_customers`
    ADD COLUMN IF NOT EXISTS `notify_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `experience_level`,
    ADD COLUMN IF NOT EXISTS `notify_whatsapp` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notify_email`;

-- 3. Standard-Einstellungen & E-Mail-Templates in settings-Tabelle hinterlegen
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
-- Allgemeine E-Mail-Konfiguration
('boating_mail_from_address', 'info@bootschule.ch'),
('boating_mail_from_name', 'Deine Bootsfahrschule'),
('boating_mail_bcc_admin', ''),
('boating_mail_reply_to', ''),
('boating_mail_admin_address', 'info@bootschule.ch'),
('boating_reminder_hours_before', '24'),

-- Benachrichtigungs-Toggles (1 = aktiv, 0 = deaktiviert)
('boating_notify_customer_booking_created', '1'),
('boating_notify_customer_booking_updated', '1'),
('boating_notify_customer_booking_cancelled', '1'),
('boating_notify_customer_booking_reminder', '1'),
('boating_notify_admin_new_booking', '1'),
('boating_notify_instructor_new_booking', '0'),

-- Kunden-Template: Neue Buchung / Anfrage
('boating_tpl_customer_booking_created_subject', 'Buchungsbestätigung: Deine Fahrstunde / Buchung am {date}'),
('boating_tpl_customer_booking_created_body', 'Hallo {customer_first_name},

vielen Dank für deine Buchungsanfrage bei unserer Bootsfahrschule!

Hier sind deine Buchungsdetails im Überblick:
- Buchungs-ID: #{appointment_id}
- Leistung: {service_name}
- Datum: {date}
- Zeitfenster: {start_time} - {end_time} Uhr
- Treffpunkt / Steg: {location_name}
- Schulungsboot: {boat_name}
- Zugewiesener Fahrlehrer: {instructor_name}
- Status: {status_label}
- Preis / Gebühr: {price} CHF

{notes_block}

Deine Termine und Buchungen kannst du jederzeit in unserem Kundenportal einsehen:
{portal_url}

Wir freuen uns auf die Ausbildungszeit mit dir auf dem See!

Freundliche Grüsse
{school_name}'),

-- Kunden-Template: Terminänderung / Verschiebung
('boating_tpl_customer_booking_updated_subject', 'Terminänderung: Deine Buchung #{appointment_id} wurde aktualisiert'),
('boating_tpl_customer_booking_updated_body', 'Hallo {customer_first_name},

dein Termin bei unserer Bootsfahrschule wurde aktualisiert:

Aktuelle Buchungsdaten:
- Buchungs-ID: #{appointment_id}
- Leistung: {service_name}
- Neues Datum: {date}
- Uhrzeit: {start_time} - {end_time} Uhr
- Treffpunkt: {location_name}
- Schulungsboot: {boat_name}
- Fahrlehrer: {instructor_name}
- Neuer Status: {status_label}

{changes_block}

Du kannst alle Details jederzeit in deinem Kundenkonto prüfen:
{portal_url}

Freundliche Grüsse
{school_name}'),

-- Kunden-Template: Stornierung
('boating_tpl_customer_booking_cancelled_subject', 'Stornierungsbestätigung: Termin #{appointment_id} am {date}'),
('boating_tpl_customer_booking_cancelled_body', 'Hallo {customer_first_name},

dein gebuchter Termin bei unserer Bootsfahrschule wurde storniert:

- Buchungs-ID: #{appointment_id}
- Leistung: {service_name}
- Ursprüngliches Datum: {date}
- Zeitfenster: {start_time} - {end_time} Uhr
- Storniert durch: {cancelled_by}
{cancel_reason_block}

Wir bedauern die Absage. Einen neuen Termin kannst du jederzeit bequem über unser Buchungsportal auswählen:
{portal_url}

Freundliche Grüsse
{school_name}'),

-- Kunden-Template: Terminerinnerung
('boating_tpl_customer_booking_reminder_subject', 'Terminerinnerung: Deine Bootsfahrstunde am {date} um {start_time} Uhr'),
('boating_tpl_customer_booking_reminder_body', 'Hallo {customer_first_name},

wir möchten dich an deine bevorstehende Fahrstunde / Bootsbuchung erinnern:

- Datum: {date}
- Uhrzeit: {start_time} - {end_time} Uhr
- Leistung: {service_name}
- Treffpunkt / Hafen: {location_name}
- Schulungsboot: {boat_name}
- Fahrlehrer: {instructor_name}

Bitte sei pünktlich ca. 5 bis 10 Minuten vor Ausbildungsbeginn am Steg. Denk an wetterangepasste Kleidung und Bootsschuhe (helle, saubere Sohle).

Bis bald auf dem Wasser!

Freundliche Grüsse
{school_name}'),

-- Admin-Template: Neue Buchung eingegangen
('boating_tpl_admin_new_booking_subject', '[Neue Buchung] #{appointment_id} von {customer_name} am {date}'),
('boating_tpl_admin_new_booking_body', 'Hallo Bootschule-Team,

über das Kundenportal ist eine neue Buchung / Terminanfrage eingegangen:

- Buchungs-ID: #{appointment_id}
- Kunde: {customer_name}
- E-Mail: {customer_email}
- Telefon: {customer_phone}
- Leistung: {service_name}
- Datum: {date}
- Uhrzeit: {start_time} - {end_time} Uhr
- Standort / Steg: {location_name}
- Zugewiesenes Boot: {boat_name}
- Zugewiesener Fahrlehrer: {instructor_name}
- Preis: {price} CHF
- Status: {status_label}

Kundenhinweise:
{notes}

Direkt zur Kalender-Disposition im Admin:
{admin_calendar_url}'),

-- Fahrlehrer-Template: Neuer Termin / Fahrstunde zugewiesen
('boating_tpl_instructor_new_booking_subject', 'Neuer Termin zugewiesen: #{appointment_id} am {date} ({service_name})'),
('boating_tpl_instructor_new_booking_body', 'Hallo {instructor_first_name},

dir wurde eine neue Fahrstunde / Terminbuchung zugewiesen:

- Buchungs-ID: #{appointment_id}
- Fahrschüler: {customer_name} (Tel: {customer_phone}, E-Mail: {customer_email})
- Leistung: {service_name}
- Datum: {date}
- Uhrzeit: {start_time} - {end_time} Uhr
- Treffpunkt: {location_name}
- Schulungsboot: {boat_name}
- Besonderheiten / Notizen: {notes}

Viel Erfolg bei der Schulungseinheit!

Freundliche Grüsse
{school_name}');
