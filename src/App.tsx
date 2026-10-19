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
  Lock,
  Phone,
  Mail,
  MapPin,
  FileText
} from 'lucide-react';

// --- Datentypen & Mock-Daten der Bootsfahrschule ---

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
  const [activeTab, setActiveTab] = useState<'payroll' | 'student' | 'instructor' | 'architecture'>('payroll');

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
              <Link className="w-4 h-4" />
              <span className="hidden md:inline">2. Schüler Magic-Link</span>
              <span className="md:hidden">Schüler</span>
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
        {activeTab === 'student' && (
          <div className="space-y-6">
            <div className="bg-gradient-to-r from-sky-900 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                  <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-semibold border border-sky-400/30 mb-3">
                    <Shield className="w-3.5 h-3.5" />
                    Sicherer Token-Zugang ohne Passwort
                  </div>
                  <h1 className="text-3xl font-extrabold tracking-tight">Schülerportal: «Meine Termine»</h1>
                  <p className="text-sky-200/80 text-sm mt-2 max-w-2xl">
                    Kunden erhalten ihren persönlichen Magic-Link automatisch in jeder Buchungsbestätigung oder können jederzeit per E-Mail einen neuen Einmal-Link generieren.
                  </p>
                </div>

                <div className="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 text-xs space-y-2 max-w-sm">
                  <div className="font-semibold text-white flex items-center gap-2">
                    <Lock className="w-4 h-4 text-sky-400" />
                    Aktiver Magic-Token (SHA-256)
                  </div>
                  <div className="font-mono bg-black/30 p-2 rounded text-sky-300 break-all select-all">
                    {magicToken}
                  </div>
                  <div className="text-slate-300 text-[11px] flex justify-between">
                    <span>Gültig für: <strong>Lukas Berner</strong></span>
                    <span>Ablauf: 7 Tage</span>
                  </div>
                </div>
              </div>
            </div>

            {magicFeedback && (
              <div className="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-sm flex items-center gap-3 shadow-sm">
                <CheckCircle2 className="w-5 h-5 text-emerald-600 flex-shrink-0" />
                <span>{magicFeedback}</span>
              </div>
            )}

            {/* Schüler Info & Übersicht */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Schüler-Karte */}
              <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-lg">
                      LB
                    </div>
                    <div>
                      <h2 className="text-lg font-bold text-slate-900">Hallo Lukas!</h2>
                      <p className="text-xs text-slate-500">Lukas Berner &bull; Nr. #1042 &bull; Schüler Kat. A</p>
                    </div>
                  </div>
                  <button
                    onClick={() => {
                      setMagicFeedback('Sitzung sicher beendet. Du hast dich erfolgreich abgemeldet.');
                      setTimeout(() => setMagicFeedback(null), 5000);
                    }}
                    className="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition"
                  >
                    Abmelden
                  </button>
                </div>

                <div className="border-t border-slate-100 pt-3 space-y-2 text-xs text-slate-600">
                  <div className="flex items-center gap-2">
                    <Mail className="w-4 h-4 text-slate-400" />
                    <span>lukas.berner@swiss-pilot.ch</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Phone className="w-4 h-4 text-slate-400" />
                    <span>+41 79 400 11 22</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Award className="w-4 h-4 text-slate-400" />
                    <span>Theorieprüfung bestanden (01/2026)</span>
                  </div>
                </div>

                {/* Abo Card */}
                <div className="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-xl shadow-sm text-xs space-y-2">
                  <div className="flex justify-between items-center text-slate-300">
                    <span>Mehrfachkarte / 10er Abo</span>
                    <span className="text-emerald-400 font-bold">Aktiv</span>
                  </div>
                  <div className="text-lg font-bold">6 / 10 Lektionen absolviert</div>
                  <div className="w-full bg-slate-700 h-2 rounded-full overflow-hidden">
                    <div className="bg-sky-400 h-full w-[60%]" />
                  </div>
                  <div className="text-[11px] text-slate-400 flex justify-between">
                    <span>Verbleibend: 4 Lektionen</span>
                    <span>Wert: CHF 780.00</span>
                  </div>
                </div>

                {/* Neuen Link per Mail anfordern */}
                <div className="border-t border-slate-100 pt-3">
                  <label className="text-xs font-semibold text-slate-700 block mb-1">
                    Neuen Zugangslink anfordern:
                  </label>
                  <div className="flex gap-2">
                    <input
                      type="email"
                      value={studentEmailRequest}
                      onChange={e => setStudentEmailRequest(e.target.value)}
                      placeholder="deine-email@beispiel.ch"
                      className="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs flex-1 focus:outline-none focus:ring-2 focus:ring-sky-500"
                    />
                    <button
                      onClick={() => {
                        setMagicFeedback(`Neuer Zugangslink wurde an ${studentEmailRequest || 'lukas.berner@swiss-pilot.ch'} gesendet.`);
                        setTimeout(() => setMagicFeedback(null), 5000);
                      }}
                      className="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition"
                    >
                      Senden
                    </button>
                  </div>
                </div>
              </div>

              {/* Schüler Termine */}
              <div className="lg:col-span-2 space-y-6">
                {/* Anstehende Termine */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                  <div className="p-4 border-b border-slate-100 bg-sky-50/50 flex items-center justify-between">
                    <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-sky-600" />
                      Bevorstehende Fahrstunden (2)
                    </h3>
                    <span className="text-xs text-sky-700 font-medium">Stornierung bis 24h vorher kostenlos</span>
                  </div>

                  <div className="divide-y divide-slate-100">
                    {appointments
                      .filter(a => a.customer_id === 1 && a.status === 'confirmed')
                      .map(a => (
                        <div key={a.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="font-bold text-slate-900 text-base">{a.service_name}</span>
                              <span className="text-xs px-2 py-0.5 bg-sky-100 text-sky-800 rounded-full font-medium">
                                Bestätigt
                              </span>
                            </div>
                            <div className="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                              <span><Calendar className="w-3.5 h-3.5 inline mr-1 text-slate-400" />{a.date}</span>
                              <span><Clock className="w-3.5 h-3.5 inline mr-1 text-slate-400" />{a.start_time.slice(0, 5)} - {a.end_time.slice(0, 5)} Uhr</span>
                              <span><Ship className="w-3.5 h-3.5 inline mr-1 text-slate-400" />{a.boat_name}</span>
                              <span><User className="w-3.5 h-3.5 inline mr-1 text-slate-400" />Coach: {a.instructor_name}</span>
                            </div>
                            <div className="text-xs text-slate-400 mt-1">
                              <MapPin className="w-3.5 h-3.5 inline mr-1 text-slate-400" />{a.location_name}
                            </div>
                          </div>

                          <div className="flex sm:flex-col items-center sm:items-end justify-between gap-2">
                            <span className="text-sm font-bold text-slate-900">CHF {a.price.toFixed(2)}</span>
                            <button
                              onClick={() => setCancelModalAppointment(a)}
                              className="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition"
                            >
                              Stornieren
                            </button>
                          </div>
                        </div>
                      ))}
                  </div>
                </div>

                {/* Vergangene Termine */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                  <div className="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                      <Clock className="w-4 h-4 text-slate-500" />
                      Vergangene Fahrstunden &amp; Prüfungserfolge
                    </h3>
                  </div>

                  <div className="divide-y divide-slate-100">
                    {appointments
                      .filter(a => a.customer_id === 1 && (a.status === 'completed' || a.status === 'cancelled_customer'))
                      .map(a => (
                        <div key={a.id} className="p-4 flex items-center justify-between text-xs hover:bg-slate-50 transition">
                          <div>
                            <div className="font-semibold text-slate-800 text-sm">{a.service_name}</div>
                            <div className="text-slate-500 mt-0.5">
                              {a.date} &bull; {a.duration_minutes} Min mit {a.instructor_name} auf {a.boat_name}
                            </div>
                            {a.status === 'cancelled_customer' && (
                              <div className="text-rose-600 font-medium mt-0.5">
                                {a.instructor_payout_note}
                              </div>
                            )}
                          </div>

                          <div className="text-right">
                            <div className="font-bold text-slate-900">CHF {a.price.toFixed(2)}</div>
                            {a.status === 'completed' ? (
                              <span className="text-emerald-600 font-semibold flex items-center gap-1 justify-end">
                                <CheckCircle2 className="w-3.5 h-3.5" /> Abgeschlossen
                              </span>
                            ) : (
                              <span className="text-rose-600 font-semibold">Storniert</span>
                            )}
                          </div>
                        </div>
                      ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

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
        {/* TAB 4: BACKEND ARCHITEKTUR & MIGRATIONEN                      */}
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
