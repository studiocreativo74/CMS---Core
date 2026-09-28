<?php

declare(strict_types=1);

/**
 * Modul-spezifischer Wrapper / Autoloader für SharePointConfig
 */

if (!class_exists('SharePointConfig')) {
    $corePath = dirname(__DIR__, 2) . '/core/SharePointConfig.php';
    if (file_exists($corePath)) {
        require_once $corePath;
    }
}
