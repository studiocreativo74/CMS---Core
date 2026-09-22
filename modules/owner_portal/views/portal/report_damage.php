<?php

declare(strict_types=1);

/**
 * Eigentümer- & Mieterportal: Schadenmeldung erfassen
 *
 * @var array<int, array<string, mixed>> $userUnits
 * @var array<int, array<string, mixed>> $properties
 */

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$title = 'Schadenmeldung erfassen';
$currentRoute = 'portal/damage/report';

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=portal/dashboard" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item active" aria-current="page">Schaden melden</li>
        </ol>
    </nav>
    <a href="?route=portal/dashboard" class="btn btn-sm btn-outline-secondary">
        &larr; Zurück zum Dashboard
    </a>
</div>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-danger bg-opacity-10 text-danger rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-tools" viewBox="0 0 16 16">
                            <path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.878-.851l2.654-2.617.968.968-.305.914a1 1 0 0 0 .242 1.023l3.27 3.27a.997.997 0 0 0 1.414 0l1.586-1.586a.997.997 0 0 0 0-1.414l-3.27-3.27a1 1 0 0 0-1.023-.242L10.5 9.5l-.969-.969 2.675-2.675a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419L16 1 15 0 11.919 2.2a1 1 0 0 0-.419.815v.07a1 1 0 0 1-.293.708L8.532 6.468 5.857 3.793l.676-.676a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419z"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Neue Schadenmeldung erfassen</h5>
                        <p class="text-muted small mb-0">Informieren Sie die Hausverwaltung über Mängel oder notwendige Reparaturen.</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="?route=portal/damage/submit" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <!-- Liegenschaft & Einheit Auswahl -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="selectProperty" class="form-label fw-semibold">Liegenschaft <span class="text-danger">*</span></label>
                            <select name="property_id" id="selectProperty" class="form-select" required>
                                <option value="">-- Bitte Liegenschaft wählen --</option>
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>">
                                        <?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($p['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="selectUnit" class="form-label fw-semibold">Betroffene Einheit / Wohnung</label>
                            <select name="unit_id" id="selectUnit" class="form-select">
                                <option value="">Gemeinschaftseigentum / Haus allgemein</option>
                                <?php foreach ($userUnits as $uu): ?>
                                    <option value="<?= (int) $uu['unit_id'] ?>" data-prop="<?= (int) $uu['property_id'] ?>">
                                        WE <?= htmlspecialchars((string) $uu['unit_number'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $uu['property_name'], ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Titel & Schadenort -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="damageTitle" class="form-label fw-semibold">Kurze Schadenbezeichnung <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="damageTitle" class="form-control" placeholder="z. B. Wasserfleck Decke Badezimmer oder Klingel defekt" required>
                        </div>
                        <div class="col-md-5">
                            <label for="damageLocation" class="form-label fw-semibold">Genauer Ort / Raum</label>
                            <input type="text" name="damage_location" id="damageLocation" class="form-control" placeholder="z. B. Küche unter Spüle, Kellerabteil 3">
                        </div>
                    </div>

                    <!-- Dringlichkeit -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dringlichkeit</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="prioNormal" value="normal" checked>
                                <label class="form-check-label" for="prioNormal">
                                    <strong>Normal</strong> (Reguläre Behebung)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="prioHigh" value="high">
                                <label class="form-check-label text-warning" for="prioHigh">
                                    <strong>Hoch</strong> (Zeitnahe Prüfung erforderlich)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="prioUrgent" value="urgent">
                                <label class="form-check-label text-danger" for="prioUrgent">
                                    <strong>Notfall / Dringend</strong> (z.B. Rohrbruch, totaler Heizungsausfall)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Beschreibung -->
                    <div class="mb-3">
                        <label for="damageDescription" class="form-label fw-semibold">Ausführliche Beschreibung des Schadens</label>
                        <textarea name="description" id="damageDescription" rows="4" class="form-control" placeholder="Bitte beschreiben Sie so genau wie möglich: Wann ist der Schaden aufgetreten? Was genau ist passiert? Sind Folgeschäden zu befürchten?"></textarea>
                    </div>

                    <!-- Foto- / Dokumenten-Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Schadensfoto oder Beleg anhängen (DMS)</label>
                        <div class="p-3 border rounded bg-light">
                            <input type="file" name="attachment" id="damageAttachment" class="form-control form-control-sm mb-2" accept="image/*,.pdf">
                            <div class="text-muted small" style="font-size: 0.75rem;">
                                Unterstützt Bilder (JPG, PNG, WebP) und PDF-Dokumente bis max. 10 MB. Die Datei wird sicher im zentralen Dokumentenarchiv abgelegt.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="?route=portal/dashboard" class="btn btn-secondary">Abbrechen</a>
                        <button type="submit" class="btn btn-danger fw-semibold px-4">
                            Schadenmeldung absenden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
