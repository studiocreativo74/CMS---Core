import React, { useState } from 'react';
import { Bell, Check, CheckCircle2, ChevronDown, ChevronUp, AlertCircle } from 'lucide-react';
import { Announcement } from '../../types/index.ts';

interface AnnouncementBannerProps {
  announcements: Announcement[];
  currentRole: 'coach' | 'customer';
  currentUserId: number;
  currentUserName: string;
  onMarkRead: (announcementId: number) => void;
}

export const AnnouncementBanner: React.FC<AnnouncementBannerProps> = ({
  announcements,
  currentRole,
  currentUserId,
  currentUserName,
  onMarkRead
}) => {
  const [expandedMap, setExpandedMap] = useState<Record<number, boolean>>({});
  const [dismissingId, setDismissingId] = useState<number | null>(null);

  if (!announcements || announcements.length === 0) {
    return null;
  }

  const toggleExpand = (id: number) => {
    setExpandedMap(prev => ({ ...prev, [id]: !prev[id] }));
  };

  const handleConfirm = (id: number) => {
    setDismissingId(id);
    setTimeout(() => {
      onMarkRead(id);
      setDismissingId(null);
    }, 280);
  };

  return (
    <div className="space-y-3.5 mb-6">
      {announcements.map(ann => {
        const isDismissing = dismissingId === ann.id;
        const isExpanded = !!expandedMap[ann.id];
        const isLongText = ann.content.length > 220;

        return (
          <div
            key={ann.id}
            className={`bg-white rounded-2xl border-l-4 border-l-sky-500 border border-slate-200/90 shadow-md p-5 sm:p-6 transition-all duration-300 ${
              isDismissing
                ? 'opacity-0 -translate-y-2 scale-98 pointer-events-none'
                : 'opacity-100 translate-y-0'
            }`}
          >
            <div className="flex flex-col md:flex-row md:items-start justify-between gap-4">
              
              {/* Linke Seite: Icon + Text */}
              <div className="flex items-start gap-3.5 sm:gap-4 flex-1">
                <div className="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-sky-50 border border-sky-200 text-sky-700 flex items-center justify-center shrink-0 shadow-inner mt-0.5">
                  <Bell className="w-5 h-5 text-sky-600 animate-pulse" />
                </div>

                <div className="flex-1 space-y-1.5 min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-sky-100 text-sky-800 border border-sky-200">
                      Wichtige Mitteilung
                    </span>
                    <span className="text-xs text-slate-400">
                      &bull; Veröffentlicht am {new Date(ann.created_at).toLocaleDateString('de-CH', { day: '2-digit', month: 'long', year: 'numeric' })}
                    </span>
                    {ann.author_name && (
                      <span className="text-xs text-slate-500 hidden sm:inline">
                        von {ann.author_name}
                      </span>
                    )}
                  </div>

                  <h3 className="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight leading-snug">
                    {ann.title}
                  </h3>

                  <div className="text-sm text-slate-700 leading-relaxed pt-1">
                    <p className={`whitespace-pre-wrap ${!isExpanded && isLongText ? 'line-clamp-3' : ''}`}>
                      {ann.content}
                    </p>

                    {isLongText && (
                      <button
                        type="button"
                        onClick={() => toggleExpand(ann.id)}
                        className="mt-1 text-xs text-sky-700 hover:text-sky-900 font-semibold inline-flex items-center gap-1 cursor-pointer"
                      >
                        {isExpanded ? (
                          <>
                            <span>Weniger anzeigen</span>
                            <ChevronUp className="w-3.5 h-3.5" />
                          </>
                        ) : (
                          <>
                            <span>Vollständige Mitteilung lesen...</span>
                            <ChevronDown className="w-3.5 h-3.5" />
                          </>
                        )}
                      </button>
                    )}
                  </div>
                </div>
              </div>

              {/* Rechte Seite: Quittierungs-Button */}
              <div className="self-start md:self-center shrink-0 pt-2 md:pt-0">
                <button
                  type="button"
                  onClick={() => handleConfirm(ann.id)}
                  className="w-full sm:w-auto px-4 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer group"
                  title="Kenntnisnahme bestätigen und Nachricht ausblenden"
                >
                  <Check className="w-4 h-4 text-sky-200 group-hover:scale-110 transition-transform" />
                  <span>Gelesen markieren</span>
                </button>
                <div className="text-[10px] text-slate-400 text-center mt-1">
                  Revisionssichere Quittierung
                </div>
              </div>

            </div>
          </div>
        );
      })}
    </div>
  );
};
