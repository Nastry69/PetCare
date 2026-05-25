import { NavLink, useNavigate } from "react-router-dom";
import { LayoutDashboard, PawPrint, CalendarDays, Settings } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const navItems = [
  { label: "Tableau de bord", to: "/dashboard", icon: LayoutDashboard },
  { label: "Mes animaux", to: "/animals", icon: PawPrint },
  { label: "Calendrier", to: "/calendar", icon: CalendarDays },
  { label: "Paramètres", to: "/settings", icon: Settings },
];

function Sidebar() {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate("/login");
  };

  return (
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
  );
}

export default Sidebar;
