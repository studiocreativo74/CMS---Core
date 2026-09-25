<?php

declare(strict_types=1);

/**
 * Admin: Detailansicht eines Sicherungsvorgangs (/admin/secure/cases/view)
 *
 * @var array<string, mixed> $case
 * @var array<int, array<string, mixed>> $caseLogs
 * @var bool $canManage
 */

$title = 'Vorgang ' . htmlspecialchars((string) ($case['case_number'] ?? ''), ENT_QUOTES, 'UTF-8');
$activeNav = 'admin/secure/cases';

$caseId = (int) ($case['id'] ?? 0);
$caseNumber = (string) ($case['case_number'] ?? '');
$status = (string) ($case['status'] ?? 'new');
$statusLabel = (string) ($case['status_label'] ?? ucfirst($status));
$statusBadge = (string) ($case['status_badge'] ?? 'bg-secondary');

$secType = (string) ($case['securing_type'] ?? 'VIDEO');
$secTypeLabel = (string) ($case['securing_type_label'] ?? $secType);
$meta = (array) ($case['securing_meta_decoded'] ?? []);

$statuses = SecurePortalRepository::STATUSES;
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

ob_start();
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="?route=admin/secure/cases" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Zurück zur Liste
            </a>
            <span class="badge bg-light text-dark border font-monospace">ID: <?= htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1 class="h3 fw-bold text-dark mb-0">
            Aktenzeichen: <?= htmlspecialchars((string) ($case['reference_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <small class="text-muted">
            Behörde: <strong><?= htmlspecialchars((string) ($case['police_department'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> &middot; 
            Sachbearbeiter: <?= htmlspecialchars((string) ($case['contact_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </small>
    </div>

    <div class="d-flex gap-2">
        <a href="?route=admin/secure/cases/download-warrant&id=<?= $caseId ?>" class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Editionsverfügung herunterladen
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Linke Spalte: Vorgangsdetails & Technische Spezifikation -->
    <div class="col-lg-7">

        <!-- 1. Basis- und Kontaktdaten -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-building text-primary me-2"></i> Behörden- &amp; Kontaktdaten
                </h5>
                <span class="small text-muted">
                    Eingang: <?= date('d.m.Y H:i', strtotime((string)$case['created_at'])) ?> Uhr
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Dienststelle</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($case['police_department'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Sachbearbeiter</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($case['contact_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Dienstliche E-Mail</label>
                        <a href="mailto:<?= htmlspecialchars((string) ($case['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                            <?= htmlspecialchars((string) ($case['contact_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Telefonnummer</label>
                        <a href="tel:<?= htmlspecialchars((string) ($case['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                            <?= htmlspecialchars((string) ($case['contact_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Behördliches Aktenzeichen</label>
                        <span class="badge bg-light text-dark border fs-6 font-monospace">
                            <?= htmlspecialchars((string) ($case['reference_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Gewünschter Sicherungstermin</label>
                        <div class="fw-semibold <?= !empty($case['desired_date']) ? 'text-primary' : 'text-muted' ?>">
                            <?= !empty($case['desired_date']) ? date('d.m.Y', strtotime((string)$case['desired_date'])) : 'Keine spezifische Frist' ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold d-block text-uppercase">Beschreibung des Sicherungsumfangs</label>
                    <div class="p-3 bg-light rounded border text-dark">
                        <?= nl2br(htmlspecialchars((string) ($case['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                </div>

                <?php if (!empty($case['remarks'])): ?>
                    <div>
                        <label class="text-muted small fw-bold d-block text-uppercase">Besondere Hinweise / Bemerkungen</label>
                        <div class="p-2 bg-light rounded text-muted small border">
                            <?= nl2br(htmlspecialchars((string) $case['remarks'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Technische Spezifikation -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-hdd-network text-primary me-2"></i> Art der Sicherung: <?= htmlspecialchars($secTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <span class="badge bg-primary-subtle text-primary border">
                    <?= htmlspecialchars($secType, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="card-body p-4">
                <?php if ($secType === 'VIDEO'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Sicherungszeitraum VON</label>
                            <div class="p-2 bg-light rounded border font-monospace">
                                <?= !empty($meta['timeframe_from']) ? htmlspecialchars((string) $meta['timeframe_from'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Sicherungszeitraum BIS</label>
                            <div class="p-2 bg-light rounded border font-monospace">
                                <?= !empty($meta['timeframe_to']) ? htmlspecialchars((string) $meta['timeframe_to'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold d-block text-uppercase">Kamera-Standorte / Bereiche</label>
                            <div class="fw-semibold text-dark">
                                <?= htmlspecialchars((string) ($meta['camera_location'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <?php if (!empty($meta['video_notes'])): ?>
                            <div class="col-12">
                                <label class="text-muted small fw-bold d-block text-uppercase">Personen- / Fahrzeugmerkmale</label>
                                <div class="p-2 bg-light rounded text-muted small border">
                                    <?= nl2br(htmlspecialchars((string) $meta['video_notes'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($secType === 'MAIL'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Postfach / E-Mail</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['mailbox_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Umfang</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_scope'] ?? 'Vollständig'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum von</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_timeframe_from'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum bis</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_timeframe_to'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>

                <?php elseif ($secType === 'CLOUD'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">System / Server</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['system_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zieldateien / Pfade / Accounts</label>
                            <div class="p-2 bg-light rounded font-monospace small border">
                                <?= nl2br(htmlspecialchars((string) ($meta['cloud_target_data'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($secType === 'ACCESS_LOG'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Türen / Zutrittspunkte</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['doors_points'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum</label>
                            <div><?= htmlspecialchars((string) ($meta['log_timeframe'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if (!empty($meta['card_ids'])): ?>
                            <div class="col-12">
                                <label class="text-muted small fw-bold d-block text-uppercase">Transponder- / Kartennummern</label>
                                <div class="font-monospace small"><?= htmlspecialchars((string) $meta['card_ids'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div>
                        <label class="text-muted small fw-bold d-block text-uppercase">Spezifikation</label>
                        <div class="p-3 bg-light rounded border">
                            <?= nl2br(htmlspecialchars((string) ($meta['other_details'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Rechte Spalte: Status ändern, Notizen & Audit-Log -->
    <div class="col-lg-5">

        <!-- Editionsverfügung & Fall-Zugangscode -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-shield-lock text-primary me-2"></i> Dokumente &amp; Zugangsdaten
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                        <div>
                            <div class="fw-bold text-dark">Editionsverfügung (PDF)</div>
                            <small class="text-muted">
                                <?= number_format(((int)($case['warrant_file_size'] ?? 0)) / 1024 / 1024, 2) ?> MB &middot; 
                                Hochgeladen <?= date('d.m.Y H:i', strtotime((string)$case['warrant_uploaded_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <a href="?route=admin/secure/cases/download-warrant&id=<?= $caseId ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                        Download
                    </a>
                </div>

                <div class="p-3 bg-dark text-white rounded text-center">
                    <small class="text-white-50 text-uppercase d-block mb-1">Fall-Zugangscode (Antragsteller)</small>
                    <div class="font-monospace fs-5 fw-bold text-warning letter-spacing-1">
                        <?= htmlspecialchars((string) ($case['access_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <small class="text-white-50" style="font-size: 0.75rem;">
                        Nur zur behördlichen Authentifizierung bei telefonischen Rückfragen
                    </small>
                </div>
            </div>
        </div>

        <!-- Status & Bearbeitung -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-pencil-square text-primary me-2"></i> Status aktualisieren &amp; Notizen
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="?route=admin/secure/cases/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= $caseId ?>">

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold text-dark">Vorgangsstatus</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statuses as $stKey => $stInfo): ?>
                                <option value="<?= $stKey ?>" <?= $status === $stKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="internal_notes" class="form-label fw-semibold text-dark">Interne Bearbeitungsnotizen</label>
                        <textarea name="internal_notes" id="internal_notes" rows="3" class="form-control" 
                                  placeholder="Nur für Technik und Verwaltung sichtbar..."><?= htmlspecialchars((string) ($case['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Wird dem Antragsteller niemals angezeigt.</div>
                    </div>

                    <div class="mb-3">
                        <label for="log_message" class="form-label fw-semibold text-dark">Protokoll-Eintrag / Mitteilung verfassen</label>
                        <textarea name="log_message" id="log_message" rows="2" class="form-control" 
                                  placeholder="z. B. Sicherungsdaten auf Transfer-Medium aufgespielt..."></textarea>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="log_is_public" id="log_is_public" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="log_is_public">
                            Mitteilung im Fallzugang für Antragsteller sichtbar machen
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Änderungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- Verlaufsprotokoll (Audit Trail) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i> Aktivitäten- &amp; Statusprotokoll
                </h5>
                <span class="badge bg-secondary-subtle text-secondary"><?= count($caseLogs) ?></span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($caseLogs)): ?>
                    <div class="text-center text-muted small py-3">Keine Protokolleinträge vorhanden.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($caseLogs as $log): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge <?= !empty($log['is_internal']) ? 'bg-secondary' : 'bg-success-subtle text-success border' ?> small">
                                        <?= !empty($log['is_internal']) ? 'Intern' : 'Öffentlich sichtbar' ?>
                                    </span>
                                    <small class="text-muted"><?= date('d.m.Y H:i', strtotime((string)$log['created_at'])) ?></small>
                                </div>
                                <div class="text-dark small mb-1">
                                    <?= htmlspecialchars((string)$log['message'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Von: <?= htmlspecialchars((string)$log['author'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
