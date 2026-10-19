<?php

declare(strict_types=1);

/**
 * BoatingCustomerMagicLinkService
 *
 * Verwaltet den tokenbasierten, passwortlosen Schüler-Zugang („Meine Termine“)
 * für die Bootsfahrschule:
 * - Generierung kryptografisch sicherer Tokens (SHA-256 Hash in DB, niemals Klartext)
 * - Gültigkeit standardmäßig 7 Tage (konfigurierbar via 'boating_magic_link_valid_days')
 * - Vollständige Ziel-URL: {BASE_URL}/bootschule/me/link?token=...
 * - Validierung und Aktualisierung von last_used_at
 * - Widerruf aller Links für einen Kunden (revokeAllForCustomer)
 * - Rate-Limiting für Neuanforderungen (max 5 pro IP / Stunde via RateLimiter)
 * - Stornierungs-Workflow mit Fristprüfung ('boating_cancel_min_hours_before', Default 24)
 * - Strikte Session-Trennung (boating_customer_id ohne Admin-Rechte)
 */
class BoatingCustomerMagicLinkService
{
    public const PURPOSE_APPOINTMENTS_VIEW = 'appointments_view';
    public const DEFAULT_VALID_DAYS = 7;
    public const DEFAULT_CANCEL_MIN_HOURS = 24;
    public const RATE_LIMIT_MAX_REQUESTS = 5;
    public const RATE_LIMIT_DECAY_SECONDS = 3600;

    private static bool $tableEnsured = false;

    /**
     * Stellt sicher, dass die Tabelle bs_customer_magic_links existiert (idempotent)
     * und Spalten wie last_used_at vorhanden sind.
     */
    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        try {
            DB::execute("
                CREATE TABLE IF NOT EXISTS `bs_customer_magic_links` (
                    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `customer_id`  INT UNSIGNED NOT NULL,
                    `token_hash`   CHAR(64) NOT NULL,
                    `purpose`      VARCHAR(32) NOT NULL DEFAULT 'appointments_view',
                    `expires_at`   DATETIME NOT NULL,
                    `last_used_at` DATETIME NULL,
                    `created_at`   DATETIME NOT NULL,
                    `created_ip`   VARCHAR(45) NULL,
                    CONSTRAINT `fk_bs_magic_customer` FOREIGN KEY (`customer_id`)
                        REFERENCES `bs_customers` (`id`) ON DELETE CASCADE,
                    INDEX `idx_bs_magic_token` (`token_hash`),
                    INDEX `idx_bs_magic_customer` (`customer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Migration: Falls noch die Spalte used_at vorhanden ist, last_used_at hinzufügen
            $cols = DB::fetchAll("SHOW COLUMNS FROM `bs_customer_magic_links`");
            $colNames = array_map(static fn($c): string => strtolower((string) ($c['Field'] ?? '')), $cols);

            if (!in_array('last_used_at', $colNames, true)) {
                DB::execute("ALTER TABLE `bs_customer_magic_links` ADD COLUMN `last_used_at` DATETIME NULL AFTER `expires_at`");
                if (in_array('used_at', $colNames, true)) {
                    DB::execute("UPDATE `bs_customer_magic_links` SET `last_used_at` = `used_at` WHERE `last_used_at` IS NULL");
                }
            }
        } catch (\Throwable $e) {
            error_log('BoatingCustomerMagicLinkService::ensureTable Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt einen neuen Magic-Link für den Kunden und speichert den SHA-256-Hash in der DB.
     * Gültigkeitsdauer aus Setting 'boating_magic_link_valid_days' (Default 7).
     *
     * @param int $customerId
     * @param string $purpose Verwendungszweck (Standard: 'appointments_view')
     * @return string Vollständige URL: {BASE_URL}/bootschule/me/link?token=...
     */
    public static function createLinkForCustomer(
        int $customerId,
        string $purpose = self::PURPOSE_APPOINTMENTS_VIEW
    ): string {
        self::ensureTable();

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Ungültige customer_id für Magic-Link.');
        }

        // 1. Zufälliges kryptografisches Token erzeugen
        $plainToken = bin2hex(random_bytes(32)); // 64 Hex-Zeichen
        $tokenHash  = hash('sha256', $plainToken);

        // 2. Gültigkeit aus Konfiguration laden (Standard: 7 Tage)
        $validDays = self::DEFAULT_VALID_DAYS;
        if (class_exists('Settings')) {
            $settingVal = Settings::get('boating_magic_link_valid_days');
            if ($settingVal !== null && is_numeric($settingVal)) {
                $validDays = max(1, (int) $settingVal);
            }
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$validDays} days"));
        $clientIp  = !empty($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : null;

        // 3. Nur den Hash in der Datenbank persistieren
        $sql = 'INSERT INTO `bs_customer_magic_links`
                (`customer_id`, `token_hash`, `purpose`, `expires_at`, `last_used_at`, `created_at`, `created_ip`)
                VALUES
                (:customer_id, :token_hash, :purpose, :expires_at, NULL, NOW(), :created_ip)';

        DB::execute($sql, [
            'customer_id' => $customerId,
            'token_hash'  => $tokenHash,
            'purpose'     => $purpose,
            'expires_at'  => $expiresAt,
            'created_ip'  => $clientIp,
        ]);

        // 4. Audit-Logging (Niemals das Klartext-Token loggen!)
        if (class_exists('ActivityLog')) {
            ActivityLog::log('boating.magic_link_created', null, [
                'customer_id' => $customerId,
                'purpose'     => $purpose,
                'expires_at'  => $expiresAt,
            ]);
        }

        // 5. Vollständige URL zurückgeben
        return self::buildMagicUrl($plainToken);
    }

    /**
     * Erstellt die vollständige Ziel-URL mit dem Klartext-Token.
     *
     * @param string $plainToken
     * @return string {BASE_URL}/bootschule/me/link?token=...
     */
    public static function buildMagicUrl(string $plainToken): string
    {
        $baseUrl = self::getBaseUrl();
        return "{$baseUrl}/bootschule/me/link?token=" . urlencode($plainToken);
    }

    /**
     * Validiert ein übergebenes Klartext-Token.
     * Vergleicht den Hash, prüft expires_at > NOW() und liefert Kunde und Link-Datensatz oder null.
     * Aktualisiert last_used_at auf den aktuellen Zeitpunkt.
     *
     * @param string $token
     * @return array<string, mixed>|null ['customer' => array, 'magic_link' => array] oder null
     */
    public static function validateToken(string $token): ?array
    {
        self::ensureTable();

        $cleanToken = trim($token);
        if ($cleanToken === '' || strlen($cleanToken) < 16) {
            return null;
        }

        $tokenHash = hash('sha256', $cleanToken);

        try {
            $sql = 'SELECT * FROM `bs_customer_magic_links`
                    WHERE `token_hash` = :hash
                      AND `expires_at` > NOW()
                    ORDER BY `id` DESC
                    LIMIT 1';

            $row = DB::fetchOne($sql, ['hash' => $tokenHash]);
            if (!$row) {
                return null;
            }

            // Sicherer Timing-Angriffs-resistenter Vergleich
            if (!hash_equals((string) $row['token_hash'], $tokenHash)) {
                return null;
            }

            $customerId = (int) $row['customer_id'];
            $customer = BoatingSchoolRepository::getCustomerById($customerId);
            if (!$customer) {
                return null;
            }

            // Prüfen, ob Kunde aktiv ist (nicht deaktiviert)
            if (isset($customer['is_active']) && (int) $customer['is_active'] !== 1) {
                return null;
            }

            // last_used_at (und used_at falls Spalte existiert) aktualisieren
            DB::execute(
                'UPDATE `bs_customer_magic_links` SET `last_used_at` = NOW() WHERE `id` = :id',
                ['id' => (int) $row['id']]
            );

            if (class_exists('ActivityLog')) {
                ActivityLog::log('boating.magic_link_validated', null, [
                    'customer_id'   => $customerId,
                    'magic_link_id' => (int) $row['id'],
                ]);
            }

            return [
                'magic_link' => $row,
                'customer'   => $customer,
            ];
        } catch (\Throwable $e) {
            error_log('BoatingCustomerMagicLinkService::validateToken Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Widerruft alle Magic-Links eines Kunden.
     * Wird bei Deaktivierung oder Anonymisierung des Kunden aufgerufen.
     *
     * @param int $customerId
     */
    public static function revokeAllForCustomer(int $customerId): void
    {
        self::ensureTable();

        if ($customerId <= 0) {
            return;
        }

        try {
            DB::execute(
                'DELETE FROM `bs_customer_magic_links` WHERE `customer_id` = :cid',
                ['cid' => $customerId]
            );

            if (class_exists('ActivityLog')) {
                ActivityLog::log('boating.magic_links_revoked', null, [
                    'customer_id' => $customerId,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('BoatingCustomerMagicLinkService::revokeAllForCustomer Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Nimmt eine E-Mail-Adresse entgegen, prüft das Rate-Limit, sucht einen aktiven
     * Kunden und versendet einen neuen Zugangslink per E-Mail.
     * Die Antwort ist neutral, um Information Disclosure / Email-Enumeration zu verhindern.
     *
     * @param string $email
     * @param string|null $ip
     * @return array<string, mixed> ['success' => bool, 'message' => string, 'rate_limited' => bool]
     */
    public static function requestLinkByEmail(string $email, ?string $ip = null): array
    {
        $neutralMessage = 'Falls die Adresse bekannt ist, haben wir einen neuen Link gesendet.';

        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $rateKey = 'magic_req_' . substr($clientIp, 0, 45);

        // Rate-Limit prüfen (max 5 Anforderungen pro IP und Stunde)
        if (class_exists('RateLimiter')) {
            if (!RateLimiter::isAllowed($rateKey, 'magic_link_request', self::RATE_LIMIT_MAX_REQUESTS, self::RATE_LIMIT_DECAY_SECONDS)) {
                if (class_exists('ActivityLog')) {
                    ActivityLog::log('boating.magic_link_rate_limited', null, ['ip' => $clientIp]);
                }
                return [
                    'success'      => true,
                    'message'      => $neutralMessage,
                    'rate_limited' => true,
                ];
            }
            RateLimiter::hit($rateKey, 'magic_link_request', self::RATE_LIMIT_DECAY_SECONDS);
        }

        $cleanEmail = trim(strtolower($email));
        if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success'      => true,
                'message'      => $neutralMessage,
                'rate_limited' => false,
            ];
        }

        // Aktiven Kunden suchen
        $customers = BoatingSchoolRepository::getCustomers(['email' => $cleanEmail, 'is_active' => 1], 1, 0);
        if (empty($customers)) {
            // Unabhängig vom Ergebnis neutral antworten
            return [
                'success'      => true,
                'message'      => $neutralMessage,
                'rate_limited' => false,
            ];
        }

        $customer = $customers[0];
        $customerId = (int) $customer['id'];

        // Neuen Link generieren (Gültigkeit 7 Tage)
        $link = self::createLinkForCustomer($customerId, self::PURPOSE_APPOINTMENTS_VIEW);

        // E-Mail versenden
        $firstName = !empty($customer['first_name']) ? (string) $customer['first_name'] : 'Schüler';
        $schoolName = class_exists('BoatingNotificationService')
            ? BoatingNotificationService::getSetting(BoatingNotificationService::KEY_FROM_NAME)
            : 'Bootsfahrschule';

        $subject = 'Dein persönlicher Zugangslink – Meine Termine';
        $body = "Hallo {$firstName},\n\n"
              . "du hast einen Zugangslink für deine Terminübersicht angefordert.\n\n"
              . "Klicke einfach auf folgenden Link, um deine bevorstehenden und vergangenen Fahrstunden einzusehen:\n\n"
              . "{$link}\n\n"
              . "Dieser Link ist für 7 Tage gültig und benötigt kein Passwort.\n\n"
              . "Freundliche Grüsse\n"
              . "{$schoolName}";

        if (class_exists('BoatingNotificationService')) {
            BoatingNotificationService::sendMail(
                $cleanEmail,
                $subject,
                $body,
                'magic_link_requested',
                ['customer_id' => $customerId]
            );
        } elseif (class_exists('Mailer')) {
            try {
                Mailer::send($cleanEmail, $subject, $body);
            } catch (\Throwable $e) {
                error_log('BoatingCustomerMagicLinkService Mail-Fehler: ' . $e->getMessage());
            }
        }

        return [
            'success'      => true,
            'message'      => $neutralMessage,
            'rate_limited' => false,
        ];
    }

    /**
     * Lädt die Termine eines Schülers:
     * - Bevorstehend: ab heute, nach Startzeit aufsteigend sortiert.
     * - Vergangen: die letzten 12 Monate, absteigend sortiert.
     *
     * @param int $customerId
     * @return array<string, array<int, array<string, mixed>>> ['upcoming' => [...], 'past' => [...], 'customer' => [...]]
     */
    public static function getCustomerAppointments(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['upcoming' => [], 'past' => []];
        }

        $today = date('Y-m-d');
        $nowTime = date('H:i:s');
        $twelveMonthsAgo = date('Y-m-d', strtotime('-12 months'));

        // Alle Termine des Kunden laden (WHERE customer_id = :sessionCustomerId)
        $all = BoatingSchoolRepository::getAppointments(['customer_id' => $customerId], 1000, 0);

        $upcoming = [];
        $past = [];

        foreach ($all as $app) {
            $appDate = (string) ($app['date'] ?? '');
            $endTime = (string) ($app['end_time'] ?? '23:59:59');

            if ($appDate > $today || ($appDate === $today && $endTime >= $nowTime)) {
                $upcoming[] = $app;
            } elseif ($appDate >= $twelveMonthsAgo) {
                $past[] = $app;
            }
        }

        // Bevorstehend: Nach Datum und Startzeit aufsteigend sortieren
        usort($upcoming, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['start_time'] ?? ''), (string) ($b['start_time'] ?? ''));
        });

        // Vergangen: Die letzten 12 Monate, absteigend sortiert
        usort($past, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($b['start_time'] ?? ''), (string) ($a['start_time'] ?? ''));
        });

        return [
            'upcoming' => $upcoming,
            'past'     => $past,
        ];
    }

    /**
     * Storniert einen Termin durch den Schüler.
     * Prüft, ob:
     * - der Termin zur Session-Kundin / zum Session-Kunden gehört,
     * - der Termin in der Zukunft liegt,
     * - der Status 'requested' oder 'confirmed' ist,
     * - die Storno-Frist eingehalten wird (Setting 'boating_cancel_min_hours_before', Default 24).
     *
     * @param int $appointmentId
     * @param int $customerId
     * @param string|null $reason
     * @return array<string, mixed> ['success' => bool, 'message' => string]
     */
    public static function cancelAppointmentByStudent(
        int $appointmentId,
        int $customerId,
        ?string $reason = null
    ): array {
        if ($appointmentId <= 0 || $customerId <= 0) {
            return ['success' => false, 'message' => 'Ungültige Termindaten.'];
        }

        $app = BoatingSchoolRepository::getAppointmentById($appointmentId);
        if (!$app) {
            return ['success' => false, 'message' => 'Termin wurde nicht gefunden.'];
        }

        // 1. Eigentümerschaft prüfen
        if ((int) $app['customer_id'] !== $customerId) {
            return ['success' => false, 'message' => 'Sie sind nicht berechtigt, diesen Termin zu verwalten.'];
        }

        // 2. Status prüfen (nur 'requested' oder 'confirmed' erlaubt)
        $currentStatus = (string) ($app['status'] ?? '');
        if (!in_array($currentStatus, ['requested', 'confirmed'], true)) {
            if (in_array($currentStatus, ['cancelled_customer', 'cancelled_system', 'cancelled'], true)) {
                return ['success' => false, 'message' => 'Dieser Termin wurde bereits storniert.'];
            }
            if ($currentStatus === 'completed') {
                return ['success' => false, 'message' => 'Bereits absolvierte Fahrten können nicht storniert werden.'];
            }
            return ['success' => false, 'message' => 'Dieser Termin kann im aktuellen Status nicht storniert werden.'];
        }

        // 3. Storno-Frist prüfen (Setting boating_cancel_min_hours_before, Default 24)
        $minHours = self::DEFAULT_CANCEL_MIN_HOURS;
        if (class_exists('Settings')) {
            $settingVal = Settings::get('boating_cancel_min_hours_before');
            if ($settingVal !== null && is_numeric($settingVal)) {
                $minHours = max(0, (int) $settingVal);
            }
        }

        $appDate = (string) ($app['date'] ?? '');
        $appTime = (string) ($app['start_time'] ?? '00:00:00');
        $appTimestamp = strtotime("{$appDate} {$appTime}");
        $now = time();

        if ($appTimestamp <= $now) {
            return ['success' => false, 'message' => 'Termine in der Vergangenheit können nicht mehr storniert werden.'];
        }

        $diffHours = ($appTimestamp - $now) / 3600;
        if ($diffHours < $minHours) {
            return [
                'success' => false,
                'message' => "Storno nur bis {$minHours} Stunden vor Termin möglich. Bitte kontaktieren Sie uns telefonisch.",
            ];
        }

        // 4. Status auf 'cancelled_customer' setzen
        $updated = BoatingSchoolRepository::updateAppointmentStatus($appointmentId, 'cancelled_customer');
        if (!$updated) {
            return ['success' => false, 'message' => 'Fehler beim Stornieren des Termins in der Datenbank.'];
        }

        // 5. Instructor Payout bei offener Abrechnung anpassen
        if (class_exists('InstructorPayoutService') && ($app['instructor_payout_status'] ?? 'open') === 'open') {
            InstructorPayoutService::updateAppointmentPayout(
                $appointmentId,
                0.00,
                'open',
                'Storniert durch Schüler: ' . ($reason ? trim($reason) : 'Ohne Angabe von Gründen')
            );
        }

        // 6. Benachrichtigungs-Mail über BoatingNotificationService auslösen
        if (class_exists('BoatingNotificationService')) {
            try {
                BoatingNotificationService::sendBookingCancelledNotifications(
                    $appointmentId,
                    'customer',
                    $reason ? trim($reason) : 'Stornierung durch Schüler via Magic-Link'
                );
            } catch (\Throwable $e) {
                error_log('BoatingCustomerMagicLinkService: Storno-Mail-Fehler: ' . $e->getMessage());
            }
        }

        // 7. Activity-Log
        if (class_exists('ActivityLog')) {
            ActivityLog::log('boating.appointment_cancelled_by_student', 'customer', [
                'appointment_id' => $appointmentId,
                'customer_id'    => $customerId,
                'reason'         => $reason,
                'cancelled_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'success' => true,
            'message' => 'Deine Fahrstunde wurde erfolgreich storniert. Eine Bestätigung wurde per E-Mail versendet.',
        ];
    }

    /**
     * Ermittelt die Basis-URL der aktuellen Installation.
     */
    public static function getBaseUrl(): string
    {
        $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return "{$scheme}://{$host}";
    }
}
