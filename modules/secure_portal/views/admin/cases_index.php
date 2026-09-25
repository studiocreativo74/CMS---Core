<?php

declare(strict_types=1);

/**
 * Admin: Sicherungsvorgänge Übersicht (/admin/secure/cases)
 *
 * @var array<int, array<string, mixed>> $cases
 * @var array<string, int> $stats
 * @var int $totalCases
 * @var array<string, mixed> $filters
 */

$title = 'Sicherungsvorgänge (Polizei & StA)';
$activeNav = 'admin/secure/cases';

$currentStatus = (string) ($filters['status'] ?? '');
$currentType = (string) ($filters['securing_type'] ?? '');
$search = (string) ($filters['search'] ?? '');

$statuses = SecurePortalRepository::STATUSES;
$securingTypes = SecurePortalRepository::SECURING_TYPES;

ob_start();
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-dark">Sicherungsvorgänge</h1>
        <p class="text-muted small mb-0">
            Übersicht aller eingereichten Sicherungsanträge, Editionsverfügungen und technischer Bereitstellungen.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=sicherung/antrag" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Öffentliches Antragsformular
        </a>
        <a href="?route=sicherung" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i> Portal-Startseite
        </a>
    </div>
</div>

<!-- Stat-Kacheln -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === '' ? 'border-primary border-2' : '' ?>">
            <div class="text-muted small text-uppercase fw-semibold mb-1">Alle Vorgänge</div>
            <div class="fs-3 fw-bold text-dark"><?= $stats['total'] ?? 0 ?></div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases&status=new" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === 'new' ? 'border-primary border-2' : '' ?>">
            <div class="text-primary small text-uppercase fw-semibold mb-1">Neu eingegangen</div>
            <div class="fs-3 fw-bold text-primary"><?= $stats['new'] ?? 0 ?></div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases&status=in_review" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === 'in_review' ? 'border-primary border-2' : '' ?>">
            <div class="text-warning small text-uppercase fw-semibold mb-1">In Prüfung</div>
            <div class="fs-3 fw-bold text-warning"><?= $stats['in_review'] ?? 0 ?></div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases&status=in_progress" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === 'in_progress' ? 'border-primary border-2' : '' ?>">
            <div class="text-info small text-uppercase fw-semibold mb-1">In Bearbeitung</div>
            <div class="fs-3 fw-bold text-info"><?= $stats['in_progress'] ?? 0 ?></div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases&status=available" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === 'available' ? 'border-primary border-2' : '' ?>">
            <div class="text-success small text-uppercase fw-semibold mb-1">Bereitgestellt</div>
            <div class="fs-3 fw-bold text-success"><?= $stats['available'] ?? 0 ?></div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <a href="?route=admin/secure/cases&status=clarification_required" class="card border-0 shadow-sm text-decoration-none h-100 text-center p-3 <?= $currentStatus === 'clarification_required' ? 'border-primary border-2' : '' ?>">
            <div class="text-danger small text-uppercase fw-semibold mb-1">Rückfragen</div>
            <div class="fs-3 fw-bold text-danger"><?= $stats['clarification_required'] ?? 0 ?></div>
        </a>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/secure/cases">

            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Suche nach ID, Aktenzeichen, Dienststelle, Name...">
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Alle Status --</option>
                    <?php foreach ($statuses as $stKey => $stInfo): ?>
                        <option value="<?= $stKey ?>" <?= $currentStatus === $stKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="securing_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Alle Sicherungsarten --</option>
                    <?php foreach ($securingTypes as $typeKey => $tInfo): ?>
                        <option value="<?= $typeKey ?>" <?= $currentType === $typeKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tInfo['short'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Filtern</button>
                <?php if ($search !== '' || $currentStatus !== '' || $currentType !== ''): ?>
                    <a href="?route=admin/secure/cases" class="btn btn-outline-secondary btn-sm" title="Filter zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Vorgangstabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-semibold text-dark">
            Gefundene Vorgänge <span class="badge bg-secondary-subtle text-secondary ms-1"><?= $totalCases ?></span>
        </h5>
    </div>

    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-folder2-open display-4 opacity-50 mb-3 d-block"></i>
                <h6 class="fw-bold text-dark">Keine Sicherungsvorgänge gefunden</h6>
                <p class="small max-w-md mx-auto mb-0" style="max-width: 460px;">
                    <?= ($search !== '' || $currentStatus !== '' || $currentType !== '') 
                        ? 'Für die gewählten Filterkriterien wurden keine Einträge gefunden. Setzen Sie die Filter zurück.' 
                        : 'Bisher wurden noch keine Anträge über das Sicherungsportal eingereicht.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 170px;">Vorgangs-ID</th>
                            <th>Dienststelle &amp; Sachbearbeiter</th>
                            <th>Aktenzeichen</th>
                            <th style="width: 160px;">Sicherungsart</th>
                            <th style="width: 140px;">Status</th>
                            <th style="width: 130px;">Wunschtermin</th>
                            <th style="width: 130px;">Eingang</th>
                            <th class="text-end" style="width: 110px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): ?>
                            <tr>
                                <td>
                                    <a href="?route=admin/secure/cases/view&id=<?= (int)$c['id'] ?>" class="font-monospace fw-bold text-primary text-decoration-none">
                                        <?= htmlspecialchars((string) $c['case_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $c['police_department'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string) $c['contact_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars((string) $c['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border d-inline-flex align-items-center gap-1">
                                        <i class="bi <?= htmlspecialchars((string) ($c['securing_type_icon'] ?? 'bi-folder'), ENT_QUOTES, 'UTF-8') ?>"></i>
                                        <?= htmlspecialchars((string) ($c['securing_type_short'] ?? $c['securing_type']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= htmlspecialchars((string) ($c['status_badge'] ?? 'bg-secondary'), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) ($c['status_label'] ?? $c['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($c['desired_date']) ? date('d.m.Y', strtotime((string)$c['desired_date'])) : '-' ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d.m.Y H:i', strtotime((string)$c['created_at'])) ?>
                                </td>
                                <td class="text-end">
                                    <a href="?route=admin/secure/cases/view&id=<?= (int)$c['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="Details öffnen">
                                        Öffnen &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
