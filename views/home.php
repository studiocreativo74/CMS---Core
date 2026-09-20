<?php
declare(strict_types=1);

$sessionEmail = $_SESSION['magic_email'] ?? ($_SESSION['magic_input_email'] ?? null);
$error = $error ?? ($_SESSION['flash_home_error'] ?? null);
$success = $success ?? ($_SESSION['flash_home_success'] ?? null);
$info = $info ?? ($_SESSION['flash_home_info'] ?? null);

unset($_SESSION['flash_home_error'], $_SESSION['flash_home_success'], $_SESSION['flash_home_info']);

// Dynamische Startseiten-Einstellungen laden (mit robusten Fallbacks)
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

if ($homepageTheme === '' || !in_array($homepageTheme, ['standard', 'light', 'dark', 'blue'], true)) {
    $homepageTheme = 'standard';
}

// Dynamische Inhaltsblöcke abrufen (nur sichtbare)
$blocks = class_exists('HomepageBlock') ? HomepageBlock::all(true) : [];

// Maximale Breite für den Inhaltscontainer ermitteln
$containerMaxWidth = match ($homepageLayout) {
    'wide' => '1140px',
    'full' => '1280px',
    default => '860px',
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --cms-primary: <?= htmlspecialchars($homepagePrimaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --cms-secondary: <?= htmlspecialchars($homepageSecondaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --cms-bg: <?= htmlspecialchars($homepageBgColor, ENT_QUOTES, 'UTF-8') ?>;
            --cms-text: <?= htmlspecialchars($homepageTextColor, ENT_QUOTES, 'UTF-8') ?>;
            --cms-container-max: <?= $containerMaxWidth ?>;
        }

        * {
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: var(--cms-text);
            background-color: var(--cms-bg);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .page-content {
            flex: 1 0 auto;
        }

        /* Top Bar / Brand Header */
        .site-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .site-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
            font-weight: 700;
            font-size: 1.15rem;
        }
        .site-logo {
            max-height: 40px;
            max-width: 180px;
            object-fit: contain;
        }

        /* Layout Container */
        .cms-container {
            max-width: var(--cms-container-max);
            margin: 0 auto;
            padding: 0 1.25rem;
            width: 100%;
        }

        /* Hero & Sections */
        .cms-section {
            padding: 3rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .cms-section:last-of-type {
            border-bottom: none;
        }
        .cms-hero {
            padding: 3.5rem 0 2.5rem 0;
            text-align: center;
        }
        .cms-hero h1 {
            font-size: 2.25rem;
            margin: 0 0 0.75rem 0;
            line-height: 1.2;
            font-weight: 800;
            color: inherit;
        }
        .cms-hero .subtitle {
            font-size: 1.2rem;
            color: #4b5563;
            margin: 0 auto 1rem auto;
            max-width: 680px;
            font-weight: 500;
        }
        .cms-hero .description {
            font-size: 1rem;
            color: #6b7280;
            max-width: 720px;
            margin: 0 auto 1.75rem auto;
            line-height: 1.65;
        }

        /* Modulare CMS-Block-Typen */
        .block-text {
            line-height: 1.7;
            font-size: 1.05rem;
        }
        .block-text h2, .block-two-col h2, .block-features h2, .block-cta h2 {
            font-size: 1.65rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        .block-subtitle {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 1.25rem;
            font-weight: 500;
        }

        /* 2-Spalten-Block */
        .two-column-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
            margin-top: 1.5rem;
        }
        @media (max-width: 768px) {
            .two-column-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        .column-box {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            padding: 1.5rem;
        }

        /* Features Kacheln */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .feature-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.06);
        }
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background-color: var(--cms-primary);
            color: #ffffff;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        .feature-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 0 0.5rem 0;
        }
        .feature-text {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
        }

        /* CTA-Sektion */
        .block-cta-box {
            text-align: center;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 2.5rem 1.5rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }
        .cta-btn {
            display: inline-block;
            margin-top: 1.25rem;
            padding: 0.75rem 1.75rem;
            font-size: 1.05rem;
            font-weight: 600;
            color: #fff;
            background-color: var(--cms-primary);
            border-radius: 6px;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .cta-btn:hover {
            opacity: 0.9;
        }

        /* Magic-Login-Bereich (Bleibt immer robust erhalten) */
        .magic-wrapper {
            max-width: 520px;
            margin: 2.5rem auto;
            width: 100%;
        }
        .magic-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.75rem;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }
        .alert-error {
            background-color: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f8b4b4;
        }
        .alert-success {
            background-color: #e6f7ec;
            color: #1e6b37;
            border: 1px solid #c2ebd0;
        }
        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .form-row {
            margin-bottom: 1rem;
        }
        .form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .form-row input[type="email"] {
            width: 100%;
            box-sizing: border-box;
            padding: 0.65rem 0.85rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-row input[type="email"]:focus {
            border-color: var(--cms-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
        }
        .btn {
            display: inline-block;
            padding: 0.65rem 1.25rem;
            background: var(--cms-primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            text-align: center;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: var(--cms-secondary);
        }
        .btn-outline {
            background: transparent;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .btn-outline:hover {
            background: #f3f4f6;
        }
        .email-info-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            margin-bottom: 1.25rem;
        }
        .email-display {
            font-weight: 600;
            color: #1f2937;
            word-break: break-all;
        }
        .code-digits-wrapper {
            display: flex;
            gap: 6px;
            justify-content: space-between;
            margin: 1.25rem 0;
        }
        .code-digit {
            width: 38px;
            height: 48px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            text-transform: uppercase;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 0;
            background: #fff;
            color: #111;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .code-digit:focus {
            border-color: var(--cms-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
        }
        @media (max-width: 480px) {
            .code-digits-wrapper {
                gap: 4px;
            }
            .code-digit {
                width: 30px;
                height: 42px;
                font-size: 1.05rem;
            }
        }
        .links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
        }
        .links a {
            color: var(--cms-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .links a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .site-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 2.5rem 1rem;
            font-size: 0.88rem;
            color: #6b7280;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.5);
        }

        /* --- THEME VARIATIONEN --- */
        body.theme-light {
            background-color: #ffffff;
            color: #1e293b;
        }
        body.theme-dark {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        body.theme-dark .site-header {
            background-color: rgba(15, 23, 42, 0.85);
            border-bottom-color: #1e293b;
        }
        body.theme-dark .cms-section {
            border-bottom-color: #1e293b;
        }
        body.theme-dark .cms-hero .subtitle {
            color: #94a3b8;
        }
        body.theme-dark .cms-hero .description {
            color: #cbd5e1;
        }
        body.theme-dark .magic-card,
        body.theme-dark .feature-card,
        body.theme-dark .block-cta-box,
        body.theme-dark .column-box {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        body.theme-dark .code-digit {
            background: #0f172a;
            color: #ffffff;
            border-color: #475569;
        }
        body.theme-dark .email-info-box {
            background: #0f172a;
            border-color: #334155;
        }
        body.theme-dark .email-display {
            color: #38bdf8;
        }
        body.theme-dark .site-footer {
            background: #0f172a;
            color: #64748b;
            border-top-color: #1e293b;
        }

        body.theme-blue {
            background-color: #f0f7ff;
            color: #1e293b;
        }
        body.theme-blue .magic-card,
        body.theme-blue .feature-card,
        body.theme-blue .block-cta-box {
            background: #ffffff;
            border-color: #bae6fd;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);
        }
    </style>
</head>
<body class="theme-<?= htmlspecialchars($homepageTheme, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Top-Header mit Logo oder Brand-Name -->
    <header class="site-header">
        <div class="cms-container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="?route=/" class="site-brand">
                <?php if ($homepageLogoPath !== ''): ?>
                    <img src="<?= htmlspecialchars($homepageLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?>" class="site-logo">
                <?php else: ?>
                    <span><?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </a>
            <div>
                <?php if (!empty($_SESSION['magic_authenticated']) || class_exists('Auth') && (Auth::checkMagic() || Auth::check())): ?>
                    <a href="?route=admin" class="btn" style="width: auto; padding: 0.4rem 0.9rem; font-size: 0.88rem;">
                        Admin-Dashboard
                    </a>
                <?php else: ?>
                    <a href="#magic-section" class="btn btn-outline" style="width: auto; padding: 0.4rem 0.9rem; font-size: 0.88rem;">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-content">
        <!-- ============================================================= -->
        <!-- DYNAMISCHE INHALTSBLÖCKE (MINI-CMS)                           -->
        <!-- ============================================================= -->
        <?php if (!empty($blocks)): ?>
            <?php foreach ($blocks as $block): ?>
                <?php
                $bType = $block['type'] ?? 'text';
                $bTitle = $block['title'] ?? '';
                $bSubtitle = $block['subtitle'] ?? '';
                $bContent = $block['content'] ?? '';
                $bExtra = $block['extra'] ?? [];
                ?>

                <!-- 1. Hero Block -->
                <?php if ($bType === 'hero'): ?>
                    <section class="cms-section cms-hero">
                        <div class="cms-container">
                            <?php if ($bTitle !== ''): ?>
                                <h1><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                            <?php endif; ?>
                            <?php if ($bSubtitle !== ''): ?>
                                <p class="subtitle"><?= htmlspecialchars($bSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($bContent !== ''): ?>
                                <div class="description"><?= nl2br(htmlspecialchars($bContent, ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($bExtra['btn_text'])): ?>
                                <a href="<?= htmlspecialchars((string) ($bExtra['btn_link'] ?? '#magic-section'), ENT_QUOTES, 'UTF-8') ?>" class="cta-btn">
                                    <?= htmlspecialchars((string) $bExtra['btn_text'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>

                <!-- 2. Reiner Textblock -->
                <?php elseif ($bType === 'text'): ?>
                    <section class="cms-section">
                        <div class="cms-container block-text">
                            <?php if ($bTitle !== ''): ?>
                                <h2><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php endif; ?>
                            <?php if ($bSubtitle !== ''): ?>
                                <div class="block-subtitle"><?= htmlspecialchars($bSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($bContent !== ''): ?>
                                <div><?= nl2br(htmlspecialchars($bContent, ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>
                        </div>
                    </section>

                <!-- 3. 2-Spalten-Block -->
                <?php elseif ($bType === 'two_column'): ?>
                    <section class="cms-section">
                        <div class="cms-container block-two-col">
                            <?php if ($bTitle !== ''): ?>
                                <h2><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php endif; ?>
                            <?php if ($bSubtitle !== ''): ?>
                                <div class="block-subtitle"><?= htmlspecialchars($bSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <div class="two-column-grid">
                                <div class="column-box">
                                    <?= nl2br(htmlspecialchars($bContent, ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                                <div class="column-box">
                                    <?php if (!empty($bExtra['col2_title'])): ?>
                                        <h3 style="margin-top: 0; font-size: 1.25rem; font-weight: 600;"><?= htmlspecialchars((string) $bExtra['col2_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php endif; ?>
                                    <?= nl2br(htmlspecialchars((string) ($bExtra['col2_content'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        </div>
                    </section>

                <!-- 4. Feature-Liste (Kacheln) -->
                <?php elseif ($bType === 'features'): ?>
                    <section class="cms-section">
                        <div class="cms-container block-features">
                            <div style="text-align: center; max-width: 680px; margin: 0 auto 1.5rem auto;">
                                <?php if ($bTitle !== ''): ?>
                                    <h2><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                                <?php endif; ?>
                                <?php if ($bSubtitle !== ''): ?>
                                    <div class="block-subtitle"><?= htmlspecialchars($bSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="features-grid">
                                <?php if (!empty($bExtra['items']) && is_array($bExtra['items'])): ?>
                                    <?php foreach ($bExtra['items'] as $item): ?>
                                        <div class="feature-card">
                                            <div class="feature-icon">★</div>
                                            <h3 class="feature-title"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p class="feature-text"><?= htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="feature-card">
                                        <div class="feature-icon">1</div>
                                        <h3 class="feature-title">Sicherheit</h3>
                                        <p class="feature-text"><?= nl2br(htmlspecialchars($bContent, ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                <!-- 5. Call-To-Action Block -->
                <?php elseif ($bType === 'cta'): ?>
                    <section class="cms-section">
                        <div class="cms-container block-cta">
                            <div class="block-cta-box">
                                <?php if ($bTitle !== ''): ?>
                                    <h2><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                                <?php endif; ?>
                                <?php if ($bSubtitle !== ''): ?>
                                    <div class="block-subtitle" style="margin-bottom: 0.75rem;"><?= htmlspecialchars($bSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if ($bContent !== ''): ?>
                                    <p style="max-width: 620px; margin: 0 auto; color: #4b5563; font-size: 1.05rem;">
                                        <?= nl2br(htmlspecialchars($bContent, ENT_QUOTES, 'UTF-8')) ?>
                                    </p>
                                <?php endif; ?>
                                <?php
                                $btnText = !empty($bExtra['btn_text']) ? (string) $bExtra['btn_text'] : 'Jetzt starten';
                                $btnLink = !empty($bExtra['btn_link']) ? (string) $bExtra['btn_link'] : '#magic-section';
                                ?>
                                <a href="<?= htmlspecialchars($btnLink, ENT_QUOTES, 'UTF-8') ?>" class="cta-btn">
                                    <?= htmlspecialchars($btnText, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        </div>
                    </section>

                <!-- 6. Custom HTML Block -->
                <?php elseif ($bType === 'custom'): ?>
                    <section class="cms-section">
                        <div class="cms-container">
                            <?php if ($bTitle !== ''): ?>
                                <h2><?= htmlspecialchars($bTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php endif; ?>
                            <div><?= $bContent /* Absichtlicher Custom-HTML-Output für Webmaster */ ?></div>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback-Hero falls noch keine Blöcke in der Datenbank gepflegt wurden -->
            <section class="cms-section cms-hero" style="padding-bottom: 1rem;">
                <div class="cms-container">
                    <h1><?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if ($homepageSubtitle !== ''): ?>
                        <p class="subtitle"><?= htmlspecialchars($homepageSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($homepageDescription !== ''): ?>
                        <div class="description"><?= nl2br(htmlspecialchars($homepageDescription, ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- MAGIC LOGIN FORMULAR (Kernkomponente bleibt 100% erhalten)    -->
        <!-- ============================================================= -->
        <div class="cms-container" id="magic-section">
            <div class="magic-wrapper">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <p style="color: #9b1c1c; margin: 0;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($info)): ?>
                    <div class="alert alert-info">
                        <p style="margin: 0;"><?= htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <p style="margin: 0;"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($sessionEmail)): ?>
                    <!-- Schritt 1: Noch keine E-Mail in der Session -->
                    <div class="magic-card">
                        <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.25rem; font-weight: 700;">Anmeldung</h3>
                        <p style="margin-top: 0; margin-bottom: 1.25rem; color: #64748b; font-size: 0.95rem;">Gib deine E-Mail-Adresse ein, um einen Magic Code anzufordern oder dich einzuloggen:</p>
                        <form method="post" action="?route=magic-set-email">
                            <div class="form-row">
                                <label for="email">E-Mail-Adresse:</label>
                                <input type="email" id="email" name="email" maxlength="191" required placeholder="name@beispiel.ch" autofocus>
                            </div>
                            <button type="submit" class="btn">Weiter</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Schritt 2: E-Mail in der Session gespeichert -->
                    <div class="magic-card">
                        <div class="email-info-box">
                            <div>
                                <small style="color: #666; display: block;">Angemeldete E-Mail:</small>
                                <span class="email-display"><?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <form method="post" action="?route=magic-clear-email" style="margin: 0;">
                                <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; width: auto;">E-Mail ändern</button>
                            </form>
                        </div>

                        <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.15rem; font-weight: 700;">Magic Code eingeben</h3>
                        <p style="margin-top: 0; margin-bottom: 0.75rem; color: #64748b; font-size: 0.9rem;">Gib deinen 10-stelligen Code aus der E-Mail ein:</p>
                        <form method="post" action="?route=magic-login" id="magic_login_form">
                            <input type="hidden" name="magic_code" id="magic_code_hidden">

                            <div class="code-digits-wrapper" id="magic_code_container">
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <input type="text"
                                           class="code-digit"
                                           data-index="<?= $i ?>"
                                           maxlength="1"
                                           autocomplete="off"
                                           autocapitalize="characters"
                                           spellcheck="false"
                                           <?= $i === 0 ? 'autofocus' : '' ?>>
                                <?php endfor; ?>
                            </div>

                            <button type="submit" class="btn">Code einloggen</button>
                        </form>

                        <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #d1d5db;">
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #555;">Keinen Code zur Hand oder abgelaufen?</p>
                            <form method="post" action="?route=magic-request" style="margin: 0;">
                                <button type="submit" class="btn btn-secondary" style="font-size: 0.88rem;">Neuen Code anfordern</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['magic_authenticated']) || class_exists('Auth') && (Auth::checkMagic() || Auth::check())): ?>
                    <div class="links">
                        <a href="?route=admin">→ Direkt zum Admin-Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Einheitlicher Footer -->
    <footer class="site-footer">
        <div class="cms-container">
            &copy; <?= date('Y') ?> StudioCreativo. Alle Rechte vorbehalten.
        </div>
    </footer>

    <!-- 10-Digit Code Input Logik -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('magic_login_form');
        if (!form) return;

        const hiddenInput = document.getElementById('magic_code_hidden');
        const inputs = Array.from(form.querySelectorAll('.code-digit'));

        function updateHiddenValue() {
            if (hiddenInput) {
                hiddenInput.value = inputs.map(function(inp) {
                    return inp.value.trim().toUpperCase();
                }).join('');
            }
        }

        inputs.forEach(function(input, index) {
            // Nur Grossbuchstaben und Ziffern A-Z, 0-9 erlauben
            input.addEventListener('input', function() {
                const clean = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                this.value = clean.slice(0, 1);

                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
                updateHiddenValue();
            });

            // Backspace-Sprung zum vorherigen Feld
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    if (this.value === '' && index > 0) {
                        e.preventDefault();
                        inputs[index - 1].focus();
                        inputs[index - 1].value = '';
                        updateHiddenValue();
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    inputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                    e.preventDefault();
                    inputs[index + 1].focus();
                }
            });

            // Paste-Unterstützung für den gesamten 10-stelligen Code
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text')
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .slice(0, 10);

                if (!pastedData) return;

                pastedData.split('').forEach(function(char, i) {
                    if (inputs[i]) {
                        inputs[i].value = char;
                    }
                });

                updateHiddenValue();

                // Fokus auf das nächste leere Feld oder den Submit-Button
                if (pastedData.length < inputs.length) {
                    inputs[pastedData.length].focus();
                } else {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.focus();
                }
            });
        });
    });
    </script>
</body>
</html>
