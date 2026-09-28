<?php

declare(strict_types=1);

/**
 * CLI Cron-Task zur Fristenprüfung & Bereinigung im Sicherungsportal (secure_portal)
 *
 * Alias/Einstiegspunkt für bin/secure-retention-cron.php
 *
 * Aufruf per CLI:
 * php bin/cron_retention.php
 */

require_once __DIR__ . '/secure-retention-cron.php';
