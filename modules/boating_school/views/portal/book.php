<?php

declare(strict_types=1);

/**
 * Portal: Buchungsflow für Fahrstunden und Mieten (/bootschule/book)
 *
 * @var array<int, array<string, mixed>> $services
 * @var array<int, array<string, mixed>> $locations
 * @var int|null $selectedServiceId
 * @var string $selectedDate
 * @var int|null $selectedLocationId
 * @var array<int, array<string, mixed>> $freeSlots
 * @var string|null $selectedSlotStart
 * @var string|null $selectedSlotEnd
 */

$title = 'Terminslot buchen - Bootsfahrschule';
$currentRoute = 'bootschule/book';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+90 days'));

ob_start();
?>

<div class="container mb-5">
    <div class="max-w-900 mx-auto">
        <!-- Titel & Intro -->
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                <i class="bi bi-calendar-plus me-1"></i> Online-Buchung
            </span>
            <h1 class="h2 fw-bold text-dark">Wunschtermin für Ihre Fahrstunde</h1>
            <p class="text-muted">
                Wählen Sie Ihre gewünschte Leistung und ein Datum aus, um in Echtzeit freie Zeitfenster zu sehen.
            </p>
        </div>

        <div class="row g-4">
            <!-- Linke Spalte: Slot-Konfiguration & Zeitfenster -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center;">1</span>
                        <span>Ausbildung &amp; Wunschtag wählen</span>
                    </h5>

                    <form method="get" action="?route=bootschule/book" id="slotFilterForm">
                        <input type="hidden" name="route" value="bootschule/book">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Dienstleistung / Kurs <span class="text-danger">*</span></label>
                            <select name="service_id" id="serviceSelect" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= (int) $srv['id'] ?>" <?= ($selectedServiceId === (int) $srv['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?> 
                                        (<?= (int) ($srv['duration_minutes'] ?? 60) ?> Min. – CHF <?= number_format((float) ($srv['price'] ?? 0), 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-7">
                                <label class="form-label small fw-semibold">Wunschdatum <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="dateInput" class="form-control" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" min="<?= $minDate ?>" max="<?= $maxDate ?>" onchange="this.form.submit()">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold">Standort (optional)</label>
                                <select name="location_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Alle Häfen</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= (int) $loc['id'] ?>" <?= ($selectedLocationId === (int) $loc['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $loc['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">
                                <i class="bi bi-arrow-repeat me-1"></i> Freie Slots aktualisieren
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Anzeige der freien Slots -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center;">2</span>
                        <span>Verfügbare Zeitfenster</span>
                    </h5>

                    <?php if (empty($selectedServiceId)): ?>
                        <div class="alert alert-light border small text-center py-3 text-muted">
                            <i class="bi bi-arrow-up-circle me-1"></i> Bitte wählen Sie zuerst eine Leistung aus.
                        </div>
                    <?php elseif (empty($freeSlots)): ?>
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark small py-3">
                            <div class="fw-bold"><i class="bi bi-info-circle me-1"></i> Keine freien Slots am <?= date('d.m.Y', strtotime($selectedDate)) ?></div>
                            Für dieses Datum sind alle Boote oder Instruktoren bereits ausgebucht. Bitte wählen Sie ein anderes Datum aus.
                        </div>
                    <?php else: ?>
                        <p class="small text-muted mb-3">
                            Klicken Sie auf Ihren bevorzugten Slot für den <strong><?= date('d.m.Y', strtotime($selectedDate)) ?></strong>:
                        </p>

                        <div class="d-flex flex-wrap gap-2" id="slotButtonContainer">
                            <?php foreach ($freeSlots as $slot): ?>
                                <?php
                                $isCurrentActive = ($selectedSlotStart === $slot['start_time']);
                                ?>
                                <button type="button" 
                                        class="btn <?= $isCurrentActive ? 'btn-primary' : 'btn-outline-primary' ?> slot-select-btn px-3 py-2 rounded-3 text-start"
                                        data-start="<?= htmlspecialchars($slot['start_time'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-end="<?= htmlspecialchars($slot['end_time'], ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="selectSlot('<?= htmlspecialchars($slot['start_time'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($slot['end_time'], ENT_QUOTES, 'UTF-8') ?>')">
                                    <div class="fw-bold"><i class="bi bi-clock me-1"></i> <?= htmlspecialchars($slot['start_time'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($slot['end_time'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small opacity-75" style="font-size: 0.75rem;">Frei &bull; <?= (int) $slot['duration_minutes'] ?> Min</div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rechte Spalte: Kundendaten & Buchungsabschluss -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center;">3</span>
                        <span>Ihre Kontaktdaten &amp; Abschluss</span>
                    </h5>

                    <!-- Ausgewählter Slot Zusammenfassung -->
                    <div class="p-3 bg-light rounded-3 mb-3 border" id="selectedSlotInfo">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Ausgewählter Termin:</div>
                                <div class="fw-bold text-dark" id="slotSummaryText">
                                    <?php if ($selectedSlotStart): ?>
                                        <?= date('d.m.Y', strtotime($selectedDate)) ?> &bull; <?= htmlspecialchars($selectedSlotStart) ?> - <?= htmlspecialchars($selectedSlotEnd ?? '') ?> Uhr
                                    <?php else: ?>
                                        <span class="text-muted fw-normal">Noch kein Slot ausgewählt</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-primary rounded-pill px-3 py-2" id="slotStatusBadge">
                                <?= $selectedSlotStart ? 'Slot reserviert' : 'Bitte wählen' ?>
                            </span>
                        </div>
                    </div>

                    <form method="post" action="?route=bootschule/book" id="bookingSubmitForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="service_id" value="<?= (int) ($selectedServiceId ?? 0) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="location_id" value="<?= (int) ($selectedLocationId ?? 0) ?>">
                        <input type="hidden" name="start_time" id="hiddenStartTime" value="<?= htmlspecialchars($selectedSlotStart ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="end_time" id="hiddenEndTime" value="<?= htmlspecialchars($selectedSlotEnd ?? '', ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Vorname <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required placeholder="z. B. Marc">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Nachname <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required placeholder="z. B. Meier">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">E-Mail-Adresse <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="marc.meier@beispiel.ch">
                            <div class="form-text" style="font-size: 0.75rem;">
                                Sie erhalten Ihre Buchungsbestätigung an diese Adresse.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Telefon / Mobilnummer <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="+41 79 123 45 67">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Angestrebter Führerschein / Vorkenntnisse</label>
                            <select name="target_license" class="form-select">
                                <option value="">-- Keine Angabe --</option>
                                <option value="Kat. A Motorboot">Motorboot Führerschein (Kat. A)</option>
                                <option value="Kat. D Segelschiff">Segelschiff Führerschein (Kat. D)</option>
                                <option value="Bootsmiete mit Ausweis">Besitze bereits Bootsführerschein (Miete)</option>
                                <option value="Auffrischungstraining">Skipper-Training / Auffrischung</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Bemerkungen / Wünsche (optional)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="z. B. Bevorzugter Hafen, spezielle Manöver-Wünsche etc."></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold" id="submitBookingBtn" <?= empty($selectedSlotStart) ? 'disabled' : '' ?>>
                                <i class="bi bi-send-check me-2"></i> Verbindlich anfragen
                            </button>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-shield-lock me-1"></i> Kostenlose Stornierung bis 24h vor dem Termin möglich.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectSlot(start, end) {
    document.getElementById('hiddenStartTime').value = start;
    document.getElementById('hiddenEndTime').value = end;

    const dateVal = document.getElementById('dateInput').value;
    const dateFormatted = new Date(dateVal + 'T00:00:00').toLocaleDateString('de-CH');

    document.getElementById('slotSummaryText').innerHTML = '<strong>' + dateFormatted + '</strong> &bull; ' + start + ' - ' + end + ' Uhr';
    document.getElementById('slotStatusBadge').className = 'badge bg-success rounded-pill px-3 py-2';
    document.getElementById('slotStatusBadge').innerText = 'Slot ausgewählt';

    document.getElementById('submitBookingBtn').disabled = false;

    // Buttons hervorheben
    const buttons = document.querySelectorAll('.slot-select-btn');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-start') === start) {
            btn.className = 'btn btn-primary slot-select-btn px-3 py-2 rounded-3 text-start shadow-sm';
        } else {
            btn.className = 'btn btn-outline-primary slot-select-btn px-3 py-2 rounded-3 text-start';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
