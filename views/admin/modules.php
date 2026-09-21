<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $modules */
$modules = $modules ?? (class_exists('ModuleManager') ? ModuleManager::all() : []);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Modulverwaltung';
$currentRoute = 'admin/modules';

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

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <p class="text-muted mb-0">Übersicht aller installierten Erweiterungen und Plugins für das CMS.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-2 d-inline-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-cpu text-primary me-2" viewBox="0 0 16 16">
                <path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 14 4.5h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14A2.5 2.5 0 0 1 11.5 14v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14A2.5 2.5 0 0 1 2 11.5H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2A2.5 2.5 0 0 1 4.5 2V.5A.5.5 0 0 1 5 0m-.5 3A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13h7a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 11.5 3zM5 6.5A1.5 1.5 0 0 1 6.5 5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5zM6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"/>
            </svg>
            Core: <strong>v<?= htmlspecialchars(class_exists('CoreVersion') ? CoreVersion::VERSION : '1.0.0', ENT_QUOTES, 'UTF-8') ?></strong>
        </span>
        <a href="?route=admin/system" class="btn btn-outline-primary d-inline-flex align-items-center">
            System &amp; Migrationen
        </a>
        <form method="POST" action="?route=admin/modules/rescan" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat me-2" viewBox="0 0 16 16">
                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
                </svg>
                Dateisystem neu scannen
            </button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-semibold text-dark">Installierte Module (<?= count($modules) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($modules)): ?>
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-puzzle text-muted mb-3" viewBox="0 0 16 16">
                    <path d="M3.112 5.145a1.5 1.5 0 0 1-.258-.87A1.5 1.5 0 0 1 4.354 2.77c.44-.06.84.154 1.08.52.22.336.56.55 1.066.55s.846-.214 1.066-.55c.24-.366.64-.58 1.08-.52a1.5 1.5 0 0 1 1.5 1.505c0 .324-.094.63-.258.87-.318.468-.266.97.025 1.344.29.373.792.51 1.312.335.43-.146.89-.044 1.206.27.318.316.42.776.273 1.207-.176.52-.038 1.02.335 1.312.373.29.876.343 1.344.025.24-.164.546-.258.87-.258a1.5 1.5 0 0 1 1.505 1.5 1.5 1.5 0 0 1-.27 1.207c-.146.43-.044.89.27 1.206.316.318.776.42 1.207.273.52-.176 1.02-.038 1.312.335.29.373.343.876.025 1.344-.164.24-.258.546-.258.87a1.5 1.5 0 0 1-1.5 1.505c-.44.06-.84-.154-1.08-.52-.22-.336-.56-.55-1.066-.55s-.846.214-1.066.55c-.24.366-.64.58-1.08.52a1.5 1.5 0 0 1-1.5-1.505c0-.324.094-.63.258-.87.318-.468.266-.97-.025-1.344-.29-.373-.792-.51-1.312-.335-.43.146-.89.044-1.206-.27-.318-.316-.42-.776-.273-1.207.176-.52.038-1.02-.335-1.312-.373-.29-.876-.343-1.344-.025-.24.164-.546.258-.87.258a1.5 1.5 0 0 1-1.505-1.5 1.5 1.5 0 0 1 .27-1.207c.146-.43.044-.89-.27-1.206-.316-.318-.776-.42-1.207-.273-.52.176-1.02.038-1.312-.335-.29-.373-.343-.876-.025-1.344.164-.24.258-.546.258-.87a1.5 1.5 0 0 1 1.5-1.505z"/>
                </svg>
                <h6 class="text-muted fw-normal">Bisher sind noch keine Module registriert.</h6>
                <p class="text-muted small">Erstellen Sie einen Ordner in <code>/modules</code> mit einer <code>module.php</code> oder <code>module.json</code>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Name / Schlüssel</th>
                            <th>Beschreibung</th>
                            <th>Modul-Version</th>
                            <th>Benötigt Core</th>
                            <th>Status</th>
                            <th>Installiert</th>
                            <th class="text-end pe-3" style="width: 140px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $mod): 
                            $isEnabled = (int) ($mod['is_enabled'] ?? 0) === 1;
                            $key = (string) ($mod['key'] ?? '');
                            $name = (string) ($mod['name'] ?? $key);
                            $version = (string) ($mod['version'] ?? '1.0.0');
                            $requiresCore = (string) ($mod['requires_core'] ?? '');
                            $desc = (string) ($mod['description'] ?? '—');
                            $compat = class_exists('ModuleManager') ? ModuleManager::getCompatibilityInfo($mod) : ['is_compatible' => true, 'message' => 'OK'];
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                                    <code class="small text-muted"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></code>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">v<?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?php if ($requiresCore !== ''): ?>
                                        <code class="small text-dark"><?= htmlspecialchars($requiresCore, ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php if ($compat['is_compatible']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" title="<?= htmlspecialchars($compat['message'], ENT_QUOTES, 'UTF-8') ?>">
                                                ✓ OK
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" title="<?= htmlspecialchars($compat['message'], ENT_QUOTES, 'UTF-8') ?>">
                                                ⚠ Inkompatibel
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Beliebig (*)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isEnabled): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Deaktiviert</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($mod['installed_at']) ? htmlspecialchars((string) $mod['installed_at'], ENT_QUOTES, 'UTF-8') : '—' ?>
                                </td>
                                <td class="text-end pe-3">
                                    <form method="POST" action="?route=admin/modules/toggle" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="enable" value="<?= $isEnabled ? '0' : '1' ?>">
                                        <?php if ($isEnabled): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Deaktivieren
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                Aktivieren
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 bg-light p-3 small text-muted">
    <div class="d-flex align-items-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-info-circle me-2 flex-shrink-0" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
        </svg>
        <span>
            Module können per Dateisystem unter <code>/modules/[modul-name]/</code> abgelegt werden. Eine <code>module.php</code> registriert automatisch Routen, Menüpunkte und Hooks.
        </span>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
