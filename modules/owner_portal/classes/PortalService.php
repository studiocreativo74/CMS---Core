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

    /**
     * Liefert die Daten des aktuell angemeldeten Benutzers (auch Magic-Admin).
     *
     * @return array<string, mixed>|null
     */
    public static function getCurrentUser(): ?array
    {
        if (class_exists('Auth')) {
            $user = Auth::user();
            if ($user !== null) {
                return $user;
            }
        }

        if (!empty($_SESSION['user_id'])) {
            try {
                $user = DB::fetchOne('SELECT * FROM `users` WHERE `id` = :id AND `is_active` = 1 LIMIT 1', [
                    'id' => (int) $_SESSION['user_id'],
                ]);
                if ($user) {
                    return $user;
                }
            } catch (\Throwable $e) {
                // Ignore DB error
            }
        }

        if (!empty($_SESSION['magic_authenticated'])) {
            return [
                'id'         => 0,
                'name'       => 'Magic-Admin',
                'email'      => (string) ($_SESSION['magic_email'] ?? 'admin@magic-code'),
                'is_magic'   => true,
                'role'       => 'admin',
                'theme_mode' => (string) ($_SESSION['theme_mode'] ?? 'system'),
            ];
        }

        return null;
    }

    public static function getCurrentUserId(): ?int
    {
        $user = self::getCurrentUser();
        if ($user !== null && isset($user['id'])) {
            return (int) $user['id'];
        }

        return null;
    }

    public static function isMagicAdmin(): bool
    {
        return (class_exists('Auth') && Auth::checkMagic()) || !empty($_SESSION['magic_authenticated']);
    }

    /**
     * Prüft, ob der aktuelle Benutzer administrative Rechte für Liegenschaften hat.
     */
    public static function canManageProperties(): bool
    {
        if (self::isMagicAdmin()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.properties.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = self::getCurrentUser();
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der aktuelle Benutzer Liegenschaften ansehen darf.
     */
    public static function canViewProperties(): bool
    {
        if (self::canManageProperties()) {
            return true;
        }

        if (class_exists('Rbac') && (Rbac::can('portal.properties.manage') || Rbac::can('portal.view'))) {
            return true;
        }

        $user = self::getCurrentUser();
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager', 'advisory_board'], true);
    }

    /**
     * Prüft, ob der Benutzer Zugriff auf eine spezifische Liegenschaft hat.
     */
    public static function canAccessProperty(int $propertyId, ?int $userId = null): bool
    {
        if (self::canManageProperties()) {
            return true;
        }

        if ($userId === null) {
            $userId = self::getCurrentUserId();
        }

        if ($userId === null || $userId <= 0) {
            return false;
        }

        $userProps = PortalRepository::getUserProperties($userId);
        foreach ($userProps as $p) {
            if ((int) $p['id'] === $propertyId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft, ob der aktuelle Benutzer Einheiten & Zuweisungen verwalten darf.
     */
    public static function canManageUnits(): bool
    {
        if (self::isMagicAdmin()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.units.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = self::getCurrentUser();
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der aktuelle Benutzer alle Cases administrativ verwalten darf.
     */
    public static function canManageCases(): bool
    {
        if (self::isMagicAdmin()) {
            return true;
        }

        if (class_exists('Rbac')) {
            if (Rbac::can('portal.cases.manage') || Rbac::can('admin.portal.manage')) {
                return true;
            }
        }

        $user = self::getCurrentUser();
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager'], true);
    }

    /**
     * Prüft, ob der aktuelle Benutzer Vorgänge einsehen darf.
     */
    public static function canViewCases(): bool
    {
        return self::canManageCases() || self::canAccessPortal();
    }

    /**
     * Prüft, ob der Benutzer Zugriff auf einen spezifischen Case hat.
     */
    public static function canAccessCase(int $caseId, ?int $userId = null): bool
    {
        if (self::canManageCases()) {
            return true;
        }

        if ($userId === null) {
            $userId = self::getCurrentUserId();
        }

        if ($userId === null || $userId <= 0) {
            return false;
        }

        $case = PortalRepository::getCase($caseId);
        if ($case === null) {
            return false;
        }

        if ((int) ($case['creator_user_id'] ?? 0) === $userId) {
            return true;
        }

        return self::canAccessProperty((int) $case['property_id'], $userId);
    }

    /**
     * Prüft, ob der Benutzer berechtigt ist, das Eigentümerportal aufzurufen.
     */
    public static function canAccessPortal(): bool
    {
        if (self::isMagicAdmin()) {
            return true;
        }

        if (class_exists('Auth') && !Auth::check()) {
            return false;
        }

        if (class_exists('Rbac') && Rbac::can('portal.view')) {
            return true;
        }

        $user = self::getCurrentUser();
        $role = (string) ($user['role'] ?? '');
        return in_array($role, ['admin', 'superadmin', 'property_manager', 'owner', 'tenant', 'advisory_board', 'external'], true);
    }

    /**
     * Prüft, ob der Benutzer eine Schadenmeldung erfassen darf.
     */
    public static function canCreateDamage(): bool
    {
        if (self::isMagicAdmin()) {
            return true;
        }

        if (class_exists('Auth') && !Auth::check()) {
            return false;
        }

        if (class_exists('Rbac') && Rbac::can('portal.damage.create')) {
            return true;
        }

        return true;
    }

    // =========================================================================
    // 2. CRUD-SERVICES FÜR LIEGENSCHAFTEN, EINHEITEN & VORGÄNGE
    // =========================================================================

    public static function createProperty(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Bitte geben Sie einen Namen für die Liegenschaft an.');
        }

        $id = PortalRepository::createProperty($data);
        if ($id <= 0) {
            throw new \RuntimeException('Die Liegenschaft konnte nicht angelegt werden.');
        }

        return $id;
    }

    public static function updateProperty(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Ungültige Liegenschafts-ID.');
        }

        return PortalRepository::updateProperty($id, $data);
    }

    public static function createUnit(array $data): int
    {
        $propertyId = (int) ($data['property_id'] ?? 0);
        $unitNumber = trim((string) ($data['unit_number'] ?? ''));

        if ($propertyId <= 0 || $unitNumber === '') {
            throw new \InvalidArgumentException('Liegenschaft und Einheiten-Nummer sind Pflichtangaben.');
        }

        $id = PortalRepository::createUnit($data);
        if ($id <= 0) {
            throw new \RuntimeException('Die Einheit konnte nicht angelegt werden.');
        }

        return $id;
    }

    public static function updateUnit(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Ungültige Einheiten-ID.');
        }

        return PortalRepository::updateUnit($id, $data);
    }

    public static function assignUserToUnit(int $unitId, int $userId, string $relationType = 'owner'): int
    {
        if ($unitId <= 0 || $userId <= 0) {
            throw new \InvalidArgumentException('Einheit und Benutzer müssen ausgewählt werden.');
        }

        $id = PortalRepository::assignUserToUnit($unitId, $userId, $relationType);
        if ($id <= 0) {
            throw new \RuntimeException('Der Benutzer konnte der Einheit nicht zugewiesen werden.');
        }

        return $id;
    }

    public static function createCase(array $data): int
    {
        $propertyId = (int) ($data['property_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));

        if ($propertyId <= 0 || $title === '') {
            throw new \InvalidArgumentException('Liegenschaft und Titel sind Pflichtangaben.');
        }

        if (empty($data['creator_user_id'])) {
            $data['creator_user_id'] = self::getCurrentUserId();
        }

        $id = PortalRepository::createCase($data);
        if ($id <= 0) {
            throw new \RuntimeException('Der Vorgang konnte nicht angelegt werden.');
        }

        return $id;
    }

    public static function updateCaseStatus(int $caseId, string $status, string $priority): bool
    {
        $case = PortalRepository::getCase($caseId);
        if ($case === null) {
            throw new \RuntimeException('Vorgang nicht gefunden.');
        }

        $data = $case;
        $data['status'] = $status;
        $data['priority'] = $priority;

        return PortalRepository::updateCase($caseId, $data);
    }

    public static function addCaseMessage(int $caseId, string $message, bool $isInternal = false): int
    {
        if (trim($message) === '') {
            throw new \InvalidArgumentException('Die Nachricht darf nicht leer sein.');
        }

        $userId = self::getCurrentUserId();
        $id = PortalRepository::addCaseMessage($caseId, $userId, $message, $isInternal);
        if ($id <= 0) {
            throw new \RuntimeException('Die Nachricht konnte nicht gespeichert werden.');
        }

        return $id;
    }

    public static function submitDamageReport(array $post, ?array $file = null): int
    {
        $userId = self::getCurrentUserId();
        return self::handleCreateDamageReport($post, $file, $userId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getUserCases(int $userId): array
    {
        if (self::canManageCases()) {
            return PortalRepository::getCases([], 50);
        }

        $props = PortalRepository::getUserProperties($userId);
        $propIds = array_column($props, 'id');

        if (empty($propIds)) {
            return PortalRepository::getCases(['creator_user_id' => $userId], 50);
        }

        return PortalRepository::getCases(['allowed_property_ids' => $propIds], 50);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDocumentsForEntity(string $entityType, int $entityId): array
    {
        return self::getLinkedDocuments($entityType, $entityId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getUserDocuments(int $userId, array $filters = []): array
    {
        if (!class_exists('DocumentRepository')) {
            return [];
        }

        if (self::canManageProperties()) {
            return DocumentRepository::getDocuments($filters, 100);
        }

        $props = PortalRepository::getUserProperties($userId);
        $units = PortalRepository::getUserUnits($userId);

        $docMap = [];

        foreach ($props as $p) {
            $docs = DocumentRepository::getDocumentsForTarget('property', (string) $p['id']);
            foreach ($docs as $d) {
                $vis = (string) ($d['visibility'] ?? 'internal');
                if (in_array($vis, ['public', 'portal', 'owner_portal'], true)) {
                    $docMap[$d['id']] = $d;
                }
            }
        }

        foreach ($units as $u) {
            $unitId = (int) ($u['unit_id'] ?? $u['id'] ?? 0);
            if ($unitId > 0) {
                $docs = DocumentRepository::getDocumentsForTarget('unit', (string) $unitId);
                foreach ($docs as $d) {
                    $vis = (string) ($d['visibility'] ?? 'internal');
                    if (in_array($vis, ['public', 'portal', 'owner_portal'], true)) {
                        $docMap[$d['id']] = $d;
                    }
                }
            }
        }

        return array_values($docMap);
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
