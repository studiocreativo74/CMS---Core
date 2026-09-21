<?php
declare(strict_types=1);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Design & Brand-Einstellungen';
$currentRoute = 'admin/design';

$adminBrandColor = class_exists('Settings') ? (string) Settings::get('admin_brand_color', '#0d6efd') : '#0d6efd';
$adminAccentColor = class_exists('Settings') ? (string) Settings::get('admin_accent_color', '#0ea5e9') : '#0ea5e9';
$adminDefaultTheme = class_exists('Settings') ? (string) Settings::get('admin_default_theme', 'system') : 'system';

if (!in_array($adminDefaultTheme, ['system', 'light', 'dark'], true)) {
    $adminDefaultTheme = 'system';
}

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold">Design &amp; Brand-System</h1>
        <p class="text-muted mb-0">Steuere die primäre Brand-Farbe, optionale Akzente und das globale Standard-Theme für das gesamte Admin-Interface.</p>
    </div>
    <div>
        <a href="?route=admin/homepage" class="btn btn-outline-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-window-sidebar me-1" viewBox="0 0 16 16">
                <path d="M2.5 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m2-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m1 .5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v2H1V3a1 1 0 0 1 1-1zM1 13V6h4v8H2a1 1 0 0 1-1-1m5 1V6h9v7a1 1 0 0 1-1 1z"/>
            </svg>
            Startseiten-CMS aufrufen
        </a>
    </div>
</div>

<?php if ($flashSuccess !== null): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger me-2" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Linke Spalte: Formular -->
    <div class="col-lg-7 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h5 mb-0 fw-bold text-dark d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-palette-fill text-primary me-2" viewBox="0 0 16 16">
                        <path d="M12.433 10.07C14.133 10.585 16 11.15 16 8a8 8 0 1 0-8 8c1.996 0 1.826-1.504 1.649-3.08-.124-1.101-.252-2.237.351-2.92.465-.527 1.42-.237 2.433.07"/>
                    </svg>
                    Design-Konfiguration
                </h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="?route=admin/design" id="adminDesignForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <!-- 1. Brand-Farbe (Primär) -->
                    <div class="mb-4">
                        <label for="admin_brand_color" class="form-label fw-bold">Brand-Farbe (Primär)</label>
                        <p class="text-muted small mb-2">Wird für Haupt-Buttons, aktive Navigationselemente, Fokus-Rahmen und Primärakzente verwendet.</p>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_color_picker" value="<?= htmlspecialchars($adminBrandColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen" style="max-width: 50px; cursor: pointer;">
                            <input type="text" class="form-control font-monospace" id="admin_brand_color" name="admin_brand_color" value="<?= htmlspecialchars($adminBrandColor, ENT_QUOTES, 'UTF-8') ?>" pattern="^#([A-Fa-f0-9]{6})$" required>
                        </div>
                        <div class="form-text small">Hexadezimalcode z. B. <code>#0d6efd</code>, <code>#4f46e5</code>, <code>#0284c7</code></div>
                    </div>

                    <!-- 2. Akzent-Farbe (Optional) -->
                    <div class="mb-4">
                        <label for="admin_accent_color" class="form-label fw-bold">Akzent-Farbe (Sekundär)</label>
                        <p class="text-muted small mb-2">Für sekundäre Badges, Unterstreichungen und informative Hervorhebungen.</p>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="accent_color_picker" value="<?= htmlspecialchars($adminAccentColor, ENT_QUOTES, 'UTF-8') ?>" title="Farbe wählen" style="max-width: 50px; cursor: pointer;">
                            <input type="text" class="form-control font-monospace" id="admin_accent_color" name="admin_accent_color" value="<?= htmlspecialchars($adminAccentColor, ENT_QUOTES, 'UTF-8') ?>" pattern="^#([A-Fa-f0-9]{6})$">
                        </div>
                        <div class="form-text small">Hexadezimalcode z. B. <code>#0ea5e9</code>, <code>#10b981</code>, <code>#8b5cf6</code></div>
                    </div>

                    <!-- 3. Globales Standard-Theme -->
                    <div class="mb-4">
                        <label for="admin_default_theme" class="form-label fw-bold">Globales Standard-Theme</label>
                        <p class="text-muted small mb-2">Definiert das Ausgangs-Theme für neue Benutzer und Besucher ohne persönliche Theme-Einstellung.</p>
                        <select class="form-select" id="admin_default_theme" name="admin_default_theme">
                            <option value="system" <?= $adminDefaultTheme === 'system' ? 'selected' : '' ?>>System (Automatisch nach Betriebssystem/Browser)</option>
                            <option value="light" <?= $adminDefaultTheme === 'light' ? 'selected' : '' ?>>Hell (Light Mode)</option>
                            <option value="dark" <?= $adminDefaultTheme === 'dark' ? 'selected' : '' ?>>Dunkel (Dark Mode)</option>
                        </select>
                        <div class="form-text small">Jeder Benutzer kann in der oberen Leiste sein persönliches Theme zusätzlich individuell wählen.</div>
                    </div>

                    <!-- Schnellauswahl-Presets -->
                    <div class="mb-4 pt-3 border-top">
                        <label class="form-label fw-bold d-block small text-uppercase text-muted">Vordefinierte Farbpaletten</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-brand="#0d6efd" data-accent="#0ea5e9">
                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background: #0d6efd; vertical-align: middle;"></span>
                                Classic Blue
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-brand="#4f46e5" data-accent="#8b5cf6">
                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background: #4f46e5; vertical-align: middle;"></span>
                                Indigo Modern
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-brand="#059669" data-accent="#10b981">
                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background: #059669; vertical-align: middle;"></span>
                                Emerald Forest
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-brand="#0284c7" data-accent="#38bdf8">
                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background: #0284c7; vertical-align: middle;"></span>
                                Sky Oceanic
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" data-brand="#d97706" data-accent="#f59e0b">
                                <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background: #d97706; vertical-align: middle;"></span>
                                Warm Amber
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte: Live-Vorschau & Token-Übersicht -->
    <div class="col-lg-5 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h5 mb-0 fw-bold text-dark d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-eye-fill text-secondary me-2" viewBox="0 0 16 16">
                        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/>
                    </svg>
                    Live-Vorschau der Komponenten
                </h2>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">So wirken deine ausgewählten Design-Farben auf Buttons, aktive Menüeinträge und Karten:</p>

                <!-- Vorschau-Kasten -->
                <div class="p-3 border rounded mb-4" id="livePreviewContainer" style="background-color: var(--sc-bg-elevated);">
                    <!-- 1. Buttons Vorschau -->
                    <div class="mb-3">
                        <label class="small text-muted fw-bold d-block mb-2">Interaktive Buttons:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary btn-sm" id="previewBtnPrimary">Primary Button</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="previewBtnOutline">Outline Button</button>
                            <span class="badge" id="previewBadgeBrand" style="background-color: var(--sc-primary); align-self: center;">Brand Badge</span>
                            <span class="badge" id="previewBadgeAccent" style="background-color: var(--sc-accent); align-self: center;">Accent Badge</span>
                        </div>
                    </div>

                    <!-- 2. Sidebar Navigation Item Vorschau -->
                    <div class="mb-3">
                        <label class="small text-muted fw-bold d-block mb-2">Aktiver Navigationseintrag (Sidebar):</label>
                        <div class="p-2 rounded" style="background-color: var(--sc-nav-bg); max-width: 280px;">
                            <div class="d-flex align-items-center px-3 py-2 rounded text-white fw-bold small" id="previewNavItem" style="background-color: var(--sc-primary);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-speedometer2 me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.024l-.454.455a.5.5 0 0 0 .707.707l.455-.454a.39.39 0 0 0-.181-.684z"/>
                                    <path d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25-1.026-.33-2.023-.974-3.11-1.911-.476-.41-1.002-.87-1.505-1.341v-.002A5 5 0 1 0 2.89 12.18c.386.417.804.819 1.25 1.196.447.377.934.73 1.455 1.054 1.134.704 2.455 1.194 3.738 1.464A8 8 0 0 1 0 10m7.5-6a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13"/>
                                </svg>
                                Aktiver Menüpunkt
                            </div>
                        </div>
                    </div>

                    <!-- 3. Soft Highlight Box -->
                    <div>
                        <label class="small text-muted fw-bold d-block mb-2">Weicher Primär-Container:</label>
                        <div class="p-3 rounded border" id="previewSoftBox" style="background-color: var(--sc-primary-soft); border-color: var(--sc-border);">
                            <span class="fw-semibold text-dark d-block mb-1" id="previewSoftText">Wichtiger Hinweisbereich</span>
                            <small class="text-muted">Verwendet die harmonisierte CSS-Variable <code>--sc-primary-soft</code>.</small>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h3 class="h6 fw-bold mb-2">CSS-Tokens Referenz</h3>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-1"><code>--sc-primary</code>: Primäre Brand-Farbe</li>
                        <li class="mb-1"><code>--sc-accent</code>: Sekundäre Akzentfarbe</li>
                        <li class="mb-1"><code>--sc-bg</code> &amp; <code>--sc-bg-elevated</code>: Seiten- &amp; Kartenhintergrund</li>
                        <li class="mb-1"><code>--sc-nav-bg</code>, <code>--sc-nav-active-bg</code>: Sidebar-Farben</li>
                        <li><code>--sc-border</code> &amp; <code>--sc-text</code>: Typografie &amp; Trennlinien</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const brandPicker = document.getElementById('brand_color_picker');
    const brandInput = document.getElementById('admin_brand_color');
    const accentPicker = document.getElementById('accent_color_picker');
    const accentInput = document.getElementById('admin_accent_color');

    const previewBtnPrimary = document.getElementById('previewBtnPrimary');
    const previewBtnOutline = document.getElementById('previewBtnOutline');
    const previewBadgeBrand = document.getElementById('previewBadgeBrand');
    const previewBadgeAccent = document.getElementById('previewBadgeAccent');
    const previewNavItem = document.getElementById('previewNavItem');
    const previewSoftBox = document.getElementById('previewSoftBox');

    function updatePreview() {
        const brand = brandInput.value.trim() || '#0d6efd';
        const accent = accentInput.value.trim() || '#0ea5e9';

        if (previewBtnPrimary) previewBtnPrimary.style.backgroundColor = brand;
        if (previewBtnPrimary) previewBtnPrimary.style.borderColor = brand;
        if (previewBtnOutline) {
            previewBtnOutline.style.color = brand;
            previewBtnOutline.style.borderColor = brand;
        }
        if (previewBadgeBrand) previewBadgeBrand.style.backgroundColor = brand;
        if (previewBadgeAccent) previewBadgeAccent.style.backgroundColor = accent;
        if (previewNavItem) previewNavItem.style.backgroundColor = brand;
        if (previewSoftBox) previewSoftBox.style.backgroundColor = brand + '1a';
    }

    brandPicker.addEventListener('input', function() {
        brandInput.value = this.value;
        updatePreview();
    });

    brandInput.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            brandPicker.value = this.value;
            updatePreview();
        }
    });

    accentPicker.addEventListener('input', function() {
        accentInput.value = this.value;
        updatePreview();
    });

    accentInput.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            accentPicker.value = this.value;
            updatePreview();
        }
    });

    document.querySelectorAll('.preset-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const b = this.getAttribute('data-brand');
            const a = this.getAttribute('data-accent');
            if (b) {
                brandInput.value = b;
                brandPicker.value = b;
            }
            if (a) {
                accentInput.value = a;
                accentPicker.value = a;
            }
            updatePreview();
        });
    });

    updatePreview();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
