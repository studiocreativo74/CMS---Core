<?php

declare(strict_types=1);

/**
 * Admin: Account-Anfragen & Profil-Zuweisung
 *
 * Ermöglicht dem Administrator, eingegangene Registrierungsanfragen direkt in der
 * einheitlichen CMS-Adminoberfläche zu prüfen, abzulehnen oder zu bestätigen
 * und ihnen das gewünschte Profil zuzuweisen (Fahrlehrer, Schüler, Bootsmieter oder Admin).
 *
 * @var array<int, array<string, mixed>> $requests
 * @var string $currentStatusFilter ('pending', 'approved', 'rejected', 'all')
 * @var int $pendingCount
 */

$title = 'Account-Anfragen & Freigaben - Nautic';
$currentRoute = 'admin/boating/account-requests';
$navTitle = 'Nautic Account-Anfragen & Freigaben';
$navSubtitle = 'Registrierungsanfragen prüfen, Profil zuweisen und Benutzerzugänge freischalten.';
$breadcrumbCurrent = 'Account-Anfragen';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ob_start();
?>

<!-- Modul-Header mit Navigation -->
<?php require __DIR__ . '/_nautic_nav.php'; ?>

<!-- Flash Meldungen -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div><?= htmlspecialchars((string) $flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schliessen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div><?= htmlspecialchars((string) $flashError, ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schliessen"></button>
    </div>
<?php endif; ?>

<!-- Tabs zur schnellen Navigation zwischen Kunden-CRM und Account-Anfragen -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <ul class="nav nav-pills bg-white p-1.5 rounded-4 shadow-sm border">
        <li class="nav-item">
            <a class="nav-link rounded-pill text-muted px-3 py-1.5 fw-semibold" href="?route=admin/boating/customers">
                <i class="bi bi-people me-1"></i> Kunden- &amp; Schülerkartei (CRM)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active rounded-pill px-3 py-1.5 fw-bold" href="?route=admin/boating/account-requests">
                <i class="bi bi-person-check-fill me-1"></i> Account-Anfragen &amp; Freigaben
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark ms-1.5"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/boating/customers" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Zur Kundenkartei
        </a>
    </div>
</div>

<!-- Haupt-Card: Anfragen-Übersicht -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-person-check text-primary"></i>
                Eingegangene Registrierungen
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark font-monospace small"><?= $pendingCount ?> ausstehend</span>
                <?php endif; ?>
            </h5>
            <p class="text-muted small mb-0">
                Weisen Sie neuen Benutzern direkt das passende Profil zu (Fahrlehrer, Schüler, Mieter oder Admin) und schalten Sie diese mit 1-Klick frei.
            </p>
        </div>

        <!-- Status Filter Tabs -->
        <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border">
            <a href="?route=admin/boating/account-requests&status=pending" class="btn rounded-pill px-3 <?= $currentStatusFilter === 'pending' ? 'btn-primary' : 'btn-light text-muted' ?>">
                Ausstehend
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?route=admin/boating/account-requests&status=approved" class="btn rounded-pill px-3 <?= $currentStatusFilter === 'approved' ? 'btn-primary' : 'btn-light text-muted' ?>">
                Bestätigt
            </a>
            <a href="?route=admin/boating/account-requests&status=rejected" class="btn rounded-pill px-3 <?= $currentStatusFilter === 'rejected' ? 'btn-primary' : 'btn-light text-muted' ?>">
                Abgelehnt
            </a>
            <a href="?route=admin/boating/account-requests&status=all" class="btn rounded-pill px-3 <?= $currentStatusFilter === 'all' ? 'btn-primary' : 'btn-light text-muted' ?>">
                Alle
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                <h6 class="fw-bold text-dark">Keine Anfragen in diesem Filter</h6>
                <p class="small mb-0">Neue Registrierungen über die Nautic-Logonseite erscheinen automatisch hier.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <tr>
                            <th class="ps-4 py-3">Antragsteller</th>
                            <th class="py-3">Kontakt</th>
                            <th class="py-3">Gewünschtes Profil</th>
                            <th class="py-3">Eingang</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Aktion &amp; Zuweisung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): 
                            $isPending  = ($req['status'] === 'pending');
                            $isApproved = ($req['status'] === 'approved');
                            $isRejected = ($req['status'] === 'rejected');
                            $desired    = (string) ($req['desired_role'] ?? 'student');
                        ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($req['notes'])): ?>
                                        <div class="text-muted small mt-0.5">
                                            <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($req['notes'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <div class="font-monospace small">
                                        <a href="mailto:<?= htmlspecialchars($req['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($req['email'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($req['phone'])): ?>
                                        <div class="text-muted small">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($req['phone'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-light text-dark border">
                                        <?= match ($desired) {
                                            'instructor' => 'Fahrcoach / Skipper',
                                            'renter'     => 'Bootsmieter / Charter',
                                            'admin'      => 'Flottenleitung / Admin',
                                            default      => 'Fahrschüler (Kat. A / D)',
                                        } ?>
                                    </span>
                                </td>
                                <td class="py-3 small text-muted">
                                    <?= htmlspecialchars(date('d.m.Y H:i', strtotime($req['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-3">
                                    <?php if ($isPending): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1">
                                            <i class="bi bi-clock me-1"></i> Ausstehend
                                        </span>
                                    <?php elseif ($isApproved): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                            <i class="bi bi-check-circle me-1"></i> Bestätigt als <?= htmlspecialchars(ucfirst((string) ($req['assigned_profile'] ?? $desired)), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1">
                                            <i class="bi bi-x-circle me-1"></i> Abgelehnt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <?php if ($isPending): ?>
                                        <form method="POST" action="?route=admin/boating/account-requests/approve" class="d-inline-flex align-items-center gap-1.5">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">

                                            <select name="profile_type" class="form-select form-select-sm py-1 px-2 rounded-2" style="width: auto; font-size: 0.8rem;">
                                                <option value="instructor" <?= $desired === 'instructor' ? 'selected' : '' ?>>Fahrlehrer / Coach</option>
                                                <option value="student" <?= $desired === 'student' ? 'selected' : '' ?>>Schüler (Fahrschule)</option>
                                                <option value="renter" <?= $desired === 'renter' ? 'selected' : '' ?>>Bootsmieter (Charter)</option>
                                                <option value="admin" <?= $desired === 'admin' ? 'selected' : '' ?>>Flottenleiter (Admin)</option>
                                            </select>

                                            <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm px-2.5" title="Account bestätigen und Profil zuweisen">
                                                <i class="bi bi-check-lg"></i>
                                                <span>Freischalten</span>
                                            </button>
                                        </form>

                                        <form method="POST" action="?route=admin/boating/account-requests/reject" class="d-inline ms-1" onsubmit="return confirm('Möchten Sie diese Anfrage wirklich ablehnen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Anfrage ablehnen">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            Geprüft am <?= htmlspecialchars(date('d.m.Y', strtotime($req['approved_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
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
