<?php

declare(strict_types=1);

/**
 * ServiceService
 *
 * Verwaltet Leistungen, Kurse und Tarife (bs_services) der Bootsfahrschule.
 *
 * Schema bs_services:
 *  - id (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
 *  - name (VARCHAR(255) NOT NULL)
 *  - code (VARCHAR(64) NULL: z. B. PRAX-MOT-90)
 *  - category (VARCHAR(64) NOT NULL: practical, theory, rental, exam, package, other)
 *  - boat_type (VARCHAR(64) NOT NULL DEFAULT 'any': any, motor, sail, rib, electric)
 *  - duration_minutes (INT UNSIGNED NULL DEFAULT 60)
 *  - price (DECIMAL(10,2) NOT NULL DEFAULT 0.00)
 *  - tax_rate (DECIMAL(5,2) NOT NULL DEFAULT 0.00)
 *  - description (TEXT NULL)
 *  - is_active (TINYINT(1) NOT NULL DEFAULT 1)
 *  - created_at (DATETIME)
 *  - updated_at (DATETIME)
 */
final class ServiceService
{
    /** @var array<string, string> Kategorien */
    public const CATEGORIES = [
        'practical' => 'Praxisunterricht',
        'theory'    => 'Theoriekurs',
        'rental'    => 'Bootsmiete / Charter',
        'exam'      => 'Prüfungsfahrt',
        'package'   => 'Pauschale / Ausbildungspaket',
        'other'     => 'Sonstiges',
    ];

    /** @var array<string, string> Bootstypen für Leistungen */
    public const BOAT_TYPES = [
        'any'      => 'Alle / Beliebig',
        'motor'    => 'Motorboot',
        'sail'     => 'Segelboot',
        'rib'      => 'RIB / Festrumpfschlauchboot',
        'electric' => 'Elektroboot',
    ];

    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }

    public static function getBoatTypes(): array
    {
        return self::BOAT_TYPES;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return BoatingSchoolRepository::getServices($filters, $limit, $offset);
    }

    public static function count(array $filters = []): int
    {
        return BoatingSchoolRepository::countServices($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getServiceById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return BoatingSchoolRepository::createService($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateService($id, $data);
    }

    public static function delete(int $id): bool
    {
        return BoatingSchoolRepository::deleteService($id);
    }

    public static function toggleStatus(int $id): bool
    {
        return BoatingSchoolRepository::toggleServiceStatus($id);
    }
}
