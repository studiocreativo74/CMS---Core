<?php

declare(strict_types=1);

/**
 * SharePoint Configuration Helper
 *
 * Verwaltet SharePoint / Microsoft Graph Konfigurationsparameter.
 * Liest Werte bevorzugt aus Settings::get(), fallback auf config/config.php,
 * getenv() oder $_ENV.
 *
 * Wichtig: Alle Secrets (Client-ID, Secret, Tenant etc.) bleiben stets
 * außerhalb des Git-Repositories und werden niemals fest im Code verdrahtet.
 */
final class SharePointConfig
{
    /** @var array<string, mixed>|null */
    private static ?array $fileConfig = null;

    /**
     * Liest einen Konfigurationswert mit Priorität:
     * 1. Settings::get('sp_' . $key) / Settings::get($key) (Datenbank)
     * 2. config/config.php (Dateisystem)
     * 3. Umgebungsvariablen (SP_TENANT_ID etc.)
     */
    public static function get(string $key, string $default = ''): string
    {
        $cleanKey = trim($key);
        if ($cleanKey === '') {
            return $default;
        }

        // 1. Settings::get() aus Tabelle `settings` (falls vorhanden)
        if (class_exists('Settings')) {
            try {
                $valPrefixed = Settings::get('sp_' . $cleanKey);
                if ($valPrefixed !== null && trim($valPrefixed) !== '') {
                    return trim($valPrefixed);
                }
                $valDirect = Settings::get($cleanKey);
                if ($valDirect !== null && trim($valDirect) !== '') {
                    return trim($valDirect);
                }
            } catch (\Throwable $e) {
                error_log('SharePointConfig::get Settings-Fehler: ' . $e->getMessage());
            }
        }

        // 2. config/config.php
        $fileCfg = self::loadFileConfig();
        if (isset($fileCfg['sp_' . $cleanKey]) && trim((string) $fileCfg['sp_' . $cleanKey]) !== '') {
            return trim((string) $fileCfg['sp_' . $cleanKey]);
        }
        if (isset($fileCfg[$cleanKey]) && trim((string) $fileCfg[$cleanKey]) !== '') {
            return trim((string) $fileCfg[$cleanKey]);
        }
        if (isset($fileCfg['sharepoint'][$cleanKey]) && trim((string) $fileCfg['sharepoint'][$cleanKey]) !== '') {
            return trim((string) $fileCfg['sharepoint'][$cleanKey]);
        }

        // 3. Umgebungsvariablen (z. B. SP_TENANT_ID, SP_CLIENT_ID)
        $envPrefixed = strtoupper('SP_' . $cleanKey);
        $envVal = getenv($envPrefixed);
        if ($envVal !== false && trim($envVal) !== '') {
            return trim($envVal);
        }
        if (!empty($_ENV[$envPrefixed])) {
            return trim((string) $_ENV[$envPrefixed]);
        }
        if (!empty($_SERVER[$envPrefixed])) {
            return trim((string) $_SERVER[$envPrefixed]);
        }

        // Fallback: Direkte Env-Variable (z. B. TENANT_ID)
        $envDirect = strtoupper($cleanKey);
        $envDirectVal = getenv($envDirect);
        if ($envDirectVal !== false && trim($envDirectVal) !== '') {
            return trim($envDirectVal);
        }

        return $default;
    }

    /**
     * Microsoft Entra ID (Azure AD) Tenant-ID (Directory ID / Mandanten-ID)
     */
    public static function getTenantId(): string
    {
        return self::get('tenant_id');
    }

    /**
     * App-Registrierung Client-ID (Application ID)
     */
    public static function getClientId(): string
    {
        return self::get('client_id');
    }

    /**
     * App-Registrierung Client-Secret (Wert des geheimen Clientschlüssels)
     */
    public static function getClientSecret(): string
    {
        return self::get('client_secret');
    }

    /**
     * SharePoint Site-ID (z. B. tenant.sharepoint.com,site-guid,web-guid)
     */
    public static function getSiteId(): string
    {
        return self::get('site_id');
    }

    /**
     * SharePoint Dokumentenbibliothek Drive-ID
     */
    public static function getDriveId(): string
    {
        return self::get('drive_id');
    }

    /**
     * Basisordner für Vorgänge (Standard: 'Vorgaenge')
     */
    public static function getBaseFolder(): string
    {
        $base = self::get('base_folder', 'Vorgaenge');
        return trim($base, "/ \t\n\r\0\x0B");
    }

    /**
     * Prüft, ob die zwingend erforderlichen Parameter für Microsoft Graph konfiguriert sind.
     * Mindestens erforderlich: tenant_id, client_id, client_secret sowie (drive_id ODER site_id).
     */
    public static function isConfigured(): bool
    {
        return self::getTenantId() !== ''
            && self::getClientId() !== ''
            && self::getClientSecret() !== ''
            && (self::getDriveId() !== '' || self::getSiteId() !== '');
    }

    /**
     * Lädt config/config.php einmalig statisch gecached.
     *
     * @return array<string, mixed>
     */
    private static function loadFileConfig(): array
    {
        if (self::$fileConfig !== null) {
            return self::$fileConfig;
        }

        self::$fileConfig = [];
        $configPath = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configPath) && is_readable($configPath)) {
            try {
                $res = require $configPath;
                if (is_array($res)) {
                    self::$fileConfig = $res;
                }
            } catch (\Throwable $e) {
                error_log('SharePointConfig::loadFileConfig Fehler: ' . $e->getMessage());
            }
        }

        return self::$fileConfig;
    }
}
