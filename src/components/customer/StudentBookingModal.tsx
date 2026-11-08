import React, { useState } from 'react';
import { X, Calendar, Clock, MapPin, User, Check, Sparkles, Award } from 'lucide-react';
import { ServiceOffer, Instructor } from '../../types/index.ts';

interface StudentBookingModalProps {
  isOpen: boolean;
  onClose: () => void;
  services: ServiceOffer[];
  instructors: Instructor[];
  studentRemainingSessions: number;
  studentName: string;
  studentEmail: string;
  studentPhone: string;
  onSubmit: (appointmentData: {
    service_name: string;
    duration_minutes: number;
    price: number;
    instructor_id: number;
    instructor_name: string;
    boat_name: string;
    location_name: string;
    date: string;
    start_time: string;
    end_time: string;
    notes: string;
    use_balance: boolean;
  }) => void;
}

export const StudentBookingModal: React.FC<StudentBookingModalProps> = ({
  isOpen,
  onClose,
  services,
  instructors,
  studentRemainingSessions,
  studentName,
  studentEmail,
  studentPhone,
  onSubmit
}) => {
  if (!isOpen) return null;

  // Nur aktive und erfasste Dienstleistungen
  const activeServices = services.filter(s => s.is_active);

  const [selectedServiceId, setSelectedServiceId] = useState<number>(
    activeServices[0]?.id || 1
  );
  const [selectedCoachId, setSelectedCoachId] = useState<number | 'any'>('any');

  // Tomorrow as default
  const tomorrowStr = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
  const [selectedDate, setSelectedDate] = useState<string>(tomorrowStr);
  const [selectedStartTime, setSelectedStartTime] = useState<string>('13:30');
  const [selectedLocation, setSelectedLocation] = useState<string>('Luzern (Alpenquai)');
  const [useBalance, setUseBalance] = useState<boolean>(studentRemainingSessions > 0);
  const [notes, setNotes] = useState<string>('');

  const currentService = activeServices.find(s => s.id === selectedServiceId) || activeServices[0];
  const serviceDuration = currentService?.price_options?.[0]?.duration || 90;
  const servicePrice = currentService?.price_options?.[0]?.price || 180.0;

  // Calculate end time
  const [startHour, startMin] = selectedStartTime.split(':').map(Number);
  const endMinutesTotal = startHour * 60 + startMin + serviceDuration;
  const endHour = Math.floor(endMinutesTotal / 60) % 24;
  const endMin = endMinutesTotal % 60;
  const calculatedEndTime = `${endHour < 10 ? '0' : ''}${endHour}:${endMin < 10 ? '0' : ''}${endMin}:00`;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    let assignedCoach: Instructor;
    if (selectedCoachId === 'any') {
      assignedCoach = instructors[0];
    } else {
      assignedCoach = instructors.find(i => i.id === selectedCoachId) || instructors[0];
    }

    const boatName = currentService.name.toLowerCase().includes('kat. a')
      ? 'Boesch 620 Bimini'
      : 'Nautic Star 21 (Schulungsboot)';

    onSubmit({
      service_name: currentService.name,
      duration_minutes: serviceDuration,
      price: useBalance ? 0 : servicePrice,
      instructor_id: assignedCoach.id,
      instructor_name: assignedCoach.name,
      boat_name: boatName,
      location_name: selectedLocation,
      date: selectedDate,
      start_time: `${selectedStartTime}:00`,
      end_time: calculatedEndTime,
      notes: notes.trim(),
      use_balance: useBalance
    });

    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Header */}
        <div className="px-6 py-5 bg-[#2d2421] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-400/30 flex items-center justify-center shrink-0">
              <Calendar className="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <h3 className="text-base sm:text-lg font-bold text-white">
                Termin buchen
              </h3>
              <p className="text-xs text-stone-300">
                Wählen Sie Ihre gewünschte Dienstleistung, Ihren Coach und den Termin
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="text-stone-400 hover:text-white p-1 rounded-lg hover:bg-stone-800 transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5 text-xs">
          
          {/* 1. Dienstleistung wählen (NUR erfasste Dienstleistungen) */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
              1. Dienstleistung auswählen *
            </label>
            <select
              value={selectedServiceId}
              onChange={e => setSelectedServiceId(Number(e.target.value))}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
            >
              {activeServices.map(s => (
                <option key={s.id} value={s.id}>
                  {s.name} ({s.price_options?.[0]?.duration || 90} Min. – CHF {(s.price_options?.[0]?.price || 0).toFixed(2)})
                </option>
              ))}
            </select>
            {currentService && (
              <p className="text-[11px] text-slate-500 line-clamp-2 mt-1">
                {currentService.description}
              </p>
            )}
          </div>

          {/* 2. Fahrcoach wählen (NUR erfasste Coaches) */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
              <User className="w-3.5 h-3.5 text-slate-500" />
              <span>2. Coach / Fahrlehrer wählen *</span>
            </label>
            <select
              value={selectedCoachId}
              onChange={e => setSelectedCoachId(e.target.value === 'any' ? 'any' : Number(e.target.value))}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
            >
              <option value="any">Beliebiger Coach (Früheste Verfügbarkeit)</option>
              {instructors.map(i => (
                <option key={i.id} value={i.id}>
                  {i.name} ({i.title})
                </option>
              ))}
            </select>
          </div>

          {/* 3. Datum & Uhrzeit */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
                <Calendar className="w-3.5 h-3.5 text-slate-500" />
                <span>Wunschdatum *</span>
              </label>
              <input
                type="date"
                required
                min={new Date().toISOString().slice(0, 10)}
                value={selectedDate}
                onChange={e => setSelectedDate(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
                <Clock className="w-3.5 h-3.5 text-slate-500" />
                <span>Startzeit *</span>
              </label>
              <select
                value={selectedStartTime}
                onChange={e => setSelectedStartTime(e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                <option value="08:30">08:30 Uhr (Frühschicht)</option>
                <option value="10:30">10:30 Uhr (Vormittag)</option>
                <option value="13:30">13:30 Uhr (Nachmittag)</option>
                <option value="15:30">15:30 Uhr (Spätnachmittag)</option>
                <option value="17:30">17:30 Uhr (Abend / Sonnenuntergang)</option>
              </select>
            </div>
          </div>

          {/* 4. Treffpunkt / Standort */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
              <MapPin className="w-3.5 h-3.5 text-slate-500" />
              <span>Treffpunkt / Hafensteg *</span>
            </label>
            <select
              value={selectedLocation}
              onChange={e => setSelectedLocation(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
            >
              <option value="Luzern (Alpenquai)">Luzern – Alpenquai Steg 3 (Hauptstandort)</option>
              <option value="Hergiswil NW (Bootshafen)">Hergiswil NW – Bootshafen / Seestrasse</option>
              <option value="Stansstad NW (Hafenanlage)">Stansstad NW – Öffentliche Hafenanlage</option>
            </select>
          </div>

          {/* 5. Guthaben einlösen (falls vorhanden) */}
          {studentRemainingSessions > 0 && (
            <div className="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200 flex items-center justify-between gap-3">
              <div className="flex items-center gap-2.5">
                <div className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                  <Award className="w-4 h-4 text-emerald-700" />
                </div>
                <div>
                  <div className="font-bold text-xs text-emerald-950">
                    VIP-Guthaben verfügbar
                  </div>
                  <div className="text-[11px] text-emerald-800">
                    Sie haben noch {studentRemainingSessions} bezahlte Lektionen auf Ihrem Konto.
                  </div>
                </div>
              </div>

              <label className="flex items-center gap-2 cursor-pointer font-bold text-xs text-emerald-900">
                <input
                  type="checkbox"
                  checked={useBalance}
                  onChange={e => setUseBalance(e.target.checked)}
                  className="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                />
                <span>Anrechnen (CHF 0.00)</span>
              </label>
            </div>
          )}

          {/* 6. Bemerkungen */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
              Wünsche &amp; Trainingsschwerpunkte (optional)
            </label>
            <input
              type="text"
              placeholder="z. B. Fokus auf Rückwärts in Box anlegen, Boje über Bord Manöver..."
              value={notes}
              onChange={e => setNotes(e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
          </div>

          {/* Zusammenfassung & Footer */}
          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <div className="text-[11px] text-slate-500">Gesamtbetrag:</div>
              <div className="text-base font-extrabold text-slate-900">
                {useBalance ? 'CHF 0.00 (aus Abo verrechnet)' : `CHF ${servicePrice.toFixed(2)}`}
              </div>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-white transition cursor-pointer"
              >
                Abbrechen
              </button>
              <button
                type="submit"
                className="px-5 py-2.5 bg-[#2d2421] hover:bg-[#3d322e] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
              >
                <Check className="w-4 h-4 text-amber-400" />
                <span>Termin jetzt verbindlich anfragen</span>
              </button>
            </div>
          </div>

        </form>

      </div>
    </div>
  );
};
