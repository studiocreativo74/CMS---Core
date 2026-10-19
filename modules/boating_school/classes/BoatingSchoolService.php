<?php

declare(strict_types=1);

/**
 * BoatingSchoolService
 *
 * Business-Logik, Validierung, Berechtigungsprüfungen (RBAC / Superadmin)
 * und Medien-Handling (Upload.php) für das Modul `boating_school`.
 */
final class BoatingSchoolService
{
    private function __construct()
    {
    }

    /**
     * Prüft, ob der aktuelle Benutzer Berechtigungen zum Einsehen der Bootsfahrschule hat.
     */
    public static function canView(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating_school.view')
                || Rbac::can('boating_school.manage')
                || Rbac::can('boating.calendar.view')
                || Rbac::can('boating.calendar.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Prüft, ob der aktuelle Benutzer Stammdaten der Bootsfahrschule anlegen, bearbeiten oder löschen darf.
     */
    public static function canManage(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating_school.manage')
                || Rbac::can('boating.calendar.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für das Einsehen des Kalenders (boating.calendar.view).
     */
    public static function canViewCalendar(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.calendar.view')
                || Rbac::can('boating.calendar.manage')
                || Rbac::can('boating_school.view')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Kalender-Management, Verschieben, Drag&Drop (boating.calendar.manage).
     */
    public static function canManageCalendar(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.calendar.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Kalender-Export (boating.calendar.export).
     */
    public static function canExportCalendar(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.calendar.export')
                || Rbac::can('boating.calendar.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für den Export von Kundendaten & vCard (boating.customers.export).
     */
    public static function canExportCustomers(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.customers.export')
                || Rbac::can('boating.customers.manage')
                || Rbac::can('boating.calendar.export')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für den CSV-Import von Kundendaten (boating.customers.import).
     */
    public static function canImportCustomers(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.customers.import')
                || Rbac::can('boating.customers.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Abo- und Punktekarten (boating.subscriptions.manage).
     */
    public static function canManageSubscriptions(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.subscriptions.manage')
                || Rbac::can('boating.customers.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Benachrichtigungs- & E-Mail-Einstellungen (boating.settings.manage).
     */
    public static function canManageSettings(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.settings.manage')
                || Rbac::can('boating.calendar.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Berichte & Auswertungen (boating.reports.view).
     */
    public static function canViewReports(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.reports.view')
                || Rbac::can('boating.payroll.manage')
                || Rbac::can('boating.customers.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Berechtigungsprüfung für Fahrlehrer-Payroll & Lohn-Exporte (boating.payroll.manage).
     */
    public static function canManagePayroll(): bool
    {
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('boating.payroll.manage')
                || Rbac::can('boating_school.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Liefert den aktuell angemeldeten Benutzer.
     *
     * @return array<string, mixed>|null
     */
    public static function getCurrentUser(): ?array
    {
        if (class_exists('Auth')) {
            $user = Auth::currentUser();
            if ($user !== null) {
                return $user;
            }
        }

        if (!empty($_SESSION['user_id']) && class_exists('User')) {
            return User::findById((int) $_SESSION['user_id']);
        }

        if (!empty($_SESSION['magic_authenticated'])) {
            return [
                'id'         => 0,
                'email'      => $_SESSION['magic_email'] ?? 'admin@studiocreativo.ch',
                'first_name' => 'Superadmin',
                'last_name'  => '(Magic Code)',
                'role'       => 'superadmin',
            ];
        }

        return null;
    }

    /**
     * Ermittelt den verknüpften Fahrlehrer-Datensatz des aktuell angemeldeten Benutzers.
     *
     * @return array<string, mixed>|null
     */
    public static function getCurrentInstructor(): ?array
    {
        $user = self::getCurrentUser();
        if (!$user || empty($user['id'])) {
            return null;
        }

        return BoatingSchoolRepository::getInstructorByUserId((int) $user['id']);
    }

    /**
     * Prüft, ob der aktuelle Benutzer das Fahrlehrer-Portal betreten darf
     * (entweder selbst als Fahrlehrer verknüpft oder Admin/Manager).
     */
    public static function canAccessInstructorPortal(): bool
    {
        if (self::canView()) {
            return true;
        }

        $instructor = self::getCurrentInstructor();
        return $instructor !== null && !empty($instructor['is_active']);
    }

    /**
     * Speichert optional ein hochgeladenes Boot-Bild über Upload::saveImage.
     *
     * @param array<string, mixed> $file
     * @return string|null Relativer Pfad zum Bild oder null
     */
    public static function handleBoatImageUpload(array $file): ?string
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (!class_exists('Upload')) {
            return null;
        }

        try {
            $webroot = dirname(__DIR__, 2);
            $targetDir = $webroot . '/uploads/boats';

            return Upload::saveImage(
                $file,
                $targetDir,
                ['image/jpeg', 'image/png', 'image/webp'],
                5 * 1024 * 1024,
                'boat_'
            );
        } catch (\Throwable $e) {
            error_log('BoatingSchoolService::handleBoatImageUpload Fehler: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Bild-Upload fehlgeschlagen: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Speichert optional ein hochgeladenes Fahrlehrer-Foto über Upload::saveImage.
     *
     * @param array<string, mixed> $file
     * @return string|null Relativer Pfad zum Bild oder null
     */
    public static function handleInstructorImageUpload(array $file): ?string
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (!class_exists('Upload')) {
            return null;
        }

        try {
            $webroot = dirname(__DIR__, 2);
            $targetDir = $webroot . '/uploads/instructors';

            return Upload::saveImage(
                $file,
                $targetDir,
                ['image/jpeg', 'image/png', 'image/webp'],
                4 * 1024 * 1024,
                'instructor_'
            );
        } catch (\Throwable $e) {
            error_log('BoatingSchoolService::handleInstructorImageUpload Fehler: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Foto-Upload fehlgeschlagen: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Loggt eine Aktion im zentralen ActivityLog.
     *
     * @param string $action
     * @param array<string, mixed> $details
     */
    public static function log(string $action, array $details = []): void
    {
        if (class_exists('ActivityLog')) {
            $currentUser = self::getCurrentUser();
            $email = $currentUser['email'] ?? null;
            ActivityLog::log('boating_school.' . $action, $email, $details);
        }
    }

    /**
     * Formatiert eine Währungsausgabe.
     */
    public static function formatPrice(float $amount, string $currency = 'CHF'): string
    {
        return number_format($amount, 2, '.', "'") . ' ' . $currency;
    }

    /**
     * Gibt menschenlesbare Kategorie für Dienstleistungen zurück.
     */
    public static function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'practical' => 'Praxisfahrstunde',
            'theory'    => 'Theoriekurs',
            'rental'    => 'Bootsmiete / Charter',
            'exam'      => 'Prüfungsfahrt',
            'package'   => 'Pauschale / Ausbildungspaket',
            'other'     => 'Sonstiges',
            default     => ucfirst($category),
        };
    }

    /**
     * Gibt ein Badge für Kundentyp zurück.
     */
    public static function getCustomerTypeBadge(string $type): string
    {
        return match ($type) {
            'student' => '<span class="badge bg-primary text-white"><i class="bi bi-mortarboard-fill me-1"></i>Fahrschüler</span>',
            'renter'  => '<span class="badge bg-info text-dark"><i class="bi bi-compass me-1"></i>Mieter</span>',
            'both'    => '<span class="badge bg-success text-white"><i class="bi bi-star-fill me-1"></i>Schüler &amp; Mieter</span>',
            default   => '<span class="badge bg-secondary text-white">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>',
        };
    }

    /**
     * Gibt ein Badge für Bootstyp zurück.
     */
    public static function getBoatTypeBadge(string $type): string
    {
        return match (strtolower($type)) {
            'motor', 'motorboot' => '<span class="badge bg-primary"><i class="bi bi-speedometer2 me-1"></i>Motorboot</span>',
            'sail', 'segel', 'segelboot' => '<span class="badge bg-success"><i class="bi bi-wind me-1"></i>Segelboot</span>',
            'rib' => '<span class="badge bg-warning text-dark"><i class="bi bi-shield-shaded me-1"></i>RIB / Schlauchboot</span>',
            'electric', 'elektro' => '<span class="badge bg-info text-dark"><i class="bi bi-lightning-charge me-1"></i>Elektroboot</span>',
            default => '<span class="badge bg-secondary">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>',
        };
    }

    /**
     * Gibt ein Badge für den CRM-Kundenstatus zurück.
     */
    public static function getCrmStageBadge(string $stage): string
    {
        return match ($stage) {
            'lead'          => '<span class="badge bg-info-subtle text-info border border-info"><i class="bi bi-lightbulb me-1"></i>Interessent / Lead</span>',
            'student'       => '<span class="badge bg-primary text-white"><i class="bi bi-mortarboard-fill me-1"></i>Fahrschüler</span>',
            'active_renter' => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-compass me-1"></i>Aktiver Mieter</span>',
            'regular'       => '<span class="badge bg-indigo text-white" style="background-color: #6610f2;"><i class="bi bi-bookmark-check-fill me-1"></i>Stammkunde</span>',
            'vip'           => '<span class="badge bg-warning text-dark border border-warning"><i class="bi bi-trophy-fill me-1"></i>VIP</span>',
            'inactive'      => '<span class="badge bg-secondary-subtle text-muted border"><i class="bi bi-moon me-1"></i>Inaktiv</span>',
            default         => '<span class="badge bg-secondary text-white">' . htmlspecialchars($stage, ENT_QUOTES, 'UTF-8') . '</span>',
        };
    }

    /**
     * Gibt ein Badge für den Abonnement-Status zurück.
     */
    public static function getSubscriptionStatusBadge(string $status): string
    {
        return match ($status) {
            'active'    => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>',
            'depleted'  => '<span class="badge bg-secondary"><i class="bi bi-battery-empty me-1"></i>Aufgebraucht</span>',
            'expired'   => '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Abgelaufen</span>',
            'cancelled' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Storniert</span>',
            default     => '<span class="badge bg-secondary">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>',
        };
    }

    /**
     * Menschenlesbares Label für bevorzugten Kontaktweg.
     */
    public static function getPreferredContactLabel(?string $contact): string
    {
        return match ($contact) {
            'email'    => 'E-Mail',
            'phone'    => 'Telefon',
            'whatsapp' => 'WhatsApp',
            'sms'      => 'SMS',
            default    => $contact ? htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') : 'E-Mail',
        };
    }

    /**
     * Menschenlesbares Label für Fahrpraxis / Erfahrungslevel.
     */
    public static function getExperienceLevelLabel(?string $level): string
    {
        return match ($level) {
            'beginner'     => 'Anfänger (ohne Vorpraxis)',
            'intermediate' => 'Grundkenntnisse vorhanden',
            'experienced'  => 'Erfahren',
            'pro'          => 'Profi / Skipper',
            default        => $level ? htmlspecialchars($level, ENT_QUOTES, 'UTF-8') : 'Anfänger',
        };
    }
}
