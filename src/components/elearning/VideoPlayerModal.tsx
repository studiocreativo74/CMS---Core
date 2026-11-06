import React from 'react';
import {
  Play,
  Pause,
  X,
  RotateCcw,
  Volume2,
  CheckCircle2,
  Film,
  Sparkles,
  Check
} from 'lucide-react';
import { ElearningVideo } from '../../types/index.ts';

interface VideoPlayerModalProps {
  video: ElearningVideo;
  onClose: () => void;
  isPlaying: boolean;
  setIsPlaying: React.Dispatch<React.SetStateAction<boolean>>;
  progress: number;
  setProgress: React.Dispatch<React.SetStateAction<number>>;
  speed: number;
  setSpeed: React.Dispatch<React.SetStateAction<number>>;
  volume: number;
  setVolume: React.Dispatch<React.SetStateAction<number>>;
  completedVideoIds: number[];
  setCompletedVideoIds: React.Dispatch<React.SetStateAction<number[]>>;
}

export const VideoPlayerModal: React.FC<VideoPlayerModalProps> = ({
  video,
  onClose,
  isPlaying,
  setIsPlaying,
  progress,
  setProgress,
  speed,
  setSpeed,
  volume,
  setVolume,
  completedVideoIds,
  setCompletedVideoIds
}) => {
  const isDone = completedVideoIds.includes(video.id);

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
      <div className="bg-slate-900 rounded-3xl max-w-4xl w-full border border-slate-800 shadow-2xl text-white my-auto overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        {/* Header Bar */}
        <div className="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between gap-4 bg-slate-950/70">
          <div className="flex items-center gap-2.5 overflow-hidden">
            <span className="w-9 h-9 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0 border border-cyan-500/30">
              <Play className="w-4 h-4 fill-current ml-0.5" />
            </span>
            <div className="truncate">
              <div className="flex items-center gap-2">
                <span className="text-[10px] font-bold uppercase tracking-wider text-cyan-400">
                  {video.categoryLabel}
                </span>
                <span className="text-slate-600">&bull;</span>
                <span className="text-[10px] text-slate-400">
                  {video.difficulty}
                </span>
              </div>
              <h3 className="font-extrabold text-white text-sm sm:text-base truncate">
                {video.title}
              </h3>
            </div>
          </div>

          <div className="flex items-center gap-2 shrink-0">
            <button
              type="button"
              onClick={() => {
                setCompletedVideoIds(prev =>
                  isDone ? prev.filter(id => id !== video.id) : [...prev, video.id]
                );
              }}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer ${
                isDone
                  ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                  : 'bg-slate-800 hover:bg-slate-700 text-slate-300'
              }`}
            >
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">
                {isDone ? 'Abgeschlossen ✓' : 'Als erledigt markieren'}
              </span>
            </button>

            <button
              type="button"
              onClick={onClose}
              className="p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white transition cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Video Canvas / Player Screen */}
        <div className="relative aspect-video bg-black flex items-center justify-center overflow-hidden group">
          <img
            src={video.thumbnail}
            alt={video.title}
            className={`w-full h-full object-cover transition duration-700 ${
              isPlaying ? 'scale-105 opacity-80' : 'opacity-65'
            }`}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-black/30 pointer-events-none" />

          {/* Live Playback Glow Effect */}
          {isPlaying && (
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="w-36 h-36 rounded-full bg-cyan-500/10 animate-ping" />
              <div className="absolute top-4 left-4 px-2.5 py-1 rounded-full bg-black/60 border border-cyan-500/40 text-[10px] text-cyan-300 font-mono flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                <span>HD 1080p &bull; {speed}x</span>
              </div>
            </div>
          )}

          {/* Center Play/Pause Click Overlay */}
          <button
            type="button"
            onClick={() => setIsPlaying(!isPlaying)}
            className="absolute w-16 h-16 rounded-full bg-black/60 hover:bg-cyan-500 text-white hover:text-slate-950 flex items-center justify-center transition-all backdrop-blur-sm border border-white/20 group-hover:scale-110 cursor-pointer shadow-2xl"
          >
            {isPlaying ? (
              <Pause className="w-7 h-7 fill-current" />
            ) : (
              <Play className="w-7 h-7 fill-current ml-1" />
            )}
          </button>

          {/* Player Bottom Control Bar */}
          <div className="absolute bottom-0 inset-x-0 p-3 sm:p-4 bg-gradient-to-t from-black/95 via-black/80 to-transparent space-y-2">
            {/* Timeline Scrubber Bar */}
            <div className="flex items-center gap-3">
              <input
                type="range"
                min="0"
                max="100"
                value={progress}
                onChange={e => setProgress(Number(e.target.value))}
                className="w-full h-1.5 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-cyan-400"
              />
            </div>

            <div className="flex items-center justify-between text-xs text-slate-300">
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => setIsPlaying(!isPlaying)}
                  className="p-1 hover:text-white transition cursor-pointer"
                >
                  {isPlaying ? <Pause className="w-4 h-4 fill-current" /> : <Play className="w-4 h-4 fill-current" />}
                </button>

                <button
                  type="button"
                  onClick={() => setProgress(p => Math.max(0, p - 7))}
                  className="hover:text-white transition flex items-center gap-0.5 text-[11px] cursor-pointer"
                  title="10 Sekunden zurück"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>-10s</span>
                </button>

                <button
                  type="button"
                  onClick={() => setProgress(p => Math.min(100, p + 7))}
                  className="hover:text-white transition flex items-center gap-0.5 text-[11px] cursor-pointer"
                  title="10 Sekunden vor"
                >
                  <RotateCcw className="w-3.5 h-3.5 -scale-x-100" />
                  <span>+10s</span>
                </button>

                <span className="font-mono text-[11px] text-slate-400">
                  {Math.floor((video.durationSeconds * (progress / 100)) / 60)
                    .toString()
                    .padStart(2, '0')}:
                  {Math.floor((video.durationSeconds * (progress / 100)) % 60)
                    .toString()
                    .padStart(2, '0')}{' '}
                  / {video.duration}
                </span>
              </div>

              <div className="flex items-center gap-3">
                {/* Geschwindigkeit */}
                <div className="flex items-center gap-1 text-[10px] font-bold">
                  {[1, 1.25, 1.5].map(spd => (
                    <button
                      key={spd}
                      type="button"
                      onClick={() => setSpeed(spd)}
                      className={`px-1.5 py-0.5 rounded transition cursor-pointer ${
                        speed === spd
                          ? 'bg-cyan-500 text-slate-950'
                          : 'bg-slate-800 text-slate-400 hover:text-white'
                      }`}
                    >
                      {spd}x
                    </button>
                  ))}
                </div>

                {/* Lautstärke */}
                <div className="hidden sm:flex items-center gap-1.5">
                  <Volume2 className="w-3.5 h-3.5 text-slate-400" />
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={volume}
                    onChange={e => setVolume(Number(e.target.value))}
                    className="w-16 h-1 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-cyan-400"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Chapters & Content Area */}
        <div className="p-5 sm:p-6 space-y-5 max-h-[36vh] overflow-y-auto bg-slate-900">
          
          {/* Kapitelauswahl (Interactive Chapter Markers) */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5 flex items-center gap-1.5">
              <Film className="w-3.5 h-3.5 text-cyan-400" />
              <span>Kapitelübersicht (Direktsprung):</span>
            </h4>

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
              {video.chapters.map((chap, idx) => {
                const chapterProgressPercent = (chap.seconds / video.durationSeconds) * 100;
                return (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => {
                      setProgress(chapterProgressPercent);
                      setIsPlaying(true);
                    }}
                    className="p-2.5 bg-slate-950/70 hover:bg-slate-800 border border-slate-800 rounded-xl text-left flex items-start gap-2.5 transition cursor-pointer group"
                  >
                    <span className="font-mono text-[10px] text-cyan-400 font-bold px-1.5 py-0.5 bg-cyan-950/80 rounded border border-cyan-800/40 shrink-0 mt-0.5">
                      {chap.time}
                    </span>
                    <span className="text-xs text-slate-300 group-hover:text-white font-medium line-clamp-1">
                      {chap.title}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Prüfungs-Schlüsselerkenntnisse & Gesetz */}
          <div className="p-4 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-2">
            <div className="flex items-center justify-between text-xs font-bold text-amber-400">
              <span className="flex items-center gap-1.5">
                <Sparkles className="w-3.5 h-3.5" />
                Amtliche Prüfungsschwerpunkte für dieses Manöver:
              </span>
              {video.bsvRef && (
                <span className="text-[10px] font-mono text-slate-400 font-normal">
                  {video.bsvRef}
                </span>
              )}
            </div>

            <ul className="space-y-1.5 text-xs text-slate-300">
              {video.keyTakeaways.map((tip, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <Check className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>{tip}</span>
                </li>
              ))}
            </ul>
          </div>

        </div>

      </div>
    </div>
  );
};
