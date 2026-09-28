<?php

declare(strict_types=1);

/**
 * Öffentlicher externer Datenraum für die Polizei / Ermittlungsbehörden
 *
 * Zugriff erfolgt ausschließlich token-basiert (40-stelliger Hex-Token):
 * ?route=sicherung/download&token={40_HEX_CHARS}
 *
 * Zeigt ausschließlich die Sicherungsdaten für genau diesen Fall an.
 * Jeder Download wird mit Zeitstempel, IP-Adresse und Prüfsumme protokolliert.
 */

$token = trim((string) ($_GET['token'] ?? ''));
$case = null;
$validity = ['valid' => false, 'reason' => 'Kein Freigabe-Token übermittelt.'];
$caseFiles = [];

if ($token !== '') {
    $case = SecurePortalRepository::findCaseByDownloadToken($token);
    if ($case) {
        $validity = SecurePortalRepository::checkDownloadValidity($case);
        if ($validity['valid']) {
            $caseFiles = SecurePortalRepository::getCaseFiles((int) $case['id'], true);
        }
    } else {
        $validity = ['valid' => false, 'reason' => 'Ungültiger oder nicht mehr existierender Freigabe-Token.'];
    }
}

$logoPath = class_exists('Settings') ? (string) Settings::get('homepage_logo_path', '') : '';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sicherungsdaten – Externer Datenraum</title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sec-primary: #0f2b5c;
            --sec-dark: #071529;
            --sec-accent: #0284c7;
            --sec-bg: #f8fafc;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--sec-bg);
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-navbar {
            background: linear-gradient(135deg, #071529 0%, #0f2b5c 100%);
            color: #ffffff;
            padding: 1.15rem 0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .data-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }

        .hash-badge {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.82rem;
            color: #1e293b;
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
        }

        .footer {
            margin-top: auto;
            background-color: #071529;
            color: #94a3b8;
            padding: 1.5rem 0;
            font-size: 0.875rem;
            border-top: 1px solid #1e293b;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="portal-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($logoPath !== ''): ?>
                        <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height: 42px; max-width: 180px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-white bg-opacity-10 p-2 rounded text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="h5 mb-0 fw-bold text-white tracking-tight">Sicherungsportal – Externer Datenraum</h1>
                        <small class="text-white-50">Geschützter Beweismittel- &amp; Datenabruf für Ermittlungsbehörden</small>
                    </div>
                </div>

                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i> Token-Autorisiert &amp; Audit-Geschützt
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">

                <?php if (!$validity['valid'] || !$case): ?>
                    <!-- Ungültiger oder abgelaufener Zugriff -->
                    <div class="card border-0 shadow-sm p-4 p-md-5 text-center data-card">
                        <div class="mb-4">
                            <span class="badge bg-danger-subtle text-danger p-3 rounded-circle">
                                <i class="bi bi-shield-slash-fill fs-1"></i>
                            </span>
                        </div>
                        <h2 class="h4 fw-bold text-dark mb-2">Zugriff nicht möglich</h2>
                        <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">
                            <?= htmlspecialchars((string) ($validity['reason'] ?? 'Ungültiger Freigabelink.'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <div class="alert alert-light border small text-muted mx-auto text-start" style="max-width: 600px;">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Hinweis:</strong> Die Freigabe von Sicherungsdaten erfolgt unter strengen datenschutzrechtlichen und behördlichen Auflagen. Sollte der Zugriffslink abgelaufen oder deaktiviert sein, wenden Sie sich bitte an die zuständige Fachabteilung mit Ihrer Vorgangsnummer.
                        </div>
                        <div class="mt-4">
                            <a href="?route=sicherung" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Zur Startseite des Portals
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- Vorgangskopf & Freigabedetails -->
                    <div class="data-card p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1 fw-bold">
                                    Freigegebener Vorgang
                                </span>
                                <h2 class="h3 fw-bold text-dark mb-0 font-monospace">
                                    <?= htmlspecialchars((string) $case['case_number'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success-subtle">
                                    <i class="bi bi-check-circle-fill me-1"></i> Status: Bereitgestellt
                                </span>
                            </div>
                        </div>

                        <!-- Metadaten-Übersicht -->
                        <div class="row g-3 small mb-4">
                            <div class="col-md-4">
                                <span class="text-muted d-block fw-semibold text-uppercase">Behörde / Dienststelle</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars((string) $case['police_department'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block fw-semibold text-uppercase">Fall Nr / Aktenzeichen</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars((string) $case['reference_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block fw-semibold text-uppercase">Gültigkeit des Datenraums</span>
                                <?php if (!empty($case['download_expires_at'])): ?>
                                    <span class="text-dark fw-bold">
                                        <i class="bi bi-calendar-event me-1 text-primary"></i>
                                        Bis <?= date('d.m.Y H:i', strtotime((string)$case['download_expires_at'])) ?> Uhr
                                    </span>
                                <?php else: ?>
                                    <span class="text-success fw-bold">
                                        <i class="bi bi-infinity me-1"></i> Unbefristet freigegeben
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sicherheitshinweis -->
                        <div class="alert alert-info border-info-subtle d-flex align-items-start gap-3 mb-4">
                            <i class="bi bi-shield-check text-info fs-3 flex-shrink-0 mt-1"></i>
                            <div class="small">
                                <strong class="text-dark d-block mb-1">Revisionssichere Beweismittel-Bereitstellung</strong>
                                Jeder Dateidownload wird mit Zeitstempel, Prüfsummenabgleich und Netzwerkadresse im Audit-Protokoll des Falls erfasst.
                                Bitte vergleichen Sie nach dem Download die SHA-256 Prüfsumme auf Ihrem lokalen System zur Sicherstellung der Datenintegrität.
                            </div>
                        </div>

                        <!-- Dateiliste -->
                        <h4 class="h5 fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i>
                            Sicherungsdateien (<?= count($caseFiles) ?>)
                        </h4>

                        <?php if (empty($caseFiles)): ?>
                            <div class="p-4 bg-light rounded text-center text-muted border">
                                <i class="bi bi-clock-history fs-2 d-block mb-2 text-secondary"></i>
                                Der Vorgang ist freigegeben, jedoch wurden noch keine Sicherungsdateien hinterlegt.
                            </div>
                        <?php else: ?>
                            <div class="list-group mb-3">
                                <?php foreach ($caseFiles as $cf): ?>
                                    <?php 
                                        $cfId = (int) $cf['id'];
                                        $sizeMb = number_format(((int) $cf['file_size']) / 1024 / 1024, 2);
                                        $downloadUrl = '?route=sicherung/download/file&token=' . urlencode($token) . '&file_id=' . $cfId;
                                    ?>
                                    <div class="list-group-item p-3 p-md-4 border rounded mb-3 shadow-sm">
                                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-primary text-white p-3 rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-file-earmark-zip-fill fs-3"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-1 fw-bold text-dark font-monospace text-break">
                                                        <?= htmlspecialchars((string) $cf['file_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </h5>
                                                    <div class="text-muted small">
                                                        <span><i class="bi bi-hdd me-1"></i><?= $sizeMb ?> MB</span> &middot;
                                                        <span><i class="bi bi-calendar-check me-1"></i>Bereitgestellt am <?= date('d.m.Y H:i', strtotime((string)$cf['uploaded_at'])) ?> Uhr</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <a href="<?= $downloadUrl ?>" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm text-nowrap">
                                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Datei herunterladen
                                                </a>
                                            </div>
                                        </div>

                                        <!-- SHA-256 Prüfsumme Box -->
                                        <div class="p-2 bg-light rounded border small font-monospace d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="text-truncate me-2">
                                                <span class="text-muted fw-bold me-1">SHA-256:</span>
                                                <span class="text-dark select-all" id="sha_<?= $cfId ?>"><?= htmlspecialchars((string) $cf['sha256'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 text-nowrap" onclick="copyChecksum('sha_<?= $cfId ?>', this)">
                                                <i class="bi bi-clipboard me-1"></i> Hash kopieren
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <div class="small">
                &copy; <?= date('Y') ?> Sicherungsportal &middot; Geschützter behördlicher Datenraum &middot; Alle Zugriffe werden revisionssicher protokolliert.
            </div>
        </div>
    </footer>

    <script>
    function copyChecksum(id, btn) {
        var el = document.getElementById(id);
        if (!el) return;
        var text = (el.innerText || el.textContent).trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                var old = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check text-success me-1"></i> Kopiert!';
                setTimeout(function() { btn.innerHTML = old; }, 2000);
            });
        } else {
            var ta = document.createElement("textarea");
            ta.value = text;
            ta.style.position = "fixed";
            ta.style.left = "-999999px";
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                var old = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check text-success me-1"></i> Kopiert!';
                setTimeout(function() { btn.innerHTML = old; }, 2000);
            } catch (e) {
                console.error(e);
            }
            document.body.removeChild(ta);
        }
    }
    </script>
</body>
</html>
