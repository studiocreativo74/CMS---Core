<?php

declare(strict_types=1);

/**
 * BoatingSchoolRepository
 *
 * Data-Access-Layer für das Modul `boating_school` (Bootsfahrschule).
 * Verwaltet Boote (bs_boats), Fahrlehrer (bs_instructors),
 * Leistungen/Produkte (bs_services) und Kunden/Fahrschüler (bs_customers) via PDO (DB.php).
 *
 * -----------------------------------------------------------------------------
 * DATENMODELL-DEFINITIONEN (SQL-Referenzkommentare, nicht automatisch ausführen):
 * -----------------------------------------------------------------------------
 *
 * 1) Boote: bs_boats
 * CREATE TABLE bs_boats (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     name VARCHAR(255) NOT NULL,              -- Anzeigename des Boots
 *     registration VARCHAR(64) NULL,           -- Kennzeichen / Schiffsnummer
 *     type VARCHAR(64) NULL,                   -- Bootstyp (z.B. motor, sail, rib, electric)
 *     capacity INT UNSIGNED NULL,              -- Anzahl Personen
 *     engine_power INT UNSIGNED NULL,          -- PS/kW
 *     color VARCHAR(64) NULL,
 *     image_path VARCHAR(255) NULL,            -- Bild des Boots
 *     notes TEXT NULL,
 *     is_active TINYINT(1) NOT NULL DEFAULT 1,
 *     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * 2) Fahrlehrer: bs_instructors
 * CREATE TABLE bs_instructors (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     user_id INT UNSIGNED NULL,                -- Optionaler CMS-Benutzerbezug
 *     first_name VARCHAR(128) NOT NULL,
 *     last_name VARCHAR(128) NOT NULL,
 *     email VARCHAR(191) NULL,
 *     phone VARCHAR(64) NULL,
 *     license_number VARCHAR(128) NULL,         -- Fahrlehrer-Ausweisnr. / Schiffsführerpatent
 *     qualifications TEXT NULL,                 -- Kategorien (z.B. Kat. A Motor, Kat. D Segel, Hochsee, Funk)
 *     color_code VARCHAR(16) NULL DEFAULT '#0d6efd', -- Farbcodierung
 *     image_path VARCHAR(255) NULL,             -- Foto des Fahrlehrers
 *     notes TEXT NULL,
 *     is_active TINYINT(1) NOT NULL DEFAULT 1,
 *     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * 3) Leistungen: bs_services
 * CREATE TABLE bs_services (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     name VARCHAR(255) NOT NULL,              -- z.B. Praxisfahrstunde Motorboot Kat. A
 *     code VARCHAR(64) NULL,                   -- Artikel- / Kurskürzel (z.B. PRAX-MOT-90)
 *     category VARCHAR(64) NOT NULL DEFAULT 'practical', -- practical, theory, rental, exam, package, other
 *     boat_type VARCHAR(64) NOT NULL DEFAULT 'any',      -- any, motor, sail, rib, electric
 *     duration_minutes INT UNSIGNED NULL DEFAULT 60, -- Dauer in Minuten
 *     price DECIMAL(10,2) NOT NULL DEFAULT 0.00,    -- Bruttopreis
 *     tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- MwSt.-Satz
 *     description TEXT NULL,
 *     is_active TINYINT(1) NOT NULL DEFAULT 1,
 *     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * 4) Schüler / Kunden: bs_customers
 * CREATE TABLE bs_customers (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     customer_number VARCHAR(64) NULL,        -- Kundennummer (z.B. KD-2026-001)
 *     type VARCHAR(32) NOT NULL DEFAULT 'student', -- student, renter, both
 *     user_id INT UNSIGNED NULL,                -- Optional verknüpft mit users.id
 *     first_name VARCHAR(128) NOT NULL,
 *     last_name VARCHAR(128) NOT NULL,
 *     email VARCHAR(191) NULL,
 *     phone VARCHAR(64) NULL,
 *     street VARCHAR(255) NULL,
 *     zip VARCHAR(32) NULL,
 *     city VARCHAR(128) NULL,
 *     birthdate DATE NULL,
 *     target_license VARCHAR(128) NULL,        -- z.B. Kat. A Motorboot, Kat. D Segelschiff
 *     theory_passed TINYINT(1) NOT NULL DEFAULT 0,
 *     theory_passed_at DATE NULL,
 *     practical_passed TINYINT(1) NOT NULL DEFAULT 0,
 *     practical_passed_at DATE NULL,
 *     emergency_contact VARCHAR(255) NULL,     -- Notfallkontakt
 *     notes TEXT NULL,
 *     is_active TINYINT(1) NOT NULL DEFAULT 1,
 *     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */
final class BoatingSchoolRepository
{
    /** @var array<string, bool> Cache für Tabellen-Existenzprüfungen */
    private static array $tableExistsCache = [];

    private function __construct()
    {
    }

    /**
     * Prüft, ob eine Tabelle der Bootsfahrschule in der Datenbank existiert.
     */
    public static function isTableCreated(string $table = 'bs_boats'): bool
    {
        if (isset(self::$tableExistsCache[$table])) {
            return self::$tableExistsCache[$table];
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE '{$table}'");
            self::$tableExistsCache[$table] = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            error_log("BoatingSchoolRepository::isTableCreated Fehler ({$table}): " . $e->getMessage());
            self::$tableExistsCache[$table] = false;
        }

        return self::$tableExistsCache[$table];
    }

    /**
     * Prüft, ob alle 4 Kern-Tabellen des Moduls angelegt sind.
     */
    public static function areAllTablesCreated(): bool
    {
        $tables = ['bs_boats', 'bs_instructors', 'bs_services', 'bs_customers'];
        foreach ($tables as $t) {
            if (!self::isTableCreated($t)) {
                return false;
            }
        }
        return true;
    }

    // =========================================================================
    // 1. BOOTE (bs_boats)
    // =========================================================================

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getBoats(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        if (!self::isTableCreated('bs_boats')) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(b.`name` LIKE :search OR b.`registration` LIKE :search OR b.`type` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'b.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            if (!empty($filters['type'])) {
                $where[] = 'b.`type` = :type';
                $params['type'] = (string) $filters['type'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT b.* FROM `bs_boats` b {$whereSql} ORDER BY b.`name` ASC LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getBoats Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countBoats(?array $filters = null): int
    {
        if (!self::isTableCreated('bs_boats')) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(b.`name` LIKE :search OR b.`registration` LIKE :search OR b.`type` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'b.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            if (!empty($filters['type'])) {
                $where[] = 'b.`type` = :type';
                $params['type'] = (string) $filters['type'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $row = DB::fetchOne("SELECT COUNT(*) AS c FROM `bs_boats` b {$whereSql}", $params);

            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::countBoats Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getBoatById(int $id): ?array
    {
        if (!self::isTableCreated('bs_boats') || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_boats` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getBoatById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createBoat(array $data): int
    {
        $sql = 'INSERT INTO `bs_boats` 
                (`name`, `registration`, `type`, `capacity`, `engine_power`, `color`, `image_path`, `notes`, `is_active`, `created_at`, `updated_at`) 
                VALUES 
                (:name, :registration, :type, :capacity, :engine_power, :color, :image_path, :notes, :is_active, NOW(), NOW())';

        DB::execute($sql, [
            'name'         => trim((string) ($data['name'] ?? '')),
            'registration' => !empty($data['registration']) ? trim((string) $data['registration']) : null,
            'type'         => !empty($data['type']) ? trim((string) $data['type']) : null,
            'capacity'     => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'engine_power' => !empty($data['engine_power']) ? (int) $data['engine_power'] : null,
            'color'        => !empty($data['color']) ? trim((string) $data['color']) : null,
            'image_path'   => !empty($data['image_path']) ? trim((string) $data['image_path']) : null,
            'notes'        => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active'    => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ]);

        return (int) DB::lastInsertId();
    }

    public static function updateBoat(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $fields = [
            'name'         => trim((string) ($data['name'] ?? '')),
            'registration' => !empty($data['registration']) ? trim((string) $data['registration']) : null,
            'type'         => !empty($data['type']) ? trim((string) $data['type']) : null,
            'capacity'     => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'engine_power' => !empty($data['engine_power']) ? (int) $data['engine_power'] : null,
            'color'        => !empty($data['color']) ? trim((string) $data['color']) : null,
            'notes'        => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active'    => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'id'           => $id,
        ];

        $imageSql = '';
        if (array_key_exists('image_path', $data)) {
            $imageSql = ', `image_path` = :image_path';
            $fields['image_path'] = !empty($data['image_path']) ? trim((string) $data['image_path']) : null;
        }

        $sql = "UPDATE `bs_boats` 
                SET `name` = :name, 
                    `registration` = :registration, 
                    `type` = :type, 
                    `capacity` = :capacity, 
                    `engine_power` = :engine_power, 
                    `color` = :color, 
                    `notes` = :notes, 
                    `is_active` = :is_active
                    {$imageSql},
                    `updated_at` = NOW() 
                WHERE `id` = :id";

        try {
            DB::execute($sql, $fields);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateBoat Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function toggleBoatStatus(int $id): bool
    {
        $boat = self::getBoatById($id);
        if (!$boat) {
            return false;
        }
        $newStatus = empty($boat['is_active']) ? 1 : 0;
        try {
            DB::execute('UPDATE `bs_boats` SET `is_active` = :status, `updated_at` = NOW() WHERE `id` = :id', [
                'status' => $newStatus,
                'id'     => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::toggleBoatStatus Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteBoat(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            DB::execute('DELETE FROM `bs_boats` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteBoat Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 2. FAHRLEHRER (bs_instructors)
    // =========================================================================

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getInstructors(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        if (!self::isTableCreated('bs_instructors')) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(i.`first_name` LIKE :search OR i.`last_name` LIKE :search OR i.`email` LIKE :search OR i.`license_number` LIKE :search OR i.`qualifications` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'i.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT i.* FROM `bs_instructors` i {$whereSql} ORDER BY i.`last_name` ASC, i.`first_name` ASC LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getInstructors Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countInstructors(?array $filters = null): int
    {
        if (!self::isTableCreated('bs_instructors')) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(i.`first_name` LIKE :search OR i.`last_name` LIKE :search OR i.`email` LIKE :search OR i.`license_number` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'i.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $row = DB::fetchOne("SELECT COUNT(*) AS c FROM `bs_instructors` i {$whereSql}", $params);

            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::countInstructors Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getInstructorById(int $id): ?array
    {
        if (!self::isTableCreated('bs_instructors') || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_instructors` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getInstructorById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function getInstructorByUserId(int $userId): ?array
    {
        if (!self::isTableCreated('bs_instructors') || $userId <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_instructors` WHERE `user_id` = :user_id LIMIT 1', ['user_id' => $userId]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getInstructorByUserId Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createInstructor(array $data): int
    {
        self::ensureCrmSchema();
        $sql = 'INSERT INTO `bs_instructors` 
                (`user_id`, `first_name`, `last_name`, `title`, `email`, `phone`, `public_mobile`, `is_mobile_public`, `license_number`, `pay_rate_type`, `pay_rate_amount`, `payroll_external_code`, `qualifications`, `color_code`, `image_path`, `image_url`, `notes`, `is_active`, `created_at`, `updated_at`) 
                VALUES 
                (:user_id, :first_name, :last_name, :title, :email, :phone, :public_mobile, :is_mobile_public, :license_number, :pay_rate_type, :pay_rate_amount, :payroll_external_code, :qualifications, :color_code, :image_path, :image_url, :notes, :is_active, NOW(), NOW())';

        DB::execute($sql, [
            'user_id'               => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'first_name'            => trim((string) ($data['first_name'] ?? '')),
            'last_name'             => trim((string) ($data['last_name'] ?? '')),
            'title'                 => !empty($data['title']) ? trim((string) $data['title']) : 'Fahrcoach',
            'email'                 => !empty($data['email']) ? trim((string) $data['email']) : null,
            'phone'                 => !empty($data['phone']) ? trim((string) $data['phone']) : null,
            'public_mobile'         => !empty($data['public_mobile']) ? trim((string) $data['public_mobile']) : null,
            'is_mobile_public'      => !empty($data['is_mobile_public']) ? 1 : 0,
            'license_number'        => !empty($data['license_number']) ? trim((string) $data['license_number']) : null,
            'pay_rate_type'         => !empty($data['pay_rate_type']) ? (string) $data['pay_rate_type'] : 'per_hour',
            'pay_rate_amount'       => isset($data['pay_rate_amount']) ? (float) $data['pay_rate_amount'] : 0.00,
            'payroll_external_code' => !empty($data['payroll_external_code']) ? trim((string) $data['payroll_external_code']) : null,
            'qualifications'        => !empty($data['qualifications']) ? trim((string) $data['qualifications']) : null,
            'color_code'            => !empty($data['color_code']) ? trim((string) $data['color_code']) : '#0d6efd',
            'image_path'            => !empty($data['image_path']) ? trim((string) $data['image_path']) : null,
            'image_url'             => !empty($data['image_url']) ? trim((string) $data['image_url']) : null,
            'notes'                 => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active'             => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ]);

        return (int) DB::lastInsertId();
    }

    public static function updateInstructor(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        self::ensureCrmSchema();
        $fields = [
            'user_id'               => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'first_name'            => trim((string) ($data['first_name'] ?? '')),
            'last_name'             => trim((string) ($data['last_name'] ?? '')),
            'title'                 => !empty($data['title']) ? trim((string) $data['title']) : 'Fahrcoach',
            'email'                 => !empty($data['email']) ? trim((string) $data['email']) : null,
            'phone'                 => !empty($data['phone']) ? trim((string) $data['phone']) : null,
            'public_mobile'         => !empty($data['public_mobile']) ? trim((string) $data['public_mobile']) : null,
            'is_mobile_public'      => !empty($data['is_mobile_public']) ? 1 : 0,
            'license_number'        => !empty($data['license_number']) ? trim((string) $data['license_number']) : null,
            'pay_rate_type'         => !empty($data['pay_rate_type']) ? (string) $data['pay_rate_type'] : 'per_hour',
            'pay_rate_amount'       => isset($data['pay_rate_amount']) ? (float) $data['pay_rate_amount'] : 0.00,
            'payroll_external_code' => !empty($data['payroll_external_code']) ? trim((string) $data['payroll_external_code']) : null,
            'qualifications'        => !empty($data['qualifications']) ? trim((string) $data['qualifications']) : null,
            'color_code'            => !empty($data['color_code']) ? trim((string) $data['color_code']) : '#0d6efd',
            'notes'                 => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active'             => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'id'                    => $id,
        ];

        $imageSql = '';
        if (array_key_exists('image_path', $data)) {
            $imageSql .= ', `image_path` = :image_path';
            $fields['image_path'] = !empty($data['image_path']) ? trim((string) $data['image_path']) : null;
        }
        if (array_key_exists('image_url', $data)) {
            $imageSql .= ', `image_url` = :image_url';
            $fields['image_url'] = !empty($data['image_url']) ? trim((string) $data['image_url']) : null;
        }

        $sql = "UPDATE `bs_instructors` 
                SET `user_id` = :user_id,
                    `first_name` = :first_name, 
                    `last_name` = :last_name, 
                    `title` = :title,
                    `email` = :email, 
                    `phone` = :phone, 
                    `public_mobile` = :public_mobile,
                    `is_mobile_public` = :is_mobile_public,
                    `license_number` = :license_number,
                    `pay_rate_type` = :pay_rate_type,
                    `pay_rate_amount` = :pay_rate_amount,
                    `payroll_external_code` = :payroll_external_code,
                    `qualifications` = :qualifications, 
                    `color_code` = :color_code, 
                    `notes` = :notes, 
                    `is_active` = :is_active
                    {$imageSql},
                    `updated_at` = NOW() 
                WHERE `id` = :id";

        try {
            DB::execute($sql, $fields);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateInstructor Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function toggleInstructorStatus(int $id): bool
    {
        $instructor = self::getInstructorById($id);
        if (!$instructor) {
            return false;
        }
        $newStatus = empty($instructor['is_active']) ? 1 : 0;
        try {
            DB::execute('UPDATE `bs_instructors` SET `is_active` = :status, `updated_at` = NOW() WHERE `id` = :id', [
                'status' => $newStatus,
                'id'     => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::toggleInstructorStatus Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteInstructor(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            DB::execute('DELETE FROM `bs_instructors` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteInstructor Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 3. LEISTUNGEN & PRODUKTE (bs_services)
    // =========================================================================

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getServices(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        if (!self::isTableCreated('bs_services')) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(s.`name` LIKE :search OR s.`code` LIKE :search OR s.`description` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (!empty($filters['category'])) {
                $where[] = 's.`category` = :category';
                $params['category'] = (string) $filters['category'];
            }

            if (!empty($filters['boat_type'])) {
                $where[] = 's.`boat_type` = :boat_type';
                $params['boat_type'] = (string) $filters['boat_type'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 's.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT s.* FROM `bs_services` s {$whereSql} ORDER BY s.`category` ASC, s.`name` ASC LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getServices Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countServices(?array $filters = null): int
    {
        if (!self::isTableCreated('bs_services')) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(s.`name` LIKE :search OR s.`code` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (!empty($filters['category'])) {
                $where[] = 's.`category` = :category';
                $params['category'] = (string) $filters['category'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 's.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $row = DB::fetchOne("SELECT COUNT(*) AS c FROM `bs_services` s {$whereSql}", $params);

            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::countServices Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getServiceById(int $id): ?array
    {
        if (!self::isTableCreated('bs_services') || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_services` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getServiceById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createService(array $data): int
    {
        $sql = 'INSERT INTO `bs_services` 
                (`name`, `code`, `category`, `boat_type`, `duration_minutes`, `price`, `tax_rate`, `description`, `is_active`, `created_at`, `updated_at`) 
                VALUES 
                (:name, :code, :category, :boat_type, :duration_minutes, :price, :tax_rate, :description, :is_active, NOW(), NOW())';

        DB::execute($sql, [
            'name'             => trim((string) ($data['name'] ?? '')),
            'code'             => !empty($data['code']) ? trim((string) $data['code']) : null,
            'category'         => !empty($data['category']) ? trim((string) $data['category']) : 'practical',
            'boat_type'        => !empty($data['boat_type']) ? trim((string) $data['boat_type']) : null,
            'duration_minutes' => isset($data['duration_minutes']) && $data['duration_minutes'] !== '' ? (int) $data['duration_minutes'] : 60,
            'price'            => isset($data['price']) ? (float) $data['price'] : 0.00,
            'tax_rate'         => isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0.00,
            'description'      => !empty($data['description']) ? trim((string) $data['description']) : null,
            'is_active'        => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ]);

        return (int) DB::lastInsertId();
    }

    public static function updateService(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $sql = 'UPDATE `bs_services` 
                SET `name` = :name, 
                    `code` = :code, 
                    `category` = :category, 
                    `boat_type` = :boat_type, 
                    `duration_minutes` = :duration_minutes, 
                    `price` = :price, 
                    `tax_rate` = :tax_rate, 
                    `description` = :description, 
                    `is_active` = :is_active, 
                    `updated_at` = NOW() 
                WHERE `id` = :id';

        try {
            DB::execute($sql, [
                'name'             => trim((string) ($data['name'] ?? '')),
                'code'             => !empty($data['code']) ? trim((string) $data['code']) : null,
                'category'         => !empty($data['category']) ? trim((string) $data['category']) : 'practical',
                'boat_type'        => !empty($data['boat_type']) ? trim((string) $data['boat_type']) : null,
                'duration_minutes' => isset($data['duration_minutes']) && $data['duration_minutes'] !== '' ? (int) $data['duration_minutes'] : 60,
                'price'            => isset($data['price']) ? (float) $data['price'] : 0.00,
                'tax_rate'         => isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0.00,
                'description'      => !empty($data['description']) ? trim((string) $data['description']) : null,
                'is_active'        => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
                'id'               => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateService Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function toggleServiceStatus(int $id): bool
    {
        $srv = self::getServiceById($id);
        if (!$srv) {
            return false;
        }
        $newStatus = empty($srv['is_active']) ? 1 : 0;
        try {
            DB::execute('UPDATE `bs_services` SET `is_active` = :status, `updated_at` = NOW() WHERE `id` = :id', [
                'status' => $newStatus,
                'id'     => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::toggleServiceStatus Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteService(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            DB::execute('DELETE FROM `bs_services` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteService Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 4. KUNDEN & SCHÜLER (bs_customers)
    // =========================================================================

    /**
     * Stellt sicher, dass eine Tabellenspalte existiert – 100% kompatibel mit allen MySQL- und MariaDB-Versionen.
     */
    public static function ensureColumn(string $table, string $column, string $definition): void
    {
        try {
            $cols = DB::fetchAll("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            if (empty($cols)) {
                DB::execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (\Throwable $e) {
            error_log("BoatingSchoolRepository::ensureColumn ({$table}.{$column}) Fehler: " . $e->getMessage());
        }
    }

    /**
     * Stellt sicher, dass Tabellen & Spalten für CRM & Abos bereitstehen.
     */
    public static function ensureCrmSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        if (!self::isTableCreated('bs_customers')) {
            return;
        }

        try {
            // 1. CRM-Spalten in bs_customers idempotent ergänzen
            self::ensureColumn('bs_customers', 'licenses_held', 'VARCHAR(255) NULL AFTER `target_license`');
            self::ensureColumn('bs_customers', 'license_number', 'VARCHAR(128) NULL AFTER `licenses_held`');
            self::ensureColumn('bs_customers', 'faber', 'VARCHAR(64) NULL AFTER `license_number`');
            self::ensureColumn('bs_customers', 'pin', 'VARCHAR(64) NULL AFTER `faber`');
            self::ensureColumn('bs_customers', 'pid', 'VARCHAR(64) NULL AFTER `pin`');
            self::ensureColumn('bs_customers', 'experience_level', "VARCHAR(64) NULL DEFAULT 'beginner' AFTER `pid`");
            self::ensureColumn('bs_customers', 'notify_email', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `experience_level`');
            self::ensureColumn('bs_customers', 'notify_whatsapp', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `notify_email`');
            self::ensureColumn('bs_customers', 'company', 'VARCHAR(191) NULL AFTER `last_name`');
            self::ensureColumn('bs_customers', 'preferred_contact', "VARCHAR(32) NULL DEFAULT 'email' AFTER `phone`");
            self::ensureColumn('bs_customers', 'lead_source', 'VARCHAR(64) NULL AFTER `preferred_contact`');
            self::ensureColumn('bs_customers', 'crm_stage', "VARCHAR(32) NOT NULL DEFAULT 'student' AFTER `lead_source`");
            self::ensureColumn('bs_customers', 'tags', 'VARCHAR(255) NULL AFTER `crm_stage`');
            self::ensureColumn('bs_customers', 'internal_notes', 'TEXT NULL AFTER `notes`');

            // 2. Tabelle bs_subscriptions anlegen
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_subscriptions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `customer_id` INT UNSIGNED NOT NULL,
                `service_id` INT UNSIGNED NULL,
                `title` VARCHAR(255) NOT NULL,
                `code` VARCHAR(64) NULL,
                `type` ENUM('units', 'hours', 'unlimited') NOT NULL DEFAULT 'units',
                `total_units` INT NOT NULL DEFAULT 10,
                `used_units` INT NOT NULL DEFAULT 0,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `valid_from` DATE NULL,
                `valid_until` DATE NULL,
                `status` ENUM('active', 'depleted', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_bs_sub_customer` (`customer_id`),
                INDEX `idx_bs_sub_service` (`service_id`),
                INDEX `idx_bs_sub_status` (`status`),
                INDEX `idx_bs_sub_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 3. Tabelle bs_subscription_usages anlegen
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_subscription_usages` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `subscription_id` INT UNSIGNED NOT NULL,
                `appointment_id` INT UNSIGNED NULL,
                `units` INT NOT NULL DEFAULT 1,
                `used_date` DATE NOT NULL,
                `note` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_bs_sub_usage_sub` (`subscription_id`),
                INDEX `idx_bs_sub_usage_app` (`appointment_id`),
                INDEX `idx_bs_sub_usage_date` (`used_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 4. subscription_id und reminder_sent_at in bs_appointments
            if (self::isTableCreated('bs_appointments')) {
                self::ensureColumn('bs_appointments', 'subscription_id', 'INT UNSIGNED NULL AFTER `price`');
                self::ensureColumn('bs_appointments', 'reminder_sent_at', 'DATETIME NULL AFTER `updated_at`');
            }

            // 5. Payroll-Felder in bs_instructors idempotent ergänzen
            if (self::isTableCreated('bs_instructors')) {
                self::ensureColumn('bs_instructors', 'pay_rate_type', "ENUM('per_hour','per_appointment') NOT NULL DEFAULT 'per_hour' AFTER `license_number`");
                self::ensureColumn('bs_instructors', 'pay_rate_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `pay_rate_type`');
                self::ensureColumn('bs_instructors', 'payroll_external_code', 'VARCHAR(64) NULL AFTER `pay_rate_amount`');
                self::ensureColumn('bs_instructors', 'title', "VARCHAR(100) NOT NULL DEFAULT 'Fahrcoach' AFTER `last_name`");
                self::ensureColumn('bs_instructors', 'description', 'TEXT NULL AFTER `qualifications`');
                self::ensureColumn('bs_instructors', 'image_url', 'VARCHAR(255) NULL AFTER `image_path`');
                self::ensureColumn('bs_instructors', 'public_mobile', 'VARCHAR(50) NULL AFTER `phone`');
                self::ensureColumn('bs_instructors', 'is_mobile_public', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `public_mobile`');
            }

            // 6. Payroll-Abrechnungsstatus & Betrag in bs_appointments idempotent ergänzen
            if (self::isTableCreated('bs_appointments')) {
                self::ensureColumn('bs_appointments', 'instructor_payout_status', "ENUM('open','exported','paid') NOT NULL DEFAULT 'open' AFTER `price`");
                self::ensureColumn('bs_appointments', 'instructor_payout_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `instructor_payout_status`');
                self::ensureColumn('bs_appointments', 'instructor_payout_note', 'VARCHAR(255) NULL AFTER `instructor_payout_amount`');
            }

            // 7. Schüler Magic-Links Tabelle anlegen
            if (class_exists('BoatingMagicLinkService')) {
                BoatingMagicLinkService::ensureTable();
            }

            // 8. Coach-Modul (HR-Kartei, Absenzen & Timesheets) sicherstellen
            if (class_exists('CoachService')) {
                CoachService::ensureTables();
            }
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::ensureCrmSchema Fehler: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getCustomers(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        if (!self::isTableCreated('bs_customers')) {
            return [];
        }

        self::ensureCrmSchema();

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(c.`first_name` LIKE :search OR c.`last_name` LIKE :search OR c.`email` LIKE :search OR c.`customer_number` LIKE :search OR c.`phone` LIKE :search OR c.`city` LIKE :search OR c.`company` LIKE :search OR c.`tags` LIKE :search OR c.`licenses_held` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (!empty($filters['type'])) {
                $where[] = 'c.`type` = :type';
                $params['type'] = (string) $filters['type'];
            }

            if (!empty($filters['crm_stage'])) {
                $where[] = 'c.`crm_stage` = :crm_stage';
                $params['crm_stage'] = (string) $filters['crm_stage'];
            }

            if (!empty($filters['experience_level'])) {
                $where[] = 'c.`experience_level` = :experience_level';
                $params['experience_level'] = (string) $filters['experience_level'];
            }

            if (!empty($filters['lead_source'])) {
                $where[] = 'c.`lead_source` = :lead_source';
                $params['lead_source'] = (string) $filters['lead_source'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'c.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            if (isset($filters['theory_passed']) && $filters['theory_passed'] !== '') {
                $where[] = 'c.`theory_passed` = :tp';
                $params['tp'] = (int) $filters['theory_passed'];
            }

            if (isset($filters['practical_passed']) && $filters['practical_passed'] !== '') {
                $where[] = 'c.`practical_passed` = :pp';
                $params['pp'] = (int) $filters['practical_passed'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT c.* FROM `bs_customers` c {$whereSql} ORDER BY c.`last_name` ASC, c.`first_name` ASC LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getCustomers Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countCustomers(?array $filters = null): int
    {
        if (!self::isTableCreated('bs_customers')) {
            return 0;
        }

        self::ensureCrmSchema();

        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = '(c.`first_name` LIKE :search OR c.`last_name` LIKE :search OR c.`email` LIKE :search OR c.`customer_number` LIKE :search OR c.`phone` LIKE :search OR c.`company` LIKE :search OR c.`tags` LIKE :search)';
                $params['search'] = '%' . trim((string) $filters['search']) . '%';
            }

            if (!empty($filters['type'])) {
                $where[] = 'c.`type` = :type';
                $params['type'] = (string) $filters['type'];
            }

            if (!empty($filters['crm_stage'])) {
                $where[] = 'c.`crm_stage` = :crm_stage';
                $params['crm_stage'] = (string) $filters['crm_stage'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = 'c.`is_active` = :is_active';
                $params['is_active'] = (int) $filters['is_active'];
            }

            if (isset($filters['theory_passed']) && $filters['theory_passed'] !== '') {
                $where[] = 'c.`theory_passed` = :tp';
                $params['tp'] = (int) $filters['theory_passed'];
            }

            if (isset($filters['practical_passed']) && $filters['practical_passed'] !== '') {
                $where[] = 'c.`practical_passed` = :pp';
                $params['pp'] = (int) $filters['practical_passed'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $row = DB::fetchOne("SELECT COUNT(*) AS c FROM `bs_customers` c {$whereSql}", $params);

            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::countCustomers Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getCustomerById(int $id): ?array
    {
        if (!self::isTableCreated('bs_customers') || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_customers` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getCustomerById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createCustomer(array $data): int
    {
        self::ensureCrmSchema();

        // Automatische Kundennummer erzeugen, falls leer
        $customerNumber = !empty($data['customer_number']) ? trim((string) $data['customer_number']) : null;
        if ($customerNumber === null) {
            $year = date('Y');
            $rand = strtoupper(bin2hex(random_bytes(2)));
            $customerNumber = 'KD-' . $year . '-' . $rand;
        }

        $faberVal = !empty($data['faber']) ? trim((string) $data['faber']) : (!empty($data['license_number']) ? trim((string) $data['license_number']) : null);
        $pinVal   = !empty($data['pin']) ? trim((string) $data['pin']) : null;
        $pidVal   = !empty($data['pid']) ? trim((string) $data['pid']) : null;

        $sql = 'INSERT INTO `bs_customers` 
                (`customer_number`, `type`, `user_id`, `first_name`, `last_name`, `company`, `email`, `phone`, `preferred_contact`, `lead_source`, `crm_stage`, `tags`, `street`, `zip`, `city`, `birthdate`, `target_license`, `licenses_held`, `license_number`, `faber`, `pin`, `pid`, `experience_level`, `notify_email`, `notify_whatsapp`, `theory_passed`, `theory_passed_at`, `practical_passed`, `practical_passed_at`, `emergency_contact`, `notes`, `internal_notes`, `is_active`, `created_at`, `updated_at`) 
                VALUES 
                (:customer_number, :type, :user_id, :first_name, :last_name, :company, :email, :phone, :preferred_contact, :lead_source, :crm_stage, :tags, :street, :zip, :city, :birthdate, :target_license, :licenses_held, :license_number, :faber, :pin, :pid, :experience_level, :notify_email, :notify_whatsapp, :theory_passed, :theory_passed_at, :practical_passed, :practical_passed_at, :emergency_contact, :notes, :internal_notes, :is_active, NOW(), NOW())';

        DB::execute($sql, [
            'customer_number'     => $customerNumber,
            'type'                => !empty($data['type']) ? trim((string) $data['type']) : 'student',
            'user_id'             => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'first_name'          => trim((string) ($data['first_name'] ?? '')),
            'last_name'           => trim((string) ($data['last_name'] ?? '')),
            'company'             => !empty($data['company']) ? trim((string) $data['company']) : null,
            'email'               => !empty($data['email']) ? trim((string) $data['email']) : null,
            'phone'               => !empty($data['phone']) ? trim((string) $data['phone']) : null,
            'preferred_contact'   => !empty($data['preferred_contact']) ? trim((string) $data['preferred_contact']) : 'email',
            'lead_source'         => !empty($data['lead_source']) ? trim((string) $data['lead_source']) : null,
            'crm_stage'           => !empty($data['crm_stage']) ? trim((string) $data['crm_stage']) : 'student',
            'tags'                => !empty($data['tags']) ? trim((string) $data['tags']) : null,
            'street'              => !empty($data['street']) ? trim((string) $data['street']) : null,
            'zip'                 => !empty($data['zip']) ? trim((string) $data['zip']) : null,
            'city'                => !empty($data['city']) ? trim((string) $data['city']) : null,
            'birthdate'           => !empty($data['birthdate']) ? trim((string) $data['birthdate']) : null,
            'target_license'      => !empty($data['target_license']) ? trim((string) $data['target_license']) : null,
            'licenses_held'       => !empty($data['licenses_held']) ? trim((string) $data['licenses_held']) : null,
            'license_number'      => $faberVal,
            'faber'               => $faberVal,
            'pin'                 => $pinVal,
            'pid'                 => $pidVal,
            'experience_level'    => !empty($data['experience_level']) ? trim((string) $data['experience_level']) : 'beginner',
            'notify_email'        => isset($data['notify_email']) ? (int) (bool) $data['notify_email'] : 1,
            'notify_whatsapp'     => !empty($data['notify_whatsapp']) ? 1 : 0,
            'theory_passed'       => !empty($data['theory_passed']) ? 1 : 0,
            'theory_passed_at'    => !empty($data['theory_passed_at']) ? trim((string) $data['theory_passed_at']) : null,
            'practical_passed'    => !empty($data['practical_passed']) ? 1 : 0,
            'practical_passed_at' => !empty($data['practical_passed_at']) ? trim((string) $data['practical_passed_at']) : null,
            'emergency_contact'   => !empty($data['emergency_contact']) ? trim((string) $data['emergency_contact']) : null,
            'notes'               => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'internal_notes'      => !empty($data['internal_notes']) ? trim((string) $data['internal_notes']) : null,
            'is_active'           => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ]);

        return (int) DB::lastInsertId();
    }

    public static function updateCustomer(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        self::ensureCrmSchema();

        $faberVal = !empty($data['faber']) ? trim((string) $data['faber']) : (!empty($data['license_number']) ? trim((string) $data['license_number']) : null);
        $pinVal   = !empty($data['pin']) ? trim((string) $data['pin']) : null;
        $pidVal   = !empty($data['pid']) ? trim((string) $data['pid']) : null;

        $sql = 'UPDATE `bs_customers` 
                SET `customer_number` = :customer_number, 
                    `type` = :type, 
                    `user_id` = :user_id, 
                    `first_name` = :first_name, 
                    `last_name` = :last_name, 
                    `company` = :company,
                    `email` = :email, 
                    `phone` = :phone, 
                    `preferred_contact` = :preferred_contact,
                    `lead_source` = :lead_source,
                    `crm_stage` = :crm_stage,
                    `tags` = :tags,
                    `street` = :street, 
                    `zip` = :zip, 
                    `city` = :city, 
                    `birthdate` = :birthdate, 
                    `target_license` = :target_license, 
                    `licenses_held` = :licenses_held,
                    `license_number` = :license_number,
                    `faber` = :faber,
                    `pin` = :pin,
                    `pid` = :pid,
                    `experience_level` = :experience_level,
                    `notify_email` = :notify_email,
                    `notify_whatsapp` = :notify_whatsapp,
                    `theory_passed` = :theory_passed, 
                    `theory_passed_at` = :theory_passed_at, 
                    `practical_passed` = :practical_passed, 
                    `practical_passed_at` = :practical_passed_at, 
                    `emergency_contact` = :emergency_contact, 
                    `notes` = :notes, 
                    `internal_notes` = :internal_notes,
                    `is_active` = :is_active, 
                    `updated_at` = NOW() 
                WHERE `id` = :id';

        try {
            DB::execute($sql, [
                'customer_number'     => !empty($data['customer_number']) ? trim((string) $data['customer_number']) : null,
                'type'                => !empty($data['type']) ? trim((string) $data['type']) : 'student',
                'user_id'             => !empty($data['user_id']) ? (int) $data['user_id'] : null,
                'first_name'          => trim((string) ($data['first_name'] ?? '')),
                'last_name'           => trim((string) ($data['last_name'] ?? '')),
                'company'             => !empty($data['company']) ? trim((string) $data['company']) : null,
                'email'               => !empty($data['email']) ? trim((string) $data['email']) : null,
                'phone'               => !empty($data['phone']) ? trim((string) $data['phone']) : null,
                'preferred_contact'   => !empty($data['preferred_contact']) ? trim((string) $data['preferred_contact']) : 'email',
                'lead_source'         => !empty($data['lead_source']) ? trim((string) $data['lead_source']) : null,
                'crm_stage'           => !empty($data['crm_stage']) ? trim((string) $data['crm_stage']) : 'student',
                'tags'                => !empty($data['tags']) ? trim((string) $data['tags']) : null,
                'street'              => !empty($data['street']) ? trim((string) $data['street']) : null,
                'zip'                 => !empty($data['zip']) ? trim((string) $data['zip']) : null,
                'city'                => !empty($data['city']) ? trim((string) $data['city']) : null,
                'birthdate'           => !empty($data['birthdate']) ? trim((string) $data['birthdate']) : null,
                'target_license'      => !empty($data['target_license']) ? trim((string) $data['target_license']) : null,
                'licenses_held'       => !empty($data['licenses_held']) ? trim((string) $data['licenses_held']) : null,
                'license_number'      => $faberVal,
                'faber'               => $faberVal,
                'pin'                 => $pinVal,
                'pid'                 => $pidVal,
                'experience_level'    => !empty($data['experience_level']) ? trim((string) $data['experience_level']) : 'beginner',
                'notify_email'        => isset($data['notify_email']) ? (int) (bool) $data['notify_email'] : 1,
                'notify_whatsapp'     => !empty($data['notify_whatsapp']) ? 1 : 0,
                'theory_passed'       => !empty($data['theory_passed']) ? 1 : 0,
                'theory_passed_at'    => !empty($data['theory_passed_at']) ? trim((string) $data['theory_passed_at']) : null,
                'practical_passed'    => !empty($data['practical_passed']) ? 1 : 0,
                'practical_passed_at' => !empty($data['practical_passed_at']) ? trim((string) $data['practical_passed_at']) : null,
                'emergency_contact'   => !empty($data['emergency_contact']) ? trim((string) $data['emergency_contact']) : null,
                'notes'               => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                'internal_notes'      => !empty($data['internal_notes']) ? trim((string) $data['internal_notes']) : null,
                'is_active'           => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
                'id'                  => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateCustomer Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function toggleCustomerStatus(int $id): bool
    {
        $cust = self::getCustomerById($id);
        if (!$cust) {
            return false;
        }
        $newStatus = empty($cust['is_active']) ? 1 : 0;
        try {
            DB::execute('UPDATE `bs_customers` SET `is_active` = :status, `updated_at` = NOW() WHERE `id` = :id', [
                'status' => $newStatus,
                'id'     => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::toggleCustomerStatus Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function getCustomerByEmail(string $email): ?array
    {
        if (!self::isTableCreated('bs_customers')) {
            return null;
        }

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `bs_customers` WHERE LOWER(`email`) = :email ORDER BY `id` DESC LIMIT 1',
                ['email' => strtolower(trim($email))]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getCustomerByEmail Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function getCustomerByNumber(string $number): ?array
    {
        if (!self::isTableCreated('bs_customers')) {
            return null;
        }

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `bs_customers` WHERE `customer_number` = :num LIMIT 1',
                ['num' => trim($number)]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getCustomerByNumber Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function deleteCustomer(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            DB::execute('DELETE FROM `bs_customers` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteCustomer Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 4b. ABO- & PUNKTEKARTEN (bs_subscriptions & bs_subscription_usages)
    // =========================================================================

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptions(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        self::ensureCrmSchema();
        if (!self::isTableCreated('bs_subscriptions')) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['customer_id'])) {
                $where[] = 's.`customer_id` = :customer_id';
                $params['customer_id'] = (int) $filters['customer_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = 's.`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT s.*, 
                           c.`first_name` AS customer_first_name, c.`last_name` AS customer_last_name, c.`customer_number`,
                           srv.`name` AS service_name
                    FROM `bs_subscriptions` s
                    LEFT JOIN `bs_customers` c ON s.`customer_id` = c.`id`
                    LEFT JOIN `bs_services` srv ON s.`service_id` = srv.`id`
                    {$whereSql}
                    ORDER BY s.`id` DESC
                    LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getSubscriptions Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Liefert alle Abonnements eines Kunden inklusive Verbrauchs-Berechnung.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptionsByCustomerId(int $customerId): array
    {
        return self::getSubscriptions(['customer_id' => $customerId], 100, 0);
    }

    public static function getSubscriptionById(int $id): ?array
    {
        self::ensureCrmSchema();
        if (!self::isTableCreated('bs_subscriptions') || $id <= 0) {
            return null;
        }

        try {
            $sql = "SELECT s.*, 
                           c.`first_name` AS customer_first_name, c.`last_name` AS customer_last_name, c.`customer_number`,
                           srv.`name` AS service_name
                    FROM `bs_subscriptions` s
                    LEFT JOIN `bs_customers` c ON s.`customer_id` = c.`id`
                    LEFT JOIN `bs_services` srv ON s.`service_id` = srv.`id`
                    WHERE s.`id` = :id
                    LIMIT 1";
            $row = DB::fetchOne($sql, ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getSubscriptionById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Erstellt eine neue Abo- oder Punktekarte.
     *
     * @param array<string, mixed> $data
     */
    public static function createSubscription(array $data): int
    {
        self::ensureCrmSchema();

        $code = !empty($data['code']) ? trim((string) $data['code']) : null;
        if ($code === null) {
            $code = 'ABO-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));
        }

        $totalUnits = isset($data['total_units']) ? max(1, (int) $data['total_units']) : 10;
        $usedUnits = isset($data['used_units']) ? max(0, (int) $data['used_units']) : 0;

        $sql = 'INSERT INTO `bs_subscriptions`
                (`customer_id`, `service_id`, `title`, `code`, `type`, `total_units`, `used_units`, `price`, `valid_from`, `valid_until`, `status`, `notes`, `created_at`, `updated_at`)
                VALUES
                (:customer_id, :service_id, :title, :code, :type, :total_units, :used_units, :price, :valid_from, :valid_until, :status, :notes, NOW(), NOW())';

        DB::execute($sql, [
            'customer_id' => (int) ($data['customer_id'] ?? 0),
            'service_id'  => !empty($data['service_id']) ? (int) $data['service_id'] : null,
            'title'       => trim((string) ($data['title'] ?? 'Abonnement')),
            'code'        => $code,
            'type'        => !empty($data['type']) ? trim((string) $data['type']) : 'units',
            'total_units' => $totalUnits,
            'used_units'  => $usedUnits,
            'price'       => isset($data['price']) ? (float) $data['price'] : 0.00,
            'valid_from'  => !empty($data['valid_from']) ? trim((string) $data['valid_from']) : date('Y-m-d'),
            'valid_until' => !empty($data['valid_until']) ? trim((string) $data['valid_until']) : null,
            'status'      => !empty($data['status']) ? trim((string) $data['status']) : 'active',
            'notes'       => !empty($data['notes']) ? trim((string) $data['notes']) : null,
        ]);

        return (int) DB::lastInsertId();
    }

    /**
     * Aktualisiert eine Abo-Karte.
     *
     * @param array<string, mixed> $data
     */
    public static function updateSubscription(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        self::ensureCrmSchema();

        $sql = 'UPDATE `bs_subscriptions`
                SET `service_id` = :service_id,
                    `title` = :title,
                    `code` = :code,
                    `type` = :type,
                    `total_units` = :total_units,
                    `used_units` = :used_units,
                    `price` = :price,
                    `valid_from` = :valid_from,
                    `valid_until` = :valid_until,
                    `status` = :status,
                    `notes` = :notes,
                    `updated_at` = NOW()
                WHERE `id` = :id';

        try {
            DB::execute($sql, [
                'service_id'  => !empty($data['service_id']) ? (int) $data['service_id'] : null,
                'title'       => trim((string) ($data['title'] ?? 'Abonnement')),
                'code'        => !empty($data['code']) ? trim((string) $data['code']) : null,
                'type'        => !empty($data['type']) ? trim((string) $data['type']) : 'units',
                'total_units' => isset($data['total_units']) ? max(1, (int) $data['total_units']) : 10,
                'used_units'  => isset($data['used_units']) ? max(0, (int) $data['used_units']) : 0,
                'price'       => isset($data['price']) ? (float) $data['price'] : 0.00,
                'valid_from'  => !empty($data['valid_from']) ? trim((string) $data['valid_from']) : null,
                'valid_until' => !empty($data['valid_until']) ? trim((string) $data['valid_until']) : null,
                'status'      => !empty($data['status']) ? trim((string) $data['status']) : 'active',
                'notes'       => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                'id'          => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateSubscription Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteSubscription(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        self::ensureCrmSchema();
        try {
            DB::execute('DELETE FROM `bs_subscription_usages` WHERE `subscription_id` = :id', ['id' => $id]);
            DB::execute('DELETE FROM `bs_subscriptions` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteSubscription Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bucht Einheiten von einem Abonnement ab und protokolliert den Verbrauch.
     */
    public static function recordSubscriptionUsage(
        int $subscriptionId,
        int $units = 1,
        ?int $appointmentId = null,
        ?string $note = null,
        ?string $usedDate = null
    ): int {
        if ($subscriptionId <= 0 || $units <= 0) {
            return 0;
        }

        self::ensureCrmSchema();
        $sub = self::getSubscriptionById($subscriptionId);
        if (!$sub) {
            return 0;
        }

        $date = !empty($usedDate) ? trim($usedDate) : date('Y-m-d');

        // 1. Verbrauchsjournal schreiben
        $sql = 'INSERT INTO `bs_subscription_usages`
                (`subscription_id`, `appointment_id`, `units`, `used_date`, `note`, `created_at`)
                VALUES
                (:sub_id, :app_id, :units, :used_date, :note, NOW())';

        DB::execute($sql, [
            'sub_id'    => $subscriptionId,
            'app_id'    => $appointmentId ?: null,
            'units'     => $units,
            'used_date' => $date,
            'note'      => $note ? trim($note) : null,
        ]);
        $usageId = (int) DB::lastInsertId();

        // 2. Abo-Status und used_units anpassen
        $newUsed = (int) $sub['used_units'] + $units;
        $total = (int) $sub['total_units'];
        $newStatus = ($newUsed >= $total && $sub['type'] !== 'unlimited') ? 'depleted' : $sub['status'];

        DB::execute('UPDATE `bs_subscriptions` SET `used_units` = :used, `status` = :status, `updated_at` = NOW() WHERE `id` = :id', [
            'used'   => $newUsed,
            'status' => $newStatus,
            'id'     => $subscriptionId,
        ]);

        // 3. Falls Termin angegeben, dort die subscription_id verknüpfen
        if ($appointmentId && $appointmentId > 0 && self::isTableCreated('bs_appointments')) {
            DB::execute('UPDATE `bs_appointments` SET `subscription_id` = :sub_id, `updated_at` = NOW() WHERE `id` = :app_id', [
                'sub_id' => $subscriptionId,
                'app_id' => $appointmentId,
            ]);
        }

        return $usageId;
    }

    /**
     * Liefert die Verbrauchs-Historie für eine Abo-Karte.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptionUsages(int $subscriptionId): array
    {
        self::ensureCrmSchema();
        if (!self::isTableCreated('bs_subscription_usages') || $subscriptionId <= 0) {
            return [];
        }

        try {
            $sql = 'SELECT u.*, a.`date` AS appointment_date, a.`start_time` AS appointment_start, a.`end_time` AS appointment_end
                    FROM `bs_subscription_usages` u
                    LEFT JOIN `bs_appointments` a ON u.`appointment_id` = a.`id`
                    WHERE u.`subscription_id` = :sub_id
                    ORDER BY u.`used_date` DESC, u.`id` DESC';
            return DB::fetchAll($sql, ['sub_id' => $subscriptionId]);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getSubscriptionUsages Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Storniert eine Abbuchung und gibt die Einheiten dem Abo wieder frei.
     */
    public static function deleteSubscriptionUsage(int $usageId): bool
    {
        if ($usageId <= 0) {
            return false;
        }

        self::ensureCrmSchema();
        $usage = DB::fetchOne('SELECT * FROM `bs_subscription_usages` WHERE `id` = :id LIMIT 1', ['id' => $usageId]);
        if (!$usage) {
            return false;
        }

        $subId = (int) $usage['subscription_id'];
        $units = (int) $usage['units'];

        DB::execute('DELETE FROM `bs_subscription_usages` WHERE `id` = :id', ['id' => $usageId]);

        // Einheiten wieder gutschreiben
        $sub = self::getSubscriptionById($subId);
        if ($sub) {
            $newUsed = max(0, (int) $sub['used_units'] - $units);
            $newStatus = ($newUsed < (int) $sub['total_units'] && $sub['status'] === 'depleted') ? 'active' : $sub['status'];
            DB::execute('UPDATE `bs_subscriptions` SET `used_units` = :used, `status` = :status, `updated_at` = NOW() WHERE `id` = :id', [
                'used'   => $newUsed,
                'status' => $newStatus,
                'id'     => $subId,
            ]);
        }

        return true;
    }

    /**
     * 360-Grad CRM-Kundenakte: Stammdaten + Terminhistorie + Umsatz + Abonnements.
     *
     * @return array<string, mixed>|null
     */
    public static function getCustomerCrmDetails(int $id): ?array
    {
        $customer = self::getCustomerById($id);
        if (!$customer) {
            return null;
        }

        self::ensureCrmSchema();

        // 1. Termine des Kunden
        $appointments = self::getAppointments(['customer_id' => $id], 300, 0);

        // 2. Abos des Kunden
        $subscriptions = self::getSubscriptionsByCustomerId($id);

        // 3. Kennzahlen berechnen
        $totalAppointments = count($appointments);
        $completedAppointments = 0;
        $cancelledAppointments = 0;
        $totalRevenue = 0.00;
        $nextAppointment = null;
        $lastAppointment = null;
        $today = date('Y-m-d');

        foreach ($appointments as $app) {
            $status = (string) ($app['status'] ?? '');
            $price = (float) ($app['price'] ?? 0.00);
            $appDate = (string) ($app['date'] ?? '');

            if ($status === 'completed') {
                $completedAppointments++;
                $totalRevenue += $price;
            } elseif (in_array($status, ['requested', 'confirmed'], true)) {
                $totalRevenue += $price;
                if ($appDate >= $today && ($nextAppointment === null || $appDate < $nextAppointment['date'])) {
                    $nextAppointment = $app;
                }
            } elseif (in_array($status, ['cancelled_customer', 'cancelled_system'], true)) {
                $cancelledAppointments++;
            }

            if ($appDate <= $today && ($lastAppointment === null || $appDate > $lastAppointment['date'])) {
                $lastAppointment = $app;
            }
        }

        // Zuzüglich Abo-Preise zum Gesamtumsatz
        $totalSubscriptionRevenue = 0.00;
        $totalUnitsPurchased = 0;
        $totalUnitsUsed = 0;
        $activeSubscriptionsCount = 0;

        foreach ($subscriptions as $sub) {
            $totalSubscriptionRevenue += (float) ($sub['price'] ?? 0.00);
            $totalUnitsPurchased += (int) ($sub['total_units'] ?? 0);
            $totalUnitsUsed += (int) ($sub['used_units'] ?? 0);
            if (($sub['status'] ?? '') === 'active') {
                $activeSubscriptionsCount++;
            }
        }

        $overallRevenue = $totalRevenue + $totalSubscriptionRevenue;
        $remainingUnits = max(0, $totalUnitsPurchased - $totalUnitsUsed);

        return [
            'customer'                 => $customer,
            'appointments'             => $appointments,
            'subscriptions'            => $subscriptions,
            'total_appointments'       => $totalAppointments,
            'completed_appointments'   => $completedAppointments,
            'cancelled_appointments'   => $cancelledAppointments,
            'appointment_revenue'      => $totalRevenue,
            'subscription_revenue'     => $totalSubscriptionRevenue,
            'overall_revenue'          => $overallRevenue,
            'active_subscriptions'     => $activeSubscriptionsCount,
            'remaining_units'          => $remainingUnits,
            'next_appointment'         => $nextAppointment,
            'last_appointment'         => $lastAppointment,
        ];
    }

    // =========================================================================
    // 5. STANDORTE (bs_locations)
    // =========================================================================

    /**
     * @param bool|array<string, mixed> $onlyActive
     * @return array<int, array<string, mixed>>
     */
    public static function getLocations($onlyActive = true): array
    {
        if (!self::isTableCreated('bs_locations')) {
            return [];
        }

        $activeOnly = true;
        if (is_bool($onlyActive)) {
            $activeOnly = $onlyActive;
        } elseif (is_array($onlyActive)) {
            $activeOnly = !empty($onlyActive['is_active']);
        }

        try {
            $sql = 'SELECT * FROM `bs_locations` ' . ($activeOnly ? 'WHERE `is_active` = 1 ' : '') . 'ORDER BY `name` ASC';
            return DB::fetchAll($sql);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getLocations Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function getLocationById(int $id): ?array
    {
        if (!self::isTableCreated('bs_locations') || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_locations` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getLocationById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    public static function createLocation(array $data): int
    {
        if (!self::isTableCreated('bs_locations')) {
            return 0;
        }

        $sql = 'INSERT INTO `bs_locations` (`name`, `code`, `address`, `notes`, `is_active`, `created_at`, `updated_at`)
                VALUES (:name, :code, :address, :notes, :is_active, NOW(), NOW())';

        DB::execute($sql, [
            'name'      => trim((string) ($data['name'] ?? '')),
            'code'      => !empty($data['code']) ? trim((string) $data['code']) : null,
            'address'   => !empty($data['address']) ? trim((string) $data['address']) : null,
            'notes'     => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
        ]);

        return (int) DB::lastInsertId();
    }

    // =========================================================================
    // 6. TERMINE / BUCHUNGEN (bs_appointments)
    // =========================================================================

    /**
     * Holt Termine mit aufgelösten Relationen (Service, Kunde, Fahrlehrer, Boot, Standort).
     *
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAppointments(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        if (!self::isTableCreated('bs_appointments')) {
            return [];
        }

        self::ensureCrmSchema();

        try {
            $where = [];
            $params = [];

            if (!empty($filters['date'])) {
                $where[] = 'a.`date` = :date';
                $params['date'] = (string) $filters['date'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = 'a.`date` >= :date_from';
                $params['date_from'] = (string) $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = 'a.`date` <= :date_to';
                $params['date_to'] = (string) $filters['date_to'];
            }

            if (!empty($filters['customer_id'])) {
                $where[] = 'a.`customer_id` = :customer_id';
                $params['customer_id'] = (int) $filters['customer_id'];
            }

            if (!empty($filters['instructor_id'])) {
                $where[] = 'a.`instructor_id` = :instructor_id';
                $params['instructor_id'] = (int) $filters['instructor_id'];
            }

            if (!empty($filters['boat_id'])) {
                $where[] = 'a.`boat_id` = :boat_id';
                $params['boat_id'] = (int) $filters['boat_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = 'a.`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['instructor_payout_status'])) {
                $where[] = 'a.`instructor_payout_status` = :payout_status';
                $params['payout_status'] = (string) $filters['instructor_payout_status'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT a.*,
                           s.`name` AS service_name, s.`category` AS service_category, s.`duration_minutes` AS service_duration,
                           c.`first_name` AS customer_first_name, c.`last_name` AS customer_last_name, c.`email` AS customer_email, c.`phone` AS customer_phone,
                           i.`first_name` AS instructor_first_name, i.`last_name` AS instructor_last_name, i.`color_code` AS instructor_color,
                           i.`pay_rate_type`, i.`pay_rate_amount`, i.`payroll_external_code`,
                           b.`name` AS boat_name, b.`type` AS boat_type,
                           l.`name` AS location_name, l.`code` AS location_code
                    FROM `bs_appointments` a
                    LEFT JOIN `bs_services` s ON a.`service_id` = s.`id`
                    LEFT JOIN `bs_customers` c ON a.`customer_id` = c.`id`
                    LEFT JOIN `bs_instructors` i ON a.`instructor_id` = i.`id`
                    LEFT JOIN `bs_boats` b ON a.`boat_id` = b.`id`
                    LEFT JOIN `bs_locations` l ON a.`location_id` = l.`id`
                    {$whereSql}
                    ORDER BY a.`date` ASC, a.`start_time` ASC
                    LIMIT {$limit} OFFSET {$offset}";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getAppointments Fehler: ' . $e->getMessage());
            return [];
        }
    }

    public static function countAppointments(?array $filters = null): int
    {
        if (!self::isTableCreated('bs_appointments')) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['date'])) {
                $where[] = '`date` = :date';
                $params['date'] = (string) $filters['date'];
            }

            if (!empty($filters['customer_id'])) {
                $where[] = '`customer_id` = :customer_id';
                $params['customer_id'] = (int) $filters['customer_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = '`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['instructor_payout_status'])) {
                $where[] = '`instructor_payout_status` = :payout_status';
                $params['payout_status'] = (string) $filters['instructor_payout_status'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $row = DB::fetchOne("SELECT COUNT(*) AS c FROM `bs_appointments` {$whereSql}", $params);
            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::countAppointments Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getAppointmentById(int $id): ?array
    {
        if (!self::isTableCreated('bs_appointments') || $id <= 0) {
            return null;
        }

        try {
            $sql = "SELECT a.*,
                           s.`name` AS service_name, s.`category` AS service_category, s.`duration_minutes` AS service_duration,
                           c.`first_name` AS customer_first_name, c.`last_name` AS customer_last_name, c.`email` AS customer_email, c.`phone` AS customer_phone,
                           c.`notify_email` AS customer_notify_email,
                           i.`first_name` AS instructor_first_name, i.`last_name` AS instructor_last_name, i.`color_code` AS instructor_color,
                           i.`email` AS instructor_email, i.`phone` AS instructor_phone,
                           i.`pay_rate_type`, i.`pay_rate_amount`, i.`payroll_external_code`,
                           b.`name` AS boat_name, b.`type` AS boat_type,
                           l.`name` AS location_name, l.`code` AS location_code, l.`address` AS location_address
                    FROM `bs_appointments` a
                    LEFT JOIN `bs_services` s ON a.`service_id` = s.`id`
                    LEFT JOIN `bs_customers` c ON a.`customer_id` = c.`id`
                    LEFT JOIN `bs_instructors` i ON a.`instructor_id` = i.`id`
                    LEFT JOIN `bs_boats` b ON a.`boat_id` = b.`id`
                    LEFT JOIN `bs_locations` l ON a.`location_id` = l.`id`
                    WHERE a.`id` = :id
                    LIMIT 1";

            $row = DB::fetchOne($sql, ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getAppointmentById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ermittelt alle anstehenden Termine, die für eine Erinnerung fällig sind.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAppointmentsDueForReminder(int $hoursAhead = 24): array
    {
        if (!self::isTableCreated('bs_appointments')) {
            return [];
        }

        try {
            $sql = "SELECT a.*,
                           s.`name` AS service_name, s.`category` AS service_category, s.`duration_minutes` AS service_duration,
                           c.`first_name` AS customer_first_name, c.`last_name` AS customer_last_name, c.`email` AS customer_email, c.`phone` AS customer_phone,
                           c.`notify_email` AS customer_notify_email,
                           i.`first_name` AS instructor_first_name, i.`last_name` AS instructor_last_name, i.`email` AS instructor_email, i.`phone` AS instructor_phone,
                           b.`name` AS boat_name, b.`type` AS boat_type,
                           l.`name` AS location_name, l.`code` AS location_code, l.`address` AS location_address
                    FROM `bs_appointments` a
                    JOIN `bs_customers` c ON a.`customer_id` = c.`id`
                    LEFT JOIN `bs_services` s ON a.`service_id` = s.`id`
                    LEFT JOIN `bs_instructors` i ON a.`instructor_id` = i.`id`
                    LEFT JOIN `bs_boats` b ON a.`boat_id` = b.`id`
                    LEFT JOIN `bs_locations` l ON a.`location_id` = l.`id`
                    WHERE a.`status` = 'confirmed'
                      AND a.`reminder_sent_at` IS NULL
                      AND TIMESTAMP(a.`date`, a.`start_time`) >= NOW()
                      AND TIMESTAMP(a.`date`, a.`start_time`) <= DATE_ADD(NOW(), INTERVAL :hours HOUR)
                    ORDER BY a.`date` ASC, a.`start_time` ASC";

            return DB::fetchAll($sql, ['hours' => $hoursAhead]);
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::getAppointmentsDueForReminder Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Markiert einen Termin als erinnert (reminder_sent_at = NOW()).
     */
    public static function markAppointmentReminderSent(int $id): bool
    {
        if (!self::isTableCreated('bs_appointments') || $id <= 0) {
            return false;
        }

        try {
            DB::execute('UPDATE `bs_appointments` SET `reminder_sent_at` = NOW() WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::markAppointmentReminderSent Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function createAppointment(array $data): int
    {
        $payoutAmount = 0.00;
        if (isset($data['instructor_payout_amount'])) {
            $payoutAmount = (float) $data['instructor_payout_amount'];
        } elseif (!empty($data['instructor_id']) && class_exists('InstructorPayoutService')) {
            $inst = self::getInstructorById((int) $data['instructor_id']);
            if ($inst) {
                $payoutAmount = InstructorPayoutService::calculatePayout($data, $inst);
            }
        }

        // Verfügbarkeits-Validierung vor dem Speichern
        if (!empty($data['instructor_id']) && class_exists('CoachAvailabilityService')) {
            $dtStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', ((string) $data['date']) . ' ' . substr((string) $data['start_time'], 0, 5));
            $dtEnd   = \DateTimeImmutable::createFromFormat('Y-m-d H:i', ((string) $data['date']) . ' ' . substr((string) $data['end_time'], 0, 5));
            $locId   = !empty($data['location_id']) ? (int) $data['location_id'] : null;
            if ($dtStart && $dtEnd) {
                $avail = CoachAvailabilityService::isInstructorAvailableAt((int) $data['instructor_id'], $dtStart, $dtEnd, $locId);
                if (!$avail['available']) {
                    throw new \InvalidArgumentException($avail['reason'] ?? 'Coach ist in diesem Zeitraum nicht verfügbar (Urlaub/Feiertag/Wochenplan).');
                }
            }
        }

        $payoutStatus = !empty($data['instructor_payout_status']) ? (string) $data['instructor_payout_status'] : 'open';
        $payoutNote = !empty($data['instructor_payout_note']) ? trim((string) $data['instructor_payout_note']) : null;

        $sql = 'INSERT INTO `bs_appointments`
                (`date`, `start_time`, `end_time`, `service_id`, `customer_id`, `instructor_id`, `boat_id`, `location_id`, `status`, `price`, `instructor_payout_status`, `instructor_payout_amount`, `instructor_payout_note`, `notes`, `created_at`, `updated_at`)
                VALUES
                (:date, :start_time, :end_time, :service_id, :customer_id, :instructor_id, :boat_id, :location_id, :status, :price, :instructor_payout_status, :instructor_payout_amount, :instructor_payout_note, :notes, NOW(), NOW())';

        DB::execute($sql, [
            'date'                     => (string) $data['date'],
            'start_time'               => (string) $data['start_time'],
            'end_time'                 => (string) $data['end_time'],
            'service_id'               => (int) $data['service_id'],
            'customer_id'              => (int) $data['customer_id'],
            'instructor_id'            => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'boat_id'                  => !empty($data['boat_id']) ? (int) $data['boat_id'] : null,
            'location_id'              => !empty($data['location_id']) ? (int) $data['location_id'] : null,
            'status'                   => !empty($data['status']) ? (string) $data['status'] : 'requested',
            'price'                    => isset($data['price']) ? (float) $data['price'] : 0.00,
            'instructor_payout_status' => $payoutStatus,
            'instructor_payout_amount' => $payoutAmount,
            'instructor_payout_note'   => $payoutNote,
            'notes'                    => !empty($data['notes']) ? trim((string) $data['notes']) : null,
        ]);

        return (int) DB::lastInsertId();
    }

    public static function updateAppointment(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $payoutFieldsSql = '';
        $params = [
            'date'          => (string) $data['date'],
            'start_time'    => (string) $data['start_time'],
            'end_time'      => (string) $data['end_time'],
            'service_id'    => (int) $data['service_id'],
            'customer_id'   => (int) $data['customer_id'],
            'instructor_id' => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'boat_id'       => !empty($data['boat_id']) ? (int) $data['boat_id'] : null,
            'location_id'   => !empty($data['location_id']) ? (int) $data['location_id'] : null,
            'status'        => !empty($data['status']) ? (string) $data['status'] : 'requested',
            'price'         => isset($data['price']) ? (float) $data['price'] : 0.00,
            'notes'         => !empty($data['notes']) ? trim((string) $data['notes']) : null,
            'id'            => $id,
        ];

        if (array_key_exists('instructor_payout_status', $data)) {
            $payoutFieldsSql .= ', `instructor_payout_status` = :payout_status';
            $params['payout_status'] = (string) $data['instructor_payout_status'];
        }
        if (array_key_exists('instructor_payout_amount', $data)) {
            $payoutFieldsSql .= ', `instructor_payout_amount` = :payout_amount';
            $params['payout_amount'] = (float) $data['instructor_payout_amount'];
        }
        if (array_key_exists('instructor_payout_note', $data)) {
            $payoutFieldsSql .= ', `instructor_payout_note` = :payout_note';
            $params['payout_note'] = !empty($data['instructor_payout_note']) ? trim((string) $data['instructor_payout_note']) : null;
        }

        $sql = "UPDATE `bs_appointments`
                SET `date` = :date,
                    `start_time` = :start_time,
                    `end_time` = :end_time,
                    `service_id` = :service_id,
                    `customer_id` = :customer_id,
                    `instructor_id` = :instructor_id,
                    `boat_id` = :boat_id,
                    `location_id` = :location_id,
                    `status` = :status,
                    `price` = :price,
                    `notes` = :notes
                    {$payoutFieldsSql},
                    `updated_at` = NOW()
                WHERE `id` = :id";

        try {
            DB::execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateAppointment Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function updateAppointmentPayout(
        int $id,
        float $amount,
        ?string $status = null,
        ?string $note = null
    ): bool {
        if ($id <= 0) {
            return false;
        }

        $sets = ['`instructor_payout_amount` = :amount'];
        $params = [
            'amount' => round($amount, 2),
            'id'     => $id,
        ];

        if ($status !== null) {
            $sets[] = '`instructor_payout_status` = :status';
            $params['status'] = $status;
        }

        if ($note !== null) {
            $sets[] = '`instructor_payout_note` = :note';
            $params['note'] = trim($note);
        }

        $sets[] = '`updated_at` = NOW()';
        $sql = 'UPDATE `bs_appointments` SET ' . implode(', ', $sets) . ' WHERE `id` = :id';
        try {
            DB::execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateAppointmentPayout Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function bulkUpdateAppointmentPayoutStatus(array $ids, string $status): int
    {
        if (empty($ids)) {
            return 0;
        }

        $cleanIds = array_filter(array_map('intval', $ids), static fn($i) => $i > 0);
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "UPDATE `bs_appointments` SET `instructor_payout_status` = ?, `updated_at` = NOW() WHERE `id` IN ({$placeholders})";
        $stmt = DB::getConnection()->prepare($sql);
        $stmt->execute(array_merge([$status], $cleanIds));
        return $stmt->rowCount();
    }

    public static function updateAppointmentStatus(int $id, string $status): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            DB::execute(
                'UPDATE `bs_appointments` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id',
                ['status' => $status, 'id' => $id]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::updateAppointmentStatus Fehler: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteAppointment(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            DB::execute('DELETE FROM `bs_appointments` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::deleteAppointment Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prüft, ob ein Zeitfenster mit bestehenden Terminen kollidiert.
     * Ignoriert stornierte Termine ('cancelled_customer', 'cancelled_system').
     *
     * @return array<string, mixed> ['has_conflict' => bool, 'reason' => string|null]
     */
    public static function checkConflicts(
        string $date,
        string $startTime,
        string $endTime,
        ?int $boatId = null,
        ?int $instructorId = null,
        ?int $customerId = null,
        ?int $excludeAppointmentId = null,
        ?int $locationId = null
    ): array {
        if (!self::isTableCreated('bs_appointments')) {
            return ['has_conflict' => false, 'reason' => null];
        }

        try {
            // Überlappungs-Bedingung: Termin beginnt vor unserem Ende und endet nach unserem Beginn
            $timeOverlap = '(:start_time < a.`end_time` AND :end_time > a.`start_time`)';
            $notCancelled = "a.`status` NOT IN ('cancelled_customer', 'cancelled_system')";

            // 1. Boot-Konflikt
            if ($boatId !== null && $boatId > 0) {
                $sql = "SELECT a.id, b.name AS boat_name FROM `bs_appointments` a
                        LEFT JOIN `bs_boats` b ON a.`boat_id` = b.`id`
                        WHERE a.`date` = :date AND a.`boat_id` = :boat_id AND {$timeOverlap} AND {$notCancelled}";
                $params = [
                    'date'       => $date,
                    'boat_id'    => $boatId,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ];
                if ($excludeAppointmentId) {
                    $sql .= ' AND a.`id` != :exclude_id';
                    $params['exclude_id'] = $excludeAppointmentId;
                }
                $existing = DB::fetchOne($sql, $params);
                if ($existing) {
                    return [
                        'has_conflict' => true,
                        'reason'       => 'Das ausgewählte Boot ist im gewünschten Zeitraum bereits vergeben.',
                    ];
                }
            }

            // 2. Fahrlehrer-Konflikt & Verfügbarkeits-Prüfung
            if ($instructorId !== null && $instructorId > 0) {
                $sql = "SELECT a.id, i.first_name, i.last_name FROM `bs_appointments` a
                        LEFT JOIN `bs_instructors` i ON a.`instructor_id` = i.`id`
                        WHERE a.`date` = :date AND a.`instructor_id` = :instructor_id AND {$timeOverlap} AND {$notCancelled}";
                $params = [
                    'date'          => $date,
                    'instructor_id' => $instructorId,
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                ];
                if ($excludeAppointmentId) {
                    $sql .= ' AND a.`id` != :exclude_id';
                    $params['exclude_id'] = $excludeAppointmentId;
                }
                $existing = DB::fetchOne($sql, $params);
                if ($existing) {
                    return [
                        'has_conflict' => true,
                        'reason'       => 'Der ausgewählte Fahrlehrer ist im gewünschten Zeitraum bereits eingeteilt.',
                    ];
                }

                // 2b. Prüfen via CoachAvailabilityService (Wochen-Templates, Absenzen, Feiertage & Overrides)
                if (class_exists('CoachAvailabilityService')) {
                    $dtStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} " . substr($startTime, 0, 5));
                    $dtEnd   = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} " . substr($endTime, 0, 5));
                    if ($dtStart && $dtEnd) {
                        $availCheck = CoachAvailabilityService::isInstructorAvailableAt($instructorId, $dtStart, $dtEnd, $locationId);
                        if (!$availCheck['available']) {
                            return [
                                'has_conflict' => true,
                                'reason'       => $availCheck['reason'] ?? 'Der ausgewählte Fahrlehrer ist im gewünschten Zeitraum nicht verfügbar.',
                            ];
                        }
                    }
                } elseif (class_exists('CoachService')) {
                    $absence = CoachService::checkCoachAbsence($instructorId, $date, $startTime, $endTime, $locationId);
                    if ($absence['is_blocked']) {
                        return [
                            'has_conflict' => true,
                            'reason'       => $absence['reason'] ?? 'Der ausgewählte Fahrlehrer ist im gewünschten Zeitraum abwesend.',
                        ];
                    }
                }
            }

            // 3. Kunden-Konflikt
            if ($customerId !== null && $customerId > 0) {
                $sql = "SELECT a.id FROM `bs_appointments` a
                        WHERE a.`date` = :date AND a.`customer_id` = :customer_id AND {$timeOverlap} AND {$notCancelled}";
                $params = [
                    'date'        => $date,
                    'customer_id' => $customerId,
                    'start_time'  => $startTime,
                    'end_time'    => $endTime,
                ];
                if ($excludeAppointmentId) {
                    $sql .= ' AND a.`id` != :exclude_id';
                    $params['exclude_id'] = $excludeAppointmentId;
                }
                $existing = DB::fetchOne($sql, $params);
                if ($existing) {
                    return [
                        'has_conflict' => true,
                        'reason'       => 'Sie haben für diesen Zeitraum bereits einen anderen Termin gebucht.',
                    ];
                }
            }

            return ['has_conflict' => false, 'reason' => null];
        } catch (\Throwable $e) {
            error_log('BoatingSchoolRepository::checkConflicts Fehler: ' . $e->getMessage());
            return ['has_conflict' => false, 'reason' => null];
        }
    }

    // =========================================================================
    // 7. DASHBOARD-STATISTIKEN
    // =========================================================================

    /**
     * @return array<string, int>
     */
    public static function getDashboardStats(): array
    {
        return [
            'boats_total'           => self::countBoats(),
            'boats_active'          => self::countBoats(['is_active' => 1]),
            'instructors_total'     => self::countInstructors(),
            'instructors_active'    => self::countInstructors(['is_active' => 1]),
            'services_total'        => self::countServices(),
            'services_active'       => self::countServices(['is_active' => 1]),
            'customers_total'       => self::countCustomers(),
            'students_total'        => self::countCustomers(['type' => 'student']),
            'renters_total'         => self::countCustomers(['type' => 'renter']),
            'both_total'            => self::countCustomers(['type' => 'both']),
            'appointments_total'    => self::countAppointments(),
            'appointments_req'      => self::countAppointments(['status' => 'requested']),
            'appointments_conf'     => self::countAppointments(['status' => 'confirmed']),
        ];
    }
}

