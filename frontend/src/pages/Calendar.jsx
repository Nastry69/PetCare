import { useState, useEffect } from "react";
import { ChevronLeft, ChevronRight, Plus } from "lucide-react";
import { Link } from "react-router-dom";
import api from "../api/axios";

const MONTHS = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];
const DAYS = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];

function statusColor(statut) {
  if (statut === "effectue") return "bg-[#22C55E]";
  if (statut === "a_confirmer") return "bg-[#F59E0B]";
  if (statut === "annule") return "bg-[#EF4444]";
  return "bg-[#1377EC]";
}

function Calendar() {
  const today = new Date();
  const [year, setYear] = useState(today.getFullYear());
  const [month, setMonth] = useState(today.getMonth());
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState(null);

  useEffect(() => {
    api.get("/evenements")
      .then((res) => setEvents(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const prevMonth = () => {
    if (month === 0) { setMonth(11); setYear((y) => y - 1); }
    else setMonth((m) => m - 1);
    setSelected(null);
  };
  const nextMonth = () => {
    if (month === 11) { setMonth(0); setYear((y) => y + 1); }
    else setMonth((m) => m + 1);
    setSelected(null);
  };

  const firstDay = new Date(year, month, 1);
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const startOffset = (firstDay.getDay() + 6) % 7;

  const eventsThisMonth = events.filter((e) => {
    const d = new Date(e.dateHeureEvenement);
    return d.getFullYear() === year && d.getMonth() === month;
  });

  const eventsForDay = (day) => {
    return eventsThisMonth.filter((e) => new Date(e.dateHeureEvenement).getDate() === day);
  };

  const selectedEvents = selected ? eventsForDay(selected) : [];

  const cells = [];
  for (let i = 0; i < startOffset; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);

  return (
    <div className="mx-auto max-w-[1120px]">
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-[20px] font-bold text-[#0F172A]">Calendrier</h1>
          <p className="mt-1 text-[14px] text-[#64748B]">Visualisez et gérez les événements de vos animaux.</p>
        </div>
        <Link
          to="/events/new"
          className="inline-flex items-center gap-2 rounded-[10px] bg-[#1377EC] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#0E68D0]"
        >
          <Plus size={15} />
          Ajouter un événement
        </Link>
      </div>

      <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-5 shadow-sm">
        <div className="mb-5 flex items-center justify-between">
          <button onClick={prevMonth} className="rounded-[8px] border border-[#E5EAF3] p-2 text-[#64748B] hover:bg-[#F8FAFC]">
            <ChevronLeft size={16} />
          </button>
          <h2 className="text-[17px] font-semibold text-[#0F172A]">
            {MONTHS[month]} {year}
          </h2>
          <button onClick={nextMonth} className="rounded-[8px] border border-[#E5EAF3] p-2 text-[#64748B] hover:bg-[#F8FAFC]">
            <ChevronRight size={16} />
          </button>
        </div>

        <div className="grid grid-cols-7 gap-1">
          {DAYS.map((d) => (
            <div key={d} className="pb-2 text-center text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
              {d}
            </div>
          ))}

          {cells.map((day, i) => {
            if (!day) return <div key={`empty-${i}`} />;
            const dayEvents = eventsForDay(day);
            const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const isSelected = day === selected;

            return (
              <button
                key={day}
                onClick={() => setSelected(day === selected ? null : day)}
                className={`relative flex min-h-[60px] flex-col items-center rounded-[10px] p-1.5 text-[13px] transition
                  ${isToday ? "border-2 border-[#1377EC] font-bold text-[#1377EC]" : "text-[#334155]"}
                  ${isSelected ? "bg-[#EAF3FF]" : "hover:bg-[#F8FAFC]"}
                `}
              >
                <span className={`h-6 w-6 rounded-full text-center leading-6
                  ${isToday ? "bg-[#1377EC] text-white" : ""}
                `}>
                  {day}
                </span>
                <div className="mt-1 flex flex-wrap justify-center gap-0.5">
                  {dayEvents.slice(0, 3).map((e) => (
                    <span key={e.id} className={`h-1.5 w-1.5 rounded-full ${statusColor(e.statut)}`} />
                  ))}
                </div>
              </button>
            );
          })}
        </div>
      </div>

      {selected && (
        <div className="mt-5 rounded-[18px] border border-[#E5EAF3] bg-white p-5 shadow-sm">
          <h3 className="mb-4 text-[16px] font-semibold text-[#0F172A]">
            Événements du {selected} {MONTHS[month]} {year}
          </h3>

          {selectedEvents.length === 0 ? (
            <p className="text-[13px] text-[#94A3B8]">Aucun événement ce jour.</p>
          ) : (
            <div className="space-y-3">
              {selectedEvents.map((event) => (
                <div key={event.id} className="flex items-center gap-4 rounded-[12px] border border-[#EEF2F7] p-4">
                  <span className={`h-3 w-3 rounded-full ${statusColor(event.statut)}`} />
                  <div className="flex-1">
                    <p className="text-[14px] font-semibold text-[#0F172A]">
                      {event.typeEvenement?.libelle}
                    </p>
                    <p className="text-[12px] text-[#64748B]">
                      {event.animal?.nom} · {new Date(event.dateHeureEvenement).toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })}
                    </p>
                    {event.commentaire && (
                      <p className="mt-1 text-[12px] text-[#94A3B8]">{event.commentaire}</p>
                    )}
                  </div>
                  <span className={`rounded-full px-2.5 py-1 text-[11px] font-medium
                    ${event.statut === "effectue" ? "bg-[#EAF8EF] text-[#22C55E]" :
                      event.statut === "a_confirmer" ? "bg-[#FFF4E5] text-[#F59E0B]" :
                      event.statut === "annule" ? "bg-[#FEECEC] text-[#EF4444]" :
                      "bg-[#EAF3FF] text-[#1377EC]"}
                  `}>
                    {event.statut === "effectue" ? "Effectué" :
                     event.statut === "a_confirmer" ? "À confirmer" :
                     event.statut === "annule" ? "Annulé" : "Prévu"}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {loading && (
        <p className="mt-4 text-center text-[13px] text-[#94A3B8]">Chargement des événements…</p>
      )}
    </div>
  );
}

export default Calendar;
