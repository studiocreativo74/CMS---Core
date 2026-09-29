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
            $errors['reference_number'] = 'Bitte geben Sie die Fall Nr an.';
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
     * Organisiert die Editionsverfügung im dedizierten Fall-Ordner:
     * Ziel: uploads/secure/cases/{folder}/{filename}
     *
     * @param int    $caseId         Vorgangs-ID in DB
     * @param string $caseNumber     Vorgangsnummer (z. B. POL-2026-000123)
     * @param string $sourceFilePath Aktueller (temporärer) Dateipfad
     * @param int    $version        Versionsnummer (Standard: 1)
     * @param array<string, mixed> $caseData Optionale Vorgangsdaten (reference_number, created_at, etc.)
     * @return string Neuer relativer Dateipfad (z. B. uploads/secure/cases/POL-2026-000123/POL-2026-000123_Editionsverfuegung_v1.pdf)
     */
    public static function finalizeCaseWarrant(int $caseId, string $caseNumber, string $sourceFilePath, int $version = 1, array $caseData = []): string
    {
        $cleanSource = trim($sourceFilePath);
        if ($cleanSource === '' || $caseId <= 0 || trim($caseNumber) === '') {
            return $cleanSource;
        }

        if (!class_exists('SecurePortalConfig')) {
            require_once __DIR__ . '/SecurePortalConfig.php';
        }

        if (empty($caseData) && class_exists('SecurePortalRepository')) {
            $caseData = SecurePortalRepository::getCaseById($caseId) ?? [];
        }

        // Ziel-Ordner sicherstellen
        $targetDir = SecurePortalConfig::getCaseDir($caseNumber, true, $caseData);

        // Standardisierter Dateiname
        $targetFileName = SecurePortalConfig::buildWarrantFileName($caseNumber, $version, $caseData);
        $targetFullPath = $targetDir . '/' . $targetFileName;
        $targetRelPath  = SecurePortalConfig::buildWarrantRelativePath($caseNumber, $version, $caseData);

        // Lokalen Pfad der Quell-Datei auflösen
        $resolvedSource = SecurePortalConfig::resolveLocalFilePath($cleanSource);

        if ($resolvedSource !== null && file_exists($resolvedSource) && is_file($resolvedSource)) {
            // Nur verschieben/kopieren, wenn Ziel noch nicht identisch mit Quelle ist
            if (realpath($resolvedSource) !== realpath($targetFullPath)) {
                $copied = @copy($resolvedSource, $targetFullPath);
                if ($copied) {
                    // Temporäre Ausgangsdatei nach erfolgreichem Kopieren aufräumen
                    @unlink($resolvedSource);

                    // Datenbank-Eintrag aktualisieren
                    SecurePortalRepository::updateWarrantFilePath($caseId, $targetRelPath);

                    // Audit-Log für interne Nachverfolgbarkeit
                    SecurePortalRepository::addCaseLog(
                        $caseId,
                        'warrant_organized',
                        sprintf('Editionsverfügung im Fallordner abgelegt: %s', $targetRelPath),
                        'System',
                        true
                    );

                    return $targetRelPath;
                }
            } else {
                return $targetRelPath;
            }
        }

        return $cleanSource;
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
                if (empty($meta['object'])) {
                    $errors['object'] = 'Bitte wählen Sie das betroffene Objekt / Liegenschaft aus.';
                }
                if (empty($meta['floor'])) {
                    $errors['floor'] = 'Bitte wählen Sie das Stockwerk / die Ebene aus.';
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

    // =========================================================================
    // E-MAIL-BENACHRICHTIGUNG AN SAMMELADRESSE BEI NEUEM VORGANG
    // =========================================================================

    /**
     * Versendet eine neutrale E-Mail-Benachrichtigung an die in Settings konfigurierte
     * Sammeladresse (`secure_notification_email`), sobald ein neuer Fall erfasst wurde.
     *
     * Sicherheitsrichtlinie:
     * - Es werden KEINE Editionsverfügungen oder Sicherungsdaten angehängt.
     * - Es werden ausschliesslich Metadaten und Vorgangs-IDs übermittelt.
     * - Ein Fehler beim Mailversand bricht den Vorgang niemals ab.
     *
     * @param array<string, mixed> $case Daten des soeben erstellten Vorgangs
     * @return bool True bei erfolgreichem Versand, sonst false
     */
    public static function sendNewCaseNotification(array $case): bool
    {
        if (!class_exists('Settings')) {
            $settingsPath = dirname(__DIR__, 2) . '/core/Settings.php';
            if (file_exists($settingsPath)) {
                require_once $settingsPath;
            }
        }

        if (!class_exists('Settings')) {
            return false;
        }

        $notificationEmail = trim((string) Settings::get('secure_notification_email', ''));
        if ($notificationEmail === '' || !filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
            // Keine Sammeladresse konfiguriert oder ungültig -> stillschweigend überspringen
            return false;
        }

        if (!class_exists('Mailer')) {
            $mailerPath = dirname(__DIR__, 2) . '/core/Mailer.php';
            if (file_exists($mailerPath)) {
                require_once $mailerPath;
            }
        }

        if (!class_exists('Mailer')) {
            error_log('SecurePortalService::sendNewCaseNotification: Mailer-Klasse nicht gefunden.');
            return false;
        }

        $caseId       = (int) ($case['id'] ?? 0);
        $caseNumber   = trim((string) ($case['case_number'] ?? ''));
        $refNumber    = trim((string) ($case['reference_number'] ?? '-'));
        $dept         = trim((string) ($case['police_department'] ?? '-'));
        $contactName  = trim((string) ($case['contact_name'] ?? '-'));
        $contactEmail = trim((string) ($case['contact_email'] ?? '-'));
        $contactPhone = trim((string) ($case['contact_phone'] ?? '-'));
        $securingType = (string) ($case['securing_type'] ?? 'VIDEO');
        $desiredDate  = trim((string) ($case['desired_date'] ?? ''));
        $createdAt    = (string) ($case['created_at'] ?? date('Y-m-d H:i:s'));

        // Art der Sicherung lesbar formatieren
        $typeLabel = $securingType;
        if (class_exists('SecurePortalRepository') && isset(SecurePortalRepository::SECURING_TYPES[$securingType]['label'])) {
            $typeLabel = SecurePortalRepository::SECURING_TYPES[$securingType]['label'];
        }

        // Ort ermitteln (falls vorhanden)
        $city = '';
        if (class_exists('Naming')) {
            $city = Naming::extractCity($case);
        }
        $cityStr = $city !== '' ? $city : '-';

        // Datum lesbar aufbereiten
        $ts = strtotime($createdAt) ?: time();
        $formattedDate = date('d.m.Y H:i', $ts);
        $desiredDateStr = $desiredDate !== '' ? date('d.m.Y', strtotime($desiredDate)) : 'Nicht vorgegeben';

        // Betreff zusammenbauen
        $subject = sprintf('[Sicherungsportal] Neuer Antrag eingegangen: %s (%s)', $caseNumber, $refNumber);

        // Host / Deep-Link zum Admin-Vorgang ermitteln
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $viewUrl = $caseId > 0 
            ? "{$scheme}://{$host}/?route=admin/secure/cases/view&id={$caseId}"
            : "{$scheme}://{$host}/?route=admin/secure/cases";

        // Neutraler E-Mail-Body (Plaintext)
        $body = "Guten Tag,\n\n"
            . "im Sicherungsportal ist ein neuer behördlicher Sicherungsantrag eingegangen.\n\n"
            . "VORGANGSDETAILS\n"
            . "------------------------------------------------------------\n"
            . sprintf("Vorgangs-ID:         %s\n", $caseNumber)
            . sprintf("Fall Nr / Ref:       %s\n", $refNumber)
            . sprintf("Eingang am:          %s Uhr\n", $formattedDate)
            . sprintf("Art der Sicherung:   %s\n", $typeLabel)
            . sprintf("Gewünschter Termin:  %s\n", $desiredDateStr)
            . "------------------------------------------------------------\n\n"
            . "BEHÖRDE & ANTRAGSTELLER\n"
            . "------------------------------------------------------------\n"
            . sprintf("Dienststelle:        %s\n", $dept)
            . sprintf("Ort:                 %s\n", $cityStr)
            . sprintf("Sachbearbeiter:      %s\n", $contactName)
            . sprintf("Dienstliche E-Mail:  %s\n", $contactEmail)
            . sprintf("Telefon:             %s\n", $contactPhone)
            . "------------------------------------------------------------\n\n"
            . "BEARBEITUNG IM SYSTEM\n"
            . "------------------------------------------------------------\n"
            . "Öffnen Sie den Vorgang zur redaktionellen/technischen Prüfung:\n"
            . "{$viewUrl}\n\n"
            . "SICHERHEITSHINWEIS\n"
            . "------------------------------------------------------------\n"
            . "Aus Datenschutz- und Sicherheitsgründen sind die behördliche Editionsverfügung\n"
            . "sowie die Sicherungsdaten nicht per E-Mail beigefügt. Bitte bearbeiten\n"
            . "Sie den Fall ausschliesslich über den internen Administrationsbereich.\n\n"
            . "Freundliche Grüsse,\n"
            . "Sicherungsportal System\n";

        try {
            $sent = Mailer::send($notificationEmail, $subject, $body, false);

            if ($caseId > 0 && class_exists('SecurePortalRepository')) {
                if ($sent) {
                    SecurePortalRepository::addCaseLog(
                        $caseId,
                        'notification_sent',
                        sprintf('Benachrichtigungs-Mail an Sammeladresse (%s) erfolgreich gesendet.', $notificationEmail),
                        'System',
                        true
                    );
                } else {
                    SecurePortalRepository::addCaseLog(
                        $caseId,
                        'notification_failed',
                        sprintf('Versand der Benachrichtigungs-Mail an Sammeladresse (%s) fehlgeschlagen.', $notificationEmail),
                        'System',
                        true
                    );
                }
            }

            return $sent;
        } catch (\Throwable $e) {
            error_log('SecurePortalService::sendNewCaseNotification Fehler: ' . $e->getMessage());
            if ($caseId > 0 && class_exists('SecurePortalRepository')) {
                SecurePortalRepository::addCaseLog(
                    $caseId,
                    'notification_failed',
                    sprintf('Fehler beim E-Mail-Versand an Sammeladresse (%s): %s', $notificationEmail, $e->getMessage()),
                    'System',
                    true
                );
            }
            return false;
        }
    }

    // =========================================================================
    // SICHERUNGSDATEN: INTERNER UPLOAD & SHA-256 PRÜFUNG (ADMIN)
    // =========================================================================

    /**
     * Bereinigt den Dateinamen eines Sicherungsarchivs oder Beweismittels.
     */
    public static function sanitizeSecuringFileName(string $originalName, string $caseNumber): string
    {
        $base = trim($originalName);
        if ($base === '') {
            $base = $caseNumber . '_sicherungsdaten.zip';
        }

        // Umlaute und Sonderzeichen transliterieren
        $base = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', ' '],
            ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '_'],
            $base
        );

        // Pfadteile entfernen
        $base = basename(str_replace(['\\', '/'], '/', $base));

        // Unerlaubte Zeichen durch Unterstriche ersetzen
        $base = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $base) ?? $base;
        $base = preg_replace('/_+/', '_', $base) ?? $base;
        $base = trim($base, '._ ');

        if ($base === '' || $base === '.') {
            $base = $caseNumber . '_sicherungsdaten.zip';
        }

        return $base;
    }

    /**
     * Verarbeitet den internen Upload einer Sicherungsdatei (z.B. ZIP, TAR, 7Z, ISO)
     * im Admin-Bereich, berechnet die SHA-256 Prüfsumme und speichert den Eintrag in DB.
     *
     * @param int $caseId Vorgangs-ID
     * @param array<string, mixed> $file $_FILES['securing_file']
     * @param bool $setAvailable Ob der Fallstatus direkt auf „Bereitgestellt“ gesetzt werden soll
     * @param string $author Name des eingeloggten Admins/Technikers
     * @param int|null $uploadedBy Optional: User-ID des Admins
     * @return array{success: bool, error: ?string, file_id: ?int, file_name: ?string, sha256: ?string, file_size: ?int}
     */
    public static function processSecuringDataUpload(
        int $caseId,
        array $file,
        bool $setAvailable = false,
        string $author = 'Admin',
        ?int $uploadedBy = null
    ): array {
        if ($caseId <= 0) {
            return ['success' => false, 'error' => 'Ungültige Vorgangs-ID.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        $case = SecurePortalRepository::getCaseById($caseId);
        if (!$case) {
            return ['success' => false, 'error' => 'Sicherungsvorgang nicht gefunden.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        if (empty($file) || !isset($file['error'])) {
            return ['success' => false, 'error' => 'Keine Datei empfangen.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei überschreitet die maximal erlaubte Upload-Grösse des Webservers.',
                UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise übertragen.',
                UPLOAD_ERR_NO_FILE => 'Es wurde keine Sicherungsdatei ausgewählt.',
                default => 'Fehler beim Datei-Upload (Code ' . (int) $file['error'] . ').',
            };
            return ['success' => false, 'error' => $msg, 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !file_exists($tmpPath) || !is_readable($tmpPath)) {
            return ['success' => false, 'error' => 'Temporäre Upload-Datei nicht lesbar.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        $caseNumber = (string) $case['case_number'];
        $rawFileName = (string) ($file['name'] ?? 'sicherungsarchiv.zip');
        $safeFileName = self::sanitizeSecuringFileName($rawFileName, $caseNumber);

        // Ziel-Verzeichnis: uploads/secure/cases/{FALLORDNER}/data/
        $dataDir = SecurePortalConfig::getCaseDataDir($caseNumber, true, $case);

        // Bei Namenskollision Datei eindeutig nummerieren
        $targetFileName = $safeFileName;
        $counter = 1;
        $ext = pathinfo($safeFileName, PATHINFO_EXTENSION);
        $nameNoExt = pathinfo($safeFileName, PATHINFO_FILENAME);

        while (file_exists($dataDir . '/' . $targetFileName)) {
            $targetFileName = sprintf('%s_v%d.%s', $nameNoExt, ++$counter, $ext);
        }

        $targetFullPath = $dataDir . '/' . $targetFileName;

        // Datei in das geschützte Fallverzeichnis verschieben
        $moved = false;
        if (is_uploaded_file($tmpPath)) {
            $moved = @move_uploaded_file($tmpPath, $targetFullPath);
        } else {
            $moved = @copy($tmpPath, $targetFullPath);
            if ($moved) {
                @unlink($tmpPath);
            }
        }

        if (!$moved || !file_exists($targetFullPath)) {
            return ['success' => false, 'error' => 'Fehler beim Verschieben der Sicherungsdatei in das Fall-Verzeichnis.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        // SHA-256 Prüfsumme auf dem Datenträger berechnen
        $sha256 = hash_file('sha256', $targetFullPath);
        if ($sha256 === false || strlen($sha256) !== 64) {
            @unlink($targetFullPath);
            return ['success' => false, 'error' => 'Berechnung der SHA-256 Prüfsumme fehlgeschlagen.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }
        $sha256 = strtolower($sha256);

        // Dateigrösse und MIME-Typ ermitteln
        $fileSize = (int) filesize($targetFullPath);
        $mimeType = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($targetFullPath);
            if ($detected !== false && $detected !== '') {
                $mimeType = $detected;
            }
        }

        // Relativer Pfad im Webspace (z. B. uploads/secure/cases/.../data/archiv.zip)
        $relPath = SecurePortalConfig::getCaseRelativePath($caseNumber, 'data/' . $targetFileName, $case);

        // In Datenbank eintragen
        $fileId = SecurePortalRepository::addCaseFile(
            $caseId,
            $targetFileName,
            $relPath,
            $mimeType,
            $fileSize,
            $sha256,
            $uploadedBy
        );

        if (!$fileId) {
            return ['success' => false, 'error' => 'Datenbankfehler beim Speichern des Datei-Eintrags.', 'file_id' => null, 'file_name' => null, 'sha256' => null, 'file_size' => null];
        }

        // Audit-Logeintrag verfassen
        $sizeMb = number_format($fileSize / 1024 / 1024, 2);
        SecurePortalRepository::addCaseLog(
            $caseId,
            'securing_file_uploaded',
            sprintf('Sicherungsdatei „%s“ (%s MB) hochgeladen. SHA-256 Prüfsumme: %s', $targetFileName, $sizeMb, $sha256),
            $author,
            true // Internes Log
        );

        // Fallstatus bei Bedarf direkt auf „Bereitgestellt“ setzen
        if ($setAvailable) {
            SecurePortalRepository::setCaseAvailable($caseId, $author);
        }

        return [
            'success'   => true,
            'error'     => null,
            'file_id'   => $fileId,
            'file_name' => $targetFileName,
            'sha256'    => $sha256,
            'file_size' => $fileSize,
        ];
    }

    /**
     * Führt eine Re-Verifizierung der SHA-256 Prüfsumme einer gespeicherten Sicherungsdatei durch.
     *
     * @param int $caseId Vorgangs-ID
     * @param int $fileId Datei-ID
     * @param string $author Name des prüfenden Admins
     * @return array{success: bool, error: ?string, match: bool, stored_sha256: string, actual_sha256: string, file_name: string}
     */
    public static function verifyFileHash(int $caseId, int $fileId, string $author = 'Admin'): array
    {
        $file = SecurePortalRepository::getCaseFileById($fileId, $caseId);
        if (!$file) {
            return ['success' => false, 'error' => 'Sicherungsdatei nicht gefunden.', 'match' => false, 'stored_sha256' => '', 'actual_sha256' => '', 'file_name' => ''];
        }

        $relPath = (string) $file['file_path'];
        $fullPath = SecurePortalConfig::resolveLocalFilePath($relPath);

        if ($fullPath === null || !file_exists($fullPath) || !is_readable($fullPath)) {
            return ['success' => false, 'error' => 'Datei existiert nicht auf dem Server oder ist nicht lesbar (' . $relPath . ').', 'match' => false, 'stored_sha256' => (string)$file['sha256'], 'actual_sha256' => '', 'file_name' => (string)$file['file_name']];
        }

        $actualHash = hash_file('sha256', $fullPath);
        if ($actualHash === false) {
            return ['success' => false, 'error' => 'Berechnung der SHA-256 Prüfsumme fehlgeschlagen.', 'match' => false, 'stored_sha256' => (string)$file['sha256'], 'actual_sha256' => '', 'file_name' => (string)$file['file_name']];
        }

        $actualHash = strtolower($actualHash);
        $storedHash = strtolower(trim((string) $file['sha256']));
        $match = ($actualHash === $storedHash);

        // Ergebnis im Fall-Auditlog dokumentieren
        $statusText = $match ? 'Erfolgreich bestätigt (Exakt übereinstimmend)' : 'FEHLGESCHLAGEN (Abweichung festgestellt!)';
        SecurePortalRepository::addCaseLog(
            $caseId,
            'sha256_verified',
            sprintf('SHA-256 Integritätsprüfung für „%s“: %s. Berechneter Hash: %s', $file['file_name'], $statusText, $actualHash),
            $author,
            true
        );

        return [
            'success'       => true,
            'error'         => null,
            'match'         => $match,
            'stored_sha256' => $storedHash,
            'actual_sha256' => $actualHash,
            'file_name'     => (string) $file['file_name'],
        ];
    }

    /**
     * Löscht eine Sicherungsdatei und dokumentiert dies im Log.
     */
    public static function deleteSecuringFile(int $caseId, int $fileId, string $author = 'Admin'): bool
    {
        $file = SecurePortalRepository::getCaseFileById($fileId, $caseId);
        if (!$file) {
            return false;
        }

        $relPath = (string) $file['file_path'];
        $fullPath = SecurePortalConfig::resolveLocalFilePath($relPath);
        if ($fullPath !== null && file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }

        $deleted = SecurePortalRepository::deleteCaseFile($fileId, $caseId, false);
        if ($deleted) {
            SecurePortalRepository::addCaseLog(
                $caseId,
                'securing_file_deleted',
                sprintf('Sicherungsdatei „%s“ wurde durch %s entfernt.', $file['file_name'], $author),
                $author,
                true
            );
        }

        return $deleted;
    }

    /**
     * Startet die automatisierte oder manuelle Fristen-Bereinigung:
     * - T+30: Download-Zugang sperren (download_enabled = 0)
     * - T+60: Sicherungsdateien löschen und Fallstatus aktualisieren
     *
     * @param int|null $daysActive
     * @param int|null $daysDelete
     * @param string $author
     * @return array<string, mixed>
     */
    public static function runRetentionCleanup(
        ?int $daysActive = null,
        ?int $daysDelete = null,
        string $author = 'Fristen-Cron'
    ): array {
        return SecurePortalRepository::processRetentionDeadlines($daysActive, $daysDelete, $author);
    }
}
