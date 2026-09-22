<?php

declare(strict_types=1);

/**
 * Admin: Liegenschaften Übersicht (Properties)
 *
 * @var array<int, array<string, mixed>> $properties
 * @var int $totalProperties
 * @var string $search
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Liegenschaften (Eigentümerportal)';
$currentRoute = 'admin/portal/properties';

$canManage = PortalService::canManageProperties();

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger me-2" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if (!$tablesCreated): ?>
    <div class="card border-warning border-start border-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-database-exclamation" viewBox="0 0 16 16">
                        <path d="M8 1c-1.573 0-3.022.289-4.096.777C2.823 2.269 2 2.99 2 4s.823 1.731 1.904 2.223C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777C13.177 5.731 14 5.01 14 4s-.823-1.731-1.904-2.223C11.022 1.289 9.573 1 8 1"/>
                        <path d="M2 7v-.839c.457.432 1.004.751 1.49.972C4.601 7.649 6.222 8 8 8s3.399-.35 4.51-.867c.486-.22 1.033-.54 1.49-.972V7c0 .121-.012.237-.035.352a4 4 0 0 0-1.214-.464c-.787-.14-1.63-.223-2.541-.223-1.573 0-3.022.289-4.096.777C5.022 7.93 4.223 8.651 4.223 9.661V10c0 .121-.012.237-.035.352A4 4 0 0 0 2 10.352V10c0-.121.012-.237.035-.352A4 4 0 0 1 2 9.661V7z"/>
                        <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1.5a.5.5 0 0 0 1 0V11a.5.5 0 0 0-.5-.5m0 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                    </svg>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-1">Eigentümerportal-Datenbanktabellen noch ausstehend</h5>
                    <p class="text-muted small mb-2">
                        Die Migration <code>006_create_owner_portal_schema.sql</code> für Liegenschaften, Einheiten, Zuweisungen und Cases wurde noch nicht in MySQL ausgeführt.
                    </p>
                    <a href="?route=admin/system" class="btn btn-sm btn-warning fw-semibold">
                        Zu System &amp; Migrationen
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Obere Navigations- & Aktionsleiste -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Liegenschaftsverwaltung</h4>
        <p class="text-muted small mb-0">Verwaltung von Immobilien, WEG-Objekten, zugeordneten Einheiten und Akten.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/portal/cases" class="btn btn-sm btn-outline-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-folder2-open me-1" viewBox="0 0 16 16">
                <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14z"/>
            </svg>
            Alle Vorgänge (Cases)
        </a>
        <a href="?route=portal/dashboard" class="btn btn-sm btn-outline-primary" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-arrow-up-right me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
            </svg>
            Portal-Ansicht öffnen
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createPropertyModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg>
                Liegenschaft anlegen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Suchleiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/portal/properties">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-search text-muted" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                    </span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Liegenschaftsname, Adresse, Ort, Verw.-Nr..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Suchen</button>
                <?php if ($search !== ''): ?>
                    <a href="?route=admin/portal/properties" class="btn btn-sm btn-light text-muted">Zurücksetzen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Liste der Liegenschaften -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            Liegenschaften 
            <span class="badge bg-light text-muted border ms-1 fw-normal"><?= $totalProperties ?> gesamt</span>
        </h6>
    </div>

    <div class="card-body p-0">
        <?php if (empty($properties)): ?>
            <div class="text-center py-5">
                <div class="p-3 bg-light rounded-circle d-inline-flex mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-building text-muted" viewBox="0 0 16 16">
                        <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 8.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 11.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                        <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3z"/>
                    </svg>
                </div>
                <h6 class="fw-semibold text-dark">Noch keine Liegenschaften vorhanden</h6>
                <p class="text-muted small mb-3">Legen Sie Ihr erstes Immobilienobjekt an, um Einheiten, Eigentümer und Akten zu verwalten.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPropertyModal">
                        Liegenschaft anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Liegenschaft</th>
                            <th>Adresse</th>
                            <th>Verw.-Nr.</th>
                            <th>Einheiten</th>
                            <th>Offene Vorgänge</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $prop): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded me-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                                                <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 8.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM4 11.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                                                <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <a href="?route=admin/portal/property&id=<?= (int) $prop['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars((string) $prop['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars((string) ($prop['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars((string) ($prop['street'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>,
                                    <span class="text-muted"><?= htmlspecialchars((string) ($prop['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($prop['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($prop['external_ref'])): ?>
                                        <code><?= htmlspecialchars((string) $prop['external_ref'], ENT_QUOTES, 'UTF-8') ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= (int) ($prop['unit_count'] ?? 0) ?> Einheiten
                                    </span>
                                </td>
                                <td>
                                    <?php $openC = (int) ($prop['open_cases_count'] ?? 0); ?>
                                    <?php if ($openC > 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <?= $openC ?> offen
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            Keine offenen
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/portal/property&id=<?= (int) $prop['id'] ?>" class="btn btn-outline-primary" title="Details &amp; Einheiten">
                                            Verwalten &rarr;
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="POST" action="?route=admin/portal/property/delete" onsubmit="return confirm('Möchten Sie diese Liegenschaft samt Einheiten und Zuweisungen wirklich löschen?');" class="d-inline">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $prop['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Löschen">✕</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: NEUE LIEGENSCHAFT ANLEGEN                                          -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="createPropertyModal" tabindex="-1" aria-labelledby="createPropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/property/create">
                <?= Csrf::field() ?>

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="createPropertyModalLabel">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-building-add text-primary" viewBox="0 0 16 16">
                            <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0"/>
                            <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6.5a.5.5 0 0 1-1 0V1H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h1a.5.5 0 0 1 0 1H3a1 1 0 0 1-1-1z"/>
                        </svg>
                        Neue Liegenschaft anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="propName" class="form-label fw-semibold">Bezeichnung / Liegenschaftsname <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="propName" class="form-control" placeholder="z. B. WEG Parkstraße 14-16" required>
                        </div>
                        <div class="col-md-4">
                            <label for="propExtRef" class="form-label fw-semibold">Interne Verwaltungs-ID</label>
                            <input type="text" name="external_ref" id="propExtRef" class="form-control" placeholder="z. B. WEG-104">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="propStreet" class="form-label fw-semibold">Straße &amp; Hausnummer</label>
                            <input type="text" name="street" id="propStreet" class="form-control" placeholder="Parkstraße 14-16">
                        </div>
                        <div class="col-md-2">
                            <label for="propZip" class="form-label fw-semibold">PLZ</label>
                            <input type="text" name="zip" id="propZip" class="form-control" placeholder="80331">
                        </div>
                        <div class="col-md-4">
                            <label for="propCity" class="form-label fw-semibold">Ort / Stadt</label>
                            <input type="text" name="city" id="propCity" class="form-control" placeholder="München">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="propNotes" class="form-label fw-semibold">Notizen / Hausmeister / Besonderheiten</label>
                        <textarea name="notes" id="propNotes" rows="3" class="form-control" placeholder="z. B. Hausmeister Herr Meyer (Tel. 0170/...), Mülltonnenleerung Dienstags"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Liegenschaft speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
