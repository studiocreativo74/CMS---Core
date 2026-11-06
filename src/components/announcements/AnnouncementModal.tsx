import React, { useState, useEffect } from 'react';
import { X, Bell, Users, Check, AlertCircle } from 'lucide-react';
import { Announcement, AnnouncementTargetRole } from '../../types/index.ts';

interface AnnouncementModalProps {
  announcement?: Announcement | null;
  onSave: (data: {
    id?: number;
    title: string;
    content: string;
    target_roles: AnnouncementTargetRole[];
    is_active: boolean;
  }) => void;
  onClose: () => void;
}

export const AnnouncementModal: React.FC<AnnouncementModalProps> = ({
  announcement,
  onSave,
  onClose
}) => {
  const isEditing = !!announcement;

  const [title, setTitle] = useState(announcement?.title || '');
  const [content, setContent] = useState(announcement?.content || '');
  const [targetRoles, setTargetRoles] = useState<AnnouncementTargetRole[]>(
    announcement?.target_roles || ['coach', 'customer']
  );
  const [isActive, setIsActive] = useState<boolean>(
    announcement ? announcement.is_active : true
  );
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    if (announcement) {
      setTitle(announcement.title);
      setContent(announcement.content);
      setTargetRoles(announcement.target_roles);
      setIsActive(announcement.is_active);
    }
  }, [announcement]);

  const toggleRole = (role: AnnouncementTargetRole) => {
    setErrorMsg(null);
    if (targetRoles.includes(role)) {
      if (targetRoles.length === 1) {
        setErrorMsg('Mindestens eine Zielgruppe (Coaches oder Kunden) muss ausgewählt bleiben.');
        return;
      }
      setTargetRoles(targetRoles.filter(r => r !== role));
    } else {
      setTargetRoles([...targetRoles, role]);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) {
      setErrorMsg('Bitte geben Sie einen Titel für die Mitteilung ein.');
      return;
    }
    if (!content.trim()) {
      setErrorMsg('Bitte erfassen Sie den Inhalt der Mitteilung.');
      return;
    }
    if (targetRoles.length === 0) {
      setErrorMsg('Bitte wählen Sie mindestens eine Zielgruppe (Coaches oder Kunden) aus.');
      return;
    }

    onSave({
      id: announcement?.id,
      title: title.trim(),
      content: content.trim(),
      target_roles: targetRoles,
      is_active: isActive
    });
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Modal Header */}
        <div className="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-white/10 text-amber-400 border border-white/10 flex items-center justify-center shrink-0">
              <Bell className="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <h3 className="text-base sm:text-lg font-bold text-white">
                {isEditing ? 'Mitteilung bearbeiten' : 'Neue Mitteilung erstellen'}
              </h3>
              <p className="text-xs text-slate-300">
                Wichtige Informationen rollenbasiert an Dashboards ausspielen
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-6 space-y-5">
          
          {errorMsg && (
            <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2.5">
              <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Titel */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
              Titel der Mitteilung *
            </label>
            <input
              type="text"
              required
              placeholder="z.B. Hafensperrung Luzern wegen Regatta am kommenden Samstag"
              value={title}
              onChange={e => setTitle(e.target.value)}
              className="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium"
            />
          </div>

          {/* Zielgruppe Multi-Select Buttons */}
          <div className="space-y-2">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
              Zielgruppe (Ausspielung auf Dashboard) *
            </label>
            <p className="text-xs text-slate-500">
              Wählen Sie, welche Benutzergruppen diese Mitteilung mit Bestätigungs-Button sehen sollen:
            </p>

            <div className="grid grid-cols-2 gap-3 pt-1">
              {/* Button Coaches */}
              <button
                type="button"
                onClick={() => toggleRole('coach')}
                className={`p-3.5 rounded-2xl border text-left transition flex items-start justify-between gap-3 cursor-pointer ${
                  targetRoles.includes('coach')
                    ? 'bg-sky-50 border-sky-400 text-sky-950 shadow-xs ring-1 ring-sky-300'
                    : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                }`}
              >
                <div>
                  <div className="font-bold text-xs sm:text-sm flex items-center gap-1.5">
                    <span>Coaches</span>
                    {targetRoles.includes('coach') && (
                      <span className="w-2 h-2 rounded-full bg-sky-500"></span>
                    )}
                  </div>
                  <div className="text-[11px] text-slate-500 mt-0.5">
                    Fahrlehrer &amp; Instruktoren
                  </div>
                </div>
                <div className={`w-5 h-5 rounded-lg flex items-center justify-center text-xs shrink-0 ${
                  targetRoles.includes('coach')
                    ? 'bg-sky-600 text-white'
                    : 'border border-slate-300 bg-white'
                }`}>
                  {targetRoles.includes('coach') && <Check className="w-3.5 h-3.5" />}
                </div>
              </button>

              {/* Button Kunden */}
              <button
                type="button"
                onClick={() => toggleRole('customer')}
                className={`p-3.5 rounded-2xl border text-left transition flex items-start justify-between gap-3 cursor-pointer ${
                  targetRoles.includes('customer')
                    ? 'bg-emerald-50 border-emerald-400 text-emerald-950 shadow-xs ring-1 ring-emerald-300'
                    : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                }`}
              >
                <div>
                  <div className="font-bold text-xs sm:text-sm flex items-center gap-1.5">
                    <span>Kunden</span>
                    {targetRoles.includes('customer') && (
                      <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                    )}
                  </div>
                  <div className="text-[11px] text-slate-500 mt-0.5">
                    Fahrschüler &amp; Bootsmieter
                  </div>
                </div>
                <div className={`w-5 h-5 rounded-lg flex items-center justify-center text-xs shrink-0 ${
                  targetRoles.includes('customer')
                    ? 'bg-emerald-600 text-white'
                    : 'border border-slate-300 bg-white'
                }`}>
                  {targetRoles.includes('customer') && <Check className="w-3.5 h-3.5" />}
                </div>
              </button>
            </div>
          </div>

          {/* Inhalt (mehrzeilig) */}
          <div className="space-y-1.5">
            <label className="block text-xs font-bold text-slate-800 uppercase tracking-wider">
              Inhalt der Mitteilung *
            </label>
            <textarea
              required
              rows={5}
              placeholder="Geben Sie den vollständigen Text ein. Zeilenumbrüche werden auf dem Dashboard exakt so dargestellt wie erfasst..."
              value={content}
              onChange={e => setContent(e.target.value)}
              className="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 font-normal leading-relaxed whitespace-pre-wrap"
            />
            <div className="text-[11px] text-slate-400 flex items-center justify-between">
              <span>Unterstützt Absätze &amp; Listen.</span>
              <span>{content.length} Zeichen</span>
            </div>
          </div>

          {/* Status Aktiv / Inaktiv */}
          <div className="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
            <div>
              <div className="font-bold text-xs text-slate-900">
                Mitteilung aktiv schalten
              </div>
              <div className="text-[11px] text-slate-500">
                Nur aktive Mitteilungen werden auf den Dashboards der Empfänger angezeigt.
              </div>
            </div>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={isActive}
                onChange={e => setIsActive(e.target.checked)}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
          </div>

          {/* Footer Buttons */}
          <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              className="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer"
            >
              <Check className="w-4 h-4 text-sky-200" />
              <span>{isEditing ? 'Änderungen speichern' : 'Mitteilung veröffentlichen'}</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};
