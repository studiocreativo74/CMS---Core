<?php

declare(strict_types=1);

/**
 * SecurePortalService
 *
 * Business-Logik und Session-Management für das Sicherungsportal.
 * Handhabt die isolierte Authentifizierung von Antragstellern (Fallzugang),
 * Formular-Validierungen für das mehrteilige Webformular sowie RBAC-Prüfungen im Admin-Bereich.
 */
final class SecurePortalService
{
    private const CASE_AUTH_SESSION_KEY = 'secure_case_auth';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 300; // 5 Minuten Sperre

    private function __construct()
    {
    }

    /**
     * Ermittelt den aktuell über Vorgangs-ID und Zugangscode authentifizierten Fall.
     * Stellt sicher, dass der Antragsteller isoliert NUR auf seinen eigenen Fall zugreifen kann.
     *
     * @return array<string, mixed>|null
     */
    public static function getAuthenticatedCase(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (class_exists('Auth')) {
                Auth::startSession();
            } else {
                @session_start();
            }
        }

        $sessionData = $_SESSION[self::CASE_AUTH_SESSION_KEY] ?? null;
        if (!is_array($sessionData) || empty($sessionData['case_id'])) {
            return null;
        }

        $caseId = (int) $sessionData['case_id'];
        $case = SecurePortalRepository::getCaseById($caseId);

        if (!$case) {
            self::logoutCase();
            return null;
        }

        return $case;
    }

    /**
     * Prüft die Anmeldedaten des Fallzugangs und setzt die isolierte Fall-Session.
     *
     * @return array{success: bool, error: string|null, case: array<string, mixed>|null}
     */
    public static function loginCase(string $caseNumber, string $accessCode): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (class_exists('Auth')) {
                Auth::startSession();
            } else {
                @session_start();
            }
        }

        // Rate-Limiting gegen Brute-Force
        if (self::isLoginThrottled()) {
            return [
                'success' => false,
                'error'   => 'Zu viele fehlerhafte Anmeldeversuche. Bitte warten Sie 5 Minuten, bevor Sie es erneut versuchen.',
                'case'    => null,
            ];
        }

        $caseNumber = trim($caseNumber);
        $accessCode = trim($accessCode);

        if ($caseNumber === '' || $accessCode === '') {
            return [
                'success' => false,
                'error'   => 'Bitte geben Sie sowohl Ihre Vorgangs-ID (z. B. POL-2026-123456) als auch den Zugangscode ein.',
                'case'    => null,
            ];
        }

        $case = SecurePortalRepository::findCaseByCredentials($caseNumber, $accessCode);

        if (!$case) {
            self::recordFailedLoginAttempt();
            return [
                'success' => false,
                'error'   => 'Ungültige Vorgangs-ID oder ungültiger Zugangscode. Bitte prüfen Sie Ihre Eingaben.',
                'case'    => null,
            ];
        }

        // Erfolgreicher Login: Fehlversuche zurücksetzen & isolierte Session setzen
        self::clearLoginAttempts();

        $_SESSION[self::CASE_AUTH_SESSION_KEY] = [
            'case_id'     => (int) $case['id'],
            'case_number' => (string) $case['case_number'],
            'auth_time'   => time(),
        ];

        return [
            'success' => true,
            'error'   => null,
            'case'    => $case,
        ];
    }

    /**
     * Beendet die Fallzugangs-Session des Antragstellers.
     */
    public static function logoutCase(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        unset($_SESSION[self::CASE_AUTH_SESSION_KEY]);
    }

    /**
     * Prüft, ob der angemeldete Admin-Benutzer Sicherungsvorgänge einsehen darf.
     */
    public static function canViewCases(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('secure_portal.cases.view') || Rbac::can('secure_portal.cases.manage')) {
                return true;
            }
        }

        // Fallback: Benutzer mit Admin- oder Techniker-Rolle
        $user = self::getCurrentAdminUser();
        if ($user !== null) {
            $role = strtolower(trim((string) ($user['role'] ?? '')));
            if (in_array($role, ['admin', 'superadmin', 'technician', 'administration', 'property_manager'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft, ob der angemeldete Admin-Benutzer Sicherungsvorgänge bearbeiten darf.
     */
    public static function canManageCases(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('secure_portal.cases.manage')) {
                return true;
            }
        }

        $user = self::getCurrentAdminUser();
        if ($user !== null) {
            $role = strtolower(trim((string) ($user['role'] ?? '')));
            if (in_array($role, ['admin', 'superadmin', 'technician', 'administration'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert den aktuellen angemeldeten Benutzer des Backends.
     *
     * @return array<string, mixed>|null
     */
    public static function getCurrentAdminUser(): ?array
    {
        if (class_exists('Auth') && Auth::check()) {
            $user = Auth::user();
            if (is_array($user)) {
                return $user;
            }
        }

        if (!empty($_SESSION['user_id']) && class_exists('User')) {
            $user = User::findById((int) $_SESSION['user_id']);
            if (is_array($user)) {
                return $user;
            }
        }

        if (!empty($_SESSION['magic_authenticated'])) {
            return [
                'id'       => (int) ($_SESSION['user_id'] ?? 1),
                'email'    => (string) ($_SESSION['magic_email'] ?? 'admin@system.local'),
                'username' => 'Admin (Magic Session)',
                'role'     => 'superadmin',
            ];
        }

        return null;
    }

    // =========================================================================
    // VALIDIERUNG FÜR DAS MEHRTEILIGE WEBFORMULAR
    // =========================================================================

    /**
     * Validiert Schritt 1: Basisdaten des Antrags.
     *
     * @param array<string, mixed> $data
     * @return array<string, string> Fehlermeldungen (leer bei Erfolg)
     */
    public static function validateStep1(array $data): array
    {
        $errors = [];

        $dept = trim((string) ($data['police_department'] ?? ''));
        if ($dept === '' || strlen($dept) < 3 || strlen($dept) > 255) {
            $errors['police_department'] = 'Bitte geben Sie die antragstellende Dienststelle/Behörde an (mind. 3 Zeichen).';
        }

        $name = trim((string) ($data['contact_name'] ?? ''));
        if ($name === '' || strlen($name) < 3 || strlen($name) > 255) {
            $errors['contact_name'] = 'Bitte geben Sie den Namen und ggf. Dienstgrad/Funktion des Sachbearbeiters an.';
        }

        $email = trim((string) ($data['contact_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Bitte geben Sie eine gültige dienstliche E-Mail-Adresse für Status-Benachrichtigungen an.';
        }

        $phone = trim((string) ($data['contact_phone'] ?? ''));
        if ($phone === '' || strlen($phone) < 5 || strlen($phone) > 64) {
            $errors['contact_phone'] = 'Bitte geben Sie eine telefonische Erreichbarkeit für Rückfragen an.';
        }

        $ref = trim((string) ($data['reference_number'] ?? ''));
        if ($ref === '' || strlen($ref) < 2 || strlen($ref) > 128) {
            $errors['reference_number'] = 'Bitte geben Sie das behördliche Aktenzeichen / die Vorgangsnummer an.';
        }

        $desc = trim((string) ($data['description'] ?? ''));
        if ($desc === '' || strlen($desc) < 10) {
            $errors['description'] = 'Bitte beschreiben Sie den Umfang der gewünschten Sicherung detailliert (mindestens 10 Zeichen).';
        }

        // Optionales Datum prüfen, falls eingegeben
        $desiredDate = trim((string) ($data['desired_date'] ?? ''));
        if ($desiredDate !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $desiredDate);
            if (!$d || $d->format('Y-m-d') !== $desiredDate) {
                $errors['desired_date'] = 'Das gewünschte Sicherungsdatum ist im ungültigen Format (JJJJ-MM-TT).';
            }
        }

        return $errors;
    }

    /**
     * Speichert die hochgeladene Editionsverfügung (PDF) manipulationssicher ab.
     *
     * @param array<string, mixed> $file $_FILES['warrant_file']
     * @return array{file_path: string, mime: string, file_size: int, original_filename: string}
     * @throws RuntimeException
     */
    public static function processWarrantUpload(array $file): array
    {
        if (!class_exists('Upload')) {
            require_once dirname(__DIR__, 3) . '/core/Upload.php';
        }

        // Zielverzeichnis im geschützten Storage-Bereich
        $baseDir = dirname(__DIR__, 3);
        $targetDir = $baseDir . '/storage/secure_portal/warrants';

        // Strikte Einschränkung auf echte PDFs
        $allowedMime = [
            'application/pdf',
            'application/x-pdf',
        ];

        // Maximal 30 MB für behördliche Beschlüsse
        $maxBytes = 31457280;

        $docInfo = Upload::saveDocument($file, $targetDir, $allowedMime, $maxBytes, 'editionsverfuegung_');

        return [
            'file_path'         => $docInfo['relative_path'],
            'mime'              => $docInfo['mime_type'],
            'file_size'         => $docInfo['file_size'],
            'original_filename' => $docInfo['original_filename'],
        ];
    }

    /**
     * Validiert Schritt 3: Typabhängige Felder.
     *
     * @param string $type Typ (VIDEO, MAIL, CLOUD, ACCESS_LOG, OTHER)
     * @param array<string, mixed> $meta
     * @return array<string, string>
     */
    public static function validateStep3(string $type, array $meta): array
    {
        $errors = [];

        if (!array_key_exists($type, SecurePortalRepository::SECURING_TYPES)) {
            $errors['securing_type'] = 'Bitte wählen Sie eine gültige Art der Sicherung aus.';
            return $errors;
        }

        switch ($type) {
            case 'VIDEO':
                if (empty($meta['timeframe_from'])) {
                    $errors['timeframe_from'] = 'Bitte Beginn des Sicherungszeitraums angeben.';
                }
                if (empty($meta['timeframe_to'])) {
                    $errors['timeframe_to'] = 'Bitte Ende des Sicherungszeitraums angeben.';
                }
                if (empty($meta['camera_location'])) {
                    $errors['camera_location'] = 'Bitte Standort bzw. relevante Kameras/Bereiche angeben.';
                }
                break;

            case 'MAIL':
                if (empty($meta['mailbox_address'])) {
                    $errors['mailbox_address'] = 'Bitte das betroffene Postfach bzw. die E-Mail-Adresse angeben.';
                }
                if (empty($meta['mail_timeframe_from'])) {
                    $errors['mail_timeframe_from'] = 'Bitte Zeitraum von angeben.';
                }
                if (empty($meta['mail_timeframe_to'])) {
                    $errors['mail_timeframe_to'] = 'Bitte Zeitraum bis angeben.';
                }
                break;

            case 'CLOUD':
                if (empty($meta['system_name'])) {
                    $errors['system_name'] = 'Bitte System-, Server- oder Cloud-Dienstbezeichnung angeben.';
                }
                if (empty($meta['cloud_target_data'])) {
                    $errors['cloud_target_data'] = 'Bitte Verzeichnisse, Dateien oder Benutzerkonten spezifizieren.';
                }
                break;

            case 'ACCESS_LOG':
                if (empty($meta['doors_points'])) {
                    $errors['doors_points'] = 'Bitte Türen, Tore oder Kontrollpunkte angeben.';
                }
                if (empty($meta['log_timeframe'])) {
                    $errors['log_timeframe'] = 'Bitte Zeitraum der Protokollierung angeben.';
                }
                break;

            case 'OTHER':
                if (empty($meta['other_details'])) {
                    $errors['other_details'] = 'Bitte spezifizieren Sie die technischen Details der Sicherung.';
                }
                break;
        }

        return $errors;
    }

    // =========================================================================
    // RATE LIMITING FÜR DEN ANTRAGSTELLER-ZUGANG
    // =========================================================================

    private static function isLoginThrottled(): bool
    {
        $attempts = (int) ($_SESSION['secure_login_attempts'] ?? 0);
        $lastAttempt = (int) ($_SESSION['secure_login_last_attempt'] ?? 0);

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            if ((time() - $lastAttempt) < self::LOGIN_LOCKOUT_SECONDS) {
                return true;
            }
            // Sperrzeit abgelaufen
            self::clearLoginAttempts();
        }

        return false;
    }

    private static function recordFailedLoginAttempt(): void
    {
        $attempts = (int) ($_SESSION['secure_login_attempts'] ?? 0);
        $_SESSION['secure_login_attempts'] = $attempts + 1;
        $_SESSION['secure_login_last_attempt'] = time();
    }

    private static function clearLoginAttempts(): void
    {
        unset($_SESSION['secure_login_attempts'], $_SESSION['secure_login_last_attempt']);
    }
}
