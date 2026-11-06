import React, { useState } from 'react';
import { X, Search, CheckCircle2, Eye, ShieldCheck, Clock, User, Download } from 'lucide-react';
import { Announcement, AnnouncementRead } from '../../types/index.ts';

interface ReadProtocolModalProps {
  announcement: Announcement;
  reads: AnnouncementRead[];
  onClose: () => void;
}

export const ReadProtocolModal: React.FC<ReadProtocolModalProps> = ({
  announcement,
  reads,
  onClose
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState<'all' | 'coach' | 'customer'>('all');

  const filteredReads = reads.filter(r => {
    const matchesSearch = r.user_name.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesRole = roleFilter === 'all' || r.user_role === roleFilter;
    return matchesSearch && matchesRole;
  });

  const exportCSV = () => {
    const headers = ['ID', 'Mitteilung-ID', 'Titel', 'Benutzer-Name', 'Rolle', 'Bestätigt Am (Zeitstempel)'];
    const rows = reads.map(r => [
      r.id,
      r.announcement_id,
      `"${announcement.title.replace(/"/g, '""')}"`,
      `"${r.user_name.replace(/"/g, '""')}"`,
      r.user_role,
      r.read_at
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `leseprotokoll_mitteilung_${announcement.id}_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8">
        
        {/* Modal Header */}
        <div className="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-start justify-between gap-4">
          <div className="flex items-center gap-3.5">
            <div className="w-10 h-10 rounded-2xl bg-white/10 text-amber-400 border border-white/10 flex items-center justify-center shrink-0">
              <ShieldCheck className="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 uppercase tracking-wider">
                  Audit-Trail
                </span>
                <span className="text-xs text-slate-300">
                  Mitteilungs-ID #{announcement.id}
                </span>
              </div>
              <h3 className="text-lg font-bold text-white mt-0.5">
                Revisionssicheres Leseprotokoll
              </h3>
              <p className="text-xs text-slate-300 mt-0.5">
                Übersicht aller Personen, die diese Nachricht gelesen &amp; quittiert haben
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

        {/* Mitteilungs-Zusammenfassung */}
        <div className="p-6 bg-slate-50 border-b border-slate-200 space-y-2">
          <div className="text-xs font-bold text-slate-500 uppercase tracking-wider">
            Betreffende Mitteilung:
          </div>
          <div className="font-bold text-slate-900 text-sm">
            {announcement.title}
          </div>
          <div className="text-xs text-slate-600 line-clamp-2">
            {announcement.content}
          </div>
          <div className="flex flex-wrap items-center gap-2 pt-1">
            <span className="text-xs text-slate-500">
              Zielgruppe:
            </span>
            {announcement.target_roles.map(role => (
              <span
                key={role}
                className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                  role === 'coach'
                    ? 'bg-sky-100 text-sky-800 border border-sky-200'
                    : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                }`}
              >
                {role === 'coach' ? 'Coaches' : 'Kunden'}
              </span>
            ))}
            <span className="text-xs text-slate-400 ml-auto font-medium">
              Veröffentlicht: {new Date(announcement.created_at).toLocaleString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })} Uhr
            </span>
          </div>
        </div>

        {/* Such- & Filterleiste */}
        <div className="p-6 pb-2 space-y-4">
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            
            <div className="relative flex-1">
              <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Nach Person suchen..."
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
                className="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500"
              />
            </div>

            <div className="flex items-center gap-2">
              <select
                value={roleFilter}
                onChange={e => setRoleFilter(e.target.value as any)}
                className="px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500"
              >
                <option value="all">Alle Rollen ({reads.length})</option>
                <option value="coach">Nur Coaches ({reads.filter(r => r.user_role === 'coach').length})</option>
                <option value="customer">Nur Kunden ({reads.filter(r => r.user_role === 'customer').length})</option>
              </select>

              {reads.length > 0 && (
                <button
                  type="button"
                  onClick={exportCSV}
                  className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                  title="Leseprotokoll als CSV exportieren"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span className="hidden sm:inline">CSV Export</span>
                </button>
              )}
            </div>

          </div>

          <div className="flex items-center justify-between text-xs text-slate-500 pt-1">
            <span>
              Bestätigt von <strong>{reads.length}</strong> Empfängern (Anzeige: {filteredReads.length})
            </span>
            <span className="text-emerald-700 font-semibold flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
              Echtzeit-Synchronisiert
            </span>
          </div>
        </div>

        {/* Protokoll-Liste */}
        <div className="p-6 pt-2 max-h-80 overflow-y-auto">
          {filteredReads.length === 0 ? (
            <div className="py-12 px-4 text-center space-y-3 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
              <div className="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center mx-auto shadow-2xs">
                <Eye className="w-6 h-6 text-slate-400" />
              </div>
              <h4 className="font-bold text-slate-800 text-sm">
                Bisher keine Lesebestätigungen
              </h4>
              <p className="text-xs text-slate-500 max-w-sm mx-auto">
                {searchTerm || roleFilter !== 'all'
                  ? 'Keine Treffer für diesen Filter gefunden.'
                  : 'Sobald ein Coach oder Kunde diese Mitteilung auf seinem Dashboard bestätigt, wird der exakte Zeitstempel hier protokolliert.'}
              </p>
            </div>
          ) : (
            <div className="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden bg-white">
              {filteredReads.map(entry => {
                const initials = entry.user_name
                  .split(' ')
                  .map(n => n[0])
                  .join('')
                  .slice(0, 2)
                  .toUpperCase();

                const formattedDate = new Date(entry.read_at).toLocaleString('de-CH', {
                  day: '2-digit',
                  month: '2-digit',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                  second: '2-digit'
                });

                return (
                  <div
                    key={entry.id}
                    className="p-3.5 sm:p-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border ${
                        entry.user_role === 'coach'
                          ? 'bg-sky-100 text-sky-800 border-sky-300'
                          : 'bg-emerald-100 text-emerald-800 border-emerald-300'
                      }`}>
                        {initials}
                      </div>

                      <div className="truncate">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-slate-900 text-xs sm:text-sm truncate">
                            {entry.user_name}
                          </span>
                          <span className={`px-2 py-0.2 rounded-full text-[9px] font-extrabold uppercase tracking-wider ${
                            entry.user_role === 'coach'
                              ? 'bg-sky-100 text-sky-800 border border-sky-200'
                              : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                          }`}>
                            {entry.user_role === 'coach' ? 'Coach' : 'Kunde'}
                          </span>
                        </div>
                        <div className="text-[11px] text-slate-400">
                          User ID #{entry.user_id} &bull; Quittung #{entry.id}
                        </div>
                      </div>
                    </div>

                    <div className="text-right shrink-0">
                      <div className="flex items-center gap-1.5 text-xs font-semibold text-slate-800 justify-end">
                        <Clock className="w-3.5 h-3.5 text-slate-400" />
                        <span>{formattedDate} Uhr</span>
                      </div>
                      <span className="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full mt-0.5 border border-emerald-200">
                        <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                        Rechtsgültig quittiert
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Modal Footer */}
        <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
          <div className="text-[11px] text-slate-500">
            Audit-Datensatz konform mit Schifffahrtsrecht &amp; AMB
          </div>
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
          >
            Schliessen
          </button>
        </div>

      </div>
    </div>
  );
};
