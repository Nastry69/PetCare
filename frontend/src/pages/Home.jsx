import { Link } from "react-router-dom";
import { PawPrint, CalendarDays, Bell, Share2, Shield, ChevronRight } from "lucide-react";
import logo from "../assets/Logo 1.PNG";

const features = [
  {
    icon: PawPrint,
    title: "Tous vos animaux au même endroit",
    description: "Chiens, chats, lapins, NAC… chaque animal a sa fiche complète avec ses informations, son historique et ses documents.",
    color: "bg-[#EAF3FF] text-[#1377EC]",
  },
  {
    icon: CalendarDays,
    title: "Plus besoin de carnet de santé papier",
    description: "Vaccins, visites vétérinaires, traitements antiparasitaires — tout est enregistré, daté et consultable à tout moment depuis votre téléphone.",
    color: "bg-[#EAF8EF] text-[#22C55E]",
  },
  {
    icon: Bell,
    title: "Rappels automatiques",
    description: "Ne ratez plus jamais un rendez-vous. PetCare vous notifie avant chaque échéance importante pour la santé de vos compagnons.",
    color: "bg-[#FFF4E5] text-[#F59E0B]",
  },
  {
    icon: Share2,
    title: "Partagez avec votre entourage",
    description: "Famille, pet-sitter, vétérinaire… donnez accès aux informations de vos animaux aux personnes de confiance, en lecture seule ou en gestion.",
    color: "bg-[#F3EEFF] text-[#8B5CF6]",
  },
  {
    icon: Shield,
    title: "Vos données vous appartiennent",
    description: "Export complet de vos données en un clic, suppression de compte possible à tout moment. Conforme RGPD.",
    color: "bg-[#FEECEC] text-[#EF4444]",
  },
];

const steps = [
  { num: "01", title: "Créez votre compte", desc: "Inscription gratuite en 30 secondes, aucune carte bancaire requise." },
  { num: "02", title: "Ajoutez vos animaux", desc: "Renseignez les informations de base : nom, espèce, race, date de naissance." },
  { num: "03", title: "Centralisez leur santé", desc: "Enregistrez chaque événement vétérinaire et activez les rappels." },
];

function Home() {
  return (
    <div className="min-h-screen bg-white font-sans">
      {/* Navbar */}
      <header className="sticky top-0 z-50 border-b border-[#E5EAF3] bg-white/90 backdrop-blur-sm">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
          <div className="flex items-center gap-3">
            <img src={logo} alt="PetCare" className="h-9 w-9 rounded-full object-cover" />
            <span className="text-[17px] font-bold text-[#0F172A]">PetCare</span>
          </div>
          <nav className="hidden items-center gap-8 md:flex">
            <a href="#features" className="text-[14px] text-[#475569] transition hover:text-[#1377EC]">Fonctionnalités</a>
            <a href="#how" className="text-[14px] text-[#475569] transition hover:text-[#1377EC]">Comment ça marche</a>
          </nav>
          <div className="flex items-center gap-3">
            <Link
              to="/login"
              className="flex h-9 items-center rounded-[9px] border border-[#E5EAF3] px-4 text-[13px] font-medium text-[#475569] transition hover:border-[#1377EC] hover:text-[#1377EC]"
            >
              Se connecter
            </Link>
            <Link
              to="/register"
              className="flex h-9 items-center rounded-[9px] bg-[#1377EC] px-4 text-[13px] font-semibold text-white transition hover:bg-[#0E68D0]"
            >
              S'inscrire gratuitement
            </Link>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-b from-[#F0F7FF] to-white px-6 py-20 text-center">
        <div className="mx-auto max-w-3xl">
          <span className="mb-4 inline-flex items-center gap-2 rounded-full bg-[#EAF3FF] px-4 py-1.5 text-[13px] font-medium text-[#1377EC]">
            <PawPrint size={14} /> La santé de vos animaux, centralisée
          </span>
          <h1 className="mt-4 text-[48px] font-extrabold leading-tight tracking-tight text-[#0F172A]">
            Plus besoin de carnet<br className="hidden sm:block" /> de santé papier
          </h1>
          <p className="mt-5 text-[18px] leading-relaxed text-[#475569]">
            PetCare centralise l'intégralité des informations de santé de vos animaux. Vaccins, rendez-vous, traitements — tout est accessible, organisé et partageable en un instant.
          </p>
          <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
              to="/register"
              className="inline-flex h-12 items-center gap-2 rounded-[12px] bg-[#1377EC] px-7 text-[15px] font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-[#0E68D0]"
            >
              Commencer gratuitement <ChevronRight size={18} />
            </Link>
            <Link
              to="/login"
              className="inline-flex h-12 items-center gap-2 rounded-[12px] border border-[#E5EAF3] bg-white px-7 text-[15px] font-medium text-[#475569] transition hover:border-[#1377EC] hover:text-[#1377EC]"
            >
              J'ai déjà un compte
            </Link>
          </div>
        </div>
        <div className="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-[#1377EC]/5 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-16 -left-16 h-72 w-72 rounded-full bg-[#22C55E]/5 blur-3xl" />
      </section>

      {/* Features */}
      <section id="features" className="px-6 py-20">
        <div className="mx-auto max-w-5xl">
          <div className="mb-12 text-center">
            <h2 className="text-[32px] font-bold text-[#0F172A]">Tout ce dont vous avez besoin</h2>
            <p className="mt-2 text-[15px] text-[#64748B]">Une application pensée pour les propriétaires d'animaux exigeants.</p>
          </div>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {features.map((f) => (
              <div key={f.title} className="rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm transition hover:shadow-md">
                <div className={`mb-4 inline-flex h-11 w-11 items-center justify-center rounded-[12px] ${f.color}`}>
                  <f.icon size={22} />
                </div>
                <h3 className="mb-2 text-[15px] font-semibold text-[#0F172A]">{f.title}</h3>
                <p className="text-[13px] leading-relaxed text-[#64748B]">{f.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section id="how" className="bg-[#F8FAFC] px-6 py-20">
        <div className="mx-auto max-w-4xl">
          <div className="mb-12 text-center">
            <h2 className="text-[32px] font-bold text-[#0F172A]">Comment ça marche ?</h2>
            <p className="mt-2 text-[15px] text-[#64748B]">Démarrez en 3 étapes simples, sans prise en main complexe.</p>
          </div>
          <div className="grid gap-6 sm:grid-cols-3">
            {steps.map((s) => (
              <div key={s.num} className="flex flex-col items-center text-center">
                <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#1377EC] text-[18px] font-bold text-white shadow-lg shadow-blue-200">
                  {s.num}
                </div>
                <h3 className="mb-2 text-[15px] font-semibold text-[#0F172A]">{s.title}</h3>
                <p className="text-[13px] text-[#64748B]">{s.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA bottom */}
      <section className="px-6 py-20 text-center">
        <div className="mx-auto max-w-xl">
          <h2 className="text-[30px] font-bold text-[#0F172A]">Prêt à simplifier la santé de vos animaux ?</h2>
          <p className="mt-3 text-[15px] text-[#64748B]">Rejoignez PetCare et dites adieu aux carnets perdus, aux rappels oubliés et aux informations éparpillées.</p>
          <Link
            to="/register"
            className="mt-7 inline-flex h-12 items-center gap-2 rounded-[12px] bg-[#1377EC] px-8 text-[15px] font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-[#0E68D0]"
          >
            Créer mon compte gratuitement <ChevronRight size={18} />
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-[#E5EAF3] px-6 py-8">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row">
          <div className="flex items-center gap-2">
            <img src={logo} alt="PetCare" className="h-7 w-7 rounded-full object-cover" />
            <span className="text-[14px] font-semibold text-[#0F172A]">PetCare</span>
          </div>
          <p className="text-[12px] text-[#94A3B8]">© 2026 PetCare — Gestionnaire de santé animale</p>
        </div>
      </footer>
    </div>
  );
}

export default Home;
