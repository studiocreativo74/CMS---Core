<?php

declare(strict_types=1);

/**
 * HealthCheck
 *
 * Systemstatus- & Health-Check Service für das CMS:
 * - Datenbankverbindung & Tabellenintegrität
 * - Upload-Verzeichnisse & Schreibrechte
 * - E-Mail-Konfiguration & optionaler Testversand
 * - SharePoint / Microsoft Graph Konfiguration & optionaler Token-Test
 */
final class HealthCheck
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ERROR = 'error';

    /**
     * Privater Konstruktor: rein statische Service-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Führt alle Standard-Health-Checks aus und liefert ein Gesamtergebnis.
     *
     * @return array{
     *     overall: string,
     *     overall_badge: string,
     *     overall_label: string,
     *     timestamp: string,
     *     checks: array{
     *         database: array<string, mixed>,
     *         uploads: array<string, mixed>,
     *         mail: array<string, mixed>,
     *         sharepoint: array<string, mixed>
     *     }
     * }
     */
    public static function runAll(): array
    {
        $db = self::checkDatabase();
        $uploads = self::checkUploads();
        $mail = self::checkMailConfig(false);
        $sharepoint = self::checkSharePointConfig(false);

        $statuses = [$db['status'], $uploads['status'], $mail['status'], $sharepoint['status']];

        $overall = self::STATUS_OK;
        if (in_array(self::STATUS_ERROR, $statuses, true)) {
            $overall = self::STATUS_ERROR;
            $overallBadge = 'bg-danger';
            $overallLabel = 'Fehler vorhanden';
        } elseif (in_array(self::STATUS_WARNING, $statuses, true)) {
            $overall = self::STATUS_WARNING;
            $overallBadge = 'bg-warning text-dark';
            $overallLabel = 'Warnungen vorhanden';
        } else {
            $overallBadge = 'bg-success';
            $overallLabel = 'Alle Systeme betriebsbereit';
        }

        return [
            'overall'       => $overall,
            'overall_badge' => $overallBadge,
            'overall_label' => $overallLabel,
            'timestamp'     => date('d.m.Y H:i:s'),
            'checks'        => [
                'database'   => $db,
                'uploads'    => $uploads,
                'mail'       => $mail,
                'sharepoint' => $sharepoint,
            ],
        ];
    }

    /**
     * Prüft die Datenbankverbindung, Latenz, Zeichensatz und Kern-Tabellen.
     *
     * @return array{
     *     status: string,
     *     badge: string,
     *     label: string,
     *     message: string,
     *     latency_ms: float|null,
     *     details: array<string, mixed>
     * }
     */
    public static function checkDatabase(): array
    {
        $label = 'Datenbank-Verbindung';
        $startTime = microtime(true);

        try {
            if (!class_exists('DB')) {
                return [
                    'status'     => self::STATUS_ERROR,
                    'badge'      => 'bg-danger',
                    'label'      => $label,
                    'message'    => 'Datenbank-Klasse (DB.php) nicht gefunden.',
                    'latency_ms' => null,
                    'details'    => [],
                ];
            }

            // Test-Abfrage zur Ermittlung von Server-Version und Latenz
            $verRow = DB::fetchOne('SELECT VERSION() AS ver, DATABASE() AS db_name');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            $serverVersion = (string) ($verRow['ver'] ?? 'Unbekannt');
            $dbName = (string) ($verRow['db_name'] ?? 'Unbekannt');

            // Zeichensatz auslesen
            $csRow = DB::fetchOne('SELECT @@character_set_database AS cs, @@collation_database AS col');
            $charset = (string) ($csRow['cs'] ?? 'utf8mb4');
            $collation = (string) ($csRow['col'] ?? 'utf8mb4_unicode_ci');

            // Kern-Tabellen auf Existenz prüfen
            $expectedTables = ['users', 'settings', 'roles', 'migrations', 'secure_cases', 'secure_case_files'];
            $existingTables = [];
            $missingTables = [];

            foreach ($expectedTables as $t) {
                try {
                    $st = DB::query("SHOW TABLES LIKE '{$t}'");
                    if ($st->fetch() !== false) {
                        $existingTables[] = $t;
                    } else {
                        $missingTables[] = $t;
                    }
                } catch (\Throwable $e) {
                    $missingTables[] = $t;
                }
            }

            $details = [
                'Datenbank-Name'   => $dbName,
                'Server-Version'   => $serverVersion,
                'Zeichensatz'      => "{$charset} ({$collation})",
                'Antwortzeit'      => "{$latencyMs} ms",
                'Gefundene Tabellen' => implode(', ', $existingTables),
            ];

            if (!empty($missingTables)) {
                $details['Ausstehende Tabellen'] = implode(', ', $missingTables);
            }

            // Status bewerten: Falls Kern-Tabellen fehlen -> Warning
            if (in_array('users', $missingTables, true) || in_array('settings', $missingTables, true)) {
                return [
                    'status'     => self::STATUS_WARNING,
                    'badge'      => 'bg-warning text-dark',
                    'label'      => $label,
                    'message'    => 'Verbindung aktiv, jedoch fehlen grundlegende System-Tabellen (z. B. ' . implode(', ', $missingTables) . ').',
                    'latency_ms' => $latencyMs,
                    'details'    => $details,
                ];
            }

            return [
                'status'     => self::STATUS_OK,
                'badge'      => 'bg-success',
                'label'      => $label,
                'message'    => "Verbindung zur Datenbank '{$dbName}' erfolgreich hergestellt ({$latencyMs} ms).",
                'latency_ms' => $latencyMs,
                'details'    => $details,
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'status'     => self::STATUS_ERROR,
                'badge'      => 'bg-danger',
                'label'      => $label,
                'message'    => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage(),
                'latency_ms' => $latencyMs,
                'details'    => [
                    'Fehler' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Prüft die Upload- und Speicherverzeichnisse auf Existenz, Schreibbarkeit und Sicherheit.
     *
     * @return array{
     *     status: string,
     *     badge: string,
     *     label: string,
     *     message: string,
     *     details: array<string, mixed>,
     *     directories: array<int, array<string, mixed>>
     * }
     */
    public static function checkUploads(): array
    {
        $label = 'Upload- & Dateisystem';
        $rootDir = dirname(__DIR__);

        $secureBase = class_exists('SecurePortalConfig')
            ? SecurePortalConfig::getCaseBaseDir()
            : ($rootDir . '/uploads/secure/cases');

        $directoriesToCheck = [
            'uploads'              => [
                'path'     => $rootDir . '/uploads',
                'label'    => 'Basis-Uploads (/uploads)',
                'required' => true,
            ],
            'uploads_homepage'     => [
                'path'     => $rootDir . '/uploads/homepage',
                'label'    => 'Homepage & Medien (/uploads/homepage)',
                'required' => false,
            ],
            'uploads_secure'       => [
                'path'     => $rootDir . '/uploads/secure',
                'label'    => 'Sicherungsportal Basis (/uploads/secure)',
                'required' => true,
            ],
            'uploads_secure_cases' => [
                'path'     => $secureBase,
                'label'    => 'Sicherungsfälle & Beweismittel (' . str_replace($rootDir, '', $secureBase) . ')',
                'required' => true,
            ],
            'storage'              => [
                'path'     => $rootDir . '/storage',
                'label'    => 'Interner Speicher (/storage)',
                'required' => false,
            ],
        ];

        $dirResults = [];
        $hasError = false;
        $hasWarning = false;

        foreach ($directoriesToCheck as $key => $info) {
            $p = $info['path'];
            $dirLabel = $info['label'];
            $isRequired = $info['required'];

            $exists = file_exists($p) && is_dir($p);
            $createdNow = false;

            // Falls nicht existiert, Versuch der automatischen Erstellung
            if (!$exists) {
                $createdNow = @mkdir($p, 0755, true);
                $exists = $createdNow;
            }

            $isWritable = $exists && is_writable($p);
            $canWriteFile = false;

            // Echtes Schreib- und Lösch-Audit
            if ($exists && $isWritable) {
                $testFile = $p . '/.health_test_' . uniqid('', true) . '.tmp';
                if (@file_put_contents($testFile, 'health_check_' . time()) !== false) {
                    $canWriteFile = true;
                    @unlink($testFile);
                }
            }

            $perms = $exists ? substr(sprintf('%o', fileperms($p)), -4) : 'N/A';

            // Sicherheitscheck: .htaccess vorhanden?
            $htaccessExists = file_exists($p . '/.htaccess');
            $indexHtmlExists = file_exists($p . '/index.html');

            $dirStatus = self::STATUS_OK;
            if (!$exists) {
                $dirStatus = $isRequired ? self::STATUS_ERROR : self::STATUS_WARNING;
            } elseif (!$isWritable || !$canWriteFile) {
                $dirStatus = $isRequired ? self::STATUS_ERROR : self::STATUS_WARNING;
            }

            if ($dirStatus === self::STATUS_ERROR) {
                $hasError = true;
            } elseif ($dirStatus === self::STATUS_WARNING) {
                $hasWarning = true;
            }

            $dirResults[] = [
                'key'             => $key,
                'label'           => $dirLabel,
                'path'            => $p,
                'exists'          => $exists,
                'is_writable'     => $isWritable && $canWriteFile,
                'permissions'     => $perms,
                'status'          => $dirStatus,
                'htaccess'        => $htaccessExists,
                'index_html'      => $indexHtmlExists,
            ];
        }

        // Freier Speicherplatz ermitteln
        $freeBytes = @disk_free_space($rootDir);
        $totalBytes = @disk_total_space($rootDir);
        $freeSpaceFormatted = 'Nicht ermittelbar';
        if ($freeBytes !== false && $freeBytes !== null) {
            $freeGb = round($freeBytes / 1024 / 1024 / 1024, 2);
            $freeSpaceFormatted = "{$freeGb} GB verfügbar";
            if ($totalBytes !== false && $totalBytes > 0) {
                $totalGb = round($totalBytes / 1024 / 1024 / 1024, 2);
                $freeSpaceFormatted .= " (von {$totalGb} GB)";
            }
        }

        $details = [
            'Geprüfte Verzeichnisse' => count($dirResults) . ' Verzeichnisse',
            'Freier Speicherplatz'   => $freeSpaceFormatted,
            'Upload Max Filesize'    => ini_get('upload_max_filesize') ?: 'Standard',
            'Post Max Size'          => ini_get('post_max_size') ?: 'Standard',
        ];

        if ($hasError) {
            return [
                'status'      => self::STATUS_ERROR,
                'badge'       => 'bg-danger',
                'label'       => $label,
                'message'     => 'Ein oder mehrere erforderliche Upload-Verzeichnisse existieren nicht oder sind nicht beschreibbar.',
                'details'     => $details,
                'directories' => $dirResults,
            ];
        }

        if ($hasWarning) {
            return [
                'status'      => self::STATUS_WARNING,
                'badge'       => 'bg-warning text-dark',
                'label'       => $label,
                'message'     => 'Optionale Upload-Pfade fehlen oder sind eingeschränkt beschreibbar.',
                'details'     => $details,
                'directories' => $dirResults,
            ];
        }

        return [
            'status'      => self::STATUS_OK,
            'badge'       => 'bg-success',
            'label'       => $label,
            'message'     => 'Alle Upload- und Fallverzeichnisse existieren und sind uneingeschränkt beschreibbar.',
            'details'     => $details,
            'directories' => $dirResults,
        ];
    }

    /**
     * Prüft die E-Mail-Konfiguration und führt auf Wunsch einen echten Test-Versand aus.
     *
     * @param bool $testSend Ob eine echte Test-Mail verschickt werden soll
     * @param string|null $testEmail Zieladresse für den Testversand
     * @return array{
     *     status: string,
     *     badge: string,
     *     label: string,
     *     message: string,
     *     details: array<string, mixed>,
     *     test_result: array<string, mixed>|null
     * }
     */
    public static function checkMailConfig(bool $testSend = false, ?string $testEmail = null): array
    {
        $label = 'E-Mail-Konfiguration';

        $fromEmail = 'noreply@safecase.ch';
        $fromName  = 'CMS System';
        $notificationEmail = '';

        if (class_exists('Settings')) {
            $fromEmail = (string) (Settings::get('system_email_from', 'noreply@safecase.ch') ?: 'noreply@safecase.ch');
            $fromName  = (string) (Settings::get('system_email_from_name', 'CMS System') ?: 'CMS System');
            $notificationEmail = (string) (Settings::get('secure_notification_email', '') ?: '');
        }

        $mailFunctionExists = function_exists('mail');
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        $mailDisabled = in_array('mail', $disabledFunctions, true);

        $sendmailPath = ini_get('sendmail_path');

        $details = [
            'Absender-Adresse'       => $fromEmail,
            'Absender-Name'          => $fromName,
            'Sammel-E-Mail (Portal)' => $notificationEmail !== '' ? $notificationEmail : 'Nicht konfiguriert (optional)',
            'PHP mail() Funktion'    => ($mailFunctionExists && !$mailDisabled) ? 'Aktiviert & verfügbar' : 'Deaktiviert / blockiert',
            'sendmail_path'          => $sendmailPath ?: 'Standard',
        ];

        // 1. Grundvalidierung der Absender-Adresse
        $isFromValid = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) !== false;
        if (!$isFromValid) {
            return [
                'status'      => self::STATUS_ERROR,
                'badge'       => 'bg-danger',
                'label'       => $label,
                'message'     => "Die hinterlegte System-Absenderadresse '{$fromEmail}' ist ungültig.",
                'details'     => $details,
                'test_result' => null,
            ];
        }

        if (!$mailFunctionExists || $mailDisabled) {
            return [
                'status'      => self::STATUS_ERROR,
                'badge'       => 'bg-danger',
                'label'       => $label,
                'message'     => 'Die PHP-Funktion mail() ist auf diesem Server deaktiviert oder gesperrt.',
                'details'     => $details,
                'test_result' => null,
            ];
        }

        // 2. Test-Versand ausführen (falls angefordert)
        $testResult = null;
        if ($testSend) {
            $target = trim((string) ($testEmail ?: $fromEmail));

            if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                return [
                    'status'      => self::STATUS_ERROR,
                    'badge'       => 'bg-danger',
                    'label'       => $label,
                    'message'     => "Die angegebene Test-Zieladresse '{$target}' ist ungültig.",
                    'details'     => $details,
                    'test_result' => [
                        'success' => false,
                        'target'  => $target,
                        'error'   => 'Ungültiges E-Mail-Format',
                    ],
                ];
            }

            $subject = 'Test-Nachricht: CMS Systemstatus & Health-Check';
            $now = date('d.m.Y H:i:s');
            $body = "Hallo,\n\n"
                  . "Dies ist eine automatisierte Test-E-Mail aus dem CMS Health-Check.\n\n"
                  . "--------------------------------------------------\n"
                  . "Zeitstempel: {$now}\n"
                  . "Absender:    {$fromName} <{$fromEmail}>\n"
                  . "Empfänger:   {$target}\n"
                  . "Server:      " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n"
                  . "--------------------------------------------------\n\n"
                  . "Wenn Sie diese E-Mail erhalten, ist das Mailsystem des CMS betriebsbereit.\n";

            try {
                $sent = class_exists('Mailer')
                    ? Mailer::send($target, $subject, $body, false)
                    : @mail($target, $subject, $body);

                if ($sent) {
                    $testResult = [
                        'success'   => true,
                        'target'    => $target,
                        'timestamp' => $now,
                    ];
                    return [
                        'status'      => self::STATUS_OK,
                        'badge'       => 'bg-success',
                        'label'       => $label,
                        'message'     => "Test-E-Mail erfolgreich an '{$target}' übergeben.",
                        'details'     => $details,
                        'test_result' => $testResult,
                    ];
                }

                $testResult = [
                    'success' => false,
                    'target'  => $target,
                    'error'   => 'Die mail()-Funktion hat false zurückgegeben (Prüfen Sie sendmail/MTA).',
                ];
                return [
                    'status'      => self::STATUS_ERROR,
                    'badge'       => 'bg-danger',
                    'label'       => $label,
                    'message'     => "Versand der Test-E-Mail an '{$target}' ist fehlgeschlagen.",
                    'details'     => $details,
                    'test_result' => $testResult,
                ];
            } catch (\Throwable $e) {
                $testResult = [
                    'success' => false,
                    'target'  => $target,
                    'error'   => $e->getMessage(),
                ];
                return [
                    'status'      => self::STATUS_ERROR,
                    'badge'       => 'bg-danger',
                    'label'       => $label,
                    'message'     => 'Fehler beim Versenden der Test-Mail: ' . $e->getMessage(),
                    'details'     => $details,
                    'test_result' => $testResult,
                ];
            }
        }

        // Kein Test-Versand angefordert: Plausibilitäts-Check
        if ($notificationEmail === '') {
            return [
                'status'      => self::STATUS_WARNING,
                'badge'       => 'bg-warning text-dark',
                'label'       => $label,
                'message'     => 'Mail-Konfiguration ist gültig. Es ist jedoch noch keine Sammel-E-Mail für das Sicherungsportal hinterlegt.',
                'details'     => $details,
                'test_result' => null,
            ];
        }

        return [
            'status'      => self::STATUS_OK,
            'badge'       => 'bg-success',
            'label'       => $label,
            'message'     => 'E-Mail-Konfiguration plausibel und vollständig eingerichtet.',
            'details'     => $details,
            'test_result' => null,
        ];
    }

    /**
     * Prüft die Microsoft Graph / SharePoint Konfiguration und führt optional einen OAuth-Token-Test durch.
     *
     * @param bool $testToken Ob ein OAuth-Token live bei Microsoft angefragt werden soll
     * @return array{
     *     status: string,
     *     badge: string,
     *     label: string,
     *     message: string,
     *     details: array<string, mixed>,
     *     token_result: array<string, mixed>|null
     * }
     */
    public static function checkSharePointConfig(bool $testToken = false): array
    {
        $label = 'SharePoint & Microsoft Graph';

        if (!class_exists('SharePointConfig')) {
            return [
                'status'       => self::STATUS_ERROR,
                'badge'        => 'bg-danger',
                'label'        => $label,
                'message'      => 'SharePointConfig-Klasse nicht gefunden.',
                'details'      => [],
                'token_result' => null,
            ];
        }

        $tenantId     = SharePointConfig::getTenantId();
        $clientId     = SharePointConfig::getClientId();
        $clientSecret = SharePointConfig::getClientSecret();
        $driveId      = SharePointConfig::getDriveId();
        $siteId       = SharePointConfig::getSiteId();
        $baseFolder   = SharePointConfig::getBaseFolder();
        $isConfigured = SharePointConfig::isConfigured();

        $details = [
            'Tenant-ID'     => $tenantId !== '' ? (substr($tenantId, 0, 8) . '...' . substr($tenantId, -4)) : 'Nicht hinterlegt',
            'Client-ID'     => $clientId !== '' ? (substr($clientId, 0, 8) . '...' . substr($clientId, -4)) : 'Nicht hinterlegt',
            'Client-Secret' => $clientSecret !== '' ? 'Hinterlegt (' . strlen($clientSecret) . ' Zeichen, maskiert)' : 'Nicht hinterlegt',
            'Drive-ID'      => $driveId !== '' ? (substr($driveId, 0, 8) . '...' . substr($driveId, -4)) : 'Nicht hinterlegt',
            'Site-ID'       => $siteId !== '' ? (substr($siteId, 0, 8) . '...') : 'Nicht hinterlegt',
            'Basisordner'   => $baseFolder !== '' ? $baseFolder : 'Vorgaenge (Standard)',
        ];

        // 1. Nicht konfiguriert -> Warning (kein Hard Error, da Fallback auf lokales Filesystem aktiv bleibt)
        if (!$isConfigured) {
            $missing = [];
            if ($tenantId === '') $missing[] = 'Tenant-ID';
            if ($clientId === '') $missing[] = 'Client-ID';
            if ($clientSecret === '') $missing[] = 'Client-Secret';
            if ($driveId === '' && $siteId === '') $missing[] = 'Drive-ID / Site-ID';

            return [
                'status'       => self::STATUS_WARNING,
                'badge'        => 'bg-warning text-dark',
                'label'        => $label,
                'message'      => 'SharePoint ist nicht vollständig konfiguriert (' . implode(', ', $missing) . ' fehlt). Vorgänge werden lokal im Dateisystem archiviert.',
                'details'      => $details,
                'token_result' => null,
            ];
        }

        // 2. Token-Test durchführen (falls angefordert)
        $tokenResult = null;
        if ($testToken) {
            if (!class_exists('SharePointService')) {
                return [
                    'status'       => self::STATUS_ERROR,
                    'badge'        => 'bg-danger',
                    'label'        => $label,
                    'message'      => 'SharePointService-Klasse nicht verfügbar zur Token-Anforderung.',
                    'details'      => $details,
                    'token_result' => null,
                ];
            }

            $startTime = microtime(true);
            try {
                // Token mit erzwungenem Refresh anfordern
                $token = SharePointService::getAccessToken(true);
                $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

                $tokenLen = strlen($token);
                $tokenResult = [
                    'success'    => true,
                    'latency_ms' => $latencyMs,
                    'token_len'  => $tokenLen,
                    'timestamp'  => date('d.m.Y H:i:s'),
                ];

                $details['OAuth-Token'] = "Erfolgreich abgerufen ({$latencyMs} ms, {$tokenLen} Zeichen)";

                return [
                    'status'       => self::STATUS_OK,
                    'badge'        => 'bg-success',
                    'label'        => $label,
                    'message'      => "Microsoft Graph Authentifizierung erfolgreich! Access-Token erhalten ({$latencyMs} ms).",
                    'details'      => $details,
                    'token_result' => $tokenResult,
                ];
            } catch (\Throwable $e) {
                $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
                $cleanMsg = $e->getMessage();
                // Client-Secret niemals im Fehlertext anzeigen
                if ($clientSecret !== '' && strlen($clientSecret) >= 4) {
                    $cleanMsg = str_replace($clientSecret, '***REDACTED***', $cleanMsg);
                }

                $tokenResult = [
                    'success'    => false,
                    'latency_ms' => $latencyMs,
                    'error'      => $cleanMsg,
                ];

                return [
                    'status'       => self::STATUS_ERROR,
                    'badge'        => 'bg-danger',
                    'label'        => $label,
                    'message'      => 'Microsoft Graph Token-Test fehlgeschlagen: ' . $cleanMsg,
                    'details'      => $details,
                    'token_result' => $tokenResult,
                ];
            }
        }

        // Konfiguration ist komplett, aber noch kein manueller Token-Test erfolgt
        return [
            'status'       => self::STATUS_OK,
            'badge'        => 'bg-success',
            'label'        => $label,
            'message'      => 'Konfiguration ist vollständig hinterlegt (Tenant-ID, Client-ID, Secret, Drive). Token-Test kann bei Bedarf gestartet werden.',
            'details'      => $details,
            'token_result' => null,
        ];
    }
}
