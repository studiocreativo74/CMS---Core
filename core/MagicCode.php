<?php

declare(strict_types=1);

final class MagicCode
{
    public const DEFAULT_LENGTH = 10;
    private const CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Generiert einen zufälligen Code aus A-Z und 0-9 mit gegebener Länge.
     */
    public static function generateCode(int $length = self::DEFAULT_LENGTH): string
    {
        $code = '';
        $maxIndex = strlen(self::CODE_ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**
     * Legt einen neuen Magic-Code für eine E-Mail an.
     * - $email: E-Mail-Adresse, für die der Code gilt
     * - $usageType: standardmässig 'admin_login'
     * - $maxUses: wie oft darf der Code benutzt werden (default 1)
     * - $expiresAt: optionales Ablaufdatum als DateTimeInterface oder null
     *
     * Gibt ein Array mit ['id' => int, 'code' => string, 'email' => string] zurück.
     *
     * @return array{id: int, code: string, email: string}
     */
    public static function createCodeForEmail(
        string $email,
        string $usageType = 'admin_login',
        int $maxUses = 1,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $normalizedEmail = strtolower(trim($email));
        $code = self::generateCode(self::DEFAULT_LENGTH);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresFormatted = $expiresAt !== null ? $expiresAt->format('Y-m-d H:i:s') : null;

        DB::execute(
            'INSERT INTO magic_codes (email, code_hash, usage_type, max_uses, used_count, expires_at, created_at)
             VALUES (:email, :code_hash, :usage_type, :max_uses, 0, :expires_at, NOW())',
            [
                'email' => $normalizedEmail,
                'code_hash' => $codeHash,
                'usage_type' => $usageType,
                'max_uses' => $maxUses,
                'expires_at' => $expiresFormatted,
            ]
        );

        $id = (int) DB::lastInsertId();

        return [
            'id' => $id,
            'code' => $code,
            'email' => $normalizedEmail,
        ];
    }

    /**
     * Prüft einen Klartext-Code für eine bestimmte E-Mail für admin_login.
     * - Nur Codes mit passender E-Mail, usage_type = 'admin_login',
     *   used_count < max_uses und nicht abgelaufen.
     * Liefert bei Erfolg den Datensatz oder null.
     */
    public static function verifyAdminCodeForEmail(string $email, string $code): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanCode = strtoupper(trim($code));

        if ($normalizedEmail === '' || $cleanCode === '' || strlen($cleanCode) > self::DEFAULT_LENGTH) {
            return null;
        }

        try {
            $candidates = DB::fetchAll(
                "SELECT * FROM magic_codes 
                 WHERE email = :email
                   AND usage_type = 'admin_login' 
                   AND used_count < max_uses 
                   AND (expires_at IS NULL OR expires_at > NOW())",
                ['email' => $normalizedEmail]
            );
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($candidates as $row) {
            if (password_verify($cleanCode, (string) ($row['code_hash'] ?? ''))) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Markiert einen Code nach erfolgreicher Nutzung als verwendet
     * (used_count++, used_at = NOW()).
     */
    public static function markUsed(int $id): void
    {
        try {
            DB::execute(
                'UPDATE magic_codes SET used_count = used_count + 1, used_at = NOW() WHERE id = :id',
                ['id' => $id]
            );
        } catch (\Throwable $e) {
            // Fehlertolerante Handhabung
        }
    }

    /**
     * Liefert alle Magic-Codes absteigend nach Erstelldatum sortiert zurück.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(): array
    {
        try {
            return DB::fetchAll('SELECT * FROM magic_codes ORDER BY created_at DESC');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Versendet eine Benachrichtigungs-E-Mail mit dem Klartext-Code an die angegebene Adresse
     * sowie als Kopie/BCC an office@studiocreativo.ch.
     */
    public static function sendEmailNotification(
        string $code,
        string $recipientEmail,
        string $usageType = 'admin_login',
        int $maxUses = 1,
        string $bccEmail = 'office@studiocreativo.ch'
    ): bool {
        $subject = 'Dein Magic-Code für das CMS';
        $message = "Guten Tag,\n\n"
            . "Du hast einen Magic-Code für das CMS angefordert:\n\n"
            . "----------------------------------------\n"
            . "Dein Magic-Code:  {$code}\n"
            . "E-Mail:           {$recipientEmail}\n"
            . "Verwendungszweck: {$usageType}\n"
            . "Max. Nutzungen:   {$maxUses}\n"
            . "Erstellt am:      " . date('d.m.Y H:i:s') . "\n"
            . "----------------------------------------\n\n"
            . "Gib diesen 10-stelligen Code auf der Startseite ein, um dich einzuloggen.\n\n"
            . "Freundliche Grüsse,\n"
            . "Dein CMS-System\n";

        $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $fromEmail = "no-reply@{$serverHost}";

        $headers = "From: {$fromEmail}\r\n"
            . "Reply-To: office@studiocreativo.ch\r\n";

        if ($bccEmail !== '' && strtolower($recipientEmail) !== strtolower($bccEmail)) {
            $headers .= "Bcc: {$bccEmail}\r\n";
        }

        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        $mailSent = @mail($recipientEmail, $subject, $message, $headers);

        // Falls BCC über den lokalen MTA geblockt wird oder office@ separat informiert werden soll:
        if ($bccEmail !== '' && strtolower($recipientEmail) !== strtolower($bccEmail)) {
            $adminSubject = "[Kopie/BCC] Neuer Magic-Code für {$recipientEmail}";
            $adminHeaders = "From: {$fromEmail}\r\n"
                . "Reply-To: {$recipientEmail}\r\n"
                . "X-Mailer: PHP/" . phpversion() . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";
            @mail($bccEmail, $adminSubject, $message, $adminHeaders);
        }

        return $mailSent;
    }
}
