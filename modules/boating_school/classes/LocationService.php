<?php

declare(strict_types=1);

/**
 * LocationService
 *
 * Verwaltet Ausbildungsstandorte, Stege und Häfen (bs_locations).
 */
class LocationService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(bool $onlyActive = true): array
    {
        return BoatingSchoolRepository::getLocations($onlyActive);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getLocationById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return BoatingSchoolRepository::createLocation($data);
    }
}
