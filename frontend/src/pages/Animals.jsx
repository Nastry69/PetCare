import chien from "../assets/chien.jpeg";
import chat from "../assets/chat.png";
import lapin from "../assets/lapin.jpg";
import { Link } from "react-router-dom";
import { Plus, MoreHorizontal, SquarePen, CalendarPlus, Camera } from "lucide-react";

const animals = [
  {
    id: 1,
    nom: "Rex",
    espece: "Golden Retriever",
    age: "5 ans",
    image: chien,
    badge: "EN PLEINE FORME",
    badgeColor: "green",
    highlighted: false,
  },
  {
    id: 2,
    nom: "Mina",
    espece: "Chat de gouttière",
    age: "2 ans",
    image: chat,
    badge: "EN PLEINE FORME",
    badgeColor: "green",
    highlighted: false,
  },
  {
    id: 3,
    nom: "Oscar",
    espece: "Lapin bélier",
    age: "1 an",
    image: lapin,
    badge: "VACCIN À PRÉVOIR",
    badgeColor: "orange",
    highlighted: true,
  },
];

function badgeClass(color) {
  const map = {
    green: "bg-[#EAF8EF] text-[#22C55E]",
    orange: "bg-[#FFF4E5] text-[#F59E0B]",
  };

  return map[color] || "bg-[#F1F5F9] text-[#64748B]";
}

function AnimalCard({ animal }) {
  return (
    <div
      className={`overflow-hidden rounded-[18px] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] ${
        animal.highlighted
          ? "border border-[#F6D7A8]"
          : "border border-[#E5EAF3]"
      }`}
    >
      <div className="relative h-[200px] w-full overflow-hidden">
        <img
          src={animal.image}
          alt={animal.nom}
          className="h-full w-full object-cover"
        />

        <span
          className={`absolute right-3 top-3 rounded-full px-3 py-1 text-[10px] font-semibold tracking-[0.02em] ${badgeClass(
            animal.badgeColor
          )}`}
        >
          {animal.badge}
        </span>
      </div>

      <div className="p-4">
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-[18px] font-bold text-[#0F172A]">{animal.nom}</h2>
            <p className="mt-1 text-[14px] text-[#64748B]">
              {animal.espece} • {animal.age}
            </p>
          </div>

          <button className="text-[#94A3B8] hover:text-[#64748B]">
            <MoreHorizontal size={18} />
          </button>
        </div>

        <div className="mt-5 border-t border-[#EEF2F7] pt-4">
          <div className="flex items-center justify-between">
            <Link
              to={`/animals/${animal.id}`}
              className="text-[13px] font-semibold text-[#1377EC] hover:underline"
            >
              Voir le profil ›
            </Link>

            <div className="flex items-center gap-3 text-[#64748B]">
              <button className="rounded-full bg-[#F8FAFC] p-2 hover:bg-[#EEF2F7]">
                <CalendarPlus size={15} />
              </button>
              <button className="rounded-full bg-[#F8FAFC] p-2 hover:bg-[#EEF2F7]">
                <SquarePen size={15} />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function EmptyAddCard() {
  return (
    <div className="mt-8 flex min-h-[180px] flex-col items-center justify-center rounded-[18px] border-2 border-dashed border-[#D7DEE9] bg-[#FAFBFD] px-6 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#94A3B8] shadow-sm">
        <Camera size={22} />
      </div>

      <h3 className="mt-5 text-[22px] font-semibold text-[#475569]">Nouvel arrivant ?</h3>
      <p className="mt-3 max-w-[360px] text-[14px] leading-6 text-[#94A3B8]">
        Cliquez sur le bouton pour ajouter un nouveau membre à votre famille.
      </p>
    </div>
  );
}

function Animals() {
  return (
    <div className="w-full">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-[44px] font-bold leading-none text-[#0F172A]">
            Mes Animaux
          </h1>
          <p className="mt-4 text-[24px] text-[#5B7FA9]">
            Gérez la santé et le bien-être de vos compagnons au quotidien.
          </p>
        </div>

        <button className="inline-flex h-[52px] items-center gap-2 rounded-[12px] bg-[#1377EC] px-5 text-[16px] font-semibold text-white shadow-[0_6px_18px_rgba(19,119,236,0.25)] transition hover:bg-[#0E68D0]">
          <Plus size={18} />
          Ajouter un animal
        </button>
      </div>

      <section className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {animals.map((animal) => (
          <AnimalCard key={animal.id} animal={animal} />
        ))}
      </section>

      <EmptyAddCard />
    </div>
  );
}

export default Animals;