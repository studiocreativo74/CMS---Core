<?php

declare(strict_types=1);

/**
 * Zentrale Definition der Core-Version des CMS.
 *
 * Verwaltet Versionsnummer, Build-Metadaten und Kompatibilitätsprüfungen
 * für Module und Datenbank-Migrationen.
 */
final class CoreVersion
{
    /**
     * Aktuelle semantische Version des CMS-Kerns (MAJOR.MINOR.PATCH).
     */
    public const VERSION = '1.0.0';

    /**
     * Offizieller Produkt- / Systemname.
     */
    public const NAME = 'StudioCreativo CMS';

    /**
     * Release-Datum dieser Version.
     */
    public const RELEASE_DATE = '2026-09-20';

    /**
     * Mindestens vorausgesetzte PHP-Version.
     */
    public const REQUIRED_PHP = '8.1.0';

    /**
     * Privater Konstruktor: rein statische Utility-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Liefert die reine Versionsnummer (z. B. "1.0.0").
     */
    public static function get(): string
    {
        return self::VERSION;
    }

    /**
     * Liefert den vollständigen System- und Versionsstring.
     */
    public static function getFull(): string
    {
        return self::NAME . ' v' . self::VERSION;
    }

    /**
     * Prüft, ob die aktuelle Core-Version eine Versionsanforderung (Constraint) erfüllt.
     *
     * Unterstützte Formate:
     * - "1.0.0" oder "=1.0.0" (exakte Version oder höher)
     * - ">=1.0.0", ">1.0.0", "<=2.0.0", "<2.0.0"
     * - "^1.0.0" (kompatibel mit 1.x)
     * - "~1.0.0" (kompatibel mit 1.0.x)
     * - "*" oder "" (jede Version erlaubt)
     *
     * @param string|null $constraint Die Anforderungs-Bedingung
     * @return bool True wenn kompatibel, sonst False
     */
    public static function satisfies(?string $constraint): bool
    {
        if ($constraint === null) {
            return true;
        }

        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        // Operatoren wie >=, <=, >, <, =, ==, !=
        if (preg_match('/^([><!=]=?)\s*([0-9\.]+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $targetVersion = $matches[2];
            return version_compare(self::VERSION, $targetVersion, $operator);
        }

        // Caret ^1.0.0 (kompatibel mit Major-Version 1)
        if (str_starts_with($constraint, '^')) {
            $target = ltrim($constraint, '^');
            $parts = explode('.', $target);
            $targetMajor = (int) ($parts[0] ?? 1);

            $currentParts = explode('.', self::VERSION);
            $currentMajor = (int) ($currentParts[0] ?? 1);

            return $currentMajor === $targetMajor && version_compare(self::VERSION, $target, '>=');
        }

        // Tilde ~1.0.0 (kompatibel mit Minor-Version)
        if (str_starts_with($constraint, '~')) {
            $target = ltrim($constraint, '~');
            return version_compare(self::VERSION, $target, '>=');
        }

        // Default: Mindestens die angegebene Version erforderlich
        return version_compare(self::VERSION, $constraint, '>=');
    }
}
