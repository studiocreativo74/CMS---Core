<?php

declare(strict_types=1);

/**
 * CLI Cron-Task zur Fristenprüfung & Bereinigung im Sicherungsportal (secure_portal)
 *
 * Automatische Ausführung (z. B. via Crontab):
 * 0 3 * * * php /pfad/zu/bin/secure-retention-cron.php > /dev/null 2>&1
 *
 * Lebenszyklus:
 * 1. Phase 1 (T+30 / secure_download_days_active): Externen Download-Zugang sperren (download_enabled = 0)
 * 2. Phase 2 (T+60 / secure_download_days_delete): Sicherungsdateien löschen und Fallstatus aktualisieren
 */

$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));

$rootDir = dirname(__DIR__);
require_once $rootDir . '/DB.php';
require_once $rootDir . '/core/Settings.php';
require_once $rootDir . '/core/SecurePortalConfig.php';
require_once $rootDir . '/modules/secure_portal/classes/SecurePortalRepository.php';
require_once $rootDir . '/modules/secure_portal/classes/SecurePortalService.php';

$daysActive = SecurePortalConfig::getDownloadDaysActive();
$daysDelete = SecurePortalConfig::getDownloadDaysDelete();

$timestamp = date('Y-m-d H:i:s');
if ($isCli) {
    echo "[{$timestamp}] Starte Fristenprüfung für Sicherungsportal (T+{$daysActive} Sperre / T+{$daysDelete} Löschung)...\n";
}

$result = SecurePortalService::runRetentionCleanup($daysActive, $daysDelete, 'CLI-Cron');

if ($isCli) {
    echo sprintf(
        "[%s] Abgeschlossen: %d Zugang/Zugänge gesperrt (Fälle: %s), %d Datei(en) in %d Fall/Fällen gelöscht (Fälle: %s).\n",
        date('Y-m-d H:i:s'),
        $result['locked_count'],
        empty($result['locked_cases']) ? 'keine' : implode(', ', $result['locked_cases']),
        $result['purged_files_count'],
        $result['purged_cases_count'],
        empty($result['purged_cases']) ? 'keine' : implode(', ', $result['purged_cases'])
    );

    if (!empty($result['errors'])) {
        echo "Fehler aufgetreten:\n";
        foreach ($result['errors'] as $err) {
            echo " - " . $err . "\n";
        }
        exit(1);
    }

    exit(0);
} else {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
