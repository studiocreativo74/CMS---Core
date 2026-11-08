import React, { useState } from 'react';
import { X, User, HeartPulse, Shield, MapPin, Phone, Mail, Check } from 'lucide-react';

export interface StudentProfileData {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  birthdate: string;
  street: string;
  zip: string;
  city: string;
  avatar_url?: string;
  theory_passed?: boolean;
  theory_passed_date?: string;
  faber?: string;
  pin?: string;
  pid?: string;
  license_number?: string;
  emergency_contact?: string;
  allergies?: string;
  notes?: string;
}

interface StudentProfileModalProps {
  isOpen: boolean;
  onClose: () => void;
  profile: StudentProfileData;
  onSave: (updatedProfile: StudentProfileData) => void;
}

export const StudentProfileModal: React.FC<StudentProfileModalProps> = ({
  isOpen,
  onClose,
  profile,
  onSave
}) => {
  if (!isOpen) return null;

  const [formData, setFormData] = useState<StudentProfileData>({
    ...profile,
    first_name: (!profile.first_name || profile.first_name.toLowerCase() === 'office') ? 'Roland' : profile.first_name,
    last_name: (!profile.last_name || profile.last_name.toLowerCase() === 'office') ? 'Grüter' : profile.last_name,
    allergies: profile.allergies || 'Keine bekannten Allergien',
    emergency_contact: profile.emergency_contact || '+41 79 123 45 67 (Ehepartner)',
    phone: profile.phone || '+41 79 000 00 00'
  });

  const handleChange = (field: keyof StudentProfileData, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Header */}
        <div className="px-6 py-5 bg-[#2d2421] text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-400/30 flex items-center justify-center shrink-0">
              <User className="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <h3 className="text-base sm:text-lg font-bold text-white">
                Mein Kundenprofil &amp; Stammdaten
              </h3>
              <p className="text-xs text-stone-300">
                Persönliche Angaben, Notfallkontakt &amp; Allergien verwalten
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

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5 text-xs">
          
          {/* Vorname & Nachname */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                Vorname *
              </label>
              <input
                type="text"
                required
                value={formData.first_name}
                onChange={e => handleChange('first_name', e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                Nachname *
              </label>
              <input
                type="text"
                required
                value={formData.last_name}
                onChange={e => handleChange('last_name', e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          {/* E-Mail & Telefon */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
                <Mail className="w-3.5 h-3.5 text-slate-500" />
                <span>E-Mail-Adresse *</span>
              </label>
              <input
                type="email"
                required
                value={formData.email}
                onChange={e => handleChange('email', e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
                <Phone className="w-3.5 h-3.5 text-slate-500" />
                <span>Telefon / Mobilnummer *</span>
              </label>
              <input
                type="tel"
                required
                value={formData.phone}
                onChange={e => handleChange('phone', e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          {/* Adresse: Strasse, PLZ & Ort */}
          <div className="grid grid-cols-3 gap-3">
            <div className="col-span-3 sm:col-span-2 space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
                <MapPin className="w-3.5 h-3.5 text-slate-500" />
                <span>Strasse &amp; Nr.</span>
              </label>
              <input
                type="text"
                placeholder="z. B. Seestrasse 42"
                value={formData.street || ''}
                onChange={e => handleChange('street', e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div className="col-span-3 sm:col-span-1 space-y-1.5">
              <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                PLZ &amp; Ort
              </label>
              <input
                type="text"
                placeholder="6005 Luzern"
                value={formData.city ? `${formData.zip ? formData.zip + ' ' : ''}${formData.city}` : ''}
                onChange={e => {
                  const val = e.target.value;
                  const parts = val.split(' ');
                  if (parts.length > 1) {
                    handleChange('zip', parts[0]);
                    handleChange('city', parts.slice(1).join(' '));
                  } else {
                    handleChange('city', val);
                  }
                }}
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          {/* Notfallkontakt */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1">
              <Shield className="w-3.5 h-3.5 text-slate-500" />
              <span>Notfallkontakt (Name &amp; Telefonnummer)</span>
            </label>
            <input
              type="text"
              placeholder="z. B. Maria Grüter (Ehefrau) – +41 79 123 45 67"
              value={formData.emergency_contact || ''}
              onChange={e => handleChange('emergency_contact', e.target.value)}
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
            <p className="text-[11px] text-slate-400">
              Wird gemäss Schifffahrtsgesetz für Ausfahrten und Bootsmieten benötigt.
            </p>
          </div>

          {/* ALLERGIEN (SPEZIFISCHE ANFORDERUNG: Feld Allergien in Kundendaten) */}
          <div className="space-y-1.5 p-3.5 bg-amber-50/70 border border-amber-200 rounded-2xl">
            <label className="block text-xs font-bold text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
              <HeartPulse className="w-4 h-4 text-amber-600" />
              <span>Allergien &amp; gesundheitliche Hinweise</span>
            </label>
            <input
              type="text"
              placeholder="z. B. Wespenstich-Allergie (EpiPen an Bord), Asthma, Keine Allergien..."
              value={formData.allergies || ''}
              onChange={e => handleChange('allergies', e.target.value)}
              className="w-full px-3.5 py-2 bg-white border border-amber-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
            <p className="text-[11px] text-amber-800">
              Wichtig für Ihren Coach bei Notfällen auf dem See (z. B. Insektenstiche, Notfallmedikamente).
            </p>
          </div>

          {/* Footer Buttons */}
          <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              className="px-5 py-2.5 bg-[#2d2421] hover:bg-[#3d322e] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <Check className="w-4 h-4 text-amber-400" />
              <span>Profil speichern</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};
