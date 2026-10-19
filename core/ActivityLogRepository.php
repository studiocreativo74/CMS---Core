<?php

declare(strict_types=1);

/**
 * ActivityLogRepository - Service-Klasse zur strukturierten Abfrage und Filterung von Audit-Logs
 */
final class ActivityLogRepository
{
    /**
     * Gibt eine Liste gefilterter Log-Einträge paginiert zurück.
     *
     * @param array<string, mixed> $filters Filterkriterien (event_type, category, email, search, date_from, date_to, case_id, user_id)
     * @param int $limit Maximale Anzahl der Einträge (Standard: 100)
     * @param int $offset Versatz / Startposition für Paginierung
     * @return array<int, array<string, mixed>> Normalisierte Log-Einträge
     */
    public static function list(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            $table = ActivityLog::resolveTable();
            $cols = ActivityLog::resolveColumns($table);

            // Spalten-Mapping vorbereiten
            $eventCol = in_array('event_type', $cols, true) ? '`event_type`' : (in_array('action', $cols, true) ? '`action`' : "'unbekannt'");
            $emailCol = in_array('email', $cols, true) ? '`email`' : (in_array('user_email', $cols, true) ? '`user_email`' : "NULL");
            $uaCol    = in_array('user_agent', $cols, true) ? '`user_agent`' : "NULL";
            $ipCol    = in_array('ip_address', $cols, true) ? '`ip_address`' : "NULL";
            $detCol   = in_array('details', $cols, true) ? '`details`' : "NULL";
            $dateCol  = in_array('created_at', $cols, true) ? '`created_at`' : "NOW()";

            [$whereSql, $params] = self::buildWhereClause($filters, $cols);

            $selectSql = "SELECT `id`,
                                 {$eventCol} AS `event_type`,
                                 {$emailCol} AS `email`,
                                 {$ipCol} AS `ip_address`,
                                 {$uaCol} AS `user_agent`,
                                 {$detCol} AS `details`,
                                 {$dateCol} AS `created_at`
                          FROM `{$table}`
                          {$whereSql}
                          ORDER BY {$dateCol} DESC, `id` DESC
                          LIMIT {$limit} OFFSET {$offset}";

            $rows = DB::fetchAll($selectSql, $params);

            // Normalisierung der Ergebniszeilen
            $result = [];
            foreach ($rows as $row) {
                $eventType = (string) ($row['event_type'] ?? 'general.event');
                $rawDetails = $row['details'] ?? null;
                $parsedDetails = self::parseDetails(is_string($rawDetails) ? $rawDetails : null);
                $eventMeta = self::getEventMeta($eventType);

                $result[] = [
                    'id'           => (int) ($row['id'] ?? 0),
                    'event_type'   => $eventType,
                    'email'        => !empty($row['email']) ? (string) $row['email'] : null,
                    'ip_address'   => !empty($row['ip_address']) ? (string) $row['ip_address'] : null,
                    'user_agent'   => !empty($row['user_agent']) ? (string) $row['user_agent'] : null,
                    'created_at'   => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                    'details'      => is_string($rawDetails) ? $rawDetails : '',
                    'details_data' => $parsedDetails,
                    'meta'         => $eventMeta,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            error_log('ActivityLogRepository::list Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Zählt die Anzahl der Einträge für gegebene Filterkriterien.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public static function count(array $filters = []): int
    {
        try {
            $table = ActivityLog::resolveTable();
            $cols = ActivityLog::resolveColumns($table);

            [$whereSql, $params] = self::buildWhereClause($filters, $cols);

            $countSql = "SELECT COUNT(*) AS `c` FROM `{$table}` {$whereSql}";
            $row = DB::fetchOne($countSql, $params);

            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('ActivityLogRepository::count Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ermittelt alle Audit-Log-Einträge für einen spezifischen Sicherungsfall.
     *
     * @param int $caseId Vorgangs-ID
     * @param int $limit Maximale Anzahl der Einträge (Standard: 50)
     * @return array<int, array<string, mixed>>
     */
    public static function listForCase(int $caseId, int $limit = 50): array
    {
        if ($caseId <= 0) {
            return [];
        }

        // Ermitteln, ob Vorgangsnummer existiert, um auch Volltexttreffer zu finden
        $caseNumber = null;
        try {
            $stmt = DB::query("SHOW TABLES LIKE 'secure_cases'");
            if ($stmt->fetch()) {
                $caseRow = DB::fetchOne('SELECT `case_number` FROM `secure_cases` WHERE `id` = :id LIMIT 1', ['id' => $caseId]);
                if (!empty($caseRow['case_number'])) {
                    $caseNumber = (string) $caseRow['case_number'];
                }
            }
        } catch (\Throwable $e) {
            // Ignorieren falls Tabelle nicht existiert
        }

        $filters = [
            'case_id'     => $caseId,
            'case_number' => $caseNumber,
        ];

        return self::list($filters, $limit, 0);
    }

    /**
     * Ermittelt alle Audit-Log-Einträge für ein bestimmtes Benutzerkonto.
     *
     * @param int $userId ID des Benutzers
     * @param int $limit Maximale Anzahl der Einträge (Standard: 50)
     * @return array<int, array<string, mixed>>
     */
    public static function listForUser(int $userId, int $limit = 50): array
    {
        if ($userId <= 0) {
            return [];
        }

        $userEmail = null;
        try {
            $userRow = DB::fetchOne('SELECT `email` FROM `users` WHERE `id` = :id LIMIT 1', ['id' => $userId]);
            if (!empty($userRow['email'])) {
                $userEmail = (string) $userRow['email'];
            }
        } catch (\Throwable $e) {
            // Ignorieren falls DB nicht erreichbar
        }

        $filters = [
            'user_id'    => $userId,
            'user_email' => $userEmail,
        ];

        return self::list($filters, $limit, 0);
    }

    /**
     * Ermittelt eine Liste aller distinkten Event-Typen zur Verwendung in Filter-Dropdowns.
     *
     * @return array<int, string>
     */
    public static function getDistinctEventTypes(): array
    {
        try {
            $table = ActivityLog::resolveTable();
            $cols = ActivityLog::resolveColumns($table);

            $eventCol = in_array('event_type', $cols, true) ? '`event_type`' : (in_array('action', $cols, true) ? '`action`' : null);
            if ($eventCol === null) {
                return [];
            }

            $rows = DB::fetchAll("SELECT DISTINCT {$eventCol} AS `event_type` FROM `{$table}` WHERE {$eventCol} IS NOT NULL AND {$eventCol} != '' ORDER BY `event_type` ASC");
            $types = [];
            foreach ($rows as $r) {
                if (!empty($r['event_type'])) {
                    $types[] = (string) $r['event_type'];
                }
            }
            return $types;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Berechnet zusammenfassende Statistiken für die Admin-Übersicht.
     *
     * @return array<string, int|string>
     */
    public static function getStatistics(): array
    {
        $stats = [
            'total_count'       => 0,
            'today_count'       => 0,
            'case_events_count' => 0,
            'latest_timestamp'  => '—',
        ];

        try {
            $table = ActivityLog::resolveTable();
            $cols = ActivityLog::resolveColumns($table);

            $eventCol = in_array('event_type', $cols, true) ? '`event_type`' : (in_array('action', $cols, true) ? '`action`' : null);
            $dateCol  = in_array('created_at', $cols, true) ? '`created_at`' : 'NOW()';

            // 1. Gesamtzahl
            $rowTotal = DB::fetchOne("SELECT COUNT(*) AS `c`, MAX({$dateCol}) AS `max_date` FROM `{$table}`");
            if ($rowTotal) {
                $stats['total_count'] = (int) ($rowTotal['c'] ?? 0);
                if (!empty($rowTotal['max_date'])) {
                    $stats['latest_timestamp'] = (string) $rowTotal['max_date'];
                }
            }

            // 2. Heute
            $rowToday = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `{$table}` WHERE DATE({$dateCol}) = CURRENT_DATE()");
            if ($rowToday) {
                $stats['today_count'] = (int) ($rowToday['c'] ?? 0);
            }

            // 3. Sicherungsfälle (Events mit secure_case.* oder Fallbezug)
            if ($eventCol !== null) {
                $rowCases = DB::fetchOne("SELECT COUNT(*) AS `c` FROM `{$table}` WHERE {$eventCol} LIKE 'secure_case%'");
                if ($rowCases) {
                    $stats['case_events_count'] = (int) ($rowCases['c'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            // Ignorieren
        }

        return $stats;
    }

    /**
     * Parst Rohdaten sicher in ein assoziatives Array.
     *
     * @param string|null $raw
     * @return array<string, mixed>
     */
    public static function parseDetails(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Falls es kein JSON ist, Rohwert als Einzeleintrag zurückgeben
        return ['message' => $raw];
    }

    /**
     * Liefert visuelle Formatierungs-Metadaten (Badge-Farbe, Icon, Label) für einen Event-Typ.
     *
     * @param string $eventType
     * @return array{
     *     title: string,
     *     category: string,
     *     badge_class: string,
     *     icon_class: string,
     *     description: string
     * }
     */
    public static function getEventMeta(string $eventType): array
    {
        $map = [
            // Sicherungsfall-Ereignisse
            'secure_case.created' => [
                'title'       => 'Vorgang erstellt',
                'category'    => 'Sicherungsfall',
                'badge_class' => 'bg-success text-white',
                'icon_class'  => 'bi-folder-plus',
                'description' => 'Ein neuer Sicherungsantrag wurde eingereicht.',
            ],
            'secure_case.warrant_uploaded' => [
                'title'       => 'Editionsverfügung hochgeladen',
                'category'    => 'Sicherungsfall',
                'badge_class' => 'bg-primary text-white',
                'icon_class'  => 'bi-file-earmark-lock',
                'description' => 'Editionsverfügung wurde im System hinterlegt.',
            ],
            'secure_case.warrant_sp_uploaded' => [
                'title'       => 'In SharePoint archiviert',
                'category'    => 'SharePoint',
                'badge_class' => 'bg-info text-dark',
                'icon_class'  => 'bi-cloud-arrow-up',
                'description' => 'Editionsverfügung wurde automatisiert in SharePoint abgelegt.',
            ],
            'secure_case.file_uploaded' => [
                'title'       => 'Sicherungsdatei hochgeladen',
                'category'    => 'Sicherungsfall',
                'badge_class' => 'bg-primary text-white',
                'icon_class'  => 'bi-upload',
                'description' => 'Neue Videodatei oder Anlage zum Vorgang hinzugefügt.',
            ],
            'secure_case.file_downloaded' => [
                'title'       => 'Datei heruntergeladen',
                'category'    => 'Download',
                'badge_class' => 'bg-warning text-dark',
                'icon_class'  => 'bi-download',
                'description' => 'Eine Sicherungsdatei wurde über den Datenraum bezogen.',
            ],
            'secure_case.download_enabled' => [
                'title'       => 'Download freigeschaltet',
                'category'    => 'Freigabe',
                'badge_class' => 'bg-teal text-white bg-opacity-75',
                'icon_class'  => 'bi-unlock',
                'description' => 'Download-Zugang für Antragsteller wurde aktiviert.',
            ],
            'secure_case.download_disabled' => [
                'title'       => 'Download gesperrt',
                'category'    => 'Freigabe',
                'badge_class' => 'bg-secondary text-white',
                'icon_class'  => 'bi-lock',
                'description' => 'Download-Zugang wurde gesperrt oder ist abgelaufen.',
            ],
            'secure_case.status_updated' => [
                'title'       => 'Status geändert',
                'category'    => 'Sicherungsfall',
                'badge_class' => 'bg-indigo text-white',
                'icon_class'  => 'bi-arrow-repeat',
                'description' => 'Bearbeitungsstatus des Vorgangs wurde modifiziert.',
            ],

            // Authentifizierung
            'auth.login' => [
                'title'       => 'Benutzer-Anmeldung',
                'category'    => 'Sicherheit',
                'badge_class' => 'bg-success text-white',
                'icon_class'  => 'bi-box-arrow-in-right',
                'description' => 'Erfolgreiche Anmeldung am System.',
            ],
            'auth.logout' => [
                'title'       => 'Benutzer-Abmeldung',
                'category'    => 'Sicherheit',
                'badge_class' => 'bg-light text-dark border',
                'icon_class'  => 'bi-box-arrow-right',
                'description' => 'Benutzer hat die Sitzung beendet.',
            ],
            'auth.failed' => [
                'title'       => 'Fehlgeschlagener Login',
                'category'    => 'Sicherheit',
                'badge_class' => 'bg-danger text-white',
                'icon_class'  => 'bi-shield-x',
                'description' => 'Anmeldeversuch mit fehlerhaften Anmeldedaten.',
            ],
            'magic_code.generated' => [
                'title'       => 'Magic-Code erstellt',
                'category'    => 'Sicherheit',
                'badge_class' => 'bg-info text-dark',
                'icon_class'  => 'bi-key',
                'description' => 'Ein Einmalpasswort / Magic Code wurde erzeugt.',
            ],

            // Benutzer & Rollen
            'user.created' => [
                'title'       => 'Benutzer erstellt',
                'category'    => 'Benutzerverwaltung',
                'badge_class' => 'bg-success text-white',
                'icon_class'  => 'bi-person-plus',
                'description' => 'Ein neues Benutzerkonto wurde angelegt.',
            ],
            'user.updated' => [
                'title'       => 'Benutzer aktualisiert',
                'category'    => 'Benutzerverwaltung',
                'badge_class' => 'bg-info text-dark',
                'icon_class'  => 'bi-person-gear',
                'description' => 'Stammdaten des Benutzers wurden bearbeitet.',
            ],
            'user.role_changed' => [
                'title'       => 'Rolle modifiziert',
                'category'    => 'Benutzerverwaltung',
                'badge_class' => 'bg-dark text-white',
                'icon_class'  => 'bi-shield-shaded',
                'description' => 'Rollen oder Zugriffsrechte wurden angepasst.',
            ],

            // System & Health
            'healthcheck.mail_failed' => [
                'title'       => 'Mailversand fehlgeschlagen',
                'category'    => 'System & Diagnose',
                'badge_class' => 'bg-danger text-white',
                'icon_class'  => 'bi-envelope-exclamation',
                'description' => 'Mail-Versandtest der Systemdiagnose schlug fehl.',
            ],
            'healthcheck.ran' => [
                'title'       => 'Diagnose durchgeführt',
                'category'    => 'System & Diagnose',
                'badge_class' => 'bg-light text-secondary border',
                'icon_class'  => 'bi-heart-pulse',
                'description' => 'System-Integritätsprüfung wurde aufgerufen.',
            ],
            'system.settings_updated' => [
                'title'       => 'Einstellungen geändert',
                'category'    => 'System',
                'badge_class' => 'bg-secondary text-white',
                'icon_class'  => 'bi-gear-wide-connected',
                'description' => 'Globale Systemeinstellungen wurden gespeichert.',
            ],
        ];

        if (isset($map[$eventType])) {
            return $map[$eventType];
        }

        // Automatische Ableitung für unbekannte Event-Typen
        $parts = explode('.', $eventType);
        $prefix = $parts[0] ?? 'system';
        $action = $parts[1] ?? $eventType;

        $category = ucfirst(str_replace('_', ' ', $prefix));
        $title = ucwords(str_replace('_', ' ', $action));

        $badgeClass = 'bg-secondary text-white';
        $iconClass  = 'bi-dot';

        if (str_contains($eventType, 'error') || str_contains($eventType, 'fail')) {
            $badgeClass = 'bg-danger text-white';
            $iconClass  = 'bi-exclamation-octagon';
        } elseif (str_contains($eventType, 'created') || str_contains($eventType, 'success')) {
            $badgeClass = 'bg-success text-white';
            $iconClass  = 'bi-check-circle';
        } elseif (str_contains($eventType, 'update') || str_contains($eventType, 'edit')) {
            $badgeClass = 'bg-info text-dark';
            $iconClass  = 'bi-pencil-square';
        } elseif (str_contains($eventType, 'delete') || str_contains($eventType, 'remove')) {
            $badgeClass = 'bg-danger text-white';
            $iconClass  = 'bi-trash';
        }

        return [
            'title'       => $title,
            'category'    => $category,
            'badge_class' => $badgeClass,
            'icon_class'  => $iconClass,
            'description' => "Ereignis: {$eventType}",
        ];
    }

    /**
     * Baut die WHERE-Klausel und Bind-Parameter anhand des Filter-Arrays auf.
     *
     * @param array<string, mixed> $filters
     * @param array<string> $cols
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function buildWhereClause(array $filters, array $cols): array
    {
        $eventCol = in_array('event_type', $cols, true) ? '`event_type`' : (in_array('action', $cols, true) ? '`action`' : null);
        $emailCol = in_array('email', $cols, true) ? '`email`' : (in_array('user_email', $cols, true) ? '`user_email`' : null);
        $detCol   = in_array('details', $cols, true) ? '`details`' : null;
        $ipCol    = in_array('ip_address', $cols, true) ? '`ip_address`' : null;
        $dateCol  = in_array('created_at', $cols, true) ? '`created_at`' : null;

        $clauses = [];
        $params = [];

        // 1. Spezifischer Event-Typ
        if (!empty($filters['event_type']) && $eventCol !== null) {
            $clauses[] = "{$eventCol} = :filter_event_type";
            $params['filter_event_type'] = trim((string) $filters['event_type']);
        }

        // 2. Kategorie (z. B. 'secure_case', 'auth')
        if (!empty($filters['category']) && $eventCol !== null) {
            $clauses[] = "{$eventCol} LIKE :filter_category";
            $params['filter_category'] = trim((string) $filters['category']) . '.%';
        }

        // 3. E-Mail Filter
        if (!empty($filters['email']) && $emailCol !== null) {
            $clauses[] = "{$emailCol} LIKE :filter_email";
            $params['filter_email'] = '%' . trim((string) $filters['email']) . '%';
        }

        // 4. Datumsfilter Von
        if (!empty($filters['date_from']) && $dateCol !== null) {
            $dateFrom = trim((string) $filters['date_from']);
            if (strlen($dateFrom) === 10) {
                $dateFrom .= ' 00:00:00';
            }
            $clauses[] = "{$dateCol} >= :filter_date_from";
            $params['filter_date_from'] = $dateFrom;
        }

        // 5. Datumsfilter Bis
        if (!empty($filters['date_to']) && $dateCol !== null) {
            $dateTo = trim((string) $filters['date_to']);
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $clauses[] = "{$dateCol} <= :filter_date_to";
            $params['filter_date_to'] = $dateTo;
        }

        // 6. Sicherungsfall-Filter (case_id oder case_number)
        if (!empty($filters['case_id'])) {
            $caseId = (int) $filters['case_id'];
            $caseSubClauses = [];

            if ($detCol !== null) {
                $caseSubClauses[] = "{$detCol} LIKE :case_id_pattern_num";
                $caseSubClauses[] = "{$detCol} LIKE :case_id_pattern_str";
                $params['case_id_pattern_num'] = '%"case_id":' . $caseId . '%';
                $params['case_id_pattern_str'] = '%"case_id":"' . $caseId . '"%';

                if (!empty($filters['case_number'])) {
                    $caseSubClauses[] = "{$detCol} LIKE :case_number_pattern";
                    $params['case_number_pattern'] = '%' . trim((string) $filters['case_number']) . '%';
                }
            }

            if (!empty($caseSubClauses)) {
                $clauses[] = '(' . implode(' OR ', $caseSubClauses) . ')';
            }
        }

        // 7. Benutzer-Filter (user_id oder user_email)
        if (!empty($filters['user_id']) || !empty($filters['user_email'])) {
            $userSubClauses = [];

            if (!empty($filters['user_email']) && $emailCol !== null) {
                $userSubClauses[] = "{$emailCol} = :user_email_exact";
                $params['user_email_exact'] = trim((string) $filters['user_email']);
            }

            if (!empty($filters['user_id']) && $detCol !== null) {
                $userId = (int) $filters['user_id'];
                $userSubClauses[] = "{$detCol} LIKE :user_id_num";
                $userSubClauses[] = "{$detCol} LIKE :user_id_str";
                $params['user_id_num'] = '%"user_id":' . $userId . '%';
                $params['user_id_str'] = '%"user_id":"' . $userId . '"%';
            }

            if (!empty($userSubClauses)) {
                $clauses[] = '(' . implode(' OR ', $userSubClauses) . ')';
            }
        }

        // 8. Volltext-Suchbegriff (über Event, E-Mail, Details und IP)
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $searchPattern = '%' . $search . '%';
            $searchSubClauses = [];

            if ($eventCol !== null) {
                $searchSubClauses[] = "{$eventCol} LIKE :search_term_ev";
                $params['search_term_ev'] = $searchPattern;
            }
            if ($emailCol !== null) {
                $searchSubClauses[] = "{$emailCol} LIKE :search_term_em";
                $params['search_term_em'] = $searchPattern;
            }
            if ($ipCol !== null) {
                $searchSubClauses[] = "{$ipCol} LIKE :search_term_ip";
                $params['search_term_ip'] = $searchPattern;
            }
            if ($detCol !== null) {
                $searchSubClauses[] = "{$detCol} LIKE :search_term_dt";
                $params['search_term_dt'] = $searchPattern;
            }

            if (!empty($searchSubClauses)) {
                $clauses[] = '(' . implode(' OR ', $searchSubClauses) . ')';
            }
        }

        $whereSql = !empty($clauses) ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$whereSql, $params];
    }
}
