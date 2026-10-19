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
     * Kompatibilitätsmethode für frühere Aufrufe.
     */
    public function calculatePayoutForAppointment(array $appointment, array $instructor): float
    {
        return self::calculatePayout($appointment, $instructor);
    }
}
