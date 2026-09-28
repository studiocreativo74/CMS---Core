<?php

declare(strict_types=1);

/**
 * SharePoint & Microsoft Graph Integration Service
 *
 * Verantwortlich für die Anbindung an Microsoft SharePoint via Microsoft Graph API
 * (Client-Credentials-Flow / OAuth 2.0).
 *
 * Phase 1 Aufgaben:
 * - Automatische Erstellung eines Vorgangsordners pro Fall (z. B. 'Vorgaenge/POL-2026-123456')
 * - Manipulationssicheres Hochladen und Archivieren der Editionsverfügung (PDF)
 * - Persistierung der SharePoint-Referenzen (Item-ID, Web-URL, Ordner-URL) im Case-Record
 * - Robuste Fehlerbehandlung: Bei Nicht-Erreichbarkeit bleibt der Vorgang lokal vollständig
 *   erhalten und der Fehler wird intern auditiert, ohne sensible Secrets preiszugeben.
 */
final class SharePointService
{
    /** @var string|null Gecachter Access Token */
    private static ?string $cachedToken = null;

    /** @var int Ablaufzeitpunkt des Tokens (Unix-Timestamp) */
    private static int $tokenExpiresAt = 0;

    /**
     * Hauptmethode zur Archivierung der Editionsverfügung eines Sicherungsvorgangs.
     *
     * @param array<string, mixed> $case Fall-Daten (aus DB oder createCase)
     * @param array<string, mixed> $extra Zusätzliche Formulardaten (z. B. warrant_file_path falls noch nicht in $case)
     * @return array{success: bool, item_id?: string, web_url?: string, folder_url?: string, error?: string}|null
     */
    public static function archiveCaseWarrant(array $case, array $extra = []): ?array
    {
        // 1. Prüfen, ob SharePoint konfiguriert ist
        if (!SharePointConfig::isConfigured()) {
            return null;
        }

        $caseId = (int) ($case['id'] ?? 0);
        $caseNumber = trim((string) ($case['case_number'] ?? ($extra['case_number'] ?? '')));

        if ($caseId <= 0 || $caseNumber === '') {
            error_log('SharePointService::archiveCaseWarrant: Ungültige Case-Daten (ID oder Case-Number fehlt).');
            return ['success' => false, 'error' => 'Ungültige Vorgangsdaten.'];
        }

        // 2. Lokale PDF-Datei ermitteln
        $relPath = (string) ($case['warrant_file_path'] ?? ($extra['warrant_file_path'] ?? ''));
        $localFilePath = class_exists('SecurePortalConfig') 
            ? SecurePortalConfig::resolveLocalFilePath($relPath) 
            : self::resolveLocalFilePath($relPath);

        if ($localFilePath === null || !file_exists($localFilePath) || !is_readable($localFilePath)) {
            $msg = 'Lokale Editionsverfügung nicht im Dateisystem auffindbar (' . htmlspecialchars($relPath, ENT_QUOTES, 'UTF-8') . ').';
            error_log('SharePointService::archiveCaseWarrant: ' . $msg);
            self::logInternalCaseError($caseId, $msg);
            return ['success' => false, 'error' => $msg];
        }

        try {
            // 3. Authentifizierungstoken abrufen
            $accessToken = self::getAccessToken();

            // 4. Zielordner und Dateiname bestimmen
            $caseCombined = array_merge($case, $extra);
            $baseFolder = SharePointConfig::getBaseFolder();

            // Ordner- und Dateinamen strikt synchron zur lokalen Fallstruktur (über Naming / SecurePortalConfig)
            if (class_exists('Naming')) {
                $safeCaseFolder = Naming::buildCaseFolder($caseNumber, $caseCombined);
                $remoteFileName = Naming::buildWarrantFileName($caseNumber, $caseCombined);
            } elseif (class_exists('SecurePortalConfig')) {
                $safeCaseFolder = SecurePortalConfig::getCaseDir($caseNumber, false, $caseCombined);
                $safeCaseFolder = basename($safeCaseFolder);
                $remoteFileName = SecurePortalConfig::buildWarrantFileName($caseNumber, 1, $caseCombined);
            } else {
                $safeCaseFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $caseNumber) ?: 'CASE';
                $remoteFileName = $safeCaseFolder . '_Editionsverfuegung_v1.pdf';
            }

            $targetFolderPath = $baseFolder !== '' ? "{$baseFolder}/{$safeCaseFolder}" : $safeCaseFolder;

            // 5. Datei zu SharePoint übertragen
            $driveBase = self::getDriveBaseUrl();
            $driveItem = self::uploadFileToDrive($accessToken, $driveBase, $targetFolderPath, $remoteFileName, $localFilePath);

            $itemId   = (string) ($driveItem['id'] ?? '');
            $fileUrl  = (string) ($driveItem['webUrl'] ?? '');

            // 6. Ordner-URL ermitteln (für direkten Admin-Sprung in SharePoint)
            $folderUrl = self::getFolderWebUrl($accessToken, $driveBase, $targetFolderPath, $fileUrl);

            $syncData = [
                'item_id'    => $itemId,
                'web_url'    => $fileUrl,
                'folder_url' => $folderUrl,
                'synced_at'  => date('Y-m-d H:i:s'),
            ];

            // 7. Referenz im `secure_cases`-Eintrag speichern
            if (class_exists('SecurePortalRepository')) {
                SecurePortalRepository::updateSharePointReference($caseId, $syncData);

                // Audit-Log für interne Mitarbeiter eintragen
                SecurePortalRepository::addCaseLog(
                    $caseId,
                    'sharepoint_archived',
                    sprintf(
                        'Editionsverfügung erfolgreich in SharePoint archiviert (Ordner: %s, Item-ID: %s).',
                        $targetFolderPath,
                        $itemId
                    ),
                    'System / SharePoint',
                    true // nur intern sichtbar
                );
            }

            return array_merge(['success' => true], $syncData);
        } catch (\Throwable $e) {
            // Robuste Fehlerbehandlung: Der Antragsteller erfährt keine Details, interner Log wird befüllt
            $safeError = self::sanitizeErrorMessage($e->getMessage());
            error_log(sprintf('SharePoint-Archivierung für Fall %s fehlgeschlagen: %s', $caseNumber, $safeError));

            self::logInternalCaseError($caseId, 'SharePoint-Archivierung fehlgeschlagen: ' . $safeError);

            return [
                'success' => false,
                'error'   => $safeError,
            ];
        }
    }

    /**
     * Fordert einen Microsoft Graph OAuth 2.0 Access Token via Client-Credentials an.
     *
     * @throws RuntimeException Bei Fehlschlag
     */
    public static function getAccessToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && self::$cachedToken !== null && time() < (self::$tokenExpiresAt - 60)) {
            return self::$cachedToken;
        }

        $tenantId     = SharePointConfig::getTenantId();
        $clientId     = SharePointConfig::getClientId();
        $clientSecret = SharePointConfig::getClientSecret();

        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('SharePoint-Konfiguration unvollständig (Tenant-ID, Client-ID oder Secret fehlt).');
        }

        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
        $postData = http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]);

        $response = self::makeHttpRequest(
            'POST',
            $tokenUrl,
            ['Content-Type: application/x-www-form-urlencoded'],
            $postData,
            15
        );

        if ($response['status'] !== 200) {
            $errDesc = (string) ($response['json']['error_description'] ?? ($response['json']['error'] ?? 'HTTP ' . $response['status']));
            throw new \RuntimeException('OAuth-Token-Anforderung fehlgeschlagen: ' . $errDesc);
        }

        $token = (string) ($response['json']['access_token'] ?? '');
        $expiresIn = (int) ($response['json']['expires_in'] ?? 3600);

        if ($token === '') {
            throw new \RuntimeException('Microsoft Graph hat keinen gültigen access_token zurückgegeben.');
        }

        self::$cachedToken = $token;
        self::$tokenExpiresAt = time() + $expiresIn;

        return $token;
    }

    /**
     * Ermittelt die Basis-URL des Ziel-Drives in Microsoft Graph.
     *
     * @throws RuntimeException
     */
    public static function getDriveBaseUrl(): string
    {
        $driveId = SharePointConfig::getDriveId();
        $siteId  = SharePointConfig::getSiteId();

        if ($driveId !== '') {
            return 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode($driveId);
        }

        if ($siteId !== '') {
            return 'https://graph.microsoft.com/v1.0/sites/' . rawurlencode($siteId) . '/drive';
        }

        throw new \RuntimeException('Weder sp_drive_id noch sp_site_id konfiguriert.');
    }

    /**
     * Überträgt eine Datei in den Zielordner auf SharePoint.
     * Bei Dateien <= 4MB per direktem PUT, bei größeren Dateien via Upload-Session.
     *
     * @return array<string, mixed> Graph DriveItem Metadaten
     * @throws RuntimeException
     */
    private static function uploadFileToDrive(
        string $token,
        string $driveBase,
        string $folderPath,
        string $remoteFileName,
        string $localFilePath
    ): array {
        $fileSize = (int) filesize($localFilePath);
        $encodedPath = self::encodeItemPath($folderPath . '/' . $remoteFileName);

        // A) Für Dateien bis 4 MB: Direkter PUT-Upload
        if ($fileSize <= 4 * 1024 * 1024) {
            $content = file_get_contents($localFilePath);
            if ($content === false) {
                throw new \RuntimeException('Fehler beim Einlesen der lokalen PDF-Datei.');
            }

            $uploadUrl = "{$driveBase}/root:/{$encodedPath}:/content";
            $res = self::makeHttpRequest(
                'PUT',
                $uploadUrl,
                [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/pdf',
                ],
                $content,
                30
            );

            if ($res['status'] !== 200 && $res['status'] !== 201) {
                $err = (string) ($res['json']['error']['message'] ?? 'HTTP ' . $res['status']);
                throw new \RuntimeException('SharePoint Datei-Upload fehlgeschlagen: ' . $err);
            }

            return $res['json'];
        }

        // B) Für Dateien > 4 MB: UploadSession (Chunked Upload)
        return self::uploadLargeFileViaSession($token, $driveBase, $encodedPath, $localFilePath, $fileSize);
    }

    /**
     * Führt einen Chunked Upload über eine Microsoft Graph UploadSession durch (für PDFs bis 30 MB).
     *
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    private static function uploadLargeFileViaSession(
        string $token,
        string $driveBase,
        string $encodedPath,
        string $localFilePath,
        int $fileSize
    ): array {
        $createSessionUrl = "{$driveBase}/root:/{$encodedPath}:/createUploadSession";
        $sessionPayload = json_encode([
            'item' => [
                '@microsoft.graph.conflictBehavior' => 'replace',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $sessionRes = self::makeHttpRequest(
            'POST',
            $createSessionUrl,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            $sessionPayload,
            15
        );

        if ($sessionRes['status'] !== 200 && $sessionRes['status'] !== 201) {
            $err = (string) ($sessionRes['json']['error']['message'] ?? 'HTTP ' . $sessionRes['status']);
            throw new \RuntimeException('Erstellung der SharePoint Upload-Session fehlgeschlagen: ' . $err);
        }

        $uploadUrl = (string) ($sessionRes['json']['uploadUrl'] ?? '');
        if ($uploadUrl === '') {
            throw new \RuntimeException('Upload-Session lieferte keine uploadUrl.');
        }

        // Chunks à 5 MB (Vielfaches von 320 KiB wie von Graph vorgeschrieben: 320 * 1024 * 16 = 5,242,880)
        $chunkSize = 5 * 1024 * 1024;
        $handle = fopen($localFilePath, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Konnte Datei für Chunk-Upload nicht öffnen.');
        }

        try {
            $offset = 0;
            $finalItem = null;

            while (!feof($handle) && $offset < $fileSize) {
                $chunkData = fread($handle, $chunkSize);
                if ($chunkData === false) {
                    throw new \RuntimeException('Fehler beim Lesen eines Datenblocks.');
                }

                $chunkLen = strlen($chunkData);
                $end = $offset + $chunkLen - 1;

                $chunkHeaders = [
                    'Content-Length: ' . $chunkLen,
                    "Content-Range: bytes {$offset}-{$end}/{$fileSize}",
                ];

                $chunkRes = self::makeHttpRequest('PUT', $uploadUrl, $chunkHeaders, $chunkData, 45);

                if ($chunkRes['status'] === 200 || $chunkRes['status'] === 201) {
                    $finalItem = $chunkRes['json'];
                    break;
                }

                if ($chunkRes['status'] !== 202) {
                    $err = (string) ($chunkRes['json']['error']['message'] ?? 'HTTP ' . $chunkRes['status']);
                    throw new \RuntimeException('Chunk-Upload fehlgeschlagen bei Offset ' . $offset . ': ' . $err);
                }

                $offset += $chunkLen;
            }

            if (!is_array($finalItem)) {
                throw new \RuntimeException('UploadSession wurde nicht mit Status 200/201 beendet.');
            }

            return $finalItem;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Ermittelt die Web-URL des Vorgangs-Ordners auf SharePoint.
     */
    private static function getFolderWebUrl(string $token, string $driveBase, string $folderPath, string $fallbackFileUrl): string
    {
        try {
            $encodedPath = self::encodeItemPath($folderPath);
            $folderRes = self::makeHttpRequest(
                'GET',
                "{$driveBase}/root:/{$encodedPath}",
                ['Authorization: Bearer ' . $token],
                null,
                10
            );

            if ($folderRes['status'] === 200 && !empty($folderRes['json']['webUrl'])) {
                return (string) $folderRes['json']['webUrl'];
            }
        } catch (\Throwable $e) {
            error_log('SharePointService::getFolderWebUrl Hinweis: ' . $e->getMessage());
        }

        // Falls Ordner-URL nicht direkt geliefert wird: Ableitung aus File-URL
        if ($fallbackFileUrl !== '') {
            $lastSlash = strrpos($fallbackFileUrl, '/');
            if ($lastSlash !== false) {
                return substr($fallbackFileUrl, 0, $lastSlash);
            }
        }

        return $fallbackFileUrl;
    }

    /**
     * Kodiert Pfadkomponenten sicher für Microsoft Graph URL-Addressing.
     */
    private static function encodeItemPath(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $encoded = array_map('rawurlencode', $segments);
        return implode('/', $encoded);
    }

    /**
     * Löst den lokalen Pfad der Editionsverfügung robust auf.
     */
    private static function resolveLocalFilePath(string $relPath): ?string
    {
        $clean = trim($relPath);
        if ($clean === '') {
            return null;
        }

        // Falls absoluter Pfad übergeben wurde
        if (file_exists($clean)) {
            return $clean;
        }

        // Ausgehend vom Workspace-Root
        $baseDir = dirname(__DIR__, 3);
        $candidate1 = $baseDir . '/' . ltrim($clean, '/');
        if (file_exists($candidate1)) {
            return $candidate1;
        }

        $baseDir2 = dirname(__DIR__, 2);
        $candidate2 = $baseDir2 . '/' . ltrim($clean, '/');
        if (file_exists($candidate2)) {
            return $candidate2;
        }

        return null;
    }

    /**
     * Schreibt einen internen Fehlervermerk in das Audit-Log des Falls.
     */
    private static function logInternalCaseError(int $caseId, string $message): void
    {
        if ($caseId <= 0 || !class_exists('SecurePortalRepository')) {
            return;
        }

        try {
            SecurePortalRepository::addCaseLog(
                $caseId,
                'sharepoint_failed',
                $message,
                'System / SharePoint',
                true // Nur intern sichtbar!
            );
        } catch (\Throwable $e) {
            error_log('SharePointService::logInternalCaseError Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Entfernt eventuelle Secrets aus Fehlermeldungen vor dem internen Logging.
     */
    private static function sanitizeErrorMessage(string $message): string
    {
        $secret = SharePointConfig::getClientSecret();
        if ($secret !== '' && strlen($secret) >= 4) {
            $message = str_replace($secret, '***REDACTED***', $message);
        }

        // Bearer-Tokens maskieren
        return preg_replace('/Bearer\s+[A-Za-z0-9\-_.]+/i', 'Bearer ***REDACTED***', $message) ?? $message;
    }

    /**
     * Universeller HTTP-Client für Microsoft Graph (cURL bevorzugt, fallback auf stream_context).
     *
     * @param array<int, string> $headers
     * @param string|null $body
     * @return array{status: int, headers: array<string, string>, body: string, json: array<string, mixed>}
     */
    public static function makeHttpRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
        int $timeout = 15
    ): array {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $effectiveHeaders = $headers;
            $effectiveHeaders[] = 'User-Agent: Sicherungsportal-CMS/2.0';

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $effectiveHeaders);

            $responseBody = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($responseBody === false) {
                throw new \RuntimeException('cURL-Netzwerkfehler: ' . $curlError);
            }

            $json = [];
            $decoded = json_decode((string) $responseBody, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }

            return [
                'status'  => $statusCode,
                'headers' => [],
                'body'    => (string) $responseBody,
                'json'    => $json,
            ];
        }

        // Fallback ohne cURL via stream_context_create
        $headerLines = $headers;
        $headerLines[] = 'User-Agent: Sicherungsportal-CMS/2.0';

        $opts = [
            'http' => [
                'method'        => strtoupper($method),
                'header'        => implode("\r\n", $headerLines),
                'content'       => $body ?? '',
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ];

        $context = stream_context_create($opts);
        $fp = @fopen($url, 'rb', false, $context);
        if (!$fp) {
            throw new \RuntimeException('Verbindung zu Microsoft Graph fehlgeschlagen (fopen failed).');
        }

        $meta = stream_get_meta_data($fp);
        $responseBody = stream_get_contents($fp);
        fclose($fp);

        $statusLine = $meta['wrapper_data'][0] ?? 'HTTP/1.1 500';
        preg_match('#HTTP/\S+\s+(\d+)#', $statusLine, $m);
        $statusCode = isset($m[1]) ? (int) $m[1] : 500;

        $json = [];
        $decoded = json_decode((string) $responseBody, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }

        return [
            'status'  => $statusCode,
            'headers' => [],
            'body'    => (string) $responseBody,
            'json'    => $json,
        ];
    }
}
