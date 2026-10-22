<?php

declare(strict_types=1);

/**
 * CoachAvailabilityService
 *
 * Verwaltet und prüft die Verfügbarkeit von Fahrlehrern (Coaches):
 * - Wochen-Templates (bs_coach_weekly_templates)
 * - Tages-Absenzen / Sperrzeiten (bs_coach_availabilities) mit Standortbezug (location_id)
 * - Schweizer Feiertags-Engine mit Feiertags-Overrides (type='holiday_override' / is_holiday_active=1)
 * - Zentrale Schnittstelle: isInstructorAvailableAt(...)
 */
final class CoachAvailabilityService
{
    /**
     * Stellt sicher, dass Tabellen für Wochen-Templates und Verfügbarkeiten idempotent existieren.
     */
    public static function ensureTables(): void
    {
        if (!class_exists('DB')) {
            return;
        }

        try {
            // 1. bs_coach_availabilities Spalten nachrüsten (location_id, is_holiday_active)
            if (BoatingSchoolRepository::isTableCreated('bs_coach_availabilities')) {
                BoatingSchoolRepository::ensureColumn('bs_coach_availabilities', 'location_id', 'INT UNSIGNED NULL AFTER `end_time`');
                BoatingSchoolRepository::ensureColumn('bs_coach_availabilities', 'is_holiday_active', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`');
            }

            // 2. bs_coach_weekly_templates anlegen
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_coach_weekly_templates` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `instructor_id` INT UNSIGNED NOT NULL,
                `weekday`       TINYINT UNSIGNED NOT NULL COMMENT '1=Mo, 2=Di, ..., 7=So',
                `start_time`    TIME NOT NULL,
                `end_time`      TIME NOT NULL,
                `location_id`   INT UNSIGNED NULL COMMENT 'NULL = alle Standorte',
                `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`    DATETIME NOT NULL,
                `updated_at`    DATETIME NOT NULL,
                INDEX `idx_coach_tpl_inst_wd` (`instructor_id`, `weekday`, `is_active`),
                INDEX `idx_coach_tpl_loc` (`location_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::ensureTables Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Prüft, ob ein Datum in der Schweiz ein offizieller Feiertag ist.
     *
     * @return string|null Name des Feiertags oder null
     */
    public static function isPublicHoliday(\DateTimeInterface $date): ?string
    {
        $year = (int) $date->format('Y');
        $md   = $date->format('m-d');

        // 1. Feste Schweizer Feiertage
        $fixedHolidays = [
            '01-01' => 'Neujahr',
            '01-02' => 'Berchtoldstag',
            '05-01' => 'Tag der Arbeit',
            '08-01' => 'Bundesfeiertag (1. August)',
            '12-25' => 'Weihnachten',
            '12-26' => 'Stephanstag',
        ];

        if (isset($fixedHolidays[$md])) {
            return $fixedHolidays[$md];
        }

        // 2. Bewegliche Feiertage basierend auf Ostersonntag
        $easterDays = easter_days($year);
        $easterSunday = (new \DateTimeImmutable(sprintf('%d-03-21', $year)))->modify("+{$easterDays} days");

        $goodFriday   = $easterSunday->modify('-2 days')->format('m-d');
        $easterMonday = $easterSunday->modify('+1 day')->format('m-d');
        $ascension    = $easterSunday->modify('+39 days')->format('m-d'); // Auffahrt
        $whitMonday   = $easterSunday->modify('+50 days')->format('m-d'); // Pfingstmontag

        $dynamicHolidays = [
            $goodFriday   => 'Karfreitag',
            $easterMonday => 'Ostermontag',
            $ascension    => 'Auffahrt (Christi Himmelfahrt)',
            $whitMonday   => 'Pfingstmontag',
        ];

        return $dynamicHolidays[$md] ?? null;
    }

    /**
     * Zentrale Verfügbarkeitsprüfung eines Fahrlehrers für einen konkreten Zeitraum & Standort.
     *
     * Regelwerk:
     * 1. Feiertag: Standardmäßig NICHT buchbar, außer ein Override (type='holiday_override' / is_holiday_active=1) existiert.
     * 2. Weekly Template: Falls Templates für diesen Coach definiert sind, muss der Slot innerhalb eines aktiven Templates liegen.
     * 3. Absenzen: Keine überschneidenden Absenzen/Sperrzeiten (Ferien, Krankheit, etc.) für den Standort (oder alle Standorte).
     *
     * @param int $instructorId Fahrlehrer ID
     * @param \DateTimeInterface $start Startzeitpunkt
     * @param \DateTimeInterface $end Endzeitpunkt
     * @param int|null $locationId Standort-ID (NULL = alle / standortunabhängig)
     * @return array{available: bool, reason: string|null, code: string}
     */
    public static function isInstructorAvailableAt(
        int $instructorId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $locationId = null
    ): array {
        if ($instructorId <= 0) {
            return [
                'available' => false,
                'reason'    => 'Ungültige Fahrlehrer-ID.',
                'code'      => 'INVALID_INSTRUCTOR',
            ];
        }

        self::ensureTables();

        $dateStr      = $start->format('Y-m-d');
        $startTimeStr = $start->format('H:i');
        $endTimeStr   = $end->format('H:i');
        $weekday      = (int) $start->format('N'); // 1 = Mo, ..., 7 = So

        // ---------------------------------------------------------------------
        // 1. FEIERTAGS-PRÜFUNG & OVERRIDE
        // ---------------------------------------------------------------------
        $holidayName = self::isPublicHoliday($start);
        if ($holidayName !== null) {
            // Prüfe, ob für diesen Coach ein Feiertags-Override für diesen Tag existiert
            $override = self::findHolidayOverride($instructorId, $dateStr, $startTimeStr, $endTimeStr);
            if (!$override) {
                return [
                    'available' => false,
                    'reason'    => sprintf('Fahrlehrer an Feiertag nicht verfügbar (%s). Keine Feiertags-Freigabe aktiv.', $holidayName),
                    'code'      => 'PUBLIC_HOLIDAY',
                ];
            }
        }

        // ---------------------------------------------------------------------
        // 2. WOCHEN-TEMPLATE PRÜFUNG (bs_coach_weekly_templates)
        // ---------------------------------------------------------------------
        $hasAnyTemplates = self::hasWeeklyTemplatesConfigured($instructorId);
        if ($hasAnyTemplates) {
            $matchingTemplate = self::findMatchingWeeklyTemplate($instructorId, $weekday, $startTimeStr, $endTimeStr, $locationId);
            if (!$matchingTemplate) {
                return [
                    'available' => false,
                    'reason'    => sprintf(
                        'Fahrlehrer hat laut Wochenplan am %s zwischen %s und %s keine Arbeitszeit eingeteilt.',
                        self::getWeekdayLabel($weekday),
                        $startTimeStr,
                        $endTimeStr
                    ),
                    'code'      => 'OUTSIDE_WEEKLY_SCHEDULE',
                ];
            }
        }

        // ---------------------------------------------------------------------
        // 3. ABSENZEN & SPERRZEITEN PRÜFUNG (bs_coach_availabilities)
        // ---------------------------------------------------------------------
        $absence = self::findConflictingAbsence($instructorId, $dateStr, $startTimeStr, $endTimeStr, $locationId);
        if ($absence !== null) {
            return [
                'available' => false,
                'reason'    => $absence['reason'],
                'code'      => 'COACH_ABSENCE',
            ];
        }

        return [
            'available' => true,
            'reason'    => null,
            'code'      => 'AVAILABLE',
        ];
    }

    /**
     * Prüft, ob ein Feiertags-Override für den Fahrlehrer existiert.
     *
     * @return array<string, mixed>|null
     */
    public static function findHolidayOverride(int $instructorId, string $date, string $startTime, string $endTime): ?array
    {
        try {
            $sql = "SELECT * FROM `bs_coach_availabilities`
                    WHERE `instructor_id` = :inst_id
                      AND `date` = :date
                      AND (`type` = 'holiday_override' OR `is_holiday_active` = 1 OR `type` = 'available')";
            $records = DB::fetchAll($sql, [
                'inst_id' => $instructorId,
                'date'    => $date,
            ]);

            foreach ($records as $rec) {
                // Ganztägiges Override
                if (empty($rec['start_time']) || empty($rec['end_time'])) {
                    return $rec;
                }

                $recStart = substr((string) $rec['start_time'], 0, 5);
                $recEnd   = substr((string) $rec['end_time'], 0, 5);

                // Slot muss im freigegebenen Zeitfenster liegen
                if ($startTime >= $recStart && $endTime <= $recEnd) {
                    return $rec;
                }
            }

            return null;
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::findHolidayOverride Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prüft, ob der Fahrlehrer überhaupt Wochen-Templates eingerichtet hat.
     */
    public static function hasWeeklyTemplatesConfigured(int $instructorId): bool
    {
        try {
            $row = DB::fetchOne(
                'SELECT COUNT(*) AS c FROM `bs_coach_weekly_templates` WHERE `instructor_id` = :id',
                ['id' => $instructorId]
            );
            return ((int) ($row['c'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Sucht ein passendes aktives Wochentemplate, das den Slot abdeckt.
     *
     * @return array<string, mixed>|null
     */
    public static function findMatchingWeeklyTemplate(
        int $instructorId,
        int $weekday,
        string $startTime,
        string $endTime,
        ?int $locationId = null
    ): ?array {
        try {
            $templates = DB::fetchAll(
                'SELECT * FROM `bs_coach_weekly_templates`
                 WHERE `instructor_id` = :inst_id
                   AND `weekday` = :weekday
                   AND `is_active` = 1',
                [
                    'inst_id' => $instructorId,
                    'weekday' => $weekday,
                ]
            );

            foreach ($templates as $tpl) {
                $tplStart = substr((string) $tpl['start_time'], 0, 5);
                $tplEnd   = substr((string) $tpl['end_time'], 0, 5);

                // Liegt der Slot komplett innerhalb der Arbeitszeit?
                if ($startTime >= $tplStart && $endTime <= $tplEnd) {
                    // Prüfe Standortbezug: NULL = alle Standorte
                    $tplLoc = !empty($tpl['location_id']) ? (int) $tpl['location_id'] : null;
                    if ($tplLoc === null || $locationId === null || $tplLoc === $locationId) {
                        return $tpl;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::findMatchingWeeklyTemplate Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prüft, ob eine Absenz / Sperrzeit mit dem Slot kollidiert.
     *
     * @return array{record: array<string, mixed>, reason: string}|null
     */
    public static function findConflictingAbsence(
        int $instructorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $locationId = null
    ): ?array {
        try {
            $sql = 'SELECT * FROM `bs_coach_availabilities`
                    WHERE `instructor_id` = :inst_id AND `date` = :date';
            $records = DB::fetchAll($sql, [
                'inst_id' => $instructorId,
                'date'    => $date,
            ]);

            $absenceTypes = [
                'unavailable' => 'Nicht verfügbar',
                'vacation'    => 'Ferien / Urlaub',
                'sick'        => 'Krankheit / Unfall',
                'military'    => 'Militär / Zivildienst',
                'other'       => 'Abwesenheit / Sperrzeit',
            ];

            foreach ($records as $rec) {
                $type = (string) ($rec['type'] ?? 'unavailable');
                if ($type === 'available' || $type === 'holiday_override') {
                    continue;
                }

                // Standortbezug prüfen:
                // Wenn rec['location_id'] NULL ist -> gilt für ALLE Standorte.
                // Wenn rec['location_id'] gesetzt ist -> gilt nur, wenn gleicher Standort oder keine Standortfilterung.
                $recLoc = !empty($rec['location_id']) ? (int) $rec['location_id'] : null;
                if ($recLoc !== null && $locationId !== null && $recLoc !== $locationId) {
                    continue; // Absenz betrifft einen anderen Hafen
                }

                $typeLabel = $absenceTypes[$type] ?? 'Abwesend';
                $noteSuffix = !empty($rec['note']) ? ' (' . $rec['note'] . ')' : '';

                // Ganztägige Absenz
                if (empty($rec['start_time']) || empty($rec['end_time'])) {
                    return [
                        'record' => $rec,
                        'reason' => sprintf('Fahrlehrer ist ganztägig abwesend: %s%s', $typeLabel, $noteSuffix),
                    ];
                }

                // Zeitüberschneidung
                $recStart = substr((string) $rec['start_time'], 0, 5);
                $recEnd   = substr((string) $rec['end_time'], 0, 5);

                if ($startTime < $recEnd && $endTime > $recStart) {
                    return [
                        'record' => $rec,
                        'reason' => sprintf(
                            'Fahrlehrer hat eine Sperrzeit (%s - %s Uhr: %s%s)',
                            $recStart,
                            $recEnd,
                            $typeLabel,
                            $noteSuffix
                        ),
                    ];
                }
            }

            return null;
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::findConflictingAbsence Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt alle Wochen-Templates eines Fahrlehrers sortiert nach Wochentag.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getWeeklyTemplates(int $instructorId): array
    {
        self::ensureTables();
        if ($instructorId <= 0) {
            return [];
        }

        try {
            $sql = 'SELECT t.*, l.name AS location_name
                    FROM `bs_coach_weekly_templates` t
                    LEFT JOIN `bs_locations` l ON l.id = t.location_id
                    WHERE t.`instructor_id` = :id
                    ORDER BY t.`weekday` ASC, t.`start_time` ASC';
            return DB::fetchAll($sql, ['id' => $instructorId]);
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::getWeeklyTemplates Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Speichert oder aktualisiert ein Wochen-Template.
     *
     * @param array<string, mixed> $data
     */
    public static function saveWeeklyTemplate(int $instructorId, array $data): int
    {
        self::ensureTables();
        if ($instructorId <= 0) {
            return 0;
        }

        $id        = (int) ($data['id'] ?? 0);
        $weekday   = max(1, min(7, (int) ($data['weekday'] ?? $data['day_of_week'] ?? 1)));
        $startTime = substr(trim((string) ($data['start_time'] ?? '08:00')), 0, 5);
        $endTime   = substr(trim((string) ($data['end_time'] ?? '18:00')), 0, 5);
        $locId     = !empty($data['location_id']) ? (int) $data['location_id'] : null;
        $isActive  = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;
        $now       = date('Y-m-d H:i:s');

        try {
            if ($id > 0) {
                $sql = 'UPDATE `bs_coach_weekly_templates`
                        SET `weekday` = :weekday, `start_time` = :start, `end_time` = :end,
                            `location_id` = :loc, `is_active` = :active, `updated_at` = :now
                        WHERE `id` = :id AND `instructor_id` = :inst_id';
                DB::execute($sql, [
                    'weekday' => $weekday,
                    'start'   => $startTime . ':00',
                    'end'     => $endTime . ':00',
                    'loc'     => $locId,
                    'active'  => $isActive,
                    'now'     => $now,
                    'id'      => $id,
                    'inst_id' => $instructorId,
                ]);
                return $id;
            }

            $sql = 'INSERT INTO `bs_coach_weekly_templates`
                    (`instructor_id`, `weekday`, `start_time`, `end_time`, `location_id`, `is_active`, `created_at`, `updated_at`)
                    VALUES (:inst_id, :weekday, :start, :end, :loc, :active, :now, :now)';
            DB::execute($sql, [
                'inst_id' => $instructorId,
                'weekday' => $weekday,
                'start'   => $startTime . ':00',
                'end'     => $endTime . ':00',
                'loc'     => $locId,
                'active'  => $isActive,
                'now'     => $now,
            ]);
            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::saveWeeklyTemplate Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Löscht ein Wochen-Template.
     */
    public static function deleteWeeklyTemplate(int $id, int $instructorId): bool
    {
        if ($id <= 0 || $instructorId <= 0) {
            return false;
        }

        try {
            DB::execute('DELETE FROM `bs_coach_weekly_templates` WHERE `id` = :id AND `instructor_id` = :inst_id', [
                'id'      => $id,
                'inst_id' => $instructorId,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('CoachAvailabilityService::deleteWeeklyTemplate Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liefert den deutschen Namen eines Wochentags (1=Montag bis 7=Sonntag).
     */
    public static function getWeekdayLabel(int $weekday): string
    {
        return match ($weekday) {
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            7 => 'Sonntag',
            default => 'Tag ' . $weekday,
        };
    }
}

// Namespace-Alias für modulares CMS
if (!class_exists('Modules\\BoatingSchool\\CoachAvailabilityService', false)) {
    class_alias(CoachAvailabilityService::class, 'Modules\\BoatingSchool\\CoachAvailabilityService');
}
