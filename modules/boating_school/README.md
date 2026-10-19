# Bootsfahrschule Modul (`boating_school`)

Modulares Fachmodul für das CMS-Core zur Verwaltung einer professionellen Bootsfahrschule.

## Zweck & Funktionsumfang

1. **Stammdatenverwaltung**
   - Schulungsboote (`bs_boats`) mit Typ, Kapazität, Heimathafen und Wartungsstatus.
   - Fahrlehrer / Coaches (`bs_instructors`) mit Verknüpfung zu Core-Usern (`user_id`), Lohnsätzen, HR-Details und Qualifikationen.
   - Ausbildungsstandorte & Häfen (`bs_locations`) mit Adressdaten und Stegdetails.
   - Dienstleistungen & Kurse (`bs_services`) mit Dauer, Preis und Kategorie (z. B. Motorboot Kat. A, Segeln Kat. D).

2. **Termine & Buchungsportal**
   - Öffentliches Kunden-Buchungsportal unter `/bootschule/book`.
   - Admin-Kalender (`/admin/boating/calendar`) mit Tages-, Wochen- und Monatsansicht.
   - Automatisierte Slot-Berechnung unter Berücksichtigung von Lehrer-Verfügbarkeiten, Booten und Feiertagen.

3. **Kunden-Self-Service („Meine Termine“ / Magic-Link)**
   - Passwortloser, hochsicherer Schülerzugang via zeitlich begrenzter Magic-Links (7 Tage Gültigkeit).
   - Routen: `/bootschule/me/link?token=...`, `/bootschule/me/appointments`, `/bootschule/me/request-link`.
   - Stornierung durch Schüler unter Einhaltung der konfigurierbaren Stornofrist (Default: 24 Stunden).

4. **Coach-Portal, Verfügbarkeiten & Timesheets**
   - Fahrlehrer-Portal (`/admin/boating/coach/...`) mit Terminübersicht, Verfügbarkeitsverwaltung und Abwesenheiten.
   - Snapshot-basierte Monatsabrechnung (`bs_coach_timesheet_closures`) mit Status-Workflow (`draft` → `submitted` → `approved` → `rejected` → `reopened` → `paid`).
   - Zentrale Auswertung über `/admin/boating/payroll`.

5. **Benachrichtigungssystem**
   - `BoatingNotificationService` für Buchungsbestätigungen, Erinnerungen, Terminverschiebungen und Stornierungen.
   - Platzhalter wie `{{my_appointments_url}}` zur nahtlosen Schüler-Authentifizierung.

## Installation & Abhängigkeiten

- **PHP**: >= 8.1
- **Datenbank**: MySQL / MariaDB (InnoDB, `utf8mb4_unicode_ci`)
- **Core-Komponenten**: `DB`, `Session`, `Csrf`, `Settings`, `RateLimiter`, `ActivityLog`
- **Installation**:
  Das Modul wird über die Modul-Verwaltung oder automatisch über `BoatingMigrationManager::migrate()` initialisiert.

## Konfiguration (Settings)

| Schlüssel | Typ | Default | Beschreibung |
|---|---|---|---|
| `boating_magic_link_valid_days` | int | `7` | Gültigkeitsdauer für Schüler-Magic-Links in Tagen |
| `boating_cancel_min_hours_before` | int | `24` | Mindestvorlaufzeit in Stunden für Stornierungen durch Schüler |
| `boating_notification_from_name` | string | `Bootsfahrschule` | Absendername für Benachrichtigungs-E-Mails |
| `boating_notification_from_email` | string | `info@bootschule.local` | Absenderadresse |
| `payroll_tax_deduction_pct` | float | `0.00` | Standard-Steuerabzug in Prozent |
| `payroll_social_security_pct` | float | `0.00` | Standard-Sozialabgaben in Prozent |
