<?php

declare(strict_types=1);

/**
 * InstructorService
 *
 * Verwaltet Fahrlehrer (bs_instructors) der Bootsfahrschule.
 *
 * Schema bs_instructors:
 *  - id (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
 *  - user_id (INT UNSIGNED NULL: Verknüpfung zu users.id)
 *  - first_name (VARCHAR(128) NOT NULL)
 *  - last_name (VARCHAR(128) NOT NULL)
 *  - email (VARCHAR(191) NULL)
 *  - phone (VARCHAR(64) NULL)
 *  - license_number (VARCHAR(128) NULL: Fahrlehrer-Patent/Ausweis)
 *  - qualifications (TEXT NULL: z.B. Kat. A Motor, Kat. D Segel, etc.)
 *  - color_code (VARCHAR(16) NULL DEFAULT '#0d6efd')
 *  - image_path (VARCHAR(255) NULL)
 *  - notes (TEXT NULL)
 *  - is_active (TINYINT(1) NOT NULL DEFAULT 1)
 *  - created_at (DATETIME)
 *  - updated_at (DATETIME)
 */
final class InstructorService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return BoatingSchoolRepository::getInstructors($filters, $limit, $offset);
    }

    public static function count(array $filters = []): int
    {
        return BoatingSchoolRepository::countInstructors($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getInstructorById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return BoatingSchoolRepository::createInstructor($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateInstructor($id, $data);
    }

    public static function delete(int $id): bool
    {
        return BoatingSchoolRepository::deleteInstructor($id);
    }

    public static function toggleStatus(int $id): bool
    {
        return BoatingSchoolRepository::toggleInstructorStatus($id);
    }
}
