import chien from "../assets/chien.jpeg";
import chat from "../assets/chat.png";
import lapin from "../assets/lapin.jpg";
import {
  CheckCircle,
  PawPrint,
  CalendarDays,
  AlertTriangle
} from "lucide-react";

const statsCards = [
  {
    value: "15",
    label: "Rendez-vous passés",
    color: "blue",
    icon: CheckCircle,
  },
  {
    value: "3",
    label: "Animaux suivis",
    color: "green",
    icon: PawPrint,
  },
  {
    value: "4",
    label: "Événements à venir",
    color: "orange",
    icon: CalendarDays,
  },
  {
    value: "1",
    label: "Rappel urgent",
    color: "red",
    icon: AlertTriangle,
  },
];

const animalStats = [
  {
    id: 1,
    name: "REX",
    image: chien,
    rows: [
      { left: "Vermifuge", right: "À jour", leftColor: "green", rightColor: "green" },
      { left: "Vaccins", right: "À jour", leftColor: "green", rightColor: "green" },
      { left: "RDV", right: "J - 2", leftColor: "blue", rightColor: "blue" },
    ],
  },
  {
    id: 2,
    name: "Mina",
    image: chat,
    rows: [
      { left: "Vermifuge", right: "Urgent", leftColor: "red", rightColor: "red" },
      { left: "Vaccins", right: "À jour", leftColor: "green", rightColor: "green" },
      { left: "RDV", right: "Aucun", leftColor: "gray", rightColor: "gray" },
    ],
  },
  {
    id: 3,
    name: "Oscar",
    image: lapin,
    rows: [
      { left: "Vermifuge", right: "À jour", leftColor: "green", rightColor: "green" },
      { left: "Vaccins", right: "À prévoir", leftColor: "orange", rightColor: "orange" },
      { left: "RDV", right: "Demain", leftColor: "blue", rightColor: "blue" },
    ],
  },
];

const upcomingEvents = [
  {
    id: 1,
    date: "12 Oct 2023",
    hour: "14:30",
    animal: "Rex",
    image: chien,
    type: "Vaccin Annuel",
    status: "À confirmer",
    statusColor: "orange",
  },
  {
    id: 2,
    date: "14 Oct 2023",
    hour: "10:00",
    animal: "Mina",
    image: chat,
    type: "Consultation Dentaire",
    status: "Confirmé",
    statusColor: "green",
  },
  {
    id: 3,
    date: "18 Oct 2023",
    hour: "16:15",
    animal: "Oscar",
    image: lapin,
    type: "Vermifuge",
    status: "Confirmé",
    statusColor: "green",
  },
];

function badgeClass(color) {
  const map = {
    blue: "bg-[#EAF3FF] text-[#1377EC]",
    green: "bg-[#EAF8EF] text-[#22C55E]",
    orange: "bg-[#FFF4E5] text-[#F59E0B]",
    red: "bg-[#FEECEC] text-[#EF4444]",
    gray: "bg-[#F1F5F9] text-[#64748B]",
  };

  return map[color] || map.gray;
}

function StatsCard({ value, label, color, icon: Icon }) {
  const styles = {
    blue: "border-[#D9E9FF] bg-[#F5F9FF] text-[#1377EC]",
    green: "border-[#DCFCE7] bg-[#F0FDF4] text-[#22C55E]",
    orange: "border-[#FDE7C3] bg-[#FFF7ED] text-[#F59E0B]",
    red: "border-[#FECACA] bg-[#FEF2F2] text-[#EF4444]",
  };

  return (
    <div className="rounded-[16px] border bg-white px-6 py-5 shadow-sm">
      <div className="flex items-center gap-4">
        <div
          className={`flex h-11 w-11 items-center justify-center rounded-xl ${styles[color]}`}
        >
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

function Dashboard() {
  return (
    <div className="mx-auto max-w-[1120px]">
      <div className="mb-6">
        <h1 className="text-[20px] font-bold text-[#0F172A]">Bonjour Max,</h1>
        <p className="mt-1 text-[14px] text-[#64748B]">
          Voici l’état actuel de santé de vos compagnons aujourd’hui.
        </p>
      </div>

      <section className="grid grid-cols-1 gap-4 xl:grid-cols-4">
        {statsCards.map((card) => (
          <StatsCard key={card.label} {...card} />
        ))}
      </section>

      <section className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[0.95fr_1.95fr]">
        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
          <h2 className="text-[14px] font-semibold text-[#0F172A]">Statut des RDV</h2>

          <div className="mx-auto mt-8 flex h-[146px] w-[146px] items-center justify-center rounded-full border-[8px] border-[#1377EC]">
            <div className="text-center">
              <p className="text-[22px] font-bold text-[#0F172A]">75%</p>
              <p className="text-[10px] uppercase tracking-[0.18em] text-[#64748B]">
                Complétés
              </p>
            </div>
          </div>

          <div className="mt-8 space-y-3 text-[13px]">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-[#475569]">
                <span className="h-2.5 w-2.5 rounded-full bg-[#1377EC]" />
                Terminés
              </div>
              <span className="font-medium text-[#0F172A]">15</span>
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-[#475569]">
                <span className="h-2.5 w-2.5 rounded-full bg-[#CBD5E1]" />
                En attente
              </div>
              <span className="font-medium text-[#0F172A]">5</span>
            </div>
          </div>
        </div>

        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
          <div className="flex items-center justify-between">
            <h2 className="text-[14px] font-semibold text-[#0F172A]">
              Statistiques sur mes animaux
            </h2>
            <button className="text-[12px] font-semibold text-[#1377EC]">Voir tout</button>
          </div>

          <div className="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
            {animalStats.map((animal) => (
              <div
                key={animal.id}
                className="rounded-[14px] border border-[#EEF2F7] bg-[#F8FAFC] p-4"
              >
                <div className="flex flex-col items-center">
                  <img
                    src={animal.image}
                    alt={animal.name}
                    className="h-14 w-14 rounded-full object-cover"
                  />
                  <p className="mt-3 text-[16px] font-bold text-[#1E293B]">{animal.name}</p>
                </div>

                <div className="mt-4 space-y-2">
                  {animal.rows.map((row, index) => (
                    <div key={index} className="flex items-center justify-between gap-2">
                      <span
                        className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass(
                          row.leftColor
                        )}`}
                      >
                        {row.left}
                      </span>
                      <span
                        className={`rounded-full px-2.5 py-1 text-[11px] font-medium ${badgeClass(
                          row.rightColor
                        )}`}
                      >
                        {row.right}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mt-6 rounded-[18px] border border-[#E5EAF3] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <h2 className="text-[20px] font-semibold text-[#1E293B]">
            Détails des événements à venir
          </h2>

          <div className="flex gap-3">
            <button className="rounded-[10px] border border-[#E5EAF3] bg-white px-4 py-2 text-[13px] font-medium text-[#64748B] hover:bg-[#F8FAFC]">
              Filtrer
            </button>
            <button className="rounded-[10px] bg-[#1377EC] px-4 py-2 text-[13px] font-medium text-white hover:bg-[#0E68D0]">
              + Ajouter
            </button>
          </div>
        </div>

        <div className="mt-5 overflow-x-auto">
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
              {upcomingEvents.map((event) => (
                <tr key={event.id} className="border-b border-[#EEF2F7] last:border-b-0">
                  <td className="py-5">
                    <div className="text-[13px] font-medium text-[#0F172A]">{event.date}</div>
                    <div className="mt-1 text-[12px] text-[#64748B]">{event.hour}</div>
                  </td>

                  <td className="py-5">
                    <div className="flex items-center gap-3">
                      <img
                        src={event.image}
                        alt={event.animal}
                        className="h-8 w-8 rounded-full object-cover"
                      />
                      <span className="text-[14px] font-medium text-[#1E293B]">
                        {event.animal}
                      </span>
                    </div>
                  </td>

                  <td className="py-5 text-[14px] text-[#475569]">{event.type}</td>

                  <td className="py-5">
                    <span
                      className={`rounded-full px-3 py-1 text-[11px] font-semibold ${badgeClass(
                        event.statusColor
                      )}`}
                    >
                      {event.status}
                    </span>
                  </td>

                  <td className="py-5 text-right text-[#94A3B8]">•••</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}

export default Dashboard;