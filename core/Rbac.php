<?php

declare(strict_types=1);

/**
 * Role-Based Access Control (RBAC) für das CMS.
 *
 * Verwaltet Rollen, Berechtigungen und deren Zuweisung zu Benutzern.
 * Beinhaltet einen vollständigen Bypass für aktive Magic-Code-Admin-Sessions.
 */
final class Rbac
{
    /**
     * Privater Konstruktor: rein statische Utility-/Repository-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Lädt alle Rollen eines Users (per user_id) aus der DB.
     * Berücksichtigt sowohl die Pivot-Tabelle `user_roles` als auch
     * die Fallback-Spalte `users.role`.
     *
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    public static function getUserRoles(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            // Rollen aus user_roles verknüpft mit roles
            $roles = DB::fetchAll(
                'SELECT r.`id`, r.`key`, r.`name`, r.`description`, r.`created_at`, r.`updated_at` 
                 FROM `user_roles` ur 
                 JOIN `roles` r ON ur.`role_id` = r.`id` 
                 WHERE ur.`user_id` = :user_id 
                 ORDER BY r.`name` ASC',
                ['user_id' => $userId]
            );

            // Fallback: Prüfen, ob der User in der users-Tabelle eine primäre Rolle hat
            if (empty($roles)) {
                $user = DB::fetchOne('SELECT `role` FROM `users` WHERE `id` = :id LIMIT 1', ['id' => $userId]);
                if (!empty($user['role'])) {
                    $fallbackRole = DB::fetchOne(
                        'SELECT `id`, `key`, `name`, `description`, `created_at`, `updated_at` 
                         FROM `roles` 
                         WHERE `key` = :key 
                         LIMIT 1',
                        ['key' => (string) $user['role']]
                    );
                    if ($fallbackRole) {
                        $roles[] = $fallbackRole;
                    }
                }
            }

            return $roles;
        } catch (\Throwable $e) {
            error_log('Rbac::getUserRoles Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lädt alle Permission-Keys (z.B. 'admin.users.view') für einen User über seine Rollen.
     *
     * @param int $userId
     * @return array<int, string>
     */
    public static function getUserPermissions(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $roles = self::getUserRoles($userId);

            // Superadmin-Rolle hat implizit immer alle existierenden Berechtigungen
            foreach ($roles as $role) {
                if (($role['key'] ?? '') === 'superadmin') {
                    $allPermissions = DB::fetchAll('SELECT `key` FROM `permissions`');
                    return array_values(array_filter(array_column($allPermissions, 'key')));
                }
            }

            if (empty($roles)) {
                return [];
            }

            $roleIds = array_filter(array_map('intval', array_column($roles, 'id')));
            if (empty($roleIds)) {
                return [];
            }

            $inPlaceholders = implode(',', array_fill(0, count($roleIds), '?'));
            $sql = "SELECT DISTINCT p.`key` 
                    FROM `permissions` p 
                    JOIN `role_permissions` rp ON p.`id` = rp.`permission_id` 
                    WHERE rp.`role_id` IN ($inPlaceholders)";

            $rows = DB::fetchAll($sql, array_values($roleIds));
            return array_values(array_filter(array_column($rows, 'key')));
        } catch (\Throwable $e) {
            error_log('Rbac::getUserPermissions Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Prüft, ob ein User (oder die aktuelle Session) eine bestimmte Berechtigung hat.
     * WICHTIG: Wenn eine Magic-Code-Admin-Session aktiv ist, wird IMMER true zurückgegeben (Superadmin-Bypass).
     *
     * @param int|null $userId
     * @param string $permissionKey
     * @return bool
     */
    public static function userHasPermission(?int $userId, string $permissionKey): bool
    {
        // 1. Superadmin-Bypass: Aktive Magic-Code-Admin-Session hat ausnahmslos alle Rechte
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth') && Auth::checkMagic()) {
            return true;
        }

        // 2. Wenn keine User-ID übergeben wurde, versuchen wir die ID aus der Session zu ermitteln
        if ($userId === null || $userId <= 0) {
            if (!empty($_SESSION['user_id'])) {
                $userId = (int) $_SESSION['user_id'];
            } else {
                return false;
            }
        }

        // 3. User-Rechte ermitteln und prüfen
        $permissions = self::getUserPermissions($userId);
        return in_array($permissionKey, $permissions, true);
    }

    /**
     * Convenience-Methode: Prüft die Permission des aktuell eingeloggten Users.
     *
     * @param string $permissionKey
     * @return bool
     */
    public static function can(string $permissionKey): bool
    {
        // Magic-Code Session hat immer vollen Zugriff
        if (!empty($_SESSION['magic_authenticated'])) {
            return true;
        }

        if (class_exists('Auth')) {
            if (Auth::checkMagic()) {
                return true;
            }
            $user = Auth::user();
            $userId = !empty($user['id']) ? (int) $user['id'] : null;
            return self::userHasPermission($userId, $permissionKey);
        }

        $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        return self::userHasPermission($userId, $permissionKey);
    }

    /**
     * Erzwingt eine Berechtigung:
     * Wenn nicht vorhanden, erfolgt ein Abbruch bzw. Redirect mit Flash-Meldung.
     *
     * @param string $permissionKey
     * @return void
     */
    public static function requirePermission(string $permissionKey): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (!self::can($permissionKey)) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Für diese Aktion fehlt die erforderliche Berechtigung (' . htmlspecialchars($permissionKey, ENT_QUOTES, 'UTF-8') . ').';

            // Wenn überhaupt keine Session existiert -> Login
            if (class_exists('Auth') && !Auth::check()) {
                header('Location: ?route=/');
                exit;
            }

            // Eingeloggt, aber ohne ausreichende Rechte -> Admin-Dashboard
            header('Location: ?route=admin');
            exit;
        }
    }

    /**
     * Weist einer Rolle Berechtigungen zu (überschreibt bestehende Zuweisungen).
     *
     * @param int $roleId
     * @param array<int, int> $permissionIds
     * @return void
     */
    public static function setRolePermissions(int $roleId, array $permissionIds): void
    {
        try {
            DB::execute('DELETE FROM `role_permissions` WHERE `role_id` = :role_id', ['role_id' => $roleId]);

            $permissionIds = array_unique(array_filter(array_map('intval', $permissionIds)));
            foreach ($permissionIds as $pId) {
                if ($pId > 0) {
                    DB::execute(
                        'INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :permission_id)',
                        [
                            'role_id' => $roleId,
                            'permission_id' => $pId,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            error_log('Rbac::setRolePermissions Fehler: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Liefert alle Berechtigungen einer Rolle.
     *
     * @param int $roleId
     * @return array<int, array<string, mixed>>
     */
    public static function getRolePermissions(int $roleId): array
    {
        try {
            return DB::fetchAll(
                'SELECT p.`id`, p.`key`, p.`name`, p.`description` 
                 FROM `permissions` p 
                 JOIN `role_permissions` rp ON p.`id` = rp.`permission_id` 
                 WHERE rp.`role_id` = :role_id 
                 ORDER BY p.`key` ASC',
                ['role_id' => $roleId]
            );
        } catch (\Throwable $e) {
            error_log('Rbac::getRolePermissions Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Weist einem Benutzer Rollen zu (überschreibt bestehende Zuweisungen).
     * Aktualisiert zur Rückwärtskompatibilität auch die Spalte `users.role`.
     *
     * @param int $userId
     * @param array<int, int> $roleIds
     * @return void
     */
    public static function setUserRoles(int $userId, array $roleIds): void
    {
        try {
            DB::execute('DELETE FROM `user_roles` WHERE `user_id` = :user_id', ['user_id' => $userId]);

            $roleIds = array_unique(array_filter(array_map('intval', $roleIds)));
            $firstRoleKey = null;

            foreach ($roleIds as $rId) {
                if ($rId > 0) {
                    DB::execute(
                        'INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (:user_id, :role_id)',
                        [
                            'user_id' => $userId,
                            'role_id' => $rId,
                        ]
                    );

                    if ($firstRoleKey === null) {
                        $roleRow = DB::fetchOne('SELECT `key` FROM `roles` WHERE `id` = :id LIMIT 1', ['id' => $rId]);
                        if ($roleRow) {
                            $firstRoleKey = (string) $roleRow['key'];
                        }
                    }
                }
            }

            // Sync mit users.role falls vorhanden
            if ($firstRoleKey !== null) {
                DB::execute('UPDATE `users` SET `role` = :role, `updated_at` = NOW() WHERE `id` = :id', [
                    'role' => $firstRoleKey,
                    'id' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('Rbac::setUserRoles Fehler: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Liefert alle im System definierten Rollen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllRoles(): array
    {
        try {
            return DB::fetchAll('SELECT `id`, `key`, `name`, `description`, `created_at`, `updated_at` FROM `roles` ORDER BY `id` ASC');
        } catch (\Throwable $e) {
            error_log('Rbac::getAllRoles Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Liefert alle im System definierten Berechtigungen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllPermissions(): array
    {
        try {
            return DB::fetchAll('SELECT `id`, `key`, `name`, `description`, `created_at` FROM `permissions` ORDER BY `key` ASC');
        } catch (\Throwable $e) {
            error_log('Rbac::getAllPermissions Fehler: ' . $e->getMessage());
            return [];
        }
    }
}
