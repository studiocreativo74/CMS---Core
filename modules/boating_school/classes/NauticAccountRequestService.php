<?php

declare(strict_types=1);

/**
 * NauticAccountRequestService
 *
 * Verwaltet Account-Registrierungsanfragen für das Nautic-Modul.
 * Benutzer können über die Nautic-Logonseite einen Account anfordern.
 * Der Administrator bestätigt die Anfrage und weist das konkrete Profil zu
 * (Fahrlehrer / Coach, Schüler / Fahrschüler, Bootsmieter / Charterkunde, Flottenleitung / Admin).
 */
final class NauticAccountRequestService
{
    private static bool $tableEnsured = false;

    /**
     * Stellt sicher, dass die Tabelle bs_account_requests existiert (idempotent).
     */
    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        try {
            DB::execute("
                CREATE TABLE IF NOT EXISTS `bs_account_requests` (
                    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `first_name`       VARCHAR(128) NOT NULL,
                    `last_name`        VARCHAR(128) NOT NULL,
                    `email`            VARCHAR(191) NOT NULL,
                    `phone`            VARCHAR(64) NULL,
                    `desired_role`     VARCHAR(64) NOT NULL DEFAULT 'student',
                    `assigned_profile` VARCHAR(64) NULL,
                    `notes`            TEXT NULL,
                    `status`           VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `approved_at`      DATETIME NULL,
                    `approved_by`      VARCHAR(191) NULL,
                    INDEX `idx_bs_req_status` (`status`),
                    INDEX `idx_bs_req_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            error_log('NauticAccountRequestService::ensureTable Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt eine neue Account-Registrierungsanfrage.
     *
     * @param array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone?: ?string,
     *     desired_role?: ?string,
     *     notes?: ?string
     * } $data
     * @return int ID des erstellten Datensatzes
     */
    public static function createRequest(array $data): int
    {
        self::ensureTable();

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = trim(strtolower((string) ($data['email'] ?? '')));
        $phone = !empty($data['phone']) ? trim((string) $data['phone']) : null;
        $desiredRole = trim((string) ($data['desired_role'] ?? 'student'));
        $notes = !empty($data['notes']) ? trim((string) $data['notes']) : null;

        if ($firstName === '' || $lastName === '' || $email === '') {
            throw new \InvalidArgumentException('Vorname, Nachname und E-Mail-Adresse sind Pflichtfelder.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Bitte eine gültige E-Mail-Adresse eingeben.');
        }

        // Prüfen, ob bereits eine ausstehende Anfrage mit dieser E-Mail existiert
        $existing = DB::fetchOne(
            'SELECT id FROM `bs_account_requests` WHERE `email` = :email AND `status` = "pending" LIMIT 1',
            ['email' => $email]
        );

        if ($existing) {
            DB::execute(
                'UPDATE `bs_account_requests` 
                 SET `first_name` = :fn, `last_name` = :ln, `phone` = :ph, `desired_role` = :dr, `notes` = :nt, `created_at` = NOW()
                 WHERE `id` = :id',
                [
                    'fn' => $firstName,
                    'ln' => $lastName,
                    'ph' => $phone,
                    'dr' => $desiredRole,
                    'nt' => $notes,
                    'id' => $existing['id'],
                ]
            );
            return (int) $existing['id'];
        }

        DB::execute(
            'INSERT INTO `bs_account_requests` 
             (`first_name`, `last_name`, `email`, `phone`, `desired_role`, `notes`, `status`, `created_at`)
             VALUES (:fn, :ln, :email, :phone, :dr, :notes, "pending", NOW())',
            [
                'fn'    => $firstName,
                'ln'    => $lastName,
                'email' => $email,
                'phone' => $phone,
                'dr'    => $desiredRole,
                'notes' => $notes,
            ]
        );

        $id = (int) DB::lastInsertId();

        if (class_exists('ActivityLog')) {
            ActivityLog::log('nautic.account_request_created', null, [
                'request_id'   => $id,
                'email'        => $email,
                'name'         => "{$firstName} {$lastName}",
                'desired_role' => $desiredRole,
            ]);
        }

        return $id;
    }

    /**
     * Ruft alle Account-Anfragen ab, optional nach Status gefiltert.
     *
     * @param string|null $status 'pending', 'approved', 'rejected' oder null für alle
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(?string $status = null): array
    {
        self::ensureTable();

        try {
            if ($status !== null && $status !== '') {
                return DB::fetchAll(
                    'SELECT * FROM `bs_account_requests` WHERE `status` = :st ORDER BY `created_at` DESC',
                    ['st' => $status]
                );
            }

            return DB::fetchAll('SELECT * FROM `bs_account_requests` ORDER BY `created_at` DESC');
        } catch (\Throwable $e) {
            error_log('NauticAccountRequestService::getAll Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ermittelt die Anzahl ausstehender ('pending') Registrierungsanfragen.
     */
    public static function getPendingCount(): int
    {
        self::ensureTable();

        try {
            $row = DB::fetchOne('SELECT COUNT(*) AS cnt FROM `bs_account_requests` WHERE `status` = "pending"');
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Sucht eine Anfrage anhand ihrer ID.
     */
    public static function findById(int $id): ?array
    {
        self::ensureTable();

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_account_requests` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Bestätigt eine Registrierungsanfrage, weist das Profil zu und legt
     * die Stammdaten im entsprechenden Zielsystem (Fahrlehrer, Schüler/Kunde, Admin) an.
     *
     * @param int $requestId ID der Registrierungsanfrage
     * @param string $profileType 'instructor', 'student', 'renter', 'admin'
     * @param string|null $adminUser Name/E-Mail des bestätigenden Administrators
     * @return array{success: bool, message: string, profile: string}
     */
    public static function approveAndAssignProfile(int $requestId, string $profileType, ?string $adminUser = null): array
    {
        self::ensureTable();

        $req = self::findById($requestId);
        if (!$req) {
            return ['success' => false, 'message' => 'Registrierungsanfrage nicht gefunden.', 'profile' => $profileType];
        }

        $email = (string) $req['email'];
        $firstName = (string) $req['first_name'];
        $lastName = (string) $req['last_name'];
        $phone = $req['phone'] ? (string) $req['phone'] : null;
        $profileTypeClean = in_array($profileType, ['instructor', 'student', 'renter', 'admin'], true) ? $profileType : 'student';

        try {
            // 1. Zuweisung je nach Profiltyp
            switch ($profileTypeClean) {
                case 'instructor':
                    // In bs_instructors anlegen oder aktivieren
                    $existingCoach = DB::fetchOne(
                        'SELECT id FROM `bs_instructors` WHERE `email` = :email LIMIT 1',
                        ['email' => $email]
                    );

                    if (!$existingCoach) {
                        DB::execute(
                            'INSERT INTO `bs_instructors` 
                             (`first_name`, `last_name`, `email`, `phone`, `license_number`, `qualifications`, `color_code`, `is_active`, `created_at`)
                             VALUES (:fn, :ln, :email, :phone, :lic, :qual, "#0284c7", 1, NOW())',
                            [
                                'fn'    => $firstName,
                                'ln'    => $lastName,
                                'email' => $email,
                                'phone' => $phone,
                                'lic'   => 'In Prüfung',
                                'qual'  => 'Kat. A Motorboot',
                            ]
                        );
                    } else {
                        DB::execute('UPDATE `bs_instructors` SET `is_active` = 1 WHERE `id` = :id', ['id' => $existingCoach['id']]);
                    }

                    // Benutzerkonto für Fahrlehrer-Portal
                    self::ensureCmsUser($email, "{$firstName} {$lastName}", 'editor');
                    break;

                case 'student':
                case 'renter':
                    // In bs_customers anlegen oder aktivieren
                    $existingCustomer = DB::fetchOne(
                        'SELECT id FROM `bs_customers` WHERE `email` = :email LIMIT 1',
                        ['email' => $email]
                    );

                    if (!$existingCustomer) {
                        $custNumber = 'KD-' . date('Y') . '-' . str_pad((string) mt_rand(100, 999), 3, '0', STR_PAD_LEFT);
                        DB::execute(
                            'INSERT INTO `bs_customers` 
                             (`customer_number`, `type`, `first_name`, `last_name`, `email`, `phone`, `target_license`, `is_active`, `created_at`)
                             VALUES (:cn, :type, :fn, :ln, :email, :phone, :target, 1, NOW())',
                            [
                                'cn'     => $custNumber,
                                'type'   => $profileTypeClean,
                                'fn'     => $firstName,
                                'ln'     => $lastName,
                                'email'  => $email,
                                'phone'  => $phone,
                                'target' => ($profileTypeClean === 'student') ? 'Kat. A Motorboot' : 'Bootsmiete / Charter',
                            ]
                        );
                    } else {
                        DB::execute(
                            'UPDATE `bs_customers` SET `type` = :tp, `is_active` = 1 WHERE `id` = :id',
                            ['tp' => $profileTypeClean, 'id' => $existingCustomer['id']]
                        );
                    }
                    break;

                case 'admin':
                    // Als CMS-Admin in users anlegen
                    self::ensureCmsUser($email, "{$firstName} {$lastName}", 'admin');
                    break;
            }

            // 2. Status in bs_account_requests auf 'approved' setzen
            DB::execute(
                'UPDATE `bs_account_requests` 
                 SET `status` = "approved", `assigned_profile` = :prof, `approved_at` = NOW(), `approved_by` = :by 
                 WHERE `id` = :id',
                [
                    'prof' => $profileTypeClean,
                    'by'   => $adminUser ?? 'Administrator',
                    'id'   => $requestId,
                ]
            );

            // 3. Magic-Code für sofortigen Erst-Login erstellen
            if (class_exists('MagicCode')) {
                try {
                    $createdCode = MagicCode::createCodeForEmail($email, 'nautic_login', 3, 48);
                    MagicCode::sendEmailNotification(
                        $createdCode['code'],
                        $email,
                        'nautic_login',
                        3,
                        $adminUser ?? 'office@studiocreativo.ch'
                    );
                } catch (\Throwable $e) {
                    error_log('Magic-Code Erstellung nach Approval fehlgeschlagen: ' . $e->getMessage());
                }
            }

            if (class_exists('ActivityLog')) {
                ActivityLog::log('nautic.account_request_approved', null, [
                    'request_id'       => $requestId,
                    'email'            => $email,
                    'assigned_profile' => $profileTypeClean,
                    'approved_by'      => $adminUser,
                ]);
            }

            $profileLabel = match ($profileTypeClean) {
                'instructor' => 'Fahrlehrer / Crew',
                'student'    => 'Schüler (Fahrschule)',
                'renter'     => 'Bootsmieter / Charterkunde',
                'admin'      => 'Flottenleitung / Admin',
                default      => $profileTypeClean,
            };

            return [
                'success' => true,
                'message' => "Account für '{$firstName} {$lastName}' ({$email}) erfolgreich als '{$profileLabel}' bestätigt und freigeschaltet.",
                'profile' => $profileTypeClean,
            ];
        } catch (\Throwable $e) {
            error_log('NauticAccountRequestService::approveAndAssignProfile Fehler: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Fehler beim Bestätigen des Accounts: ' . $e->getMessage(),
                'profile' => $profileTypeClean,
            ];
        }
    }

    /**
     * Weist eine Registrierungsanfrage ab.
     */
    public static function rejectRequest(int $requestId, ?string $adminUser = null): bool
    {
        self::ensureTable();

        try {
            DB::execute(
                'UPDATE `bs_account_requests` 
                 SET `status` = "rejected", `approved_at` = NOW(), `approved_by` = :by 
                 WHERE `id` = :id',
                [
                    'by' => $adminUser ?? 'Administrator',
                    'id' => $requestId,
                ]
            );

            if (class_exists('ActivityLog')) {
                ActivityLog::log('nautic.account_request_rejected', null, [
                    'request_id'  => $requestId,
                    'rejected_by' => $adminUser,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('NauticAccountRequestService::rejectRequest Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stellt sicher, dass ein CMS-Benutzer in users existiert.
     */
    private static function ensureCmsUser(string $email, string $name, string $role = 'editor'): void
    {
        try {
            $user = DB::fetchOne('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
            if (!$user) {
                $dummyPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                DB::execute(
                    'INSERT INTO users (email, name, password_hash, role, is_active, created_at) 
                     VALUES (:email, :name, :hash, :role, 1, NOW())',
                    [
                        'email' => $email,
                        'name'  => $name,
                        'hash'  => $dummyPasswordHash,
                        'role'  => $role,
                    ]
                );
            } else {
                DB::execute('UPDATE users SET is_active = 1 WHERE id = :id', ['id' => $user['id']]);
            }
        } catch (\Throwable $e) {
            error_log('NauticAccountRequestService::ensureCmsUser Fehler: ' . $e->getMessage());
        }
    }
}
