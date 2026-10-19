<?php

declare(strict_types=1);

/**
 * BoatService
 *
 * Verwaltet Boote (bs_boats) der Bootsfahrschule und Flotte.
 *
 * Schema bs_boats:
 *  - id (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
 *  - name (VARCHAR(255) NOT NULL)
 *  - registration (VARCHAR(64) NULL)
 *  - type (VARCHAR(64) NULL: motor, sail, rib, electric)
 *  - capacity (INT UNSIGNED NULL)
 *  - engine_power (INT UNSIGNED NULL)
 *  - color (VARCHAR(64) NULL)
 *  - image_path (VARCHAR(255) NULL)
 *  - notes (TEXT NULL)
 *  - is_active (TINYINT(1) NOT NULL DEFAULT 1)
 *  - created_at (DATETIME)
 *  - updated_at (DATETIME)
 */
final class BoatService
{
    /** @var array<string, string> Gültige Bootstypen */
    public const TYPES = [
        'motor'    => 'Motorboot',
        'sail'     => 'Segelboot',
        'rib'      => 'RIB / Festrumpfschlauchboot',
        'electric' => 'Elektroboot',
    ];

    public static function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return BoatingSchoolRepository::getBoats($filters, $limit, $offset);
    }

    public static function count(array $filters = []): int
    {
        return BoatingSchoolRepository::countBoats($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getBoatById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return BoatingSchoolRepository::createBoat($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateBoat($id, $data);
    }

    public static function delete(int $id): bool
    {
        return BoatingSchoolRepository::deleteBoat($id);
    }

    public static function toggleStatus(int $id): bool
    {
        return BoatingSchoolRepository::toggleBoatStatus($id);
    }
}
