# Smoke Test & Checkliste: Modul `boating_school`

Manuelle Testcheckliste zur Qualitätssicherung vor dem Produktiveinsatz.

## 1. Öffentliche Buchung (Frontend)
- [ ] `/bootschule/book` im Browser aufrufen.
- [ ] Leistung, Standort, Boot, Fahrlehrer sowie Wunschdatum und Zeitslot wählen.
- [ ] Kundendaten (Name, E-Mail, Telefon) eingeben und Formular absenden.
- [ ] **Erwartetes Ergebnis**:
  - Buchungsanfrage wird in `bs_appointments` mit `status = 'requested'` gespeichert.
  - Kunde erhält Bestätigungsmail mit Buchungsdetails und Link `{{my_appointments_url}}`.
  - Admin erhält Benachrichtigungsmail.

## 2. Schüler-Magic-Link & „Meine Termine“
- [ ] E-Mail-Bestätigung öffnen und Magic-Link anklicken (`/bootschule/me/link?token=...`).
- [ ] **Erwartetes Ergebnis**:
  - Redirect auf `/bootschule/me/appointments` (Token verschwindet aus URL-Leiste).
  - Begrüssung „Hallo [Vorname]!“ wird korrekt angezeigt.
  - Bevorstehende Termine werden chronologisch aufsteigend aufgeführt (Datum, Uhrzeit, Fahrlehrer, Boot, Standort, Preis).
  - Mobilansicht prüfen: Responsives Kartenlayout.
- [ ] Logout testen: Klick auf „Abmelden“ beendet die Session. Ein erneuter Aufruf von `/bootschule/me/appointments` zeigt das Anmeldeformular.
- [ ] Ungültiges Token testen: Aufruf mit beliebigem Fake-Token zeigt Fehlermeldung „Link ist ungültig oder abgelaufen“.
- [ ] Link-Anforderung per E-Mail: Adresse eingeben und absenden -> Neutrale Meldung „Falls die Adresse bekannt ist, haben wir einen neuen Link gesendet.“

## 3. Stornierung durch Schüler
- [ ] Anstehenden Termin wählen, der mehr als 24 Stunden in der Zukunft liegt.
- [ ] Button „Termin absagen“ klicken, optionalen Grund angeben und bestätigen.
- [ ] **Erwartetes Ergebnis**:
  - Status wechselt auf `cancelled_customer`.
  - Stornierungs-E-Mail wird an Kunde und Admin verschickt.
  - Event erscheint im `ActivityLog`.
- [ ] Stornierung kurzfristig (< 24 Stunden vor Termin) versuchen:
  - Button ist deaktiviert oder Meldung erscheint: „Storno nur bis 24 Stunden vor Termin möglich. Bitte kontaktieren Sie uns.“

## 4. Fahrlehrer-Verfügbarkeit & Abwesenheiten
- [ ] Als Fahrlehrer im Coach-Portal anmelden (`/admin/boating/coach/availability`).
- [ ] Arbeitszeiten anpassen und speichern.
- [ ] Eine Abwesenheit (z. B. Urlaub) für kommenden Freitag eintragen.
- [ ] Im Buchungsportal prüfen: Für den betreffenden Fahrlehrer werden an diesem Freitag keine freien Slots mehr angeboten.

## 5. Timesheet & Monatsabschluss
- [ ] Fahrlehrer ruft `/admin/boating/coach/timesheets` auf.
- [ ] Alle absolvierten Einheiten des Vormonats werden gelistet.
- [ ] Klick auf „Monatsabschluss einreichen“ wechselt den Status auf `submitted` und erzeugt den JSON-Snapshot.
- [ ] Als Admin `/admin/boating/payroll` aufrufen:
  - Eingereichtes Timesheet erscheint in der Prüfliste.
  - Admin klickt „Genehmigen“ -> Status wechselt auf `approved`.
  - Nach Auszahlung „Als bezahlt markieren“ -> Status wechselt auf `paid`.

## 6. Rollen- und Rechteprüfung (RBAC)
- [ ] **Rolle Coach/Fahrlehrer**:
  - Hat Zugriff auf `/admin/boating/coach/...`.
  - Hat **keinen** Zugriff auf fremde Fahrlehrer-Daten, `/admin/boating/payroll` oder `/admin/boating/settings`.
- [ ] **Rolle Disponent / Administrator**:
  - Hat vollen Zugriff auf Kalender, CRM, Boote, Standorte und Einstellungen.
- [ ] **Schüler / Kunde**:
  - Bleibt strikt im Frontend (`/bootschule/...`).
  - Ein direkter Aufruf von `/admin/...` wird mit 403 Forbidden oder Login-Redirect abgewiesen.
