<?php

declare(strict_types=1);

/**
 * Admin: Account-Anfragen & Profil-Zuweisung
 *
 * Ermöglicht dem Administrator, eingegangene Registrierungsanfragen zu prüfen,
 * abzulehnen oder zu bestätigen und ihnen das gewünschte Profil zuzuweisen
 * (Fahrlehrer, Schüler, Bootsmieter oder Admin).
 *
 * @var array<int, array<string, mixed>> $requests
 * @var string $currentStatusFilter ('pending', 'approved', 'rejected', 'all')
 * @var int $pendingCount
 */

$currentRoute = 'admin/boating/account-requests';
$navTitle = 'Nautic Account-Anfragen & Freigaben';
$navSubtitle = 'Registrierungsanfragen prüfen, Profil zuweisen und Benutzerzugänge freischalten.';
$breadcrumbCurrent = 'Account-Anfragen';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($navTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body class="py-4">
    <div class="container-fluid px-3 px-md-5">

        <!-- Modul-Header mit Navigation -->
        <?php require __DIR__ . '/_nautic_nav.php'; ?>

        <!-- Flash Meldungen -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars((string) $_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Haupt-Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-check text-primary"></i>
                        Eingegangene Registrierungen
                    </h5>
                    <p class="text-muted small mb-0">
                        Weisen Sie jedem Benutzer das passende Profil zu (Fahrlehrer, Schüler, Mieter oder Flottenleitung).
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
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-slate-300"></i>
                        <h6>Keine Account-Anfragen in dieser Ansicht</h6>
                        <p class="small mb-0">Neue Anfragen von der Nautic-Logonseite erscheinen automatisch hier.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Antragsteller</th>
                                    <th>Kontakt</th>
                                    <th>Gewünschtes Profil</th>
                                    <th>Eingereicht am</th>
                                    <th>Status / Profil</th>
                                    <th class="pe-4 text-end">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): 
                                    $isPending = ($req['status'] === 'pending');
                                    $isApproved = ($req['status'] === 'approved');
                                    $isRejected = ($req['status'] === 'rejected');
                                    $desired = (string) ($req['desired_role'] ?? 'student');
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">
                                                <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <?php if (!empty($req['notes'])): ?>
                                                <div class="text-muted small mt-0.5">
                                                    <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($req['notes'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
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
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= match ($desired) {
                                                    'instructor' => 'Fahrcoach / Skipper',
                                                    'renter'     => 'Bootsmieter / Charter',
                                                    'admin'      => 'Flottenleitung / Admin',
                                                    default      => 'Fahrschüler (Kat. A / D)',
                                                } ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars(date('d.m.Y H:i', strtotime($req['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <?php if ($isPending): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1">
                                                    Ausstehend
                                                </span>
                                            <?php elseif ($isApproved): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                                    Bestätigt als <?= htmlspecialchars(ucfirst((string) $req['assigned_profile']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1">
                                                    Abgelehnt
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($isPending): ?>
                                                <form method="POST" action="?route=admin/boating/account-requests/approve" class="d-inline-flex align-items-center gap-1">
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
                                                        <span>Bestätigen</span>
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
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
