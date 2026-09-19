<?php

declare(strict_types=1);

final class Mailer
{
    private const DEFAULT_FROM_EMAIL = 'noreply@safecase.ch';
    private const DEFAULT_FROM_NAME  = 'CMS System';
    private const OFFICE_COPY_EMAIL  = 'office@studiocreativo.ch';

    /**
     * Versendet eine E-Mail über die native mail()-Funktion von PHP.
     * Optional kann eine Kopie an die Office-Adresse gesendet werden.
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        bool $sendCopyToOffice = false
    ): bool {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $fromEmail = self::DEFAULT_FROM_EMAIL;
        $fromName  = self::DEFAULT_FROM_NAME;
        $replyTo   = self::OFFICE_COPY_EMAIL;

        // UTF-8 Subject-Encoding nach RFC 2047
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [
            'From: ' . sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromEmail),
            'Reply-To: ' . $replyTo,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: PHP/' . phpversion(),
        ];

        $headersStr = implode("\r\n", $headers) . "\r\n";
        $extraParam = '-f' . escapeshellarg($fromEmail);

        // Hauptempfänger
        $mailSent = @mail($to, $encodedSubject, $body, $headersStr, $extraParam);

        // Optionale Kopie an office@studiocreativo.ch
        if ($sendCopyToOffice && strtolower($to) !== strtolower(self::OFFICE_COPY_EMAIL)) {
            $officeSubject = '=?UTF-8?B?' . base64_encode('[Kopie] ' . $subject) . '?=';
            @mail(self::OFFICE_COPY_EMAIL, $officeSubject, $body, $headersStr, $extraParam);
        }

        return $mailSent;
    }

    /**
     * Versendet den 10-stelligen Magic-Code an die angegebene Benutzer-E-Mail
     * und benachrichtigt standardmässig die Office-Adresse.
     */
    public static function sendMagicCodeToUser(
        string $toEmail,
        string $code
    ): void {
        $subject = 'Dein Magic-Code für das CMS';
        $nowFormatted = date('d.m.Y H:i:s');

        $body = "Guten Tag,\n\n"
            . "Du hast einen Magic-Code für das CMS angefordert:\n\n"
            . "----------------------------------------\n"
            . "Dein Magic-Code:  {$code}\n"
            . "E-Mail:           {$toEmail}\n"
            . "Erstellt am:      {$nowFormatted}\n"
            . "----------------------------------------\n\n"
            . "Gib diesen 10-stelligen Code auf der Startseite ein, um dich einzuloggen.\n\n"
            . "Freundliche Grüsse,\n"
            . "Dein CMS-System\n";

        self::send($toEmail, $subject, $body, true);
    }
}
