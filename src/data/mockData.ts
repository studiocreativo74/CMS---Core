import {
  Instructor,
  Appointment,
  CoachAvailability,
  CoachWorkingHour,
  AccountRequest,
  ServiceOffer,
  PromoCode,
  CustomerSubscription,
  ElearningVideo,
  ElearningChart,
  ElearningQuizQuestion,
  Boat,
  BoatCheckin,
  BoatCheckout,
  CmsBlock
} from '../types/index.ts';

export const INITIAL_ACCOUNT_REQUESTS: AccountRequest[] = [
  {
    id: 1,
    first_name: 'Stefan',
    last_name: 'Brunner',
    email: 'stefan.brunner@see-experience.ch',
    phone: '+41 79 555 12 34',
    desired_role: 'instructor',
    status: 'pending',
    created_at: '2026-03-24 14:15',
    allergies: 'Keine Allergien bekannt',
    notes: 'Inhaber Motorboot Kat. A & Segelpatent Kat. D seit 2018. Bewerbung als Wochenend-Fahrcoach.'
  },
  {
    id: 2,
    first_name: 'Elena',
    last_name: 'Moser',
    email: 'elena.moser@bluewin.ch',
    phone: '+41 78 444 88 99',
    desired_role: 'student',
    status: 'pending',
    created_at: '2026-03-24 16:30',
    allergies: 'Wespenstich-Allergie (EpiPen Notfallset wird mitgeführt)',
    notes: 'Möchte Motorbootprüfung Kat. A absolvieren. Theorieprüfung Schifffahrtsamt bereits bestanden.'
  }
];

export const INITIAL_SERVICES: ServiceOffer[] = [
  {
    id: 1,
    name: '10er VIP Abo Motorboot Kat. A (Luzern & NW)',
    description: 'Das Rundum-Sorglos-Paket für Ihre amtliche Schiffsführerprüfung Kat. A. Beinhaltet 10 intensive Praxisfahrstunden à 90 Minuten, Hafen- & Boxenmanöver, Boje über Bord sowie Prüfungsbegleitung. Inklusive Treibstoff und Versicherung.',
    offer_type: 'bootsfahrschule',
    target_audience: 'Prüfungsvorbereitung Kat. A',
    image_url: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: true,
    sessions_count: 10,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_hergiswil'],
    price_options: [
      { id: 101, service_id: 1, duration: 90, price: 1800.0, sessions_count: 10, sort_order: 1 }
    ]
  },
  {
    id: 2,
    name: '5er Abo Hafen- & Boxentraining Kat. A',
    description: 'Gezieltes Manövertraining für höchste Präzision: Rückwärts einparken in enge Boxen, Anlegen bei Seitenwind, Festmachen an Bojen und Verhalten bei Wellengang.',
    offer_type: 'bootsfahrschule',
    target_audience: 'Fortgeschrittene & Prüfungsendspurt',
    image_url: 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d17?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: true,
    sessions_count: 5,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_hergiswil'],
    price_options: [
      { id: 102, service_id: 2, duration: 90, price: 925.0, sessions_count: 5, sort_order: 1 }
    ]
  },
  {
    id: 3,
    name: 'Motorboot Einzellektion Kat. A',
    description: 'Flexible Praxisausbildung auf modernen Fahrschulbooten mit Doppelsteuerstand. Wählen Sie Ihre gewünschte Lektionsdauer frei nach Bedarf.',
    offer_type: 'bootsfahrschule',
    target_audience: 'Alle Ausbildungsstufen & Schnupperer',
    image_url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: false,
    sessions_count: 1,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_hergiswil', 'loc_buochs'],
    price_options: [
      { id: 103, service_id: 3, duration: 60, price: 135.0, sessions_count: 1, sort_order: 1 },
      { id: 104, service_id: 3, duration: 90, price: 195.0, sessions_count: 1, sort_order: 2 },
      { id: 105, service_id: 3, duration: 120, price: 260.0, sessions_count: 1, sort_order: 3 }
    ]
  },
  {
    id: 4,
    name: 'Spezial-Skipper Coaching & Nachtfahrt',
    description: 'Für Patentinhaber und Skipper, die ihre Sicherheit bei Dunkelheit, Radar- und GPS-Navigation oder bei schwierigen Wetterlagen vertiefen möchten.',
    offer_type: 'coaching',
    target_audience: 'Inhaber Schiffsführerausweis',
    image_url: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: false,
    sessions_count: 1,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_buochs'],
    price_options: [
      { id: 106, service_id: 4, duration: 120, price: 290.0, sessions_count: 1, sort_order: 1 },
      { id: 107, service_id: 4, duration: 180, price: 390.0, sessions_count: 1, sort_order: 2 }
    ]
  },
  {
    id: 5,
    name: 'Exklusiver Wertgutschein Nautic',
    description: 'Verschenken Sie unvergessliche Erlebnisse auf dem Vierwaldstättersee. Einlösbar für alle Ausbildungen, Coachings oder Bootsmieten.',
    offer_type: 'anderes',
    target_audience: 'Geschenk für Bootssportler & Paare',
    image_url: 'https://images.unsplash.com/photo-1513151233558-d860c5398176?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: false,
    sessions_count: 1,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_hergiswil', 'loc_buochs', 'loc_ennetbuergen'],
    price_options: [
      { id: 108, service_id: 5, duration: 60, price: 100.0, sessions_count: 1, sort_order: 1 },
      { id: 109, service_id: 5, duration: 90, price: 250.0, sessions_count: 1, sort_order: 2 },
      { id: 110, service_id: 5, duration: 120, price: 500.0, sessions_count: 1, sort_order: 3 }
    ]
  },
  {
    id: 6,
    name: 'Bootsmiete Boesch 620 Bimini',
    description: 'Erleben Sie Schweizer Edel-Mahagoni auf dem Vierwaldstättersee. Inklusive Sicherheitsausrüstung, Badeleiter, Sonnenverdeck und Self-Check-in.',
    offer_type: 'mieten',
    target_audience: 'Patentierte Skipper & Familien',
    image_url: 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: false,
    sessions_count: 1,
    location_ids: ['loc_luzern', 'loc_hergiswil'],
    price_options: [
      { id: 111, service_id: 6, duration: 240, price: 480.0, sessions_count: 1, sort_order: 1 },
      { id: 112, service_id: 6, duration: 480, price: 850.0, sessions_count: 1, sort_order: 2 }
    ]
  },
  {
    id: 7,
    name: 'Nautic Academy: Digitales Theorie- & Videopaket (1-Jahres-Abo)',
    description: 'Das offizielle Schweizer E-Learning-Komplettpaket für Motorboot Kat. A & Segeln Kat. D: Uneingeschränkter 365-Tage-Zugriff auf über 25 HD-Manöver-Lehrfilme (Hafenmanöver bei Seitenwind, Boje über Bord, Radeffekt), amtliche hochauflösende Schifffahrts-Bildtafeln (Lichterführung BSV, Sturmwarnung, Seezeichen) und den interaktiven ASA-Theorie- & Prüfungsfragentrainer.',
    offer_type: 'digital',
    target_audience: 'Fahrschüler & Bootsmieter (365 Tage gültig)',
    image_url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    is_active: true,
    is_subscription: true,
    sessions_count: 365,
    location_ids: ['loc_luzern', 'loc_stansstad', 'loc_hergiswil', 'loc_buochs', 'loc_ennetbuergen'],
    price_options: [
      { id: 113, service_id: 7, duration: 365, price: 149.0, sessions_count: 365, sort_order: 1 },
      { id: 114, service_id: 7, duration: 730, price: 239.0, sessions_count: 730, sort_order: 2 }
    ]
  }
];

export const INITIAL_PROMO_CODES: PromoCode[] = [
  {
    id: 1,
    code: 'BOOT50',
    description: 'CHF 50.- Willkommens-Rabatt für neue Fahrschüler',
    discount_type: 'fixed',
    discount_value: 50.0,
    min_order_amount: 300.0,
    max_uses: 50,
    used_count: 4,
    valid_from: '2026-01-01',
    valid_until: '2026-12-31',
    is_active: true
  },
  {
    id: 2,
    code: 'SOMMER2026',
    description: '10% Sommer-Aktion auf alle Abos & Coachings',
    discount_type: 'percentage',
    discount_value: 10.0,
    min_order_amount: 200.0,
    max_uses: null,
    used_count: 14,
    valid_from: '2026-03-01',
    valid_until: '2026-09-30',
    is_active: true
  },
  {
    id: 3,
    code: 'VIPNAUTIC',
    description: '15% VIP-Aktionsrabatt für 10er Pakete',
    discount_type: 'percentage',
    discount_value: 15.0,
    min_order_amount: 500.0,
    max_uses: 20,
    used_count: 12,
    valid_from: '2026-02-01',
    valid_until: '2026-12-31',
    is_active: true
  }
];

export const INITIAL_CUSTOMER_SUBSCRIPTIONS: CustomerSubscription[] = [
  {
    id: 1,
    customer_id: 1,
    service_id: 1,
    package_name: '10er VIP Abo Motorboot Kat. A (Luzern & NW)',
    total_sessions: 10,
    used_sessions: 2,
    purchased_at: '2026-03-01 10:15:00',
    expires_at: '2027-03-01',
    price_paid: 1800.0,
    payment_method: 'bank_transfer',
    payment_status: 'paid',
    order_id: 'CB-ORD-849201',
    location_name: 'Luzern & Stansstad'
  },
  {
    id: 2,
    customer_id: 1,
    service_id: 7,
    package_name: 'Nautic Academy: Digitales Theorie- & Videopaket (1-Jahres-Abo)',
    total_sessions: 365,
    used_sessions: 48,
    purchased_at: '2026-03-01 11:00:00',
    expires_at: '2027-03-01',
    price_paid: 149.0,
    payment_method: 'qr_bill',
    payment_status: 'paid',
    order_id: 'CB-ELE-981042',
    location_name: 'Online Campus (Schweizweit)',
    is_digital_elearning: true
  }
];

export const INITIAL_ELEARNING_VIDEOS: ElearningVideo[] = [
  {
    id: 1,
    title: 'Boxen- & Hafenmanöver rückwärts bei Seitenwind',
    category: 'maneuver',
    categoryLabel: 'Hafenmanöver',
    duration: '14:20 Min',
    durationSeconds: 860,
    thumbnail: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Fortgeschritten',
    description: 'Das anspruchsvollste Prüfungsmanöver auf Schweizer Seen: Schritt für Schritt rückwärts in eine enge Box manövrieren unter Ausnutzung des Radeffekts und Windabtrift.',
    chapters: [
      { title: 'Windbeobachtung & Vorbereitung', time: '00:00', seconds: 0 },
      { title: 'Leinen- & Fender-Positionierung', time: '02:40', seconds: 160 },
      { title: 'Radeffekt linksdrehend richtig ansetzen', time: '05:15', seconds: 315 },
      { title: 'Ruderlegen im Stillstand & Schubimpuls', time: '08:30', seconds: 510 },
      { title: 'Festmachen am Luv-Pfahl & Heckleine', time: '11:45', seconds: 705 }
    ],
    keyTakeaways: [
      'Immer gegen den Wind eindrehen und dem Luv-Pfahl strikt den Vorrang geben.',
      'Kurze, dosierte Gasstösse statt Dauergas – das Boot behält hydrodynamischen Ruderdruck.',
      'Fender erst nach Verlassen der Fahrrinne auf Relinghöhe ausbringen.'
    ],
    bsvRef: 'Art. 44 BSV & Richtlinien Schifffahrtsamt Kat. A'
  },
  {
    id: 2,
    title: 'Boje über Bord (Mann über Bord Manöver)',
    category: 'safety',
    categoryLabel: 'Sicherheit & Notfall',
    duration: '09:45 Min',
    durationSeconds: 585,
    thumbnail: 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Einsteiger',
    description: 'Lebensrettende Sofortmassnahmen bei Personen im Wasser: Auskuppeln, Heck abwenden, Rettungsring zuwerfen und gezielte Anfahrt gegen den Wind.',
    chapters: [
      { title: 'Alarmruf "Person über Bord" & Auskuppeln', time: '00:00', seconds: 0 },
      { title: 'Heck wegdrehen zur Personenabwendung', time: '01:50', seconds: 110 },
      { title: 'Rettungsring & ständiger Ausguck-Sichtkontakt', time: '03:30', seconds: 210 },
      { title: 'Anfahrtsschleife gegen den Wind (Halbwindkurs)', time: '06:10', seconds: 370 },
      { title: 'Stillstand vor der Boje auf Lee-Seite', time: '08:20', seconds: 500 }
    ],
    keyTakeaways: [
      'Propeller sofort stilllegen – Gashebel unverzüglich in Neutralstellung ziehen!',
      'Ein Besatzungsmitglied zeigt mit ausgestrecktem Arm permanent auf die Person im Wasser.',
      'Die finale Bergung erfolgt stets im Lee bei stehendem Motor, um Verletzungen auszuschliessen.'
    ],
    bsvRef: 'Art. 76 BSV Pflichten bei Notfällen'
  },
  {
    id: 3,
    title: 'Physik auf dem Wasser: Radeffekt & Schraubenschlag',
    category: 'physics',
    categoryLabel: 'Bootsphysik',
    duration: '11:15 Min',
    durationSeconds: 675,
    thumbnail: 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d17?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Fortgeschritten',
    description: 'Warum zieht das Heck beim Rückwärtsfahren nach Backbord? Visualisierte Strömungsmechanik von rechts- und linksdrehenden Schrauben mit Praxiserprobung.',
    chapters: [
      { title: 'Schraubengeometrie & Dichteunterschiede', time: '00:00', seconds: 0 },
      { title: 'Wirkung des Radeffekts bei Vorwärtsfahrt', time: '03:10', seconds: 190 },
      { title: 'Rückwärtsgang: Querströmung am Rumpf', time: '06:20', seconds: 380 },
      { title: 'Praxistest: Tellerwende auf engstem Raum', time: '09:00', seconds: 540 }
    ],
    keyTakeaways: [
      'Rechtsdrehende Schraube: Heck schlägt beim Rückwärtsfahren nach Backbord (links) aus.',
      'Linksdrehende Schraube: Heck schlägt beim Rückwärtsfahren nach Steuerbord (rechts) aus.',
      'Nutzen Sie den Radeffekt als wertvolle Unterstützung beim millimetergenauen Anlegen.'
    ],
    bsvRef: 'Theoretische Grundlagen Schifffahrt ASA'
  },
  {
    id: 4,
    title: 'Die 6 amtlichen Schifferknoten für die Kat. A Prüfung',
    category: 'knots',
    categoryLabel: 'Seemannschaft',
    duration: '08:30 Min',
    durationSeconds: 510,
    thumbnail: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Einsteiger',
    description: 'Alle 6 Pflichtknoten in hochauflösender Makro-Perspektive: Palstek, Webeleinstek (auch auf Slip), Achterknoten, Kreuzknoten, Schotstek und Klampenschlag.',
    chapters: [
      { title: 'Achterknoten (Stopperknoten)', time: '00:00', seconds: 0 },
      { title: 'Kreuzknoten für gleichstarke Leinen', time: '01:25', seconds: 85 },
      { title: 'Palstek (das unlösbare feste Auge)', time: '03:00', seconds: 180 },
      { title: 'Webeleinstek & Webeleinstek auf Slip', time: '04:45', seconds: 285 },
      { title: 'Schotstek für ungleiche Taustärken', time: '06:15', seconds: 375 },
      { title: 'Belegen auf der Klampe mit Kopfschlag', time: '07:10', seconds: 430 }
    ],
    keyTakeaways: [
      'Jeder Knoten muss blind, flüssig und in unter 15 Sekunden gesteckt werden können.',
      'Beim Belegen auf der Klampe darf der Kopfschlag nicht beklemmen.',
      'Knoten schwächen das Tauwerk um 30–50 % – sorgfältiges Stecken schützt Leinen.'
    ],
    bsvRef: 'Prüfungsanforderungen Schifffahrtsamt'
  },
  {
    id: 5,
    title: 'Nachtfahrt, Radarnavigation & Schweizer Sturmwarnung',
    category: 'navigation',
    categoryLabel: 'Navigation & Wetter',
    duration: '16:50 Min',
    durationSeconds: 1010,
    thumbnail: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Experte',
    description: 'Sichere Schiffsführung bei Nacht und Unwetter auf Schweizer Seen: Optische Signale, Lichterführung, GPS-Navigation und Notfallprotokoll bei 40 vs. 90 Blitzen.',
    chapters: [
      { title: 'Nachtfahrts-Vorschriften nach BSV', time: '00:00', seconds: 0 },
      { title: 'Lichterführung: Topp-, Seiten- & Hecklicht', time: '04:20', seconds: 260 },
      { title: 'Starkwind- vs. Sturmwarnung (MeteoSchweiz)', time: '08:40', seconds: 520 },
      { title: 'Routenwahl im Sturm & Nothäfen Vierwaldstättersee', time: '12:30', seconds: 750 }
    ],
    keyTakeaways: [
      '40 Blitze/min = Starkwindwarnung (Böen 25-33 kn) -> Erhöhte Wachsamkeit & Ufernähe.',
      '90 Blitze/min = Sturmwarnung (Böen ab 34 kn) -> Sofort nächsten Schutzhafen anlaufen!',
      'Instrumentenbeleuchtung bei Dunkelheit dimmen, um die natürliche Nachtsicht nicht zu beeinträchtigen.'
    ],
    bsvRef: 'BSV Art. 18-35 & Art. 40'
  },
  {
    id: 6,
    title: 'Richtiges Ankern & Liegeplatz-Sicherung im See',
    category: 'safety',
    categoryLabel: 'Seemannschaft',
    duration: '12:10 Min',
    durationSeconds: 730,
    thumbnail: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    difficulty: 'Fortgeschritten',
    description: 'Ankerpraxis für Schweizer Tiefwasserseen: Ankergeschirr, Kettenvorlauf, Schwoikreisberechnung, Einfahrprobe und sicheres Aufholen bei Wind.',
    chapters: [
      { title: 'Ankergrund & Tiefenprüfung mit Echolot', time: '00:00', seconds: 0 },
      { title: 'Ausbringen der 3- bis 5-fachen Wassertiefe', time: '03:15', seconds: 195 },
      { title: 'Rückwärts-Einfahrprobe (Festkommen)', time: '06:40', seconds: 400 },
      { title: 'Schwoikreis & Abstand zu Ufer und Nachbarn', time: '09:20', seconds: 560 }
    ],
    keyTakeaways: [
      'Niemals nur mit Leine ankern – mindestens 3 bis 5 Meter Kettenvorlauf sind Pflicht.',
      'Bei steil abfallendem Seegrund (wie im Vierwaldstättersee) auf Abrutschgefahr achten.',
      'Peilpunkte am Ufer merken, um das Slippen des Ankers sofort zu erkennen.'
    ],
    bsvRef: 'BSV Art. 48 & Seemannschaft'
  }
];

export const INITIAL_ELEARNING_CHARTS: ElearningChart[] = [
  {
    id: 1,
    title: 'BSV Lichterführung auf Schweizer Gewässern',
    subtitle: 'Amtliche Beleuchtungsvorschriften für Motor-, Segel- & Vorrangschiffe',
    category: 'Lichter & Signale',
    imageUrl: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?auto=format&fit=crop&w=1200&q=80',
    bsvArticle: 'BSV Art. 18 – 35',
    description: 'Visualisierung aller gesetzlich vorgeschriebenen Lichter nach der Binnenschifffahrtsverordnung (BSV) bei Nacht und unsichtigem Wetter.',
    keyPoints: [
      'Topplicht (weiss): Leuchtet im Winkel von 225° von vorne bis 22.5° hinter die Quere.',
      'Steuerbordlicht (grün, 112.5°) & Backbordlicht (rot, 112.5°): Sektoren genau abgedeckt.',
      'Hecklicht (weiss, 135°): Leuchtet von hinten nach beiden Seiten.',
      'Vorrangschiffe (Kursschiffe): Weisses Topplicht + grünes Rundumlicht (360°).'
    ],
    tips: 'Segelschiffe unter Motor gelten rechtlich als Motorboote und müssen zwingend zusätzlich das weisse Topplicht einschalten!'
  },
  {
    id: 2,
    title: 'Schweizer Seezeichen & Bojensystem',
    subtitle: 'Kardinalzeichen, Fahrwassermarkierungen & Uferschutzzonen',
    category: 'Seezeichen & Betonnung',
    imageUrl: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80',
    bsvArticle: 'BSV Anhänge 1–3',
    description: 'Übersicht über die Betonnung auf Schweizer Binnenseen nach dem IALA-A-System: Gefahrenstellen, Kardinalzeichen und Uferschutzzonen.',
    keyPoints: [
      'Kardinal Nord: Schwarz über Gelb, Spitzen nach oben | Süd: Gelb über Schwarz, Spitzen nach unten.',
      'Kardinal Ost: Schwarz-Gelb-Schwarz (Spitzen auseinander) | West: Gelb-Schwarz-Gelb (Spitzen zueinander).',
      'Gelbe Kugelbojen: Markierung der 150m- bzw. 300m-Uferschutzzone (Höchstgeschwindigkeit 10 km/h).',
      'Verbotszonen: Weisse Bojen oder Tafeln mit rotem Rand signalisieren Naturschutz- oder Badebereiche.'
    ],
    tips: 'Passieren Sie Gefahrenstellen immer auf der durch das Kardinalzeichen ausgewiesenen Himmelsrichtung (z.B. nördlich der Nord-Tonne).'
  },
  {
    id: 3,
    title: 'Die offizielle BSV Vortritts-Pyramide',
    subtitle: 'Wer weicht wem aus? Amtliche Rangordnung auf Schweizer Gewässern',
    category: 'Vorfahrtsregeln',
    imageUrl: 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&q=80',
    bsvArticle: 'BSV Art. 44',
    description: 'Die verbindliche Vorfahrts-Hierarchie auf allen Schweizer Binnenseen vom Kursschiff bis zum Sportmotorboot.',
    keyPoints: [
      '1. Rang: Vorrangschiffe (Kursschiffe mit grünem Ball am Tag / grünem Rundumlicht bei Nacht).',
      '2. Rang: Güterschiffe & gewerbsmässige Schleppverbände.',
      '3. Rang: Berufsfischer mit gelber Kugel oder gelbem Rundumlicht.',
      '4. Rang: Segelfahrzeuge (ausser wenn der Hilfsmotor läuft).',
      '5. Rang: Ruderboote, Kanus & Stand-Up-Paddler (Muskelkraft).',
      '6. Rang: Motorboote & Mietboote (müssen ausnahmslos allen Vorgenannten ausweichen!).'
    ],
    tips: 'Zwei Motorboote auf Gegenkurs: Beide weichen nach Steuerbord (rechts) aus. Bei Kreuzungskursen weicht das von links kommende Boot aus (Rechtsvortritt).'
  },
  {
    id: 4,
    title: 'Sturm- und Starkwindwarnsystem auf Schweizer Seen',
    subtitle: 'Starkwindwarnung vs. Sturmwarnung – Blitzfrequenzen & Sicherheitsmassnahmen',
    category: 'Wetter & Notfall',
    imageUrl: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
    bsvArticle: 'BSV Art. 40',
    description: 'Das automatische Warnleuchtensystem der kantonalen Schifffahrtsämter und MeteoSchweiz rund um den Vierwaldstättersee.',
    keyPoints: [
      'Starkwindwarnung (40 orange Blitze / Min.): Gefahr von Windböen von 25 bis 33 Knoten (Windstärke 6-7 Bft). Erhöhte Wachsamkeit!',
      'Sturmwarnung (90 orange Blitze / Min.): Unmittelbare Gefahr von Sturmböen ab 34 Knoten (Windstärke 8+ Bft).',
      'Pflichten bei Sturmwarnung: Schwimmwesten anlegen, Motor starten, Leinen klarmachen und ohne Verzug nächsten Schutzhafen anlaufen.',
      'Föhnstürme in der Zentralschweiz können binnen 10 Minuten von Windstille auf 45 Knoten umschlagen.'
    ],
    tips: 'Sollte das Erreichen des Heimathafens nicht mehr sicher sein, laufen Sie sofort einen Ausweichhafen (z.B. Hergiswil, Stansstad, Kehrsiten) an.'
  },
  {
    id: 5,
    title: 'Die 6 amtlichen Prüfungs-Knoten im Grossformat',
    subtitle: 'Schritt-für-Schritt Detailgrafiken aller praktischen Prüfungsknoten',
    category: 'Seemannschaft',
    imageUrl: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=80',
    bsvArticle: 'Prüfungsvorschriften Schifffahrtsamt',
    description: 'Makro-Illustrationen zur korrekten Leinenführung bei allen 6 geforderten Knoten der praktischen Schiffsführerprüfung.',
    keyPoints: [
      'Achterknoten: Zuverlässiger Stopperknoten am Leinenende.',
      'Kreuzknoten: Verbinden von zwei gleichstarken Leinen unter Zug.',
      'Palstek: Bildet ein festes, nicht zuziehendes Auge für Pfähle und Bojen.',
      'Webeleinstek: Festmachen an Dalben oder Relingsrohren (auch mit Slipstek).',
      'Schotstek: Sichere Verbindung von zwei unterschiedlich dicken Tauen.',
      'Klampenschlag mit Kopfschlag: Richtiges Belegen an der Schiffsklampe.'
    ],
    tips: 'Der Klampenkopfschlag muss so liegen, dass die lose Part unter der Wicklung liegt, sich jedoch ohne Werkzeug jederzeit wieder lösen lässt.'
  }
];

export const INITIAL_ELEARNING_QUIZ: ElearningQuizQuestion[] = [
  {
    id: 1,
    question: 'Wie viele orange Blitze pro Minute sendet das Warnlicht bei einer akuten STURMWARNUNG auf Schweizer Seen aus?',
    options: [
      '20 Blitze pro Minute',
      '40 Blitze pro Minute',
      '90 Blitze pro Minute',
      '120 Blitze pro Minute'
    ],
    correctIndex: 2,
    explanation: 'Gemäss Art. 40 BSV signalisieren 40 Blitze/min eine Starkwindwarnung (Böen 25-33 kn) und 90 Blitze/min eine akute Sturmwarnung (Böen ab 34 kn). Bei 90 Blitzen ist unverzüglich der nächste Schutzhafen anzulaufen.',
    lawRef: 'BSV Art. 40 Abs. 2'
  },
  {
    id: 2,
    question: 'Zwei motorisierte Schiffe nähern sich auf freiem See auf direktem Gegenkurs. Wie müssen die Schiffsführer ausweichen?',
    options: [
      'Beide Schiffe weichen nach Steuerbord (nach rechts) aus',
      'Das windwärtige Schiff hat Vortritt, das andere weicht nach links aus',
      'Das kleinere Boot muss stoppen und das grössere Boot passieren lassen',
      'Beide Schiffe weichen nach Backbord (nach links) aus'
    ],
    correctIndex: 0,
    explanation: 'Gemäss Art. 44 Abs. 2 BSV müssen beide Motorboote bei direktem Gegenkurs nach Steuerbord (rechts) ausweichen, sodass sie einander an Backbord (rot an rot) passieren.',
    lawRef: 'BSV Art. 44 Abs. 2'
  },
  {
    id: 3,
    question: 'Welche Promillegrenze gilt für Schiffsführer und Rudergänger von motorisierten Sport- und Mietbooten in der Schweiz?',
    options: [
      '0.50 Promille wie im Strassenverkehr',
      '0.20 Promille Toleranzgrenze',
      'Strikt 0.00 Promille (Nulltoleranz)',
      '0.80 Promille bei Tagfahrten'
    ],
    correctIndex: 2,
    explanation: 'Seit dem 1. Januar 2020 gilt auf allen Schweizer Gewässern eine strikte 0.0‰-Promillegrenze für Schiffsführer (Nulltoleranz). Zuwiderhandlungen führen zum sofortigen Führerscheinentzug.',
    lawRef: 'BSV Art. 78a'
  }
];

export const INITIAL_BOATS: Boat[] = [
  {
    id: 1,
    name: 'Boesch 620 Bimini',
    type: 'Motorboot (Edel-Mahagoni)',
    registration: 'CH-LU-84920',
    berth: 'Steg 1, Box 4 (Hergiswil NW)',
    status: 'available',
    fuel_capacity: 160,
    current_fuel_percent: 100,
    hourly_consumption: 12.5,
    telemetry_gateway_id: 'NMEA2K-BSH-620-BIMINI',
    image_url: 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
    current_session: null
  },
  {
    id: 2,
    name: 'Boesch 620 Riviera',
    type: 'Motorboot Klassiker Kat. A',
    registration: 'CH-LU-10492',
    berth: 'Steg 3, Platz 12 (Tiefenbrunnen Luzern)',
    status: 'available',
    fuel_capacity: 150,
    current_fuel_percent: 100,
    hourly_consumption: 13.0,
    telemetry_gateway_id: 'NMEA2K-BSH-620-RIVIERA',
    image_url: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
    current_session: null
  },
  {
    id: 3,
    name: 'Axopar 28 T-Top Charter',
    type: 'Offshore Sport-Cruiser',
    registration: 'CH-LU-77401',
    berth: 'Steg 2, Marina Kastanienbaum (LU)',
    status: 'available',
    fuel_capacity: 280,
    current_fuel_percent: 95,
    hourly_consumption: 22.0,
    telemetry_gateway_id: 'NMEA2K-AXP-28-TTOP',
    image_url: 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d17?auto=format&fit=crop&w=800&q=80',
    current_session: null
  },
  {
    id: 4,
    name: 'Nautique 210 Super Air',
    type: 'Wakeboard- & Schulungsboot',
    registration: 'CH-NW-29184',
    berth: 'Steg 1, Bürkliplatz (Luzern)',
    status: 'available',
    fuel_capacity: 180,
    current_fuel_percent: 100,
    hourly_consumption: 18.0,
    telemetry_gateway_id: 'NMEA2K-NTQ-210-AIR',
    image_url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
    current_session: null
  }
];

export const INITIAL_BOAT_CHECKINS: BoatCheckin[] = [
  {
    id: 1,
    appointment_id: 100,
    customer_id: 1,
    boat_id: 1,
    boat_name: 'Boesch 620 Bimini',
    checked_in_at: '2026-03-12T13:45:00',
    check_in_code: 'CB-CHK-48291',
    fitness_confirmed: true,
    license_confirmed: true,
    safety_confirmed: true,
    emergency_contact_confirmed: true,
    boat_condition_confirmed: true,
    boat_damage_reported: false,
    boat_damage_photos: [],
    boat_damage_notes: '',
    telemetry_session_id: 'TEL-481920-312',
    external_script_triggered: true,
    external_script_message: 'NMEA 2000 Gateway CAN-ID 0x28 verbunden. Aufzeichnung aktiv.'
  }
];

export const INITIAL_BOAT_CHECKOUTS: BoatCheckout[] = [
  {
    id: 1,
    check_in_id: 1,
    appointment_id: 100,
    customer_id: 1,
    boat_id: 1,
    boat_name: 'Boesch 620 Bimini',
    checked_out_at: '2026-03-12T16:15:00',
    duration_minutes: 150,
    fuel_status: 'needs_fuel',
    liters_refueled: 0,
    sensor_fuel_liters_used: 18.4,
    sensor_engine_hours: 1.5,
    fuel_cost_total: 41.40,
    fuel_price_per_liter: 2.25,
    return_condition_confirmed: true,
    return_damage_reported: false,
    return_damage_photos: [],
    return_damage_notes: '',
    cleanliness_rating: 'clean',
    mooring_secured: true,
    keys_returned: true,
    equipment_checked: true,
    external_script_queried: true,
    external_script_data: {
      fuel_liters_used: 18.4,
      engine_hours: 1.5,
      avg_rpm: 2180,
      max_rpm: 3350,
      coolant_temp_c: 83.0,
      battery_v: 14.2,
      nmea_signature: 'NMEA2K-HASH-5891AF2-VERIFIED'
    }
  }
];

export const INITIAL_CMS_BLOCKS: CmsBlock[] = [
  {
    id: 1,
    type: 'hero',
    title: 'Leinen los! Deine Bootsfahrschule & Charter am See',
    subtitle: 'Professionelle Ausbildung für Motorboot Kat. A & Segeln Kat. D sowie exklusive Bootsvermietung',
    content: 'Lerne das Bootfahren von Grund auf bei erfahrenen und lizenzierten Skippern. Flexible Ausbildungszeiten von Montag bis Sonntag, transparenter Ausbildungsstand und direkte Terminbuchung.',
    sort_order: 10,
    is_visible: true,
    extra: { btn_text: 'Zum Magic-Link Login', btn_link: '#magic-section' }
  },
  {
    id: 2,
    type: 'features',
    title: 'Unser Angebot auf dem Wasser',
    subtitle: 'Von den ersten Manövern bis zum amtlichen Schiffsführerausweis und Wochenend-Charter',
    content: '',
    sort_order: 20,
    is_visible: true,
    extra: {
      items: [
        { title: 'Motorboot Kat. A', text: 'Intensive Prüfungsvorbereitung, Hafenmanöver, An- und Ablegen, Mann-über-Bord und Knotenkunde mit hoher Erfolgsquote.' },
        { title: 'Segelausbildung Kat. D', text: 'Von den Segelgrundlagen bis zur Regatta-Taktik. Praxisnahe Ausbildung bei jedem Wind auf dem See.' },
        { title: 'Flottenvermietung & Charter', text: 'Gepflegte Boote (Boesch, Axopar) für Ausflüge, Sonnenuntergangs-Cruises oder private Events mieten.' },
        { title: 'Crew & Schüler Portal', text: 'Passwortloser Magic-Link Zugang: Alle Fahrstunden, Ausbildungskarten, Termine und Abrechnungen auf einen Blick.' }
      ]
    }
  },
  {
    id: 3,
    type: 'two_column',
    title: 'Warum Nautic Bootsfahrschule?',
    subtitle: 'Sicherheit, modernste Technik und persönliche Betreuung',
    content: 'Unsere Ausbildungsflotte erfüllt höchste Sicherheitsstandards und wird laufend gewartet. Mit flexiblen Ausbildungszeiten von Montag bis Sonntag passen wir uns deinem Terminkalender an.\n\nAlle Fahrlehrer sind staatlich lizenziert und bringen jahrelange Erfahrung auf Schweizer Seen mit.',
    sort_order: 30,
    is_visible: true,
    extra: {
      col2_title: 'Transparenz & Direkte Buchung',
      col2_content: 'Keine versteckten Gebühren: Treibstoff, Versicherung und Prüfungsbegleitung sind transparent kalkuliert.\n\nPrüfungsstandorte: Schifffahrtsamt Zürich, Luzern und St. Gallen.'
    }
  },
  {
    id: 4,
    type: 'cta',
    title: 'Bereit für das Skipper-Patent?',
    subtitle: 'Fordere jetzt dein persönliches Konto an oder logge dich direkt per Magic-Link ein.',
    content: 'Als Schüler oder Crew-Mitglied benötigst du kein Passwort. Gib einfach deine E-Mail-Adresse ein und starte sofort.',
    sort_order: 40,
    is_visible: true,
    extra: { btn_text: 'Jetzt per Magic-Link einloggen', btn_link: '#magic-section' }
  }
];

export const INITIAL_INSTRUCTORS: Instructor[] = [
  {
    id: 1,
    name: 'Beat Vollenweider',
    title: 'Chef-Fahrcoach & Prüfungsexperte',
    email: 'beat@bootschule-see.ch',
    phone: '+41 79 345 67 89',
    public_mobile: '+41 79 345 67 89',
    is_mobile_public: true,
    color_code: '#0284c7',
    pay_rate_type: 'per_hour',
    pay_rate_amount: 65.0,
    payroll_external_code: 'PERS-101',
    license_number: 'CH-FL-2894',
    ahv_number: '756.8492.3012.44',
    bank_iban: 'CH89 0070 0110 0002 4491 8',
    bank_name: 'Zürcher Kantonalbank',
    bank_city: 'Zürich',
    address: 'Seestrasse 104, 8700 Küsnacht',
    hourly_rate: 65.0
  },
  {
    id: 2,
    name: 'Corinne Meier',
    title: 'Dipl. Bootsfahrlehrerin Kat. A & D',
    email: 'corinne@bootschule-see.ch',
    phone: '+41 78 612 90 44',
    public_mobile: '+41 78 612 90 44',
    is_mobile_public: true,
    color_code: '#10b981',
    pay_rate_type: 'per_hour',
    pay_rate_amount: 60.0,
    payroll_external_code: 'PERS-102',
    license_number: 'CH-FL-3419',
    ahv_number: '756.9182.4921.18',
    bank_iban: 'CH12 0023 0001 8920 3819 1',
    bank_name: 'UBS Switzerland AG',
    bank_city: 'Rapperswil',
    address: 'Hafenweg 14, 8640 Rapperswil',
    hourly_rate: 60.0
  },
  {
    id: 3,
    name: 'Marc Steiner',
    title: 'Fahrcoach & Segelinstruktor',
    email: 'marc@bootschule-see.ch',
    phone: '+41 76 555 81 20',
    public_mobile: '+41 76 555 81 20',
    is_mobile_public: false,
    color_code: '#8b5cf6',
    pay_rate_type: 'per_appointment',
    pay_rate_amount: 95.0,
    payroll_external_code: 'PERS-103',
    license_number: 'CH-FL-4102',
    ahv_number: '756.7721.9023.67',
    bank_iban: 'CH65 0900 0000 4829 1928 3',
    bank_name: 'PostFinance',
    bank_city: 'Bern',
    address: 'Lindenhofstrasse 8, 8001 Zürich',
    hourly_rate: 60.0
  }
];

export const INITIAL_APPOINTMENTS: Appointment[] = [
  {
    id: 101,
    date: '2026-03-02',
    start_time: '09:00:00',
    end_time: '10:30:00',
    duration_minutes: 90,
    service_name: 'Motorboot Fahrstunde Kat. A',
    boat_name: 'Boesch 620 Riviera',
    location_name: 'Steg 3 (Tiefenbrunnen)',
    customer_id: 1,
    customer_name: 'Lukas Berner',
    customer_email: 'lukas.berner@swiss-pilot.ch',
    customer_phone: '+41 79 400 11 22',
    instructor_id: 1,
    instructor_name: 'Beat Vollenweider',
    status: 'completed',
    price: 195.0,
    instructor_payout_amount: 97.5,
    instructor_payout_status: 'open',
    instructor_payout_note: 'Standard 90 Min (1.5h à CHF 65)'
  },
  {
    id: 102,
    date: '2026-03-04',
    start_time: '14:00:00',
    end_time: '16:00:00',
    duration_minutes: 120,
    service_name: 'Hafenmanöver Intensivtraining',
    boat_name: 'Nautique 210',
    location_name: 'Steg 1 (Bürkliplatz)',
    customer_id: 2,
    customer_name: 'Sophie Keller',
    customer_email: 'sophie.keller@keller-arch.ch',
    customer_phone: '+41 78 890 33 44',
    instructor_id: 1,
    instructor_name: 'Beat Vollenweider',
    status: 'completed',
    price: 260.0,
    instructor_payout_amount: 130.0,
    instructor_payout_status: 'open',
    instructor_payout_note: '2.0h à CHF 65'
  },
  {
    id: 103,
    date: '2026-03-08',
    start_time: '10:00:00',
    end_time: '11:30:00',
    duration_minutes: 90,
    service_name: 'Motorboot Fahrstunde Kat. A',
    boat_name: 'Boesch 620 Riviera',
    location_name: 'Steg 3 (Tiefenbrunnen)',
    customer_id: 3,
    customer_name: 'Patrick Odermatt',
    customer_email: 'p.odermatt@outlook.com',
    customer_phone: '+41 79 123 99 88',
    instructor_id: 2,
    instructor_name: 'Corinne Meier',
    status: 'completed',
    price: 195.0,
    instructor_payout_amount: 90.0,
    instructor_payout_status: 'exported',
    instructor_payout_note: '1.5h à CHF 60 (Lohnlauf März 1)'
  },
  {
    id: 104,
    date: '2026-03-10',
    start_time: '13:00:00',
    end_time: '15:00:00',
    duration_minutes: 120,
    service_name: 'Segelausbildung Kat. D Praxis',
    boat_name: 'Surprise Segelboot',
    location_name: 'Hafen Enge',
    customer_id: 1,
    customer_name: 'Lukas Berner',
    customer_email: 'lukas.berner@swiss-pilot.ch',
    customer_phone: '+41 79 400 11 22',
    instructor_id: 3,
    instructor_name: 'Marc Steiner',
    status: 'completed',
    price: 240.0,
    instructor_payout_amount: 95.0,
    instructor_payout_status: 'paid',
    instructor_payout_note: 'Pauschale pro Termin à CHF 95'
  },
  {
    id: 105,
    date: '2026-03-16',
    start_time: '15:00:00',
    end_time: '16:30:00',
    duration_minutes: 90,
    service_name: 'Prüfungsvorbereitung Boje über Bord',
    boat_name: 'Boesch 620 Riviera',
    location_name: 'Steg 3 (Tiefenbrunnen)',
    customer_id: 1,
    customer_name: 'Lukas Berner',
    customer_email: 'lukas.berner@swiss-pilot.ch',
    customer_phone: '+41 79 400 11 22',
    instructor_id: 1,
    instructor_name: 'Beat Vollenweider',
    status: 'confirmed',
    price: 195.0,
    instructor_payout_amount: 97.5,
    instructor_payout_status: 'open',
    instructor_payout_note: 'Geplante Fahrt'
  },
  {
    id: 106,
    date: '2026-03-20',
    start_time: '09:00:00',
    end_time: '11:00:00',
    duration_minutes: 120,
    service_name: 'Amtliche Führerprüfung Kat. A',
    boat_name: 'Boesch 620 Riviera',
    location_name: 'Schifffahrtsamt Oberrieden',
    customer_id: 1,
    customer_name: 'Lukas Berner',
    customer_email: 'lukas.berner@swiss-pilot.ch',
    customer_phone: '+41 79 400 11 22',
    instructor_id: 1,
    instructor_name: 'Beat Vollenweider',
    status: 'confirmed',
    price: 280.0,
    instructor_payout_amount: 130.0,
    instructor_payout_status: 'open',
    instructor_payout_note: 'Begleitung Prüfung'
  },
  {
    id: 107,
    date: '2026-03-24',
    start_time: '14:00:00',
    end_time: '18:00:00',
    duration_minutes: 240,
    service_name: 'Bootsmiete Boesch 620 Bimini',
    boat_name: 'Boesch 620 Bimini',
    location_name: 'Steg 1 (Hergiswil NW)',
    customer_id: 1,
    customer_name: 'Roland Grüter',
    customer_email: 'office@studiocreativo.ch',
    customer_phone: '+41 79 000 00 00',
    instructor_id: 1,
    instructor_name: 'Self-Check-in (Ohne Coach)',
    status: 'confirmed',
    price: 480.0,
    instructor_payout_amount: 0.0,
    instructor_payout_status: 'open',
    instructor_payout_note: 'Bootsmiete Selbstfahrer (NMEA 2000 Telemetrie)'
  }
];

export const INITIAL_AVAILABILITIES: CoachAvailability[] = [
  {
    id: 1,
    instructor_id: 1,
    date: '2026-03-24',
    start_time: '08:00:00',
    end_time: '17:00:00',
    type: 'vacation',
    note: 'Sperrzeit Frühlingsferien'
  },
  {
    id: 2,
    instructor_id: 1,
    date: '2026-03-28',
    start_time: '08:00:00',
    end_time: '12:00:00',
    type: 'unavailable',
    note: 'Zahnarzttermin Küsnacht'
  },
  {
    id: 3,
    instructor_id: 2,
    date: '2026-03-12',
    start_time: '12:00:00',
    end_time: '15:00:00',
    type: 'unavailable',
    note: 'Weiterbildung Nothelfer am Wasser'
  }
];

export const INITIAL_WORKING_HOURS: CoachWorkingHour[] = [
  {
    id: 1,
    instructor_id: 1,
    date: '2026-03-05',
    start_time: '08:00:00',
    end_time: '11:30:00',
    break_minutes: 30,
    total_hours: 3.0,
    rate_amount: 65.0,
    total_amount: 195.0,
    category: 'maintenance',
    status: 'approved',
    note: 'Saison-Einwasserung & Sicherheitscheck Boesch 620'
  },
  {
    id: 2,
    instructor_id: 1,
    date: '2026-03-11',
    start_time: '13:30:00',
    end_time: '15:30:00',
    break_minutes: 0,
    total_hours: 2.0,
    rate_amount: 65.0,
    total_amount: 130.0,
    category: 'admin',
    status: 'submitted',
    note: 'Terminkoordination & Dossierprüfung Schifffahrtsamt'
  }
];
