import { useState, useEffect, useRef } from "react";
import { createPortal } from "react-dom";
import { useNavigate } from "react-router-dom";
import { CheckCircle, PawPrint, CalendarDays, AlertTriangle, MoreHorizontal } from "lucide-react";
import api from "../api/axios";
import { useAuth } from "../context/AuthContext";

// ── Status config ─────────────────────────────────────────────────────────────
const STATUS_CFG = {
  effectue:   { label: "Effectué",    color: "#22C55E" },
  prevu:      { label: "Prévu",       color: "#1377EC" },
  a_confirmer:{ label: "À confirmer", color: "#F59E0B" },
  annule:     { label: "Annulé",      color: "#EF4444" },
};

const TYPE_COLORS = {
  Vaccin:       "#8B5CF6",
  Traitement:   "#14B8A6",
  Toilettage:   "#EC4899",
  Consultation: "#3B82F6",
};
function typeColor(name) { return TYPE_COLORS[name] || "#94A3B8"; }

// ── Donut chart ───────────────────────────────────────────────────────────────
function DonutChart({ segments, centerValue, centerLabel }) {
  const SIZE = 150;
  const SW   = 14;
  const r    = (SIZE - SW) / 2;
  const cx   = SIZE / 2;
  const cy   = SIZE / 2;
  const C    = 2 * Math.PI * r;
  const total = segments.reduce((s, a) => s + a.value, 0);

  let cumDeg = 0;
  const arcs = total > 0
    ? segments.filter((s) => s.value > 0).map((seg) => {
        const frac = seg.value / total;
        const deg  = frac * 360;
        const arcLen = frac * C;
        const start = cumDeg;
        cumDeg += deg;
        return { ...seg, arcLen, gapLen: C - arcLen, startDeg: start };
      })
    : [];

  return (
    <div className="relative mx-auto" style={{ width: SIZE, height: SIZE }}>
      <svg width={SIZE} height={SIZE}>
        {/* background ring */}
        <circle cx={cx} cy={cy} r={r} fill="none" stroke="#EEF2F7" strokeWidth={SW} />
        {arcs.map((arc, i) => (
          <circle
            key={i}
            cx={cx} cy={cy} r={r}
            fill="none"
            stroke={arc.color}
            strokeWidth={SW}
            strokeDasharray={`${arc.arcLen} ${arc.gapLen}`}
            strokeDashoffset={0}
            strokeLinecap="butt"
            transform={`rotate(${arc.startDeg - 90} ${cx} ${cy})`}
          />
        ))}
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
        <p className="text-[22px] font-bold leading-none text-[#0F172A]">{centerValue}</p>
        <p className="mt-1 text-[10px] uppercase tracking-[0.16em] text-[#64748B]">{centerLabel}</p>
      </div>
    </div>
  );
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function badgeClass(color) {
  const map = {
    blue:   "bg-[#EAF3FF] text-[#1377EC]",
    green:  "bg-[#EAF8EF] text-[#22C55E]",
    orange: "bg-[#FFF4E5] text-[#F59E0B]",
    red:    "bg-[#FEECEC] text-[#EF4444]",
    gray:   "bg-[#F1F5F9] text-[#64748B]",
  };
  return map[color] || map.gray;
}

function StatsCard({ value, label, color, icon: Icon }) {
  const styles = {
    blue:   "border-[#D9E9FF] bg-[#F5F9FF] text-[#1377EC]",
    green:  "border-[#DCFCE7] bg-[#F0FDF4] text-[#22C55E]",
    orange: "border-[#FDE7C3] bg-[#FFF7ED] text-[#F59E0B]",
    red:    "border-[#FECACA] bg-[#FEF2F2] text-[#EF4444]",
  };
  return (
    <div className="rounded-[16px] border bg-white px-6 py-5 shadow-sm">
      <div className="flex items-center gap-4">
        <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${styles[color]}`}>
          <Icon size={20} />
        </div>
        <div>
          <p className="text-[22px] font-bold text-[#0F172A]">{value}</p>
          <p className="text-[13px] text-[#64748B]">{label}</p>
        </div>
      </div>
    </div>
  );
}

function getStatusColor(statut) {
  if (statut === "effectue")   return "green";
  if (statut === "a_confirmer") return "orange";
  if (statut === "annule")     return "red";
  return "blue";
}

function getStatusLabel(statut) {
  return STATUS_CFG[statut]?.label || statut;
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleDateString("fr-FR", { day: "2-digit", month: "short", year: "numeric" });
}

function formatHour(dateStr) {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
}

// ── Event action menu ─────────────────────────────────────────────────────────
function EventMenu({ event, onDelete, deleting }) {
  const [open, setOpen] = useState(false);
  const [pos, setPos]   = useState({ top: 0, right: 0 });
  const btnRef  = useRef(null);
  const menuRef = useRef(null);
  const navigate = useNavigate();

  const handleToggle = () => {
    if (!open && btnRef.current) {
      const r = btnRef.current.getBoundingClientRect();
      setPos({ top: r.bottom + 4, right: window.innerWidth - r.right });
    }
    setOpen((v) => !v);
  };

  useEffect(() => {
    if (!open) return;
    const handler = (e) => {
      if (
        menuRef.current && !menuRef.current.contains(e.target) &&
        btnRef.current  && !btnRef.current.contains(e.target)
      ) setOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, [open]);

  return (
    <>
      <button
        ref={btnRef}
        type="button"
        onClick={handleToggle}
        disabled={deleting}
        className="inline-flex h-8 w-8 items-center justify-center rounded-full text-[#94A3B8] transition hover:bg-[#F1F5F9] hover:text-[#475569] disabled:cursor-wait disabled:opacity-60"
        title="Actions"
      >
        <MoreHorizontal size={16} />
      </button>

      {open && createPortal(
        <div
          ref={menuRef}
          style={{ top: pos.top, right: pos.right, position: "fixed" }}
          className="z-[9999] min-w-[148px] rounded-[10px] border border-[#E5EAF3] bg-white py-1 shadow-xl"
        >
          <button type="button" onClick={() => { setOpen(false); navigate(`/animals/${event.animal?.id}`); }}
            className="flex w-full items-center px-4 py-2 text-[13px] text-[#334155] hover:bg-[#F8FAFC]">
            Voir
          </button>
          <button type="button" onClick={() => { setOpen(false); navigate(`/events/${event.id}/edit`); }}
            className="flex w-full items-center px-4 py-2 text-[13px] text-[#334155] hover:bg-[#F8FAFC]">
            Modifier
          </button>
          <div className="mx-3 my-1 border-t border-[#EEF2F7]" />
          <button type="button" onClick={() => { setOpen(false); onDelete(event); }}
            className="flex w-full items-center px-4 py-2 text-[13px] text-[#EF4444] hover:bg-[#FEF2F2]">
            Supprimer
          </button>
        </div>,
        document.body
      )}
    </>
  );
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
function Dashboard() {
  const { user } = useAuth();
  const [animals,   setAnimals]   = useState([]);
  const [events,    setEvents]    = useState([]);
  const [allEvents, setAllEvents] = useState([]);
  const [loading,   setLoading]   = useState(true);
  const [deletingId, setDeletingId] = useState(null);
  const [error,     setError]     = useState("");
  const [chartView, setChartView] = useState("statut"); // "statut" | "type"

  useEffect(() => {
    Promise.all([
      api.get("/animals"),
      api.get("/evenements/upcoming"),
      api.get("/evenements"),
    ])
      .then(([animalsRes, upcomingRes, allRes]) => {
        setAnimals(animalsRes.data);
        setEvents(upcomingRes.data.slice(0, 5));
        setAllEvents(allRes.data);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const handleDeleteEvent = async (event) => {
    const label = event.typeEvenement?.libelle || "cet événement";
    if (!confirm(`Supprimer ${label} ? Cette action est irréversible.`)) return;
    setDeletingId(event.id);
    setError("");
    try {
      await api.delete(`/evenements/${event.id}`);
      setEvents((c) => c.filter((i) => i.id !== event.id));
      setAllEvents((c) => c.filter((i) => i.id !== event.id));
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de la suppression de l'événement.");
    } finally {
      setDeletingId(null);
    }
  };

  // ── Chart segments ──
  const statusSegments = Object.entries(STATUS_CFG).map(([key, cfg]) => ({
    label: cfg.label,
    color: cfg.color,
    value: allEvents.filter((e) => e.statut === key).length,
  }));

  const typeMap = {};
  allEvents.forEach((e) => {
    const t = e.typeEvenement?.libelle || "Autre";
    typeMap[t] = (typeMap[t] || 0) + 1;
  });
  const typeSegments = Object.entries(typeMap).map(([label, value]) => ({
    label,
    color: typeColor(label),
    value,
  }));

  const segments    = chartView === "statut" ? statusSegments : typeSegments;
  const visibleSegs = segments.filter((s) => s.value > 0);

  const doneCount = allEvents.filter((e) => e.statut === "effectue").length;
  const total     = allEvents.length;
  const pct       = total > 0 ? Math.round((doneCount / total) * 100) : 0;

  const chartCenter      = chartView === "statut" ? `${pct}%` : String(total);
  const chartCenterLabel = chartView === "statut" ? "Complétés" : "Total";

  // ── Stats cards ──
  const urgentCount = events.filter((e) => e.rappelActif && e.rappelJoursAvant <= 2).length;
  const statsCards = [
    { value: String(doneCount),     label: "Rendez-vous passés",   color: "blue",   icon: CheckCircle },
    { value: String(animals.length),label: "Animaux suivis",        color: "green",  icon: PawPrint },
    { value: String(events.length), label: "Événements à venir",    color: "orange", icon: CalendarDays },
    { value: String(urgentCount),   label: "Rappel urgent",         color: "red",    icon: AlertTriangle },
  ];

  if (loading) {
    return (
      <div className="mx-auto max-w-[1120px]">
        <div className="flex items-center justify-center py-20 text-[#64748B]">Chargement…</div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-[1120px]">
      <div className="mb-6">
        <h1 className="text-[20px] font-bold text-[#0F172A]">Bonjour {user?.prenom || ""},</h1>
        <p className="mt-1 text-[14px] text-[#64748B]">
          Voici l'état actuel de santé de vos compagnons aujourd'hui.
        </p>
      </div>

      {/* Stats cards */}
      <section className="grid grid-cols-1 gap-4 xl:grid-cols-4">
        {statsCards.map((card) => (
          <StatsCard key={card.label} {...card} />
        ))}
      </section>

      {/* Charts + animals */}
      <section className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[0.95fr_1.95fr]">

        {/* Donut chart card */}
        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
          <div className="flex items-center justify-between">
            <h2 className="text-[14px] font-semibold text-[#0F172A]">Répartition des RDV</h2>
            {/* Toggle */}
            <div className="flex overflow-hidden rounded-[8px] border border-[#E5EAF3] bg-[#F8FAFC]">
              {["statut", "type"].map((v) => (
                <button
                  key={v}
                  onClick={() => setChartView(v)}
                  className={`px-3 py-1 text-[11px] font-semibold capitalize transition ${
                    chartView === v
                      ? "bg-[#1377EC] text-white"
                      : "text-[#64748B] hover:text-[#334155]"
                  }`}
                >
                  {v === "statut" ? "Statut" : "Type"}
                </button>
              ))}
            </div>
          </div>

          <div className="mt-6">
            <DonutChart
              segments={segments}
              centerValue={chartCenter}
              centerLabel={chartCenterLabel}
            />
          </div>

          {/* Legend */}
          <div className="mt-5 space-y-2">
            {visibleSegs.length === 0 ? (
              <p className="text-center text-[12px] text-[#94A3B8]">Aucun événement.</p>
            ) : (
              visibleSegs.map((s) => (
                <div key={s.label} className="flex items-center justify-between text-[12px]">
                  <div className="flex items-center gap-2 text-[#475569]">
                    <span className="h-2.5 w-2.5 shrink-0 rounded-full" style={{ backgroundColor: s.color }} />
                    {s.label}
                  </div>
                  <span className="font-semibold text-[#0F172A]">{s.value}</span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Animals stats */}
        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
          <h2 className="text-[14px] font-semibold text-[#0F172A]">Statistiques sur mes animaux</h2>
          {animals.length === 0 ? (
            <p className="mt-6 text-center text-[13px] text-[#94A3B8]">Aucun animal enregistré.</p>
          ) : (
            <div className="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
              {animals.slice(0, 3).map((animal) => (
                <div key={animal.id} className="rounded-[14px] border border-[#EEF2F7] bg-[#F8FAFC] p-4">
                  <div className="flex flex-col items-center">
                    {animal.photoUrl ? (
                      <img src={animal.photoUrl} alt={animal.nom} className="h-14 w-14 rounded-full object-cover" />
                    ) : (
                      <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#EAF3FF] text-[24px]">🐾</div>
                    )}
                    <p className="mt-3 text-[16px] font-bold text-[#1E293B]">{animal.nom}</p>
                    <p className="text-[12px] text-[#64748B]">{animal.espece}</p>
                  </div>
                  <div className="mt-4 space-y-2">
                    <div className="flex items-center justify-between gap-2">
                      <span className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass("blue")}`}>Espèce</span>
                      <span className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass("gray")}`}>{animal.espece}</span>
                    </div>
                    {animal.race && (
                      <div className="flex items-center justify-between gap-2">
                        <span className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass("blue")}`}>Race</span>
                        <span className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass("gray")}`}>{animal.race}</span>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Upcoming events table */}
      <section className="mt-6 rounded-[18px] border border-[#E5EAF3] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <h2 className="text-[20px] font-semibold text-[#1E293B]">Détails des événements à venir</h2>
          <a href="/events/new"
            className="rounded-[10px] bg-[#1377EC] px-4 py-2 text-[13px] font-medium text-white hover:bg-[#0E68D0]">
            + Ajouter
          </a>
        </div>

        <div className="mt-5 overflow-x-auto">
          {error && (
            <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{error}</div>
          )}
          {events.length === 0 ? (
            <p className="py-6 text-center text-[13px] text-[#94A3B8]">Aucun événement à venir.</p>
          ) : (
            <table className="min-w-full">
              <thead>
                <tr className="border-b border-[#EEF2F7] text-left text-[11px] uppercase tracking-[0.08em] text-[#94A3B8]">
                  <th className="pb-4 font-semibold">Date</th>
                  <th className="pb-4 font-semibold">Animal</th>
                  <th className="pb-4 font-semibold">Type d'événement</th>
                  <th className="pb-4 font-semibold">Status</th>
                  <th className="pb-4 text-right font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                {events.map((event) => {
                  const typeCfg = typeColor(event.typeEvenement?.libelle);
                  return (
                    <tr key={event.id} className="border-b border-[#EEF2F7] last:border-b-0">
                      <td className="py-5">
                        <div className="text-[13px] font-medium text-[#0F172A]">{formatDate(event.dateHeureEvenement)}</div>
                        <div className="mt-1 text-[12px] text-[#64748B]">{formatHour(event.dateHeureEvenement)}</div>
                      </td>
                      <td className="py-5">
                        <div className="flex items-center gap-3">
                          {event.animal?.photoUrl ? (
                            <img src={event.animal.photoUrl} alt={event.animal.nom} className="h-8 w-8 rounded-full object-cover" />
                          ) : (
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[#EAF3FF] text-[14px]">🐾</div>
                          )}
                          <span className="text-[14px] font-medium text-[#1E293B]">{event.animal?.nom}</span>
                        </div>
                      </td>
                      <td className="py-5">
                        <span
                          className="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                          style={{ backgroundColor: `${typeCfg}18`, color: typeCfg }}
                        >
                          <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: typeCfg }} />
                          {event.typeEvenement?.libelle}
                        </span>
                      </td>
                      <td className="py-5">
                        <span className={`rounded-full px-3 py-1 text-[11px] font-semibold ${badgeClass(getStatusColor(event.statut))}`}>
                          {getStatusLabel(event.statut)}
                        </span>
                      </td>
                      <td className="py-5 text-right">
                        <EventMenu event={event} onDelete={handleDeleteEvent} deleting={deletingId === event.id} />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </section>
    </div>
  );
}

export default Dashboard;
