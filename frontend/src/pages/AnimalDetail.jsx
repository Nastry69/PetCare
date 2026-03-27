import chien from "../assets/chien.jpeg";
import { CalendarDays, Share2, Stethoscope, FlaskConical, Pencil, Syringe, Pill, Scissors } from "lucide-react";

const upcomingEvents = [
  {
    id: 1,
    date: "20/10/2023",
    type: "Vaccin",
    description: "Rappel annuel Rage",
    icon: Syringe,
  },
  {
    id: 2,
    date: "15/11/2023",
    type: "Traitement",
    description: "Vermifuge trimestriel",
    icon: Pill,
  },
  {
    id: 3,
    date: "05/12/2023",
    type: "Toilettage",
    description: "Coupe d’hiver",
    icon: Scissors,
  },
];

const historyItems = [
  {
    id: 1,
    title: "Consultation Générale",
    location: "Dr. Martin · Clinique des Lilas",
    description:
      "Contrôle de routine, tout est normal. Poids stable à 32kg.",
    date: "12/09/2023",
    icon: Stethoscope,
  },
  {
    id: 2,
    title: "Analyse de sang",
    location: "Laboratoire VetLab",
    description: "Bilan annuel préventif.",
    date: "15/05/2023",
    icon: FlaskConical,
  },
];

function EventTypeBadge({ type, Icon }) {
  const styles = {
    Vaccin: "bg-[#EAF3FF] text-[#1377EC]",
    Traitement: "bg-[#EAF3FF] text-[#1377EC]",
    Toilettage: "bg-[#EAF3FF] text-[#1377EC]",
  };

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-[6px] px-2 py-1 text-[11px] font-medium ${
        styles[type] || "bg-[#F1F5F9] text-[#64748B]"
      }`}
    >
      <Icon size={12} />
      {type}
    </span>
  );
}

function AnimalDetail() {
  return (
    <div className="w-full">
      {/* HEADER */}
      <section className="border-b border-[#DDE5F0] pb-6">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
          <div className="relative h-[160px] w-[160px] overflow-hidden rounded-[14px]">
            <img
              src={chien}
              alt="Rex"
              className="h-full w-full object-cover"
            />

            <button className="absolute bottom-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#475569] shadow">
              <Pencil size={14} />
            </button>
          </div>

          <div className="pt-1">
            <h1 className="text-[56px] font-bold leading-none text-[#0F172A]">
              REX
            </h1>

            <div className="mt-3 flex flex-wrap items-center gap-4 text-[18px] text-[#64748B]">
              <span>🐾 Golden Retriever</span>
              <span>🎂 Né le 15/08/2022</span>
            </div>

            <div className="mt-4">
              <span className="rounded-full bg-[#EAF3FF] px-3 py-1 text-[12px] font-semibold tracking-[0.04em] text-[#1377EC]">
                MÂLE
              </span>
            </div>
          </div>
        </div>
      </section>

      {/* CONTENT */}
      <section className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1fr]">
        {/* PROCHAINS EVENEMENTS */}
        <div>
          <h2 className="mb-4 flex items-center gap-2 text-[18px] font-semibold text-[#94A3B8]">
            <CalendarDays size={18} />
            Prochains événements
          </h2>

          <div className="overflow-hidden rounded-[14px] border border-[#DDE5F0] bg-white">
            <table className="min-w-full">
              <thead>
                <tr className="bg-[#F8FAFC] text-left text-[11px] uppercase tracking-[0.08em] text-[#94A3B8]">
                  <th className="px-4 py-3 font-semibold">Date</th>
                  <th className="px-4 py-3 font-semibold">Type</th>
                  <th className="px-4 py-3 font-semibold">Description</th>
                </tr>
              </thead>
              <tbody>
                {upcomingEvents.map((event) => (
                  <tr key={event.id} className="border-t border-[#EEF2F7]">
                    <td className="px-4 py-4 text-[14px] text-[#475569]">{event.date}</td>
                    <td className="px-4 py-4">
                      <EventTypeBadge type={event.type} Icon={event.icon} />
                    </td>
                    <td className="px-4 py-4 text-[14px] text-[#475569]">
                      {event.description}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* HISTORIQUE */}
        <div>
          <h2 className="mb-4 flex items-center gap-2 text-[18px] font-semibold text-[#94A3B8]">
            <span className="text-[20px]">↻</span>
            Historique
          </h2>

          <div className="rounded-[14px] border border-[#DDE5F0] bg-white p-3">
            <div className="space-y-3">
              {historyItems.map((item) => {
                const Icon = item.icon;
                return (
                  <div
                    key={item.id}
                    className="flex items-start justify-between rounded-[12px] border border-[#EEF2F7] bg-[#FCFDFE] p-4"
                  >
                    <div className="flex gap-4">
                      <div className="mt-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#F5F9FF] text-[#1377EC]">
                        <Icon size={18} />
                      </div>

                      <div>
                        <h3 className="text-[16px] font-semibold text-[#1E293B]">
                          {item.title}
                        </h3>
                        <p className="mt-1 text-[13px] text-[#64748B]">
                          {item.location}
                        </p>
                        <p className="mt-2 max-w-[320px] text-[14px] leading-6 text-[#64748B]">
                          “{item.description}”
                        </p>
                      </div>
                    </div>

                    <div className="pl-4 text-[12px] text-[#94A3B8]">{item.date}</div>
                  </div>
                );
              })}
            </div>

            <div className="pt-5 text-center">
              <button className="text-[12px] font-semibold uppercase tracking-[0.1em] text-[#64748B] hover:text-[#1377EC]">
                Voir tout l’historique
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* ACTIONS */}
      <section className="mt-10 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <button className="flex h-[56px] items-center justify-center gap-2 rounded-[12px] bg-[#1377EC] text-[16px] font-semibold text-white shadow-[0_6px_18px_rgba(19,119,236,0.25)] transition hover:bg-[#0E68D0]">
          <CalendarDays size={18} />
          Ajouter un événement
        </button>

        <button className="flex h-[56px] items-center justify-center gap-2 rounded-[12px] border-2 border-[#1377EC] bg-white text-[16px] font-semibold text-[#1377EC] transition hover:bg-[#F5F9FF]">
          <Share2 size={18} />
          Partager l'accès
        </button>
      </section>
    </div>
  );
}

export default AnimalDetail;