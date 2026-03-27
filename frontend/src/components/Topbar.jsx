import chien from "../assets/moi anime.png";

function Topbar() {
  return (
    <header className="flex h-[74px] items-center justify-between border-b border-[#E5EAF3] bg-white px-6">
      <div className="w-full max-w-[370px]">
        <input
          type="text"
          placeholder="Rechercher un soin, un animal..."
          className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] text-[#334155] outline-none placeholder:text-[#94A3B8]"
        />
      </div>

      <div className="ml-6 flex items-center gap-6">
        <button className="text-[#64748B]">🔔</button>

        <div className="flex items-center gap-3">
          <div className="text-right">
            <p className="text-[13px] font-semibold text-[#0F172A]">Max Dupont</p>
            <p className="text-[11px] text-[#64748B]">Propriétaire</p>
          </div>
          <img
            src={chien}
            alt="avatar"
            className="h-10 w-10 rounded-full object-cover"
          />
        </div>
      </div>
    </header>
  );
}

export default Topbar;