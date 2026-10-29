import React, { useState, useMemo } from 'react';
import {
  Calendar,
  Clock,
  DollarSign,
  User,
  Users,
  Compass,
  Ship,
  FileSpreadsheet,
  CheckCircle2,
  AlertTriangle,
  Send,
  Link,
  Shield,
  Layers,
  ChevronRight,
  RefreshCw,
  Download,
  Filter,
  Check,
  X,
  CreditCard,
  Briefcase,
  Award,
  Plus,
  Eye,
  EyeOff,
  Lock,
  Phone,
  Mail,
  MapPin,
  FileText,
  LogIn,
  UserPlus,
  UserCheck,
  Key,
  Palette,
  LayoutTemplate,
  Sparkles,
  Wind,
  Sun,
  Waves,
  Wallet,
  LayoutDashboard
} from 'lucide-react';

// --- Datentypen & Mock-Daten der Bootsfahrschule & CMS ---

interface Instructor {
  id: number;
  name: string;
  title: string;
  email: string;
  phone: string;
  public_mobile: string;
  is_mobile_public: boolean;
  color_code: string;
  pay_rate_type: 'per_hour' | 'per_appointment';
  pay_rate_amount: number;
  payroll_external_code: string;
  license_number: string;
  // HR Details
  ahv_number?: string;
  bank_iban?: string;
  bank_name?: string;
  bank_city?: string;
  address?: string;
  hourly_rate?: number;
}

interface Appointment {
  id: number;
  date: string;
  start_time: string;
  end_time: string;
  duration_minutes: number;
  service_name: string;
  boat_name: string;
  location_name: string;
  customer_id: number;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  instructor_id: number;
  instructor_name: string;
  status: 'requested' | 'confirmed' | 'completed' | 'cancelled_customer';
  price: number;
  instructor_payout_amount: number;
  instructor_payout_status: 'open' | 'exported' | 'paid';
  instructor_payout_note: string;
}

interface CoachAvailability {
  id: number;
  instructor_id: number;
  date: string;
  start_time?: string;
  end_time?: string;
  type: 'vacation' | 'sick' | 'military' | 'unavailable' | 'available';
  note: string;
}

interface CoachWorkingHour {
  id: number;
  instructor_id: number;
  date: string;
  start_time: string;
  end_time: string;
  break_minutes: number;
  total_hours: number;
  rate_amount: number;
  total_amount: number;
  category: 'lesson' | 'exam' | 'admin' | 'maintenance' | 'other';
  status: 'draft' | 'submitted' | 'approved' | 'exported';
  note: string;
}

interface AccountRequest {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  desired_role: 'instructor' | 'student' | 'renter' | 'admin';
  assigned_profile?: 'instructor' | 'student' | 'renter' | 'admin';
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  approved_at?: string;
  admin_notes?: string;
  notes: string;
}

const INITIAL_ACCOUNT_REQUESTS: AccountRequest[] = [
  {
    id: 1,
    first_name: 'Stefan',
    last_name: 'Brunner',
    email: 'stefan.brunner@see-experience.ch',
    phone: '+41 79 555 12 34',
    desired_role: 'instructor',
    status: 'pending',
    created_at: '2026-03-24 14:15',
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
    notes: 'Möchte Motorbootprüfung Kat. A absolvieren. Theorieprüfung Schifffahrtsamt bereits bestanden.'
  }
];

export interface CmsBlock {
  id: number;
  type: 'hero' | 'features' | 'two_column' | 'cta' | 'text';
  title: string;
  subtitle: string;
  content: string;
  sort_order: number;
  is_visible: boolean;
  extra?: any;
}

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

const INITIAL_INSTRUCTORS: Instructor[] = [
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

const INITIAL_APPOINTMENTS: Appointment[] = [
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
  }
];

const INITIAL_AVAILABILITIES: CoachAvailability[] = [
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

const INITIAL_WORKING_HOURS: CoachWorkingHour[] = [
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

export default function App() {
  const [activeTab, setActiveTab] = useState<'payroll' | 'student' | 'instructor' | 'login' | 'cms' | 'architecture'>('cms');

  // State für Startseite bearbeiten & Mini-CMS (Vorlagen & Inhaltsblöcke)
  const [cmsActiveTemplate, setCmsActiveTemplate] = useState<'nautic' | 'standard' | 'corporate' | 'minimal'>('nautic');
  const [cmsPageTitle, setCmsPageTitle] = useState<string>('Nautic – Bootsfahrschule & Vermietung');
  const [cmsPageSubtitle, setCmsPageSubtitle] = useState<string>('Fahrstunden Kat. A & D, Bootscharter, Crew & Disposition');
  const [cmsPageDescription, setCmsPageDescription] = useState<string>('Offizielle Ausbildungs- und Charterplattform. Buche deine Lektionen oder melde dich per sicherem Magic-Link an.');
  const [cmsPrimaryColor, setCmsPrimaryColor] = useState<string>('#0284c7');
  const [cmsSecondaryColor, setCmsSecondaryColor] = useState<string>('#0369a1');
  const [cmsBgColor, setCmsBgColor] = useState<string>('#f0f9ff');
  const [cmsTextColor, setCmsTextColor] = useState<string>('#0f172a');
  const [cmsLayout, setCmsLayout] = useState<'contained' | 'wide' | 'full'>('wide');
  const [cmsBlocks, setCmsBlocks] = useState<CmsBlock[]>(INITIAL_CMS_BLOCKS);
  const [cmsSubTab, setCmsSubTab] = useState<'templates' | 'blocks' | 'design' | 'preview'>('templates');
  const [cmsEditingBlock, setCmsEditingBlock] = useState<CmsBlock | null>(null);
  const [cmsToast, setCmsToast] = useState<string | null>(null);

  const handleApplyCmsTemplate = (key: 'nautic' | 'standard' | 'corporate' | 'minimal') => {
    setCmsActiveTemplate(key);
    if (key === 'nautic') {
      setCmsPageTitle('Nautic – Bootsfahrschule & Vermietung');
      setCmsPageSubtitle('Fahrstunden Kat. A & D, Bootscharter, Crew & Disposition');
      setCmsPageDescription('Offizielle Ausbildungs- und Charterplattform. Buche deine Lektionen oder melde dich per sicherem Magic-Link an.');
      setCmsPrimaryColor('#0284c7');
      setCmsSecondaryColor('#0369a1');
      setCmsBgColor('#f0f9ff');
      setCmsTextColor('#0f172a');
      setCmsLayout('wide');
      setCmsBlocks(INITIAL_CMS_BLOCKS);
      setCmsToast("Nautic-Vorlage erfolgreich angewendet! 4 maritime Abschnitte wurden im Tab 'Inhaltsblöcke' angelegt.");
    } else if (key === 'standard') {
      setCmsPageTitle('Willkommen im Kunden- & Mitarbeiterportal');
      setCmsPageSubtitle('Sicherer Zugriff über passwortlose Magic-Links');
      setCmsPageDescription('Zentraler Einstiegspunkt für Kunden, Partner und Mitarbeiter.');
      setCmsPrimaryColor('#0d6efd');
      setCmsSecondaryColor('#6c757d');
      setCmsBgColor('#f8fafc');
      setCmsTextColor('#222222');
      setCmsLayout('contained');
      setCmsBlocks([INITIAL_CMS_BLOCKS[0], INITIAL_CMS_BLOCKS[1]]);
      setCmsToast("Standard-Vorlage angewendet.");
    } else if (key === 'corporate') {
      setCmsPageTitle('Unternehmensportal & Services');
      setCmsPageSubtitle('Exklusiver Zugang für autorisierte Partner & Kunden');
      setCmsPageDescription('Verwalte deine Projekte, Verträge und Services an einem zentralen Ort.');
      setCmsPrimaryColor('#1e293b');
      setCmsSecondaryColor('#f59e0b');
      setCmsBgColor('#ffffff');
      setCmsTextColor('#0f172a');
      setCmsLayout('wide');
      setCmsBlocks([INITIAL_CMS_BLOCKS[0], INITIAL_CMS_BLOCKS[2]]);
      setCmsToast("Corporate-Vorlage angewendet.");
    } else {
      setCmsPageTitle('Portal Login');
      setCmsPageSubtitle('Bitte authentifiziere dich mit deiner registrierten E-Mail-Adresse');
      setCmsPageDescription('Passwortlose Anmeldung.');
      setCmsPrimaryColor('#0284c7');
      setCmsSecondaryColor('#64748b');
      setCmsBgColor('#f8fafc');
      setCmsTextColor('#1e293b');
      setCmsLayout('contained');
      setCmsBlocks([]);
      setCmsToast("Minimal-Vorlage angewendet (reiner Magic-Login ohne Zusatzblöcke).");
    }
    setTimeout(() => setCmsToast(null), 4000);
  };

  const handleUpdateBlock = (updated: CmsBlock) => {
    setCmsBlocks(prev => prev.map(b => b.id === updated.id ? updated : b));
    setCmsEditingBlock(null);
    setCmsToast(`Inhaltsblock "${updated.title}" wurde erfolgreich aktualisiert.`);
    setTimeout(() => setCmsToast(null), 3000);
  };

  const handleToggleBlockVisible = (id: number) => {
    setCmsBlocks(prev => prev.map(b => b.id === id ? { ...b, is_visible: !b.is_visible } : b));
  };

  const handleDeleteBlock = (id: number) => {
    setCmsBlocks(prev => prev.filter(b => b.id !== id));
  };

  // State für Nautic Logon & Account-Anforderung
  const [authViewMode, setAuthViewMode] = useState<'login' | 'register'>('login');
  const [nauticLoginStep, setNauticLoginStep] = useState<'email' | 'code'>('email');
  const [nauticLoginTestMode, setNauticLoginTestMode] = useState<boolean>(false);
  const [codeDigits, setCodeDigits] = useState<string[]>(['R', 'O', 'L', 'A', 'N', 'D', '1', '2', '3', '4']);
  const [loginEmail, setLoginEmail] = useState<string>('beat@bootschule-see.ch');
  const [loginCode, setLoginCode] = useState<string>('ROLAND1234');
  const [loginSuccessMsg, setLoginSuccessMsg] = useState<string | null>(null);
  const [loginRememberMe, setLoginRememberMe] = useState<boolean>(true);

  // Registrierungs-State (Account anfordern & Admin-Prüfung)
  const [regFirstName, setRegFirstName] = useState<string>('');
  const [regLastName, setRegLastName] = useState<string>('');
  const [regEmail, setRegEmail] = useState<string>('');
  const [regPhone, setRegPhone] = useState<string>('');
  const [regDesiredRole, setRegDesiredRole] = useState<'instructor' | 'student' | 'renter' | 'admin'>('student');
  const [regNotes, setRegNotes] = useState<string>('');
  const [accountRequests, setAccountRequests] = useState<AccountRequest[]>(INITIAL_ACCOUNT_REQUESTS);
  const [requestFilter, setRequestFilter] = useState<'pending' | 'approved' | 'rejected' | 'all'>('pending');
  const [approvalProfileSelections, setApprovalProfileSelections] = useState<Record<number, 'instructor' | 'student' | 'renter' | 'admin'>>({});

  // State für Payroll
  const [appointments, setAppointments] = useState<Appointment[]>(INITIAL_APPOINTMENTS);
  const [selectedInstructorId, setSelectedInstructorId] = useState<number | 'all'>('all');
  const [selectedStatusFilter, setSelectedStatusFilter] = useState<string>('all');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  // State für Fahrlehrer-Portal
  const [currentInstructorId, setCurrentInstructorId] = useState<number>(1);
  const [selectedMonth, setSelectedMonth] = useState<string>('2026-03');
  const [availabilities, setAvailabilities] = useState<CoachAvailability[]>(INITIAL_AVAILABILITIES);
  const [workingHours, setWorkingHours] = useState<CoachWorkingHour[]>(INITIAL_WORKING_HOURS);
  const [coachTab, setCoachTab] = useState<'lessons' | 'upcoming' | 'availabilities' | 'timesheet' | 'hr'>('lessons');

  // State für Schüler Magic-Link
  const [magicToken, setMagicToken] = useState<string>('sec_tk_9843ab7190ef2384a0d9e');
  const [isMagicAuthenticated, setIsMagicAuthenticated] = useState<boolean>(true);
  const [studentEmailRequest, setStudentEmailRequest] = useState<string>('');
  const [magicFeedback, setMagicFeedback] = useState<string | null>(null);
  const [cancelModalAppointment, setCancelModalAppointment] = useState<Appointment | null>(null);
  const [cancelReason, setCancelReason] = useState<string>('');

  // State für Kunden-Dashboard (Wetter, Kontostand, Profil, Terminanfrage)
  const [studentWeatherStation, setStudentWeatherStation] = useState<'luzern' | 'nidwalden'>('luzern');
  const [showStudentProfileModal, setShowStudentProfileModal] = useState<boolean>(false);
  const [showStudentRequestModal, setShowStudentRequestModal] = useState<boolean>(false);
  const [studentAppointmentTab, setStudentAppointmentTab] = useState<'upcoming' | 'past'>('upcoming');
  const [studentProfile, setStudentProfile] = useState({
    first_name: 'Lukas',
    last_name: 'Berner',
    email: 'lukas.berner@swiss-pilot.ch',
    phone: '+41 79 400 11 22',
    birthdate: '1995-06-14',
    street: 'Alpenquai 12',
    zip: '6005',
    city: 'Luzern',
    faber: '123.456.789',
    pin: '7841',
    pid: 'CHE-902.144.5',
    license_number: '123.456.789',
    emergency_contact: 'Anna Berner (+41 79 888 77 66)',
    notes: 'Bevorzugt Nachmittagstermine'
  });
  const [newReqService, setNewReqService] = useState('Motorboot Kat. A (90 Min.)');
  const [newReqDate, setNewReqDate] = useState('2026-04-02');
  const [newReqTimeWindow, setNewReqTimeWindow] = useState('afternoon');
  const [newReqLocation, setNewReqLocation] = useState('Luzern (Alpenquai)');
  const [newReqCoach, setNewReqCoach] = useState('Beat Vollenweider');
  const [newReqUseBalance, setNewReqUseBalance] = useState(true);
  const [newReqNotes, setNewReqNotes] = useState('');

  // Fahrlehrer-Stammdaten als State (damit Admin h-Lohn fest hinterlegen kann)
  const [instructors, setInstructors] = useState<Instructor[]>(INITIAL_INSTRUCTORS);
  const [editingRateInstructorId, setEditingRateInstructorId] = useState<number | null>(null);
  const [tempRateInput, setTempRateInput] = useState<number>(65);

  // Neue Absenz Modal (stundenweise: von – bis Uhrzeit)
  const [showAddAbsence, setShowAddAbsence] = useState<boolean>(false);
  const [newAbsenceDate, setNewAbsenceDate] = useState<string>('2026-03-28');
  const [newAbsenceStartTime, setNewAbsenceStartTime] = useState<string>('08:00');
  const [newAbsenceEndTime, setNewAbsenceEndTime] = useState<string>('12:00');
  const [newAbsenceType, setNewAbsenceType] = useState<CoachAvailability['type']>('unavailable');
  const [newAbsenceNote, setNewAbsenceNote] = useState<string>('');

  // Neue Arbeitszeit / Timesheet Modal (h & min frei wählbar mit Betreff, fixer h-Lohn vom Admin)
  const [showAddWorkingHour, setShowAddWorkingHour] = useState<boolean>(false);
  const [whSubject, setWhSubject] = useState<string>('Bootswartung & Vorbereitung');
  const [whDate, setWhDate] = useState<string>('2026-03-24');
  const [whHours, setWhHours] = useState<number>(3);
  const [whMinutes, setWhMinutes] = useState<number>(0);
  const [whStartTime, setWhStartTime] = useState<string>('09:00');
  const [whBreakMinutes, setWhBreakMinutes] = useState<number>(0);
  const [whCategory, setWhCategory] = useState<CoachWorkingHour['category']>('maintenance');

  // Aktueller Fahrlehrer (mit vom Admin hinterlegtem Stundensatz)
  const currentInstructor = useMemo(() => {
    return instructors.find(i => i.id === currentInstructorId) || instructors[0];
  }, [instructors, currentInstructorId]);

  // Berechnungen für Admin-Payroll
  const filteredAppointments = useMemo(() => {
    return appointments.filter(a => {
      const matchInst = selectedInstructorId === 'all' || a.instructor_id === selectedInstructorId;
      const matchStat = selectedStatusFilter === 'all' || a.instructor_payout_status === selectedStatusFilter;
      return matchInst && matchStat;
    });
  }, [appointments, selectedInstructorId, selectedStatusFilter]);

  const payrollKpis = useMemo(() => {
    let totalRevenue = 0;
    let totalPayout = 0;
    let payoutOpen = 0;
    let payoutExported = 0;
    let payoutPaid = 0;
    let totalMinutes = 0;

    appointments.forEach(a => {
      totalRevenue += a.price;
      totalPayout += a.instructor_payout_amount;
      totalMinutes += a.duration_minutes;

      if (a.instructor_payout_status === 'open') payoutOpen += a.instructor_payout_amount;
      if (a.instructor_payout_status === 'exported') payoutExported += a.instructor_payout_amount;
      if (a.instructor_payout_status === 'paid') payoutPaid += a.instructor_payout_amount;
    });

    const margin = totalRevenue - totalPayout;
    const marginPercent = totalRevenue > 0 ? (margin / totalRevenue) * 100 : 0;

    return {
      totalAppointments: appointments.length,
      totalHours: (totalMinutes / 60).toFixed(1),
      totalRevenue,
      totalPayout,
      payoutOpen,
      payoutExported,
      payoutPaid,
      margin,
      marginPercent: marginPercent.toFixed(1)
    };
  }, [appointments]);

  // Fahrlehrer-Monatskpis
  const instructorMonthStats = useMemo(() => {
    const monthApps = appointments.filter(a => a.instructor_id === currentInstructorId && a.date.startsWith(selectedMonth));
    let openCount = 0;
    let openAmount = 0;
    let exportedAmount = 0;
    let paidAmount = 0;
    let totalHours = 0;

    monthApps.forEach(a => {
      totalHours += a.duration_minutes / 60;
      if (a.instructor_payout_status === 'open') {
        openCount++;
        openAmount += a.instructor_payout_amount;
      } else if (a.instructor_payout_status === 'exported') {
        exportedAmount += a.instructor_payout_amount;
      } else if (a.instructor_payout_status === 'paid') {
        paidAmount += a.instructor_payout_amount;
      }
    });

    return {
      count: monthApps.length,
      totalHours: totalHours.toFixed(1),
      openCount,
      openAmount,
      exportedAmount,
      paidAmount,
      totalAmount: openAmount + exportedAmount + paidAmount,
      appointments: monthApps
    };
  }, [appointments, currentInstructorId, selectedMonth]);

  // Aktionen
  const handleBulkStatusChange = (newStatus: 'open' | 'exported' | 'paid') => {
    if (selectedIds.length === 0) return;
    setAppointments(prev =>
      prev.map(a => (selectedIds.includes(a.id) ? { ...a, instructor_payout_status: newStatus } : a))
    );
    setSelectedIds([]);
  };

  const handleCloseMonthForInstructor = () => {
    setAppointments(prev =>
      prev.map(a => {
        if (a.instructor_id === currentInstructorId && a.date.startsWith(selectedMonth) && a.instructor_payout_status === 'open') {
          return { ...a, instructor_payout_status: 'exported' };
        }
        return a;
      })
    );
  };

  const handleCancelAppointmentByStudent = () => {
    if (!cancelModalAppointment) return;
    setAppointments(prev =>
      prev.map(a =>
        a.id === cancelModalAppointment.id
          ? { ...a, status: 'cancelled_customer', instructor_payout_note: 'Storniert durch Schüler: ' + (cancelReason || 'Kein Grund angegeben') }
          : a
      )
    );
    setCancelModalAppointment(null);
    setCancelReason('');
    setMagicFeedback('Termin erfolgreich storniert. Eine E-Mail-Bestätigung wurde an den Fahrlehrer und an dich versandt.');
    setTimeout(() => setMagicFeedback(null), 6000);
  };

  const handleDigitChange = (index: number, val: string) => {
    const clean = val.toUpperCase().replace(/[^A-Z0-9]/g, '');
    const newDigits = [...codeDigits];
    newDigits[index] = clean.slice(-1);
    setCodeDigits(newDigits);
    setLoginCode(newDigits.join(''));

    if (clean && index < 9) {
      const nextInput = document.getElementById(`digit-box-${index + 1}`);
      if (nextInput) (nextInput as HTMLInputElement).focus();
    }
  };

  const handleDigitKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !codeDigits[index] && index > 0) {
      const prevInput = document.getElementById(`digit-box-${index - 1}`);
      if (prevInput) (prevInput as HTMLInputElement).focus();
    }
  };

  const handleDigitPaste = (e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
    if (!pasted) return;
    const newDigits = [...codeDigits];
    for (let i = 0; i < 10; i++) {
      newDigits[i] = pasted[i] || '';
    }
    setCodeDigits(newDigits);
    setLoginCode(newDigits.join(''));
    const targetIdx = Math.min(pasted.length, 9);
    const targetEl = document.getElementById(`digit-box-${targetIdx}`);
    if (targetEl) (targetEl as HTMLInputElement).focus();
  };

  const fillTestCode = (code: string) => {
    const chars = code.toUpperCase().split('');
    const newDigits = Array(10).fill('');
    chars.forEach((c, idx) => {
      if (idx < 10) newDigits[idx] = c;
    });
    setCodeDigits(newDigits);
    setLoginCode(code);
  };

  const handleNauticLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Stufe 1: Nur E-Mail eingegeben -> Weiter zu Stufe 2 (Code eingeben)
    if (nauticLoginStep === 'email') {
      if (!loginEmail.trim()) return;
      setNauticLoginStep('code');
      if (nauticLoginTestMode) {
        fillTestCode('ROLAND1234');
        setLoginSuccessMsg(`Testmodus aktiv (E-Mail inaktiv): Dein Test-Code lautet ROLAND1234 (bereits in die Boxen eingefügt).`);
      } else {
        setLoginSuccessMsg(`Magic-Code wurde an ${loginEmail} gesendet. Bitte gib den Code unten in die Boxen ein.`);
      }
      setTimeout(() => setLoginSuccessMsg(null), 5000);
      return;
    }

    // Stufe 2: Code bestätigen & Anmelden
    const emailLower = loginEmail.trim().toLowerCase();

    // Prüfen, ob Fahrlehrer
    const matchInst = instructors.find(i => i.email.toLowerCase() === emailLower);
    if (matchInst) {
      setCurrentInstructorId(matchInst.id);
      setLoginSuccessMsg(`Magic-Code bestätigt! Erfolgreich angemeldet als Fahrlehrer (${matchInst.name}).`);
      setTimeout(() => {
        setLoginSuccessMsg(null);
        setActiveTab('instructor');
      }, 700);
      return;
    }

    // Prüfen, ob Admin / Flottenleitung
    if (emailLower.includes('admin') || emailLower.includes('office') || emailLower === 'coachingboot@gmail.com') {
      setLoginSuccessMsg(`Magic-Code bestätigt! Erfolgreich angemeldet als Flottenleitung (${loginEmail}).`);
      setTimeout(() => {
        setLoginSuccessMsg(null);
        setActiveTab('payroll');
      }, 700);
      return;
    }

    // Standard: Schüler / Kunde / Bootsmieter
    setLoginSuccessMsg(`Magic-Code bestätigt! Willkommen an Bord (${loginEmail}). Weiterleitung zum Kundenportal...`);
    setTimeout(() => {
      setLoginSuccessMsg(null);
      setActiveTab('student');
    }, 800);
  };

  const handleRegisterAccountSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!regEmail || !regFirstName || !regLastName) return;

    const newReq: AccountRequest = {
      id: Date.now(),
      first_name: regFirstName.trim(),
      last_name: regLastName.trim(),
      email: regEmail.trim(),
      phone: regPhone.trim(),
      desired_role: regDesiredRole,
      notes: regNotes.trim(),
      status: 'pending',
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
    };

    setAccountRequests(prev => [newReq, ...prev]);
    setLoginSuccessMsg(`Vielen Dank, ${regFirstName}! Schön, dass du mit an Bord bist. Wir richten deinen persönlichen Zugang für dich ein und melden uns so schnell wie möglich bei dir.`);
    setAuthViewMode('login');

    // Reset Form
    setRegFirstName('');
    setRegLastName('');
    setRegEmail('');
    setRegPhone('');
    setRegNotes('');
    setRegDesiredRole('student');
  };

  const handleApproveAccountRequest = (requestId: number) => {
    const target = accountRequests.find(r => r.id === requestId);
    if (!target) return;
    const assignedProfile = approvalProfileSelections[requestId] || target.desired_role;
    const generatedMagicCode = 'MAGIC-' + Math.floor(1000 + Math.random() * 9000);

    setAccountRequests(prev =>
      prev.map(r =>
        r.id === requestId
          ? {
              ...r,
              status: 'approved',
              assigned_profile: assignedProfile,
              approved_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
              admin_notes: `Freigegeben als ${assignedProfile}. Magic-Code ${generatedMagicCode} versendet.`
            }
          : r
      )
    );

    // Falls als Fahrlehrer freigegeben, automatisch in die Fahrlehrer-Liste aufnehmen
    if (assignedProfile === 'instructor' && !instructors.some(i => i.email.toLowerCase() === target.email.toLowerCase())) {
      const newInst: Instructor = {
        id: Date.now(),
        name: `${target.first_name} ${target.last_name}`,
        title: 'Bootsfahrlehrer',
        email: target.email,
        phone: target.phone || '+41 79 000 00 00',
        public_mobile: target.phone || '+41 79 000 00 00',
        is_mobile_public: true,
        color_code: '#0284c7',
        pay_rate_type: 'per_hour',
        pay_rate_amount: 65,
        hourly_rate: 65,
        payroll_external_code: 'KRED-' + Math.floor(1000 + Math.random() * 9000),
        license_number: 'CH-NAUTIC-2026'
      };
      setInstructors(prev => [...prev, newInst]);
    }

    setLoginSuccessMsg(`Account #${requestId} für ${target.first_name} ${target.last_name} als "${assignedProfile}" freigegeben!`);
    setTimeout(() => setLoginSuccessMsg(null), 4000);
  };

  const handleRejectAccountRequest = (requestId: number) => {
    setAccountRequests(prev =>
      prev.map(r =>
        r.id === requestId
          ? {
              ...r,
              status: 'rejected',
              admin_notes: 'Abgelehnt durch Administrator.'
            }
          : r
      )
    );
  };

  const handleAddAbsenceSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const newEntry: CoachAvailability = {
      id: Date.now(),
      instructor_id: currentInstructorId,
      date: newAbsenceDate,
      start_time: `${newAbsenceStartTime}:00`,
      end_time: `${newAbsenceEndTime}:00`,
      type: newAbsenceType,
      note: newAbsenceNote || `Sperrzeit ${newAbsenceStartTime} - ${newAbsenceEndTime} Uhr`
    };
    setAvailabilities(prev => [...prev, newEntry]);
    setShowAddAbsence(false);
    setNewAbsenceNote('');
  };

  // Berechnete Stunden der Absenz
  const absenceDurationHours = useMemo(() => {
    const [sH, sM] = (newAbsenceStartTime || '08:00').split(':').map(Number);
    const [eH, eM] = (newAbsenceEndTime || '12:00').split(':').map(Number);
    const startMin = (isNaN(sH) ? 8 : sH) * 60 + (isNaN(sM) ? 0 : sM);
    const endMin = (isNaN(eH) ? 12 : eH) * 60 + (isNaN(eM) ? 0 : eM);
    const diff = Math.max(0, endMin - startMin);
    return (diff / 60).toFixed(1);
  }, [newAbsenceStartTime, newAbsenceEndTime]);

  // Admin: Stundensatz für Fahrlehrer fest hinterlegen
  const handleSaveInstructorRate = (instructorId: number, newRate: number) => {
    const cleanRate = Math.max(0, Math.round(newRate * 100) / 100);
    setInstructors(prev => prev.map(inst => inst.id === instructorId ? { ...inst, pay_rate_amount: cleanRate, hourly_rate: cleanRate } : inst));
    setEditingRateInstructorId(null);
  };

  const whCalculated = useMemo(() => {
    const rawMinutes = Math.max(0, whHours * 60 + whMinutes);
    const billableMinutes = Math.max(0, rawMinutes - whBreakMinutes);
    const totalHours = Math.round((billableMinutes / 60) * 100) / 100;
    // Fest vom Admin hinterlegter h-Lohn:
    const rate = currentInstructor.pay_rate_amount || 65;
    const totalAmount = Math.round(totalHours * rate * 100) / 100;

    // Endzeit ermitteln
    const parts = (whStartTime || '09:00').split(':');
    const sH = parseInt(parts[0], 10) || 9;
    const sM = parseInt(parts[1], 10) || 0;
    const endTotalMinutes = sH * 60 + sM + rawMinutes;
    const endH = Math.floor(endTotalMinutes / 60) % 24;
    const endM = endTotalMinutes % 60;
    const endTimeStr = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}:00`;

    return {
      rawMinutes,
      billableMinutes,
      totalHours,
      rate,
      totalAmount,
      endTimeStr
    };
  }, [whHours, whMinutes, whBreakMinutes, whStartTime, currentInstructor.pay_rate_amount]);

  const handleAddWorkingHourSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!whSubject.trim()) return;

    const newHour: CoachWorkingHour = {
      id: Date.now(),
      instructor_id: currentInstructorId,
      date: whDate,
      start_time: `${whStartTime}:00`,
      end_time: whCalculated.endTimeStr,
      break_minutes: whBreakMinutes,
      total_hours: whCalculated.totalHours,
      rate_amount: currentInstructor.pay_rate_amount || 65, // Fest vom Admin hinterlegt
      total_amount: whCalculated.totalAmount,
      category: whCategory,
      status: 'submitted',
      note: whSubject.trim()
    };

    setWorkingHours(prev => [newHour, ...prev]);
    setShowAddWorkingHour(false);
  };

  const handleExportCsv = (type: 'detail' | 'summary') => {
    const filename = type === 'detail' ? `Payroll_Detail_${selectedMonth}.csv` : `Payroll_Summary_${selectedMonth}.csv`;
    const text = type === 'detail'
      ? `Fahrlehrer;Datum;Schüler;Dauer_Min;Umsatz_CHF;Honorar_CHF;Status;Notiz\n` +
        filteredAppointments.map(a => `${a.instructor_name};${a.date};${a.customer_name};${a.duration_minutes};${a.price};${a.instructor_payout_amount};${a.instructor_payout_status};"${a.instructor_payout_note}"`).join('\n')
      : `Fahrlehrer;Total_Fahrten;Umsatz_CHF;Honorar_CHF;Marge_CHF\n` +
        INITIAL_INSTRUCTORS.map(i => {
          const apps = appointments.filter(a => a.instructor_id === i.id);
          const rev = apps.reduce((s, x) => s + x.price, 0);
          const fee = apps.reduce((s, x) => s + x.instructor_payout_amount, 0);
          return `${i.name};${apps.length};${rev};${fee};${rev - fee}`;
        }).join('\n');

    const blob = new Blob([text], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 font-sans">
      {/* Top Navigation */}
      <header className="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20">
              <Compass className="w-6 h-6 animate-pulse" />
            </div>
            <div>
              <div className="text-base font-bold tracking-tight flex items-center gap-2">
                Bootsfahrschule Lake Zürich
                <span className="text-xs bg-sky-500/20 text-sky-300 px-2 py-0.5 rounded-full border border-sky-400/30">PHP 8 Core</span>
              </div>
              <p className="text-xs text-slate-400">Payroll, Schüler-Portal &amp; Coach-Management</p>
            </div>
          </div>

          {/* Module Tabs */}
          <nav className="flex space-x-1 sm:space-x-2">
            <button
              onClick={() => setActiveTab('payroll')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'payroll' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <FileSpreadsheet className="w-4 h-4" />
              <span className="hidden md:inline">1. Admin Payroll &amp; Reports</span>
              <span className="md:hidden">Payroll</span>
            </button>

            <button
              onClick={() => setActiveTab('student')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'student' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <LayoutDashboard className="w-4 h-4" />
              <span className="hidden md:inline">2. Kunden-Dashboard</span>
              <span className="md:hidden">Dashboard</span>
            </button>

            <button
              onClick={() => setActiveTab('instructor')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'instructor' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <User className="w-4 h-4" />
              <span className="hidden md:inline">3. Fahrlehrer-Portal &amp; Monatsabschluss</span>
              <span className="md:hidden">Fahrlehrer</span>
            </button>

            <button
              onClick={() => setActiveTab('login')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'login' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <LogIn className="w-4 h-4" />
              <span className="hidden md:inline">4. Nautic Logonseite (SVG Logo)</span>
              <span className="md:hidden">Logon</span>
            </button>

            <button
              onClick={() => setActiveTab('cms')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'cms' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <LayoutTemplate className="w-4 h-4" />
              <span className="hidden md:inline">5. Startseite &amp; Mini-CMS</span>
              <span className="md:hidden">CMS</span>
            </button>

            <button
              onClick={() => setActiveTab('architecture')}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 ${
                activeTab === 'architecture' ? 'bg-slate-800 text-sky-400 border border-sky-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800'
              }`}
            >
              <Layers className="w-4 h-4" />
              <span className="hidden lg:inline">Backend &amp; Schema</span>
            </button>
          </nav>
        </div>
      </header>

      {/* Main Container */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {/* ============================================================== */}
        {/* TAB 1: ADMIN REPORTING & FAHRLEHRER-PAYROLL (PROMPT 1)        */}
        {/* ============================================================== */}
        {activeTab === 'payroll' && (
          <div className="space-y-6">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
              <div>
                <div className="flex items-center gap-2">
                  <h1 className="text-2xl font-bold text-slate-900">Auswertungen &amp; Fahrlehrer-Payroll</h1>
                  <span className="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-emerald-200">
                    März 2026
                  </span>
                </div>
                <p className="text-slate-500 text-sm mt-1">
                  Berechnungsgrundlage pro Fahrlehrer (Stundenansatz oder Terminpauschale), Statusführung (offen, übergeben, bezahlt) und CSV-Export.
                </p>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleExportCsv('detail')}
                  className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold flex items-center gap-2 border border-slate-300 transition"
                >
                  <Download className="w-4 h-4 text-slate-500" />
                  Detail-CSV
                </button>
                <button
                  onClick={() => handleExportCsv('summary')}
                  className="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2 shadow-sm transition"
                >
                  <Download className="w-4 h-4" />
                  Fahrlehrer-Summary CSV
                </button>
              </div>
            </div>

            {/* KPI Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="text-xs uppercase font-semibold text-slate-500">Termine</div>
                <div className="text-2xl font-bold text-slate-900 mt-1">{payrollKpis.totalAppointments}</div>
                <div className="text-xs text-slate-400 mt-1">{payrollKpis.totalHours} Stunden Wasserzeit</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="text-xs uppercase font-semibold text-slate-500">Kundenumsatz</div>
                <div className="text-2xl font-bold text-slate-900 mt-1">CHF {payrollKpis.totalRevenue.toFixed(2)}</div>
                <div className="text-xs text-emerald-600 mt-1">Fahrstunden &amp; Prüfungen</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/50 shadow-sm">
                <div className="text-xs uppercase font-semibold text-amber-700">Honorar Offen</div>
                <div className="text-2xl font-bold text-amber-700 mt-1">CHF {payrollKpis.payoutOpen.toFixed(2)}</div>
                <div className="text-xs text-amber-600 mt-1">Noch nicht übergeben</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-sky-200 bg-sky-50/50 shadow-sm">
                <div className="text-xs uppercase font-semibold text-sky-700">Übergeben (Exported)</div>
                <div className="text-2xl font-bold text-sky-700 mt-1">CHF {payrollKpis.payoutExported.toFixed(2)}</div>
                <div className="text-xs text-sky-600 mt-1">In Lohnlauf eingereiht</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-sm">
                <div className="text-xs uppercase font-semibold text-emerald-700">Ausbezahlt</div>
                <div className="text-2xl font-bold text-emerald-700 mt-1">CHF {payrollKpis.payoutPaid.toFixed(2)}</div>
                <div className="text-xs text-emerald-600 mt-1">Vollständig verbucht</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="text-xs uppercase font-semibold text-slate-500">Deckungsbeitrag</div>
                <div className="text-2xl font-bold text-indigo-600 mt-1">CHF {payrollKpis.margin.toFixed(2)}</div>
                <div className="text-xs text-indigo-500 mt-1">{payrollKpis.marginPercent}% Marge</div>
              </div>
            </div>

            {/* Filter-Leiste & Batch Actions */}
            <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
              <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-2">
                  <Filter className="w-4 h-4 text-slate-400" />
                  <span className="text-xs font-semibold uppercase text-slate-500">Filter:</span>
                </div>

                <select
                  value={selectedInstructorId}
                  onChange={e => setSelectedInstructorId(e.target.value === 'all' ? 'all' : Number(e.target.value))}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500"
                >
                  <option value="all">Alle Fahrlehrer</option>
                  {INITIAL_INSTRUCTORS.map(i => (
                    <option key={i.id} value={i.id}>{i.name} ({i.pay_rate_type === 'per_hour' ? `CHF ${i.pay_rate_amount}/h` : `CHF ${i.pay_rate_amount}/Termin`})</option>
                  ))}
                </select>

                <select
                  value={selectedStatusFilter}
                  onChange={e => setSelectedStatusFilter(e.target.value)}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500"
                >
                  <option value="all">Alle Payout-Status</option>
                  <option value="open">Offen (open)</option>
                  <option value="exported">Exportiert (exported)</option>
                  <option value="paid">Ausbezahlt (paid)</option>
                </select>
              </div>

              {/* Batch Aktionen */}
              {selectedIds.length > 0 && (
                <div className="flex items-center gap-2 bg-sky-50 px-3 py-1.5 rounded-lg border border-sky-200">
                  <span className="text-xs font-semibold text-sky-800">{selectedIds.length} gewählt:</span>
                  <button
                    onClick={() => handleBulkStatusChange('open')}
                    className="text-xs px-2 py-1 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded font-medium"
                  >
                    Offen
                  </button>
                  <button
                    onClick={() => handleBulkStatusChange('exported')}
                    className="text-xs px-2 py-1 bg-sky-600 hover:bg-sky-700 text-white rounded font-medium"
                  >
                    An Admin übergeben
                  </button>
                  <button
                    onClick={() => handleBulkStatusChange('paid')}
                    className="text-xs px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-medium"
                  >
                    Bezahlt
                  </button>
                </div>
              )}
            </div>

            {/* Einzeltermine Tabelle */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 className="font-semibold text-slate-800 text-sm">Einzeltermine &amp; Honorarberechnung</h3>
                <span className="text-xs text-slate-500">{filteredAppointments.length} Datensätze gefunden</span>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm text-slate-600">
                  <thead className="bg-slate-100/70 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                      <th className="p-3 w-10 text-center">
                        <input
                          type="checkbox"
                          checked={selectedIds.length === filteredAppointments.length && filteredAppointments.length > 0}
                          onChange={e => {
                            if (e.target.checked) setSelectedIds(filteredAppointments.map(a => a.id));
                            else setSelectedIds([]);
                          }}
                          className="rounded text-sky-600 focus:ring-sky-500"
                        />
                      </th>
                      <th className="p-3">Datum &amp; Zeit</th>
                      <th className="p-3">Fahrlehrer</th>
                      <th className="p-3">Schüler &amp; Boot</th>
                      <th className="p-3">Dauer</th>
                      <th className="p-3 text-right">Umsatz (CHF)</th>
                      <th className="p-3 text-right">Honorar (CHF)</th>
                      <th className="p-3">Status</th>
                      <th className="p-3">Notiz</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredAppointments.map(a => {
                      const isSelected = selectedIds.includes(a.id);
                      return (
                        <tr key={a.id} className={`hover:bg-slate-50 transition ${isSelected ? 'bg-sky-50/50' : ''}`}>
                          <td className="p-3 text-center">
                            <input
                              type="checkbox"
                              checked={isSelected}
                              onChange={() => {
                                setSelectedIds(prev =>
                                  prev.includes(a.id) ? prev.filter(x => x !== a.id) : [...prev, a.id]
                                );
                              }}
                              className="rounded text-sky-600 focus:ring-sky-500"
                            />
                          </td>
                          <td className="p-3">
                            <div className="font-medium text-slate-900">{a.date}</div>
                            <div className="text-xs text-slate-400">{a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)}</div>
                          </td>
                          <td className="p-3 font-medium text-slate-800">{a.instructor_name}</td>
                          <td className="p-3">
                            <div className="font-medium text-slate-900">{a.customer_name}</div>
                            <div className="text-xs text-slate-400">{a.boat_name} &bull; {a.service_name}</div>
                          </td>
                          <td className="p-3">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                              {a.duration_minutes} Min ({(a.duration_minutes / 60).toFixed(1)}h)
                            </span>
                          </td>
                          <td className="p-3 text-right font-medium text-slate-900">
                            CHF {a.price.toFixed(2)}
                          </td>
                          <td className="p-3 text-right font-bold text-sky-700">
                            CHF {a.instructor_payout_amount.toFixed(2)}
                          </td>
                          <td className="p-3">
                            {a.instructor_payout_status === 'open' && (
                              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                Offen
                              </span>
                            )}
                            {a.instructor_payout_status === 'exported' && (
                              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                Übergeben
                              </span>
                            )}
                            {a.instructor_payout_status === 'paid' && (
                              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Bezahlt
                              </span>
                            )}
                          </td>
                          <td className="p-3 text-xs text-slate-500 max-w-xs truncate" title={a.instructor_payout_note}>
                            {a.instructor_payout_note || '-'}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* ============================================================== */}
        {/* TAB 2: SCHÜLER-MAGIC-LINK & SELF-SERVICE (PROMPT 2 & 5)        */}
        {/* ============================================================== */}
        {/* ============================================================== */}
        {/* TAB 2: NAUTIC KUNDEN-DASHBOARD (USER BRIEF)                    */}
        {/* ============================================================== */}
        {activeTab === 'student' && (() => {
          // Profil-Vollständigkeit berechnen
          const missingFields: { key: string; label: string }[] = [];
          if (!studentProfile.phone.trim()) missingFields.push({ key: 'phone', label: 'Mobiltelefonnummer' });
          if (!studentProfile.birthdate.trim()) missingFields.push({ key: 'birthdate', label: 'Geburtsdatum' });
          if (!studentProfile.faber.trim()) missingFields.push({ key: 'faber', label: 'FABER:' });
          if (!studentProfile.pin.trim()) missingFields.push({ key: 'pin', label: 'PIN:' });
          if (!studentProfile.pid.trim()) missingFields.push({ key: 'pid', label: 'PID:' });
          if (!studentProfile.emergency_contact.trim()) missingFields.push({ key: 'emergency_contact', label: 'Notfallkontakt' });
          if (!studentProfile.street.trim() || !studentProfile.city.trim()) missingFields.push({ key: 'address', label: 'Wohnadresse' });

          const totalRequired = 7;
          const completedCount = totalRequired - missingFields.length;
          const completenessPercent = Math.round((completedCount / totalRequired) * 100);

          const studentApps = appointments.filter(a => a.customer_id === 1);
          const upcomingApps = studentApps.filter(a => a.status === 'confirmed' || a.status === 'requested');
          const pastApps = studentApps.filter(a => a.status === 'completed' || a.status === 'cancelled_customer');

          return (
            <div className="space-y-6">
              {/* 1. Warnbanner: Profil-Vollständigkeit fehlt */}
              {missingFields.length > 0 && (
                <div className="bg-amber-50 border border-amber-300 rounded-2xl p-4 sm:p-5 shadow-sm text-amber-950 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                  <div className="flex items-start gap-3">
                    <div className="w-10 h-10 rounded-xl bg-amber-200/80 text-amber-800 flex items-center justify-center flex-shrink-0 mt-0.5 font-bold">
                      <AlertTriangle className="w-5 h-5 text-amber-700" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h4 className="font-bold text-sm sm:text-base text-amber-900">
                          Dein Schülerprofil ist zu {completenessPercent}% ausgefüllt – Angaben fehlen noch
                        </h4>
                        <span className="text-xs px-2 py-0.5 bg-amber-200 text-amber-900 rounded-full font-semibold">
                          Wichtig für Schifffahrtsamt
                        </span>
                      </div>
                      <p className="text-xs text-amber-800 mt-1 max-w-2xl">
                        Für die offizielle Prüfungsanmeldung und Notfallerreichbarkeit auf dem See fehlen: {' '}
                        <strong>{missingFields.map(f => f.label).join(' • ')}</strong>.
                      </p>
                    </div>
                  </div>
                  <button
                    onClick={() => setShowStudentProfileModal(true)}
                    className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap self-stretch md:self-auto text-center"
                  >
                    Profil jetzt vervollständigen &rarr;
                  </button>
                </div>
              )}

              {/* Feedback Alert */}
              {magicFeedback && (
                <div className="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                  <CheckCircle2 className="w-5 h-5 text-emerald-600 flex-shrink-0" />
                  <span>{magicFeedback}</span>
                </div>
              )}

              {/* 2. Begrüssungs-Karte mit Schnellaktionen */}
              <div className="bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div className="flex items-center gap-4">
                  <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center font-extrabold text-2xl shadow-lg border border-white/20">
                    {studentProfile.first_name[0]}{studentProfile.last_name[0]}
                  </div>
                  <div>
                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-semibold border border-sky-400/30 mb-1.5">
                      <Compass className="w-3.5 h-3.5" />
                      Nautic Kunden-Dashboard • Schüler #{1042}
                    </div>
                    <h2 className="text-2xl sm:text-3xl font-black tracking-tight">
                      Hallo {studentProfile.first_name}!
                    </h2>
                    <p className="text-sky-200/80 text-xs sm:text-sm mt-0.5 flex flex-wrap items-center gap-2">
                      <span>{studentProfile.email}</span>
                      <span>•</span>
                      <span>{studentProfile.phone || 'Keine Telefonnr.'}</span>
                      <span>•</span>
                      <span>Kat. A Motorboot</span>
                      {studentProfile.faber && (
                        <span className="bg-sky-400/20 text-sky-200 px-2 py-0.5 rounded border border-sky-400/30 text-[11px] font-mono">
                          <strong>FABER:</strong> {studentProfile.faber}
                        </span>
                      )}
                      {studentProfile.pin && (
                        <span className="bg-sky-400/20 text-sky-200 px-2 py-0.5 rounded border border-sky-400/30 text-[11px] font-mono">
                          <strong>PIN:</strong> {studentProfile.pin}
                        </span>
                      )}
                      {studentProfile.pid && (
                        <span className="bg-sky-400/20 text-sky-200 px-2 py-0.5 rounded border border-sky-400/30 text-[11px] font-mono">
                          <strong>PID:</strong> {studentProfile.pid}
                        </span>
                      )}
                    </p>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                  <button
                    onClick={() => setShowStudentRequestModal(true)}
                    className="px-4 py-2.5 bg-sky-500 hover:bg-sky-400 text-white font-bold rounded-xl text-xs sm:text-sm shadow-md shadow-sky-500/20 transition flex items-center gap-1.5"
                  >
                    <Calendar className="w-4 h-4" />
                    Terminanfrage stellen
                  </button>
                  <button
                    onClick={() => setShowStudentProfileModal(true)}
                    className="px-3.5 py-2.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl text-xs sm:text-sm border border-white/20 transition flex items-center gap-1.5"
                  >
                    <User className="w-4 h-4" />
                    Mein Profil
                  </button>
                  <button
                    onClick={() => {
                      setMagicFeedback('Du hast dich erfolgreich abgemeldet.');
                      setTimeout(() => setMagicFeedback(null), 4000);
                    }}
                    className="px-3 py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 border border-rose-400/30 font-semibold rounded-xl text-xs transition"
                  >
                    Abmelden
                  </button>
                </div>
              </div>

              {/* 3. Zweispaltige Kacheln: Kontostand & See-Wetter */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {/* Kachel A: Kontostand & Guthaben */}
                <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                      <div className="flex items-center gap-2">
                        <div className="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                          <Wallet className="w-5 h-5 text-sky-600" />
                        </div>
                        <div>
                          <h3 className="font-bold text-slate-900 text-base">Dein Kontostand &amp; Guthaben</h3>
                          <p className="text-xs text-slate-500">Bezahlte Fahrstunden &amp; Mieten</p>
                        </div>
                      </div>
                      <span className="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-200">
                        Abo Aktiv
                      </span>
                    </div>

                    <div className="grid grid-cols-2 gap-4 mt-5">
                      <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                        <span className="text-xs text-slate-500 uppercase font-semibold">Verfügbare Fahrstunden</span>
                        <div className="text-3xl font-black text-slate-900 mt-1">
                          4 <span className="text-sm font-normal text-slate-500">von 10</span>
                        </div>
                        <span className="text-[11px] text-emerald-600 font-semibold mt-1 inline-block">
                          à 90 Min. im Voraus bezahlt
                        </span>
                      </div>

                      <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                        <span className="text-xs text-slate-500 uppercase font-semibold">Bootsmiete / Charter</span>
                        <div className="text-3xl font-black text-slate-900 mt-1">
                          1 <span className="text-sm font-normal text-slate-500">Guthaben</span>
                        </div>
                        <span className="text-[11px] text-sky-600 font-semibold mt-1 inline-block">
                          Tagescharter Boesch 620
                        </span>
                      </div>
                    </div>

                    {/* Progress Bar */}
                    <div className="mt-5 space-y-2">
                      <div className="flex justify-between text-xs font-semibold text-slate-700">
                        <span>Ausbildungsfortschritt (10er Paket)</span>
                        <span>60% absolviert (6/10)</span>
                      </div>
                      <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden border border-slate-200">
                        <div className="bg-gradient-to-r from-sky-500 to-indigo-600 h-full w-[60%] rounded-full transition-all duration-500" />
                      </div>
                      <div className="flex justify-between text-[11px] text-slate-400">
                        <span>Noch 4 Fahrstunden verbleibend</span>
                        <span>Restwert: CHF 760.00</span>
                      </div>
                    </div>

                    <div className="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 flex items-center justify-between">
                      <span className="text-slate-500">Abo: <strong>10er Block Motorboot Kat. A (Luzern / NW)</strong></span>
                      <span className="text-slate-400">Gültig bis 31.12.2026</span>
                    </div>
                  </div>

                  <div className="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span className="text-xs text-slate-500">Automatische Verrechnung bei Buchung aktiv</span>
                    <button
                      onClick={() => setShowStudentRequestModal(true)}
                      className="text-xs text-sky-600 hover:text-sky-800 font-bold hover:underline"
                    >
                      Termin mit Guthaben buchen &rarr;
                    </button>
                  </div>
                </div>

                {/* Kachel B: See-Wetter Vierwaldstättersee (Luzern & Nidwalden) */}
                <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                      <div className="flex items-center gap-2">
                        <div className="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                          <Waves className="w-5 h-5 text-sky-600" />
                        </div>
                        <div>
                          <h3 className="font-bold text-slate-900 text-base">See-Wetter &amp; Seebedingungen</h3>
                          <p className="text-xs text-slate-500">Vierwaldstättersee Live-Stationen</p>
                        </div>
                      </div>

                      {/* Station Switcher */}
                      <div className="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                        <button
                          onClick={() => setStudentWeatherStation('luzern')}
                          className={`px-3 py-1 rounded-lg transition ${
                            studentWeatherStation === 'luzern'
                              ? 'bg-white text-slate-900 shadow-sm font-bold'
                              : 'text-slate-500 hover:text-slate-800'
                          }`}
                        >
                          Luzern (LU)
                        </button>
                        <button
                          onClick={() => setStudentWeatherStation('nidwalden')}
                          className={`px-3 py-1 rounded-lg transition ${
                            studentWeatherStation === 'nidwalden'
                              ? 'bg-white text-slate-900 shadow-sm font-bold'
                              : 'text-slate-500 hover:text-slate-800'
                          }`}
                        >
                          Nidwalden (NW)
                        </button>
                      </div>
                    </div>

                    {/* Wetter-Detailanzeige */}
                    {studentWeatherStation === 'luzern' ? (
                      <div className="mt-4 space-y-4">
                        <div className="flex items-center justify-between bg-sky-50/60 border border-sky-100 rounded-xl p-3.5">
                          <div className="flex items-center gap-3">
                            <Sun className="w-8 h-8 text-amber-500" />
                            <div>
                              <div className="font-bold text-slate-900 text-sm">Luzern Seebecken / Tribschen</div>
                              <div className="text-xs text-slate-500">Heiter bis leicht bewölkt • Bucht Luzern</div>
                            </div>
                          </div>
                          <div className="text-right">
                            <div className="text-2xl font-black text-slate-900">19.5 °C</div>
                            <div className="text-xs text-slate-500">Wasser: <strong className="text-sky-700">16.5 °C</strong></div>
                          </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2 text-center text-xs">
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Wind</span>
                            <strong className="text-slate-900 text-sm">12 km/h</strong>
                            <span className="text-slate-500 block text-[10px]">6.5 kn (Bft 2)</span>
                          </div>
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Richtung</span>
                            <strong className="text-slate-900 text-sm">WSW (240°)</strong>
                            <span className="text-slate-500 block text-[10px]">Leichte Brise</span>
                          </div>
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Wellen</span>
                            <strong className="text-slate-900 text-sm">&lt; 0.2m</strong>
                            <span className="text-slate-500 block text-[10px]">Ruhig</span>
                          </div>
                        </div>

                        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-900 flex items-center justify-between">
                          <span className="flex items-center gap-1.5 font-semibold">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                            Manövereignung: Optimal für An- &amp; Ablegemanöver
                          </span>
                          <span className="text-[11px] px-2 py-0.5 bg-emerald-200/80 rounded-full font-bold text-emerald-900">
                            Perfekt
                          </span>
                        </div>
                      </div>
                    ) : (
                      <div className="mt-4 space-y-4">
                        <div className="flex items-center justify-between bg-sky-50/60 border border-sky-100 rounded-xl p-3.5">
                          <div className="flex items-center gap-3">
                            <Sun className="w-8 h-8 text-amber-500" />
                            <div>
                              <div className="font-bold text-slate-900 text-sm">Nidwalden (Stansstad &amp; Hergiswil NW)</div>
                              <div className="text-xs text-slate-500">Sonnig mit thermischem Wind • Alpnachersee / Seebecken Stansstad-Hergiswil</div>
                            </div>
                          </div>
                          <div className="text-right">
                            <div className="text-2xl font-black text-slate-900">20.2 °C</div>
                            <div className="text-xs text-slate-500">Wasser: <strong className="text-sky-700">16.1 °C</strong></div>
                          </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2 text-center text-xs">
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Wind</span>
                            <strong className="text-slate-900 text-sm">15 km/h</strong>
                            <span className="text-slate-500 block text-[10px]">8.0 kn (Bft 3)</span>
                          </div>
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Richtung</span>
                            <strong className="text-slate-900 text-sm">ESE (110°)</strong>
                            <span className="text-slate-500 block text-[10px]">Schwache Brise</span>
                          </div>
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span className="text-slate-400 block text-[10px] uppercase font-semibold">Wellen</span>
                            <strong className="text-slate-900 text-sm">0.2 - 0.3m</strong>
                            <span className="text-slate-500 block text-[10px]">Leichte Kabbelwellen</span>
                          </div>
                        </div>

                        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-900 flex items-center justify-between">
                          <span className="flex items-center gap-1.5 font-semibold">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                            Manövereignung: Sehr gut für Boje über Bord &amp; Kursfahren
                          </span>
                          <span className="text-[11px] px-2 py-0.5 bg-emerald-200/80 rounded-full font-bold text-emerald-900">
                            Gut
                          </span>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Sturmwarnleuchten Status */}
                  <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2">
                      <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                      <span className="text-slate-700 font-semibold">
                        Sturmwarnleuchten: <strong>Entwarnung / Ruhe</strong> (Alle Leuchten aus)
                      </span>
                    </div>
                    <span className="text-slate-400 text-[11px]">Vierwaldstättersee Warnregion 3</span>
                  </div>
                </div>

              </div>

              {/* 4. Termine Bereich (Kommende Termine & Vergangene Termine Tabs) */}
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-4">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                      <Calendar className="w-4 h-4" />
                    </div>
                    <h3 className="font-bold text-slate-900 text-base">Deine Termine &amp; Fahrten</h3>
                  </div>

                  {/* Tabs: Kommende vs. Vergangene */}
                  <div className="flex items-center gap-2">
                    <div className="inline-flex p-1 bg-slate-200/80 rounded-xl text-xs font-semibold">
                      <button
                        onClick={() => setStudentAppointmentTab('upcoming')}
                        className={`px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 ${
                          studentAppointmentTab === 'upcoming'
                            ? 'bg-white text-slate-900 shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <Calendar className="w-3.5 h-3.5" />
                        Kommende Termine ({upcomingApps.length})
                      </button>
                      <button
                        onClick={() => setStudentAppointmentTab('past')}
                        className={`px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 ${
                          studentAppointmentTab === 'past'
                            ? 'bg-white text-slate-900 shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        <Clock className="w-3.5 h-3.5" />
                        Vergangene Termine ({pastApps.length})
                      </button>
                    </div>

                    <button
                      onClick={() => setShowStudentRequestModal(true)}
                      className="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      Termin anfragen
                    </button>
                  </div>
                </div>

                {/* Tab-Inhalt */}
                {studentAppointmentTab === 'upcoming' ? (
                  <div className="divide-y divide-slate-100">
                    {upcomingApps.length === 0 ? (
                      <div className="p-8 text-center text-slate-500 text-sm">
                        Keine bevorstehenden Termine gefunden.
                      </div>
                    ) : (
                      upcomingApps.map(a => (
                        <div key={a.id} className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="font-bold text-slate-900 text-base">{a.service_name}</span>
                              {a.status === 'confirmed' ? (
                                <span className="text-xs px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold border border-emerald-200">
                                  Bestätigt
                                </span>
                              ) : (
                                <span className="text-xs px-2.5 py-0.5 bg-amber-100 text-amber-800 rounded-full font-bold border border-amber-200">
                                  Angefragt (in Prüfung)
                                </span>
                              )}
                              <span className="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md font-medium">
                                Abo-Guthaben
                              </span>
                            </div>
                            <div className="text-xs text-slate-600 mt-2 flex flex-wrap gap-x-4 gap-y-1">
                              <span><Calendar className="w-3.5 h-3.5 inline mr-1 text-sky-600" /><strong>{a.date}</strong></span>
                              <span><Clock className="w-3.5 h-3.5 inline mr-1 text-sky-600" />{a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)} Uhr (90 Min.)</span>
                              <span><Ship className="w-3.5 h-3.5 inline mr-1 text-sky-600" />{a.boat_name}</span>
                              <span><User className="w-3.5 h-3.5 inline mr-1 text-sky-600" />Coach: {a.instructor_name}</span>
                            </div>
                            <div className="text-xs text-slate-400 mt-1">
                              <MapPin className="w-3.5 h-3.5 inline mr-1 text-slate-400" />Treffpunkt: <strong>{a.location_name}</strong>
                            </div>
                          </div>

                          <div className="flex sm:flex-col items-center sm:items-end justify-between gap-2.5">
                            <span className="text-sm font-bold text-slate-900">CHF {a.price.toFixed(2)}</span>
                            <button
                              onClick={() => setCancelModalAppointment(a)}
                              className="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition"
                            >
                              Termin stornieren
                            </button>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                ) : (
                  <div className="divide-y divide-slate-100">
                    {pastApps.length === 0 ? (
                      <div className="p-8 text-center text-slate-500 text-sm">
                        Keine vergangenen Termine vorhanden.
                      </div>
                    ) : (
                      pastApps.map(a => (
                        <div key={a.id} className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition text-xs">
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="font-bold text-slate-900 text-sm">{a.service_name}</span>
                              {a.status === 'completed' ? (
                                <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold">
                                  Erfolgreich absolviert
                                </span>
                              ) : (
                                <span className="px-2 py-0.5 bg-rose-100 text-rose-800 rounded-full font-bold">
                                  Storniert
                                </span>
                              )}
                            </div>
                            <div className="text-slate-500 mt-1">
                              {a.date} • {a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)} Uhr • Coach {a.instructor_name} • Boot {a.boat_name}
                            </div>
                            {a.instructor_payout_note && (
                              <div className="text-slate-600 font-medium mt-1 bg-slate-50 p-2 rounded border border-slate-200">
                                Feedback / Notiz: {a.instructor_payout_note}
                              </div>
                            )}
                          </div>
                          <div className="text-right">
                            <div className="font-bold text-slate-900 text-sm">CHF {a.price.toFixed(2)}</div>
                            <span className="text-slate-400">Abo-Einheit abgebucht</span>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                )}
              </div>

              {/* MODAL 1: Terminanfrage stellen */}
              {showStudentRequestModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                  <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
                    <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                      <div className="flex items-center gap-2.5">
                        <div className="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                          <Calendar className="w-5 h-5 text-sky-600" />
                        </div>
                        <div>
                          <h3 className="font-bold text-slate-900 text-base">Terminanfrage einreichen</h3>
                          <p className="text-xs text-slate-500">Wunschtermin für Fahrstunde oder Bootsmiete</p>
                        </div>
                      </div>
                      <button
                        onClick={() => setShowStudentRequestModal(false)}
                        className="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                      >
                        ✕
                      </button>
                    </div>

                    <form
                      onSubmit={e => {
                        e.preventDefault();
                        const newApp: Appointment = {
                          id: Date.now(),
                          date: newReqDate,
                          start_time: newReqTimeWindow === 'morning' ? '09:00:00' : newReqTimeWindow === 'afternoon' ? '14:00:00' : '17:30:00',
                          end_time: newReqTimeWindow === 'morning' ? '10:30:00' : newReqTimeWindow === 'afternoon' ? '15:30:00' : '19:00:00',
                          duration_minutes: 90,
                          customer_id: 1,
                          customer_name: `${studentProfile.first_name} ${studentProfile.last_name}`,
                          customer_email: studentProfile.email,
                          customer_phone: studentProfile.phone,
                          instructor_id: 1,
                          instructor_name: newReqCoach,
                          boat_name: 'Boesch 620 Bimini',
                          location_name: newReqLocation,
                          service_name: newReqService,
                          price: 190.0,
                          status: 'requested',
                          instructor_payout_amount: 97.5,
                          instructor_payout_status: 'open',
                          instructor_payout_note: newReqNotes ? `Schüler-Anfrage: ${newReqNotes}` : 'Terminanfrage über Kunden-Dashboard'
                        };

                        setAppointments(prev => [newApp, ...prev]);
                        setShowStudentRequestModal(false);
                        setMagicFeedback(`Deine Terminanfrage für ${newReqDate} wurde erfolgreich eingereicht! Die Flottenleitung prüft die Zuteilung.`);
                        setTimeout(() => setMagicFeedback(null), 6000);
                      }}
                      className="mt-5 space-y-4 text-xs"
                    >
                      <div>
                        <label className="font-bold text-slate-700 block mb-1">Ausbildungsleistung / Terminart *</label>
                        <select
                          value={newReqService}
                          onChange={e => setNewReqService(e.target.value)}
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                        >
                          <option value="Motorboot Kat. A (90 Min.)">Motorboot Kat. A (90 Min.) – CHF 190.00</option>
                          <option value="Intensiv-Doppelstunde (180 Min.)">Intensiv-Doppelstunde (180 Min.) – CHF 380.00</option>
                          <option value="Hafen- & Boxen-Manövertraining">Hafen- &amp; Boxen-Manövertraining (90 Min.) – CHF 190.00</option>
                          <option value="Prüfungsvorbereitung / Generalprobe">Prüfungsvorbereitung / Generalprobe – CHF 210.00</option>
                          <option value="Bootsmiete / Charter (Halbtag)">Bootsmiete / Charter (Halbtag) – CHF 350.00</option>
                        </select>
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Wunschdatum *</label>
                          <input
                            type="date"
                            value={newReqDate}
                            onChange={e => setNewReqDate(e.target.value)}
                            min="2026-03-20"
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          />
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Zeitfenster</label>
                          <select
                            value={newReqTimeWindow}
                            onChange={e => setNewReqTimeWindow(e.target.value)}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          >
                            <option value="morning">Vormittag (09:00 - 10:30 Uhr)</option>
                            <option value="afternoon">Nachmittag (14:00 - 15:30 Uhr)</option>
                            <option value="evening">Abenddämmerung (17:30 - 19:00 Uhr)</option>
                          </select>
                        </div>
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Bevorzugter Standort</label>
                          <select
                            value={newReqLocation}
                            onChange={e => setNewReqLocation(e.target.value)}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          >
                            <option value="Luzern (Alpenquai)">Luzern (Alpenquai / Tribschen)</option>
                            <option value="Stansstad (Hafen)">Stansstad NW (Hafenanlage)</option>
                            <option value="Hergiswil (Hafen)">Hergiswil NW (Bootshafen)</option>
                            <option value="Buochs (Seefeld)">Buochs NW (Seefeld Pier)</option>
                            <option value="Ennetbürgen">Ennetbürgen (Bürgenstock-Bucht)</option>
                          </select>
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Wunsch-Fahrlehrer</label>
                          <select
                            value={newReqCoach}
                            onChange={e => setNewReqCoach(e.target.value)}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          >
                            <option value="Beat Vollenweider">Beat Vollenweider</option>
                            <option value="Marc Hürlimann">Marc Hürlimann</option>
                            <option value="Sandra Meier">Sandra Meier</option>
                            <option value="Beliebiger verfügbarer Coach">Beliebiger Coach</option>
                          </select>
                        </div>
                      </div>

                      <div className="p-3 bg-sky-50 border border-sky-200 rounded-xl flex items-center gap-2">
                        <input
                          type="checkbox"
                          id="useBalanceCheck"
                          checked={newReqUseBalance}
                          onChange={e => setNewReqUseBalance(e.target.checked)}
                          className="rounded text-sky-600 focus:ring-sky-500"
                        />
                        <label htmlFor="useBalanceCheck" className="font-semibold text-sky-900 cursor-pointer">
                          Mit vorhandenem Abo-Guthaben verrechnen (4 Lektionen verfügbar)
                        </label>
                      </div>

                      <div>
                        <label className="font-bold text-slate-700 block mb-1">Besondere Wünsche / Notizen</label>
                        <textarea
                          rows={2}
                          value={newReqNotes}
                          onChange={e => setNewReqNotes(e.target.value)}
                          placeholder="z. B. Schwerpunkt auf Boxenmanöver rückwärts oder Generalprobe..."
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                        />
                      </div>

                      <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => setShowStudentRequestModal(false)}
                          className="px-4 py-2 text-slate-600 hover:text-slate-800 font-semibold"
                        >
                          Abbrechen
                        </button>
                        <button
                          type="submit"
                          className="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl shadow-md transition"
                        >
                          Terminanfrage verbindlich absenden &rarr;
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              )}

              {/* MODAL 2: Schülerprofil bearbeiten */}
              {showStudentProfileModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
                  <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
                    <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                      <div className="flex items-center gap-2.5">
                        <div className="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                          <User className="w-5 h-5 text-amber-700" />
                        </div>
                        <div>
                          <h3 className="font-bold text-slate-900 text-base">Schülerprofil bearbeiten</h3>
                          <p className="text-xs text-slate-500">Persönliche Angaben &amp; Schifffahrtsamts-Daten</p>
                        </div>
                      </div>
                      <button
                        onClick={() => setShowStudentProfileModal(false)}
                        className="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                      >
                        ✕
                      </button>
                    </div>

                    <form
                      onSubmit={e => {
                        e.preventDefault();
                        setShowStudentProfileModal(false);
                        setMagicFeedback('Dein Profil wurde erfolgreich gespeichert und aktualisiert.');
                        setTimeout(() => setMagicFeedback(null), 5000);
                      }}
                      className="mt-5 space-y-3.5 text-xs"
                    >
                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Vorname</label>
                          <input
                            type="text"
                            value={studentProfile.first_name}
                            onChange={e => setStudentProfile({ ...studentProfile, first_name: e.target.value })}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium"
                            required
                          />
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Nachname</label>
                          <input
                            type="text"
                            value={studentProfile.last_name}
                            onChange={e => setStudentProfile({ ...studentProfile, last_name: e.target.value })}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium"
                            required
                          />
                        </div>
                      </div>

                      <div>
                        <label className="font-bold text-slate-700 block mb-1">E-Mail-Adresse</label>
                        <input
                          type="email"
                          value={studentProfile.email}
                          disabled
                          className="w-full px-3 py-2 bg-slate-100 text-slate-500 border border-slate-200 rounded-xl text-xs font-medium"
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Mobiltelefonnummer *</label>
                          <input
                            type="tel"
                            value={studentProfile.phone}
                            onChange={e => setStudentProfile({ ...studentProfile, phone: e.target.value })}
                            placeholder="+41 79 123 45 67"
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          />
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">Geburtsdatum *</label>
                          <input
                            type="date"
                            value={studentProfile.birthdate}
                            onChange={e => setStudentProfile({ ...studentProfile, birthdate: e.target.value })}
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                          />
                        </div>
                      </div>

                      {/* Schweizer Schifffahrtsamt: FABER:, PIN:, PID: */}
                      <div className="grid grid-cols-3 gap-3">
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">FABER: *</label>
                          <input
                            type="text"
                            value={studentProfile.faber}
                            onChange={e => setStudentProfile({ ...studentProfile, faber: e.target.value, license_number: e.target.value })}
                            placeholder="z. B. 123.456.789"
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-medium focus:ring-2 focus:ring-sky-500"
                          />
                          <span className="text-[10px] text-slate-400 block mt-0.5">Fahrberechtigungs-Reg.</span>
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">PIN: *</label>
                          <input
                            type="text"
                            value={studentProfile.pin}
                            onChange={e => setStudentProfile({ ...studentProfile, pin: e.target.value })}
                            placeholder="z. B. 7841"
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-medium focus:ring-2 focus:ring-sky-500"
                          />
                          <span className="text-[10px] text-slate-400 block mt-0.5">Persönliche Ident.-Nr.</span>
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">PID: *</label>
                          <input
                            type="text"
                            value={studentProfile.pid}
                            onChange={e => setStudentProfile({ ...studentProfile, pid: e.target.value })}
                            placeholder="z. B. CHE-123.456.7"
                            required
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono font-medium focus:ring-2 focus:ring-sky-500"
                          />
                          <span className="text-[10px] text-slate-400 block mt-0.5">Personen-Identifikator</span>
                        </div>
                      </div>

                      <div>
                        <label className="font-bold text-slate-700 block mb-1">Notfallkontakt (Name &amp; Tel) *</label>
                        <input
                          type="text"
                          value={studentProfile.emergency_contact}
                          onChange={e => setStudentProfile({ ...studentProfile, emergency_contact: e.target.value })}
                          placeholder="z. B. Anna Berner, 079 888 77 66"
                          required
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500"
                        />
                      </div>

                      <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                          <label className="font-bold text-slate-700 block mb-1">Strasse &amp; Nr.</label>
                          <input
                            type="text"
                            value={studentProfile.street}
                            onChange={e => setStudentProfile({ ...studentProfile, street: e.target.value })}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium"
                          />
                        </div>
                        <div>
                          <label className="font-bold text-slate-700 block mb-1">PLZ &amp; Ort</label>
                          <input
                            type="text"
                            value={`${studentProfile.zip} ${studentProfile.city}`.trim()}
                            onChange={e => {
                              const parts = e.target.value.split(' ');
                              setStudentProfile({
                                ...studentProfile,
                                zip: parts[0] || '',
                                city: parts.slice(1).join(' ') || ''
                              });
                            }}
                            className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium"
                          />
                        </div>
                      </div>

                      <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => setShowStudentProfileModal(false)}
                          className="px-4 py-2 text-slate-600 hover:text-slate-800 font-semibold"
                        >
                          Abbrechen
                        </button>
                        <button
                          type="submit"
                          className="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-md transition"
                        >
                          Profilangaben speichern &rarr;
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              )}

            </div>
          );
        })()}

        {/* ============================================================== */}
        {/* TAB 3: FAHRLEHRER-PORTAL & MONATSABSCHLUSS (PROMPT 2, 3 & 5)   */}
        {/* ============================================================== */}
        {activeTab === 'instructor' && (
          <div className="space-y-6">
            {/* Header & Instructor Switcher */}
            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="flex items-center gap-4">
                <div
                  className="w-14 h-14 rounded-2xl text-white font-extrabold text-xl flex items-center justify-center shadow-md"
                  style={{ backgroundColor: currentInstructor.color_code }}
                >
                  {currentInstructor.name.split(' ').map(n => n[0]).join('')}
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <h1 className="text-2xl font-bold text-slate-900">{currentInstructor.name}</h1>
                    <span className="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold">Aktiv</span>
                  </div>
                  <p className="text-xs text-slate-500 mt-0.5">{currentInstructor.title} &bull; Lizenz: {currentInstructor.license_number}</p>
                  <p className="text-xs text-sky-700 font-medium mt-0.5">
                    Honorarmodell: {currentInstructor.pay_rate_type === 'per_hour' ? `CHF ${currentInstructor.pay_rate_amount.toFixed(2)} / Stunde` : `CHF ${currentInstructor.pay_rate_amount.toFixed(2)} Pauschale / Termin`}
                    {currentInstructor.payroll_external_code && ` (Lohn-Code: ${currentInstructor.payroll_external_code})`}
                  </p>
                </div>
              </div>

              {/* Fahrlehrer & Monatswechsler */}
              <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-1.5 text-xs text-slate-500">
                  <User className="w-4 h-4" />
                  <span>Coach wechseln:</span>
                </div>
                <select
                  value={currentInstructorId}
                  onChange={e => setCurrentInstructorId(Number(e.target.value))}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"
                >
                  {INITIAL_INSTRUCTORS.map(i => (
                    <option key={i.id} value={i.id}>{i.name}</option>
                  ))}
                </select>

                <input
                  type="month"
                  value={selectedMonth}
                  onChange={e => setSelectedMonth(e.target.value)}
                  className="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"
                />
              </div>
            </div>

            {/* Monatsabschluss Banner */}
            <div className={`p-6 rounded-2xl border shadow-sm transition ${
              instructorMonthStats.openCount > 0 ? 'bg-amber-50/80 border-amber-300' : 'bg-emerald-50/80 border-emerald-300'
            }`}>
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                  <div className="flex items-center gap-2">
                    {instructorMonthStats.openCount > 0 ? (
                      <AlertTriangle className="w-5 h-5 text-amber-600" />
                    ) : (
                      <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                    )}
                    <h2 className="text-lg font-bold text-slate-900">
                      Monatsabschluss {selectedMonth}
                    </h2>
                  </div>
                  <p className="text-sm text-slate-600 mt-1">
                    {instructorMonthStats.openCount > 0
                      ? `Du hast noch ${instructorMonthStats.openCount} offene Fahrstunde(n) im Wert von CHF ${instructorMonthStats.openAmount.toFixed(2)}. Bitte prüfe deine Fahrten und übergebe sie an den Admin zur Lohnabrechnung.`
                      : `Alle ${instructorMonthStats.count} Fahrstunden dieses Monats wurden erfolgreich an die Administration übergeben oder bereits ausbezahlt.`}
                  </p>
                </div>

                {instructorMonthStats.openCount > 0 && (
                  <button
                    onClick={handleCloseMonthForInstructor}
                    className="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center gap-2 self-start md:self-auto"
                  >
                    <Check className="w-4 h-4" />
                    Monat {selectedMonth} jetzt abschliessen ({instructorMonthStats.openCount} Fahrten)
                  </button>
                )}
              </div>
            </div>

            {/* Monats-KPIs des Fahrlehrers */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="text-xs uppercase font-semibold text-slate-500">Fahrten im Monat</div>
                <div className="text-2xl font-bold text-slate-900 mt-1">{instructorMonthStats.count}</div>
                <div className="text-xs text-slate-400 mt-1">{instructorMonthStats.totalHours} Stunden Ausbildungszeit</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/40 shadow-sm">
                <div className="text-xs uppercase font-semibold text-amber-700">Honorar Offen</div>
                <div className="text-2xl font-bold text-amber-800 mt-1">CHF {instructorMonthStats.openAmount.toFixed(2)}</div>
                <div className="text-xs text-amber-600 mt-1">{instructorMonthStats.openCount} Termin(e) zur Übergabe bereit</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-sky-200 bg-sky-50/40 shadow-sm">
                <div className="text-xs uppercase font-semibold text-sky-700">Übergeben (Exported)</div>
                <div className="text-2xl font-bold text-sky-800 mt-1">CHF {instructorMonthStats.exportedAmount.toFixed(2)}</div>
                <div className="text-xs text-sky-600 mt-1">Warten auf Auszahlung</div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="text-xs uppercase font-semibold text-slate-500">Monatshonorar Gesamt</div>
                <div className="text-2xl font-bold text-slate-900 mt-1">CHF {instructorMonthStats.totalAmount.toFixed(2)}</div>
                <div className="text-xs text-emerald-600 mt-1">CHF {instructorMonthStats.paidAmount.toFixed(2)} bereits bezahlt</div>
              </div>
            </div>

            {/* Unter-Tabs für Fahrlehrer (Fahrten, Bevorstehende, Absenzen, Zeiterfassung, HR) */}
            <div className="border-b border-slate-200 flex space-x-4">
              <button
                onClick={() => setCoachTab('lessons')}
                className={`pb-3 text-sm font-bold border-b-2 transition ${
                  coachTab === 'lessons' ? 'border-sky-600 text-sky-600' : 'border-transparent text-slate-500 hover:text-slate-800'
                }`}
              >
                Fahrten im {selectedMonth} ({instructorMonthStats.count})
              </button>
              <button
                onClick={() => setCoachTab('upcoming')}
                className={`pb-3 text-sm font-bold border-b-2 transition ${
                  coachTab === 'upcoming' ? 'border-sky-600 text-sky-600' : 'border-transparent text-slate-500 hover:text-slate-800'
                }`}
              >
                Bevorstehende Termine ({appointments.filter(a => a.instructor_id === currentInstructorId && a.status === 'confirmed').length})
              </button>
              <button
                onClick={() => setCoachTab('availabilities')}
                className={`pb-3 text-sm font-bold border-b-2 transition ${
                  coachTab === 'availabilities' ? 'border-sky-600 text-sky-600' : 'border-transparent text-slate-500 hover:text-slate-800'
                }`}
              >
                Absenzen &amp; Sperrzeiten ({availabilities.filter(a => a.instructor_id === currentInstructorId).length})
              </button>
              <button
                onClick={() => setCoachTab('timesheet')}
                className={`pb-3 text-sm font-bold border-b-2 transition ${
                  coachTab === 'timesheet' ? 'border-sky-600 text-sky-600' : 'border-transparent text-slate-500 hover:text-slate-800'
                }`}
              >
                CH-Zeiterfassung &amp; Timesheets ({workingHours.filter(w => w.instructor_id === currentInstructorId).length})
              </button>
              <button
                onClick={() => setCoachTab('hr')}
                className={`pb-3 text-sm font-bold border-b-2 transition ${
                  coachTab === 'hr' ? 'border-sky-600 text-sky-600' : 'border-transparent text-slate-500 hover:text-slate-800'
                }`}
              >
                HR-Personalakte
              </button>
            </div>

            {/* Tab: Fahrten im Monat */}
            {coachTab === 'lessons' && (
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table className="w-full text-left text-sm text-slate-600">
                  <thead className="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                      <th className="p-3">Datum &amp; Zeit</th>
                      <th className="p-3">Schüler</th>
                      <th className="p-3">Leistung &amp; Boot</th>
                      <th className="p-3">Dauer</th>
                      <th className="p-3 text-right">Mein Honorar</th>
                      <th className="p-3">Status</th>
                      <th className="p-3">Abrechnungsnotiz</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {instructorMonthStats.appointments.map(a => (
                      <tr key={a.id} className="hover:bg-slate-50 transition">
                        <td className="p-3 font-medium text-slate-900">
                          <div>{a.date}</div>
                          <div className="text-xs text-slate-400">{a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)}</div>
                        </td>
                        <td className="p-3">
                          <div className="font-semibold text-slate-900">{a.customer_name}</div>
                          <div className="text-xs text-slate-400">{a.customer_phone}</div>
                        </td>
                        <td className="p-3">
                          <div className="text-slate-900">{a.service_name}</div>
                          <div className="text-xs text-slate-400">{a.boat_name}</div>
                        </td>
                        <td className="p-3 text-slate-700">{a.duration_minutes} Min</td>
                        <td className="p-3 text-right font-bold text-slate-900">CHF {a.instructor_payout_amount.toFixed(2)}</td>
                        <td className="p-3">
                          {a.instructor_payout_status === 'open' && (
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                              Offen
                            </span>
                          )}
                          {a.instructor_payout_status === 'exported' && (
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                              An Admin übergeben
                            </span>
                          )}
                          {a.instructor_payout_status === 'paid' && (
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                              Ausbezahlt
                            </span>
                          )}
                        </td>
                        <td className="p-3 text-xs text-slate-500">{a.instructor_payout_note}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* Tab: Bevorstehende Termine */}
            {coachTab === 'upcoming' && (
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table className="w-full text-left text-sm text-slate-600">
                  <thead className="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                      <th className="p-3">Datum &amp; Zeit</th>
                      <th className="p-3">Schüler &amp; Mobil</th>
                      <th className="p-3">Leistung &amp; Boot</th>
                      <th className="p-3">Treffpunkt / Hafen</th>
                      <th className="p-3 text-right">Vorschau Honorar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {appointments
                      .filter(a => a.instructor_id === currentInstructorId && a.status === 'confirmed')
                      .map(a => (
                        <tr key={a.id} className="hover:bg-slate-50 transition">
                          <td className="p-3 font-medium text-slate-900">
                            <div className="text-sky-700 font-bold">{a.date}</div>
                            <div className="text-xs text-slate-500">{a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)} Uhr</div>
                          </td>
                          <td className="p-3">
                            <div className="font-semibold text-slate-900">{a.customer_name}</div>
                            <div className="text-xs text-slate-500">{a.customer_phone} &bull; {a.customer_email}</div>
                          </td>
                          <td className="p-3">
                            <div className="text-slate-900">{a.service_name}</div>
                            <div className="text-xs text-slate-400">{a.boat_name}</div>
                          </td>
                          <td className="p-3 text-slate-700">{a.location_name}</td>
                          <td className="p-3 text-right font-bold text-slate-900">CHF {a.instructor_payout_amount.toFixed(2)}</td>
                        </tr>
                      ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* Tab: Absenzen & Sperrzeiten */}
            {coachTab === 'availabilities' && (
              <div className="space-y-4">
                <div className="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200">
                  <div className="text-sm text-slate-600">
                    Gemeldete Absenzen verhindern automatisch Doppelbuchungen im Kunden-Portal und Admin-Kalender.
                  </div>
                  <button
                    onClick={() => setShowAddAbsence(true)}
                    className="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-xs"
                  >
                    <Plus className="w-4 h-4" />
                    Absenz / Sperrzeit erfassen (stundenweise)
                  </button>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                  <table className="w-full text-left text-sm text-slate-600">
                    <thead className="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                      <tr>
                        <th className="p-3">Datum</th>
                        <th className="p-3">Typ</th>
                        <th className="p-3">Zeitfenster (von – bis)</th>
                        <th className="p-3">Grund / Bemerkung</th>
                        <th className="p-3 text-right">Aktion</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {availabilities
                        .filter(a => a.instructor_id === currentInstructorId)
                        .map(av => (
                          <tr key={av.id} className="hover:bg-slate-50 transition">
                            <td className="p-3 font-bold text-slate-900">{av.date}</td>
                            <td className="p-3">
                              <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                av.type === 'vacation' ? 'bg-emerald-100 text-emerald-800' :
                                av.type === 'sick' ? 'bg-rose-100 text-rose-800' :
                                av.type === 'military' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800'
                              }`}>
                                {av.type === 'vacation' ? 'Ferien / Urlaub' :
                                 av.type === 'sick' ? 'Krankheit / Unfall' :
                                 av.type === 'military' ? 'Militärdienst' : 'Sperrzeit'}
                              </span>
                            </td>
                            <td className="p-3 text-slate-700 font-medium">
                              {av.start_time && av.end_time ? (
                                <span className="flex items-center gap-1.5 text-slate-900 font-semibold">
                                  <Clock className="w-3.5 h-3.5 text-amber-600" />
                                  {av.start_time.slice(0, 5)} – {av.end_time.slice(0, 5)} Uhr
                                </span>
                              ) : (
                                <span className="text-slate-400">Ganztägig</span>
                              )}
                            </td>
                            <td className="p-3 text-slate-600">{av.note}</td>
                            <td className="p-3 text-right">
                              <button
                                onClick={() => setAvailabilities(prev => prev.filter(x => x.id !== av.id))}
                                className="text-xs text-rose-600 hover:text-rose-800 font-semibold"
                              >
                                Löschen
                              </button>
                            </td>
                          </tr>
                        ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Tab: Zeiterfassung & Timesheets */}
            {coachTab === 'timesheet' && (
              <div className="space-y-4">
                <div className="bg-white p-4 rounded-xl border border-slate-200 flex justify-between items-center">
                  <div>
                    <h3 className="font-bold text-slate-900 text-sm">Schweizer Zeiterfassung (Timesheet)</h3>
                    <p className="text-xs text-slate-500">Zusatzleistungen wie Bootswartung, Bereitstellung, Theorieunterricht oder Prüfungsbegleitung.</p>
                  </div>
                  <button
                    onClick={() => {
                      setWhDate('2026-03-24');
                      setWhHours(3);
                      setWhMinutes(0);
                      setWhStartTime('09:00');
                      setWhBreakMinutes(0);
                      setWhCategory('maintenance');
                      setWhSubject('Bootsprüfung Vorbereitung & Tanken');
                      setShowAddWorkingHour(true);
                    }}
                    className="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm"
                  >
                    <Plus className="w-4 h-4" />
                    Arbeitszeit erfassen (h &amp; min)
                  </button>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                  <table className="w-full text-left text-sm text-slate-600">
                    <thead className="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                      <tr>
                        <th className="p-3">Datum</th>
                        <th className="p-3">Kategorie</th>
                        <th className="p-3">Zeit &amp; Pause</th>
                        <th className="p-3">Stunden</th>
                        <th className="p-3 text-right">Ansatz (CHF)</th>
                        <th className="p-3 text-right">Total (CHF)</th>
                        <th className="p-3">Status</th>
                        <th className="p-3">Betreff / Notiz</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {workingHours
                        .filter(w => w.instructor_id === currentInstructorId)
                        .map(w => (
                          <tr key={w.id} className="hover:bg-slate-50 transition">
                            <td className="p-3 font-bold text-slate-900">{w.date}</td>
                            <td className="p-3 capitalize">{w.category}</td>
                            <td className="p-3 text-slate-500">
                              {w.start_time.slice(0, 5)} - {w.end_time.slice(0, 5)} ({w.break_minutes}m Pause)
                            </td>
                            <td className="p-3 font-bold text-slate-800">{w.total_hours.toFixed(2)} h</td>
                            <td className="p-3 text-right">CHF {w.rate_amount.toFixed(2)}</td>
                            <td className="p-3 text-right font-bold text-sky-700">CHF {w.total_amount.toFixed(2)}</td>
                            <td className="p-3">
                              <span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${
                                w.status === 'approved' ? 'bg-emerald-100 text-emerald-800' :
                                w.status === 'submitted' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'
                              }`}>
                                {w.status}
                              </span>
                            </td>
                            <td className="p-3 text-xs text-slate-500">{w.note}</td>
                          </tr>
                        ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Tab: HR-Personalakte (Prompt 3) */}
            {coachTab === 'hr' && (
              <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
                <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                  <div>
                    <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                      <Shield className="w-5 h-5 text-indigo-600" />
                      HR-Personalakte (bs_instructor_details)
                    </h3>
                    <p className="text-xs text-slate-500">Vertrauliche Personaldaten für das Schweizer Lohnsystem</p>
                  </div>
                  <span className="text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full font-semibold border border-indigo-200">
                    CH Sozialversicherung
                  </span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <span className="text-xs uppercase font-semibold text-slate-400">AHV-Nummer (Schweiz)</span>
                    <div className="font-mono font-bold text-slate-900">{currentInstructor.ahv_number || '756.xxxx.xxxx.xx'}</div>
                  </div>

                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <span className="text-xs uppercase font-semibold text-slate-400">Bank IBAN</span>
                    <div className="font-mono font-bold text-slate-900">{currentInstructor.bank_iban}</div>
                    <div className="text-xs text-slate-500">{currentInstructor.bank_name}, {currentInstructor.bank_city}</div>
                  </div>

                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <span className="text-xs uppercase font-semibold text-slate-400">Wohnadresse</span>
                    <div className="font-medium text-slate-900">{currentInstructor.address}</div>
                  </div>

                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <span className="text-xs uppercase font-semibold text-slate-400">Fahrlehrer-Lizenz</span>
                    <div className="font-semibold text-slate-900">{currentInstructor.license_number}</div>
                  </div>

                  <div className="p-4 bg-emerald-50 rounded-xl border border-emerald-200 space-y-2">
                    <div className="flex justify-between items-center">
                      <span className="text-xs uppercase font-semibold text-emerald-800 flex items-center gap-1">
                        <Lock className="w-3.5 h-3.5 text-emerald-600" />
                        Lohn-Stundensatz (Admin-Einstellung)
                      </span>
                      <span className="text-[10px] bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-full font-bold">Fest hinterlegt</span>
                    </div>

                    {editingRateInstructorId === currentInstructor.id ? (
                      <div className="flex items-center gap-2 mt-1">
                        <div className="relative flex-1">
                          <input
                            type="number"
                            step="0.5"
                            min="0"
                            value={tempRateInput}
                            onChange={e => setTempRateInput(parseFloat(e.target.value) || 0)}
                            className="w-full px-2.5 py-1 text-sm bg-white border border-emerald-400 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500"
                          />
                          <span className="absolute right-2.5 top-1 text-xs text-slate-400 font-medium">CHF</span>
                        </div>
                        <button
                          type="button"
                          onClick={() => handleSaveInstructorRate(currentInstructor.id, tempRateInput)}
                          className="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold shadow-xs"
                        >
                          Speichern
                        </button>
                        <button
                          type="button"
                          onClick={() => setEditingRateInstructorId(null)}
                          className="px-2 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs"
                        >
                          Abbrechen
                        </button>
                      </div>
                    ) : (
                      <div className="flex items-center justify-between">
                        <div className="font-bold text-emerald-900 text-base">
                          CHF {currentInstructor.pay_rate_amount.toFixed(2)} <span className="text-xs font-normal text-emerald-700">/ Std.</span>
                        </div>
                        <button
                          type="button"
                          onClick={() => {
                            setEditingRateInstructorId(currentInstructor.id);
                            setTempRateInput(currentInstructor.pay_rate_amount);
                          }}
                          className="px-2.5 py-1 bg-white hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-semibold transition"
                          title="Als Admin den Stundensatz für diesen Fahrlehrer anpassen"
                        >
                          Ansatz anpassen (Admin)
                        </button>
                      </div>
                    )}
                    <p className="text-[10px] text-emerald-700">
                      Vom Admin hinterlegt: Wird automatisch und unveränderbar in alle Timesheets übernommen.
                    </p>
                  </div>

                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <span className="text-xs uppercase font-semibold text-slate-400">Kreditoren-Code</span>
                    <div className="font-mono font-semibold text-slate-900">{currentInstructor.payroll_external_code}</div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* ============================================================== */}
        {/* TAB 4: NAUTIC EIGENE LOGONSEITE MIT SVG LOGO                   */}
        {/* ============================================================== */}
        {activeTab === 'login' && (
          <div className="space-y-6">
            {/* Infobox & Direktlinks */}
            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <div className="flex items-center gap-2">
                  <span className="p-1.5 bg-sky-100 text-sky-700 rounded-lg">
                    <Compass className="w-5 h-5" />
                  </span>
                  <h2 className="text-xl font-bold text-slate-900">Eigene Nautic Logonseite mit SVG-Logo</h2>
                </div>
                <p className="text-sm text-slate-600 mt-1">
                  Einheitlicher Magic-Link Zugang für alle Benutzer (Fahrlehrer, Schüler, Charterkunden &amp; Flottenleitung) sowie Registrierung mit anschliessender administrativer Profil-Zuweisung.
                </p>
              </div>
              <div className="flex items-center gap-2 flex-wrap">
                <a
                  href="?route=nautic/login"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-sm transition"
                  title="Öffnet die PHP-Logonseite im neuen Browser-Tab"
                >
                  <LogIn className="w-4 h-4" />
                  <span>PHP Live-Route (?route=nautic/login)</span>
                </a>
                <a
                  href="?route=admin/boating/account-requests"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition"
                  title="Öffnet die PHP Admin-Prüfseite für Account-Anfragen"
                >
                  <UserCheck className="w-4 h-4 text-sky-600" />
                  <span>PHP Admin-Freigaben</span>
                </a>
              </div>
            </div>

            {/* Maritime Auth Stage (interaktive Vorschau) */}
            <div className="rounded-3xl p-6 md:p-12 shadow-2xl relative overflow-hidden flex items-center justify-center min-h-[580px]"
                 style={{
                   background: 'radial-gradient(circle at 50% 15%, #0d3859 0%, #0c2d48 45%, #06192a 100%)'
                 }}>
              
              {/* Dekorative Hintergrund-Punkte */}
              <div className="absolute inset-0 opacity-15 pointer-events-none"
                   style={{
                     backgroundImage: 'radial-gradient(#38bdf8 1px, transparent 1px)',
                     backgroundSize: '24px 24px'
                   }} />

              {/* Login / Registrierungs-Card */}
              <div className="w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl p-6 sm:p-8 shadow-2xl border border-white/20 relative z-10 space-y-5">
                
                {/* SVG Logo Einbindung – Ohne Untertext darunter */}
                <div className="text-center">
                  <div className="inline-flex items-center justify-center">
                    <svg viewBox="0 0 280 64" className="w-64 h-auto drop-shadow-sm" role="img" aria-label="Nautic Logo">
                      <defs>
                        <linearGradient id="appNauticBlue" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stopColor="#0284c7" />
                          <stop offset="50%" stopColor="#0369a1" />
                          <stop offset="100%" stopColor="#072a45" />
                        </linearGradient>
                        <linearGradient id="appNauticCyan" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stopColor="#38bdf8" />
                          <stop offset="100%" stopColor="#06b6d4" />
                        </linearGradient>
                        <linearGradient id="appNauticGold" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stopColor="#f59e0b" />
                          <stop offset="100%" stopColor="#fbbf24" />
                        </linearGradient>
                      </defs>

                      {/* Emblem */}
                      <g transform="translate(4, 4)">
                        <circle cx="28" cy="28" r="27" fill="url(#appNauticBlue)" />
                        <circle cx="28" cy="28" r="23" fill="none" stroke="#ffffff" strokeWidth="1.5" strokeOpacity="0.3" strokeDasharray="3 3" />
                        <path d="M 28 8 L 30 20 L 28 17 L 26 20 Z" fill="url(#appNauticCyan)" />
                        <path d="M 28 48 L 30 36 L 28 39 L 26 36 Z" fill="url(#appNauticCyan)" opacity="0.7" />
                        <path d="M 8 28 L 20 26 L 17 28 L 20 30 Z" fill="url(#appNauticCyan)" opacity="0.7" />
                        <path d="M 48 28 L 36 26 L 39 28 L 36 30 Z" fill="url(#appNauticCyan)" opacity="0.7" />
                        <path d="M 28 15 L 40 37 C 36 40, 20 40, 16 37 Z" fill="#ffffff" />
                        <path d="M 28 15 L 36 36 L 28 34 Z" fill="url(#appNauticCyan)" opacity="0.85" />
                        <path d="M 28 15 L 20 36 L 28 34 Z" fill="#e0f2fe" opacity="0.95" />
                        <path d="M 12 41 C 18 38, 22 43, 28 40 C 34 37, 38 42, 44 39" fill="none" stroke="url(#appNauticCyan)" strokeWidth="2.5" strokeLinecap="round" />
                        <path d="M 16 46 C 21 44, 24 47, 28 45 C 32 43, 35 46, 40 44" fill="none" stroke="url(#appNauticGold)" strokeWidth="1.8" strokeLinecap="round" />
                        <circle cx="28" cy="27" r="2.2" fill="url(#appNauticGold)" />
                      </g>

                      {/* Text */}
                      <g transform="translate(72, 0)">
                        <text x="0" y="35" fontFamily="system-ui, sans-serif" fontSize="28" fontWeight="900" letterSpacing="3" fill="#0c4a6e">
                          NAUTIC
                        </text>
                        <rect x="1" y="42" width="180" height="2" rx="1" fill="url(#appNauticCyan)" />
                        <text x="2" y="55" fontFamily="system-ui, sans-serif" fontSize="9" fontWeight="700" letterSpacing="2.2" fill="#0284c7">
                          FAHRSCHULE &amp; VERMIETUNG
                        </text>
                      </g>
                    </svg>
                  </div>
                </div>

                {/* Erfolgsmeldung / Toast */}
                {loginSuccessMsg && (
                  <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center gap-2 animate-in fade-in">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{loginSuccessMsg}</span>
                  </div>
                )}

                {/* ANSICHT 1: UNIFIZIERTER MAGIC-LINK LOGIN (2 STUFEN) */}
                {authViewMode === 'login' ? (
                  nauticLoginStep === 'email' ? (
                    /* ================================================== */
                    /* STUFE 1 (STANDARD): NUR E-MAIL-ADRESSE EINGEBEN   */
                    /* ================================================== */
                    <form onSubmit={handleNauticLoginSubmit} className="space-y-4">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                          Deine E-Mail-Adresse
                        </label>
                        <div className="relative">
                          <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                          <input
                            type="email"
                            value={loginEmail}
                            onChange={e => setLoginEmail(e.target.value)}
                            required
                            autoFocus
                            className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-inner"
                            placeholder="name@nautic.ch"
                          />
                        </div>
                        <p className="text-[11px] text-slate-500 mt-1">
                          Wir senden dir sofort einen sicheren, passwortlosen Magic-Code.
                        </p>
                      </div>

                      <div className="flex items-center justify-between text-xs text-slate-500">
                        <label className="flex items-center gap-1.5 cursor-pointer">
                          <input
                            type="checkbox"
                            checked={loginRememberMe}
                            onChange={e => setLoginRememberMe(e.target.checked)}
                            className="rounded text-sky-600 focus:ring-sky-500"
                          />
                          <span>E-Mail auf diesem Gerät merken</span>
                        </label>
                        <span className="text-[11px] text-slate-400">CH 256-Bit SSL</span>
                      </div>

                      <button
                        type="submit"
                        className="w-full py-2.5 px-4 bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-700 hover:to-sky-800 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <LogIn className="w-4 h-4" />
                        <span>Magic-Code anfordern &rarr;</span>
                      </button>

                      {/* Umschalten zu: Account anfordern */}
                      <div className="pt-2 text-center border-t border-slate-100">
                        <button
                          type="button"
                          onClick={() => setAuthViewMode('register')}
                          className="text-xs text-sky-700 hover:text-sky-900 font-semibold inline-flex items-center gap-1.5 transition cursor-pointer hover:underline"
                        >
                          <UserPlus className="w-3.5 h-3.5" />
                          <span>Noch kein Nautic-Konto? Jetzt Account anfordern &rarr;</span>
                        </button>
                      </div>

                      {/* Schnellzugriff Demo Buttons für Prototyp (NUR WENN IM ADMIN AKTIVIERT) */}
                      {nauticLoginTestMode && (
                        <div className="pt-3 border-t border-slate-200/80 animate-in fade-in">
                          <div className="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mb-1.5">
                            <Sparkles className="w-3 h-3" />
                            <span>Testmodus aktiv (Schnell-Auswahl):</span>
                          </div>
                          <div className="flex flex-wrap gap-1.5">
                            <button
                              type="button"
                              onClick={() => {
                                setLoginEmail('beat@bootschule-see.ch');
                                setNauticLoginStep('code');
                                fillTestCode('ROLAND1234');
                              }}
                              className="px-2 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-lg text-[11px] font-medium border border-sky-200 transition cursor-pointer"
                            >
                              Beat (Fahrlehrer)
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setLoginEmail('office@studiocreativo.ch');
                                setNauticLoginStep('code');
                                fillTestCode('ROLAND1234');
                              }}
                              className="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-medium border border-slate-200 transition cursor-pointer"
                            >
                              Office (Admin)
                            </button>
                            <button
                              type="button"
                              onClick={() => {
                                setLoginEmail('lukas.berner@swiss-pilot.ch');
                                setNauticLoginStep('code');
                                fillTestCode('LUKAS12345');
                              }}
                              className="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-[11px] font-medium border border-emerald-200 transition cursor-pointer"
                            >
                              Lukas (Schüler)
                            </button>
                          </div>
                        </div>
                      )}
                    </form>
                  ) : (
                    /* ================================================== */
                    /* STUFE 2: MAGIC-CODE IN KLEINEN BOXEN EINGEBEN      */
                    /* ================================================== */
                    <form onSubmit={handleNauticLoginSubmit} className="space-y-4 animate-in fade-in">
                      {/* E-Mail Info & Wechseln-Button */}
                      <div className="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <div className="text-left">
                          <span className="text-[10px] text-slate-400 block font-medium">E-Mail-Adresse:</span>
                          <span className="text-xs font-mono font-bold text-slate-800">{loginEmail}</span>
                        </div>
                        <button
                          type="button"
                          onClick={() => setNauticLoginStep('email')}
                          className="px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer"
                        >
                          E-Mail ändern
                        </button>
                      </div>

                      <div className="text-center">
                        <h4 className="text-sm font-bold text-slate-900">Magic-Code eingeben</h4>
                        <p className="text-[11px] text-slate-500 mt-0.5">
                          Gib deinen 10-stelligen Code aus der E-Mail ein:
                        </p>
                      </div>

                      {/* 10 kleine Boxen – genau eine Box pro Code-Zeichen */}
                      <div className="flex justify-between gap-1 sm:gap-1.5 py-1">
                        {codeDigits.map((digit, idx) => (
                          <input
                            key={idx}
                            id={`digit-box-${idx}`}
                            type="text"
                            maxLength={1}
                            value={digit}
                            onChange={e => handleDigitChange(idx, e.target.value)}
                            onKeyDown={e => handleDigitKeyDown(idx, e)}
                            onPaste={handleDigitPaste}
                            autoFocus={idx === 0}
                            className="w-8 sm:w-9 h-11 text-center font-mono font-bold text-sm sm:text-base text-sky-950 bg-slate-50 border-2 border-slate-300 rounded-lg focus:bg-white focus:border-sky-500 focus:ring-2 focus:ring-sky-200 focus:outline-none transition shadow-inner"
                          />
                        ))}
                      </div>

                      <button
                        type="submit"
                        className="w-full py-2.5 px-4 bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-700 hover:to-sky-800 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <CheckCircle2 className="w-4 h-4" />
                        <span>Code bestätigen &amp; Anmelden</span>
                      </button>

                      <div className="text-center pt-1 border-t border-slate-100 flex items-center justify-center gap-1.5 text-xs text-slate-500">
                        <span>Keinen Code erhalten?</span>
                        <button
                          type="button"
                          onClick={() => {
                            setLoginSuccessMsg(`Neuer Magic-Code wurde an ${loginEmail} gesendet.`);
                            setTimeout(() => setLoginSuccessMsg(null), 3000);
                          }}
                          className="font-semibold text-sky-700 hover:text-sky-900 hover:underline cursor-pointer"
                        >
                          Neuen Code anfordern
                        </button>
                      </div>

                      {/* Testmodus: Backdoor Code einfüllen */}
                      {nauticLoginTestMode && (
                        <div className="pt-2 text-center">
                          <button
                            type="button"
                            onClick={() => fillTestCode('ROLAND1234')}
                            className="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-[11px] font-semibold border border-amber-200 transition cursor-pointer"
                          >
                            Dev-Code (ROLAND1234) einfüllen
                          </button>
                        </div>
                      )}
                    </form>
                  )
                ) : (
                  /* ANSICHT 2: NEUEN ACCOUNT ANFORDERN */
                  <form onSubmit={handleRegisterAccountSubmit} className="space-y-3.5">
                    <div className="border-b border-slate-100 pb-2 text-center">
                      <div className="flex items-center justify-center gap-1.5 text-sky-700 font-bold text-sm">
                        <UserPlus className="w-4 h-4" />
                        <span>Herzlich willkommen an Bord!</span>
                      </div>
                      <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Schön, dass du dabei bist! Hinterlasse uns kurz deine Kontaktdaten – wir richten deinen persönlichen Zugang für dich ein und melden uns so schnell wie möglich bei dir.
                      </p>
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="block text-[11px] font-medium text-slate-700 mb-1">Vorname *</label>
                        <input
                          type="text"
                          required
                          value={regFirstName}
                          onChange={e => setRegFirstName(e.target.value)}
                          placeholder="Max"
                          className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-medium text-slate-700 mb-1">Nachname *</label>
                        <input
                          type="text"
                          required
                          value={regLastName}
                          onChange={e => setRegLastName(e.target.value)}
                          placeholder="Muster"
                          className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-medium text-slate-700 mb-1">E-Mail-Adresse *</label>
                      <input
                        type="email"
                        required
                        value={regEmail}
                        onChange={e => setRegEmail(e.target.value)}
                        placeholder="max.muster@beispiel.ch"
                        className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-medium text-slate-700 mb-1">Telefon / Mobile (optional)</label>
                      <input
                        type="tel"
                        value={regPhone}
                        onChange={e => setRegPhone(e.target.value)}
                        placeholder="+41 79 123 45 67"
                        className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-medium text-slate-700 mb-1">Bemerkung / Vorkenntnisse (optional)</label>
                      <textarea
                        rows={2}
                        value={regNotes}
                        onChange={e => setRegNotes(e.target.value)}
                        placeholder="z. B. Kat. A Theorie bereits bestanden, Interesse an Charter..."
                        className="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                      />
                    </div>

                    <button
                      type="submit"
                      className="w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer"
                    >
                      <UserPlus className="w-4 h-4" />
                      <span>Zugang anfordern</span>
                    </button>

                    <div className="pt-1 text-center">
                      <button
                        type="button"
                        onClick={() => setAuthViewMode('login')}
                        className="text-xs text-slate-600 hover:text-slate-900 font-medium inline-flex items-center gap-1 transition cursor-pointer hover:underline"
                      >
                        &larr; Zurück zum Magic-Link Login
                      </button>
                    </div>
                  </form>
                )}

              </div>

              {/* Copyright fest im Hauptfenster UNTEN LINKS */}
              <div className="absolute bottom-4 left-6 z-20 text-[11px] text-sky-200/60 font-medium select-none pointer-events-auto">
                &copy; {new Date().getFullYear()} Coaching-Boot / Nautic. Alle Rechte vorbehalten.
              </div>
            </div>

            {/* ============================================================== */}
            {/* ADMIN-KONSOLE: ACCOUNT-ANFRAGEN PRÜFEN & PROFIL ZUWEISEN       */}
            {/* ============================================================== */}
            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                  <span className="p-2 bg-amber-50 text-amber-700 rounded-xl">
                    <UserCheck className="w-5 h-5" />
                  </span>
                  <div>
                    <h3 className="text-base font-bold text-slate-900">
                      Admin-Konsole: Offene Account-Anfragen &amp; Profil-Zuweisung
                    </h3>
                    <p className="text-xs text-slate-500">
                      Hier prüfst du eingereichte Registrierungen, bestimmst das finale Profil und gibst den Zugang mit 1-Klick frei.
                    </p>
                  </div>
                </div>

                {/* Filter-Tabs */}
                <div className="flex bg-slate-100 p-1 rounded-xl text-xs font-semibold gap-1 self-start sm:self-auto">
                  <button
                    type="button"
                    onClick={() => setRequestFilter('pending')}
                    className={`px-3 py-1.5 rounded-lg transition ${
                      requestFilter === 'pending' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Ausstehend ({accountRequests.filter(r => r.status === 'pending').length})
                  </button>
                  <button
                    type="button"
                    onClick={() => setRequestFilter('approved')}
                    className={`px-3 py-1.5 rounded-lg transition ${
                      requestFilter === 'approved' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Bestätigt ({accountRequests.filter(r => r.status === 'approved').length})
                  </button>
                  <button
                    type="button"
                    onClick={() => setRequestFilter('rejected')}
                    className={`px-3 py-1.5 rounded-lg transition ${
                      requestFilter === 'rejected' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Abgelehnt ({accountRequests.filter(r => r.status === 'rejected').length})
                  </button>
                  <button
                    type="button"
                    onClick={() => setRequestFilter('all')}
                    className={`px-3 py-1.5 rounded-lg transition ${
                      requestFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Alle ({accountRequests.length})
                  </button>
                </div>
              </div>

              {/* Anfragen-Tabelle / Karten */}
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-600 font-semibold border-y border-slate-200">
                    <tr>
                      <th className="py-2.5 px-3">Antragsteller</th>
                      <th className="py-2.5 px-3">Kontakt</th>
                      <th className="py-2.5 px-3">Wunsch &amp; Notiz</th>
                      <th className="py-2.5 px-3">Profil-Zuweisung (Admin)</th>
                      <th className="py-2.5 px-3">Status</th>
                      <th className="py-2.5 px-3 text-right">Aktionen</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {accountRequests
                      .filter(r => requestFilter === 'all' || r.status === requestFilter)
                      .map(req => {
                        const selectedProfile = approvalProfileSelections[req.id] || req.desired_role;
                        return (
                          <tr key={req.id} className="hover:bg-slate-50/70 transition">
                            <td className="py-3 px-3 font-medium text-slate-900">
                              <div className="font-semibold">{req.first_name} {req.last_name}</div>
                              <div className="text-[10px] text-slate-400 font-normal">Eingang: {req.created_at}</div>
                            </td>
                            <td className="py-3 px-3">
                              <div className="flex items-center gap-1 text-slate-700 font-medium">
                                <Mail className="w-3.5 h-3.5 text-slate-400" />
                                <span>{req.email}</span>
                              </div>
                              {req.phone && (
                                <div className="flex items-center gap-1 text-slate-500 text-[11px] mt-0.5">
                                  <Phone className="w-3 h-3 text-slate-400" />
                                  <span>{req.phone}</span>
                                </div>
                              )}
                            </td>
                            <td className="py-3 px-3 max-w-[200px]">
                              <span className="inline-block px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-medium mb-1">
                                Wunsch: {req.desired_role === 'instructor' ? 'Fahrlehrer' : req.desired_role === 'student' ? 'Schüler' : req.desired_role === 'renter' ? 'Bootsmieter' : 'Flottenleitung'}
                              </span>
                              {req.notes && (
                                <p className="text-[11px] text-slate-500 italic truncate" title={req.notes}>
                                  "{req.notes}"
                                </p>
                              )}
                            </td>
                            <td className="py-3 px-3">
                              {req.status === 'pending' ? (
                                <div className="space-y-1">
                                  <select
                                    value={selectedProfile}
                                    onChange={e =>
                                      setApprovalProfileSelections(prev => ({
                                        ...prev,
                                        [req.id]: e.target.value as any
                                      }))
                                    }
                                    className="px-2 py-1.5 bg-amber-50/60 border border-amber-300 rounded-lg text-xs font-medium text-amber-950 focus:outline-none focus:ring-1 focus:ring-amber-500"
                                  >
                                    <option value="instructor">Fahrlehrer / Coach</option>
                                    <option value="student">Schüler / Fahrschüler</option>
                                    <option value="renter">Bootsmieter / Charterkunde</option>
                                    <option value="admin">Flottenleitung / Admin</option>
                                  </select>
                                </div>
                              ) : (
                                <div className="font-semibold text-slate-800">
                                  {req.assigned_profile === 'instructor' && 'Fahrlehrer / Coach'}
                                  {req.assigned_profile === 'student' && 'Schüler / Fahrschüler'}
                                  {req.assigned_profile === 'renter' && 'Bootsmieter / Charter'}
                                  {req.assigned_profile === 'admin' && 'Flottenleitung / Admin'}
                                  {!req.assigned_profile && '-'}
                                </div>
                              )}
                            </td>
                            <td className="py-3 px-3">
                              {req.status === 'pending' && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-[11px] font-semibold">
                                  <Clock className="w-3 h-3" /> Ausstehend
                                </span>
                              )}
                              {req.status === 'approved' && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-[11px] font-semibold">
                                  <Check className="w-3 h-3" /> Bestätigt
                                </span>
                              )}
                              {req.status === 'rejected' && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full text-[11px] font-semibold">
                                  <X className="w-3 h-3" /> Abgelehnt
                                </span>
                              )}
                            </td>
                            <td className="py-3 px-3 text-right">
                              {req.status === 'pending' ? (
                                <div className="flex items-center justify-end gap-1.5">
                                  <button
                                    type="button"
                                    onClick={() => handleApproveAccountRequest(req.id)}
                                    className="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold flex items-center gap-1 shadow-sm transition cursor-pointer"
                                    title="Antrag bestätigen & Profil aktivieren"
                                  >
                                    <Check className="w-3.5 h-3.5" />
                                    <span>Bestätigen</span>
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => handleRejectAccountRequest(req.id)}
                                    className="px-2 py-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 rounded-lg transition cursor-pointer"
                                    title="Antrag ablehnen"
                                  >
                                    <X className="w-3.5 h-3.5" />
                                  </button>
                                </div>
                              ) : (
                                <span className="text-[11px] text-slate-400">
                                  {req.approved_at ? `Freigabe: ${req.approved_at}` : 'Bearbeitet'}
                                </span>
                              )}
                            </td>
                          </tr>
                        );
                      })}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* ============================================================== */}
        {/* TAB 5: STARTSEITE BEARBEITEN & MINI-CMS (TEMPLATES & BLÖCKE)   */}
        {/* ============================================================== */}
        {activeTab === 'cms' && (
          <div className="space-y-6">
            {/* Header mit Direktlinks */}
            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <div className="flex items-center gap-2">
                  <span className="p-1.5 bg-sky-100 text-sky-700 rounded-lg">
                    <LayoutTemplate className="w-5 h-5" />
                  </span>
                  <h2 className="text-xl font-bold text-slate-900">Startseite bearbeiten &amp; Mini-CMS</h2>
                </div>
                <p className="text-sm text-slate-600 mt-1">
                  Wähle eine fertige Vorlage (z. B. <strong>Nautic – Bootsfahrschule &amp; Vermietung</strong>), bearbeite alle Inhaltsblöcke nach Belieben und passe Farben, Logo und Layout in Echtzeit an.
                </p>
              </div>
              <div className="flex items-center gap-2 flex-wrap">
                <a
                  href="?route=admin/homepage"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-sm transition"
                  title="Öffnet das PHP-Backend Mini-CMS im neuen Browser-Tab"
                >
                  <FileText className="w-4 h-4" />
                  <span>PHP Live-CMS öffnen (?route=admin/homepage)</span>
                </a>
                <a
                  href="?route=/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition"
                  title="Öffnet die öffentliche Startseite"
                >
                  <Eye className="w-4 h-4 text-sky-600" />
                  <span>Live-Startseite ansehen (?route=/)</span>
                </a>
              </div>
            </div>

            {/* Toast Feedback */}
            {cmsToast && (
              <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center gap-2 animate-in fade-in shadow-xs">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>{cmsToast}</span>
              </div>
            )}

            {/* CMS Sub-Navigation Tabs */}
            <div className="flex bg-slate-100 p-1 rounded-2xl gap-1 text-xs font-semibold overflow-x-auto">
              <button
                type="button"
                onClick={() => setCmsSubTab('templates')}
                className={`py-2 px-4 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer ${
                  cmsSubTab === 'templates' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <Sparkles className="w-4 h-4 text-amber-500" />
                <span>1. Vorlagen (Templates)</span>
                <span className="px-1.5 py-0.2 bg-sky-100 text-sky-800 rounded-full text-[10px]">
                  {cmsActiveTemplate.toUpperCase()}
                </span>
              </button>

              <button
                type="button"
                onClick={() => setCmsSubTab('blocks')}
                className={`py-2 px-4 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer ${
                  cmsSubTab === 'blocks' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <Layers className="w-4 h-4 text-sky-600" />
                <span>2. Inhaltsblöcke (Sections)</span>
                <span className="px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded-full text-[10px]">
                  {cmsBlocks.length}
                </span>
              </button>

              <button
                type="button"
                onClick={() => setCmsSubTab('design')}
                className={`py-2 px-4 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer ${
                  cmsSubTab === 'design' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <Palette className="w-4 h-4 text-indigo-600" />
                <span>3. Design, Farben &amp; Logo</span>
              </button>

              <button
                type="button"
                onClick={() => setCmsSubTab('preview')}
                className={`py-2 px-4 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer ${
                  cmsSubTab === 'preview' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <Eye className="w-4 h-4 text-emerald-600" />
                <span>4. Live-Vorschau der Startseite</span>
              </button>
            </div>

            {/* ============================================================== */}
            {/* SUBTAB 1: VORLAGEN (TEMPLATES) AUSWAHL                         */}
            {/* ============================================================== */}
            {cmsSubTab === 'templates' && (
              <div className="space-y-4">
                <div className="bg-sky-50 border border-sky-200 rounded-2xl p-4 text-xs text-sky-900 flex items-start gap-3">
                  <Sparkles className="w-5 h-5 text-sky-600 shrink-0 mt-0.5" />
                  <div>
                    <strong className="block text-sm font-bold mb-0.5">Template-System für die Startseite</strong>
                    <span>
                      Wähle eine der Vorlagen, um Design, Farben und Abschnitte mit 1-Klick vorzubereiten. 
                      Sobald ein Template angewendet wird, werden alle Inhaltsblöcke generiert und können anschließend im Tab <strong>„Inhaltsblöcke“</strong> frei editiert werden.
                    </span>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                  {/* Template 1: NAUTIC (EMPFOHLEN) */}
                  <div className={`bg-white rounded-2xl p-5 border-2 transition shadow-sm relative overflow-hidden flex flex-col justify-between ${
                    cmsActiveTemplate === 'nautic' ? 'border-sky-500 shadow-md ring-2 ring-sky-100' : 'border-slate-200 hover:border-slate-300'
                  }`}>
                    {cmsActiveTemplate === 'nautic' && (
                      <div className="absolute top-0 right-0 bg-sky-600 text-white text-[11px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
                        ● Aktiv im System
                      </div>
                    )}
                    <div>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="px-2.5 py-0.5 bg-sky-100 text-sky-800 rounded-full text-xs font-bold">
                          Offiziell für Nautic
                        </span>
                        <span className="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[11px] font-semibold">
                          Empfohlen
                        </span>
                      </div>
                      <h3 className="text-lg font-bold text-slate-900 mb-1">Nautic – Bootsfahrschule &amp; Vermietung</h3>
                      <p className="text-xs text-slate-600 mb-3">
                        Vollständige maritime Startseite für Motorboot Kat. A &amp; Segeln Kat. D, Flottencharter, Skipper-Team und Magic-Link Login.
                      </p>

                      {/* Mini Preview Box */}
                      <div className="p-3 bg-sky-50/60 rounded-xl border border-sky-100 mb-3 space-y-2">
                        <div className="flex items-center justify-between text-xs">
                          <span className="text-slate-500">Logo:</span>
                          <span className="font-semibold text-sky-900 font-mono text-[11px]">SVG Vektor (Steuerrad &amp; Rumpf)</span>
                        </div>
                        <div className="flex items-center justify-between text-xs">
                          <span className="text-slate-500">Farben:</span>
                          <div className="flex items-center gap-1.5">
                            <span className="w-3.5 h-3.5 rounded-full bg-[#0284c7] inline-block border border-white shadow-xs"></span>
                            <span className="w-3.5 h-3.5 rounded-full bg-[#0369a1] inline-block border border-white shadow-xs"></span>
                            <span className="w-3.5 h-3.5 rounded-full bg-[#f0f9ff] inline-block border border-slate-300 shadow-xs"></span>
                            <span className="text-[11px] font-mono text-slate-600">Ocean Blue</span>
                          </div>
                        </div>
                        <div className="text-xs text-slate-700">
                          <span className="text-slate-500">Vorkonfigurierte Blöcke:</span>
                          <div className="mt-1 flex flex-wrap gap-1">
                            <span className="px-1.5 py-0.5 bg-white border border-sky-200 rounded text-[10px] text-sky-800">1. Hero Aufhänger</span>
                            <span className="px-1.5 py-0.5 bg-white border border-sky-200 rounded text-[10px] text-sky-800">2. 4× Kurse &amp; Charter</span>
                            <span className="px-1.5 py-0.5 bg-white border border-sky-200 rounded text-[10px] text-sky-800">3. 2-Spalten Flotte</span>
                            <span className="px-1.5 py-0.5 bg-white border border-sky-200 rounded text-[10px] text-sky-800">4. CTA Magic-Login</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleApplyCmsTemplate('nautic')}
                      className={`w-full py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer ${
                        cmsActiveTemplate === 'nautic'
                          ? 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100'
                          : 'bg-sky-600 hover:bg-sky-700 text-white shadow'
                      }`}
                    >
                      <Check className="w-4 h-4" />
                      <span>{cmsActiveTemplate === 'nautic' ? 'Nautic-Vorlage erneut laden / zurücksetzen' : 'Nautic-Vorlage anwenden & im Mini-CMS bearbeiten'}</span>
                    </button>
                  </div>

                  {/* Template 2: STANDARD CMS */}
                  <div className={`bg-white rounded-2xl p-5 border-2 transition shadow-sm relative overflow-hidden flex flex-col justify-between ${
                    cmsActiveTemplate === 'standard' ? 'border-sky-500 shadow-md ring-2 ring-sky-100' : 'border-slate-200 hover:border-slate-300'
                  }`}>
                    {cmsActiveTemplate === 'standard' && (
                      <div className="absolute top-0 right-0 bg-sky-600 text-white text-[11px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
                        ● Aktiv im System
                      </div>
                    )}
                    <div>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="px-2.5 py-0.5 bg-slate-100 text-slate-800 rounded-full text-xs font-bold">
                          Klassisch
                        </span>
                      </div>
                      <h3 className="text-lg font-bold text-slate-900 mb-1">Standard CMS &amp; Magic-Link Portal</h3>
                      <p className="text-xs text-slate-600 mb-3">
                        Neutrales, universelles Design für Firmenportale mit dezenten Grautönen, Standard-Kacheln und Magic-Code Login.
                      </p>

                      <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 mb-3 space-y-2 text-xs">
                        <div className="flex items-center justify-between">
                          <span className="text-slate-500">Farben:</span>
                          <div className="flex items-center gap-1.5">
                            <span className="w-3.5 h-3.5 rounded-full bg-[#0d6efd] inline-block border border-white"></span>
                            <span className="w-3.5 h-3.5 rounded-full bg-[#6c757d] inline-block border border-white"></span>
                            <span className="w-3.5 h-3.5 rounded-full bg-[#f8fafc] inline-block border border-slate-300"></span>
                            <span className="text-[11px] font-mono text-slate-600">Standard Slate</span>
                          </div>
                        </div>
                        <div className="text-slate-700">
                          <span className="text-slate-500">Blöcke:</span> Hero Sektion + 3 Vorteils-Kacheln.
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleApplyCmsTemplate('standard')}
                      className={`w-full py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer ${
                        cmsActiveTemplate === 'standard'
                          ? 'bg-slate-100 text-slate-700 border border-slate-200'
                          : 'bg-slate-800 hover:bg-slate-900 text-white shadow'
                      }`}
                    >
                      <Check className="w-4 h-4" />
                      <span>{cmsActiveTemplate === 'standard' ? 'Standard-Vorlage aktiv' : 'Standard-Vorlage laden'}</span>
                    </button>
                  </div>

                  {/* Template 3: CORPORATE */}
                  <div className={`bg-white rounded-2xl p-5 border-2 transition shadow-sm relative overflow-hidden flex flex-col justify-between ${
                    cmsActiveTemplate === 'corporate' ? 'border-sky-500 shadow-md ring-2 ring-sky-100' : 'border-slate-200 hover:border-slate-300'
                  }`}>
                    {cmsActiveTemplate === 'corporate' && (
                      <div className="absolute top-0 right-0 bg-sky-600 text-white text-[11px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
                        ● Aktiv im System
                      </div>
                    )}
                    <div>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="px-2.5 py-0.5 bg-slate-900 text-white rounded-full text-xs font-bold">
                          Enterprise
                        </span>
                      </div>
                      <h3 className="text-lg font-bold text-slate-900 mb-1">Corporate Business &amp; Partner</h3>
                      <p className="text-xs text-slate-600 mb-3">
                        Dunkles Schieferblau mit goldenen Akzenten für exklusive Partner- und Kundenbereiche.
                      </p>

                      <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 mb-3 space-y-2 text-xs">
                        <div className="flex items-center justify-between">
                          <span className="text-slate-500">Farben:</span>
                          <div className="flex items-center gap-1.5">
                            <span className="w-3.5 h-3.5 rounded-full bg-[#1e293b] inline-block border border-white"></span>
                            <span className="w-3.5 h-3.5 rounded-full bg-[#f59e0b] inline-block border border-white"></span>
                            <span className="text-[11px] font-mono text-slate-600">Navy Gold</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleApplyCmsTemplate('corporate')}
                      className={`w-full py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer ${
                        cmsActiveTemplate === 'corporate'
                          ? 'bg-slate-100 text-slate-700 border border-slate-200'
                          : 'bg-slate-800 hover:bg-slate-900 text-white shadow'
                      }`}
                    >
                      <Check className="w-4 h-4" />
                      <span>{cmsActiveTemplate === 'corporate' ? 'Corporate-Vorlage aktiv' : 'Corporate-Vorlage laden'}</span>
                    </button>
                  </div>

                  {/* Template 4: MINIMAL */}
                  <div className={`bg-white rounded-2xl p-5 border-2 transition shadow-sm relative overflow-hidden flex flex-col justify-between ${
                    cmsActiveTemplate === 'minimal' ? 'border-sky-500 shadow-md ring-2 ring-sky-100' : 'border-slate-200 hover:border-slate-300'
                  }`}>
                    {cmsActiveTemplate === 'minimal' && (
                      <div className="absolute top-0 right-0 bg-sky-600 text-white text-[11px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
                        ● Aktiv im System
                      </div>
                    )}
                    <div>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="px-2.5 py-0.5 bg-slate-200 text-slate-700 rounded-full text-xs font-bold">
                          Fokus Login
                        </span>
                      </div>
                      <h3 className="text-lg font-bold text-slate-900 mb-1">Minimal Clean Login</h3>
                      <p className="text-xs text-slate-600 mb-3">
                        Reduziert auf das Wesentliche: Keine Marketingblöcke, direkter Einstieg ins Magic-Link Feld.
                      </p>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleApplyCmsTemplate('minimal')}
                      className={`w-full py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer ${
                        cmsActiveTemplate === 'minimal'
                          ? 'bg-slate-100 text-slate-700 border border-slate-200'
                          : 'bg-slate-800 hover:bg-slate-900 text-white shadow'
                      }`}
                    >
                      <Check className="w-4 h-4" />
                      <span>{cmsActiveTemplate === 'minimal' ? 'Minimal-Vorlage aktiv' : 'Minimal-Vorlage laden'}</span>
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* SUBTAB 2: INHALTSBLÖCKE BEARBEITEN                            */}
            {/* ============================================================== */}
            {cmsSubTab === 'blocks' && (
              <div className="space-y-4">
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <h3 className="font-bold text-slate-900 text-base">Inhaltsblöcke der aktiven Vorlage</h3>
                    <p className="text-xs text-slate-500">
                      Hier bearbeitest du die Texte, Überschriften und Kacheln, die durch die Vorlage auf der Startseite platziert werden.
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => {
                      const newId = Date.now();
                      const newB: CmsBlock = {
                        id: newId,
                        type: 'text',
                        title: 'Neuer Abschnitt',
                        subtitle: 'Untertitel für diesen Block',
                        content: 'Füge hier beliebige Inhalte, Fahrstunden-Hinweise oder Kontaktinfos ein.',
                        sort_order: (cmsBlocks.length + 1) * 10,
                        is_visible: true
                      };
                      setCmsBlocks([...cmsBlocks, newB]);
                      setCmsEditingBlock(newB);
                    }}
                    className="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-sm transition self-start sm:self-auto cursor-pointer"
                  >
                    <Plus className="w-4 h-4" />
                    <span>Neuen Block hinzufügen</span>
                  </button>
                </div>

                {/* Block Editor Modal / Inline Formular */}
                {cmsEditingBlock && (
                  <div className="bg-amber-50/70 border-2 border-amber-300 rounded-2xl p-5 shadow-md space-y-3 animate-in fade-in">
                    <div className="flex items-center justify-between border-b border-amber-200/80 pb-2">
                      <div className="flex items-center gap-2">
                        <span className="px-2 py-0.5 bg-amber-200 text-amber-900 rounded font-mono text-[10px] uppercase font-bold">
                          {cmsEditingBlock.type}
                        </span>
                        <h4 className="font-bold text-slate-900 text-sm">
                          Block #{cmsEditingBlock.id} bearbeiten: {cmsEditingBlock.title}
                        </h4>
                      </div>
                      <button
                        type="button"
                        onClick={() => setCmsEditingBlock(null)}
                        className="text-slate-400 hover:text-slate-700 cursor-pointer"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">Titel / Überschrift</label>
                        <input
                          type="text"
                          value={cmsEditingBlock.title}
                          onChange={e => setCmsEditingBlock({ ...cmsEditingBlock, title: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">Untertitel / Tagline</label>
                        <input
                          type="text"
                          value={cmsEditingBlock.subtitle}
                          onChange={e => setCmsEditingBlock({ ...cmsEditingBlock, subtitle: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Inhalt / Text</label>
                      <textarea
                        rows={3}
                        value={cmsEditingBlock.content}
                        onChange={e => setCmsEditingBlock({ ...cmsEditingBlock, content: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
                      />
                    </div>

                    <div className="flex items-center justify-between pt-2 border-t border-amber-200/80">
                      <div className="flex items-center gap-2">
                        <label className="text-xs text-slate-600 font-medium">Reihenfolge:</label>
                        <input
                          type="number"
                          value={cmsEditingBlock.sort_order}
                          onChange={e => setCmsEditingBlock({ ...cmsEditingBlock, sort_order: parseInt(e.target.value) || 10 })}
                          className="w-16 px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-mono"
                        />
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => setCmsEditingBlock(null)}
                          className="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold cursor-pointer"
                        >
                          Abbrechen
                        </button>
                        <button
                          type="button"
                          onClick={() => handleUpdateBlock(cmsEditingBlock)}
                          className="px-4 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1"
                        >
                          <Check className="w-3.5 h-3.5" />
                          <span>Änderungen speichern</span>
                        </button>
                      </div>
                    </div>
                  </div>
                )}

                {/* Liste aller Blöcke */}
                <div className="space-y-3">
                  {cmsBlocks.map((b, idx) => (
                    <div
                      key={b.id}
                      className={`bg-white rounded-2xl p-4 border transition flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-xs ${
                        b.is_visible ? 'border-slate-200' : 'border-slate-200 bg-slate-50/60 opacity-60'
                      }`}
                    >
                      <div className="flex items-start gap-3">
                        <div className="w-8 h-8 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 font-bold text-xs flex items-center justify-center shrink-0">
                          {idx + 1}
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-slate-900 text-sm">{b.title}</span>
                            <span className="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-mono font-semibold uppercase">
                              {b.type}
                            </span>
                            {!b.is_visible && (
                              <span className="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-semibold">
                                Versteckt
                              </span>
                            )}
                          </div>
                          {b.subtitle && (
                            <p className="text-xs text-slate-500 mt-0.5">{b.subtitle}</p>
                          )}
                          {b.content && (
                            <p className="text-[11px] text-slate-600 mt-1 line-clamp-1 italic">
                              "{b.content}"
                            </p>
                          )}
                        </div>
                      </div>

                      <div className="flex items-center gap-2 self-end md:self-auto shrink-0">
                        <button
                          type="button"
                          onClick={() => handleToggleBlockVisible(b.id)}
                          className={`px-2.5 py-1.5 rounded-xl text-xs font-semibold border transition cursor-pointer ${
                            b.is_visible ? 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                          }`}
                        >
                          {b.is_visible ? 'Ausblenden' : 'Einblenden'}
                        </button>

                        <button
                          type="button"
                          onClick={() => setCmsEditingBlock(b)}
                          className="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-xl text-xs font-semibold transition cursor-pointer"
                        >
                          Bearbeiten
                        </button>

                        <button
                          type="button"
                          onClick={() => handleDeleteBlock(b.id)}
                          className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition cursor-pointer"
                          title="Block entfernen"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* SUBTAB 3: DESIGN, FARBEN & LOGO                               */}
            {/* ============================================================== */}
            {cmsSubTab === 'design' && (
              <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <div>
                  <h3 className="font-bold text-slate-900 text-base">Design, Akzentfarben &amp; Markenauftritt</h3>
                  <p className="text-xs text-slate-500">
                    Passe das Farblayout und das Branding der Startseite an. Änderungen werden sofort in der Vorschau sichtbar.
                  </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div className="space-y-4">
                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Seitentitel (H1 &amp; Browser-Tab)</label>
                      <input
                        type="text"
                        value={cmsPageTitle}
                        onChange={e => setCmsPageTitle(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-slate-700 mb-1">Untertitel / Tagline</label>
                      <input
                        type="text"
                        value={cmsPageSubtitle}
                        onChange={e => setCmsPageSubtitle(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900"
                      />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">Primärfarbe (Buttons, Links)</label>
                        <div className="flex items-center gap-2">
                          <input
                            type="color"
                            value={cmsPrimaryColor}
                            onChange={e => setCmsPrimaryColor(e.target.value)}
                            className="w-9 h-9 rounded-lg border border-slate-200 cursor-pointer p-0.5"
                          />
                          <input
                            type="text"
                            value={cmsPrimaryColor}
                            onChange={e => setCmsPrimaryColor(e.target.value)}
                            className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-medium"
                          />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">Hintergrundfarbe</label>
                        <div className="flex items-center gap-2">
                          <input
                            type="color"
                            value={cmsBgColor}
                            onChange={e => setCmsBgColor(e.target.value)}
                            className="w-9 h-9 rounded-lg border border-slate-200 cursor-pointer p-0.5"
                          />
                          <input
                            type="text"
                            value={cmsBgColor}
                            onChange={e => setCmsBgColor(e.target.value)}
                            className="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-medium"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Logo-Vorschau */}
                  <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col justify-between">
                    <div>
                      <span className="text-xs uppercase font-semibold text-slate-400 block mb-2">Hinterlegtes Logo</span>
                      <div className="p-4 bg-white rounded-xl border border-slate-200 flex items-center justify-center">
                        <svg viewBox="0 0 280 64" className="w-56 h-auto drop-shadow-sm" role="img" aria-label="Nautic Logo">
                          <g transform="translate(4, 4)">
                            <circle cx="28" cy="28" r="27" fill="#0284c7" />
                            <circle cx="28" cy="28" r="23" fill="none" stroke="#ffffff" strokeWidth="1.5" strokeOpacity="0.3" strokeDasharray="3 3" />
                            <path d="M 28 8 L 30 20 L 28 17 L 26 20 Z" fill="#38bdf8" />
                            <path d="M 28 15 L 40 37 C 36 40, 20 40, 16 37 Z" fill="#ffffff" />
                            <path d="M 12 41 C 18 38, 22 43, 28 40 C 34 37, 38 42, 44 39" fill="none" stroke="#38bdf8" strokeWidth="2.5" strokeLinecap="round" />
                          </g>
                          <g transform="translate(72, 0)">
                            <text x="0" y="35" fontFamily="system-ui, sans-serif" fontSize="28" fontWeight="900" letterSpacing="3" fill="#0c4a6e">
                              NAUTIC
                            </text>
                            <rect x="1" y="42" width="180" height="2" rx="1" fill="#0284c7" />
                            <text x="2" y="55" fontFamily="system-ui, sans-serif" fontSize="9" fontWeight="700" letterSpacing="2.2" fill="#0284c7">
                              FAHRSCHULE &amp; VERMIETUNG
                            </text>
                          </g>
                        </svg>
                      </div>
                      <p className="text-[11px] text-slate-500 mt-2">
                        Wird über die Vorlage aus <code>public/assets/nautic-logo.svg</code> bezogen und skaliert gestochen scharf.
                      </p>
                    </div>

                    <div className="pt-3">
                      <button
                        type="button"
                        onClick={() => {
                          setCmsToast('Design-Einstellungen gespeichert.');
                          setTimeout(() => setCmsToast(null), 3000);
                        }}
                        className="w-full py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer"
                      >
                        Design-Änderungen sichern
                      </button>
                    </div>
                  </div>
                </div>

                {/* Nautic Login-Maske: Testmodus Schalter */}
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-5">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="p-1.5 bg-amber-50 text-amber-700 rounded-lg">
                        <Key className="w-4 h-4" />
                      </span>
                      <h4 className="text-sm font-bold text-slate-900">
                        Nautic Login-Maske: Testmodus (Schnell-Login ein / aus)
                      </h4>
                    </div>
                    <p className="text-xs text-slate-500 mt-1">
                      {nauticLoginTestMode
                        ? 'Testmodus ist AKTIV: Auf der Nautic-Logonseite werden Schnell-Login-Buttons für Coach, Admin und Schüler angezeigt.'
                        : 'Testmodus ist AUS (Standard): Loginseite ist maximal aufgeräumt – nur E-Mail-Adresse und danach 10 Magic-Code Einzelboxen.'}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => {
                      const next = !nauticLoginTestMode;
                      setNauticLoginTestMode(next);
                      setCmsToast(
                        next
                          ? 'Testmodus aktiviert: Schnell-Login Buttons sind jetzt im Nautic-Login sichtbar.'
                          : 'Testmodus deaktiviert: Nautic-Login ist jetzt vollkommen aufgeräumt.'
                      );
                      setTimeout(() => setCmsToast(null), 3000);
                    }}
                    className={`px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-xs ${
                      nauticLoginTestMode
                        ? 'bg-amber-500 text-white hover:bg-amber-600'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                    }`}
                  >
                    <span>{nauticLoginTestMode ? 'Testmodus: EIN' : 'Testmodus: AUS'}</span>
                  </button>
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* SUBTAB 4: LIVE-VORSCHAU DER STARTSEITE                        */}
            {/* ============================================================== */}
            {cmsSubTab === 'preview' && (
              <div className="rounded-3xl border border-slate-200 overflow-hidden shadow-lg bg-white" style={{ backgroundColor: cmsBgColor }}>
                {/* Browser Mockup Header */}
                <div className="bg-slate-900 px-4 py-2.5 flex items-center justify-between text-xs text-slate-300 border-b border-slate-800">
                  <div className="flex items-center gap-2">
                    <div className="flex gap-1.5">
                      <span className="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                      <span className="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                      <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                    </div>
                    <span className="font-mono text-[11px] text-slate-400 pl-2">https://bootschule-see.ch/ (Live-Startseite)</span>
                  </div>
                  <span className="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-sky-400 font-mono">
                    Template: {cmsActiveTemplate.toUpperCase()}
                  </span>
                </div>

                {/* Header Bar */}
                <header className="px-6 py-4 bg-white/90 backdrop-blur-md border-b border-slate-200/80 flex items-center justify-between sticky top-0 z-10">
                  <div className="flex items-center gap-2">
                    <svg viewBox="0 0 280 64" className="w-48 h-auto" role="img">
                      <g transform="translate(4, 4)">
                        <circle cx="28" cy="28" r="27" fill={cmsPrimaryColor} />
                        <path d="M 28 15 L 40 37 C 36 40, 20 40, 16 37 Z" fill="#ffffff" />
                        <path d="M 12 41 C 18 38, 22 43, 28 40 C 34 37, 38 42, 44 39" fill="none" stroke="#38bdf8" strokeWidth="2.5" strokeLinecap="round" />
                      </g>
                      <g transform="translate(72, 0)">
                        <text x="0" y="35" fontFamily="system-ui, sans-serif" fontSize="28" fontWeight="900" letterSpacing="3" fill="#0c4a6e">
                          NAUTIC
                        </text>
                        <rect x="1" y="42" width="180" height="2" rx="1" fill={cmsPrimaryColor} />
                        <text x="2" y="55" fontFamily="system-ui, sans-serif" fontSize="9" fontWeight="700" letterSpacing="2.2" fill={cmsPrimaryColor}>
                          FAHRSCHULE &amp; VERMIETUNG
                        </text>
                      </g>
                    </svg>
                  </div>

                  <a
                    href="#preview-login"
                    className="px-4 py-2 rounded-xl text-xs font-bold text-white shadow-xs transition"
                    style={{ backgroundColor: cmsPrimaryColor }}
                  >
                    Login / Magic Link
                  </a>
                </header>

                {/* Render Blöcke */}
                <div className="p-6 md:p-12 space-y-12 max-w-5xl mx-auto">
                  {cmsBlocks.filter(b => b.is_visible).map(block => (
                    <div key={block.id} className="animate-in fade-in">
                      {block.type === 'hero' && (
                        <div className="text-center space-y-4 max-w-3xl mx-auto py-6">
                          <h1 className="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {block.title}
                          </h1>
                          <p className="text-base text-slate-600 font-medium">{block.subtitle}</p>
                          <p className="text-xs text-slate-500 leading-relaxed">{block.content}</p>
                          {block.extra?.btn_text && (
                            <div className="pt-2">
                              <a
                                href="#preview-login"
                                className="inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition"
                                style={{ backgroundColor: cmsPrimaryColor }}
                              >
                                <span>{block.extra.btn_text}</span>
                                <ChevronRight className="w-4 h-4" />
                              </a>
                            </div>
                          )}
                        </div>
                      )}

                      {block.type === 'features' && (
                        <div className="space-y-6">
                          <div className="text-center max-w-xl mx-auto">
                            <h2 className="text-2xl font-bold text-slate-900">{block.title}</h2>
                            <p className="text-xs text-slate-500 mt-1">{block.subtitle}</p>
                          </div>

                          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {(block.extra?.items || []).map((item: any, i: number) => (
                              <div key={i} className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition">
                                <div
                                  className="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-xs mb-3 shadow-xs"
                                  style={{ backgroundColor: cmsPrimaryColor }}
                                >
                                  {i + 1}
                                </div>
                                <h4 className="font-bold text-slate-900 text-sm mb-1">{item.title}</h4>
                                <p className="text-xs text-slate-600 leading-relaxed">{item.text}</p>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}

                      {block.type === 'two_column' && (
                        <div className="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-1">{block.title}</h3>
                            <p className="text-xs text-slate-500 mb-3">{block.subtitle}</p>
                            <p className="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{block.content}</p>
                          </div>
                          <div className="p-4 bg-sky-50/60 rounded-2xl border border-sky-100 flex flex-col justify-center">
                            <h4 className="font-bold text-sky-950 text-sm mb-1">{block.extra?.col2_title || 'Information'}</h4>
                            <p className="text-xs text-sky-900 leading-relaxed whitespace-pre-line">{block.extra?.col2_content}</p>
                          </div>
                        </div>
                      )}

                      {block.type === 'cta' && (
                        <div
                          className="rounded-3xl p-8 text-center text-white shadow-xl relative overflow-hidden"
                          style={{
                            background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 60%, #072a45 100%)'
                          }}
                        >
                          <h3 className="text-2xl font-bold mb-2">{block.title}</h3>
                          <p className="text-xs text-sky-100 max-w-xl mx-auto mb-4">{block.subtitle}</p>
                          <a
                            href="#preview-login"
                            className="inline-flex items-center gap-2 px-6 py-2.5 bg-white text-sky-800 rounded-xl text-xs font-bold shadow hover:bg-slate-50 transition"
                          >
                            <span>{block.extra?.btn_text || 'Jetzt loslegen'}</span>
                          </a>
                        </div>
                      )}
                    </div>
                  ))}

                  {/* Magic Login Preview Card */}
                  <div id="preview-login" className="max-w-md mx-auto bg-white p-6 rounded-2xl border border-slate-200 shadow-md space-y-4">
                    <div className="text-center">
                      <h3 className="text-base font-bold text-slate-900">Magic-Link Anmeldung</h3>
                      <p className="text-xs text-slate-500 mt-0.5">Gib deine E-Mail-Adresse ein, um dich einzuloggen:</p>
                    </div>

                    <div className="space-y-3">
                      <div>
                        <label className="block text-[11px] font-semibold text-slate-700 mb-1">E-Mail-Adresse</label>
                        <input
                          type="email"
                          disabled
                          placeholder="deine-adresse@domain.ch"
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs"
                        />
                      </div>
                      <button
                        type="button"
                        disabled
                        className="w-full py-2 text-white font-bold text-xs rounded-xl shadow-xs"
                        style={{ backgroundColor: cmsPrimaryColor }}
                      >
                        Per Magic-Link anmelden &rarr;
                      </button>
                    </div>

                    <div className="pt-2 text-center border-t border-slate-100">
                      <button
                        type="button"
                        onClick={() => setActiveTab('login')}
                        className="text-xs font-semibold text-sky-700 hover:underline cursor-pointer"
                      >
                        Noch kein Konto? Jetzt Nautic-Account anfordern &rarr;
                      </button>
                    </div>
                  </div>
                </div>

                <footer className="px-6 py-6 border-t border-slate-200/80 text-center text-xs text-slate-400">
                  © 2026 {cmsPageTitle} – Alle Rechte vorbehalten.
                </footer>
              </div>
            )}
          </div>
        )}

        {/* ============================================================== */}
        {/* TAB 6: BACKEND ARCHITEKTUR & MIGRATIONEN                      */}
        {/* ============================================================== */}
        {activeTab === 'architecture' && (
          <div className="space-y-6">
            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
              <h2 className="text-xl font-bold text-slate-900 mb-2">PHP 8 Core Architektur &amp; Datenmodell</h2>
              <p className="text-sm text-slate-600">
                Übersicht aller erstellten Tabellen, SQL-Migrationen und Service-Klassen im Modul <code className="text-sky-700 font-mono bg-sky-50 px-1.5 py-0.5 rounded">boating_school</code>.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div className="flex items-center gap-2 text-sky-700 font-bold text-sm">
                  <FileText className="w-4 h-4" />
                  Migration 012: Payroll &amp; Reports
                </div>
                <div className="text-xs text-slate-600 space-y-2">
                  <p><strong>bs_instructors:</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>pay_rate_type ENUM('per_hour','per_appointment')</li>
                    <li>pay_rate_amount DECIMAL(10,2)</li>
                    <li>payroll_external_code VARCHAR(64)</li>
                  </ul>
                  <p className="mt-2"><strong>bs_appointments:</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>instructor_payout_status ENUM('open','exported','paid')</li>
                    <li>instructor_payout_amount DECIMAL(10,2)</li>
                    <li>instructor_payout_note VARCHAR(255)</li>
                  </ul>
                </div>
              </div>

              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div className="flex items-center gap-2 text-emerald-700 font-bold text-sm">
                  <FileText className="w-4 h-4" />
                  Migration 013: Magic-Links
                </div>
                <div className="text-xs text-slate-600 space-y-2">
                  <p><strong>bs_customer_magic_links:</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>id INT UNSIGNED PRIMARY KEY</li>
                    <li>customer_id INT UNSIGNED FK</li>
                    <li>token_hash CHAR(64) (SHA-256)</li>
                    <li>purpose VARCHAR(32)</li>
                    <li>expires_at DATETIME</li>
                    <li>used_at DATETIME NULL</li>
                  </ul>
                  <p className="text-slate-500 text-[11px] mt-2">
                    E-Mail Platzhalter: <code className="bg-slate-100 px-1">{'{{magic_link_my_appointments}}'}</code>
                  </p>
                </div>
              </div>

              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div className="flex items-center gap-2 text-indigo-700 font-bold text-sm">
                  <FileText className="w-4 h-4" />
                  Migration 014: Coach-Management
                </div>
                <div className="text-xs text-slate-600 space-y-2">
                  <p><strong>bs_instructor_details (HR):</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>ahv_number VARCHAR(50) (CH 756)</li>
                    <li>bank_iban, bank_name, bank_city</li>
                    <li>hourly_rate DECIMAL(10,2)</li>
                  </ul>
                  <p className="mt-2"><strong>bs_coach_availabilities:</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>type: vacation, sick, military, unavailable</li>
                  </ul>
                  <p className="mt-2"><strong>bs_coach_working_hours:</strong></p>
                  <ul className="list-disc list-inside text-slate-500 font-mono text-[11px] space-y-0.5">
                    <li>total_hours, total_amount, category</li>
                  </ul>
                </div>
              </div>
            </div>

            {/* PHP Service Classes */}
            <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <h3 className="font-bold text-slate-900 text-sm mb-4">Implementierte PHP 8 Service-Klassen</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <div className="font-bold text-slate-900 font-mono">InstructorPayoutService</div>
                  <div className="text-slate-500 mt-1">
                    Honorarberechnung, Monatsabschluss, CSV-Export, Bulk-Neuberechnung.
                  </div>
                </div>

                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <div className="font-bold text-slate-900 font-mono">BoatingMagicLinkService</div>
                  <div className="text-slate-500 mt-1">
                    Kryptografische Tokens (SHA-256), Ablaufprüfung, Schüler-Stornierungen.
                  </div>
                </div>

                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <div className="font-bold text-slate-900 font-mono">CoachService</div>
                  <div className="text-slate-500 mt-1">
                    HR-Daten (AHV, IBAN), Absenzen, Schweizer Timesheets &amp; Konfliktprüfung.
                  </div>
                </div>

                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <div className="font-bold text-slate-900 font-mono">BoatingNotificationService</div>
                  <div className="text-slate-500 mt-1">
                    E-Mail Platzhalter mit Dual-Syntax <code className="bg-slate-200 px-1">{'{tag}'}</code> &amp; <code className="bg-slate-200 px-1">{'{{tag}}'}</code>.
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </main>

      {/* Modal: Termin stornieren (Schüler) */}
      {cancelModalAppointment && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-lg flex items-center gap-2">
                <AlertTriangle className="w-5 h-5 text-rose-600" />
                Fahrstunde stornieren
              </h3>
              <button
                onClick={() => setCancelModalAppointment(null)}
                className="text-slate-400 hover:text-slate-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="text-sm text-slate-600 space-y-2">
              <p>Möchtest du diese Fahrstunde wirklich stornieren?</p>
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                <div><strong>Leistung:</strong> {cancelModalAppointment.service_name}</div>
                <div><strong>Datum:</strong> {cancelModalAppointment.date} ({cancelModalAppointment.start_time.slice(0, 5)} Uhr)</div>
                <div><strong>Fahrcoach:</strong> {cancelModalAppointment.instructor_name}</div>
                <div><strong>Boot:</strong> {cancelModalAppointment.boat_name}</div>
              </div>
            </div>

            <div>
              <label className="text-xs font-semibold text-slate-700 block mb-1">
                Grund der Stornierung (optional):
              </label>
              <textarea
                value={cancelReason}
                onChange={e => setCancelReason(e.target.value)}
                rows={2}
                placeholder="z. B. Unwetter, Krankheit oder Terminkonflikt..."
                className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-rose-500"
              />
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t">
              <button
                onClick={() => setCancelModalAppointment(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition"
              >
                Abbrechen
              </button>
              <button
                onClick={handleCancelAppointmentByStudent}
                className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow transition"
              >
                Definitiv stornieren
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Absenz erfassen (Fahrlehrer - nur stundenweise von–bis) */}
      {showAddAbsence && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-lg flex items-center gap-2">
                <Clock className="w-5 h-5 text-amber-600" />
                Absenz / Sperrzeit erfassen
              </h3>
              <button onClick={() => setShowAddAbsence(false)} className="text-slate-400 hover:text-slate-600">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddAbsenceSubmit} className="space-y-4 text-xs">
              <div>
                <label className="font-semibold text-slate-700 block mb-1">Datum:</label>
                <input
                  type="date"
                  value={newAbsenceDate}
                  onChange={e => setNewAbsenceDate(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500"
                  required
                />
              </div>

              {/* Stundenweises Zeitfenster: von – bis Uhrzeit */}
              <div className="bg-amber-50/60 p-3.5 rounded-xl border border-amber-200 space-y-2.5">
                <div className="flex items-center justify-between">
                  <label className="font-semibold text-amber-900 flex items-center gap-1.5">
                    <Clock className="w-3.5 h-3.5 text-amber-700" />
                    Sperrzeit-Fenster (von – bis Uhrzeit) <span className="text-rose-500">*</span>
                  </label>
                  <span className="text-[10px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded font-bold">Stundenweise</span>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="text-[11px] text-slate-600 block mb-0.5 font-medium">Von Uhrzeit (Start):</label>
                    <input
                      type="time"
                      value={newAbsenceStartTime}
                      onChange={e => setNewAbsenceStartTime(e.target.value)}
                      className="w-full px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-amber-500"
                      required
                    />
                  </div>
                  <div>
                    <label className="text-[11px] text-slate-600 block mb-0.5 font-medium">Bis Uhrzeit (Ende):</label>
                    <input
                      type="time"
                      value={newAbsenceEndTime}
                      onChange={e => setNewAbsenceEndTime(e.target.value)}
                      className="w-full px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-amber-500"
                      required
                    />
                  </div>
                </div>

                {/* Schnellauswahl Chips */}
                <div className="flex flex-wrap gap-1 pt-1 border-t border-amber-200/60">
                  <span className="text-[10px] text-amber-800 self-center">Schnellwahl:</span>
                  {[
                    { label: 'Vormittag (08–12)', s: '08:00', e: '12:00' },
                    { label: 'Nachmittag (13–17)', s: '13:00', e: '17:00' },
                    { label: 'Abend (17–20)', s: '17:00', e: '20:00' },
                    { label: 'Ganzer Tag (08–18)', s: '08:00', e: '18:00' }
                  ].map((preset, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => {
                        setNewAbsenceStartTime(preset.s);
                        setNewAbsenceEndTime(preset.e);
                      }}
                      className="px-1.5 py-0.5 bg-white hover:bg-amber-100 border border-amber-200 text-amber-900 rounded text-[10px] font-medium transition"
                    >
                      {preset.label}
                    </button>
                  ))}
                </div>

                <div className="text-[11px] text-amber-900 font-medium">
                  Blockierte Zeit: <strong>{absenceDurationHours} Stunden</strong> ({newAbsenceStartTime} bis {newAbsenceEndTime} Uhr)
                </div>
              </div>

              <div>
                <label className="font-semibold text-slate-700 block mb-1">Art der Abwesenheit:</label>
                <select
                  value={newAbsenceType}
                  onChange={e => setNewAbsenceType(e.target.value as any)}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500"
                >
                  <option value="unavailable">Sperrzeit / Privat</option>
                  <option value="vacation">Ferien / Urlaub</option>
                  <option value="sick">Krankheit / Unfall</option>
                  <option value="military">Militär / Zivildienst</option>
                </select>
              </div>

              <div>
                <label className="font-semibold text-slate-700 block mb-1">Bemerkung / Grund:</label>
                <input
                  type="text"
                  value={newAbsenceNote}
                  onChange={e => setNewAbsenceNote(e.target.value)}
                  placeholder="z. B. Arzttermin, private Verpflichtung oder Ferien"
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2 border-t">
                <button
                  type="button"
                  onClick={() => setShowAddAbsence(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl"
                >
                  Abbrechen
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow"
                >
                  Absenz speichern
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Arbeitszeit erfassen (h & min frei wählbar mit Betreff) */}
      {showAddWorkingHour && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-lg flex items-center gap-2">
                <Clock className="w-5 h-5 text-sky-600" />
                Arbeitszeit erfassen (Timesheet)
              </h3>
              <button
                onClick={() => setShowAddWorkingHour(false)}
                className="text-slate-400 hover:text-slate-600 transition"
                title="Schließen"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddWorkingHourSubmit} className="space-y-4 text-xs">
              {/* Betreff / Tätigkeit */}
              <div>
                <label className="font-semibold text-slate-700 block mb-1">
                  Betreff / Tätigkeit <span className="text-rose-500">*</span>:
                </label>
                <input
                  type="text"
                  value={whSubject}
                  onChange={e => setWhSubject(e.target.value)}
                  placeholder="z. B. Bootsprüfung Vorbereitung & Tanken, Bootswartung Boesch 620..."
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none"
                  required
                />
                {/* Schnellvorschläge für Betreff */}
                <div className="flex flex-wrap gap-1.5 mt-2">
                  <span className="text-[10px] text-slate-400 self-center">Vorschläge:</span>
                  {[
                    { label: 'Bootswartung & Tanken', cat: 'maintenance' },
                    { label: 'Führerprüfung Begleitung', cat: 'exam' },
                    { label: 'Theorieunterricht Kat. A', cat: 'lesson' },
                    { label: 'Bootsbereitstellung & Check', cat: 'maintenance' },
                    { label: 'Administration & Planung', cat: 'admin' },
                  ].map((sugg, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => {
                        setWhSubject(sugg.label);
                        setWhCategory(sugg.cat as any);
                      }}
                      className="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-[10px] font-medium transition"
                    >
                      + {sugg.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Datum & Kategorie */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="font-semibold text-slate-700 block mb-1">Datum:</label>
                  <input
                    type="date"
                    value={whDate}
                    onChange={e => setWhDate(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-sky-500"
                    required
                  />
                </div>
                <div>
                  <label className="font-semibold text-slate-700 block mb-1">Kategorie:</label>
                  <select
                    value={whCategory}
                    onChange={e => setWhCategory(e.target.value as any)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-sky-500"
                  >
                    <option value="maintenance">Bootswartung &amp; Tanken</option>
                    <option value="lesson">Fahrstunde (manuell)</option>
                    <option value="exam">Führerprüfung</option>
                    <option value="admin">Administration &amp; Planung</option>
                    <option value="other">Sonstiges</option>
                  </select>
                </div>
              </div>

              {/* Dauer: h & min frei wählbar */}
              <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-2.5">
                <div className="flex items-center justify-between">
                  <label className="font-semibold text-slate-800 flex items-center gap-1.5">
                    <Clock className="w-3.5 h-3.5 text-sky-600" />
                    Arbeitsdauer frei wählen (h &amp; min):
                  </label>
                  {/* Schnellauswahl Chips */}
                  <div className="flex gap-1">
                    {[
                      { label: '30m', h: 0, m: 30 },
                      { label: '1h', h: 1, m: 0 },
                      { label: '1.5h', h: 1, m: 30 },
                      { label: '2h', h: 2, m: 0 },
                      { label: '3h', h: 3, m: 0 },
                      { label: '4h', h: 4, m: 0 }
                    ].map((preset, i) => (
                      <button
                        key={i}
                        type="button"
                        onClick={() => {
                          setWhHours(preset.h);
                          setWhMinutes(preset.m);
                        }}
                        className={`px-1.5 py-0.5 rounded text-[10px] font-semibold transition ${
                          whHours === preset.h && whMinutes === preset.m
                            ? 'bg-sky-600 text-white shadow-xs'
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                        }`}
                      >
                        {preset.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="text-[11px] text-slate-500 block mb-0.5 font-medium">Stunden (h):</label>
                    <div className="relative">
                      <input
                        type="number"
                        min="0"
                        max="24"
                        step="1"
                        value={whHours}
                        onChange={e => setWhHours(Math.max(0, parseInt(e.target.value) || 0))}
                        className="w-full pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-sky-500"
                      />
                      <span className="absolute right-3 top-2 text-xs text-slate-400 font-bold">h</span>
                    </div>
                  </div>
                  <div>
                    <label className="text-[11px] text-slate-500 block mb-0.5 font-medium">Minuten (min):</label>
                    <div className="relative">
                      <input
                        type="number"
                        min="0"
                        max="59"
                        step="5"
                        value={whMinutes}
                        onChange={e => setWhMinutes(Math.max(0, Math.min(59, parseInt(e.target.value) || 0)))}
                        className="w-full pl-3 pr-10 py-2 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-sky-500"
                      />
                      <span className="absolute right-3 top-2 text-xs text-slate-400 font-bold">min</span>
                    </div>
                  </div>
                </div>

                {/* Zeitfenster & Pause */}
                <div className="grid grid-cols-3 gap-2 pt-2 border-t border-slate-200/60">
                  <div>
                    <label className="text-[10px] text-slate-500 block mb-0.5">Startzeit:</label>
                    <input
                      type="time"
                      value={whStartTime}
                      onChange={e => setWhStartTime(e.target.value)}
                      className="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                    />
                  </div>
                  <div>
                    <label className="text-[10px] text-slate-500 block mb-0.5">Pause (min):</label>
                    <input
                      type="number"
                      min="0"
                      step="5"
                      value={whBreakMinutes}
                      onChange={e => setWhBreakMinutes(Math.max(0, parseInt(e.target.value) || 0))}
                      className="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                    />
                  </div>
                  <div>
                    <label className="text-[10px] text-slate-500 block mb-0.5">Endzeit (errechnet):</label>
                    <div className="px-2 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-xs font-mono text-slate-700 font-semibold">
                      {whCalculated.endTimeStr.slice(0, 5)} Uhr
                    </div>
                  </div>
                </div>
              </div>

              {/* Stundensatz (vom Admin hinterlegt) & Live-Berechnung */}
              <div className="grid grid-cols-2 gap-3 items-center">
                <div>
                  <label className="font-semibold text-slate-700 block mb-1 flex items-center gap-1.5">
                    <Lock className="w-3.5 h-3.5 text-slate-500" />
                    Stundensatz (vom Admin hinterlegt):
                  </label>
                  <div className="px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-between shadow-xs">
                    <span>CHF {currentInstructor.pay_rate_amount.toFixed(2)} / Std.</span>
                    <span className="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-normal">Fixiert</span>
                  </div>
                  <span className="text-[10px] text-slate-400 block mt-1">Lohnansatz kann nur vom Administrator geändert werden.</span>
                </div>

                <div className="p-3 bg-sky-50 border border-sky-200 rounded-xl">
                  <div className="text-[11px] text-sky-800 font-semibold">Honorar-Abrechnung:</div>
                  <div className="text-xs text-sky-700 mt-0.5">
                    <strong>{whCalculated.totalHours.toFixed(2)} Std.</strong> ({whHours}h {whMinutes}m) × CHF {currentInstructor.pay_rate_amount.toFixed(2)}
                  </div>
                  <div className="text-sm font-bold text-sky-900 mt-0.5">
                    = CHF {whCalculated.totalAmount.toFixed(2)}
                  </div>
                </div>
              </div>

              {/* Buttons */}
              <div className="flex justify-end gap-2 pt-3 border-t">
                <button
                  type="button"
                  onClick={() => setShowAddWorkingHour(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl"
                >
                  Abbrechen
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-xs rounded-xl shadow flex items-center gap-1.5"
                >
                  <Check className="w-4 h-4" />
                  Arbeitszeit speichern
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
