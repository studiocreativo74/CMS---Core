<?php

declare(strict_types=1);

/**
 * PortalService
 *
 * Business-Logic-Layer für das Eigentümer- & Hausverwaltungsportal.
 * Handhabt Autorisierung, RBAC-Rollen, Schadenmeldungs-Workflows
 * und die Verknüpfung mit dem DMS-Modul (`documents`).
 */
final class PortalService
{
    public const CASE_TYPES = [
        'property' => [
            'label' => 'Gebäudeakte / Allgemein',
            'color' => '#0d6efd',
            'icon'  => 'building',
            'desc'  => 'Allgemeine Liegenschaftsangelegenheiten, Wartungsverträge, Versicherungen',
        ],
        'meeting'  => [
            'label' => 'Eigentümerversammlung',
            'color' => '#6f42c1',
            'icon'  => 'people',
            'desc'  => 'Einladungen, Tagesordnungen, Protokolle & Beschlusssammlung',
        ],
        'damage'   => [
            'label' => 'Schadenmeldung / Reparatur',
            'color' => '#dc3545',
            'icon'  => 'tools',
            'desc'  => 'Mängel, Schadensfälle, Handwerkeraufträge & Instandsetzungen',
        ],
    ];

    public const CASE_STATUSES = [
        'new'              => ['label' => 'Neu eingegangen', 'badge' => 'bg-primary text-white'],
        'in_progress'      => ['label' => 'In Bearbeitung', 'badge' => 'bg-warning text-dark'],
        'waiting_external' => ['label' => 'Wartet auf Handwerker / extern', 'badge' => 'bg-info text-dark'],
        'resolved'         => ['label' => 'Behoben / Erledigt', 'badge' => 'bg-success text-white'],
        'closed'           => ['label' => 'Geschlossen', 'badge' => 'bg-secondary text-white'],
    ];

    public const PRIORITIES = [
        'low'    => ['label' => 'Niedrig', 'badge' => 'bg-light text-muted border'],
        'normal' => ['label' => 'Normal', 'badge' => 'bg-light text-dark border'],
        'high'   => ['label' => 'Hoch', 'badge' => 'bg-warning text-dark'],
        'urgent' => ['label' => 'Dringend / Notfall', 'badge' => 'bg-danger text-white'],
    ];

    public const RELATION_TYPES = [
        'owner'            => 'Eigentümer',
        'tenant'           => 'Mieter',
        'advisory_board'   => 'Verwaltungsbeirat',
        'property_manager' => 'Hausverwalter',
        'external'         => 'Dienstleister / Handwerker',
    ];

    public const UNIT_TYPES = [
        'apartment'  => 'Wohnung',
        'commercial' => 'Gewerbe / Büro',
        'parking'    => 'Stellplatz / Garage',
        'storage'    => 'Keller / Lager',
        'garden'     => 'Garten / Sondernutzung',
        'other'      => 'Sonstiges',
    ];

    private function __construct()
    {
    }

    // =========================================================================
    // 1. AUTORISIERUNG & ROLLENPRÜFUNG
    // =========================================================================

    public static function getCurrentUserId(): ?int
    {
        if (class_exists('Auth') && Auth::check()) {
            $user = Auth::user();
            return isset($user['id']) ? (int) $user['id'] : null;
        }

        if (!empty($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Prüft, ob der aktuelle Benutzer administrative Rechte für Liegenschaften hat.
     */
    public static function canManageProperties(): bool
    {
        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.properties.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = class_exists('Auth') ? Auth::user() : null;
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der aktuelle Benutzer Einheiten & Zuweisungen verwalten darf.
     */
    public static function canManageUnits(): bool
    {
        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.units.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = class_exists('Auth') ? Auth::user() : null;
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der aktuelle Benutzer alle Cases administrativ verwalten darf.
     */
    public static function canManageCases(): bool
    {
        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.cases.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = class_exists('Auth') ? Auth::user() : null;
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der Benutzer berechtigt ist, das Eigentümerportal aufzurufen.
     */
    public static function canAccessPortal(): bool
    {
        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Auth') && !Auth::check()) {
            return false;
        }

        if (class_exists('Rbac') && Rbac::can('portal.view')) {
            return true;
        }

        $user = class_exists('Auth') ? Auth::user() : null;
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager', 'owner', 'tenant', 'advisory_board', 'external'], true);
    }

    /**
     * Prüft, ob der Benutzer eine Schadenmeldung erfassen darf.
     */
    public static function canCreateDamage(): bool
    {
        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        if (class_exists('Auth') && !Auth::check()) {
            return false;
        }

        if (class_exists('Rbac') && Rbac::can('portal.damage.create')) {
            return true;
        }

        return true; // Jeder angemeldete Portalnutzer (Eigentümer, Mieter, Verwaltung) kann Schäden melden
    }

    // =========================================================================
    // 2. WORKFLOW: SCHADENMELDUNG ERFASSEN (MIT DMS-INTEGRATION)
    // =========================================================================

    /**
     * Erfasst eine neue Schadenmeldung inklusive optionalem Foto-Upload ins DMS.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $file
     * @param int|null $userId
     * @return int Case-ID
     * @throws \RuntimeException
     */
    public static function handleCreateDamageReport(array $post, ?array $file = null, ?int $userId = null): int
    {
        $propertyId = (int) ($post['property_id'] ?? 0);
        $title = trim((string) ($post['title'] ?? ''));

        if ($propertyId <= 0 || $title === '') {
            throw new \RuntimeException('Bitte wählen Sie eine Liegenschaft und geben Sie einen Titel für die Schadenmeldung an.');
        }

        $unitId = !empty($post['unit_id']) ? (int) $post['unit_id'] : null;
        $description = trim((string) ($post['description'] ?? ''));
        $damageLocation = trim((string) ($post['damage_location'] ?? ''));
        $priority = in_array($post['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true) ? (string) $post['priority'] : 'normal';

        $caseData = [
            'property_id'      => $propertyId,
            'unit_id'          => $unitId,
            'case_type'        => 'damage',
            'title'            => $title,
            'description'      => $description,
            'status'           => 'new',
            'priority'         => $priority,
            'creator_user_id'  => $userId,
            'damage_location'  => $damageLocation,
        ];

        $caseId = PortalRepository::createCase($caseData);
        if ($caseId <= 0) {
            throw new \RuntimeException('Der Schadensfall konnte nicht in der Datenbank gespeichert werden.');
        }

        // Falls ein Dokument/Foto angehängt wurde: Im DMS hochladen und mit dem Case verknüpfen!
        if ($file !== null && !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            try {
                if (class_exists('DocumentService')) {
                    $docData = [
                        'title'            => 'Schadensfoto: ' . $title,
                        'doc_type'         => 'PROTOCOL',
                        'visibility'       => 'owner_portal',
                        'target_type'      => 'case',
                        'target_id'        => (string) $caseId,
                        'target_label'     => 'Schaden #' . $caseId . ': ' . $title,
                        'relation_role'    => 'attachment',
                        'new_tags_csv'     => 'Schadenmeldung, Reparatur',
                    ];

                    DocumentService::handleUploadNewDocument($docData, $file);
                }
            } catch (\Throwable $e) {
                error_log('Schadensfoto konnte nicht im DMS hinterlegt werden: ' . $e->getMessage());
            }
        }

        // Initiale System-/Bestätigungsnachricht an den Vorgang
        $welcomeMsg = "Schadenmeldung wurde über das Portal eingereicht. Status: Neu eingegangen.";
        PortalRepository::addCaseMessage($caseId, $userId, $welcomeMsg, false);

        return $caseId;
    }

    // =========================================================================
    // 3. DMS-INTEGRATION: DOKUMENTE FÜR LIEGENSCHAFTEN, EINHEITEN & CASES
    // =========================================================================

    /**
     * Liefert alle Dokumente aus dem DMS, die mit dem Zielobjekt verknüpft sind.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getLinkedDocuments(string $targetType, int $targetId, ?string $visibilityFilter = null): array
    {
        if (!class_exists('DocumentRepository')) {
            return [];
        }

        return DocumentRepository::getDocumentsForTarget($targetType, (string) $targetId, $visibilityFilter);
    }

    /**
     * Verknüpft ein existierendes DMS-Dokument mit einer Liegenschaft, Einheit oder einem Case.
     */
    public static function linkDocument(int $documentId, string $targetType, int $targetId, ?string $label = null, string $role = 'attachment'): bool
    {
        if (!class_exists('DocumentRepository') || $documentId <= 0 || $targetId <= 0) {
            return false;
        }

        $res = DocumentRepository::addRelation($documentId, $targetType, (string) $targetId, $label, $role);
        return $res > 0;
    }
}
