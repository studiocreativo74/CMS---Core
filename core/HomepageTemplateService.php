<?php

declare(strict_types=1);

/**
 * HomepageTemplateService
 *
 * Verwaltet vordefinierte Startseiten-Vorlagen (Templates) für das Mini-CMS.
 * Ermöglicht das 1-Klick-Laden kompletter Themes inklusive Inhaltsblöcken,
 * Farben, Logos und Texten (z. B. Nautic Bootsfahrschule, Standard, Corporate, Minimal),
 * welche anschließend modular im CMS bearbeitet werden können.
 */
final class HomepageTemplateService
{
    /**
     * Liefert alle verfügbaren Startseiten-Vorlagen mit ihren Einstellungen und Standardblöcken.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getTemplates(): array
    {
        return [
            'nautic' => [
                'key'         => 'nautic',
                'name'        => 'Nautic – Bootsfahrschule & Vermietung',
                'badge'       => 'Offizielle Nautic Vorlage',
                'badge_class' => 'bg-primary text-white',
                'description' => 'Vollständige maritime Startseite für Bootsfahrschulen, Skipper-Trainings, Bootscharter und Crew-Portal mit offiziellem Nautic SVG-Logo.',
                'theme'       => 'blue',
                'primary_color'   => '#0284c7',
                'secondary_color' => '#0369a1',
                'background_color'=> '#f0f9ff',
                'text_color'      => '#0f172a',
                'layout'          => 'wide',
                'logo_path'       => 'public/assets/nautic-logo.svg',
                'title'           => 'Nautic – Bootsfahrschule & Vermietung',
                'subtitle'        => 'Fahrstunden Kat. A & D, Bootscharter, Crew & Disposition',
                'page_description'=> 'Offizielle Ausbildungs- und Charterplattform. Buche deine Lektionen oder melde dich per sicherem Magic-Link an.',
                'blocks' => [
                    [
                        'type'       => 'hero',
                        'title'      => 'Leinen los! Deine Bootsfahrschule & Charter am See',
                        'subtitle'   => 'Professionelle Ausbildung für Motorboot Kat. A & Segeln Kat. D sowie exklusive Bootsvermietung',
                        'content'    => "Lerne das Bootfahren von Grund auf bei erfahrenen und lizenzierten Skippern.\nFlexible Ausbildungszeiten von Montag bis Sonntag, transparenter Ausbildungsstand und direkte Terminbuchung.",
                        'extra'      => [
                            'btn_text' => 'Zum Magic-Link Login',
                            'btn_link' => '#magic-section'
                        ],
                        'sort_order' => 10,
                        'is_visible' => 1,
                    ],
                    [
                        'type'       => 'features',
                        'title'      => 'Unser Angebot auf dem Wasser',
                        'subtitle'   => 'Von den ersten Manövern bis zum amtlichen Schiffsführerausweis und Wochenend-Charter',
                        'content'    => '',
                        'extra'      => [
                            'items' => [
                                [
                                    'title' => 'Motorboot Kat. A',
                                    'text'  => 'Intensive Prüfungsvorbereitung, Hafenmanöver, An- und Ablegen, Mann-über-Bord und Knotenkunde mit hoher Erfolgsquote.'
                                ],
                                [
                                    'title' => 'Segelausbildung Kat. D',
                                    'text'  => 'Von den Segelgrundlagen bis zur Regatta-Taktik. Praxisnahe Ausbildung bei jedem Wind auf dem See.'
                                ],
                                [
                                    'title' => 'Flottenvermietung & Charter',
                                    'text'  => 'Gepflegte Boote (Boesch, Axopar) für Ausflüge, Sonnenuntergangs-Cruises oder private Events mieten.'
                                ],
                                [
                                    'title' => 'Crew & Schüler Portal',
                                    'text'  => 'Passwortloser Magic-Link Zugang: Alle Fahrstunden, Ausbildungskarten, Termine und Abrechnungen auf einen Blick.'
                                ]
                            ]
                        ],
                        'sort_order' => 20,
                        'is_visible' => 1,
                    ],
                    [
                        'type'       => 'two_column',
                        'title'      => 'Warum Nautic Bootsfahrschule?',
                        'subtitle'   => 'Sicherheit, modernste Technik und persönliche Betreuung',
                        'content'    => "Unsere Ausbildungsflotte erfüllt höchste Sicherheitsstandards und wird laufend gewartet. Mit flexiblen Ausbildungszeiten von Montag bis Sonntag passen wir uns deinem Terminkalender an.\n\nAlle Fahrlehrer sind staatlich lizenziert und bringen jahrelange Erfahrung auf Schweizer Seen mit.",
                        'extra'      => [
                            'col2_title'   => 'Transparenz & Direkte Buchung',
                            'col2_content' => "Keine versteckten Gebühren: Treibstoff, Versicherung und Prüfungsbegleitung sind transparent kalkuliert.\n\nPrüfungsstandorte: Schifffahrtsamt Zürich, Luzern und St. Gallen."
                        ],
                        'sort_order' => 30,
                        'is_visible' => 1,
                    ],
                    [
                        'type'       => 'cta',
                        'title'      => 'Bereit für das Skipper-Patent?',
                        'subtitle'   => 'Fordere jetzt dein persönliches Konto an oder logge dich direkt per Magic-Link ein.',
                        'content'    => 'Als Schüler oder Crew-Mitglied benötigst du kein Passwort. Gib einfach deine E-Mail-Adresse ein und starte sofort.',
                        'extra'      => [
                            'btn_text' => 'Jetzt per Magic-Link einloggen',
                            'btn_link' => '#magic-section'
                        ],
                        'sort_order' => 40,
                        'is_visible' => 1,
                    ]
                ]
            ],

            'standard' => [
                'key'         => 'standard',
                'name'        => 'Standard CMS & Portal',
                'badge'       => 'Klassisch',
                'badge_class' => 'bg-secondary text-white',
                'description' => 'Schlichtes, universelles Design für Portale und Firmen-Websites mit neutralen Grautönen und blauem Akzent.',
                'theme'       => 'standard',
                'primary_color'   => '#0d6efd',
                'secondary_color' => '#6c757d',
                'background_color'=> '#f8fafc',
                'text_color'      => '#222222',
                'layout'          => 'contained',
                'logo_path'       => '',
                'title'           => 'Willkommen im Kunden- & Mitarbeiterportal',
                'subtitle'        => 'Sicherer Zugriff über passwortlose Magic-Links',
                'page_description'=> 'Zentraler Einstiegspunkt für Kunden, Partner und Mitarbeiter.',
                'blocks' => [
                    [
                        'type'       => 'hero',
                        'title'      => 'Dein zentraler Zugang zu allen Services',
                        'subtitle'   => 'Einfach, schnell und ohne Passwort-Chaos',
                        'content'    => 'Nutze das Portal für deine Anträge, Termine und Dokumente.',
                        'extra'      => [
                            'btn_text' => 'Jetzt einloggen',
                            'btn_link' => '#magic-section'
                        ],
                        'sort_order' => 10,
                        'is_visible' => 1,
                    ],
                    [
                        'type'       => 'features',
                        'title'      => 'Vorteile unseres Portals',
                        'subtitle'   => 'Modernste Technologie für höchste Sicherheit',
                        'content'    => '',
                        'extra'      => [
                            'items' => [
                                ['title' => 'Passwortlos', 'text' => 'Login in Sekundenschnelle per Magic Link oder Code.'],
                                ['title' => 'Sicher', 'text' => '256-Bit SSL Verschlüsselung und Schweizer Datenschutz.'],
                                ['title' => 'Mobil optimiert', 'text' => 'Volle Funktion auf Smartphone, Tablet und Desktop.']
                            ]
                        ],
                        'sort_order' => 20,
                        'is_visible' => 1,
                    ]
                ]
            ],

            'corporate' => [
                'key'         => 'corporate',
                'name'        => 'Corporate Business & Enterprise',
                'badge'       => 'Business',
                'badge_class' => 'bg-dark text-white',
                'description' => 'Elegantes Firmen-Design mit dunklen Akzenten und modernem 2-Spalten Layout.',
                'theme'       => 'light',
                'primary_color'   => '#1e293b',
                'secondary_color' => '#f59e0b',
                'background_color'=> '#ffffff',
                'text_color'      => '#0f172a',
                'layout'          => 'wide',
                'logo_path'       => '',
                'title'           => 'Unternehmensportal & Services',
                'subtitle'        => 'Exklusiver Zugang für autorisierte Partner & Kunden',
                'page_description'=> 'Verwalte deine Projekte, Verträge und Services an einem zentralen Ort.',
                'blocks' => [
                    [
                        'type'       => 'hero',
                        'title'      => 'Effiziente Zusammenarbeit auf höchstem Niveau',
                        'subtitle'   => 'Dein sicherer Datenraum und Service-Hub',
                        'content'    => 'Greife rund um die Uhr auf deine persönlichen Dokumente und Buchungen zu.',
                        'extra'      => [
                            'btn_text' => 'Zur Authentifizierung',
                            'btn_link' => '#magic-section'
                        ],
                        'sort_order' => 10,
                        'is_visible' => 1,
                    ],
                    [
                        'type'       => 'two_column',
                        'title'      => 'Maßgeschneiderte Betreuung',
                        'subtitle'   => 'Persönlicher Ansprechpartner & digitale Prozesse',
                        'content'    => 'Wir vereinen persönliche Beratung mit modernen digitalen Abläufen.',
                        'extra'      => [
                            'col2_title'   => 'Verlässlichkeit',
                            'col2_content' => 'Höchste Verfügbarkeit und garantierte Reaktionszeiten.'
                        ],
                        'sort_order' => 20,
                        'is_visible' => 1,
                    ]
                ]
            ],

            'minimal' => [
                'key'         => 'minimal',
                'name'        => 'Minimal Clean Login',
                'badge'       => 'Fokus Login',
                'badge_class' => 'bg-secondary-subtle text-secondary border',
                'description' => 'Reduziert auf das Wesentliche: Direkter Fokus auf den Login ohne überflüssige Marketing-Elemente.',
                'theme'       => 'light',
                'primary_color'   => '#0284c7',
                'secondary_color' => '#64748b',
                'background_color'=> '#f8fafc',
                'text_color'      => '#1e293b',
                'layout'          => 'contained',
                'logo_path'       => '',
                'title'           => 'Portal Login',
                'subtitle'        => 'Bitte authentifiziere dich mit deiner registrierten E-Mail-Adresse',
                'page_description'=> 'Passwortlose Anmeldung.',
                'blocks'          => [] // Keine Blöcke -> rein fokussierter Magic Login
            ]
        ];
    }

    /**
     * Wendet eine Vorlage auf das System an.
     *
     * @param string $templateKey Der Schlüssel der Vorlage (z.B. 'nautic')
     * @param bool $replaceBlocks Ob die Inhaltsblöcke durch die Vorlagen-Blöcke ersetzt werden sollen
     * @return array{success: bool, message: string}
     */
    public static function apply(string $templateKey, bool $replaceBlocks = true): array
    {
        $templates = self::getTemplates();
        if (!isset($templates[$templateKey])) {
            return [
                'success' => false,
                'message' => 'Die gewählte Vorlage existiert nicht.'
            ];
        }

        $tpl = $templates[$templateKey];

        try {
            // 1. Settings aktualisieren
            if (class_exists('Settings')) {
                Settings::set('homepage_template', $templateKey);
                Settings::set('homepage_theme', (string) $tpl['theme']);
                Settings::set('homepage_primary_color', (string) $tpl['primary_color']);
                Settings::set('homepage_secondary_color', (string) $tpl['secondary_color']);
                Settings::set('homepage_background_color', (string) $tpl['background_color']);
                Settings::set('homepage_text_color', (string) $tpl['text_color']);
                Settings::set('homepage_layout', (string) $tpl['layout']);
                Settings::set('homepage_title', (string) $tpl['title']);
                Settings::set('homepage_subtitle', (string) $tpl['subtitle']);
                Settings::set('homepage_description', (string) $tpl['page_description']);

                // Logo setzen (falls angegeben und Datei existiert)
                if (!empty($tpl['logo_path'])) {
                    Settings::set('homepage_logo_path', (string) $tpl['logo_path']);
                }

                // Startseitenmodus auf CMS-Blöcke stellen, damit das Template aktiv sichtbar ist
                Settings::set('homepage_mode', 'blocks');
            }

            // 2. Inhaltsblöcke anwenden
            if ($replaceBlocks && class_exists('HomepageBlock')) {
                // Bestehende Blöcke leeren
                try {
                    DB::execute('DELETE FROM `homepage_blocks`');
                } catch (\Throwable) {
                    // Tabelle ggf. noch nicht vorhanden
                }

                // Vorlagen-Blöcke einfügen
                if (!empty($tpl['blocks'])) {
                    foreach ($tpl['blocks'] as $blockData) {
                        HomepageBlock::create($blockData);
                    }
                }
            }

            // 3. Logeintrag erfassen
            if (class_exists('ActivityLog') && method_exists('ActivityLog', 'log')) {
                ActivityLog::log('homepage_template_applied', "Startseiten-Vorlage '{$tpl['name']}' angewendet.");
            }

            return [
                'success' => true,
                'message' => "Startseiten-Vorlage '{$tpl['name']}' wurde erfolgreich angewendet! Alle Abschnitte, Farben und Texte können jetzt im Mini-CMS nach deinen Wünschen bearbeitet werden."
            ];
        } catch (\Throwable $e) {
            error_log('HomepageTemplateService::apply Fehler: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Fehler beim Anwenden der Vorlage: ' . $e->getMessage()
            ];
        }
    }
}
