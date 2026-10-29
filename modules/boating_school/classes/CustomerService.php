<?php

declare(strict_types=1);

/**
 * CustomerService
 *
 * Verwaltet Schüler & Kunden (bs_customers: Fahrschüler + Mieter) der Bootsfahrschule
 * sowie CRM-Funktionen, Abonnements (bs_subscriptions), vCard-Export und CSV-Import/-Export.
 */
class CustomerService
{
    /** @var array<string, string> Kundentypen */
    public const TYPES = [
        'student' => 'Fahrschüler',
        'renter'  => 'Bootsmieter',
        'both'    => 'Fahrschüler & Mieter',
    ];

    /** @var array<string, string> CRM Lebenszyklus-Stufen */
    public const CRM_STAGES = [
        'lead'          => 'Interessent / Lead',
        'student'       => 'Fahrschüler (aktiv)',
        'active_renter' => 'Aktiver Bootsmieter',
        'regular'       => 'Stammkunde',
        'vip'           => 'VIP-Kunde',
        'inactive'      => 'Inaktiv / Ehemalig',
    ];

    /** @var array<string, string> Bevorzugte Kontaktwege */
    public const PREFERRED_CONTACTS = [
        'email'    => 'E-Mail',
        'phone'    => 'Telefon / Anruf',
        'whatsapp' => 'WhatsApp',
        'sms'      => 'SMS',
    ];

    /** @var array<string, string> Erfahrungsstufen */
    public const EXPERIENCE_LEVELS = [
        'beginner'     => 'Anfänger (ohne Vorpraxis)',
        'intermediate' => 'Grundkenntnisse vorhanden',
        'experienced'  => 'Erfahren',
        'pro'          => 'Profi / Skipper',
    ];

    public static function getTypes(): array
    {
        return self::TYPES;
    }

    public static function getCrmStages(): array
    {
        return self::CRM_STAGES;
    }

    public static function getPreferredContacts(): array
    {
        return self::PREFERRED_CONTACTS;
    }

    public static function getExperienceLevels(): array
    {
        return self::EXPERIENCE_LEVELS;
    }

    /**
     * Erzeugt eine neue Kundennummer im Format KD-{JAHR}-{ZÄHLER}.
     */
    public static function generateCustomerNumber(): string
    {
        $year = (int) date('Y');
        $count = BoatingSchoolRepository::countCustomers() + 1;
        return sprintf('KD-%d-%04d', $year, $count);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        return BoatingSchoolRepository::getCustomers($filters, $limit, $offset);
    }

    public static function count(array $filters = []): int
    {
        return BoatingSchoolRepository::countCustomers($filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return BoatingSchoolRepository::getCustomerById($id);
    }

    /**
     * Liefert die 360-Grad CRM-Akte für einen Kunden.
     *
     * @return array<string, mixed>|null
     */
    public static function getCrmDetails(int $id): ?array
    {
        return BoatingSchoolRepository::getCustomerCrmDetails($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        if (empty($data['customer_number'])) {
            $data['customer_number'] = self::generateCustomerNumber();
        }
        return BoatingSchoolRepository::createCustomer($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateCustomer($id, $data);
    }

    public static function delete(int $id): bool
    {
        if (class_exists('BoatingCustomerMagicLinkService')) {
            BoatingCustomerMagicLinkService::revokeAllForCustomer($id);
        }
        return BoatingSchoolRepository::deleteCustomer($id);
    }

    public static function toggleStatus(int $id): bool
    {
        $res = BoatingSchoolRepository::toggleCustomerStatus($id);
        $cust = BoatingSchoolRepository::getCustomerById($id);
        if ($cust && (int) ($cust['is_active'] ?? 1) === 0) {
            if (class_exists('BoatingCustomerMagicLinkService')) {
                BoatingCustomerMagicLinkService::revokeAllForCustomer($id);
            }
        }
        return $res;
    }

    /**
     * Anonymisiert einen Kunden (DSG/DSGVO) und widerruft alle Zugangslinks.
     */
    public static function anonymize(int $id): bool
    {
        if (class_exists('BoatingCustomerMagicLinkService')) {
            BoatingCustomerMagicLinkService::revokeAllForCustomer($id);
        }

        $anonData = [
            'first_name' => 'Anonymisiert',
            'last_name'  => 'Kunde #' . $id,
            'email'      => 'anonymized-' . $id . '@bootschule.local',
            'phone'      => null,
            'notes'      => 'Kunde wurde am ' . date('d.m.Y H:i') . ' DSGVO-konform anonymisiert.',
            'is_active'  => 0,
        ];

        return BoatingSchoolRepository::updateCustomer($id, $anonData);
    }

    /**
     * Sucht einen Kunden anhand seiner E-Mail-Adresse.
     *
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $email = trim(strtolower($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return BoatingSchoolRepository::getCustomerByEmail($email);
    }

    /**
     * Sucht einen Kunden anhand seiner Kundennummer.
     *
     * @return array<string, mixed>|null
     */
    public static function findByNumber(string $number): ?array
    {
        $number = trim($number);
        if ($number === '') {
            return null;
        }
        return BoatingSchoolRepository::getCustomerByNumber($number);
    }

    /**
     * Findet einen bestehenden Kunden anhand der E-Mail oder legt einen neuen an.
     *
     * @param array<string, mixed> $data
     */
    public static function findOrCreateByEmail(string $email, array $data): int
    {
        $email = trim(strtolower($email));
        $existing = self::findByEmail($email);

        if ($existing !== null) {
            $updateData = [];
            if (empty($existing['phone']) && !empty($data['phone'])) {
                $updateData['phone'] = trim((string) $data['phone']);
            }
            if (empty($existing['target_license']) && !empty($data['target_license'])) {
                $updateData['target_license'] = trim((string) $data['target_license']);
            }
            if (!empty($updateData)) {
                $merged = array_merge($existing, $updateData);
                self::update((int) $existing['id'], $merged);
            }
            return (int) $existing['id'];
        }

        $customerData = [
            'first_name'     => trim((string) ($data['first_name'] ?? '')),
            'last_name'      => trim((string) ($data['last_name'] ?? '')),
            'email'          => $email,
            'phone'          => !empty($data['phone']) ? trim((string) $data['phone']) : null,
            'street'         => !empty($data['street']) ? trim((string) $data['street']) : null,
            'zip'            => !empty($data['zip']) ? trim((string) $data['zip']) : null,
            'city'           => !empty($data['city']) ? trim((string) $data['city']) : null,
            'type'           => !empty($data['type']) ? trim((string) $data['type']) : 'student',
            'target_license' => !empty($data['target_license']) ? trim((string) $data['target_license']) : null,
            'notes'          => !empty($data['notes']) ? trim((string) $data['notes']) : 'Online-Registrierung über Buchungsportal',
            'is_active'      => 1,
        ];

        return self::create($customerData);
    }

    // =========================================================================
    // ABO- & PUNKTEKARTEN (SUBSCRIPTIONS)
    // =========================================================================

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptions(int $customerId): array
    {
        return BoatingSchoolRepository::getSubscriptionsByCustomerId($customerId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getSubscription(int $id): ?array
    {
        return BoatingSchoolRepository::getSubscriptionById($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getCustomerSubscriptions(int $customerId): array
    {
        return BoatingSchoolRepository::getSubscriptionsByCustomerId($customerId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptionsByCustomerId(int $customerId): array
    {
        return BoatingSchoolRepository::getSubscriptionsByCustomerId($customerId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createSubscription(array $data): int
    {
        return BoatingSchoolRepository::createSubscription($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updateSubscription(int $id, array $data): bool
    {
        return BoatingSchoolRepository::updateSubscription($id, $data);
    }

    public static function deleteSubscription(int $id): bool
    {
        return BoatingSchoolRepository::deleteSubscription($id);
    }

    /**
     * Bucht Einheiten von einem Abo ab.
     */
    public static function useSubscription(
        int $subscriptionId,
        int $units = 1,
        ?int $appointmentId = null,
        ?string $note = null,
        ?string $usedDate = null
    ): int {
        return BoatingSchoolRepository::recordSubscriptionUsage($subscriptionId, $units, $appointmentId, $note, $usedDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getSubscriptionUsages(int $subscriptionId): array
    {
        return BoatingSchoolRepository::getSubscriptionUsages($subscriptionId);
    }

    public static function deleteSubscriptionUsage(int $usageId): bool
    {
        return BoatingSchoolRepository::deleteSubscriptionUsage($usageId);
    }

    // =========================================================================
    // VCARD EXPORT (.vcf)
    // =========================================================================

    /**
     * Generiert eine vCard 3.0 für einen Kunden.
     *
     * @param array<string, mixed> $customer
     */
    public static function generateVCard(array $customer): string
    {
        $firstName = trim((string) ($customer['first_name'] ?? ''));
        $lastName = trim((string) ($customer['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);
        $company = trim((string) ($customer['company'] ?? ''));
        $email = trim((string) ($customer['email'] ?? ''));
        $phone = trim((string) ($customer['phone'] ?? ''));
        $street = trim((string) ($customer['street'] ?? ''));
        $zip = trim((string) ($customer['zip'] ?? ''));
        $city = trim((string) ($customer['city'] ?? ''));
        $birthdate = trim((string) ($customer['birthdate'] ?? ''));
        $targetLicense = trim((string) ($customer['target_license'] ?? ''));
        $licensesHeld = trim((string) ($customer['licenses_held'] ?? ''));
        $licenseNumber = trim((string) ($customer['license_number'] ?? ''));
        $customerNumber = trim((string) ($customer['customer_number'] ?? ''));
        $notes = trim((string) ($customer['notes'] ?? ''));
        $internalNotes = trim((string) ($customer['internal_notes'] ?? ''));

        // Zusätzliche Notizen für die vCard zusammenstellen
        $noteParts = [];
        if ($customerNumber !== '') {
            $noteParts[] = 'Kundennr: ' . $customerNumber;
        }
        if ($targetLicense !== '') {
            $noteParts[] = 'Ziel-Führerschein: ' . $targetLicense;
        }
        if ($licensesHeld !== '') {
            $noteParts[] = 'Vorhandene Lizenzen: ' . $licensesHeld;
        }
        if ($licenseNumber !== '') {
            $noteParts[] = 'Führerscheinnr: ' . $licenseNumber;
        }
        if ($notes !== '') {
            $noteParts[] = 'Ausbildungsnotiz: ' . $notes;
        }
        if ($internalNotes !== '') {
            $noteParts[] = 'CRM-Notiz: ' . $internalNotes;
        }
        $compiledNotes = implode(" \\n ", array_map(static fn($p) => str_replace(["\r", "\n", ';'], [' ', ' ', ','], $p), $noteParts));

        $lines = [];
        $lines[] = 'BEGIN:VCARD';
        $lines[] = 'VERSION:3.0';
        $lines[] = 'PRODID:-//Boatsfahrschule CRM//vCard 3.0//DE';
        $lines[] = 'N:' . self::escapeVCard($lastName) . ';' . self::escapeVCard($firstName) . ';;;';
        $lines[] = 'FN:' . self::escapeVCard($fullName);

        if ($company !== '') {
            $lines[] = 'ORG:' . self::escapeVCard($company);
        }

        if ($targetLicense !== '') {
            $lines[] = 'TITLE:' . self::escapeVCard($targetLicense);
        }

        if ($phone !== '') {
            $lines[] = 'TEL;TYPE=CELL,VOICE:' . self::escapeVCard($phone);
        }

        if ($email !== '') {
            $lines[] = 'EMAIL;TYPE=INTERNET,PREF:' . self::escapeVCard($email);
        }

        if ($street !== '' || $city !== '' || $zip !== '') {
            // Format ADR: PO Box; Extended Addr; Street; City; Region; Postal Code; Country
            $lines[] = 'ADR;TYPE=HOME:;;' . self::escapeVCard($street) . ';' . self::escapeVCard($city) . ';;' . self::escapeVCard($zip) . ';Schweiz';
        }

        if ($birthdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            $lines[] = 'BDAY:' . $birthdate;
        }

        if ($compiledNotes !== '') {
            $lines[] = 'NOTE:' . $compiledNotes;
        }

        $lines[] = 'REV:' . gmdate('Ymd\THis\Z');
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    private static function escapeVCard(string $str): string
    {
        return str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $str);
    }

    // =========================================================================
    // CSV EXPORT (Serverseitig, UTF-8 BOM für Excel)
    // =========================================================================

    /**
     * Sendet einen vollständigen CSV-Export der gefilterten Kunden.
     *
     * @param array<string, mixed> $filters
     */
    public static function streamCsvExport(array $filters = []): void
    {
        $customers = BoatingSchoolRepository::getCustomers($filters, 10000, 0);

        $filename = 'bootschule_kunden_' . date('Y-m-d_His') . '.csv';

        // Saubere HTTP-Header
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        $output = fopen('php://output', 'wb');
        if (!$output) {
            return;
        }

        // UTF-8 Byte Order Mark (BOM) für Excel-Kompatibilität
        fwrite($output, "\xEF\xBB\xBF");

        // Kopfzeile
        $headers = [
            'Kundennummer',
            'Kundentyp',
            'CRM_Status',
            'Vorname',
            'Nachname',
            'Firma',
            'E-Mail',
            'Telefon',
            'Bevorzugter_Kontakt',
            'Strasse',
            'PLZ',
            'Ort',
            'Geburtsdatum',
            'Ziel_Fuehrerschein',
            'Vorhandene_Lizenzen',
            'Ausweisnummer',
            'Erfahrungsstufe',
            'Theorie_Bestanden',
            'Theorie_Datum',
            'Praxis_Bestanden',
            'Praxis_Datum',
            'Notfallkontakt',
            'Lead_Quelle',
            'Tags',
            'Notizen',
            'Interne_CRM_Notizen',
            'Status_Aktiv',
            'Erfasst_Am',
        ];
        fputcsv($output, $headers, ';');

        foreach ($customers as $c) {
            $row = [
                (string) ($c['customer_number'] ?? ''),
                self::TYPES[$c['type'] ?? 'student'] ?? (string) ($c['type'] ?? ''),
                self::CRM_STAGES[$c['crm_stage'] ?? 'student'] ?? (string) ($c['crm_stage'] ?? ''),
                (string) ($c['first_name'] ?? ''),
                (string) ($c['last_name'] ?? ''),
                (string) ($c['company'] ?? ''),
                (string) ($c['email'] ?? ''),
                (string) ($c['phone'] ?? ''),
                self::PREFERRED_CONTACTS[$c['preferred_contact'] ?? 'email'] ?? (string) ($c['preferred_contact'] ?? ''),
                (string) ($c['street'] ?? ''),
                (string) ($c['zip'] ?? ''),
                (string) ($c['city'] ?? ''),
                (string) ($c['birthdate'] ?? ''),
                (string) ($c['target_license'] ?? ''),
                (string) ($c['licenses_held'] ?? ''),
                (string) ($c['license_number'] ?? ''),
                self::EXPERIENCE_LEVELS[$c['experience_level'] ?? 'beginner'] ?? (string) ($c['experience_level'] ?? ''),
                !empty($c['theory_passed']) ? 'Ja' : 'Nein',
                (string) ($c['theory_passed_at'] ?? ''),
                !empty($c['practical_passed']) ? 'Ja' : 'Nein',
                (string) ($c['practical_passed_at'] ?? ''),
                (string) ($c['emergency_contact'] ?? ''),
                (string) ($c['lead_source'] ?? ''),
                (string) ($c['tags'] ?? ''),
                (string) ($c['notes'] ?? ''),
                (string) ($c['internal_notes'] ?? ''),
                !empty($c['is_active']) ? 'Aktiv' : 'Inaktiv',
                (string) ($c['created_at'] ?? ''),
            ];
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    // =========================================================================
    // CSV IMPORT (Serverseitig, robuste Spalten-Erkennung & Duplikatsprüfung)
    // =========================================================================

    /**
     * Importiert eine CSV-Datei serverseitig in die bs_customers-Tabelle.
     *
     * @param string $filePath Pfad zur temporären oder hochgeladenen CSV-Datei
     * @param string $duplicateMode 'skip' oder 'update'
     * @return array{success: bool, imported: int, updated: int, skipped: int, errors: array<string>}
     */
    public static function importCsv(string $filePath, string $duplicateMode = 'skip'): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success'  => false,
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['Die angegebene CSV-Datei konnte nicht gelesen werden.'],
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'success'  => false,
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['Fehler beim Öffnen der CSV-Datei.'],
            ];
        }

        // Trennzeichen ermitteln (Semikolon ';' oder Komma ',')
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [
                'success'  => false,
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['Die Datei ist leer.'],
            ];
        }

        // BOM entfernen
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        // Zurückspulen und fgetcsv nutzen
        rewind($handle);

        $rawHeader = fgetcsv($handle, 4096, $delimiter);
        if (!$rawHeader) {
            fclose($handle);
            return [
                'success'  => false,
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['Ungültige Kopfzeile in der CSV-Datei.'],
            ];
        }

        // Normalisierte Header-Zuordnung
        $columnMap = [];
        foreach ($rawHeader as $idx => $colName) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', $colName))));
            $columnMap[$clean] = $idx;
        }

        // Feld-Aliase für Flexibilität
        $aliasMap = [
            'first_name'          => ['vorname', 'first_name', 'firstname', 'givenname'],
            'last_name'           => ['nachname', 'last_name', 'lastname', 'surename', 'name'],
            'email'               => ['email', 'e_mail', 'mail'],
            'phone'               => ['telefon', 'phone', 'mobil', 'mobile', 'tel', 'handy'],
            'company'             => ['firma', 'company', 'unternehmen', 'organisation'],
            'customer_number'     => ['kundennummer', 'customer_number', 'kdnr', 'kd_nr'],
            'type'                => ['kundentyp', 'type', 'typ'],
            'crm_stage'           => ['crm_status', 'crm_stage', 'status_crm', 'phase'],
            'street'              => ['strasse', 'street', 'adresse', 'anschrift'],
            'zip'                 => ['plz', 'zip', 'postleitzahl'],
            'city'                => ['ort', 'city', 'wohnort', 'stadt'],
            'birthdate'           => ['geburtsdatum', 'birthdate', 'dob'],
            'target_license'      => ['ziel_fuehrerschein', 'target_license', 'fuehrerschein', 'kategorie'],
            'licenses_held'       => ['vorhandene_lizenzen', 'licenses_held', 'lizenzen', 'vorbesitz'],
            'license_number'      => ['ausweisnummer', 'license_number', 'patentnummer'],
            'experience_level'    => ['erfahrungsstufe', 'experience_level', 'praxis'],
            'preferred_contact'   => ['bevorzugter_kontakt', 'preferred_contact', 'kontaktkanal'],
            'lead_source'         => ['lead_quelle', 'lead_source', 'herkunft', 'quelle'],
            'tags'                => ['tags', 'stichworte', 'kategorien'],
            'emergency_contact'   => ['notfallkontakt', 'emergency_contact'],
            'notes'               => ['notizen', 'notes', 'bemerkungen'],
            'internal_notes'      => ['interne_crm_notizen', 'internal_notes', 'crm_notizen'],
        ];

        $matchedCols = [];
        foreach ($aliasMap as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($columnMap[$alias])) {
                    $matchedCols[$field] = $columnMap[$alias];
                    break;
                }
            }
        }

        // Mindestanforderung: Vorname oder Nachname muss vorhanden sein
        if (!isset($matchedCols['first_name']) && !isset($matchedCols['last_name'])) {
            fclose($handle);
            return [
                'success'  => false,
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['In der CSV-Datei wurden weder Vorname noch Nachname in der Kopfzeile erkannt. Erkannte Spalten: ' . implode(', ', array_keys($columnMap))],
            ];
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $rowNum++;

            $getField = static function (string $key) use ($matchedCols, $row): string {
                if (isset($matchedCols[$key], $row[$matchedCols[$key]])) {
                    return trim((string) $row[$matchedCols[$key]]);
                }
                return '';
            };

            $firstName = $getField('first_name');
            $lastName = $getField('last_name');

            // Falls Name als eine Spalte geliefert wurde und Vorname leer ist
            if ($firstName === '' && $lastName !== '') {
                $parts = explode(' ', $lastName, 2);
                if (count($parts) === 2) {
                    $firstName = $parts[0];
                    $lastName = $parts[1];
                }
            }

            if ($firstName === '' && $lastName === '') {
                // Leere Zeile überspringen
                continue;
            }

            $email = $getField('email');
            $customerNumber = $getField('customer_number');

            // Prüfen, ob Kunde bereits existiert
            $existing = null;
            if ($email !== '') {
                $existing = self::findByEmail($email);
            }
            if ($existing === null && $customerNumber !== '') {
                $existing = self::findByNumber($customerNumber);
            }

            // Typ normalisieren
            $rawType = strtolower($getField('type'));
            $type = 'student';
            if (str_contains($rawType, 'miet') || $rawType === 'renter') {
                $type = 'renter';
            } elseif (str_contains($rawType, 'beid') || $rawType === 'both') {
                $type = 'both';
            }

            // CRM-Stage normalisieren
            $rawStage = strtolower($getField('crm_stage'));
            $crmStage = 'student';
            if (str_contains($rawStage, 'lead') || str_contains($rawStage, 'interess')) {
                $crmStage = 'lead';
            } elseif (str_contains($rawStage, 'miet') || $rawStage === 'active_renter') {
                $crmStage = 'active_renter';
            } elseif (str_contains($rawStage, 'stamm') || $rawStage === 'regular') {
                $crmStage = 'regular';
            } elseif (str_contains($rawStage, 'vip')) {
                $crmStage = 'vip';
            } elseif (str_contains($rawStage, 'inaktiv') || $rawStage === 'inactive') {
                $crmStage = 'inactive';
            }

            // Geburtsdatum normalisieren (YYYY-MM-DD)
            $birthdate = $getField('birthdate');
            if ($birthdate !== '') {
                if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $birthdate, $m)) {
                    $birthdate = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
                }
            }

            $data = [
                'customer_number'   => $customerNumber ?: null,
                'type'              => $type,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'company'           => $getField('company') ?: null,
                'email'             => $email ?: null,
                'phone'             => $getField('phone') ?: null,
                'preferred_contact' => $getField('preferred_contact') ?: 'email',
                'lead_source'       => $getField('lead_source') ?: null,
                'crm_stage'         => $crmStage,
                'tags'              => $getField('tags') ?: null,
                'street'            => $getField('street') ?: null,
                'zip'               => $getField('zip') ?: null,
                'city'              => $getField('city') ?: null,
                'birthdate'         => $birthdate ?: null,
                'target_license'    => $getField('target_license') ?: null,
                'licenses_held'     => $getField('licenses_held') ?: null,
                'license_number'    => $getField('license_number') ?: null,
                'experience_level'  => $getField('experience_level') ?: 'beginner',
                'emergency_contact' => $getField('emergency_contact') ?: null,
                'notes'             => $getField('notes') ?: null,
                'internal_notes'    => $getField('internal_notes') ?: null,
                'is_active'         => 1,
            ];

            if ($existing !== null) {
                if ($duplicateMode === 'skip') {
                    $skipped++;
                    continue;
                }
                // Bei 'update' aktualisieren
                self::update((int) $existing['id'], array_merge($existing, array_filter($data)));
                $updated++;
            } else {
                try {
                    self::create($data);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Zeile {$rowNum} ({$firstName} {$lastName}): " . $e->getMessage();
                }
            }
        }

        fclose($handle);

        return [
            'success'  => empty($errors),
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }
}
