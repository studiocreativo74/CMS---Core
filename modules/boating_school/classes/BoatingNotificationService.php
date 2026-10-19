<?php

declare(strict_types=1);

/**
 * BoatingNotificationService
 *
 * Verwaltet die E-Mail-Kommunikation für die Bootsfahrschule:
 * - Buchungsbestätigungen & -anfragen (Kunde, Admin, Fahrlehrer)
 * - Terminänderungen & Verschiebungen (Kunde)
 * - Stornierungen (Kunde, Admin, Fahrlehrer)
 * - Cron-gestützte Terminerinnerungen (Kunde)
 * - Beachtung der Kundenpräferenzen (notify_email)
 * - Dynamische Platzhalter-Ersetzung & E-Mail-Vorlagen
 * - Vollständige Konfigurierbarkeit über Settings
 */
final class BoatingNotificationService
{
    // E-Mail-Konfigurations-Schlüssel (Settings)
    public const KEY_FROM_ADDRESS       = 'boating_mail_from_address';
    public const KEY_FROM_NAME          = 'boating_mail_from_name';
    public const KEY_BCC_ADMIN          = 'boating_mail_bcc_admin';
    public const KEY_REPLY_TO           = 'boating_mail_reply_to';
    public const KEY_ADMIN_ADDRESS      = 'boating_mail_admin_address';
    public const KEY_REMINDER_HOURS     = 'boating_reminder_hours_before';

    // Notification-Toggles (bool: '1' oder '0')
    public const KEY_NOTIFY_CUSTOMER_CREATED   = 'boating_notify_customer_booking_created';
    public const KEY_NOTIFY_CUSTOMER_UPDATED   = 'boating_notify_customer_booking_updated';
    public const KEY_NOTIFY_CUSTOMER_CANCELLED = 'boating_notify_customer_booking_cancelled';
    public const KEY_NOTIFY_CUSTOMER_REMINDER  = 'boating_notify_customer_booking_reminder';
    public const KEY_NOTIFY_ADMIN_NEW          = 'boating_notify_admin_new_booking';
    public const KEY_NOTIFY_INSTRUCTOR_NEW     = 'boating_notify_instructor_new_booking';

    // Templates: Kunde
    public const KEY_TPL_CUSTOMER_CREATED_SUBJ   = 'boating_tpl_customer_booking_created_subject';
    public const KEY_TPL_CUSTOMER_CREATED_BODY   = 'boating_tpl_customer_booking_created_body';
    public const KEY_TPL_CUSTOMER_UPDATED_SUBJ   = 'boating_tpl_customer_booking_updated_subject';
    public const KEY_TPL_CUSTOMER_UPDATED_BODY   = 'boating_tpl_customer_booking_updated_body';
    public const KEY_TPL_CUSTOMER_CANCELLED_SUBJ = 'boating_tpl_customer_booking_cancelled_subject';
    public const KEY_TPL_CUSTOMER_CANCELLED_BODY = 'boating_tpl_customer_booking_cancelled_body';
    public const KEY_TPL_CUSTOMER_REMINDER_SUBJ  = 'boating_tpl_customer_booking_reminder_subject';
    public const KEY_TPL_CUSTOMER_REMINDER_BODY  = 'boating_tpl_customer_booking_reminder_body';

    // Templates: Admin
    public const KEY_TPL_ADMIN_NEW_SUBJ          = 'boating_tpl_admin_new_booking_subject';
    public const KEY_TPL_ADMIN_NEW_BODY          = 'boating_tpl_admin_new_booking_body';

    // Templates: Fahrlehrer
    public const KEY_TPL_INSTRUCTOR_NEW_SUBJ     = 'boating_tpl_instructor_new_booking_subject';
    public const KEY_TPL_INSTRUCTOR_NEW_BODY     = 'boating_tpl_instructor_new_booking_body';

    // Templates: Coach Timesheets (Schweizer Monatsrapporte)
    public const KEY_TPL_COACH_TIMESHEET_SUBMITTED_SUBJ = 'coach_timesheet_submitted_subject';
    public const KEY_TPL_COACH_TIMESHEET_SUBMITTED_BODY = 'coach_timesheet_submitted_body';
    public const KEY_TPL_COACH_TIMESHEET_APPROVED_SUBJ  = 'coach_timesheet_approved_subject';
    public const KEY_TPL_COACH_TIMESHEET_APPROVED_BODY  = 'coach_timesheet_approved_body';
    public const KEY_TPL_COACH_TIMESHEET_REJECTED_SUBJ  = 'coach_timesheet_rejected_subject';
    public const KEY_TPL_COACH_TIMESHEET_REJECTED_BODY  = 'coach_timesheet_rejected_body';

    /**
     * Standardwerte für alle Benachrichtigungs-Einstellungen und Vorlagen.
     *
     * @return array<string, string>
     */
    public static function getDefaults(): array
    {
        return [
            self::KEY_FROM_ADDRESS       => 'info@bootschule.ch',
            self::KEY_FROM_NAME          => 'Deine Bootsfahrschule',
            self::KEY_BCC_ADMIN          => '',
            self::KEY_REPLY_TO           => '',
            self::KEY_ADMIN_ADDRESS      => 'info@bootschule.ch',
            self::KEY_REMINDER_HOURS     => '24',

            self::KEY_NOTIFY_CUSTOMER_CREATED   => '1',
            self::KEY_NOTIFY_CUSTOMER_UPDATED   => '1',
            self::KEY_NOTIFY_CUSTOMER_CANCELLED => '1',
            self::KEY_NOTIFY_CUSTOMER_REMINDER  => '1',
            self::KEY_NOTIFY_ADMIN_NEW          => '1',
            self::KEY_NOTIFY_INSTRUCTOR_NEW     => '0',

            // Kunde: Erstellt / Angefragt
            self::KEY_TPL_CUSTOMER_CREATED_SUBJ => 'Buchungsbestätigung: Deine Fahrstunde / Buchung am {date}',
            self::KEY_TPL_CUSTOMER_CREATED_BODY => "Hallo {customer_first_name},\n\n"
                . "vielen Dank für deine Buchungsanfrage bei unserer Bootsfahrschule!\n\n"
                . "Hier sind deine Buchungsdetails im Überblick:\n"
                . "- Buchungs-ID: #{appointment_id}\n"
                . "- Leistung: {service_name}\n"
                . "- Datum: {date}\n"
                . "- Zeitfenster: {start_time} - {end_time} Uhr\n"
                . "- Treffpunkt / Steg: {location_name}\n"
                . "- Schulungsboot: {boat_name}\n"
                . "- Zugewiesener Fahrlehrer: {instructor_name}\n"
                . "- Status: {status_label}\n"
                . "- Preis / Gebühr: {price} CHF\n\n"
                . "{notes_block}\n\n"
                . "Deine Termine kannst du jederzeit passwortlos über deinen persönlichen Direktlink einsehen und verwalten:\n"
                . "{{my_appointments_url}}\n\n"
                . "Wir freuen uns auf die Ausbildungszeit mit dir auf dem See!\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Kunde: Geändert / Verschoben
            self::KEY_TPL_CUSTOMER_UPDATED_SUBJ => 'Terminänderung: Deine Buchung #{appointment_id} wurde aktualisiert',
            self::KEY_TPL_CUSTOMER_UPDATED_BODY => "Hallo {customer_first_name},\n\n"
                . "dein Termin bei unserer Bootsfahrschule wurde aktualisiert:\n\n"
                . "Aktuelle Buchungsdaten:\n"
                . "- Buchungs-ID: #{appointment_id}\n"
                . "- Leistung: {service_name}\n"
                . "- Neues Datum: {date}\n"
                . "- Uhrzeit: {start_time} - {end_time} Uhr\n"
                . "- Treffpunkt: {location_name}\n"
                . "- Schulungsboot: {boat_name}\n"
                . "- Fahrlehrer: {instructor_name}\n"
                . "- Neuer Status: {status_label}\n\n"
                . "{changes_block}\n\n"
                . "Du kannst alle Details jederzeit in deinem Kundenkonto prüfen:\n"
                . "{portal_url}\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Kunde: Storniert
            self::KEY_TPL_CUSTOMER_CANCELLED_SUBJ => 'Stornierungsbestätigung: Termin #{appointment_id} am {date}',
            self::KEY_TPL_CUSTOMER_CANCELLED_BODY => "Hallo {customer_first_name},\n\n"
                . "dein gebuchter Termin bei unserer Bootsfahrschule wurde storniert:\n\n"
                . "- Buchungs-ID: #{appointment_id}\n"
                . "- Leistung: {service_name}\n"
                . "- Ursprüngliches Datum: {date}\n"
                . "- Zeitfenster: {start_time} - {end_time} Uhr\n"
                . "- Storniert durch: {cancelled_by}\n"
                . "{cancel_reason_block}\n\n"
                . "Wir bedauern die Absage. Einen neuen Termin kannst du jederzeit bequem über unser Buchungsportal reservieren:\n"
                . "{portal_url}\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Kunde: Terminerinnerung
            self::KEY_TPL_CUSTOMER_REMINDER_SUBJ => 'Terminerinnerung: Deine Bootsfahrstunde am {date} um {start_time} Uhr',
            self::KEY_TPL_CUSTOMER_REMINDER_BODY => "Hallo {customer_first_name},\n\n"
                . "wir möchten dich an deine bevorstehende Fahrstunde / Bootsbuchung erinnern:\n\n"
                . "- Datum: {date}\n"
                . "- Uhrzeit: {start_time} - {end_time} Uhr\n"
                . "- Leistung: {service_name}\n"
                . "- Treffpunkt / Hafen: {location_name}\n"
                . "- Schulungsboot: {boat_name}\n"
                . "- Fahrlehrer: {instructor_name}\n\n"
                . "Bitte sei pünktlich ca. 5 bis 10 Minuten vor Ausbildungsbeginn am Steg. "
                . "Denk an wetterangepasste Kleidung und Bootsschuhe (helle, saubere Sohle).\n\n"
                . "Termindetails prüfen oder bei Bedarf verwalten:\n"
                . "{{my_appointments_url}}\n\n"
                . "Bis bald auf dem Wasser!\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Admin: Neue Buchung
            self::KEY_TPL_ADMIN_NEW_SUBJ => '[Neue Buchung] #{appointment_id} von {customer_name} am {date}',
            self::KEY_TPL_ADMIN_NEW_BODY => "Hallo Bootschule-Team,\n\n"
                . "über das Kundenportal ist eine neue Buchung / Terminanfrage eingegangen:\n\n"
                . "- Buchungs-ID: #{appointment_id}\n"
                . "- Kunde: {customer_name}\n"
                . "- E-Mail: {customer_email}\n"
                . "- Telefon: {customer_phone}\n"
                . "- Leistung: {service_name}\n"
                . "- Datum: {date}\n"
                . "- Uhrzeit: {start_time} - {end_time} Uhr\n"
                . "- Standort / Steg: {location_name}\n"
                . "- Zugewiesenes Boot: {boat_name}\n"
                . "- Zugewiesener Fahrlehrer: {instructor_name}\n"
                . "- Preis: {price} CHF\n"
                . "- Status: {status_label}\n\n"
                . "Kundenhinweise:\n"
                . "{notes}\n\n"
                . "Direkt zur Kalender-Disposition im Admin:\n"
                . "{admin_calendar_url}",

            // Fahrlehrer: Neue Zuweisung
            self::KEY_TPL_INSTRUCTOR_NEW_SUBJ => 'Neuer Termin zugewiesen: #{appointment_id} am {date} ({service_name})',
            self::KEY_TPL_INSTRUCTOR_NEW_BODY => "Hallo {instructor_first_name},\n\n"
                . "dir wurde eine neue Fahrstunde / Terminbuchung zugewiesen:\n\n"
                . "- Buchungs-ID: #{appointment_id}\n"
                . "- Fahrschüler: {customer_name} (Tel: {customer_phone}, E-Mail: {customer_email})\n"
                . "- Leistung: {service_name}\n"
                . "- Datum: {date}\n"
                . "- Uhrzeit: {start_time} - {end_time} Uhr\n"
                . "- Treffpunkt: {location_name}\n"
                . "- Schulungsboot: {boat_name}\n"
                . "- Besonderheiten / Notizen: {notes}\n\n"
                . "Viel Erfolg bei der Schulungseinheit!\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Coach Timesheet: Eingereicht (an Admin/Buchhaltung)
            self::KEY_TPL_COACH_TIMESHEET_SUBMITTED_SUBJ => '[Timesheet eingereicht] Monatsrapport {month} von {coach_name}',
            self::KEY_TPL_COACH_TIMESHEET_SUBMITTED_BODY => "Hallo Administration / Lohnbuchhaltung,\n\n"
                . "Fahrcoach {coach_name} hat den Monatsrapport für {month} eingereicht:\n\n"
                . "- Coach: {coach_name} (ID: #{instructor_id})\n"
                . "- Monat: {month}\n"
                . "- Geleistete Arbeitsstunden: {total_hours} h\n"
                . "- Berechneter Bruttolohn: CHF {gross_amount}\n"
                . "- Eingereicht am: {submitted_at}\n\n"
                . "Bitte prüfe und genehmige den Rapport in der Administration:\n"
                . "{admin_timesheet_url}\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Coach Timesheet: Genehmigt (an Coach)
            self::KEY_TPL_COACH_TIMESHEET_APPROVED_SUBJ => 'Dein Monatsrapport {month} wurde genehmigt',
            self::KEY_TPL_COACH_TIMESHEET_APPROVED_BODY => "Hallo {coach_name},\n\n"
                . "dein Monatsrapport für {month} wurde geprüft und genehmigt:\n\n"
                . "- Monat: {month}\n"
                . "- Arbeitsstunden: {total_hours} h\n"
                . "- Bruttolohn: CHF {gross_amount}\n"
                . "- Gesetzliche Abzüge (AHV/ALV/BVG/etc.): CHF {deductions_total}\n"
                . "- Nettolohn zur Auszahlung: CHF {net_amount}\n\n"
                . "Der Auszahlungsbetrag wird mit dem nächsten Lohnlauf auf dein Bankkonto überwiesen.\n\n"
                . "Vielen Dank für deinen geschätzten Einsatz auf dem See!\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",

            // Coach Timesheet: Zurückgewiesen (an Coach)
            self::KEY_TPL_COACH_TIMESHEET_REJECTED_SUBJ => 'Rückfrage / Korrektur nötig: Dein Monatsrapport {month}',
            self::KEY_TPL_COACH_TIMESHEET_REJECTED_BODY => "Hallo {coach_name},\n\n"
                . "dein Monatsrapport für {month} konnte in der vorliegenden Form leider noch nicht freigegeben werden.\n\n"
                . "Begründung der Lohnbuchhaltung:\n"
                . "{rejection_reason}\n\n"
                . "Bitte öffne dein Fahrlehrer-Portal, passe die betreffenden Stunden bzw. Termine an und reiche den Monat erneut ein:\n"
                . "{portal_url}\n\n"
                . "Bei Fragen steht dir die Schulleitung gerne zur Verfügung.\n\n"
                . "Freundliche Grüsse\n"
                . "{school_name}",
        ];
    }

    /**
     * Lädt einen Einstellungswert aus Settings mit Fallback auf Standardwerte.
     */
    public static function getSetting(string $key): string
    {
        $defaults = self::getDefaults();
        $default = $defaults[$key] ?? '';

        if (class_exists('Settings')) {
            $val = Settings::get($key, $default);
            return $val !== null ? (string) $val : $default;
        }

        return $default;
    }

    /**
     * Prüft, ob ein boolescher Toggle aktiv ist ('1' oder true).
     */
    public static function isToggleEnabled(string $key): bool
    {
        $val = trim(self::getSetting($key));
        return $val === '1' || strtolower($val) === 'true' || strtolower($val) === 'yes';
    }

    /**
     * Liefert alle aktuellen Einstellungen als assoziatives Array.
     *
     * @return array<string, string>
     */
    public static function getAllSettings(): array
    {
        $result = [];
        $defaults = self::getDefaults();
        foreach ($defaults as $key => $defaultVal) {
            $result[$key] = self::getSetting($key);
        }
        return $result;
    }

    /**
     * Speichert übergebene Einstellungen in der Settings-Tabelle.
     *
     * @param array<string, mixed> $data
     */
    public static function saveSettings(array $data): void
    {
        if (!class_exists('Settings')) {
            return;
        }

        $defaults = self::getDefaults();

        // 1. Toggles speichern: Wenn im Formular nicht übergeben (Checkbox unchecked), auf '0' setzen
        $toggleKeys = [
            self::KEY_NOTIFY_CUSTOMER_CREATED,
            self::KEY_NOTIFY_CUSTOMER_UPDATED,
            self::KEY_NOTIFY_CUSTOMER_CANCELLED,
            self::KEY_NOTIFY_CUSTOMER_REMINDER,
            self::KEY_NOTIFY_ADMIN_NEW,
            self::KEY_NOTIFY_INSTRUCTOR_NEW,
        ];

        foreach ($toggleKeys as $tKey) {
            $val = !empty($data[$tKey]) && ($data[$tKey] === '1' || $data[$tKey] === 1 || $data[$tKey] === 'on') ? '1' : '0';
            Settings::set($tKey, $val);
        }

        // 2. Textfelder und Templates speichern
        foreach ($defaults as $key => $defaultVal) {
            if (in_array($key, $toggleKeys, true)) {
                continue;
            }
            if (array_key_exists($key, $data)) {
                $raw = is_string($data[$key]) ? trim($data[$key]) : (string) $data[$key];
                Settings::set($key, $raw);
            }
        }
    }

    /**
     * Stellt sicher, dass Standard-Settings und Spalten in der DB existieren.
     */
    public static function ensureSettings(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        // Repository Schema-Check
        if (class_exists('BoatingSchoolRepository')) {
            BoatingSchoolRepository::ensureCrmSchema();
        }

        // Standard-Settings in DB einpflegen falls noch leer
        if (class_exists('Settings')) {
            try {
                $defaults = self::getDefaults();
                foreach ($defaults as $k => $defVal) {
                    $existing = Settings::get($k);
                    if ($existing === null) {
                        Settings::set($k, $defVal);
                    }
                }
            } catch (\Throwable $e) {
                error_log('BoatingNotificationService::ensureSettings Fehler: ' . $e->getMessage());
            }
        }
    }

    // =========================================================================
    // BUCHUNGS-EVENTS & NOTIFICATIONS
    // =========================================================================

    /**
     * Versendet Benachrichtigungen nach dem Anlegen einer neuen Buchung:
     * - Bestätigung/Eingangsbestätigung an den Kunden (falls aktiviert & Kunde notify_email=1)
     * - Benachrichtigung an Admin / Schule (falls aktiviert)
     * - Benachrichtigung an den Fahrlehrer (falls aktiviert & zugewiesen)
     *
     * @return array<string, bool> ['customer' => bool, 'admin' => bool, 'instructor' => bool]
     */
    public static function sendBookingCreatedNotifications(int $appointmentId): array
    {
        self::ensureSettings();
        $app = self::loadFullAppointment($appointmentId);
        if (!$app) {
            return ['customer' => false, 'admin' => false, 'instructor' => false];
        }

        $placeholders = self::buildPlaceholders($app);
        $results = ['customer' => false, 'admin' => false, 'instructor' => false];

        // 1. Kunde benachrichtigen
        if (self::isToggleEnabled(self::KEY_NOTIFY_CUSTOMER_CREATED)) {
            $customerEmail = !empty($app['customer_email']) ? trim((string) $app['customer_email']) : '';
            $notifyEmailPref = !isset($app['customer_notify_email']) || (int) $app['customer_notify_email'] === 1;

            if ($customerEmail !== '' && $notifyEmailPref) {
                $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_CREATED_SUBJ), $placeholders);
                $body = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_CREATED_BODY), $placeholders);
                $results['customer'] = self::sendMail($customerEmail, $subj, $body, 'booking_created_customer', [
                    'appointment_id' => $appointmentId,
                    'recipient_type' => 'customer',
                ]);
            } elseif (!$notifyEmailPref) {
                self::logActivity('boating.email_skipped_preference', $customerEmail, [
                    'appointment_id' => $appointmentId,
                    'reason'         => 'Kunde hat notify_email=0 gewählt',
                ]);
            }
        }

        // 2. Admin benachrichtigen
        if (self::isToggleEnabled(self::KEY_NOTIFY_ADMIN_NEW)) {
            $adminEmail = trim(self::getSetting(self::KEY_ADMIN_ADDRESS));
            if ($adminEmail === '') {
                $adminEmail = trim(self::getSetting(self::KEY_FROM_ADDRESS));
            }

            if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_ADMIN_NEW_SUBJ), $placeholders);
                $body = self::renderTemplate(self::getSetting(self::KEY_TPL_ADMIN_NEW_BODY), $placeholders);
                $results['admin'] = self::sendMail($adminEmail, $subj, $body, 'booking_created_admin', [
                    'appointment_id' => $appointmentId,
                    'recipient_type' => 'admin',
                ]);
            }
        }

        // 3. Fahrlehrer benachrichtigen (falls aktiv & E-Mail vorhanden)
        if (self::isToggleEnabled(self::KEY_NOTIFY_INSTRUCTOR_NEW)) {
            $instructorEmail = !empty($app['instructor_email']) ? trim((string) $app['instructor_email']) : '';
            if ($instructorEmail !== '' && filter_var($instructorEmail, FILTER_VALIDATE_EMAIL)) {
                $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_INSTRUCTOR_NEW_SUBJ), $placeholders);
                $body = self::renderTemplate(self::getSetting(self::KEY_TPL_INSTRUCTOR_NEW_BODY), $placeholders);
                $results['instructor'] = self::sendMail($instructorEmail, $subj, $body, 'booking_created_instructor', [
                    'appointment_id' => $appointmentId,
                    'recipient_type' => 'instructor',
                ]);
            }
        }

        return $results;
    }

    /**
     * Versendet Benachrichtigungen nach einer Terminänderung (z.B. per Drag&Drop verschoben oder Status bestätigt):
     *
     * @param array<string, mixed> $changes Zusätzliche Änderungsangaben (z.B. ['previous_date' => ...])
     * @return array<string, bool>
     */
    public static function sendBookingUpdatedNotifications(int $appointmentId, array $changes = []): array
    {
        self::ensureSettings();
        $app = self::loadFullAppointment($appointmentId);
        if (!$app) {
            return ['customer' => false];
        }

        $results = ['customer' => false];

        if (self::isToggleEnabled(self::KEY_NOTIFY_CUSTOMER_UPDATED)) {
            $customerEmail = !empty($app['customer_email']) ? trim((string) $app['customer_email']) : '';
            $notifyEmailPref = !isset($app['customer_notify_email']) || (int) $app['customer_notify_email'] === 1;

            if ($customerEmail !== '' && $notifyEmailPref) {
                // Formatiere Änderungen
                $changesText = '';
                if (!empty($changes['note'])) {
                    $changesText .= (string) $changes['note'] . "\n";
                }
                if (!empty($changes['new_date'])) {
                    $changesText .= "- Neues Datum: " . date('d.m.Y', strtotime((string) $changes['new_date'])) . "\n";
                }
                if (!empty($changes['new_start_time'])) {
                    $changesText .= "- Neue Uhrzeit: " . substr((string) $changes['new_start_time'], 0, 5) . " Uhr\n";
                }

                $placeholders = self::buildPlaceholders($app, [
                    'changes_block' => $changesText !== '' ? "Wichtige Änderungen:\n" . trim($changesText) : '',
                ]);

                $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_UPDATED_SUBJ), $placeholders);
                $body = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_UPDATED_BODY), $placeholders);

                $results['customer'] = self::sendMail($customerEmail, $subj, $body, 'booking_updated_customer', [
                    'appointment_id' => $appointmentId,
                    'recipient_type' => 'customer',
                    'changes'        => $changes,
                ]);
            } elseif (!$notifyEmailPref) {
                self::logActivity('boating.email_skipped_preference', $customerEmail, [
                    'appointment_id' => $appointmentId,
                    'reason'         => 'Kunde hat notify_email=0 gewählt',
                ]);
            }
        }

        return $results;
    }

    /**
     * Versendet Stornierungs-Benachrichtigungen (durch Kunde oder Admin).
     *
     * @param string $cancelledBy 'customer' oder 'admin'
     * @param string|null $reason Optionaler Grund für die Stornierung
     * @return array<string, bool>
     */
    public static function sendBookingCancelledNotifications(
        int $appointmentId,
        string $cancelledBy = 'customer',
        ?string $reason = null
    ): array {
        self::ensureSettings();
        $app = self::loadFullAppointment($appointmentId);
        if (!$app) {
            return ['customer' => false, 'admin' => false];
        }

        $results = ['customer' => false, 'admin' => false];
        $cancelledByLabel = $cancelledBy === 'admin' ? 'Bootsfahrschule (Administrator)' : 'Kunde';
        $reasonBlock = !empty($reason) ? "- Stornierungsgrund: " . trim($reason) : '';

        $placeholders = self::buildPlaceholders($app, [
            'cancelled_by'        => $cancelledByLabel,
            'cancel_reason_block' => $reasonBlock,
        ]);

        // 1. Kunde benachrichtigen
        if (self::isToggleEnabled(self::KEY_NOTIFY_CUSTOMER_CANCELLED)) {
            $customerEmail = !empty($app['customer_email']) ? trim((string) $app['customer_email']) : '';
            $notifyEmailPref = !isset($app['customer_notify_email']) || (int) $app['customer_notify_email'] === 1;

            if ($customerEmail !== '' && $notifyEmailPref) {
                $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_CANCELLED_SUBJ), $placeholders);
                $body = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_CANCELLED_BODY), $placeholders);

                $results['customer'] = self::sendMail($customerEmail, $subj, $body, 'booking_cancelled_customer', [
                    'appointment_id' => $appointmentId,
                    'cancelled_by'   => $cancelledBy,
                    'recipient_type' => 'customer',
                ]);
            }
        }

        // 2. Admin benachrichtigen, wenn Kunde storniert hat
        if ($cancelledBy === 'customer') {
            $adminEmail = trim(self::getSetting(self::KEY_ADMIN_ADDRESS));
            if ($adminEmail === '') {
                $adminEmail = trim(self::getSetting(self::KEY_FROM_ADDRESS));
            }

            if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $adminSubj = "[Stornierung durch Kunde] Termin #{$appointmentId} am " . ($placeholders['{date}'] ?? '');
                $adminBody = "Hallo Admin-Team,\n\n"
                    . "folgender Termin wurde soeben durch den Kunden im Online-Portal storniert:\n\n"
                    . "- Buchungs-ID: #{$appointmentId}\n"
                    . "- Kunde: " . ($placeholders['{customer_name}'] ?? '') . " (" . ($placeholders['{customer_email}'] ?? '') . ")\n"
                    . "- Leistung: " . ($placeholders['{service_name}'] ?? '') . "\n"
                    . "- Datum: " . ($placeholders['{date}'] ?? '') . "\n"
                    . "- Zeitfenster: " . ($placeholders['{start_time}'] ?? '') . " - " . ($placeholders['{end_time}'] ?? '') . " Uhr\n\n"
                    . "Der Termin-Slot steht ab sofort wieder für andere Buchungen zur Verfügung.\n\n"
                    . "Zum Kalender: " . ($placeholders['{admin_calendar_url}'] ?? '');

                $results['admin'] = self::sendMail($adminEmail, $adminSubj, $adminBody, 'booking_cancelled_admin_notification', [
                    'appointment_id' => $appointmentId,
                    'cancelled_by'   => 'customer',
                    'recipient_type' => 'admin',
                ]);
            }
        }

        return $results;
    }

    /**
     * Versendet anstehende Terminerinnerungen (Cronjob-gestützt).
     * Sucht alle bestätigten Termine im Intervall (z.B. nächste 24h),
     * bei denen noch keine Erinnerung versendet wurde (reminder_sent_at IS NULL).
     *
     * @param int|null $hoursAhead Zeitfenster in Stunden (Standard: aus Setting boating_reminder_hours_before)
     * @return array<string, mixed> Statistik des Durchlaufs
     */
    public static function sendUpcomingReminders(?int $hoursAhead = null): array
    {
        self::ensureSettings();

        if (!self::isToggleEnabled(self::KEY_NOTIFY_CUSTOMER_REMINDER)) {
            return [
                'success'       => true,
                'status'        => 'disabled',
                'message'       => 'Terminerinnerungen sind in den Einstellungen deaktiviert.',
                'scanned_count' => 0,
                'sent_count'    => 0,
                'skipped_count' => 0,
                'errors'        => [],
            ];
        }

        $hours = $hoursAhead ?? (int) self::getSetting(self::KEY_REMINDER_HOURS);
        if ($hours <= 0) {
            $hours = 24;
        }

        $dueAppointments = BoatingSchoolRepository::getAppointmentsDueForReminder($hours);

        $sentCount    = 0;
        $skippedCount = 0;
        $errors       = [];

        foreach ($dueAppointments as $app) {
            $appointmentId = (int) $app['id'];
            $customerEmail = !empty($app['customer_email']) ? trim((string) $app['customer_email']) : '';
            $notifyEmailPref = !isset($app['customer_notify_email']) || (int) $app['customer_notify_email'] === 1;

            if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            if (!$notifyEmailPref) {
                $skippedCount++;
                // Trotzdem als erledigt markieren, damit Cron nicht jede Minute erneut prüft
                BoatingSchoolRepository::markAppointmentReminderSent($appointmentId);
                self::logActivity('boating.reminder_skipped_preference', $customerEmail, [
                    'appointment_id' => $appointmentId,
                    'reason'         => 'Kunde hat notify_email=0 gewählt',
                ]);
                continue;
            }

            $placeholders = self::buildPlaceholders($app);
            $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_REMINDER_SUBJ), $placeholders);
            $body = self::renderTemplate(self::getSetting(self::KEY_TPL_CUSTOMER_REMINDER_BODY), $placeholders);

            $ok = self::sendMail($customerEmail, $subj, $body, 'booking_reminder_sent', [
                'appointment_id' => $appointmentId,
                'hours_ahead'    => $hours,
                'recipient_type' => 'customer',
            ]);

            if ($ok) {
                BoatingSchoolRepository::markAppointmentReminderSent($appointmentId);
                $sentCount++;
            } else {
                $errors[] = "Fehler beim Versenden der Erinnerung für Termin #{$appointmentId} an {$customerEmail}";
            }
        }

        self::logActivity('boating.reminders_cron_finished', null, [
            'scanned_count' => count($dueAppointments),
            'sent_count'    => $sentCount,
            'skipped_count' => $skippedCount,
            'hours'         => $hours,
        ]);

        return [
            'success'       => empty($errors),
            'status'        => 'completed',
            'message'       => "Erinnerungslauf abgeschlossen: {$sentCount} Erinnerung(en) versendet, {$skippedCount} übersprungen.",
            'scanned_count' => count($dueAppointments),
            'sent_count'    => $sentCount,
            'skipped_count' => $skippedCount,
            'hours_checked' => $hours,
            'errors'        => $errors,
        ];
    }

    /**
     * Versendet eine E-Mail an die Buchhaltung / Admin, wenn ein Coach sein Timesheet einreicht.
     *
     * @param array<string, mixed> $summary
     */
    public static function sendTimesheetSubmittedNotification(int $instructorId, string $yearMonth, array $summary): bool
    {
        self::ensureSettings();

        $instructor = BoatingSchoolRepository::getInstructorById($instructorId);
        if (!$instructor) {
            return false;
        }

        // Ziel-Adresse: Aus Payroll Settings oder Admin-Adresse
        $payrollEmail = '';
        if (class_exists('CoachService')) {
            $pSettings = CoachService::getPayrollSettings();
            $payrollEmail = trim((string) ($pSettings['payroll_notification_email'] ?? ''));
        }
        if ($payrollEmail === '' || !filter_var($payrollEmail, FILTER_VALIDATE_EMAIL)) {
            $payrollEmail = self::getSetting(self::KEY_ADMIN_ADDRESS);
        }
        if ($payrollEmail === '' || !filter_var($payrollEmail, FILTER_VALIDATE_EMAIL)) {
            $payrollEmail = 'payroll@bootschule.ch';
        }

        $coachName = trim($instructor['first_name'] . ' ' . $instructor['last_name']);
        $totalHours = number_format((float) ($summary['total_hours'] ?? 0.0), 2, '.', "'");
        $grossAmount = number_format((float) ($summary['total_amount'] ?? $summary['gross_amount'] ?? 0.0), 2, '.', "'");

        $adminUrl = '/admin/boating/payroll?month=' . urlencode($yearMonth) . '&instructor_id=' . $instructorId;
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $adminUrl = $proto . $_SERVER['HTTP_HOST'] . $adminUrl;
        }

        $placeholders = [
            '{coach_name}'          => $coachName,
            '{instructor_id}'       => (string) $instructorId,
            '{month}'               => $yearMonth,
            '{total_hours}'         => $totalHours,
            '{gross_amount}'        => $grossAmount,
            '{submitted_at}'        => date('d.m.Y H:i'),
            '{admin_timesheet_url}' => $adminUrl,
            '{school_name}'         => self::getSetting(self::KEY_FROM_NAME),
        ];

        $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_SUBMITTED_SUBJ), $placeholders);
        $body = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_SUBMITTED_BODY), $placeholders);

        return self::sendMail($payrollEmail, $subj, $body, 'coach_timesheet_submitted', [
            'instructor_id' => $instructorId,
            'year_month'    => $yearMonth,
            'recipient'     => 'payroll_admin',
        ]);
    }

    /**
     * Versendet eine E-Mail an den Coach, wenn sein Monatsrapport genehmigt wurde.
     *
     * @param array<string, mixed> $summary
     */
    public static function sendTimesheetApprovedNotification(int $instructorId, string $yearMonth, array $summary): bool
    {
        self::ensureSettings();

        $instructor = BoatingSchoolRepository::getInstructorById($instructorId);
        if (!$instructor) {
            return false;
        }

        // Coach E-Mail ermitteln
        $coachEmail = !empty($instructor['email']) ? trim((string) $instructor['email']) : '';
        if (class_exists('CoachService') && ($coachEmail === '' || !filter_var($coachEmail, FILTER_VALIDATE_EMAIL))) {
            $hr = CoachService::getHrDetails($instructorId);
            if (!empty($hr['email'])) {
                $coachEmail = trim((string) $hr['email']);
            }
        }

        if ($coachEmail === '' || !filter_var($coachEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("BoatingNotificationService: Keine gültige E-Mail für Coach #{$instructorId} vorhanden.");
            return false;
        }

        $coachName = trim($instructor['first_name'] . ' ' . $instructor['last_name']);
        $totalHours = number_format((float) ($summary['total_hours'] ?? 0.0), 2, '.', "'");
        $grossAmount = number_format((float) ($summary['gross_amount'] ?? $summary['total_amount'] ?? 0.0), 2, '.', "'");
        $netAmount = number_format((float) ($summary['net_amount'] ?? $summary['gross_amount'] ?? 0.0), 2, '.', "'");
        $deductionsTotal = number_format((float) ($summary['deductions_total'] ?? 0.0), 2, '.', "'");

        $placeholders = [
            '{coach_name}'        => $coachName,
            '{instructor_id}'     => (string) $instructorId,
            '{month}'             => $yearMonth,
            '{total_hours}'       => $totalHours,
            '{gross_amount}'      => $grossAmount,
            '{deductions_total}'  => $deductionsTotal,
            '{net_amount}'        => $netAmount,
            '{school_name}'       => self::getSetting(self::KEY_FROM_NAME),
        ];

        $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_APPROVED_SUBJ), $placeholders);
        $body = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_APPROVED_BODY), $placeholders);

        return self::sendMail($coachEmail, $subj, $body, 'coach_timesheet_approved', [
            'instructor_id' => $instructorId,
            'year_month'    => $yearMonth,
            'recipient'     => 'coach',
        ]);
    }

    /**
     * Versendet eine E-Mail an den Coach, wenn sein Monatsrapport zurückgewiesen wurde.
     *
     * @param array<string, mixed> $summary
     */
    public static function sendTimesheetRejectedNotification(
        int $instructorId,
        string $yearMonth,
        string $rejectionReason,
        array $summary
    ): bool {
        self::ensureSettings();

        $instructor = BoatingSchoolRepository::getInstructorById($instructorId);
        if (!$instructor) {
            return false;
        }

        $coachEmail = !empty($instructor['email']) ? trim((string) $instructor['email']) : '';
        if (class_exists('CoachService') && ($coachEmail === '' || !filter_var($coachEmail, FILTER_VALIDATE_EMAIL))) {
            $hr = CoachService::getHrDetails($instructorId);
            if (!empty($hr['email'])) {
                $coachEmail = trim((string) $hr['email']);
            }
        }

        if ($coachEmail === '' || !filter_var($coachEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("BoatingNotificationService: Keine gültige E-Mail für Coach #{$instructorId} vorhanden.");
            return false;
        }

        $coachName = trim($instructor['first_name'] . ' ' . $instructor['last_name']);
        $portalUrl = '/admin/boating/instructor-portal?month=' . urlencode($yearMonth);
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $portalUrl = $proto . $_SERVER['HTTP_HOST'] . $portalUrl;
        }

        $placeholders = [
            '{coach_name}'       => $coachName,
            '{instructor_id}'    => (string) $instructorId,
            '{month}'            => $yearMonth,
            '{rejection_reason}' => $rejectionReason,
            '{portal_url}'       => $portalUrl,
            '{school_name}'      => self::getSetting(self::KEY_FROM_NAME),
        ];

        $subj = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_REJECTED_SUBJ), $placeholders);
        $body = self::renderTemplate(self::getSetting(self::KEY_TPL_COACH_TIMESHEET_REJECTED_BODY), $placeholders);

        return self::sendMail($coachEmail, $subj, $body, 'coach_timesheet_rejected', [
            'instructor_id'    => $instructorId,
            'year_month'       => $yearMonth,
            'rejection_reason' => $rejectionReason,
            'recipient'        => 'coach',
        ]);
    }

    /**
     * Versendet eine Test-E-Mail mit Musterdaten für eine bestimmte Vorlage.
     *
     * @return array<string, mixed>
     */
    public static function sendTestEmail(string $recipientEmail, string $templateKey): array
    {
        self::ensureSettings();

        $recipientEmail = trim($recipientEmail);
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse für den Testversand an.',
            ];
        }

        // Dummy-Platzhalter für die Vorschau & Test
        $dummyApp = [
            'id'                     => 9999,
            'date'                   => date('Y-m-d', strtotime('+2 days')),
            'start_time'             => '10:00:00',
            'end_time'               => '11:30:00',
            'price'                  => 190.00,
            'status'                 => 'confirmed',
            'notes'                  => 'Test-Buchung: Schüler bringt eigene Schwimmweste mit.',
            'service_name'           => 'Motorboot Fahrunterricht Kat. A (Prüfungsvorbereitung)',
            'service_category'       => 'motor',
            'service_duration'       => 90,
            'customer_first_name'    => 'Max',
            'customer_last_name'     => 'Muster',
            'customer_email'         => $recipientEmail,
            'customer_phone'         => '+41 79 123 45 67',
            'customer_notify_email'  => 1,
            'instructor_first_name'  => 'Captain',
            'instructor_last_name'   => 'Meier',
            'instructor_email'       => 'meier@bootschule.ch',
            'instructor_phone'       => '+41 78 987 65 43',
            'boat_name'              => 'Nautique 210 Master',
            'boat_type'              => 'Motorboot',
            'location_name'          => 'Luzern - Hafenbecken Alpenquai',
            'location_address'       => 'Alpenquai 12, 6005 Luzern',
        ];

        $placeholders = self::buildPlaceholders($dummyApp, [
            'cancelled_by'        => 'Bootsfahrschule',
            'cancel_reason_block' => '- Grund: Schlechtwetter-Warnung auf dem See (Sturmwarnung aktiv)',
            'changes_block'       => "- Neues Datum: " . date('d.m.Y', strtotime('+3 days')) . "\n- Neue Startzeit: 14:00 Uhr",
        ]);

        $defaults = self::getDefaults();
        $bodyKey = $templateKey;
        $subjKey = str_replace('_body', '_subject', $templateKey);

        if (!isset($defaults[$bodyKey])) {
            $bodyKey = self::KEY_TPL_CUSTOMER_CREATED_BODY;
            $subjKey = self::KEY_TPL_CUSTOMER_CREATED_SUBJ;
        }

        $subjectRaw = self::getSetting($subjKey);
        $bodyRaw    = self::getSetting($bodyKey);

        $renderedSubject = '[TEST] ' . self::renderTemplate($subjectRaw, $placeholders);
        $renderedBody    = self::renderTemplate($bodyRaw, $placeholders)
            . "\n\n------------------------------------------------------------\n"
            . "HINWEIS: Dies ist eine Test-E-Mail aus dem Admin-Bereich des Bootsfahrschul-Moduls.";

        $sent = self::sendMail($recipientEmail, $renderedSubject, $renderedBody, 'test_email', [
            'template_key' => $templateKey,
        ]);

        if ($sent) {
            return [
                'success' => true,
                'message' => "Test-E-Mail wurde erfolgreich an {$recipientEmail} versendet.",
                'subject' => $renderedSubject,
                'body'    => $renderedBody,
            ];
        }

        return [
            'success' => false,
            'message' => "Fehler beim Versenden der Test-E-Mail an {$recipientEmail}. Bitte Server-Logs oder Mail-Konfiguration prüfen.",
            'subject' => $renderedSubject,
            'body'    => $renderedBody,
        ];
    }

    // =========================================================================
    // HELPER: PLATZHALTER & TEMPLATE-ENGINE
    // =========================================================================

    /**
     * Ersetzt alle bekannten {platzhalter} im übergebenen Text.
     *
     * @param array<string, string> $placeholders
     */
    public static function renderTemplate(string $text, array $placeholders): string
    {
        $expanded = [];
        foreach ($placeholders as $k => $v) {
            $expanded[$k] = $v;
            if (preg_match('/^\{([a-zA-Z0-9_-]+)\}$/', $k, $m)) {
                $expanded['{{' . $m[1] . '}}'] = $v;
            } elseif (preg_match('/^\{\{([a-zA-Z0-9_-]+)\}\}$/', $k, $m)) {
                $expanded['{' . $m[1] . '}'] = $v;
            }
        }
        return str_replace(array_keys($expanded), array_values($expanded), $text);
    }

    /**
     * Erstellt das vollständige Platzhalter-Array für eine Buchung.
     *
     * @param array<string, mixed> $app
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    public static function buildPlaceholders(array $app, array $extra = []): array
    {
        $dateFormatted = !empty($app['date']) ? date('d.m.Y', strtotime((string) $app['date'])) : '';
        $startTime = !empty($app['start_time']) ? substr((string) $app['start_time'], 0, 5) : '';
        $endTime   = !empty($app['end_time']) ? substr((string) $app['end_time'], 0, 5) : '';

        $custFirst = trim((string) ($app['customer_first_name'] ?? ''));
        $custLast  = trim((string) ($app['customer_last_name'] ?? ''));
        $custName  = trim($custFirst . ' ' . $custLast);
        if ($custName === '') {
            $custName = 'Kunde';
            $custFirst = 'Kunde';
        }

        $instFirst = trim((string) ($app['instructor_first_name'] ?? ''));
        $instLast  = trim((string) ($app['instructor_last_name'] ?? ''));
        $instName  = trim($instFirst . ' ' . $instLast);
        if ($instName === '') {
            $instName = 'Wird zugewiesen';
            $instFirst = 'Fahrlehrer';
        }

        $boatName = !empty($app['boat_name']) ? trim((string) $app['boat_name']) : 'Wird bereitgestellt';
        $locationName = !empty($app['location_name']) ? trim((string) $app['location_name']) : 'Standard-Steg';
        $locationAddress = !empty($app['location_address']) ? trim((string) $app['location_address']) : '';
        $serviceName = !empty($app['service_name']) ? trim((string) $app['service_name']) : 'Bootsausbildung';

        $priceVal = isset($app['price']) ? number_format((float) $app['price'], 2, '.', "'") : '0.00';
        $statusKey = (string) ($app['status'] ?? 'requested');
        $statusLabels = [
            'requested'          => 'Angefragt (Wartet auf Bestätigung)',
            'confirmed'          => 'Bestätigt',
            'cancelled_customer' => 'Storniert durch Kunde',
            'cancelled_system'   => 'Storniert / Abgesagt durch Schule',
            'completed'          => 'Abgeschlossen',
        ];
        $statusLabel = $statusLabels[$statusKey] ?? ucfirst($statusKey);

        $notesRaw = !empty($app['notes']) ? trim((string) $app['notes']) : '';
        $notesBlock = $notesRaw !== '' ? "Besondere Notizen / Wünsche:\n{$notesRaw}" : '';

        // URLs zusammensetzen
        $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = "{$scheme}://{$host}";

        // Schüler-Magic-Link für «Meine Termine» (7 Tage gültig)
        $customerId = !empty($app['customer_id']) ? (int) $app['customer_id'] : 0;
        $magicLink = '';
        if ($customerId > 0) {
            if (class_exists('BoatingCustomerMagicLinkService')) {
                try {
                    $magicLink = BoatingCustomerMagicLinkService::createLinkForCustomer($customerId, 'appointments_view');
                } catch (\Throwable $e) {
                    error_log('BoatingNotificationService: Fehler beim Erstellen des Magic-Links: ' . $e->getMessage());
                }
            } elseif (class_exists('BoatingMagicLinkService')) {
                try {
                    $magicLink = BoatingMagicLinkService::generateLinkForCustomer($customerId);
                } catch (\Throwable $e) {
                    error_log('BoatingNotificationService: Fehler beim Erstellen des Magic-Links: ' . $e->getMessage());
                }
            }
        }
        if ($magicLink === '') {
            $magicLink = "{$baseUrl}/bootschule/me/link";
            if (!empty($app['customer_email'])) {
                $magicLink .= '?email=' . urlencode((string) $app['customer_email']);
            }
        }

        $portalUrl = $magicLink;
        $adminCalendarUrl = "{$baseUrl}/?route=admin/boating/calendar&date=" . urlencode((string) ($app['date'] ?? ''));

        $schoolName  = self::getSetting(self::KEY_FROM_NAME);
        $schoolEmail = self::getSetting(self::KEY_FROM_ADDRESS);

        $map = [
            '{appointment_id}'            => (string) ($app['id'] ?? ''),
            '{customer_name}'             => $custName,
            '{customer_first_name}'       => $custFirst,
            '{customer_last_name}'        => $custLast,
            '{customer_email}'            => (string) ($app['customer_email'] ?? ''),
            '{customer_phone}'            => (string) ($app['customer_phone'] ?? ''),
            '{service_name}'              => $serviceName,
            '{service_title}'             => $serviceName,
            '{service_category}'          => (string) ($app['service_category'] ?? ''),
            '{date}'                      => $dateFormatted,
            '{date_raw}'                  => (string) ($app['date'] ?? ''),
            '{start_time}'                => $startTime,
            '{end_time}'                  => $endTime,
            '{instructor_name}'           => $instName,
            '{instructor_first_name}'     => $instFirst,
            '{instructor_email}'          => (string) ($app['instructor_email'] ?? ''),
            '{instructor_phone}'          => (string) ($app['instructor_phone'] ?? ''),
            '{boat_name}'                 => $boatName,
            '{boat_type}'                 => (string) ($app['boat_type'] ?? ''),
            '{location_name}'             => $locationName,
            '{location_address}'          => $locationAddress,
            '{price}'                     => $priceVal,
            '{status}'                    => $statusKey,
            '{status_label}'              => $statusLabel,
            '{notes}'                     => $notesRaw,
            '{notes_block}'               => $notesBlock,
            '{school_name}'               => $schoolName,
            '{school_email}'              => $schoolEmail,
            '{portal_url}'                => $portalUrl,
            '{magic_link}'                => $magicLink,
            '{magic_link_my_appointments}'=> $magicLink,
            '{my_appointments_url}'       => $magicLink,
            '{{my_appointments_url}}'     => $magicLink,
            '{admin_calendar_url}'        => $adminCalendarUrl,
            '{cancelled_by}'              => 'Kunde',
            '{cancel_reason_block}'       => '',
            '{changes_block}'             => '',
        ];

        return array_merge($map, $extra);
    }

    // =========================================================================
    // VERSAND & ACTIVITY-LOGGING
    // =========================================================================

    /**
     * Versendet eine E-Mail unter Verwendung der konfigurierten Absender-, Reply-To-
     * und optionalen BCC-Adressen und dokumentiert das Ergebnis im ActivityLog.
     *
     * @param array<string, mixed> $logContext
     */
    public static function sendMail(
        string $to,
        string $subject,
        string $body,
        string $actionName = 'mail_send',
        array $logContext = []
    ): bool {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::logActivity("boating.{$actionName}_invalid_recipient", $to, $logContext);
            return false;
        }

        $fromAddress = self::getSetting(self::KEY_FROM_ADDRESS);
        $fromName    = self::getSetting(self::KEY_FROM_NAME);
        $replyTo     = self::getSetting(self::KEY_REPLY_TO);
        $bccAdmin    = self::getSetting(self::KEY_BCC_ADMIN);

        $sent = false;

        try {
            if (class_exists('Mailer')) {
                $sent = Mailer::send(
                    $to,
                    $subject,
                    $body,
                    false,
                    $fromAddress !== '' ? $fromAddress : null,
                    $fromName !== '' ? $fromName : null,
                    $replyTo !== '' ? $replyTo : null,
                    $bccAdmin !== '' ? $bccAdmin : null
                );
            } else {
                // Fallback nativer PHP mail()
                $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                $fromHeader = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromAddress);
                $headers = [
                    'From: ' . $fromHeader,
                    'MIME-Version: 1.0',
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                    'X-Mailer: PHP/' . phpversion(),
                ];
                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $headers[] = 'Reply-To: ' . $replyTo;
                }
                if ($bccAdmin !== '' && filter_var($bccAdmin, FILTER_VALIDATE_EMAIL)) {
                    $headers[] = 'Bcc: ' . $bccAdmin;
                }
                $headersStr = implode("\r\n", $headers) . "\r\n";
                $extraParam = '-f' . escapeshellarg($fromAddress);
                $sent = (bool) @mail($to, $encodedSubject, $body, $headersStr, $extraParam);
            }
        } catch (\Throwable $e) {
            error_log("BoatingNotificationService::sendMail Fehler: " . $e->getMessage());
            $sent = false;
        }

        // Activity Logging
        $eventKey = $sent ? "boating.{$actionName}_success" : "boating.{$actionName}_failed";
        $logPayload = array_merge($logContext, [
            'to'      => $to,
            'subject' => $subject,
            'sent'    => $sent,
            'from'    => $fromAddress,
        ]);
        self::logActivity($eventKey, $to, $logPayload);

        return $sent;
    }

    /**
     * Schreibt einen Eintrag in das System-ActivityLog.
     *
     * @param array<string, mixed> $details
     */
    private static function logActivity(string $eventType, ?string $email = null, array $details = []): void
    {
        if (class_exists('ActivityLog')) {
            try {
                ActivityLog::log($eventType, $email, $details);
            } catch (\Throwable $e) {
                error_log("BoatingNotificationService::logActivity Fehler: " . $e->getMessage());
            }
        }
    }

    /**
     * Lädt einen Termin mit allen verknüpften Details (Kunde, Fahrlehrer, Boot, Service, Ort).
     *
     * @return array<string, mixed>|null
     */
    private static function loadFullAppointment(int $appointmentId): ?array
    {
        if (class_exists('AppointmentService')) {
            return AppointmentService::find($appointmentId);
        }
        if (class_exists('BoatingSchoolRepository')) {
            return BoatingSchoolRepository::getAppointmentById($appointmentId);
        }
        return null;
    }
}
