<?php

declare(strict_types=1);

/**
 * AppointmentService
 *
 * Verwaltet Termine, Buchungen, Konfliktprüfung und freie Zeitfenster
 * der Bootsfahrschule (bs_appointments).
 */
class AppointmentService
{
    public const STATUS_REQUESTED          = 'requested';
    public const STATUS_CONFIRMED          = 'confirmed';
    public const STATUS_CANCELLED_CUSTOMER = 'cancelled_customer';
    public const STATUS_CANCELLED_SYSTEM   = 'cancelled_system';
    public const STATUS_COMPLETED          = 'completed';

    public const STATUS_LABELS = [
        self::STATUS_REQUESTED          => 'Angefragt (Wartet auf Bestätigung)',
        self::STATUS_CONFIRMED          => 'Bestätigt',
        self::STATUS_CANCELLED_CUSTOMER => 'Storniert durch Kunde',
        self::STATUS_CANCELLED_SYSTEM   => 'Abgesagt / Storniert (Schule)',
        self::STATUS_COMPLETED          => 'Erfolgreich abgeschlossen',
    ];

    public const STATUS_BADGES = [
        self::STATUS_REQUESTED          => 'warning',
        self::STATUS_CONFIRMED          => 'success',
        self::STATUS_CANCELLED_CUSTOMER => 'secondary',
        self::STATUS_CANCELLED_SYSTEM   => 'danger',
        self::STATUS_COMPLETED          => 'info',
    ];

    /**
     * Prüft auf Terminkonflikte bezüglich Boot, Fahrlehrer und Kunde.
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
        return BoatingSchoolRepository::checkConflicts(
            $date,
            $startTime,
            $endTime,
            $boatId,
            $instructorId,
            $customerId,
            $excludeAppointmentId,
            $locationId
        );
    }

    /**
     * Berechnet freie buchbare Slots für einen bestimmten Tag.
     *
     * @param string $date Datum im Format YYYY-MM-DD
     * @param int|null $locationId Optionaler Standort
     * @param int|null $serviceId Optionale Leistung zur Dauer- und Bootstyp-Ermittlung
     * @return array<int, array<string, mixed>> Liste freier Slots
     */
    public static function getFreeSlotsForDay(string $date, ?int $locationId = null, ?int $serviceId = null): array
    {
        // Validiere Datum
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$dateTime) {
            return [];
        }

        // Buchung in der Vergangenheit verhindern
        $today = new \DateTimeImmutable('today');
        if ($dateTime < $today) {
            return [];
        }

        // Ermittle Service-Details
        $durationMinutes = 90; // Standard 90 Min
        $boatTypeFilter = 'any';
        if ($serviceId !== null && $serviceId > 0) {
            $service = ServiceService::find($serviceId);
            if ($service !== null) {
                if (!empty($service['duration_minutes'])) {
                    $durationMinutes = (int) $service['duration_minutes'];
                }
                if (!empty($service['boat_type'])) {
                    $boatTypeFilter = (string) $service['boat_type'];
                }
            }
        }

        // Aktive Fahrlehrer und Boote laden
        $allInstructors = InstructorService::getAll(['is_active' => 1]);
        $boatFilters = ['is_active' => 1];
        if ($boatTypeFilter !== 'any') {
            $boatFilters['type'] = $boatTypeFilter;
        }
        $allBoats = BoatService::getAll($boatFilters);

        // Standard-Raster für Fahrstunden & Mieten (z.B. Beginn alle 105 Min für 90 Min Stunden + 15 Min Puffer)
        $startTimes = [];
        if ($durationMinutes <= 60) {
            $startTimes = ['08:30', '10:00', '11:30', '13:30', '15:00', '16:30', '18:00'];
        } elseif ($durationMinutes <= 90) {
            $startTimes = ['08:30', '10:15', '12:00', '14:00', '15:45', '17:30'];
        } else {
            // z.B. 120 Minuten oder halber Tag
            $startTimes = ['08:30', '11:00', '14:00', '16:45'];
        }

        // Bestehende Termine für den Tag laden
        $existingAppointments = BoatingSchoolRepository::getAppointments(['date' => $date]);
        $activeExisting = array_filter($existingAppointments, static function ($app) {
            return !in_array($app['status'], [self::STATUS_CANCELLED_CUSTOMER, self::STATUS_CANCELLED_SYSTEM], true);
        });

        $now = new \DateTimeImmutable('now');
        $isToday = ($dateTime->format('Y-m-d') === $today->format('Y-m-d'));

        $slots = [];

        foreach ($startTimes as $start) {
            // Slot Endzeit berechnen
            $slotStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$start}");
            if (!$slotStart) {
                continue;
            }

            // Für heutigen Tag: mind. 2 Stunden Vorlauf
            if ($isToday && $slotStart < $now->modify('+2 hours')) {
                continue;
            }

            $slotEnd = $slotStart->modify("+{$durationMinutes} minutes");
            $end = $slotEnd->format('H:i');

            // Prüfen, welche Fahrlehrer in diesem Zeitraum frei sind
            $availableInstructors = [];
            foreach ($allInstructors as $inst) {
                $instId = (int) $inst['id'];
                $busy = false;
                foreach ($activeExisting as $app) {
                    if (!empty($app['instructor_id']) && (int) $app['instructor_id'] === $instId) {
                        $appStart = substr((string) $app['start_time'], 0, 5);
                        $appEnd   = substr((string) $app['end_time'], 0, 5);
                        if ($start < $appEnd && $end > $appStart) {
                            $busy = true;
                            break;
                        }
                    }
                }
                if (!$busy && class_exists('CoachAvailabilityService')) {
                    $availCheck = CoachAvailabilityService::isInstructorAvailableAt($instId, $slotStart, $slotEnd, $locationId);
                    if (!$availCheck['available']) {
                        $busy = true;
                    }
                }

                if (!$busy) {
                    $availableInstructors[] = $inst;
                }
            }

            // Prüfen, welche Boote in diesem Zeitraum frei sind
            $availableBoats = [];
            foreach ($allBoats as $boat) {
                $bId = (int) $boat['id'];
                $busy = false;
                foreach ($activeExisting as $app) {
                    if (!empty($app['boat_id']) && (int) $app['boat_id'] === $bId) {
                        $appStart = substr((string) $app['start_time'], 0, 5);
                        $appEnd   = substr((string) $app['end_time'], 0, 5);
                        if ($start < $appEnd && $end > $appStart) {
                            $busy = true;
                            break;
                        }
                    }
                }
                if (!$busy) {
                    $availableBoats[] = $boat;
                }
            }

            // Ein Slot ist verfügbar, wenn Ressourcen vorhanden sind (oder initial frei, falls keine feste Zuteilung vorliegt)
            $isAvailable = true;
            if (!empty($allInstructors) && empty($availableInstructors)) {
                $isAvailable = false;
            }
            if (!empty($allBoats) && empty($availableBoats)) {
                $isAvailable = false;
            }

            if ($isAvailable) {
                $slots[] = [
                    'date'                    => $date,
                    'start_time'              => $start,
                    'end_time'                => $end,
                    'duration_minutes'        => $durationMinutes,
                    'available'               => true,
                    'suggested_instructor_id' => !empty($availableInstructors[0]['id']) ? (int) $availableInstructors[0]['id'] : null,
                    'suggested_instructor_name' => !empty($availableInstructors[0]) ? ($availableInstructors[0]['first_name'] . ' ' . $availableInstructors[0]['last_name']) : null,
                    'suggested_boat_id'       => !empty($availableBoats[0]['id']) ? (int) $availableBoats[0]['id'] : null,
                    'suggested_boat_name'     => !empty($availableBoats[0]['name']) ? (string) $availableBoats[0]['name'] : null,
                ];
            }
        }

        return $slots;
    }

    /**
     * Erstellt einen neuen Termin.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return BoatingSchoolRepository::createAppointment($data);
    }

    /**
     * Alias für create (Anforderung Spezifikation).
     *
     * @param array<string, mixed> $data
     */
    public static function createAppointment(array $data): int
    {
        return self::create($data);
    }

    /**
     * Aktualisiert einen Termin.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateAppointment($id, $data);
    }

    /**
     * Alias für update (Anforderung Spezifikation).
     *
     * @param array<string, mixed> $data
     */
    public static function updateAppointment(int $id, array $data): bool
    {
        return self::update($id, $data);
    }

    /**
     * Löscht einen Termin.
     */
    public static function deleteAppointment(int $id): bool
    {
        return BoatingSchoolRepository::deleteAppointment($id);
    }

    /**
     * Ändert den Status eines Termins.
     */
    public static function updateStatus(int $id, string $status): bool
    {
        return BoatingSchoolRepository::updateAppointmentStatus($id, $status);
    }

    /**
     * Alias für updateStatus (Anforderung Spezifikation).
     */
    public static function changeStatus(int $id, string $status): bool
    {
        return self::updateStatus($id, $status);
    }

    /**
     * Verschiebt einen bestehenden Termin per Drag&Drop inkl. Konfliktprüfung.
     *
     * @return array<string, mixed> ['success' => bool, 'error_code' => string|null, 'message' => string, 'appointment_id' => int|null, 'mode' => 'moved', 'data' => array|null]
     */
    public static function moveAppointment(
        int $id,
        string $newDate,
        string $newStartTime,
        ?string $newEndTime = null,
        ?int $newInstructorId = null,
        ?int $newBoatId = null,
        ?int $newLocationId = null
    ): array {
        $original = self::find($id);
        if ($original === null) {
            return [
                'success'    => false,
                'error_code' => 'NOT_FOUND',
                'message'    => 'Der zu verschiebende Termin (ID ' . $id . ') wurde nicht gefunden.',
            ];
        }

        // Zeiten normieren
        $startTime = substr(trim($newStartTime), 0, 5);
        $endTime = !empty($newEndTime) ? substr(trim($newEndTime), 0, 5) : self::calculateEndTime($original, $newDate, $startTime);

        // Ressourcen bestimmen (neue übersteuern bestehende, sonst alte beibehalten)
        $boatId       = $newBoatId !== null ? $newBoatId : (!empty($original['boat_id']) ? (int) $original['boat_id'] : null);
        $instructorId = $newInstructorId !== null ? $newInstructorId : (!empty($original['instructor_id']) ? (int) $original['instructor_id'] : null);
        $locationId   = $newLocationId !== null ? $newLocationId : (!empty($original['location_id']) ? (int) $original['location_id'] : null);
        $customerId   = !empty($original['customer_id']) ? (int) $original['customer_id'] : null;

        // Konfliktprüfung (aktuelle appointment_id ausschließen, da Verschiebung)
        $conflict = self::checkConflicts(
            $newDate,
            $startTime . ':00',
            $endTime . ':00',
            $boatId,
            $instructorId,
            $customerId,
            $id
        );

        if ($conflict['has_conflict']) {
            return [
                'success'    => false,
                'error_code' => 'CONFLICT',
                'message'    => $conflict['reason'] ?? 'Verschieben nicht möglich: Boot, Fahrlehrer oder Kunde zu diesem Zeitpunkt bereits belegt.',
            ];
        }

        $updateData = [
            'date'          => $newDate,
            'start_time'    => $startTime . ':00',
            'end_time'      => $endTime . ':00',
            'service_id'    => (int) $original['service_id'],
            'customer_id'   => (int) $original['customer_id'],
            'instructor_id' => $instructorId,
            'boat_id'       => $boatId,
            'location_id'   => $locationId,
            'status'        => (string) $original['status'],
            'price'         => (float) ($original['price'] ?? 0.00),
            'notes'         => $original['notes'] ?? null,
        ];

        $ok = self::updateAppointment($id, $updateData);
        if (!$ok) {
            return [
                'success'    => false,
                'error_code' => 'UPDATE_FAILED',
                'message'    => 'Fehler beim Speichern der Terminänderung in der Datenbank.',
            ];
        }

        $updated = self::find($id);
        $dateFormatted = date('d.m.Y', strtotime($newDate));

        return [
            'success'        => true,
            'error_code'     => null,
            'mode'           => 'moved',
            'appointment_id' => $id,
            'message'        => "Termin erfolgreich verschoben auf {$dateFormatted}, {$startTime} Uhr.",
            'data'           => $updated,
        ];
    }

    /**
     * Dupliziert einen Termin per Drag&Drop im Kopiermodus inkl. Konfliktprüfung.
     *
     * @return array<string, mixed> ['success' => bool, 'error_code' => string|null, 'message' => string, 'appointment_id' => int|null, 'mode' => 'copied', 'data' => array|null]
     */
    public static function duplicateAppointment(
        int $id,
        string $newDate,
        string $newStartTime,
        ?string $newEndTime = null,
        ?int $newInstructorId = null,
        ?int $newBoatId = null,
        ?int $newLocationId = null
    ): array {
        $original = self::find($id);
        if ($original === null) {
            return [
                'success'    => false,
                'error_code' => 'NOT_FOUND',
                'message'    => 'Der zu kopierende Termin (ID ' . $id . ') wurde nicht gefunden.',
            ];
        }

        // Zeiten normieren
        $startTime = substr(trim($newStartTime), 0, 5);
        $endTime = !empty($newEndTime) ? substr(trim($newEndTime), 0, 5) : self::calculateEndTime($original, $newDate, $startTime);

        // Ressourcen bestimmen
        $boatId       = $newBoatId !== null ? $newBoatId : (!empty($original['boat_id']) ? (int) $original['boat_id'] : null);
        $instructorId = $newInstructorId !== null ? $newInstructorId : (!empty($original['instructor_id']) ? (int) $original['instructor_id'] : null);
        $locationId   = $newLocationId !== null ? $newLocationId : (!empty($original['location_id']) ? (int) $original['location_id'] : null);
        $customerId   = !empty($original['customer_id']) ? (int) $original['customer_id'] : null;

        // Konfliktprüfung (OHNE Ausschluss von $id, da das Original weiterbesteht!)
        $conflict = self::checkConflicts(
            $newDate,
            $startTime . ':00',
            $endTime . ':00',
            $boatId,
            $instructorId,
            $customerId,
            null
        );

        if ($conflict['has_conflict']) {
            return [
                'success'    => false,
                'error_code' => 'CONFLICT',
                'message'    => $conflict['reason'] ?? 'Duplizieren nicht möglich: Boot, Fahrlehrer oder Kunde am Zieltermin bereits belegt.',
            ];
        }

        $createData = [
            'date'          => $newDate,
            'start_time'    => $startTime . ':00',
            'end_time'      => $endTime . ':00',
            'service_id'    => (int) $original['service_id'],
            'customer_id'   => (int) $original['customer_id'],
            'instructor_id' => $instructorId,
            'boat_id'       => $boatId,
            'location_id'   => $locationId,
            'status'        => (string) $original['status'],
            'price'         => (float) ($original['price'] ?? 0.00),
            'notes'         => !empty($original['notes']) ? ($original['notes'] . ' (Kopie von Termin #' . $id . ')') : ('Kopie von Termin #' . $id),
        ];

        $newId = self::createAppointment($createData);
        if ($newId <= 0) {
            return [
                'success'    => false,
                'error_code' => 'CREATE_FAILED',
                'message'    => 'Fehler beim Anlegen des duplizierten Termins in der Datenbank.',
            ];
        }

        $created = self::find($newId);
        $dateFormatted = date('d.m.Y', strtotime($newDate));

        return [
            'success'        => true,
            'error_code'     => null,
            'mode'           => 'copied',
            'appointment_id' => $newId,
            'message'        => "Termin erfolgreich dupliziert auf {$dateFormatted}, {$startTime} Uhr (Neuer Termin #{$newId}).",
            'data'           => $created,
        ];
    }

    /**
     * Einheitlicher Handler für Verschieben oder Duplizieren.
     *
     * @return array<string, mixed>
     */
    public static function handleMoveOrCopy(
        int $id,
        string $newDate,
        string $newStartTime,
        ?string $newEndTime = null,
        ?int $newInstructorId = null,
        ?int $newBoatId = null,
        ?int $newLocationId = null,
        bool $isCopy = false
    ): array {
        if ($isCopy) {
            return self::duplicateAppointment($id, $newDate, $newStartTime, $newEndTime, $newInstructorId, $newBoatId, $newLocationId);
        }
        return self::moveAppointment($id, $newDate, $newStartTime, $newEndTime, $newInstructorId, $newBoatId, $newLocationId);
    }

    /**
     * Berechnet die Endzeit basierend auf Originaldauer oder Service-Dauer.
     */
    private static function calculateEndTime(array $appointment, string $date, string $startTime): string
    {
        $durationMinutes = 90; // Standard

        if (!empty($appointment['service_duration'])) {
            $durationMinutes = (int) $appointment['service_duration'];
        } elseif (!empty($appointment['start_time']) && !empty($appointment['end_time'])) {
            $origStart = strtotime((string) $appointment['date'] . ' ' . (string) $appointment['start_time']);
            $origEnd   = strtotime((string) $appointment['date'] . ' ' . (string) $appointment['end_time']);
            if ($origEnd > $origStart) {
                $durationMinutes = (int) round(($origEnd - $origStart) / 60);
            }
        }

        $startDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
        if (!$startDt) {
            return date('H:i', strtotime("{$date} {$startTime} +{$durationMinutes} minutes"));
        }

        return $startDt->modify("+{$durationMinutes} minutes")->format('H:i');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getAppointmentById($id);
    }

    /**
     * @param array<string, mixed>|null $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(?array $filters = null, int $limit = 100, int $offset = 0): array
    {
        return BoatingSchoolRepository::getAppointments($filters, $limit, $offset);
    }

    /**
     * Termine eines bestimmten Kunden abrufen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByCustomer(int $customerId): array
    {
        return BoatingSchoolRepository::getAppointments(['customer_id' => $customerId], 200);
    }

    /**
     * Termine eines Kunden anhand seiner E-Mail-Adresse abrufen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByCustomerEmail(string $email): array
    {
        $cust = CustomerService::findByEmail($email);
        if ($cust === null) {
            return [];
        }
        return self::getByCustomer((int) $cust['id']);
    }

    /**
     * Storniert einen Termin durch den Kunden nach Verifikation der E-Mail.
     *
     * @return array<string, mixed> ['success' => bool, 'message' => string]
     */
    public static function cancelByCustomer(int $appointmentId, string $customerEmail): array
    {
        $app = self::find($appointmentId);
        if ($app === null) {
            return ['success' => false, 'message' => 'Der Termin wurde nicht gefunden.'];
        }

        $appCustomerEmail = strtolower(trim((string) ($app['customer_email'] ?? '')));
        $cleanInputEmail = strtolower(trim($customerEmail));

        if ($appCustomerEmail === '' || $appCustomerEmail !== $cleanInputEmail) {
            return ['success' => false, 'message' => 'Sicherheitsprüfung fehlgeschlagen: Die E-Mail-Adresse stimmt nicht mit der Buchung überein.'];
        }

        if (in_array($app['status'], [self::STATUS_CANCELLED_CUSTOMER, self::STATUS_CANCELLED_SYSTEM], true)) {
            return ['success' => false, 'message' => 'Dieser Termin ist bereits storniert.'];
        }

        if ($app['status'] === self::STATUS_COMPLETED) {
            return ['success' => false, 'message' => 'Bereits absolvierte Termine können nicht storniert werden.'];
        }

        $success = self::updateStatus($appointmentId, self::STATUS_CANCELLED_CUSTOMER);

        if ($success) {
            if (class_exists('ActivityLog')) {
                ActivityLog::log('boating.booking_cancelled_by_customer', $cleanInputEmail, [
                    'appointment_id' => $appointmentId,
                    'customer_id'    => $app['customer_id'],
                    'service_id'     => $app['service_id'],
                    'date'           => $app['date'],
                    'start_time'     => $app['start_time'],
                ]);
            }

            return ['success' => true, 'message' => 'Ihr Termin wurde erfolgreich storniert.'];
        }

        return ['success' => false, 'message' => 'Fehler beim Stornieren des Termins. Bitte versuchen Sie es erneut.'];
    }
}
