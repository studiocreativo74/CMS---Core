<?php
declare(strict_types=1);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Startseite bearbeiten';
$currentRoute = 'admin/homepage';

// Aktuelle Einstellungen aus der Datenbank oder Default laden
$settings = class_exists('Settings') ? Settings::all() : [];
$homepageTitle = (string) ($settings['homepage_title'] ?? 'Willkommen im CMS-Prototype');
$homepageSubtitle = (string) ($settings['homepage_subtitle'] ?? '');
$homepageDescription = (string) ($settings['homepage_description'] ?? '');
$homepageTheme = (string) ($settings['homepage_theme'] ?? 'standard');
$afterLoginRedirect = (string) ($settings['after_login_redirect'] ?? 'admin');
$afterLoginCustomUrl = (string) ($settings['after_login_custom_url'] ?? '');

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Passe Inhalte, Design und das Anmeldeverhalten der öffentlichen Startseite an.</p>
    </div>
    <div>
        <a href="?route=/" target="_blank" class="btn btn-outline-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-arrow-up-right me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
            </svg>
            Startseite ansehen
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

<form method="post" action="?route=admin/homepage" class="row g-4">
    <?= class_exists('Csrf') ? Csrf::input() : '' ?>

    <!-- 1. Textinhalte -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">Texte & Beschreibungen</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="homepage_title" class="form-label fw-medium">Haupttitel der Startseite <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="homepage_title" name="homepage_title"
                           value="<?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="191" required
                           placeholder="Willkommen im CMS-Prototype">
                    <div class="form-text">Wird als Hauptüberschrift (H1) und Seitentitel über dem Login-Fenster angezeigt.</div>
                </div>

                <div class="mb-3">
                    <label for="homepage_subtitle" class="form-label fw-medium">Untertitel / Tagline (optional)</label>
                    <input type="text" class="form-control" id="homepage_subtitle" name="homepage_subtitle"
                           value="<?= htmlspecialchars($homepageSubtitle, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="191"
                           placeholder="z.B. Sichere Administration und schlankes Content Management">
                    <div class="form-text">Erscheint als prägnante Subline unter der Hauptüberschrift.</div>
                </div>

                <div class="mb-0">
                    <label for="homepage_description" class="form-label fw-medium">Einleitungstext / Beschreibung (optional)</label>
                    <textarea class="form-control" id="homepage_description" name="homepage_description"
                              rows="3" maxlength="1000"
                              placeholder="Füge hier bei Bedarf einen kurzen Hinweis oder Begrüßungstext für Besucher ein..."><?= htmlspecialchars($homepageDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="form-text">Wird vor dem Anmeldebereich als lesefreundlicher Absatz gerendert.</div>
                </div>
            </div>
        </div>

        <!-- 2. Verhalten nach Login -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">Weiterleitung nach erfolgreichem Magic-Login</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">Definiere, wohin Benutzer nach Eingabe des korrekten Magic Codes weitergeleitet werden.</p>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_admin" value="admin"
                           <?= $afterLoginRedirect === 'admin' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                    <label class="form-check-label fw-medium" for="redirect_admin">
                        Direkt ins Admin-Dashboard weiterleiten <span class="badge bg-light text-dark border ms-1">Standard</span>
                    </label>
                    <div class="text-muted small ms-4">Leitet zur Route <code>?route=admin</code> weiter.</div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_stay" value="stay"
                           <?= $afterLoginRedirect === 'stay' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                    <label class="form-check-label fw-medium" for="redirect_stay">
                        Auf der Startseite verweilen
                    </label>
                    <div class="text-muted small ms-4">Benutzer bleibt eingeloggt auf <code>?route=/</code> (mit Direktlink zum Admin-Dashboard).</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="after_login_redirect" id="redirect_custom" value="custom_url"
                           <?= $afterLoginRedirect === 'custom_url' ? 'checked' : '' ?> onchange="toggleCustomUrlField()">
                    <label class="form-check-label fw-medium" for="redirect_custom">
                        Benutzerdefinierte Ziel-URL ansteuern
                    </label>
                    <div class="text-muted small ms-4">Leitet auf eine spezifische Route oder externe URL weiter.</div>
                </div>

                <div id="custom_url_container" class="mt-3 p-3 bg-light rounded border <?= $afterLoginRedirect === 'custom_url' ? '' : 'd-none' ?>">
                    <label for="after_login_custom_url" class="form-label fw-medium">Ziel-URL</label>
                    <input type="text" class="form-control" id="after_login_custom_url" name="after_login_custom_url"
                           value="<?= htmlspecialchars($afterLoginCustomUrl, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="z.B. ?route=admin/users oder /intern">
                    <div class="form-text">Gib eine relative Route (z.B. <code>?route=admin/users</code>) oder eine absolute URL ein.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Theme & Speichern Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">Farbschema & Design</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="homepage_theme" class="form-label fw-medium">Theme-Stil</label>
                    <select class="form-select" id="homepage_theme" name="homepage_theme">
                        <option value="standard" <?= $homepageTheme === 'standard' ? 'selected' : '' ?>>Standard (Hellgrau / Slate)</option>
                        <option value="light" <?= $homepageTheme === 'light' ? 'selected' : '' ?>>Modern Light (Klares Weiß)</option>
                        <option value="dark" <?= $homepageTheme === 'dark' ? 'selected' : '' ?>>Dark Mode (Dunkles Schiefer)</option>
                        <option value="blue" <?= $homepageTheme === 'blue' ? 'selected' : '' ?>>Corporate Blue (Sanfte Blaunuance)</option>
                    </select>
                    <div class="form-text">Bestimmt Hintergrundfarbe, Kartengestaltung und Kontraste der Startseite.</div>
                </div>

                <div class="p-3 bg-light rounded border small text-muted">
                    <strong>Hinweis zum Footer:</strong><br>
                    Auf der Startseite wird standardmäßig der einheitliche Footer <code>&copy; <?= date('Y') ?> StudioCreativo</code> dargestellt.
                </div>
            </div>
            <div class="card-footer bg-white border-top p-3 d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle me-1" viewBox="0 0 16 16">
                        <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                        <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                    </svg>
                    Einstellungen speichern
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function toggleCustomUrlField() {
    const isCustom = document.getElementById('redirect_custom').checked;
    const container = document.getElementById('custom_url_container');
    if (isCustom) {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
