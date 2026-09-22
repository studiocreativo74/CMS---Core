<?php

declare(strict_types=1);

/**
 * PortalRepository
 *
 * Data-Access-Layer für das Eigentümer- & Hausverwaltungsportal.
 * Verwaltet Liegenschaften (properties), Einheiten (units), Nutzerzuordnungen
 * (unit_users), Vorgänge/Cases und Nachrichten via PDO (`DB.php`).
 */
final class PortalRepository
{
    private static ?bool $tableExists = null;

    private function __construct()
    {
    }

    /**
     * Prüft, ob die Tabelle `properties` in der Datenbank existiert.
     */
    public static function isTableCreated(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'properties'");
            self::$tableExists = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            error_log('PortalRepository::isTableCreated Fehler: ' . $e->getMessage());
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    // =========================================================================
    // 1. LIEGENSCHAFTEN (PROPERTIES)
    // =========================================================================

    /**
     * Gibt Liegenschaften mit aggregierter Anzahl an Einheiten und offenen Vorgängen zurück.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getProperties(?string $search = null, int $limit = 50, int $offset = 0): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if ($search !== null && trim($search) !== '') {
                $where[] = '(p.`name` LIKE :search OR p.`street` LIKE :search OR p.`city` LIKE :search OR p.`external_ref` LIKE :search)';
                $params['search'] = '%' . trim($search) . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT p.*,
                           (SELECT COUNT(*) FROM `units` u WHERE u.`property_id` = p.`id`) AS `unit_count`,
                           (SELECT COUNT(*) FROM `cases` c WHERE c.`property_id` = p.`id` AND c.`status` != 'closed') AS `open_cases_count`
                    FROM `properties` p
                    {$whereSql}
                    ORDER BY p.`name` ASC
                    LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getProperties Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countProperties(?string $search = null): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if ($search !== null && trim($search) !== '') {
                $where[] = '(p.`name` LIKE :search OR p.`street` LIKE :search OR p.`city` LIKE :search OR p.`external_ref` LIKE :search)';
                $params['search'] = '%' . trim($search) . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT COUNT(*) AS `cnt` FROM `properties` p {$whereSql}";
            $row = DB::fetchOne($sql, $params);
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            error_log('PortalRepository::countProperties Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getProperty(int $id): ?array
    {
        if (!self::isTableCreated() || $id <= 0) {
            return null;
        }

        try {
            $sql = "SELECT p.*,
                           (SELECT COUNT(*) FROM `units` u WHERE u.`property_id` = p.`id`) AS `unit_count`,
                           (SELECT COUNT(*) FROM `cases` c WHERE c.`property_id` = p.`id`) AS `total_cases_count`
                    FROM `properties` p
                    WHERE p.`id` = :id
                    LIMIT 1";

            return DB::fetchOne($sql, ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getProperty Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createProperty(array $data): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            DB::insert(
                'INSERT INTO `properties` (`name`, `street`, `zip`, `city`, `country`, `external_ref`, `notes`, `created_at`, `updated_at`)
                 VALUES (:name, :street, :zip, :city, :country, :external_ref, :notes, NOW(), NOW())',
                [
                    'name'         => trim((string) ($data['name'] ?? '')),
                    'street'       => !empty($data['street']) ? trim((string) $data['street']) : null,
                    'zip'          => !empty($data['zip']) ? trim((string) $data['zip']) : null,
                    'city'         => !empty($data['city']) ? trim((string) $data['city']) : null,
                    'country'      => !empty($data['country']) ? trim((string) $data['country']) : 'Deutschland',
                    'external_ref' => !empty($data['external_ref']) ? trim((string) $data['external_ref']) : null,
                    'notes'        => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('PortalRepository::createProperty Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function updateProperty(int $id, array $data): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            DB::update(
                'UPDATE `properties` 
                 SET `name` = :name,
                     `street` = :street,
                     `zip` = :zip,
                     `city` = :city,
                     `country` = :country,
                     `external_ref` = :external_ref,
                     `notes` = :notes,
                     `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    'id'           => $id,
                    'name'         => trim((string) ($data['name'] ?? '')),
                    'street'       => !empty($data['street']) ? trim((string) $data['street']) : null,
                    'zip'          => !empty($data['zip']) ? trim((string) $data['zip']) : null,
                    'city'         => !empty($data['city']) ? trim((string) $data['city']) : null,
                    'country'      => !empty($data['country']) ? trim((string) $data['country']) : 'Deutschland',
                    'external_ref' => !empty($data['external_ref']) ? trim((string) $data['external_ref']) : null,
                    'notes'        => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::updateProperty Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteProperty(int $id): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            DB::delete('DELETE FROM `properties` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::deleteProperty Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 2. EINHEITEN (UNITS)
    // =========================================================================

    /**
     * Liefert alle Einheiten einer Liegenschaft inklusive Benutzerzuordnungs-Zähler.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUnitsForProperty(int $propertyId): array
    {
        if (!self::isTableCreated() || $propertyId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT u.*,
                           (SELECT COUNT(*) FROM `unit_users` uu WHERE uu.`unit_id` = u.`id`) AS `user_count`,
                           (SELECT COUNT(*) FROM `cases` c WHERE c.`unit_id` = u.`id` AND c.`status` != 'closed') AS `open_cases_count`
                    FROM `units` u
                    WHERE u.`property_id` = :property_id
                    ORDER BY u.`unit_number` ASC";

            return DB::fetchAll($sql, ['property_id' => $propertyId]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getUnitsForProperty Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function getUnit(int $id): ?array
    {
        if (!self::isTableCreated() || $id <= 0) {
            return null;
        }

        try {
            $sql = "SELECT u.*,
                           p.`name` AS `property_name`,
                           p.`street` AS `property_street`,
                           p.`zip` AS `property_zip`,
                           p.`city` AS `property_city`
                    FROM `units` u
                    JOIN `properties` p ON u.`property_id` = p.`id`
                    WHERE u.`id` = :id
                    LIMIT 1";

            return DB::fetchOne($sql, ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getUnit Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createUnit(array $data): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            DB::insert(
                'INSERT INTO `units` (`property_id`, `unit_number`, `floor`, `size_sqm`, `mea`, `type`, `notes`, `created_at`, `updated_at`)
                 VALUES (:property_id, :unit_number, :floor, :size_sqm, :mea, :type, :notes, NOW(), NOW())',
                [
                    'property_id' => (int) $data['property_id'],
                    'unit_number' => trim((string) ($data['unit_number'] ?? '')),
                    'floor'       => !empty($data['floor']) ? trim((string) $data['floor']) : null,
                    'size_sqm'    => !empty($data['size_sqm']) ? (float) $data['size_sqm'] : null,
                    'mea'         => !empty($data['mea']) ? (float) $data['mea'] : null,
                    'type'        => !empty($data['type']) ? trim((string) $data['type']) : 'apartment',
                    'notes'       => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('PortalRepository::createUnit Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function updateUnit(int $id, array $data): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            DB::update(
                'UPDATE `units`
                 SET `unit_number` = :unit_number,
                     `floor` = :floor,
                     `size_sqm` = :size_sqm,
                     `mea` = :mea,
                     `type` = :type,
                     `notes` = :notes,
                     `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    'id'          => $id,
                    'unit_number' => trim((string) ($data['unit_number'] ?? '')),
                    'floor'       => !empty($data['floor']) ? trim((string) $data['floor']) : null,
                    'size_sqm'    => !empty($data['size_sqm']) ? (float) $data['size_sqm'] : null,
                    'mea'         => !empty($data['mea']) ? (float) $data['mea'] : null,
                    'type'        => !empty($data['type']) ? trim((string) $data['type']) : 'apartment',
                    'notes'       => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::updateUnit Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteUnit(int $id): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            DB::delete('DELETE FROM `units` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::deleteUnit Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 3. NUTZERZUORDNUNG ZU EINHEITEN (UNIT_USERS)
    // =========================================================================

    /**
     * Liefert alle Benutzer-Zuweisungen einer Einheit mit Namen & E-Mail.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUnitUsers(int $unitId): array
    {
        if (!self::isTableCreated() || $unitId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT uu.*,
                           u.`name` AS `user_name`,
                           u.`email` AS `user_email`,
                           u.`role` AS `user_role`
                    FROM `unit_users` uu
                    JOIN `users` u ON uu.`user_id` = u.`id`
                    WHERE uu.`unit_id` = :unit_id
                    ORDER BY uu.`relation_type` ASC, uu.`created_at` DESC";

            return DB::fetchAll($sql, ['unit_id' => $unitId]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getUnitUsers Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function assignUserToUnit(
        int $unitId,
        int $userId,
        string $relationType = 'owner',
        ?string $validFrom = null,
        ?string $validTo = null,
        ?string $notes = null
    ): int {
        if (!self::isTableCreated() || $unitId <= 0 || $userId <= 0) {
            return 0;
        }

        try {
            DB::insert(
                'INSERT INTO `unit_users` (`unit_id`, `user_id`, `relation_type`, `valid_from`, `valid_to`, `notes`, `created_at`, `updated_at`)
                 VALUES (:unit_id, :user_id, :relation_type, :valid_from, :valid_to, :notes, NOW(), NOW())',
                [
                    'unit_id'       => $unitId,
                    'user_id'       => $userId,
                    'relation_type' => $relationType,
                    'valid_from'    => !empty($validFrom) ? $validFrom : null,
                    'valid_to'      => !empty($validTo) ? $validTo : null,
                    'notes'         => !empty($notes) ? $notes : null,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('PortalRepository::assignUserToUnit Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function removeUserFromUnit(int $assignmentId): bool
    {
        if (!self::isTableCreated() || $assignmentId <= 0) {
            return false;
        }

        try {
            DB::delete('DELETE FROM `unit_users` WHERE `id` = :id', ['id' => $assignmentId]);
            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::removeUserFromUnit Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liefert alle Einheiten, denen ein bestimmter Benutzer zugeordnet ist.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUserUnits(int $userId): array
    {
        if (!self::isTableCreated() || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT uu.`id` AS `assignment_id`,
                           uu.`relation_type`,
                           uu.`valid_from`,
                           uu.`valid_to`,
                           u.`id` AS `unit_id`,
                           u.`unit_number`,
                           u.`floor`,
                           u.`size_sqm`,
                           u.`mea`,
                           u.`type` AS `unit_type`,
                           p.`id` AS `property_id`,
                           p.`name` AS `property_name`,
                           p.`street` AS `property_street`,
                           p.`zip` AS `property_zip`,
                           p.`city` AS `property_city`
                    FROM `unit_users` uu
                    JOIN `units` u ON uu.`unit_id` = u.`id`
                    JOIN `properties` p ON u.`property_id` = p.`id`
                    WHERE uu.`user_id` = :user_id
                    ORDER BY p.`name` ASC, u.`unit_number` ASC";

            return DB::fetchAll($sql, ['user_id' => $userId]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getUserUnits Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Liefert eindeutige Liegenschaften, an denen der Benutzer über Einheiten beteiligt ist.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUserProperties(int $userId): array
    {
        if (!self::isTableCreated() || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT DISTINCT p.*,
                           (SELECT COUNT(*) FROM `units` u2 JOIN `unit_users` uu2 ON u2.`id` = uu2.`unit_id` WHERE u2.`property_id` = p.`id` AND uu2.`user_id` = :uid1) AS `my_unit_count`
                    FROM `properties` p
                    JOIN `units` u ON p.`id` = u.`property_id`
                    JOIN `unit_users` uu ON u.`id` = uu.`unit_id`
                    WHERE uu.`user_id` = :uid2
                    ORDER BY p.`name` ASC";

            return DB::fetchAll($sql, ['uid1' => $userId, 'uid2' => $userId]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getUserProperties Fehler: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // 4. CASES (VORGÄNGE / GEBÄUDEAKTE, VERSAMMLUNGEN, SCHADENFÄLLE)
    // =========================================================================

    /**
     * Liefert Cases gefiltert nach Typ, Status, Liegenschaft oder Benutzer.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getCases(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['property_id'])) {
                $where[] = 'c.`property_id` = :property_id';
                $params['property_id'] = (int) $filters['property_id'];
            }

            if (!empty($filters['unit_id'])) {
                $where[] = 'c.`unit_id` = :unit_id';
                $params['unit_id'] = (int) $filters['unit_id'];
            }

            if (!empty($filters['case_type'])) {
                $where[] = 'c.`case_type` = :case_type';
                $params['case_type'] = (string) $filters['case_type'];
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = 'c.`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['creator_user_id'])) {
                $where[] = 'c.`creator_user_id` = :creator_user_id';
                $params['creator_user_id'] = (int) $filters['creator_user_id'];
            }

            if (!empty($filters['search'])) {
                $where[] = '(c.`title` LIKE :search OR c.`description` LIKE :search OR c.`damage_location` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            // Falls Beschränkung auf bestimmte Liegenschaften (für Eigentümer/Mieter)
            if (!empty($filters['allowed_property_ids']) && is_array($filters['allowed_property_ids'])) {
                $allowedIds = array_filter(array_map('intval', $filters['allowed_property_ids']));
                if (empty($allowedIds)) {
                    return [];
                }
                $inSql = implode(',', $allowedIds);
                $where[] = "c.`property_id` IN ({$inSql})";
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT c.*,
                           p.`name` AS `property_name`,
                           u.`unit_number`,
                           cu.`name` AS `creator_name`,
                           au.`name` AS `assigned_name`,
                           (SELECT COUNT(*) FROM `case_messages` cm WHERE cm.`case_id` = c.`id`) AS `message_count`
                    FROM `cases` c
                    JOIN `properties` p ON c.`property_id` = p.`id`
                    LEFT JOIN `units` u ON c.`unit_id` = u.`id`
                    LEFT JOIN `users` cu ON c.`creator_user_id` = cu.`id`
                    LEFT JOIN `users` au ON c.`assigned_user_id` = au.`id`
                    {$whereSql}
                    ORDER BY 
                        CASE c.`priority` 
                            WHEN 'urgent' THEN 1 
                            WHEN 'high' THEN 2 
                            WHEN 'normal' THEN 3 
                            ELSE 4 
                        END ASC,
                        c.`created_at` DESC
                    LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getCases Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countCases(array $filters = []): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['property_id'])) {
                $where[] = 'c.`property_id` = :property_id';
                $params['property_id'] = (int) $filters['property_id'];
            }

            if (!empty($filters['unit_id'])) {
                $where[] = 'c.`unit_id` = :unit_id';
                $params['unit_id'] = (int) $filters['unit_id'];
            }

            if (!empty($filters['case_type'])) {
                $where[] = 'c.`case_type` = :case_type';
                $params['case_type'] = (string) $filters['case_type'];
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = 'c.`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['creator_user_id'])) {
                $where[] = 'c.`creator_user_id` = :creator_user_id';
                $params['creator_user_id'] = (int) $filters['creator_user_id'];
            }

            if (!empty($filters['search'])) {
                $where[] = '(c.`title` LIKE :search OR c.`description` LIKE :search OR c.`damage_location` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (!empty($filters['allowed_property_ids']) && is_array($filters['allowed_property_ids'])) {
                $allowedIds = array_filter(array_map('intval', $filters['allowed_property_ids']));
                if (empty($allowedIds)) {
                    return 0;
                }
                $inSql = implode(',', $allowedIds);
                $where[] = "c.`property_id` IN ({$inSql})";
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT COUNT(*) AS `cnt` FROM `cases` c {$whereSql}";
            $row = DB::fetchOne($sql, $params);
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            error_log('PortalRepository::countCases Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getCase(int $id): ?array
    {
        if (!self::isTableCreated() || $id <= 0) {
            return null;
        }

        try {
            $sql = "SELECT c.*,
                           p.`name` AS `property_name`,
                           p.`street` AS `property_street`,
                           p.`city` AS `property_city`,
                           u.`unit_number`,
                           cu.`name` AS `creator_name`,
                           cu.`email` AS `creator_email`,
                           au.`name` AS `assigned_name`
                    FROM `cases` c
                    JOIN `properties` p ON c.`property_id` = p.`id`
                    LEFT JOIN `units` u ON c.`unit_id` = u.`id`
                    LEFT JOIN `users` cu ON c.`creator_user_id` = cu.`id`
                    LEFT JOIN `users` au ON c.`assigned_user_id` = au.`id`
                    WHERE c.`id` = :id
                    LIMIT 1";

            return DB::fetchOne($sql, ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getCase Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createCase(array $data): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            DB::insert(
                'INSERT INTO `cases` (`property_id`, `unit_id`, `case_type`, `title`, `description`, `status`, `priority`, `creator_user_id`, `assigned_user_id`, `meeting_date`, `damage_location`, `created_at`, `updated_at`)
                 VALUES (:property_id, :unit_id, :case_type, :title, :description, :status, :priority, :creator_user_id, :assigned_user_id, :meeting_date, :damage_location, NOW(), NOW())',
                [
                    'property_id'      => (int) $data['property_id'],
                    'unit_id'          => !empty($data['unit_id']) ? (int) $data['unit_id'] : null,
                    'case_type'        => trim((string) ($data['case_type'] ?? 'property')),
                    'title'            => trim((string) ($data['title'] ?? '')),
                    'description'      => !empty($data['description']) ? trim((string) $data['description']) : null,
                    'status'           => !empty($data['status']) ? trim((string) $data['status']) : 'new',
                    'priority'         => !empty($data['priority']) ? trim((string) $data['priority']) : 'normal',
                    'creator_user_id'  => !empty($data['creator_user_id']) ? (int) $data['creator_user_id'] : null,
                    'assigned_user_id' => !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null,
                    'meeting_date'     => !empty($data['meeting_date']) ? $data['meeting_date'] : null,
                    'damage_location'  => !empty($data['damage_location']) ? trim((string) $data['damage_location']) : null,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('PortalRepository::createCase Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function updateCase(int $id, array $data): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            $updates = [
                'status'           => !empty($data['status']) ? trim((string) $data['status']) : 'new',
                'priority'         => !empty($data['priority']) ? trim((string) $data['priority']) : 'normal',
                'title'            => trim((string) ($data['title'] ?? '')),
                'description'      => !empty($data['description']) ? trim((string) $data['description']) : null,
                'assigned_user_id' => !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null,
                'meeting_date'     => !empty($data['meeting_date']) ? $data['meeting_date'] : null,
                'damage_location'  => !empty($data['damage_location']) ? trim((string) $data['damage_location']) : null,
                'id'               => $id,
            ];

            DB::update(
                'UPDATE `cases`
                 SET `status` = :status,
                     `priority` = :priority,
                     `title` = :title,
                     `description` = :description,
                     `assigned_user_id` = :assigned_user_id,
                     `meeting_date` = :meeting_date,
                     `damage_location` = :damage_location,
                     `updated_at` = NOW()
                 WHERE `id` = :id',
                $updates
            );

            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::updateCase Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteCase(int $id): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            DB::delete('DELETE FROM `cases` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('PortalRepository::deleteCase Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 5. CASE-NACHRICHTEN & VERLAUF
    // =========================================================================

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getCaseMessages(int $caseId, bool $includeInternal = true): array
    {
        if (!self::isTableCreated() || $caseId <= 0) {
            return [];
        }

        try {
            $where = ['cm.`case_id` = :case_id'];
            $params = ['case_id' => $caseId];

            if (!$includeInternal) {
                $where[] = 'cm.`is_internal` = 0';
            }

            $whereSql = implode(' AND ', $where);

            $sql = "SELECT cm.*,
                           u.`name` AS `user_name`,
                           u.`email` AS `user_email`,
                           u.`role` AS `user_role`
                    FROM `case_messages` cm
                    LEFT JOIN `users` u ON cm.`user_id` = u.`id`
                    WHERE {$whereSql}
                    ORDER BY cm.`created_at` ASC";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('PortalRepository::getCaseMessages Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function addCaseMessage(int $caseId, ?int $userId, string $message, bool $isInternal = false): int
    {
        if (!self::isTableCreated() || $caseId <= 0 || trim($message) === '') {
            return 0;
        }

        try {
            DB::insert(
                'INSERT INTO `case_messages` (`case_id`, `user_id`, `message`, `is_internal`, `created_at`)
                 VALUES (:case_id, :user_id, :message, :is_internal, NOW())',
                [
                    'case_id'     => $caseId,
                    'user_id'     => ($userId !== null && $userId > 0) ? $userId : null,
                    'message'     => trim($message),
                    'is_internal' => $isInternal ? 1 : 0,
                ]
            );

            // Aktualisiere updated_at auf dem Case
            DB::update('UPDATE `cases` SET `updated_at` = NOW() WHERE `id` = :id', ['id' => $caseId]);

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('PortalRepository::addCaseMessage Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    // 6. DASHBOARD & STATISTIKEN
    // =========================================================================

    /**
     * @return array<string, int>
     */
    public static function getStats(?int $userId = null): array
    {
        if (!self::isTableCreated()) {
            return [
                'total_properties' => 0,
                'total_units'      => 0,
                'open_damages'     => 0,
                'open_meetings'    => 0,
                'total_cases'      => 0,
            ];
        }

        try {
            if ($userId === null) {
                // Globale Admin-Statistiken
                $propRow = DB::fetchOne('SELECT COUNT(*) AS `c` FROM `properties`') ?? ['c' => 0];
                $unitRow = DB::fetchOne('SELECT COUNT(*) AS `c` FROM `units`') ?? ['c' => 0];
                $damageRow = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `cases` WHERE `case_type` = 'damage' AND `status` != 'closed'") ?? ['c' => 0];
                $meetingRow = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `cases` WHERE `case_type` = 'meeting'") ?? ['c' => 0];
                $casesRow = DB::fetchOne('SELECT COUNT(*) AS `c` FROM `cases`') ?? ['c' => 0];

                return [
                    'total_properties' => (int) $propRow['c'],
                    'total_units'      => (int) $unitRow['c'],
                    'open_damages'     => (int) $damageRow['c'],
                    'open_meetings'    => (int) $meetingRow['c'],
                    'total_cases'      => (int) $casesRow['c'],
                ];
            }

            // Benutzer-spezifische Portal-Statistiken
            $userUnits = self::getUserUnits($userId);
            $userProps = self::getUserProperties($userId);
            $propIds = array_column($userProps, 'id');

            $openDamages = 0;
            $openMeetings = 0;
            if (!empty($propIds)) {
                $inProps = implode(',', array_map('intval', $propIds));
                $dRow = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `cases` WHERE `property_id` IN ({$inProps}) AND `case_type` = 'damage' AND `status` != 'closed'") ?? ['c' => 0];
                $mRow = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `cases` WHERE `property_id` IN ({$inProps}) AND `case_type` = 'meeting'") ?? ['c' => 0];
                $openDamages = (int) $dRow['c'];
                $openMeetings = (int) $mRow['c'];
            }

            return [
                'total_properties' => count($userProps),
                'total_units'      => count($userUnits),
                'open_damages'     => $openDamages,
                'open_meetings'    => $openMeetings,
                'total_cases'      => $openDamages + $openMeetings,
            ];
        } catch (\Throwable $e) {
            error_log('PortalRepository::getStats Fehler: ' . $e->getMessage());
            return [
                'total_properties' => 0,
                'total_units'      => 0,
                'open_damages'     => 0,
                'open_meetings'    => 0,
                'total_cases'      => 0,
            ];
        }
    }
}
