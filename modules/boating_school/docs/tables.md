# Datenbank-Tabellen: Modul `boating_school`

Alle Tabellen laufen unter MySQL (InnoDB, `utf8mb4_unicode_ci`) und bilden das konsolidierte Datenmodell.

## Aktive Tabellen

### 1. `bs_customer_magic_links`
- **Zweck**: Passwortloser Schüler-Zugang für „Meine Termine“.
- **Spalten**:
  - `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `customer_id`: INT UNSIGNED NOT NULL, FK auf `bs_customers.id` (ON DELETE CASCADE)
  - `token_hash`: CHAR(64) NOT NULL (SHA-256 Hash des Zufallstokens, niemals Klartext)
  - `purpose`: VARCHAR(32) NOT NULL DEFAULT 'appointments_view'
  - `expires_at`: DATETIME NOT NULL
  - `last_used_at`: DATETIME NULL
  - `created_at`: DATETIME NOT NULL
  - `created_ip`: VARCHAR(45) NULL
- **Indizes**: `idx_bs_magic_token (token_hash)`, `idx_bs_magic_customer (customer_id)`

### 2. `bs_customers`
- **Zweck**: Stammdaten der Fahrschüler.
- **Spalten**: `id`, `first_name`, `last_name`, `email`, `phone`, `customer_number`, `is_active`, `notes`, `created_at`, `updated_at`.

### 3. `bs_instructors`
- **Zweck**: Fahrlehrer / Coaches.
- **Spalten**: `id`, `user_id` (FK auf `users.id`), `hourly_rate`, `qualifications`, `color_code`, `is_active`, `created_at`, `updated_at`.

### 4. `bs_boats`
- **Zweck**: Schulungsboote.
- **Spalten**: `id`, `name`, `type` (motor / sailing), `registration_number`, `home_location_id` (FK auf `bs_locations.id`), `capacity`, `is_active`, `created_at`.

### 5. `bs_locations`
- **Zweck**: Ausbildungsstandorte / Häfen / Stege.
- **Spalten**: `id`, `name`, `address`, `postal_code`, `city`, `dock_number`, `meeting_instructions`, `is_active`.

### 6. `bs_services`
- **Zweck**: Dienstleistungen (Motorboot-Fahrstunde, Prüfungsvorbereitung, etc.).
- **Spalten**: `id`, `name`, `duration_minutes`, `price`, `category`, `description`, `is_active`.

### 7. `bs_appointments`
- **Zweck**: Konkrete gebuchte Fahrstunden und Prüfungen.
- **Spalten**: `id`, `customer_id`, `instructor_id`, `boat_id`, `location_id`, `service_id`, `date`, `start_time`, `end_time`, `status` (`requested`, `confirmed`, `cancelled_customer`, `cancelled_system`, `completed`), `price`, `instructor_payout_amount`, `instructor_payout_status`, `instructor_payout_note`.

### 8. `bs_coach_timesheet_closures` (Einzige Lohnquelle)
- **Zweck**: Snapshot-Architektur für den Monatsabschluss und die Fahrlehrer-Abrechnung.
- **Spalten**: `id`, `instructor_id`, `period_year`, `period_month`, `status` (`draft`, `submitted`, `approved`, `rejected`, `reopened`, `paid`), `total_hours`, `gross_amount`, `deductions_amount`, `net_amount`, `snapshot_json`, `submitted_at`, `approved_at`, `paid_at`, `created_at`, `updated_at`.

### 9. `bs_coach_availabilities` & `bs_coach_absences`
- **Zweck**: Wöchentliche Arbeitszeiten je Wochentag sowie Abwesenheiten (Krankheit, Ferien) zur kollisionsfreien Buchungsberechnung in `CoachAvailabilityService`.

---

## Legacy-Tabellen

Die folgenden Tabellen wurden im Rahmen der Konsolidierung migriert und dürfen vom neuen Code nicht mehr beschrieben werden:

1. `bs_coach_timesheets_legacy` (früher `bs_coach_timesheets`):
   - Daten wurden nach `bs_coach_timesheet_closures` überführt.
   - Dient ausschließlich als Archiv/Audit-Quelle für historische Daten.

2. `bs_instructor_payroll_runs`:
   - Historische Payroll-Läufe vor Einführung der einheitlichen Snapshot-Architektur.
