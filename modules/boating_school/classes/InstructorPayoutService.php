<?php

declare(strict_types=1);

/**
 * InstructorPayoutService
 *
 * Reine Berechnungsfunktion für Fahrlehrer-Termindauer und Honorare:
 * - Berechnet Termin-Dauer und Betrag (Stundenlohn vs. Pauschale pro Termin)
 * - Dient als mathematische Basis für den zentralen Timesheet-Workflow (bs_coach_timesheet_closures)
 * - Die Felder instructor_payout_amount, instructor_payout_status und instructor_payout_note in
 *   bs_appointments bleiben erhalten und werden ausschliesslich über den Timesheet-Workflow gesetzt.
 */
class InstructorPayoutService
{
    public const RATE_PER_HOUR = 'per_hour';
    public const RATE_PER_APPOINTMENT = 'per_appointment';

    public const PAYOUT_OPEN = 'open';
    public const PAYOUT_EXPORTED = 'exported';
    public const PAYOUT_PAID = 'paid';

    /**
     * Berechnet den Auszahlungsbetrag für einen einzelnen Termin basierend auf den
     * Konditionen des Fahrlehrers (Stundenlohn oder Pauschale pro Termin).
     *
     * @param array<string, mixed> $appointment
     * @param array<string, mixed> $instructor
     * @return float
     */
    public static function calculatePayout(array $appointment, array $instructor): float
    {
        $calc = self::calculateAppointmentPayout($appointment, $instructor);
        return $calc['amount'];
    }

    /**
     * Ermittelt die genaue Aufschlüsselung der Honorarberechnung eines Termins.
     *
     * @param array<string, mixed> $appointment
     * @param array<string, mixed> $instructor
     * @return array{hours: float, rate: float, rate_type: string, amount: float, note: string}
     */
    public static function calculateAppointmentPayout(array $appointment, array $instructor): array
    {
        // 1. Wenn kein Fahrlehrer zugewiesen ist, kein Honorar
        if (empty($instructor['id'])) {
            return [
                'hours'     => 0.0,
                'rate'      => 0.0,
                'rate_type' => self::RATE_PER_HOUR,
                'amount'    => 0.00,
                'note'      => 'Kein Fahrlehrer zugewiesen',
            ];
        }

        // 2. Stornierte Termine generieren standardmäßig kein Fahrlehrer-Honorar
        $status = (string) ($appointment['status'] ?? '');
        if (in_array($status, ['cancelled_customer', 'cancelled_system', 'cancelled'], true)) {
            return [
                'hours'     => 0.0,
                'rate'      => 0.0,
                'rate_type' => (string) ($instructor['pay_rate_type'] ?? self::RATE_PER_HOUR),
                'amount'    => 0.00,
                'note'      => 'Stornierter Termin (kein Honoraranspruch)',
            ];
        }

        $rateType   = (string) ($instructor['pay_rate_type'] ?? self::RATE_PER_HOUR);
        $rateAmount = (float) ($instructor['pay_rate_amount'] ?? 0.00);

        // Fall A: Pauschalbetrag pro Termin
        if ($rateType === self::RATE_PER_APPOINTMENT) {
            return [
                'hours'     => self::calculateAppointmentHours($appointment),
                'rate'      => $rateAmount,
                'rate_type' => self::RATE_PER_APPOINTMENT,
                'amount'    => round($rateAmount, 2),
                'note'      => sprintf('Pauschale pro Termin: CHF %.2f', $rateAmount),
            ];
        }

        // Fall B: Stundenlohn
        $hours = self::calculateAppointmentHours($appointment);
        $amount = round($hours * $rateAmount, 2);

        return [
            'hours'     => $hours,
            'rate'      => $rateAmount,
            'rate_type' => self::RATE_PER_HOUR,
            'amount'    => $amount,
            'note'      => sprintf('%.2f Std. à CHF %.2f/h = CHF %.2f', $hours, $rateAmount, $amount),
        ];
    }

    /**
     * Ermittelt die effektive Dauer eines Termins in Stunden (dezimal).
     *
     * @param array<string, mixed> $appointment
     * @return float
     */
    public static function calculateAppointmentHours(array $appointment): float
    {
        $minutes = self::resolveDurationMinutes($appointment);
        return round($minutes / 60.0, 2);
    }

    /**
     * Ermittelt die effektive Dauer eines Termins in Minuten.
     *
     * @param array<string, mixed> $appointment
     * @return int
     */
    public static function resolveDurationMinutes(array $appointment): int
    {
        if (isset($appointment['duration_minutes']) && (int) $appointment['duration_minutes'] > 0) {
            return (int) $appointment['duration_minutes'];
        }

        if (!empty($appointment['start_time']) && !empty($appointment['end_time'])) {
            $date = !empty($appointment['date']) ? (string) $appointment['date'] : '2000-01-01';
            $startTime = substr((string) $appointment['start_time'], 0, 5);
            $endTime   = substr((string) $appointment['end_time'], 0, 5);

            $startTs = strtotime("{$date} {$startTime}:00");
            $endTs   = strtotime("{$date} {$endTime}:00");

            if ($endTs !== false && $startTs !== false && $endTs > $startTs) {
                return (int) round(($endTs - $startTs) / 60);
            }
        }

        if (isset($appointment['service_duration']) && (int) $appointment['service_duration'] > 0) {
            return (int) $appointment['service_duration'];
        }

        return 60;
    }

    /**
     * Aktualisiert den Payout-Betrag und Abrechnungsstatus eines Termins.
     * Wird ausschliesslich durch den zentralen Timesheet-Workflow aufgerufen.
     *
     * @param int $appointmentId
     * @param float $amount
     * @param string|null $status 'open' | 'exported' | 'paid'
     * @param string|null $note
     * @return bool
     */
    public static function updateAppointmentPayout(
        int $appointmentId,
        float $amount,
        ?string $status = null,
        ?string $note = null
    ): bool {
        if ($appointmentId <= 0 || !class_exists('DB')) {
            return false;
        }

        $sets = ['`instructor_payout_amount` = :amount'];
        $params = [
            'amount' => round($amount, 2),
            'id'     => $appointmentId,
        ];

        if ($status !== null && in_array($status, [self::PAYOUT_OPEN, self::PAYOUT_EXPORTED, self::PAYOUT_PAID], true)) {
            $sets[] = '`instructor_payout_status` = :status';
            $params['status'] = $status;
        }

        if ($note !== null) {
            $sets[] = '`instructor_payout_note` = :note';
            $params['note'] = trim($note);
        }

        $sets[] = '`updated_at` = NOW()';
        $sql = 'UPDATE `bs_appointments` SET ' . implode(', ', $sets) . ' WHERE `id` = :id';

        return DB::execute($sql, $params);
    }

    /**
     * Schliesst alle offenen Termine eines Fahrlehrers in einem Monat ab
     * und setzt instructor_payout_status auf 'exported'.
     *
     * @param int $instructorId
     * @param string $yearMonth Format 'YYYY-MM'
     * @return array{count: int, total_amount: float}
     */
    public static function closeMonthForInstructor(int $instructorId, string $yearMonth): array
    {
        if ($instructorId <= 0 || empty($yearMonth) || !class_exists('DB')) {
            return ['count' => 0, 'total_amount' => 0.0];
        }

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));

        try {
            // Hole alle Termine des Monats mit Status 'open'
            $apps = DB::fetchAll(
                "SELECT `id`, `instructor_payout_amount`
                 FROM `bs_appointments`
                 WHERE `instructor_id` = :inst_id
                   AND `date` >= :from AND `date` <= :to
                   AND (`instructor_payout_status` = 'open' OR `instructor_payout_status` IS NULL)",
                [
                    'inst_id' => $instructorId,
                    'from'    => $fromDate,
                    'to'      => $toDate,
                ]
            );

            $count = count($apps);
            $totalAmount = 0.0;
            foreach ($apps as $a) {
                $totalAmount += (float) ($a['instructor_payout_amount'] ?? 0.0);
            }

            if ($count > 0) {
                DB::execute(
                    "UPDATE `bs_appointments`
                     SET `instructor_payout_status` = 'exported',
                         `instructor_payout_note` = CONCAT(COALESCE(`instructor_payout_note`, ''), ' [Übergeben zur Lohnabrechnung]'),
                         `updated_at` = NOW()
                     WHERE `instructor_id` = :inst_id
                       AND `date` >= :from AND `date` <= :to
                       AND (`instructor_payout_status` = 'open' OR `instructor_payout_status` IS NULL)",
                    [
                        'inst_id' => $instructorId,
                        'from'    => $fromDate,
                        'to'      => $toDate,
                    ]
                );
            }

            return [
                'count'        => $count,
                'total_amount' => round($totalAmount, 2),
            ];
        } catch (\Throwable $e) {
            error_log('InstructorPayoutService::closeMonthForInstructor Fehler: ' . $e->getMessage());
            return ['count' => 0, 'total_amount' => 0.0];
        }
    }

    /**
     * Ermittelt die zusammenfassenden Kennzahlen der gesamten Bootsfahrschule für einen Zeitraum.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array<string, mixed>
     */
    public static function getSchoolReportingSummary(string $dateFrom, string $dateTo): array
    {
        $defaultSummary = [
            'total_revenue'      => 0.00,
            'total_appointments' => 0,
            'completed'          => 0,
            'total_hours'        => 0.0,
            'total_payout'       => 0.00,
            'payout_open'        => 0.00,
            'payout_exported'    => 0.00,
            'payout_paid'        => 0.00,
            'gross_margin'       => 0.00,
            'margin_percent'     => 0.0,
            'services'           => [],
            'boats'              => [],
        ];

        if (!class_exists('BoatingSchoolRepository')) {
            return $defaultSummary;
        }

        try {
            BoatingSchoolRepository::ensureCrmSchema();
            $appointments = BoatingSchoolRepository::getAppointments([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ], 10000, 0);

            $totalRevenue = 0.0;
            $completed = 0;
            $totalHours = 0.0;
            $totalPayout = 0.0;
            $payoutOpen = 0.0;
            $payoutExported = 0.0;
            $payoutPaid = 0.0;

            $servicesMap = [];
            $boatsMap = [];

            foreach ($appointments as $app) {
                $status = (string) ($app['status'] ?? '');
                $isCancelled = in_array($status, ['cancelled_customer', 'cancelled_system', 'cancelled'], true);

                if ($status === 'completed') {
                    $completed++;
                }

                $revenue = (float) ($app['price'] ?? 0.0);
                if (!$isCancelled) {
                    $totalRevenue += $revenue;
                }

                $hours = self::calculateAppointmentHours($app);
                $totalHours += $hours;

                $payout = (float) ($app['instructor_payout_amount'] ?? 0.0);
                $totalPayout += $payout;

                $pStatus = (string) ($app['instructor_payout_status'] ?? self::PAYOUT_OPEN);
                if ($pStatus === self::PAYOUT_EXPORTED) {
                    $payoutExported += $payout;
                } elseif ($pStatus === self::PAYOUT_PAID) {
                    $payoutPaid += $payout;
                } else {
                    $payoutOpen += $payout;
                }

                // Service Auswertung
                $srvName = !empty($app['service_name']) ? (string) $app['service_name'] : 'Sonstige';
                if (!isset($servicesMap[$srvName])) {
                    $servicesMap[$srvName] = [
                        'name'    => $srvName,
                        'count'   => 0,
                        'hours'   => 0.0,
                        'revenue' => 0.0,
                    ];
                }
                $servicesMap[$srvName]['count']++;
                $servicesMap[$srvName]['hours'] += $hours;
                if (!$isCancelled) {
                    $servicesMap[$srvName]['revenue'] += $revenue;
                }

                // Boot Auswertung
                if (!empty($app['boat_name'])) {
                    $boatName = (string) $app['boat_name'];
                    if (!isset($boatsMap[$boatName])) {
                        $boatsMap[$boatName] = [
                            'name'  => $boatName,
                            'count' => 0,
                            'hours' => 0.0,
                        ];
                    }
                    $boatsMap[$boatName]['count']++;
                    $boatsMap[$boatName]['hours'] += $hours;
                }
            }

            usort($servicesMap, static fn(array $a, array $b): int => $b['revenue'] <=> $a['revenue']);
            usort($boatsMap, static fn(array $a, array $b): int => $b['hours'] <=> $a['hours']);

            $grossMargin = round($totalRevenue - $totalPayout, 2);
            $marginPercent = $totalRevenue > 0.0 ? round(($grossMargin / $totalRevenue) * 100.0, 1) : 0.0;

            return [
                'total_revenue'      => round($totalRevenue, 2),
                'total_appointments' => count($appointments),
                'completed'          => $completed,
                'total_hours'        => round($totalHours, 2),
                'total_payout'       => round($totalPayout, 2),
                'payout_open'        => round($payoutOpen, 2),
                'payout_exported'    => round($payoutExported, 2),
                'payout_paid'        => round($payoutPaid, 2),
                'gross_margin'       => $grossMargin,
                'margin_percent'     => $marginPercent,
                'services'           => array_values($servicesMap),
                'boats'              => array_values($boatsMap),
            ];
        } catch (\Throwable $e) {
            error_log('InstructorPayoutService::getSchoolReportingSummary Fehler: ' . $e->getMessage());
            return $defaultSummary;
        }
    }

    /**
     * Ermittelt die Abrechnungskennzahlen pro Fahrlehrer für einen Zeitraum.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $instructorId
     * @return array<int, array<string, mixed>>
     */
    public static function getInstructorPayrollSummary(
        string $dateFrom,
        string $dateTo,
        ?int $instructorId = null
    ): array {
        if (!class_exists('BoatingSchoolRepository')) {
            return [];
        }

        try {
            BoatingSchoolRepository::ensureCrmSchema();

            $instructors = BoatingSchoolRepository::getInstructors(null, 500, 0);
            if ($instructorId !== null && $instructorId > 0) {
                $instructors = array_values(array_filter(
                    $instructors,
                    static fn(array $i): bool => (int) ($i['id'] ?? 0) === $instructorId
                ));
            }

            $appFilters = [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ];
            if ($instructorId !== null && $instructorId > 0) {
                $appFilters['instructor_id'] = $instructorId;
            }

            $appointments = BoatingSchoolRepository::getAppointments($appFilters, 10000, 0);

            $appsByInstructor = [];
            foreach ($appointments as $app) {
                $iId = (int) ($app['instructor_id'] ?? 0);
                if ($iId > 0) {
                    $appsByInstructor[$iId][] = $app;
                }
            }

            $summaries = [];
            foreach ($instructors as $inst) {
                $iId = (int) ($inst['id'] ?? 0);
                $instApps = $appsByInstructor[$iId] ?? [];

                $totalTrips = count($instApps);
                $completedTrips = 0;
                $totalHours = 0.0;
                $totalRevenue = 0.0;
                $payoutOpen = 0.0;
                $payoutExported = 0.0;
                $payoutPaid = 0.0;
                $totalPayout = 0.0;

                foreach ($instApps as $app) {
                    $st = (string) ($app['status'] ?? '');
                    $isCancelled = in_array($st, ['cancelled_customer', 'cancelled_system', 'cancelled'], true);
                    if ($st === 'completed') {
                        $completedTrips++;
                    }

                    $hrs = self::calculateAppointmentHours($app);
                    $totalHours += $hrs;

                    $rev = (float) ($app['price'] ?? 0.0);
                    if (!$isCancelled) {
                        $totalRevenue += $rev;
                    }

                    $p = (float) ($app['instructor_payout_amount'] ?? 0.0);
                    $totalPayout += $p;

                    $pStatus = (string) ($app['instructor_payout_status'] ?? self::PAYOUT_OPEN);
                    if ($pStatus === self::PAYOUT_EXPORTED) {
                        $payoutExported += $p;
                    } elseif ($pStatus === self::PAYOUT_PAID) {
                        $payoutPaid += $p;
                    } else {
                        $payoutOpen += $p;
                    }
                }

                $grossMargin = round($totalRevenue - $totalPayout, 2);
                $marginPercent = $totalRevenue > 0.0 ? round(($grossMargin / $totalRevenue) * 100.0, 1) : 0.0;

                $summaries[] = [
                    'instructor_id'          => $iId,
                    'instructor_name'        => trim(($inst['first_name'] ?? '') . ' ' . ($inst['last_name'] ?? '')),
                    'payroll_external_code'  => !empty($inst['payroll_external_code']) ? (string) $inst['payroll_external_code'] : null,
                    'color_code'             => !empty($inst['color_code']) ? (string) $inst['color_code'] : '#0d6efd',
                    'is_active'              => !empty($inst['is_active']) ? 1 : 0,
                    'pay_rate_type'          => (string) ($inst['pay_rate_type'] ?? self::RATE_PER_HOUR),
                    'pay_rate_amount'        => (float) ($inst['pay_rate_amount'] ?? 0.00),
                    'total_trips'            => $totalTrips,
                    'completed_trips'        => $completedTrips,
                    'total_hours'            => round($totalHours, 2),
                    'total_revenue'          => round($totalRevenue, 2),
                    'payout_open'            => round($payoutOpen, 2),
                    'payout_exported'        => round($payoutExported, 2),
                    'payout_paid'            => round($payoutPaid, 2),
                    'total_payout'           => round($totalPayout, 2),
                    'gross_margin'           => $grossMargin,
                    'margin_percent'         => $marginPercent,
                ];
            }

            usort(
                $summaries,
                static fn(array $a, array $b): int => strcmp((string) ($a['instructor_name'] ?? ''), (string) ($b['instructor_name'] ?? ''))
            );

            return $summaries;
        } catch (\Throwable $e) {
            error_log('InstructorPayoutService::getInstructorPayrollSummary Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Exportiert Einzeltermine als CSV.
     * Optional können offene Termine direkt auf 'exported' gesetzt werden.
     *
     * @param array<int, array<string, mixed>> $appointments
     * @param bool $markExported
     * @return string
     */
    public static function exportPayrollCsv(array $appointments, bool $markExported = false): string
    {
        if ($markExported) {
            $openIds = [];
            foreach ($appointments as $app) {
                $st = (string) ($app['instructor_payout_status'] ?? self::PAYOUT_OPEN);
                if ($st === self::PAYOUT_OPEN || empty($st)) {
                    $openIds[] = (int) $app['id'];
                }
            }
            if (!empty($openIds)) {
                self::bulkUpdatePayoutStatus($openIds, self::PAYOUT_EXPORTED);
            }
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        // UTF-8 BOM für Excel
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Termin-ID',
            'Datum',
            'Startzeit',
            'Endzeit',
            'Dauer (Std.)',
            'Terminstatus',
            'Fahrlehrer',
            'Lohn-Code',
            'Lohnmodell',
            'Lohnsatz (CHF)',
            'Kunde',
            'E-Mail Kunde',
            'Telefon Kunde',
            'Leistung',
            'Boot',
            'Standort',
            'Kundenpreis (CHF)',
            'Honorar (CHF)',
            'Abrechnungsstatus',
            'Abrechnungsnotiz',
        ], ';');

        foreach ($appointments as $app) {
            $hours = self::calculateAppointmentHours($app);
            $pStatus = (string) ($app['instructor_payout_status'] ?? self::PAYOUT_OPEN);
            if ($markExported && ($pStatus === self::PAYOUT_OPEN || empty($pStatus))) {
                $pStatus = self::PAYOUT_EXPORTED;
            }

            $instName = trim(($app['instructor_first_name'] ?? '') . ' ' . ($app['instructor_last_name'] ?? ''));
            $custName = trim(($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? ''));
            $rateType = (($app['pay_rate_type'] ?? '') === self::RATE_PER_APPOINTMENT) ? 'Pauschale/Termin' : 'pro Stunde';

            fputcsv($stream, [
                (string) ($app['id'] ?? ''),
                (string) ($app['date'] ?? ''),
                (string) ($app['start_time'] ?? ''),
                (string) ($app['end_time'] ?? ''),
                number_format($hours, 2, '.', ''),
                (string) ($app['status'] ?? ''),
                $instName,
                (string) ($app['payroll_external_code'] ?? ''),
                $rateType,
                number_format((float) ($app['pay_rate_amount'] ?? 0.0), 2, '.', ''),
                $custName,
                (string) ($app['customer_email'] ?? ''),
                (string) ($app['customer_phone'] ?? ''),
                (string) ($app['service_name'] ?? ''),
                (string) ($app['boat_name'] ?? ''),
                (string) ($app['location_name'] ?? ''),
                number_format((float) ($app['price'] ?? 0.0), 2, '.', ''),
                number_format((float) ($app['instructor_payout_amount'] ?? 0.0), 2, '.', ''),
                $pStatus,
                (string) ($app['instructor_payout_note'] ?? ''),
            ], ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv !== false ? $csv : '';
    }

    /**
     * Exportiert die aggregierte Fahrlehrer-Payroll als CSV.
     *
     * @param array<int, array<string, mixed>> $summaries
     * @return string
     */
    public static function exportSummaryCsv(array $summaries): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        // UTF-8 BOM für Excel
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Fahrlehrer-ID',
            'Fahrlehrer',
            'Lohn-Code',
            'Status Aktiv',
            'Lohnmodell',
            'Lohnsatz (CHF)',
            'Fahrten Total',
            'Fahrten Abgeschlossen',
            'Stunden Total',
            'Umsatz Kunde (CHF)',
            'Honorar Offen (CHF)',
            'Honorar Exportiert (CHF)',
            'Honorar Bezahlt (CHF)',
            'Honorar Total (CHF)',
            'Deckungsbeitrag Schule (CHF)',
            'Marge (%)',
        ], ';');

        foreach ($summaries as $row) {
            $rateType = (($row['pay_rate_type'] ?? '') === self::RATE_PER_APPOINTMENT) ? 'Pauschale/Termin' : 'pro Stunde';

            fputcsv($stream, [
                (string) ($row['instructor_id'] ?? ''),
                (string) ($row['instructor_name'] ?? ''),
                (string) ($row['payroll_external_code'] ?? ''),
                !empty($row['is_active']) ? 'Ja' : 'Nein',
                $rateType,
                number_format((float) ($row['pay_rate_amount'] ?? 0.0), 2, '.', ''),
                (string) ($row['total_trips'] ?? 0),
                (string) ($row['completed_trips'] ?? 0),
                number_format((float) ($row['total_hours'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['total_revenue'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['payout_open'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['payout_exported'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['payout_paid'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['total_payout'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['gross_margin'] ?? 0.0), 2, '.', ''),
                number_format((float) ($row['margin_percent'] ?? 0.0), 1, '.', '') . '%',
            ], ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv !== false ? $csv : '';
    }

    /**
     * Batch-Statusänderung für ausgewählte Termine.
     *
     * @param array<int> $appointmentIds
     * @param string $newStatus 'open' | 'exported' | 'paid'
     * @return int Anzahl aktualisierter Termine
     */
    public static function bulkUpdatePayoutStatus(array $appointmentIds, string $newStatus): int
    {
        if (empty($appointmentIds) || !class_exists('DB')) {
            return 0;
        }

        if (!in_array($newStatus, [self::PAYOUT_OPEN, self::PAYOUT_EXPORTED, self::PAYOUT_PAID], true)) {
            return 0;
        }

        $validIds = array_values(array_filter(
            array_map('intval', $appointmentIds),
            static fn(int $id): bool => $id > 0
        ));

        if (empty($validIds)) {
            return 0;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($validIds), '?'));
            $sql = "UPDATE `bs_appointments`
                    SET `instructor_payout_status` = ?,
                        `updated_at` = NOW()
                    WHERE `id` IN ({$placeholders})";
            $params = array_merge([$newStatus], $validIds);

            return DB::execute($sql, $params);
        } catch (\Throwable $e) {
            error_log('InstructorPayoutService::bulkUpdatePayoutStatus Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Berechnet Honorare für Termine eines Zeitraums neu.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $instructorId
     * @param bool $onlyOpen
     * @return int Anzahl neu berechneter Termine
     */
    public static function bulkRecalculate(
        string $dateFrom,
        string $dateTo,
        ?int $instructorId = null,
        bool $onlyOpen = true
    ): int {
        if (!class_exists('DB') || !class_exists('BoatingSchoolRepository')) {
            return 0;
        }

        try {
            $filters = [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ];
            if ($instructorId !== null && $instructorId > 0) {
                $filters['instructor_id'] = $instructorId;
            }
            if ($onlyOpen) {
                $filters['instructor_payout_status'] = self::PAYOUT_OPEN;
            }

            $appointments = BoatingSchoolRepository::getAppointments($filters, 10000, 0);
            if (empty($appointments)) {
                return 0;
            }

            $instructorCache = [];
            $updatedCount = 0;

            foreach ($appointments as $app) {
                $instId = (int) ($app['instructor_id'] ?? 0);
                if ($instId <= 0) {
                    continue;
                }

                if (!isset($instructorCache[$instId])) {
                    $instructorCache[$instId] = BoatingSchoolRepository::getInstructorById($instId);
                }
                $instructor = $instructorCache[$instId];
                if (!$instructor) {
                    continue;
                }

                $payout = self::calculateAppointmentPayout($app, $instructor);
                $appId = (int) $app['id'];

                $status = $onlyOpen ? self::PAYOUT_OPEN : null;
                $ok = self::updateAppointmentPayout($appId, $payout['amount'], $status, $payout['note']);
                if ($ok) {
                    $updatedCount++;
                }
            }

            return $updatedCount;
        } catch (\Throwable $e) {
            error_log('InstructorPayoutService::bulkRecalculate Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Kompatibilitätsmethode für frühere Aufrufe.
     */
    public function calculatePayoutForAppointment(array $appointment, array $instructor): float
    {
        return self::calculatePayout($appointment, $instructor);
    }
}
