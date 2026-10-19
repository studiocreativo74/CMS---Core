# Changelog – Bootsfahrschule Modul (`boating_school`)

Alle wesentlichen Änderungen und Konsolidierungsschritte dieses Moduls sind hier dokumentiert.

## [2.1.0] - Konsolidierung & Schüler-Magic-Link

### Hinzugefügt
- **Schüler-Magic-Link („Meine Termine“)**:
  - Idempotente Tabelle `bs_customer_magic_links` mit SHA-256-Hashing (`token_hash`), `last_used_at`, `expires_at` und FK auf `bs_customers.id` (ON DELETE CASCADE).
  - Service `BoatingCustomerMagicLinkService` mit `createLinkForCustomer()`, `validateToken()`, `revokeAllForCustomer()` und `requestLinkByEmail()`.
  - Rate-Limiting über `RateLimiter` (max. 5 Anforderungen pro IP und Stunde).
  - Neutrale Antwort bei E-Mail-Anforderung („Falls die Adresse bekannt ist, haben wir einen neuen Link gesendet.“) zur Verhinderung von Email Enumeration.
  - Öffentliche Frontend-Routen: `/bootschule/me/link?token=...`, `/bootschule/me/appointments`, `POST /bootschule/me/request-link`, `POST /bootschule/me/appointments/{id}/cancel` und `/bootschule/me/logout`.
  - Responsive Bootstrap-5-Ansicht für Termine (ab heute, vergangene 12 Monate, Stornomodal, Prüfung der Stornofrist).
  - E-Mail-Platzhalter `{{my_appointments_url}}` in Buchungsbestätigung und Terminerinnerung integriert und im Admin-Bereich dokumentiert.
  - Automatisches Widerrufen aller Magic-Links bei Deaktivierung oder Anonymisierung eines Schülers in `CustomerService`.

### Geändert & Konsolidiert
- **Einheitliche Lohnquelle**:
  - Monatsabrechnung läuft ausschliesslich über die Timesheet-Tabelle `bs_coach_timesheet_closures` (Snapshot-Architektur, Status `draft` → `submitted` → `approved` → `rejected` → `reopened` → `paid`).
  - Alttabelle `bs_coach_timesheets` nach `bs_coach_timesheet_closures` migriert und in `bs_coach_timesheets_legacy` umbenannt.
  - Alttabelle `bs_instructor_payroll_runs` als Legacy deklariert; kein neuer Code schreibt dorthin.
  - `InstructorPayoutService` auf reine Berechnungsfunktion reduziert.
  - Admin-Ansicht `/admin/boating/payroll` zur reinen Timesheet-Auswertung umgebaut.
- **Einheitliche Bezeichner**:
  - Durchgängig `instructor_id` (FK auf `bs_instructors.id`). `coach_id` vollständig bereinigt.
  - Durchgängig `location_id INT UNSIGNED NULL` (FK auf `bs_locations.id`, `NULL` = alle Standorte).
  - Settings-Präfixe vereinheitlicht auf `boating_` und `payroll_`.
- **RBAC & Berechtigungsmatrix**:
  - Zentrale Registrierung aller Berechtigungen in `BoatingPermissions.php`.
  - Strikte Trennung zwischen Frontend-Kunden (ohne Admin-Rechte) und Core-Usern.
- **Verfügbarkeitsprüfung**:
  - Zentrale Methode `CoachAvailabilityService::isInstructorAvailableAt()` bei Slot-Berechnung und vor Termin-Insert.
- **Lohnansatz-Sperre & Stundenweise Absenzen**:
  - Der Stundenlohn (`h-Lohn`) ist in der Zeiterfassung fest vom Administrator hinterlegt und nicht mehr vom Fahrlehrer frei editierbar.
  - Absenzen & Sperrzeiten werden stundenweise („von – bis Uhrzeit“) erfasst und bieten Schnellauswahlen für Vormittag, Nachmittag und Abend.
