<?php

declare(strict_types=1);

require_once __DIR__ . '/BoatingCustomerMagicLinkService.php';

/**
 * BoatingMagicLinkService
 *
 * Kompatibilitäts- und Alias-Klasse für BoatingCustomerMagicLinkService.
 */
class BoatingMagicLinkService extends BoatingCustomerMagicLinkService
{
    public const PURPOSE_APPOINTMENTS_VIEW = 'appointments_view';
    public const DEFAULT_EXPIRY_DAYS = 30;

    /**
     * Stellt sicher, dass die Tabelle bs_customer_magic_links existiert (idempotent).
     */
    public static function ensureTable(): void
    {
        try {
            DB::execute("
                CREATE TABLE IF NOT EXISTS `bs_customer_magic_links` (
                    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `customer_id`  INT UNSIGNED NOT NULL,
                    `token_hash`   CHAR(64) NOT NULL,
                    `purpose`      VARCHAR(32) NOT NULL DEFAULT 'appointments_view',
                    `expires_at`   DATETIME NOT NULL,
                    `used_at`      DATETIME NULL,
                    `created_at`   DATETIME NOT NULL,
                    `created_ip`   VARCHAR(45) NULL,
                    CONSTRAINT `fk_bs_magic_customer` FOREIGN KEY (`customer_id`)
                        REFERENCES `bs_customers` (`id`) ON DELETE CASCADE,
                    INDEX `idx_bs_magic_token` (`token_hash`),
                    INDEX `idx_bs_magic_customer` (`customer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            error_log('BoatingMagicLinkService::ensureTable Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt ein neues Token für den Kunden und speichert dessen SHA-256-Hash.
     *
     * @param int $customerId
     * @param int $daysValid Gültigkeitsdauer in Tagen (Standard: 30)
     * @param string $purpose Verwendungszweck
     * @param string|null $ip Optionale Client-IP
     * @return string Das Klartext-Token (wird nur einmalig zurückgegeben)
     */
    public static function createToken(
        int $customerId,
        int $daysValid = self::DEFAULT_EXPIRY_DAYS,
        string $purpose = self::PURPOSE_APPOINTMENTS_VIEW,
        ?string $ip = null
    ): string {
        self::ensureTable();

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Ungültige Kunden-ID für Magic-Link.');
        }

        $plainToken = bin2hex(random_bytes(32)); // 64 Hex-Zeichen
        $tokenHash = hash('sha256', $plainToken);

        $days = max(1, $daysValid);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        $sql = 'INSERT INTO `bs_customer_magic_links`
                (`customer_id`, `token_hash`, `purpose`, `expires_at`, `used_at`, `created_at`, `created_ip`)
                VALUES
                (:customer_id, :token_hash, :purpose, :expires_at, NULL, NOW(), :created_ip)';

        DB::execute($sql, [
            'customer_id' => $customerId,
            'token_hash'  => $tokenHash,
            'purpose'     => $purpose,
            'expires_at'  => $expiresAt,
            'created_ip'  => $clientIp ? substr($clientIp, 0, 45) : null,
        ]);

        return $plainToken;
    }

    /**
     * Generiert die vollständige URL für den Schüler-Self-Service mit dem Klartext-Token.
     *
     * @param string $plainToken
     * @return string
     */
    public static function buildMagicUrl(string $plainToken): string
    {
        $baseUrl = self::getBaseUrl();
        // Unterstützt saubere URL und Query-Parameter-Route
        return "{$baseUrl}/bootschule/meine-termine?token=" . urlencode($plainToken);
    }

    /**
     * Erstellt einen vollständigen Zugangslink für einen Kunden.
     *
     * @param int $customerId
     * @param int $daysValid
     * @return string Vollständige URL
     */
    public static function generateLinkForCustomer(int $customerId, int $daysValid = self::DEFAULT_EXPIRY_DAYS): string
    {
        $token = self::createToken($customerId, $daysValid);
        return self::buildMagicUrl($token);
    }

    /**
     * Validiert ein übergebenes Klartext-Token.
     * Prüft, ob das Token in der Datenbank existiert und noch nicht abgelaufen ist.
     *
     * @param string $plainToken
     * @param bool $touchUsedAt Aktualisiert used_at auf NOW() bei Erfolg
     * @return array<string, mixed>|null ['customer' => array, 'magic_link' => array] oder null
     */
    public static function validateToken(string $plainToken, bool $touchUsedAt = true): ?array
    {
        self::ensureTable();

        $token = trim($plainToken);
        if ($token === '' || strlen($token) < 16) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

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

            $customerId = (int) $row['customer_id'];
            $customer = BoatingSchoolRepository::getCustomerById($customerId);
            if (!$customer) {
                return null;
            }

            if ($touchUsedAt) {
                DB::execute(
                    'UPDATE `bs_customer_magic_links` SET `used_at` = NOW() WHERE `id` = :id',
                    ['id' => (int) $row['id']]
                );
            }

            return [
                'magic_link' => $row,
                'customer'   => $customer,
            ];
        } catch (\Throwable $e) {
            error_log('BoatingMagicLinkService::validateToken Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt alle Termine eines Kunden aufgeteilt in bevorstehende und vergangene.
     *
     * @param int $customerId
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getCustomerAppointments(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['upcoming' => [], 'past' => [], 'all' => []];
        }

        $all = BoatingSchoolRepository::getAppointments(['customer_id' => $customerId], 500, 0);

        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        $upcoming = [];
        $past = [];

        foreach ($all as $app) {
            $appDate = (string) ($app['date'] ?? '');
            $endTime = (string) ($app['end_time'] ?? '23:59:59');

            if ($appDate > $today || ($appDate === $today && $endTime >= $nowTime)) {
                $upcoming[] = $app;
            } else {
                $past[] = $app;
            }
        }

        // Upcoming nach Datum aufsteigend sortieren
        usort($upcoming, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['start_time'] ?? ''), (string) ($b['start_time'] ?? ''));
        });

        // Past nach Datum absteigend sortieren
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
            'all'      => $all,
        ];
    }

    /**
     * Storniert einen Termin durch den Schüler über das Magic-Link-Portal.
     * Prüft sorgfältig die Eigentümerschaft und ob die Stornierung zulässig ist.
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
            return ['success' => false, 'message' => 'Termin nicht gefunden.'];
        }

        if ((int) $app['customer_id'] !== $customerId) {
            return ['success' => false, 'message' => 'Sie sind nicht berechtigt, diesen Termin zu stornieren.'];
        }

        $currentStatus = (string) ($app['status'] ?? '');
        if (in_array($currentStatus, ['cancelled_customer', 'cancelled_system', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Dieser Termin wurde bereits storniert.'];
        }

        if ($currentStatus === 'completed') {
            return ['success' => false, 'message' => 'Ein bereits absolvierter Termin kann nicht storniert werden.'];
        }

        // Stornierungsfrist prüfen (z.B. Termin darf nicht in der Vergangenheit liegen)
        $today = date('Y-m-d');
        if ((string) $app['date'] < $today) {
            return ['success' => false, 'message' => 'Vergangene Termine können nicht storniert werden.'];
        }

        // Status auf 'cancelled_customer' setzen
        $updated = BoatingSchoolRepository::updateAppointmentStatus($appointmentId, 'cancelled_customer');
        if (!$updated) {
            return ['success' => false, 'message' => 'Fehler beim Stornieren des Termins.'];
        }

        // Bei Stornierung: Instructor Payout auf 0 setzen, falls noch 'open'
        if (($app['instructor_payout_status'] ?? 'open') === 'open') {
            InstructorPayoutService::updateAppointmentPayout(
                $appointmentId,
                0.00,
                'open',
                'Storniert durch Kunde: ' . ($reason ? trim($reason) : 'Ohne Begründung')
            );
        }

        // Benachrichtigungen versenden
        if (class_exists('BoatingNotificationService')) {
            try {
                BoatingNotificationService::sendBookingCancelledNotifications(
                    $appointmentId,
                    'customer',
                    $reason ? trim($reason) : 'Stornierung durch Schüler via Magic-Link'
                );
            } catch (\Throwable $e) {
                error_log('BoatingMagicLinkService: Fehler beim Mailversand: ' . $e->getMessage());
            }
        }

        // Activity Log
        if (class_exists('ActivityLog')) {
            ActivityLog::log('boating.appointment_cancelled_by_student', 'customer', [
                'appointment_id' => $appointmentId,
                'customer_id'    => $customerId,
                'reason'         => $reason,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Deine Fahrstunde wurde erfolgreich storniert. Eine Bestätigung wurde per E-Mail versendet.',
        ];
    }

    /**
     * Sendet einen neuen Magic-Link an eine eingegebene E-Mail-Adresse,
     * falls ein Kunde mit dieser E-Mail existiert.
     *
     * @param string $email
     * @return array<string, mixed>
     */
    public static function requestMagicLinkByEmail(string $email): array
    {
        $cleanEmail = trim(strtolower($email));
        if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            ];
        }

        $customers = BoatingSchoolRepository::getCustomers(['email' => $cleanEmail], 5, 0);
        if (empty($customers)) {
            // Aus Sicherheitsgründen keine Information preisgeben, ob E-Mail existiert
            return [
                'success' => true,
                'message' => 'Falls deine E-Mail-Adresse bei uns registriert ist, haben wir dir soeben einen neuen Zugangslink gesendet.',
            ];
        }

        $customer = $customers[0];
        $customerId = (int) $customer['id'];

        $magicUrl = self::generateLinkForCustomer($customerId, self::DEFAULT_EXPIRY_DAYS);

        // E-Mail mit Zugangslink senden
        $subject = 'Dein persönlicher Zugangslink – Meine Termine';
        $firstName = !empty($customer['first_name']) ? (string) $customer['first_name'] : 'Schüler';
        $schoolName = class_exists('BoatingNotificationService')
            ? BoatingNotificationService::getSetting(BoatingNotificationService::KEY_FROM_NAME)
            : 'Bootsfahrschule';

        $body = "Hallo {$firstName},\n\n"
              . "du hast einen Zugangslink für deine Terminübersicht bei unserer Bootsfahrschule angefordert.\n\n"
              . "Klicke einfach auf folgenden Link, um deine bevorstehenden und vergangenen Fahrstunden einzusehen:\n\n"
              . "{$magicUrl}\n\n"
              . "Dieser Link ist für 30 Tage gültig und benötigt kein Passwort.\n\n"
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
                error_log('BoatingMagicLinkService: Mailer-Fehler: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => 'Wir haben dir soeben deinen persönlichen Zugangslink per E-Mail zugestellt. Bitte prüfe auch deinen Spam-Ordner.',
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
