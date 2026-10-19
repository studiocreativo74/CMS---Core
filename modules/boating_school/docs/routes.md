# Routen-Dokumentation: Modul `boating_school`

Übersicht aller registrierten Routen, HTTP-Methoden, erforderlichen Berechtigungen und Zwecke.

## 1. Öffentliches Kunden-Portal & Schüler-Self-Service (Frontend)

| Methode | Route | Erforderliche Berechtigung | Zweck |
|---|---|---|---|
| `GET` | `/bootschule` | Keine (Öffentlich) | Einstiegsseite / Übersicht Bootsfahrschule |
| `GET` | `/bootschule/book` | Keine (Öffentlich) | Öffentliches Buchungsportal (Schritt-für-Schritt Buchungsassistent) |
| `POST` | `/bootschule/book` | Keine (CSRF-geschützt) | Erstellt eine neue Buchungsanfrage (`status = 'requested'`) |
| `GET` | `/bootschule/me/link` | Keine (Öffentlich) | Validiert Magic-Link-Token, regeneriert Session-ID und loggt Schüler ein |
| `POST` | `/bootschule/me/request-link` | Keine (CSRF-geschützt, Rate-Limit) | Fordert neuen 7-Tage-Magic-Link per E-Mail an (neutrale Antwort) |
| `GET` | `/bootschule/me/appointments` | Keine (Schüler-Session) | Zeigt bevorstehende und vergangene (12 Monate) Fahrstunden des Schülers |
| `POST` | `/bootschule/me/appointments/{id}/cancel` | Keine (Schüler-Session, CSRF) | Storniert Termin durch Schüler unter Einhaltung der Stornofrist |
| `GET` / `POST` | `/bootschule/me/logout` | Keine (Schüler-Session) | Beendet die Schüler-Session und regeneriert Session-ID |

---

## 2. Admin-Bereich (`/admin/boating/...`)

| Methode | Route | Erforderliche Berechtigung | Zweck |
|---|---|---|---|
| `GET` | `/admin/boating` | `boating.calendar.view` | Dashboard & Schnellübersicht |
| `GET` | `/admin/boating/calendar` | `boating.calendar.view` | Interaktiver Fahrstunden-Kalender |
| `POST` | `/admin/boating/appointments/create` | `boating.bookings.manage` | Manueller Termin-Eintrag durch Admin |
| `POST` | `/admin/boating/appointments/update` | `boating.bookings.manage` | Aktualisiert Termin / Status |
| `POST` | `/admin/boating/appointments/cancel` | `boating.bookings.manage` | Storniert Termin durch Schule |
| `GET` | `/admin/boating/customers` | `boating.customers.view` | CRM-Kundenliste |
| `GET` / `POST` | `/admin/boating/customers/edit` | `boating.customers.manage` | Kunde anlegen / bearbeiten |
| `POST` | `/admin/boating/customers/anonymize` | `boating.customers.manage` | DSGVO-Anonymisierung & Magic-Link-Revoke |
| `GET` | `/admin/boating/boats` | `boating.settings.manage` | Schulungsboote-Verwaltung |
| `GET` | `/admin/boating/services` | `boating.settings.manage` | Ausbildungsleistungen & Preise |
| `GET` | `/admin/boating/locations` | `boating.settings.manage` | Standorte & Häfen |
| `GET` | `/admin/boating/instructors` | `boating.coaches.view` | Fahrlehrer-Stammdaten |
| `POST` | `/admin/boating/instructors/edit` | `boating.coaches.manage` | Fahrlehrer bearbeiten / Lohnsätze |
| `GET` | `/admin/boating/payroll` | `boating.payroll.view` | Monatsauswertungen über Timesheet-Snapshots |
| `POST` | `/admin/boating/payroll/action` | `boating.payroll.manage` | Timesheet genehmigen (`approved`) oder als bezahlt markieren (`paid`) |
| `GET` / `POST` | `/admin/boating/settings` | `boating.settings.manage` | Moduleinstellungen & E-Mail-Templates |

---

## 3. Coach-Portal (`/admin/boating/coach/...`)

| Methode | Route | Erforderliche Berechtigung | Zweck |
|---|---|---|---|
| `GET` | `/admin/boating/coach/dashboard` | `boating.coach.portal` | Eigenes Dashboard des Fahrlehrers |
| `GET` | `/admin/boating/coach/availability` | `boating.coach.availability` | Wöchentliche Arbeitszeiten & Zeitfenster |
| `POST` | `/admin/boating/coach/availability/save` | `boating.coach.availability` | Speichert Verfügbarkeitsprofile |
| `GET` / `POST` | `/admin/boating/coach/absences` | `boating.coach.availability` | Abwesenheiten / Ferien eintragen |
| `GET` | `/admin/boating/coach/timesheets` | `boating.timesheets.submit` | Monatsabschluss & Stundenübersicht |
| `POST` | `/admin/boating/coach/timesheets/submit` | `boating.timesheets.submit` | Reicht Monatsabrechnung zur Prüfung ein (`submitted`) |
