import React from 'react';
import {
  Layers,
  X,
  CheckCircle2
} from 'lucide-react';
import { ElearningChart } from '../../types/index.ts';

interface ChartZoomModalProps {
  chart: ElearningChart;
  onClose: () => void;
}

export const ChartZoomModal: React.FC<ChartZoomModalProps> = ({ chart, onClose }) => {
  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
      <div className="bg-white rounded-3xl max-w-3xl w-full border border-slate-200 shadow-2xl text-slate-900 my-auto overflow-hidden animate-in fade-in zoom-in-95 duration-200 space-y-4">
        
        {/* Header */}
        <div className="p-5 border-b border-slate-100 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
              <Layers className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 uppercase">
                  {chart.bsvArticle}
                </span>
                <span className="text-[10px] text-slate-400">&bull;</span>
                <span className="text-[10px] font-bold text-slate-500 uppercase">
                  {chart.category}
                </span>
              </div>
              <h3 className="font-extrabold text-slate-900 text-base sm:text-lg mt-0.5">
                {chart.title}
              </h3>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Bild Grossansicht */}
        <div className="px-5">
          <div className="rounded-2xl overflow-hidden bg-slate-950 max-h-[50vh] flex items-center justify-center border border-slate-200 shadow-inner relative group">
            <img
              src={chart.imageUrl}
              alt={chart.title}
              className="w-full max-h-[48vh] object-contain transition duration-500 group-hover:scale-105"
            />
            <div className="absolute bottom-2 right-2 px-2.5 py-1 rounded-lg bg-black/70 backdrop-blur-sm text-white text-[10px] font-mono">
              1200 x 800 HD
            </div>
          </div>
        </div>

        {/* Erläuterung & Rechtliche Prüfpunkte */}
        <div className="px-5 pb-5 space-y-3 text-xs">
          <p className="text-slate-600 leading-relaxed">
            {chart.description}
          </p>

          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
            <div className="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-600" />
              <span>Verbindliche Prüfungsregeln:</span>
            </div>
            <ul className="space-y-1.5 text-slate-700">
              {chart.keyPoints.map((pt, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-cyan-600 mt-1.5 shrink-0" />
                  <span>{pt}</span>
                </li>
              ))}
            </ul>
          </div>

          {chart.tips && (
            <div className="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 text-[11px] leading-relaxed">
              <strong>Experten-Tipp für die Praxis:</strong> {chart.tips}
            </div>
          )}

          <div className="pt-2 flex items-center justify-between border-t border-slate-100">
            <span className="text-[11px] text-slate-400">
              Offizielle Lehrmittel Nautic &bull; Schweizer Binnenschifffahrt (BSV)
            </span>
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 bg-[#2d2421] hover:bg-[#3d322e] text-white rounded-xl text-xs font-bold transition cursor-pointer"
            >
              Schliessen
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
