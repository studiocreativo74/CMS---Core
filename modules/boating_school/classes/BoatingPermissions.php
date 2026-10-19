<?php

declare(strict_types=1);

/**
 * BoatingPermissions
 *
 * Zentrale Registrierungs- und Prüfdatei für alle rollenbasierten Berechtigungen (RBAC)
 * des Moduls Bootsfahrschule (boating_school).
 *
 * Beinhaltet:
 * - Vollständige Liste aller Modul-Berechtigungen
 * - Konsistente Routen- und Aktionsprüfungen (ohne Doppelprüfungen)
 * - Datenschutz-Garantie: HR-Daten nur mit boating.coaches.hr_details
 * - Coach-Sicherheit: instructor_id wird für Coaches ausschliesslich über user_id ermittelt
 */
final class BoatingPermissions
{
    // Buchungen & Termine
    public const BOOKINGS_VIEW    = 'boating.bookings.view';
    public const BOOKINGS_MANAGE  = 'boating.bookings.manage';

    // Kalender
    public const CALENDAR_VIEW    = 'boating.calendar.view';
    public const CALENDAR_MANAGE  = 'boating.calendar.manage';

    // Kunden & CRM
    public const CUSTOMERS_VIEW   = 'boating.customers.view';
    public const CUSTOMERS_MANAGE = 'boating.customers.manage';
    public const SUBSCRIPTIONS_MANAGE = 'boating.subscriptions.manage';

    // Auswertungen & Payroll
    public const REPORTS_VIEW     = 'boating.reports.view';
    public const PAYROLL_VIEW     = 'boating.payroll.view';
    public const PAYROLL_MANAGE   = 'boating.payroll.manage';

    // Coaches / Fahrlehrer (Admin)
    public const COACHES_VIEW     = 'boating.coaches.view';
    public const COACHES_MANAGE   = 'boating.coaches.manage';
    public const COACHES_HR_DETAILS = 'boating.coaches.hr_details';

    // Coach Self-Service Portal
    public const COACH_PORTAL     = 'boating.coach.portal';
    public const COACH_AVAILABILITY = 'boating.coach.availability';
    public const TIMESHEETS_SUBMIT = 'boating.timesheets.submit';
    public const TIMESHEETS_APPROVE = 'boating.timesheets.approve';

    // Einstellungen
    public const SETTINGS_MANAGE  = 'boating.settings.manage';

    /**
     * Gibt alle Permissions des Moduls mit Beschreibungen zurück.
     *
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        return [
            self::BOOKINGS_VIEW        => 'Buchungen und Termine einsehen',
            self::BOOKINGS_MANAGE      => 'Buchungen und Termine erstellen, bearbeiten und stornieren',
            self::CALENDAR_VIEW        => 'Ausbildungs- und Belegungskalender einsehen',
            self::CALENDAR_MANAGE      => 'Termine im Kalender verschieben, anpassen und verwalten',
            self::CUSTOMERS_VIEW       => 'Fahrschüler- und Kundenkartei einsehen',
            self::CUSTOMERS_MANAGE     => 'Kunden anlegen, bearbeiten, archivieren oder anonymisieren',
            self::SUBSCRIPTIONS_MANAGE => 'Abonnemente und Mehrfachkarten verwalten und zuweisen',
            self::REPORTS_VIEW         => 'Berichte, Statistiken und Umsatzauswertungen einsehen',
            self::PAYROLL_VIEW         => 'Fahrlehrer-Monatsrapporte und Timesheets einsehen',
            self::PAYROLL_MANAGE       => 'Lohnabrechnungen verwalten, Tarife anpassen und Auszahlungen freigeben',
            self::COACHES_VIEW         => 'Fahrlehrer-Stammdaten einsehen',
            self::COACHES_MANAGE       => 'Fahrlehrer anlegen, bearbeiten und Lohnansätze konfigurieren',
            self::COACHES_HR_DETAILS   => 'Sensible Personaldaten (AHV-Nr., IBAN, Bankverbindung) einsehen/bearbeiten',
            self::COACH_PORTAL         => 'Zugang zum persönlichen Fahrlehrer-Portal',
            self::COACH_AVAILABILITY   => 'Eigene Arbeitszeiten, Wochenpläne und Absenzen erfassen',
            self::TIMESHEETS_SUBMIT    => 'Eigenen Monatsrapport/Timesheet zur Prüfung einreichen',
            self::TIMESHEETS_APPROVE   => 'Eingereichte Fahrlehrer-Monatsrapporte prüfen, genehmigen oder zurückweisen',
            self::SETTINGS_MANAGE      => 'Modul-Einstellungen, E-Mail-Templates und Sozialabzüge verwalten',
        ];
    }

    /**
     * Registriert alle Berechtigungen idempotent in der RBAC-Tabelle (falls vorhanden).
     */
    public static function registerAll(): void
    {
        if (!class_exists('DB')) {
            return;
        }

        try {
            // Prüfen ob permissions-Tabelle existiert
            $check = DB::fetchValue("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'");
            if ((int) $check === 0) {
                return;
            }

            foreach (self::getAll() as $key => $desc) {
                DB::execute(
                    "INSERT INTO `permissions` (`key`, `name`, `description`, `created_at`) 
                     VALUES (:key, :name, :desc, NOW())
                     ON DUPLICATE KEY UPDATE `description` = :desc_up",
                    [
                        'key'     => $key,
                        'name'    => $desc,
                        'desc'    => $desc,
                        'desc_up' => $desc,
                    ]
                );
            }
        } catch (\Throwable $e) {
            error_log('BoatingPermissions::registerAll Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Prüft, ob der aktuell eingeloggte Benutzer eine bestimmte Berechtigung besitzt.
     * Administratoren (Admin-Rolle oder is_admin) haben grundsätzlich vollen Zugriff.
     */
    public static function check(string $permission): bool
    {
        if (!class_exists('Auth') || !Auth::check()) {
            return false;
        }

        // 1. Core-Admin hat immer Vollzugriff
        if (class_exists('Auth') && method_exists('Auth', 'isAdmin') && Auth::isAdmin()) {
            return true;
        }
        if (class_exists('User') && method_exists('User', 'isAdmin') && User::isAdmin()) {
            return true;
        }

        // 2. Prüfung über RBAC-System
        if (class_exists('Rbac')) {
            return Rbac::can($permission);
        }

        return false;
    }

    /**
     * Erzwingt eine Berechtigung. Bricht mit HTTP 403 ab, falls nicht berechtigt.
     */
    public static function require(string $permission): void
    {
        if (!self::check($permission)) {
            if (class_exists('ActivityLog')) {
                ActivityLog::log('boating.access_denied', null, [
                    'required_permission' => $permission,
                    'route'               => $_GET['route'] ?? '/',
                ]);
            }
            http_response_code(403);
            if (file_exists(__DIR__ . '/../../../../views/errors/403.php')) {
                require __DIR__ . '/../../../../views/errors/403.php';
            } else {
                echo '<div style="font-family:sans-serif;padding:40px;text-align:center;">'
                   . '<h2>403 - Zugriff verweigert</h2>'
                   . '<p>Sie verfügen nicht über die erforderliche Berechtigung (<code>' . htmlspecialchars($permission) . '</code>).</p>'
                   . '<a href="?route=admin">Zurück zum Dashboard</a></div>';
            }
            exit;
        }
    }

    /**
     * Ermittelt die instructor_id für den aktuell eingeloggten Benutzer.
     * VERHINDERT IDOR: Ein Coach kann NIEMALS fremde Fahrlehrer-Daten über Parameter abrufen.
     */
    public static function getInstructorIdForCurrentUser(): ?int
    {
        if (!class_exists('Auth') || !Auth::check()) {
            return null;
        }

        $userId = class_exists('Auth') && method_exists('Auth', 'id') ? Auth::id() : ($_SESSION['user_id'] ?? null);
        $userEmail = class_exists('Auth') && method_exists('Auth', 'email') ? Auth::email() : ($_SESSION['magic_email'] ?? null);

        if (!$userId && !$userEmail) {
            return null;
        }

        // Suche in bs_instructors anhand user_id oder email
        if (class_exists('DB')) {
            try {
                if ($userId) {
                    $inst = DB::fetchOne(
                        'SELECT id FROM `bs_instructors` WHERE `user_id` = :uid AND `is_active` = 1 LIMIT 1',
                        ['uid' => (int) $userId]
                    );
                    if ($inst) {
                        return (int) $inst['id'];
                    }
                }

                if ($userEmail) {
                    $inst = DB::fetchOne(
                        'SELECT id FROM `bs_instructors` WHERE LOWER(`email`) = :email AND `is_active` = 1 LIMIT 1',
                        ['email' => strtolower(trim((string) $userEmail))]
                    );
                    if ($inst) {
                        return (int) $inst['id'];
                    }
                }
            } catch (\Throwable $e) {
                error_log('BoatingPermissions::getInstructorIdForCurrentUser Fehler: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Prüft, ob der aktuelle Benutzer berechtigt ist, sensible HR-Daten eines Fahrlehrers einzusehen.
     * Erlaubt nur:
     * - Administratoren mit boating.coaches.hr_details
     * - Den Coach selbst für seine EIGENEN HR-Daten
     */
    public static function canAccessHrDetails(int $targetInstructorId): bool
    {
        // 1. Hat globale HR-Berechtigung
        if (self::check(self::COACHES_HR_DETAILS)) {
            return true;
        }

        // 2. Ist der Fahrlehrer selbst
        $myInstId = self::getInstructorIdForCurrentUser();
        if ($myInstId !== null && $myInstId === $targetInstructorId) {
            return true;
        }

        return false;
    }
}
