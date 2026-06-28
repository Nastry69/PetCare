import { NavLink, useNavigate } from "react-router-dom";
import { LayoutDashboard, PawPrint, CalendarDays, Settings } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const navItems = [
  { label: "Tableau de bord", shortLabel: "Accueil", to: "/dashboard", icon: LayoutDashboard },
  { label: "Mes animaux", shortLabel: "Animaux", to: "/animals", icon: PawPrint },
  { label: "Calendrier", shortLabel: "Calendrier", to: "/calendar", icon: CalendarDays },
  { label: "Paramètres", shortLabel: "Réglages", to: "/settings", icon: Settings },
];

function Sidebar() {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate("/login");
  };

  return (
    <>
    <aside className="hidden w-[182px] shrink-0 border-r border-[#E5EAF3] bg-white lg:flex lg:flex-col">
      <div className="flex items-center gap-3 px-4 py-4">
        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#1377EC] text-[18px]">🐾</div>
        <div>
          <p className="text-[14px] font-bold leading-none text-[#0F172A]">PetCare</p>
          <p className="mt-1 text-[10px] text-[#64748B]">Gestionnaire d'animaux</p>
        </div>
      </div>

      <nav className="mt-4 flex flex-col gap-2 px-3">
        {navItems.map((item) => (
          <NavLink
            key={item.label}
            to={item.to}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-[10px] px-4 py-3 text-[14px] font-medium transition ${
                isActive ? "bg-[#EAF3FF] text-[#1377EC]" : "text-[#475569] hover:bg-[#F8FAFC]"
              }`
            }
          >
            <item.icon size={18} />
            {item.label}
          </NavLink>
        ))}
      </nav>

      <div className="mt-auto px-4 pb-6">
        <button
          onClick={handleLogout}
          className="text-[12px] font-medium text-[#EF4444] hover:text-[#DC2626]"
        >
          Déconnexion
        </button>
      </div>
    </aside>

    {/* ── Bottom navigation bar — mobile only ────────────────── */}
    <nav className="fixed bottom-0 left-0 right-0 z-50 flex border-t border-[#E5EAF3] bg-white lg:hidden">
      {navItems.map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          className={({ isActive }) =>
            `flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[11px] font-medium transition-colors ${
              isActive ? "text-[#1377EC]" : "text-[#64748B]"
            }`
          }
        >
          <item.icon size={20} />
          <span>{item.shortLabel}</span>
        </NavLink>
      ))}
    </nav>
    </>
  );
}

export default Sidebar;
