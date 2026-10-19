<?php

declare(strict_types=1);

/**
 * CLI Cron-Task: Automatischer Versand von Terminerinnerungen (boating_school)
 *
 * Automatische Ausführung (z. B. via Crontab jede Stunde oder einmal täglich):
 * 0 * * * * php /pfad/zu/bin/boating_send_reminders.php > /dev/null 2>&1
 *
 * Aufgaben:
 * - Sucht anstehende bestätigte Termine im definierten Vorlauf-Zeitfenster (z.B. nächste 24h)
 * - Beachtet Kundenpräferenzen (notify_email = 1)
 * - Versendet personalisierte E-Mails via BoatingNotificationService
 * - Setzt reminder_sent_at = NOW(), um Mehrfachversand zu verhindern
 * - Protokolliert alle Aktionen im ActivityLog
 */

$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));

$rootDir = dirname(__DIR__);
require_once $rootDir . '/DB.php';
require_once $rootDir . '/core/Settings.php';
if (file_exists($rootDir . '/core/Mailer.php')) {
    require_once $rootDir . '/core/Mailer.php';
}
if (file_exists($rootDir . '/core/ActivityLog.php')) {
    require_once $rootDir . '/core/ActivityLog.php';
}

require_once $rootDir . '/modules/boating_school/classes/BoatingSchoolRepository.php';
require_once $rootDir . '/modules/boating_school/classes/AppointmentService.php';
require_once $rootDir . '/modules/boating_school/classes/BoatingNotificationService.php';

// Optionale Stunden-Angabe über CLI-Argument (z.B. php bin/boating_send_reminders.php 48)
$hoursArg = null;
if ($isCli && !empty($argv[1]) && is_numeric($argv[1])) {
    $hoursArg = (int) $argv[1];
}

$timestamp = date('Y-m-d H:i:s');
if ($isCli) {
    echo "[{$timestamp}] Starte Terminerinnerungs-Lauf für Bootsfahrschule...\n";
}

$result = BoatingNotificationService::sendUpcomingReminders($hoursArg);

if ($isCli) {
    echo sprintf(
        "[%s] Ergebnis: Status=%s, %d Termine gescannt, %d Erinnerungen versendet, %d übersprungen.\n",
        date('Y-m-d H:i:s'),
        $result['status'],
        $result['scanned_count'],
        $result['sent_count'],
        $result['skipped_count']
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
