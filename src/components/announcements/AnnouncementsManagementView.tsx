import React, { useState, useEffect } from 'react';
import {
  Bell,
  Plus,
  Search,
  Eye,
  Edit3,
  Trash2,
  CheckCircle2,
  ShieldCheck,
  AlertTriangle,
  Clock,
  Users,
  Filter,
  Check,
  X,
  FileText
} from 'lucide-react';
import { Announcement, AnnouncementRead, AnnouncementTargetRole } from '../../types/index.ts';
import { ReadProtocolModal } from './ReadProtocolModal.tsx';
import { AnnouncementModal } from './AnnouncementModal.tsx';

interface AnnouncementsManagementViewProps {
  announcements: Announcement[];
  reads: AnnouncementRead[];
  onCreateAnnouncement: (data: {
    title: string;
    content: string;
    target_roles: AnnouncementTargetRole[];
    is_active: boolean;
  }) => void;
  onUpdateAnnouncement: (data: {
    id: number;
    title: string;
    content: string;
    target_roles: AnnouncementTargetRole[];
    is_active: boolean;
  }) => void;
  onDeleteAnnouncement: (id: number) => void;
}

export const AnnouncementsManagementView: React.FC<AnnouncementsManagementViewProps> = ({
  announcements,
  reads,
  onCreateAnnouncement,
  onUpdateAnnouncement,
  onDeleteAnnouncement
}) => {
  // Filters & State
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState<'all' | 'coach' | 'customer'>('all');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');

  // Modals
  const [protocolAnnouncement, setProtocolAnnouncement] = useState<Announcement | null>(null);
  const [editingAnnouncement, setEditingAnnouncement] = useState<Announcement | null>(null);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

  // 2-Stufen Sicherheits-Löschen (Delete confirmation state: announcementId -> countdown remaining)
  const [deleteConfirmState, setDeleteConfirmState] = useState<{ id: number; timerSeconds: number } | null>(null);

  // Timer effect for 2-step deletion
  useEffect(() => {
    if (!deleteConfirmState) return;

    if (deleteConfirmState.timerSeconds <= 0) {
      setDeleteConfirmState(null);
      return;
    }

    const interval = setInterval(() => {
      setDeleteConfirmState(prev => {
        if (!prev) return null;
        if (prev.timerSeconds <= 1) return null;
        return { ...prev, timerSeconds: prev.timerSeconds - 1 };
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [deleteConfirmState]);

  const handleDeleteClick = (id: number) => {
    if (deleteConfirmState && deleteConfirmState.id === id) {
      // 2. Klick innerhalb des Zeitfensters -> Löschen ausführen!
      onDeleteAnnouncement(id);
      setDeleteConfirmState(null);
    } else {
      // 1. Klick -> 3-Sekunden-Countdown aktivieren
      setDeleteConfirmState({ id, timerSeconds: 3 });
    }
  };

  // Filtered Announcements
  const filteredAnnouncements = announcements.filter(ann => {
    const matchesSearch =
      ann.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      ann.content.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesRole =
      roleFilter === 'all' || ann.target_roles.includes(roleFilter);

    const matchesStatus =
      statusFilter === 'all' ||
      (statusFilter === 'active' && ann.is_active) ||
      (statusFilter === 'inactive' && !ann.is_active);

    return matchesSearch && matchesRole && matchesStatus;
  });

  // Helper: Count reads for an announcement
  const getReadsForAnnouncement = (id: number) => {
    return reads.filter(r => r.announcement_id === id);
  };

  const totalReadsCount = reads.length;
  const activeAnnouncementsCount = announcements.filter(a => a.is_active).length;

  return (
    <div className="space-y-6">
      
      {/* 1. Header Banner & Schnell-Metriken */}
      <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-2xl bg-sky-50 text-sky-700 border border-sky-200 flex items-center justify-center font-bold">
              <Bell className="w-5 h-5 text-sky-600" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                  Nachrichten &amp; Mitteilungen
                </h1>
                <span className="text-[10px] bg-sky-100 text-sky-800 font-bold px-2.5 py-0.5 rounded-full border border-sky-200 uppercase tracking-wider">
                  Audit-Log
                </span>
              </div>
              <p className="text-xs text-slate-500 mt-0.5">
                Rollenbasierte Ausspielung auf Coach- &amp; Kunden-Dashboards mit revisionssicherem Leseprotokoll.
              </p>
            </div>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          {/* Metriken */}
          <div className="flex items-center gap-2 bg-slate-50 p-1.5 px-3 rounded-2xl border border-slate-200 text-xs">
            <div className="flex items-center gap-1.5 text-slate-600 font-medium">
              <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>{activeAnnouncementsCount} aktiv</span>
            </div>
            <span className="text-slate-300">|</span>
            <div className="flex items-center gap-1.5 text-slate-600 font-medium">
              <Eye className="w-3.5 h-3.5 text-sky-600" />
              <span>{totalReadsCount} Quittierungen</span>
            </div>
          </div>

          {/* Button Mitteilung erstellen */}
          <button
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
            className="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer shrink-0"
          >
            <Plus className="w-4 h-4 text-sky-200" />
            <span>+ Neue Mitteilung erstellen</span>
          </button>
        </div>
      </div>

      {/* 2. Filter- und Suchleiste */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        
        {/* Suchfeld */}
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Mitteilungen nach Titel oder Text durchsuchen..."
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium"
          />
          {searchTerm && (
            <button
              type="button"
              onClick={() => setSearchTerm('')}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          )}
        </div>

        {/* Rollenfilter-Tabs (Alle, Coaches, Kunden) */}
        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold shrink-0">
          <button
            type="button"
            onClick={() => setRoleFilter('all')}
            className={`px-3 py-1.5 rounded-lg transition cursor-pointer ${
              roleFilter === 'all'
                ? 'bg-white text-slate-900 shadow-xs font-bold'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Alle ({announcements.length})
          </button>
          <button
            type="button"
            onClick={() => setRoleFilter('coach')}
            className={`px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 ${
              roleFilter === 'coach'
                ? 'bg-sky-600 text-white shadow-xs font-bold'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <span>Coaches</span>
            <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-black/10">
              {announcements.filter(a => a.target_roles.includes('coach')).length}
            </span>
          </button>
          <button
            type="button"
            onClick={() => setRoleFilter('customer')}
            className={`px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 ${
              roleFilter === 'customer'
                ? 'bg-emerald-600 text-white shadow-xs font-bold'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <span>Kunden</span>
            <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-black/10">
              {announcements.filter(a => a.target_roles.includes('customer')).length}
            </span>
          </button>
        </div>

        {/* Status Filter */}
        <select
          value={statusFilter}
          onChange={e => setStatusFilter(e.target.value as any)}
          className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 shrink-0"
        >
          <option value="all">Status: Alle</option>
          <option value="active">Nur Aktive</option>
          <option value="inactive">Nur Inaktive</option>
        </select>

      </div>

      {/* 3. Mitteilungs-Karten / Liste */}
      {filteredAnnouncements.length === 0 ? (
        <div className="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-xs space-y-4">
          <div className="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto shadow-inner">
            <Bell className="w-7 h-7 text-slate-300" />
          </div>
          <div>
            <h3 className="text-base font-bold text-slate-800">
              Keine Mitteilungen gefunden
            </h3>
            <p className="text-xs text-slate-500 max-w-sm mx-auto mt-1">
              {searchTerm || roleFilter !== 'all' || statusFilter !== 'all'
                ? 'Keine Treffer für die gewählten Filterkriterien vorhanden.'
                : 'Erstellen Sie Ihre erste Mitteilung, um Coaches und Kunden direkt auf ihren Dashboards zu informieren.'}
            </p>
          </div>
          <button
            type="button"
            onClick={() => setIsCreateModalOpen(true)}
            className="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Jetzt Mitteilung erfassen</span>
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredAnnouncements.map(ann => {
            const annReads = getReadsForAnnouncement(ann.id);
            const isDeleting = deleteConfirmState?.id === ann.id;

            return (
              <div
                key={ann.id}
                className="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all p-5 sm:p-6 space-y-4"
              >
                {/* Obere Zeile: Status, Badges, Datum & Aktionen */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                  <div className="flex flex-wrap items-center gap-2">
                    {/* Status Badge */}
                    <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 ${
                      ann.is_active
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-slate-100 text-slate-600 border border-slate-200'
                    }`}>
                      <span className={`w-1.5 h-1.5 rounded-full ${ann.is_active ? 'bg-emerald-500' : 'bg-slate-400'}`}></span>
                      <span>{ann.is_active ? 'Aktiv auf Dashboards' : 'Inaktiv (Archiv)'}</span>
                    </span>

                    {/* Zielgruppen Badges */}
                    {ann.target_roles.includes('coach') && (
                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-sky-800 border border-sky-200">
                        COACHES
                      </span>
                    )}
                    {ann.target_roles.includes('customer') && (
                      <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                        KUNDEN
                      </span>
                    )}

                    <span className="text-xs text-slate-400 flex items-center gap-1">
                      <Clock className="w-3.5 h-3.5 text-slate-400" />
                      <span>{new Date(ann.created_at).toLocaleString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })} Uhr</span>
                    </span>
                  </div>

                  {/* Aktionen (Bearbeiten & 2-Stufen-Löschen) */}
                  <div className="flex items-center gap-2 self-start sm:self-auto">
                    <button
                      type="button"
                      onClick={() => setEditingAnnouncement(ann)}
                      className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl flex items-center gap-1.5 transition cursor-pointer"
                      title="Mitteilung bearbeiten"
                    >
                      <Edit3 className="w-3.5 h-3.5 text-slate-500" />
                      <span>Bearbeiten</span>
                    </button>

                    {/* 2-Stufen Sicherheits-Löschen */}
                    <button
                      type="button"
                      onClick={() => handleDeleteClick(ann.id)}
                      className={`px-3 py-1.5 rounded-xl font-bold text-xs transition flex items-center gap-1.5 cursor-pointer ${
                        isDeleting
                          ? 'bg-rose-600 text-white animate-pulse shadow-md ring-2 ring-rose-400'
                          : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                      }`}
                      title={isDeleting ? 'Erneut klicken zur Bestätigung!' : 'Mitteilung löschen'}
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                      <span>
                        {isDeleting
                          ? `KLICKEN ZUM LÖSCHEN (${deleteConfirmState?.timerSeconds}s)`
                          : 'Löschen'}
                      </span>
                    </button>
                  </div>
                </div>

                {/* Mitteilung Inhalt */}
                <div className="space-y-2">
                  <h3 className="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                    {ann.title}
                  </h3>
                  <div className="text-xs sm:text-sm text-slate-700 whitespace-pre-wrap leading-relaxed bg-slate-50/70 p-4 rounded-xl border border-slate-100">
                    {ann.content}
                  </div>
                </div>

                {/* Untere Leiste: Revisionssicherer Lesebestätigungs-Zähler & Audit-Link */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                  <div className="flex items-center gap-2">
                    {/* Interaktives Lesebestätigungs-Badge */}
                    <button
                      type="button"
                      onClick={() => setProtocolAnnouncement(ann)}
                      className="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100/80 text-emerald-900 font-bold text-xs border border-emerald-300 shadow-2xs transition flex items-center gap-2 cursor-pointer group"
                      title="Klicken, um das revisionssichere Detail-Leseprotokoll zu öffnen"
                    >
                      <Eye className="w-3.5 h-3.5 text-emerald-600 group-hover:scale-110 transition-transform" />
                      <span>
                        {annReads.length === 0
                          ? '0 Lesebestätigungen'
                          : `${annReads.length} Person(en) quittiert`}
                      </span>
                      <span className="text-[10px] bg-emerald-200/80 text-emerald-900 px-1.5 py-0.2 rounded font-bold uppercase">
                        Protokoll öffnen &rarr;
                      </span>
                    </button>
                  </div>

                  <div className="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <ShieldCheck className="w-3.5 h-3.5 text-slate-400" />
                    <span>ID #{ann.id} &bull; Autor: {ann.author_name || 'Admin'}</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* 4. Modals */}
      {/* Detail-Leseprotokoll Modal */}
      {protocolAnnouncement && (
        <ReadProtocolModal
          announcement={protocolAnnouncement}
          reads={getReadsForAnnouncement(protocolAnnouncement.id)}
          onClose={() => setProtocolAnnouncement(null)}
        />
      )}

      {/* Erstellen Modal */}
      {isCreateModalOpen && (
        <AnnouncementModal
          onSave={data => {
            onCreateAnnouncement(data);
            setIsCreateModalOpen(false);
          }}
          onClose={() => setIsCreateModalOpen(false)}
        />
      )}

      {/* Bearbeiten Modal */}
      {editingAnnouncement && (
        <AnnouncementModal
          announcement={editingAnnouncement}
          onSave={data => {
            if (data.id) {
              onUpdateAnnouncement({
                id: data.id,
                title: data.title,
                content: data.content,
                target_roles: data.target_roles,
                is_active: data.is_active
              });
            }
            setEditingAnnouncement(null);
          }}
          onClose={() => setEditingAnnouncement(null)}
        />
      )}

    </div>
  );
};
