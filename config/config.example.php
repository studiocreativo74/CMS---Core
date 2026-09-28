<?php
declare(strict_types=1);

return [
    'db_host' => '127.0.0.1',
    'db_name' => 'cms',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',

    // =========================================================================
    // SharePoint / Microsoft Graph Konfiguration (Optional für Sicherungsportal)
    // Parameter können hier oder alternativ in der Datenbank-Tabelle `settings`
    // (z. B. via Settings::set('sp_tenant_id', ...)) hinterlegt werden.
    // =========================================================================
    // 'sp_tenant_id'     => '00000000-0000-0000-0000-000000000000',
    // 'sp_client_id'     => '00000000-0000-0000-0000-000000000000',
    // 'sp_client_secret' => 'Ihr_Client_Secret_Hier',
    // 'sp_site_id'       => 'tenant.sharepoint.com,site-guid,web-guid',
    // 'sp_drive_id'      => 'b!drive-id...',
    // 'sp_base_folder'   => 'Vorgaenge',
];
