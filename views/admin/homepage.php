<?php
declare(strict_types=1);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Startseite bearbeiten & Mini-CMS';
$currentRoute = 'admin/homepage';

// Aktuelle Einstellungen aus der Datenbank oder Default laden
$settings = class_exists('Settings') ? Settings::all() : [];
$homepageTitle = (string) ($settings['homepage_title'] ?? 'Willkommen im CMS-Prototype');
$homepageSubtitle = (string) ($settings['homepage_subtitle'] ?? '');
$homepageDescription = (string) ($settings['homepage_description'] ?? '');
$homepageTheme = (string) ($settings['homepage_theme'] ?? 'standard');
$homepagePrimaryColor = (string) ($settings['homepage_primary_color'] ?? '#0d6efd');
$homepageSecondaryColor = (string) ($settings['homepage_secondary_color'] ?? '#6c757d');
$homepageBgColor = (string) ($settings['homepage_background_color'] ?? '#f8fafc');
$homepageTextColor = (string) ($settings['homepage_text_color'] ?? '#222222');
$homepageLogoPath = (string) ($settings['homepage_logo_path'] ?? '');
$homepageLayout = (string) ($settings['homepage_layout'] ?? 'contained');
$afterLoginRedirect = (string) ($settings['after_login_redirect'] ?? 'admin');
$afterLoginCustomUrl = (string) ($settings['after_login_custom_url'] ?? '');

// Blöcke laden
$blocks = class_exists('HomepageBlock') ? HomepageBlock::all(false) : [];
$validTypes = class_exists('HomepageBlock') ? HomepageBlock::VALID_TYPES : [];

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Startseite &amp; Mini-CMS</h4>
        <p class="text-muted mb-0">Gestalte Farben, Logo, Inhalte und modulare Inhaltsblöcke für die öffentliche Startseite.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=/" target="_blank" class="btn btn-outline-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-arrow-up-right me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
            </svg>
            Live-Startseite ansehen
        </a>
    </div>
</div>

<?php if ($flashSuccess !== null): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- Tabs zur schnellen Navigation zwischen Design/Grundeinstellungen und Block-Management -->
<ul class="nav nav-pills mb-4" id="cmsTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-medium" id="blocks-tab" data-bs-toggle="pill" data-bs-target="#tab-blocks" type="button" role="tab" aria-controls="tab-blocks" aria-selected="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-collection me-1" viewBox="0 0 16 16">
                <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5z"/>
            </svg>
            Inhaltsblöcke (Sections) <span class="badge bg-primary ms-1"><?= count($blocks) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-medium" id="design-tab" data-bs-toggle="pill" data-bs-target="#tab-design" type="button" role="tab" aria-controls="tab-design" aria-selected="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-palette me-1" viewBox="0 0 16 16">
                <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                <path d="M16 8c0 3.15-1.866 2.585-3.58 2.585a4.9 4.9 0 0 1-.96-.093c-.66-.13-1.04-.224-1.28-.224-.543 0-.82.441-.82 1.05 0 .51.39.99.78 1.48.43.53.88 1.08.88 1.77 0 1.52-1.36 2.43-3.02 2.43C3.58 17 0 13.42 0 8s3.58-8 8-8 8 3.58 8 8m-8-7a7 7 0 0 0-7 7c0 4.41 2.91 7.22 6.58 7.22 1.05 0 1.42-.51 1.42-1.03 0-.31-.22-.61-.46-.91-.4-.5-.88-1.11-.88-1.92 0-1.42 1.06-2.4 2.32-2.4.52 0 1.05.13 1.63.24.63.12 1.34.25 2.19.25 1.02 0 2.2-.42 2.2-1.45 0-3.86-3.14-7-7-7"/>
            </svg>
            Design, Farben &amp; Logo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-medium" id="settings-tab" data-bs-toggle="pill" data-bs-target="#tab-settings" type="button" role="tab" aria-controls="tab-settings" aria-selected="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sliders me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1z"/>
            </svg>
            Titel &amp; Login-Verhalten
        </button>
    </li>
</ul>

<div class="tab-content" id="cmsTabContent">
    <!-- ================================================================= -->
    <!-- TAB 1: INHALTSBLÖCKE (SECTIONS) VERWALTEN                         -->
    <!-- ================================================================= -->
    <div class="tab-pane fade show active" id="tab-blocks" role="tabpanel" aria-labelledby="blocks-tab">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-semibold text-dark">Startseiten-Abschnitte</h5>
                    <small class="text-muted">Blöcke werden in der angegebenen Reihenfolge auf der öffentlichen Startseite gerendert.</small>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBlockModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle me-1" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                        </svg>
                        Neuen Block anlegen
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (empty($blocks)): ?>
                    <div class="p-5 text-center text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-layout-text-window-reverse mb-3 opacity-50" viewBox="0 0 16 16">
                            <path d="M13 6.5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 .5-.5m0 3a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 .5-.5m-.5 2.5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>
                            <path d="M14 0a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zM2 1a1 1 0 0 0-1 1v1h14V2a1 1 0 0 0-1-1zM1 4v10a1 1 0 0 0 1 1h2V4zm4 0v11h9a1 1 0 0 0 1-1V4z"/>
                        </svg>
                        <h6 class="fw-bold text-dark">Noch keine Inhaltsblöcke angelegt</h6>
                        <p class="small max-w-md mx-auto mb-3" style="max-width: 480px;">
                            Aktuell wird die Standard-Startseite mit dem Magic-Login-Formular dargestellt.
                            Erstelle jetzt modulare Hero-Sektionen, Textblöcke, Feature-Kacheln oder Call-to-Actions!
                        </p>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBlockModal">
                            Ersten Block anlegen
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">Reihenfolge</th>
                                    <th style="width: 140px;">Typ</th>
                                    <th>Titel / Vorschau</th>
                                    <th style="width: 120px;">Status</th>
                                    <th class="text-end" style="width: 160px;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blocks as $idx => $b): 
                                    $typeLabel = $validTypes[$b['type']] ?? ucfirst((string)$b['type']);
                                    $badgeClass = match($b['type']) {
                                        'hero' => 'bg-primary',
                                        'features' => 'bg-info text-dark',
                                        'two_column' => 'bg-secondary',
                                        'cta' => 'bg-warning text-dark',
                                        'custom' => 'bg-dark',
                                        default => 'bg-light text-dark border',
                                    };
                                ?>
                                    <tr class="<?= $b['is_visible'] ? '' : 'table-light opacity-75' ?>">
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1"><?= (int)$b['sort_order'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($b['type'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= $b['title'] !== '' ? htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8') : '<em class="text-muted">(Kein Titel)</em>' ?>
                                            </div>
                                            <?php if ($b['subtitle'] !== ''): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars(mb_strimwidth($b['subtitle'], 0, 70, '...'), ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                            <?php if ($b['content'] !== ''): ?>
                                                <small class="text-secondary d-block font-monospace" style="font-size: 0.78rem;">
                                                    <?= htmlspecialchars(mb_strimwidth(strip_tags($b['content']), 0, 90, '...'), ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" action="?route=admin/homepage/block-toggle" class="d-inline">
                                                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="is_visible" value="<?= $b['is_visible'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-sm <?= $b['is_visible'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> py-0 px-2" style="font-size: 0.8rem;" title="Klicken zum Umschalten">
                                                    <?= $b['is_visible'] ? '● Sichtbar' : '○ Versteckt' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBlockModal<?= $b['id'] ?>" title="Bearbeiten">
                                                    Bearbeiten
                                                </button>
                                                <form method="post" action="?route=admin/homepage/block-delete" class="d-inline" onsubmit="return confirm('Möchtest du diesen Block wirklich löschen?');">
                                                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Löschen">
                                                        Löschen
                                                    </button>
                                                </form>
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

        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-3 small text-muted">
                <strong>Tipp zur Magic-Login-Integration:</strong>
                Das Anmeldefeld mit den 10 Magic-Code-Boxen bleibt immer als Kernbereich der Startseite verankert.
                Deine Inhaltsblöcke werden standardmäßig harmonisch oberhalb oder unterhalb als eigenständige Abschnitte dargestellt.
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 2: DESIGN, FARBEN & LOGO                                      -->
    <!-- ================================================================= -->
    <div class="tab-pane fade" id="tab-design" role="tabpanel" aria-labelledby="design-tab">
        <form method="post" action="?route=admin/homepage" enctype="multipart/form-data">
            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
            <input type="hidden" name="section" value="design">

            <!-- Bestehende Textwerte weiterreichen, damit nichts überschrieben wird -->
            <input type="hidden" name="homepage_title" value="<?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_subtitle" value="<?= htmlspecialchars($homepageSubtitle, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_description" value="<?= htmlspecialchars($homepageDescription, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="after_login_redirect" value="<?= htmlspecialchars($afterLoginRedirect, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="after_login_custom_url" value="<?= htmlspecialchars($afterLoginCustomUrl, ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-4">
                <!-- Logo-Verwaltung -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-semibold text-dark">Startseiten-Logo</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($homepageLogoPath !== ''): ?>
                                <div class="mb-4 p-3 bg-light rounded border text-center">
                                    <div class="small text-muted mb-2">Aktuelles Logo:</div>
                                    <img src="<?= htmlspecialchars($homepageLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="img-fluid rounded" style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                    <div class="mt-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                                            <label class="form-check-label text-danger small fw-semibold" for="remove_logo">Logo entfernen</label>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-3 p-4 bg-light rounded border text-center text-muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-image mb-2 opacity-50" viewBox="0 0 16 16">
                                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                                    </svg>
                                    <div class="small">Derzeit ist kein eigenes Logo hochgeladen.</div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="logo_file" class="form-label fw-medium">Neues Logo hochladen</label>
                                <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/gif">
                                <div class="form-text">Erlaubt: PNG, JPG, SVG, WebP, GIF (max. 2 MB).</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Farb- und Theme-Palette -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-semibold text-dark">Farbschema &amp; Akzentfarben</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label for="homepage_theme" class="form-label fw-medium">Theme-Vorlage</label>
                                <select class="form-select" id="homepage_theme" name="homepage_theme">
                                    <option value="standard" <?= $homepageTheme === 'standard' ? 'selected' : '' ?>>Standard (Slate Hellgrau)</option>
                                    <option value="light" <?= $homepageTheme === 'light' ? 'selected' : '' ?>>Modern Light (Reinweiß)</option>
                                    <option value="dark" <?= $homepageTheme === 'dark' ? 'selected' : '' ?>>Dark Mode (Dunkles Schiefer)</option>
                                    <option value="blue" <?= $homepageTheme === 'blue' ? 'selected' : '' ?>>Corporate Blue (Blaunuance)</option>
                                </select>
                                <div class="form-text">Grundlegendes Farblayout der Seite.</div>
                            </div>

                            <hr class="my-3 text-muted">
                            <h6 class="fw-semibold text-dark mb-3">Feinabstimmung (Custom Colors)</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="homepage_primary_color" class="form-label small fw-medium">Primärfarbe (Buttons, Links)</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="homepage_primary_color" name="homepage_primary_color" value="<?= htmlspecialchars($homepagePrimaryColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen">
                                        <input type="text" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($homepagePrimaryColor, ENT_QUOTES, 'UTF-8') ?>" onchange="document.getElementById('homepage_primary_color').value = this.value">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label for="homepage_secondary_color" class="form-label small fw-medium">Sekundärfarbe (Badges)</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="homepage_secondary_color" name="homepage_secondary_color" value="<?= htmlspecialchars($homepageSecondaryColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen">
                                        <input type="text" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($homepageSecondaryColor, ENT_QUOTES, 'UTF-8') ?>" onchange="document.getElementById('homepage_secondary_color').value = this.value">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="homepage_background_color" class="form-label small fw-medium">Hintergrundfarbe</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="homepage_background_color" name="homepage_background_color" value="<?= htmlspecialchars($homepageBgColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen">
                                        <input type="text" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($homepageBgColor, ENT_QUOTES, 'UTF-8') ?>" onchange="document.getElementById('homepage_background_color').value = this.value">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label for="homepage_text_color" class="form-label small fw-medium">Textfarbe</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="homepage_text_color" name="homepage_text_color" value="<?= htmlspecialchars($homepageTextColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen">
                                        <input type="text" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($homepageTextColor, ENT_QUOTES, 'UTF-8') ?>" onchange="document.getElementById('homepage_text_color').value = this.value">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="homepage_layout" class="form-label small fw-medium">Inhaltsbreite / Layout</label>
                                <select class="form-select form-select-sm" id="homepage_layout" name="homepage_layout">
                                    <option value="contained" <?= $homepageLayout === 'contained' ? 'selected' : '' ?>>Fokussiert zentriert (Standard, 840px)</option>
                                    <option value="wide" <?= $homepageLayout === 'wide' ? 'selected' : '' ?>>Breit / Modern (1140px)</option>
                                    <option value="full" <?= $homepageLayout === 'full' ? 'selected' : '' ?>>Volle Breite mit Container (1280px)</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Design &amp; Logo speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 3: TITEL & LOGIN-VERHALTEN                                    -->
    <!-- ================================================================= -->
    <div class="tab-pane fade" id="tab-settings" role="tabpanel" aria-labelledby="settings-tab">
        <form method="post" action="?route=admin/homepage" class="row g-4">
            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
            <input type="hidden" name="section" value="general">

            <!-- Farbwerte weiterreichen -->
            <input type="hidden" name="homepage_theme" value="<?= htmlspecialchars($homepageTheme, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_primary_color" value="<?= htmlspecialchars($homepagePrimaryColor, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_secondary_color" value="<?= htmlspecialchars($homepageSecondaryColor, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_background_color" value="<?= htmlspecialchars($homepageBgColor, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_text_color" value="<?= htmlspecialchars($homepageTextColor, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="homepage_layout" value="<?= htmlspecialchars($homepageLayout, ENT_QUOTES, 'UTF-8') ?>">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-semibold text-dark">Texte &amp; Beschreibungen</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="homepage_title" class="form-label fw-medium">Haupttitel der Startseite <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="homepage_title" name="homepage_title"
                                   value="<?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="191" required
                                   placeholder="Willkommen im CMS-Prototype">
                            <div class="form-text">Wird als Hauptüberschrift (H1) und Seitentitel angezeigt.</div>
                        </div>

                        <div class="mb-3">
                            <label for="homepage_subtitle" class="form-label fw-medium">Untertitel / Tagline (optional)</label>
                            <input type="text" class="form-control" id="homepage_subtitle" name="homepage_subtitle"
                                   value="<?= htmlspecialchars($homepageSubtitle, ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="191"
                                   placeholder="z.B. Sichere Administration und schlankes Content Management">
                        </div>

                        <div class="mb-0">
                            <label for="homepage_description" class="form-label fw-medium">Einleitungstext (optional)</label>
                            <textarea class="form-control" id="homepage_description" name="homepage_description"
                                      rows="3" maxlength="1000"
                                      placeholder="Füge hier bei Bedarf einen kurzen Hinweis oder Begrüßungstext für Besucher ein..."><?= htmlspecialchars($homepageDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-semibold text-dark">Weiterleitung nach erfolgreichem Magic-Login</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_admin" value="admin"
                                   <?= $afterLoginRedirect === 'admin' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                            <label class="form-check-label fw-medium" for="redirect_admin">
                                Direkt ins Admin-Dashboard weiterleiten <span class="badge bg-light text-dark border ms-1">Standard</span>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_stay" value="stay"
                                   <?= $afterLoginRedirect === 'stay' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                            <label class="form-check-label fw-medium" for="redirect_stay">
                                Auf der Startseite verweilen
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_custom" value="custom_url"
                                   <?= $afterLoginRedirect === 'custom_url' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                            <label class="form-check-label fw-medium" for="redirect_custom">
                                Benutzerdefinierte Ziel-URL ansteuern
                            </label>
                        </div>

                        <div id="custom_url_container" class="p-3 bg-light rounded border <?= $afterLoginRedirect === 'custom_url' ? '' : 'd-none' ?>">
                            <label for="after_login_custom_url" class="form-label fw-medium">Ziel-URL</label>
                            <input type="text" class="form-control" id="after_login_custom_url" name="after_login_custom_url"
                                   value="<?= htmlspecialchars($afterLoginCustomUrl, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="z.B. ?route=admin/users oder /intern">
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <button type="submit" class="btn btn-primary">
                            Einstellungen speichern
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL: NEUEN BLOCK ERSTELLEN                                          -->
<!-- ===================================================================== -->
<div class="modal fade" id="createBlockModal" tabindex="-1" aria-labelledby="createBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="?route=admin/homepage/block-create" class="modal-content">
            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="createBlockModalLabel">Neuen Inhaltsblock erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label for="new_block_type" class="form-label fw-medium">Block-Typ <span class="text-danger">*</span></label>
                        <select class="form-select" id="new_block_type" name="type" required onchange="onBlockTypeChange(this, 'new')">
                            <?php foreach ($validTypes as $typeKey => $typeTitle): ?>
                                <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($typeTitle, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="new_block_sort" class="form-label fw-medium">Reihenfolge</label>
                        <input type="number" class="form-control" id="new_block_sort" name="sort_order" value="<?= class_exists('HomepageBlock') ? HomepageBlock::getNextSortOrder() : 10 ?>" step="10">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="new_block_visible" name="is_visible" value="1" checked>
                            <label class="form-check-label small" for="new_block_visible">Sichtbar</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_block_title" class="form-label fw-medium">Titel / Überschrift</label>
                    <input type="text" class="form-control" id="new_block_title" name="title" placeholder="z.B. Willkommen auf unserer Plattform" maxlength="191">
                </div>

                <div class="mb-3">
                    <label for="new_block_subtitle" class="form-label fw-medium">Untertitel / Tagline (optional)</label>
                    <input type="text" class="form-control" id="new_block_subtitle" name="subtitle" placeholder="z.B. Schnell, sicher und unkompliziert" maxlength="191">
                </div>

                <div class="mb-3">
                    <label for="new_block_content" class="form-label fw-medium" id="new_content_label">Inhalt (Fließtext oder HTML)</label>
                    <textarea class="form-control" id="new_block_content" name="content" rows="4" placeholder="Schreibe hier den Text für diesen Block..."></textarea>
                    <div class="form-text" id="new_content_help">
                        Unterstützt Standard-Text oder sicheres HTML.
                    </div>
                </div>

                <!-- Strukturierte Zusatzdaten (JSON) z.B. für Features oder CTA-Button -->
                <div class="p-3 bg-light rounded border">
                    <label for="new_block_extra" class="form-label fw-medium small text-dark mb-1">
                        Zusatz-Optionen (JSON-Struktur, optional)
                    </label>
                    <textarea class="form-control font-monospace form-control-sm" id="new_block_extra" name="extra" rows="3" placeholder='{"btn_text": "Jetzt starten", "btn_link": "#magic-section"}'></textarea>
                    <div class="form-text small">
                        Beispiel CTA: <code>{"btn_text": "Jetzt starten", "btn_link": "#magic-section", "style": "primary"}</code><br>
                        Beispiel 2-Spalten: <code>{"col2_title": "Rechte Spalte", "col2_content": "Text für die rechte Seite..."}</code>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Block anlegen</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODALS: BLOCK BEARBEITEN (FÜR JEDEN BLOCK)                             -->
<!-- ===================================================================== -->
<?php foreach ($blocks as $b): ?>
<div class="modal fade" id="editBlockModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="editBlockModalLabel<?= $b['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="?route=admin/homepage/block-edit" class="modal-content">
            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
            <input type="hidden" name="id" value="<?= $b['id'] ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editBlockModalLabel<?= $b['id'] ?>">
                    Block #<?= $b['id'] ?> bearbeiten (<?= htmlspecialchars($b['type'], ENT_QUOTES, 'UTF-8') ?>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label for="edit_type_<?= $b['id'] ?>" class="form-label fw-medium">Block-Typ</label>
                        <select class="form-select" id="edit_type_<?= $b['id'] ?>" name="type" required>
                            <?php foreach ($validTypes as $typeKey => $typeTitle): ?>
                                <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>" <?= $b['type'] === $typeKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($typeTitle, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="edit_sort_<?= $b['id'] ?>" class="form-label fw-medium">Reihenfolge</label>
                        <input type="number" class="form-control" id="edit_sort_<?= $b['id'] ?>" name="sort_order" value="<?= (int) $b['sort_order'] ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="edit_visible_<?= $b['id'] ?>" name="is_visible" value="1" <?= $b['is_visible'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="edit_visible_<?= $b['id'] ?>">Sichtbar</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="edit_title_<?= $b['id'] ?>" class="form-label fw-medium">Titel / Überschrift</label>
                    <input type="text" class="form-control" id="edit_title_<?= $b['id'] ?>" name="title" value="<?= htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8') ?>" maxlength="191">
                </div>

                <div class="mb-3">
                    <label for="edit_subtitle_<?= $b['id'] ?>" class="form-label fw-medium">Untertitel / Tagline (optional)</label>
                    <input type="text" class="form-control" id="edit_subtitle_<?= $b['id'] ?>" name="subtitle" value="<?= htmlspecialchars($b['subtitle'], ENT_QUOTES, 'UTF-8') ?>" maxlength="191">
                </div>

                <div class="mb-3">
                    <label for="edit_content_<?= $b['id'] ?>" class="form-label fw-medium">Inhalt (Fließtext oder HTML)</label>
                    <textarea class="form-control" id="edit_content_<?= $b['id'] ?>" name="content" rows="5"><?= htmlspecialchars($b['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="p-3 bg-light rounded border">
                    <label for="edit_extra_<?= $b['id'] ?>" class="form-label fw-medium small text-dark mb-1">
                        Zusatz-Optionen (JSON-Struktur)
                    </label>
                    <textarea class="form-control font-monospace form-control-sm" id="edit_extra_<?= $b['id'] ?>" name="extra" rows="3"><?= htmlspecialchars($b['extra_raw'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Änderungen speichern</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
function toggleCustomUrlField() {
    const isCustom = document.getElementById('redirect_custom') && document.getElementById('redirect_custom').checked;
    const container = document.getElementById('custom_url_container');
    if (container) {
        if (isCustom) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }
}

function onBlockTypeChange(selectEl, prefix) {
    const type = selectEl.value;
    const extraField = document.getElementById(prefix + '_block_extra');
    if (!extraField) return;

    if (type === 'cta' && extraField.value.trim() === '') {
        extraField.value = JSON.stringify({
            "btn_text": "Jetzt Anmelden",
            "btn_link": "#magic-section",
            "btn_style": "primary"
        }, null, 2);
    } else if (type === 'two_column' && extraField.value.trim() === '') {
        extraField.value = JSON.stringify({
            "col2_title": "Ergänzende Informationen",
            "col2_content": "Weitere Inhalte für die zweite Spalte..."
        }, null, 2);
    } else if (type === 'features' && extraField.value.trim() === '') {
        extraField.value = JSON.stringify({
            "items": [
                {"icon": "shield", "title": "Sicher", "text": "Passwordless Magic-Code-Authentifizierung."},
                {"icon": "speed", "title": "Schnell", "text": "Schlankes PHP 8 ohne Ballast."},
                {"icon": "custom", "title": "Flexibel", "text": "Modulare Inhaltsblöcke & Rollenrechte."}
            ]
        }, null, 2);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
