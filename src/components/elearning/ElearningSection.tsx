import React from 'react';
import {
  Film,
  Layers,
  HelpCircle,
  FileDown,
  GraduationCap,
  CheckCircle2,
  RefreshCw,
  CreditCard,
  Lock,
  Award,
  Clock,
  Play,
  Check,
  Sparkles,
  Maximize2,
  Eye,
  RotateCcw,
  FileText,
  Download
} from 'lucide-react';
import {
  ElearningVideo,
  ElearningChart,
  ServiceOffer
} from '../../types/index.ts';
import { INITIAL_ELEARNING_QUIZ } from '../../data/mockData.ts';

interface ElearningSectionProps {
  hasDigitalAccess: boolean;
  daysRemainingDigital: number;
  elearningVideos: ElearningVideo[];
  elearningCharts: ElearningChart[];
  completedVideoIds: number[];
  setCompletedVideoIds: React.Dispatch<React.SetStateAction<number[]>>;
  elearningCategoryFilter: 'all' | 'maneuver' | 'safety' | 'physics' | 'knots' | 'navigation';
  setElearningCategoryFilter: React.Dispatch<React.SetStateAction<'all' | 'maneuver' | 'safety' | 'physics' | 'knots' | 'navigation'>>;
  elearningActiveTab: 'videos' | 'charts' | 'quiz' | 'downloads';
  setElearningActiveTab: React.Dispatch<React.SetStateAction<'videos' | 'charts' | 'quiz' | 'downloads'>>;
  setActiveVideoModal: React.Dispatch<React.SetStateAction<ElearningVideo | null>>;
  setActiveVideoPlaying: React.Dispatch<React.SetStateAction<boolean>>;
  setActiveChartModal: React.Dispatch<React.SetStateAction<ElearningChart | null>>;
  quizAnswers: Record<number, number>;
  setQuizAnswers: React.Dispatch<React.SetStateAction<Record<number, number>>>;
  quizSubmitted: boolean;
  setQuizSubmitted: React.Dispatch<React.SetStateAction<boolean>>;
  demoElearningAccessOverride: boolean | null;
  setDemoElearningAccessOverride: React.Dispatch<React.SetStateAction<boolean | null>>;
  onOpenCheckout: (service: ServiceOffer) => void;
  services: ServiceOffer[];
  setMagicFeedback: (msg: string | null) => void;
}

export const ElearningSection: React.FC<ElearningSectionProps> = ({
  hasDigitalAccess,
  daysRemainingDigital,
  elearningVideos,
  elearningCharts,
  completedVideoIds,
  setCompletedVideoIds,
  elearningCategoryFilter,
  setElearningCategoryFilter,
  elearningActiveTab,
  setElearningActiveTab,
  setActiveVideoModal,
  setActiveVideoPlaying,
  setActiveChartModal,
  quizAnswers,
  setQuizAnswers,
  quizSubmitted,
  setQuizSubmitted,
  demoElearningAccessOverride,
  setDemoElearningAccessOverride,
  onOpenCheckout,
  services,
  setMagicFeedback
}) => {
  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      
      {/* Top Dark Navy Academy Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-7 rounded-3xl text-white shadow-lg border border-indigo-900/50 flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative overflow-hidden">
        <div className="absolute -right-16 -top-16 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute right-40 -bottom-16 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none" />

        <div className="space-y-2.5 relative z-10 max-w-2xl">
          <div className="flex flex-wrap items-center gap-2">
            <span className="px-3 py-1 rounded-full text-xs font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-400/30 flex items-center gap-1.5">
              <GraduationCap className="w-3.5 h-3.5" />
              Nautic Academy &bull; Online-Campus
            </span>

            <span className={`px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 ${
              hasDigitalAccess
                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30'
                : 'bg-amber-500/20 text-amber-300 border border-amber-400/30'
            }`}>
              <CheckCircle2 className="w-3.5 h-3.5" />
              {hasDigitalAccess
                ? `1-Jahres-Abo aktiv &bull; Noch ${daysRemainingDigital} Tage`
                : 'Vorschau / Kein aktives Abo'}
            </span>
          </div>

          <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
            <span>Digitales Theorie- &amp; Videopaket</span>
          </h2>

          <p className="text-slate-300 text-xs sm:text-sm leading-relaxed">
            Das offizielle digitale Lern- und Vorbereitungspaket für die Schiffsführerprüfung Kat. A &amp; D:
            HD-Manöverlehrfilme, interaktive amtliche Bildtafeln (Lichterführung, Seezeichen, Sturmwarnung) und prüfungsnaher ASA-Theorietrainer.
          </p>
        </div>

        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 relative z-10 shrink-0">
          {/* Demo-Status Toggle für Testzwecke */}
          <button
            type="button"
            onClick={() => {
              const next = demoElearningAccessOverride === null ? !hasDigitalAccess : !demoElearningAccessOverride;
              setDemoElearningAccessOverride(next);
              setMagicFeedback(next ? 'Test-Status: 1-Jahres-Abo manuell als AKTIV gesetzt.' : 'Test-Status: 1-Jahres-Abo als INAKTIV (Paywall-Vorschau) gesetzt.');
              setTimeout(() => setMagicFeedback(null), 4000);
            }}
            className="px-3.5 py-2 bg-slate-800/90 hover:bg-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-semibold border border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
            title="Simuliert gesperrten oder freigeschalteten Zugriff für Testzwecke"
          >
            <RefreshCw className="w-3.5 h-3.5" />
            <span>Abo-Status testen: {hasDigitalAccess ? 'Aktiv ✓' : 'Gesperrt 🔒'}</span>
          </button>

          {!hasDigitalAccess && (
            <button
              type="button"
              onClick={() => {
                const digitalService = services.find(s => s.offer_type === 'digital') || services[0];
                onOpenCheckout(digitalService);
              }}
              className="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-md cursor-pointer"
            >
              <CreditCard className="w-4 h-4 text-slate-950" />
              <span>Jetzt für CHF 149.- abonnieren</span>
            </button>
          )}
        </div>
      </div>

      {/* Paywall Banner falls kein Abo aktiv */}
      {!hasDigitalAccess && (
        <div className="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 rounded-2xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-start gap-3.5">
            <div className="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-600 flex items-center justify-center shrink-0 mt-0.5 border border-amber-500/30">
              <Lock className="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <h4 className="font-bold text-slate-900 text-sm">
                365 Tage Vollzugriff sichern – Nautic Academy Jahres-Abo
              </h4>
              <p className="text-xs text-slate-600 mt-0.5 leading-relaxed">
                Schalten Sie alle 6 HD-Lehrfilme in voller Länge, alle 5 hochauflösenden Schifffahrts-Bildtafeln und den interaktiven ASA-Theorietrainer für 1 volles Jahr frei.
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2 self-start md:self-auto shrink-0">
            <button
              type="button"
              onClick={() => {
                const digitalService = services.find(s => s.offer_type === 'digital') || services[0];
                onOpenCheckout(digitalService);
              }}
              className="px-4 py-2 bg-[#2d2421] hover:bg-[#3d322e] text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer"
            >
              <Award className="w-3.5 h-3.5 text-amber-400" />
              <span>Abo für CHF 149.00 / 1 Jahr buchen</span>
            </button>
          </div>
        </div>
      )}

      {/* Inner Academy Navigation Tabs */}
      <div className="bg-white p-2 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-1.5 text-xs font-bold">
          <button
            type="button"
            onClick={() => setElearningActiveTab('videos')}
            className={`px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer ${
              elearningActiveTab === 'videos'
                ? 'bg-[#2d2421] text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
          >
            <Film className="w-4 h-4 text-cyan-400" />
            <span>Lehrfilme &amp; Manöver ({elearningVideos.length})</span>
          </button>

          <button
            type="button"
            onClick={() => setElearningActiveTab('charts')}
            className={`px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer ${
              elearningActiveTab === 'charts'
                ? 'bg-[#2d2421] text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
          >
            <Layers className="w-4 h-4 text-amber-400" />
            <span>Schifffahrts-Bildtafeln ({elearningCharts.length})</span>
          </button>

          <button
            type="button"
            onClick={() => setElearningActiveTab('quiz')}
            className={`px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer ${
              elearningActiveTab === 'quiz'
                ? 'bg-[#2d2421] text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
          >
            <HelpCircle className="w-4 h-4 text-emerald-500" />
            <span>ASA-Theorietrainer</span>
          </button>

          <button
            type="button"
            onClick={() => setElearningActiveTab('downloads')}
            className={`px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer ${
              elearningActiveTab === 'downloads'
                ? 'bg-[#2d2421] text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
          >
            <FileDown className="w-4 h-4 text-sky-500" />
            <span>Merkblätter &amp; Downloads</span>
          </button>
        </div>

        {/* Academy Meta-Info */}
        <div className="px-3 py-1 bg-slate-50 rounded-xl border border-slate-200 text-[11px] font-semibold text-slate-600 hidden md:flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Offizieller Standard Schifffahrtsamt Kat. A &amp; D</span>
        </div>
      </div>

      {/* ============================================================== */}
      {/* TAB 1: LEHRFILME & MANÖVER-VIDEOTHEK                           */}
      {/* ============================================================== */}
      {elearningActiveTab === 'videos' && (
        <div className="space-y-6">
          
          {/* Fortschritt & Filterleiste */}
          <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex flex-wrap items-center gap-1.5 overflow-x-auto">
              {[
                { id: 'all', label: 'Alle Videos' },
                { id: 'maneuver', label: 'Hafenmanöver' },
                { id: 'safety', label: 'Sicherheit & Notfall' },
                { id: 'physics', label: 'Bootsphysik' },
                { id: 'knots', label: 'Schifferknoten' },
                { id: 'navigation', label: 'Navigation & Wetter' }
              ].map(f => (
                <button
                  key={f.id}
                  type="button"
                  onClick={() => setElearningCategoryFilter(f.id as any)}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer ${
                    elearningCategoryFilter === f.id
                      ? 'bg-[#2d2421] text-white shadow-xs'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  {f.label}
                </button>
              ))}
            </div>

            {/* Video-Lernfortschritt */}
            <div className="flex items-center gap-3 shrink-0">
              <div className="text-right">
                <div className="text-xs font-bold text-slate-900">
                  Lernfortschritt: {completedVideoIds.length} von {elearningVideos.length} abgeschlossen
                </div>
                <div className="text-[10px] text-slate-500">
                  {Math.round((completedVideoIds.length / elearningVideos.length) * 100)}% der Pflichtlektionen
                </div>
              </div>
              <div className="w-24 h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                <div
                  className="h-full bg-cyan-600 transition-all duration-300"
                  style={{ width: `${(completedVideoIds.length / elearningVideos.length) * 100}%` }}
                />
              </div>
            </div>
          </div>

          {/* Video Cards Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {elearningVideos
              .filter(v => elearningCategoryFilter === 'all' || v.category === elearningCategoryFilter)
              .map(vid => {
                const isCompleted = completedVideoIds.includes(vid.id);
                return (
                  <div
                    key={vid.id}
                    className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-md transition group"
                  >
                    <div>
                      {/* Thumbnail & Video-Overlay */}
                      <div
                        className="relative h-48 overflow-hidden bg-slate-900 cursor-pointer"
                        onClick={() => {
                          if (hasDigitalAccess) {
                            setActiveVideoModal(vid);
                            setActiveVideoPlaying(true);
                          } else {
                            setMagicFeedback('Dieses Lehrvideo ist Teil des 1-Jahres-Abos. Bitte aktivieren Sie Ihr Paket!');
                            setTimeout(() => setMagicFeedback(null), 4000);
                          }
                        }}
                      >
                        <img
                          src={vid.thumbnail}
                          alt={vid.title}
                          className="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-85"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />

                        {/* Play Button Overlay */}
                        <div className="absolute inset-0 flex items-center justify-center">
                          <div className={`w-14 h-14 rounded-full flex items-center justify-center transition-all ${
                            hasDigitalAccess
                              ? 'bg-cyan-500/90 text-slate-950 group-hover:scale-110 shadow-lg group-hover:bg-cyan-400'
                              : 'bg-black/70 text-amber-400 border border-amber-400/50'
                          }`}>
                            {hasDigitalAccess ? (
                              <Play className="w-6 h-6 fill-current ml-0.5" />
                            ) : (
                              <Lock className="w-6 h-6" />
                            )}
                          </div>
                        </div>

                        {/* Badges oben */}
                        <div className="absolute top-3 inset-x-3 flex items-center justify-between">
                          <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-black/70 backdrop-blur-sm text-cyan-300 border border-cyan-500/30 uppercase tracking-wider">
                            {vid.categoryLabel}
                          </span>

                          <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-black/70 backdrop-blur-sm text-white flex items-center gap-1">
                            <Clock className="w-3 h-3 text-slate-400" />
                            <span>{vid.duration}</span>
                          </span>
                        </div>

                        {/* Unten: Status & Schwierigkeit */}
                        <div className="absolute bottom-3 inset-x-3 flex items-center justify-between">
                          <span className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${
                            vid.difficulty === 'Einsteiger'
                              ? 'bg-emerald-500/30 text-emerald-200 border border-emerald-400/40'
                              : vid.difficulty === 'Fortgeschritten'
                              ? 'bg-amber-500/30 text-amber-200 border border-amber-400/40'
                              : 'bg-rose-500/30 text-rose-200 border border-rose-400/40'
                          }`}>
                            {vid.difficulty}
                          </span>

                          {isCompleted ? (
                            <span className="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500 text-white flex items-center gap-1 shadow-xs">
                              <Check className="w-3 h-3" />
                              <span>Abgeschlossen</span>
                            </span>
                          ) : (
                            <span className="text-[10px] text-slate-300 font-medium">
                              {vid.chapters.length} Kapitel
                            </span>
                          )}
                        </div>
                      </div>

                      {/* Info Body */}
                      <div className="p-5 space-y-3">
                        <h4 className="text-base font-bold text-slate-900 group-hover:text-cyan-800 transition line-clamp-2">
                          {vid.title}
                        </h4>

                        <p className="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                          {vid.description}
                        </p>

                        {/* Key Takeaways Preview */}
                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 text-[11px] text-slate-700 space-y-1">
                          <div className="font-bold text-[10px] uppercase text-slate-500 flex items-center gap-1">
                            <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                            <span>Wichtigste Prüfungsregel:</span>
                          </div>
                          <p className="text-slate-600 italic">
                            &ldquo;{vid.keyTakeaways[0]}&rdquo;
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Footer Action */}
                    <div className="p-5 pt-0 flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          if (hasDigitalAccess) {
                            setActiveVideoModal(vid);
                            setActiveVideoPlaying(true);
                          } else {
                            setMagicFeedback('Bitte abonnieren Sie das 1-Jahres-Paket für CHF 149.-, um die Lehrfilme abzuspielen.');
                            setTimeout(() => setMagicFeedback(null), 4000);
                          }
                        }}
                        className={`w-full py-2.5 font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-xs cursor-pointer ${
                          hasDigitalAccess
                            ? 'bg-[#2d2421] hover:bg-[#3d322e] text-white'
                            : 'bg-amber-100 text-amber-900 hover:bg-amber-200'
                        }`}
                      >
                        {hasDigitalAccess ? (
                          <>
                            <Play className="w-3.5 h-3.5 text-cyan-400" />
                            <span>Video ansehen</span>
                          </>
                        ) : (
                          <>
                            <Lock className="w-3.5 h-3.5" />
                            <span>Im 1-Jahres-Abo enthalten</span>
                          </>
                        )}
                      </button>

                      {hasDigitalAccess && (
                        <button
                          type="button"
                          onClick={() => {
                            setCompletedVideoIds(prev =>
                              prev.includes(vid.id) ? prev.filter(id => id !== vid.id) : [...prev, vid.id]
                            );
                          }}
                          className={`p-2.5 rounded-xl border transition cursor-pointer ${
                            isCompleted
                              ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                              : 'bg-slate-50 text-slate-400 hover:text-slate-700 border-slate-200'
                          }`}
                          title={isCompleted ? 'Als ungesehen markieren' : 'Als abgeschlossen markieren'}
                        >
                          <CheckCircle2 className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
          </div>

        </div>
      )}

      {/* ============================================================== */}
      {/* TAB 2: INTERAKTIVE SCHIFFFAHRTS-BILDTAFELN                     */}
      {/* ============================================================== */}
      {elearningActiveTab === 'charts' && (
        <div className="space-y-6">
          <div className="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 className="font-bold text-slate-900 text-base">
                Amtliche Schifffahrts-Bildtafeln &amp; Signale (BSV)
              </h3>
              <p className="text-xs text-slate-500 mt-0.5">
                Klicken Sie auf eine Bildtafel für hochauflösenden Zoom, Detailansichten und Prüfungs-Kommentare.
              </p>
            </div>
            <div className="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-900 text-xs font-bold border border-amber-200 flex items-center gap-1.5">
              <Layers className="w-4 h-4 text-amber-600" />
              <span>5 Bildtafeln verfügbar</span>
            </div>
          </div>

          {/* Bildtafeln Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {elearningCharts.map(chart => (
              <div
                key={chart.id}
                className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-md transition group"
              >
                <div>
                  {/* Bildvorschau mit Zoom-Aufforderung */}
                  <div
                    className="relative h-52 bg-slate-900 overflow-hidden cursor-pointer"
                    onClick={() => setActiveChartModal(chart)}
                  >
                    <img
                      src={chart.imageUrl}
                      alt={chart.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-90"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent" />

                    <div className="absolute top-3 left-3">
                      <span className="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 uppercase tracking-wider shadow-sm">
                        {chart.bsvArticle}
                      </span>
                    </div>

                    <div className="absolute top-3 right-3">
                      <span className="w-8 h-8 rounded-full bg-black/60 backdrop-blur-sm text-white flex items-center justify-center group-hover:bg-cyan-500 group-hover:text-slate-950 transition">
                        <Maximize2 className="w-4 h-4" />
                      </span>
                    </div>

                    <div className="absolute bottom-3 inset-x-3 text-white">
                      <div className="text-[10px] uppercase font-bold text-cyan-300">
                        {chart.category}
                      </div>
                      <div className="font-bold text-sm leading-tight mt-0.5">
                        {chart.title}
                      </div>
                    </div>
                  </div>

                  {/* Key Points Body */}
                  <div className="p-5 space-y-3 text-xs">
                    <p className="text-slate-600 leading-relaxed text-xs">
                      {chart.description}
                    </p>

                    <div className="space-y-1.5 pt-1">
                      <div className="font-bold text-slate-900 text-[11px] uppercase tracking-wider">
                        Wichtige Merkpunkte:
                      </div>
                      <ul className="space-y-1 text-slate-600">
                        {chart.keyPoints.slice(0, 3).map((pt, idx) => (
                          <li key={idx} className="flex items-start gap-1.5 text-[11px]">
                            <Check className="w-3.5 h-3.5 text-cyan-600 shrink-0 mt-0.5" />
                            <span>{pt}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>

                <div className="p-5 pt-0">
                  <button
                    type="button"
                    onClick={() => setActiveChartModal(chart)}
                    className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <Eye className="w-3.5 h-3.5 text-slate-600" />
                    <span>Bildtafel im Grossformat öffnen</span>
                  </button>
                </div>
              </div>
            ))}
          </div>

        </div>
      )}

      {/* ============================================================== */}
      {/* TAB 3: INTERAKTIVER ASA-THEORIETRAINER                         */}
      {/* ============================================================== */}
      {elearningActiveTab === 'quiz' && (
        <div className="space-y-6">
          <div className="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <div className="flex items-center gap-2">
                <h3 className="font-bold text-slate-900 text-base">
                  Interaktiver ASA-Theorie &amp; Prüfungsfragentrainer
                </h3>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                  Kat. A &amp; D
                </span>
              </div>
              <p className="text-xs text-slate-500 mt-0.5">
                Testen Sie Ihr Wissen mit offiziellen Prüfungsfragen der Vereinigung der Schifffahrtsämter (ASA).
              </p>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => {
                  setQuizAnswers({});
                  setQuizSubmitted(false);
                }}
                className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                <span>Fragen zurücksetzen</span>
              </button>
            </div>
          </div>

          {/* Quiz Questions List */}
          <div className="space-y-5">
            {INITIAL_ELEARNING_QUIZ.map((q, qIndex) => {
              const selectedOpt = quizAnswers[q.id];
              const hasSelected = selectedOpt !== undefined;
              const isCorrect = hasSelected && selectedOpt === q.correctIndex;

              return (
                <div
                  key={q.id}
                  className={`bg-white rounded-2xl p-6 border shadow-xs transition space-y-4 ${
                    quizSubmitted
                      ? isCorrect
                        ? 'border-emerald-300 bg-emerald-50/20'
                        : 'border-rose-300 bg-rose-50/20'
                      : 'border-slate-200'
                  }`}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                      <span className="w-7 h-7 rounded-lg bg-[#2d2421] text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                        {qIndex + 1}
                      </span>
                      <div>
                        <h4 className="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                          {q.question}
                        </h4>
                        <span className="text-[10px] text-slate-400 font-mono mt-0.5 block">
                          Referenz: {q.lawRef}
                        </span>
                      </div>
                    </div>

                    {quizSubmitted && (
                      <span className={`px-2.5 py-1 rounded-full text-xs font-bold shrink-0 ${
                        isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                      }`}>
                        {isCorrect ? 'Richtig ✓' : 'Falsch ✗'}
                      </span>
                    )}
                  </div>

                  {/* Options */}
                  <div className="space-y-2 pt-1">
                    {q.options.map((opt, optIndex) => {
                      const isOptionChosen = selectedOpt === optIndex;
                      const isOptionTheCorrectOne = optIndex === q.correctIndex;

                      let optStyles = 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100';
                      if (quizSubmitted) {
                        if (isOptionTheCorrectOne) {
                          optStyles = 'bg-emerald-100/80 border-emerald-400 text-emerald-900 font-bold';
                        } else if (isOptionChosen && !isOptionTheCorrectOne) {
                          optStyles = 'bg-rose-100/80 border-rose-400 text-rose-900 font-bold';
                        }
                      } else if (isOptionChosen) {
                        optStyles = 'bg-amber-50 border-amber-500 text-amber-950 font-bold shadow-xs';
                      }

                      return (
                        <label
                          key={optIndex}
                          onClick={() => {
                            if (!quizSubmitted) {
                              setQuizAnswers(prev => ({ ...prev, [q.id]: optIndex }));
                            }
                          }}
                          className={`p-3.5 rounded-xl border flex items-center justify-between gap-3 text-xs cursor-pointer transition ${optStyles}`}
                        >
                          <div className="flex items-center gap-3">
                            <div className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 ${
                              isOptionChosen
                                ? 'border-amber-600 bg-amber-600 text-white'
                                : 'border-slate-300 bg-white'
                            }`}>
                              {isOptionChosen && <span className="w-2 h-2 rounded-full bg-white" />}
                            </div>
                            <span>{opt}</span>
                          </div>

                          {quizSubmitted && isOptionTheCorrectOne && (
                            <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                          )}
                        </label>
                      );
                    })}
                  </div>

                  {/* Erklärung nach Auswertung */}
                  {quizSubmitted && (
                    <div className={`p-4 rounded-xl text-xs space-y-1 ${
                      isCorrect ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : 'bg-rose-50 border border-rose-200 text-rose-900'
                    }`}>
                      <div className="font-bold flex items-center gap-1.5">
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>Amtliche Gesetzeserklärung:</span>
                      </div>
                      <p className="leading-relaxed">
                        {q.explanation}
                      </p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>

          {/* Auswertungs-Button */}
          <div className="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="text-xs text-slate-600">
              {Object.keys(quizAnswers).length} von {INITIAL_ELEARNING_QUIZ.length} Fragen beantwortet.
            </div>

            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => {
                  setQuizSubmitted(true);
                  const correctCount = INITIAL_ELEARNING_QUIZ.filter(q => quizAnswers[q.id] === q.correctIndex).length;
                  setMagicFeedback(`Auswertung abgeschlossen: ${correctCount} von ${INITIAL_ELEARNING_QUIZ.length} Fragen richtig beantwortet!`);
                  setTimeout(() => setMagicFeedback(null), 5000);
                }}
                disabled={Object.keys(quizAnswers).length === 0}
                className={`px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer ${
                  Object.keys(quizAnswers).length === 0
                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                    : 'bg-[#2d2421] hover:bg-[#3d322e] text-white'
                }`}
              >
                <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                <span>Fragen jetzt auswerten</span>
              </button>
            </div>
          </div>

        </div>
      )}

      {/* ============================================================== */}
      {/* TAB 4: MERKBLÄTTER & DOWNLOADS (PDF)                           */}
      {/* ============================================================== */}
      {elearningActiveTab === 'downloads' && (
        <div className="space-y-6">
          <div className="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <h3 className="font-bold text-slate-900 text-base">
              Offizielle Merkblätter, Notfallkarten &amp; PDF-Checklisten
            </h3>
            <p className="text-xs text-slate-500 mt-0.5">
              Laden Sie alle Prüfungshilfen und Notfallunterlagen als druckfertige Dokumente auf Ihr Gerät herunter.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            {[
              {
                title: 'Notfallkarte & Funkkanäle Vierwaldstättersee (PDF)',
                desc: 'Wichtige Seerettungs-Telefonnummern, UKW-Funkkanal 16, Hafenmeister-Direktwahl & Notliegeplätze.',
                size: '1.2 MB &bull; PDF',
                category: 'Notfall & Sicherheit'
              },
              {
                title: 'Prüfungsknoten-Spickzettel & Klampenschlag (PDF)',
                desc: 'Schritt-für-Schritt Bildanleitung für alle 6 praktischen Prüfungsknoten nach Schweizer Standard.',
                size: '2.8 MB &bull; PDF',
                category: 'Seemannschaft'
              },
              {
                title: 'Checkliste vor dem Ablegen & Bootsübernahme (PDF)',
                desc: 'Vollständige Kontrollliste: 0.0‰, Ausrüstung, Bilge, Motoröl, Treibstoff & Notfallkontakt am Steg.',
                size: '850 KB &bull; PDF',
                category: 'Bootsmiete & Fahrschule'
              }
            ].map((doc, idx) => (
              <div
                key={idx}
                className="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between hover:shadow-md transition space-y-4"
              >
                <div className="space-y-2">
                  <div className="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center">
                    <FileText className="w-5 h-5" />
                  </div>
                  <span className="text-[10px] font-bold uppercase text-cyan-700 tracking-wider">
                    {doc.category}
                  </span>
                  <h4 className="font-bold text-slate-900 text-sm leading-snug">
                    {doc.title}
                  </h4>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    {doc.desc}
                  </p>
                </div>

                <div className="pt-3 border-t border-slate-100 flex items-center justify-between">
                  <span className="text-[11px] text-slate-400 font-mono">{doc.size}</span>
                  <button
                    type="button"
                    onClick={() => {
                      setMagicFeedback(`Dokument "${doc.title}" wurde erfolgreich heruntergeladen.`);
                      setTimeout(() => setMagicFeedback(null), 4000);
                    }}
                    className="px-3 py-1.5 bg-[#2d2421] hover:bg-[#3d322e] text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
                  >
                    <Download className="w-3.5 h-3.5 text-amber-400" />
                    <span>PDF laden</span>
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

    </div>
  );
};
