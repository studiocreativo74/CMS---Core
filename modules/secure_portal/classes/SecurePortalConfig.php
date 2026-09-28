<?php

declare(strict_types=1);

/**
 * Modul-spezifischer Wrapper / Autoloader für SecurePortalConfig
 */

if (!class_exists('SecurePortalConfig')) {
    $corePath = dirname(__DIR__, 2) . '/core/SecurePortalConfig.php';
    if (file_exists($corePath)) {
        require_once $corePath;
    }
}
