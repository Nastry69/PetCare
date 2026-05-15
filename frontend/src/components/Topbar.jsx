import { useRef, useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { Search, X } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import api from "../api/axios";

function Topbar() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const searchRef = useRef(null);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState(null);
  const [searching, setSearching] = useState(false);

  useEffect(() => {
    function handleClick(e) {
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setResults(null);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  useEffect(() => {
    if (!query.trim()) {
      setResults(null);
      return;
    }
    const timer = setTimeout(async () => {
      setSearching(true);
      try {
        const [animalsRes, eventsRes] = await Promise.all([
          api.get("/animals"),
          api.get("/evenements"),
        ]);
        const q = query.toLowerCase();
        const matchedAnimals = animalsRes.data
          .filter((a) => a.nom?.toLowerCase().includes(q) || a.espece?.toLowerCase().includes(q))
          .slice(0, 4);
        const matchedEvents = eventsRes.data
          .filter((e) => {
            const dateStr = e.dateHeureEvenement
              ? new Date(e.dateHeureEvenement).toLocaleDateString("fr-FR")
              : "";
            return (
              e.typeEvenement?.libelle?.toLowerCase().includes(q) ||
              e.animal?.nom?.toLowerCase().includes(q) ||
              dateStr.includes(q)
            );
          })
          .slice(0, 4);
        setResults({ animals: matchedAnimals, events: matchedEvents });
      } catch {
        setResults({ animals: [], events: [] });
      } finally {
        setSearching(false);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [query]);

  const clearSearch = () => {
    setQuery("");
    setResults(null);
  };

  return (
    <header className="flex h-[74px] items-center justify-between border-b border-[#E5EAF3] bg-white px-6">
      <div className="relative w-full max-w-[370px]" ref={searchRef}>
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]" />
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Rechercher un animal, un événement..."
            className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] pl-9 pr-8 text-[14px] text-[#334155] outline-none placeholder:text-[#94A3B8] focus:border-[#1377EC]"
          />
          {query && (
            <button
              onClick={clearSearch}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8] hover:text-[#475569]"
            >
              <X size={13} />
            </button>
          )}
        </div>

        {results !== null && (
          <div className="absolute left-0 top-[calc(100%+6px)] z-50 w-full rounded-[12px] border border-[#E5EAF3] bg-white shadow-lg">
            {searching ? (
              <p className="px-4 py-3 text-[13px] text-[#94A3B8]">Recherche…</p>
            ) : results.animals.length === 0 && results.events.length === 0 ? (
              <p className="px-4 py-3 text-[13px] text-[#94A3B8]">Aucun résultat.</p>
            ) : (
              <>
                {results.animals.length > 0 && (
                  <div>
                    <p className="px-4 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-[#94A3B8]">
                      Animaux
                    </p>
                    {results.animals.map((a) => (
                      <button
                        key={a.id}
                        onClick={() => { navigate(`/animals/${a.id}`); clearSearch(); }}
                        className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-[#F8FAFC]"
                      >
                        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#EAF3FF] text-[12px]">
                          🐾
                        </div>
                        <div>
                          <p className="text-[13px] font-medium text-[#0F172A]">{a.nom}</p>
                          <p className="text-[11px] text-[#64748B]">{a.espece}</p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
                {results.events.length > 0 && (
                  <div className={results.animals.length > 0 ? "border-t border-[#EEF2F7]" : ""}>
                    <p className="px-4 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-[#94A3B8]">
                      Événements
                    </p>
                    {results.events.map((e) => (
                      <button
                        key={e.id}
                        onClick={() => { navigate(`/animals/${e.animal?.id}`); clearSearch(); }}
                        className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-[#F8FAFC]"
                      >
                        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#FFF4E5] text-[12px]">
                          📅
                        </div>
                        <div>
                          <p className="text-[13px] font-medium text-[#0F172A]">{e.typeEvenement?.libelle}</p>
                          <p className="text-[11px] text-[#64748B]">
                            {e.animal?.nom}
                            {e.dateHeureEvenement
                              ? ` · ${new Date(e.dateHeureEvenement).toLocaleDateString("fr-FR")}`
                              : ""}
                          </p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        )}
      </div>

      <div className="ml-6 flex items-center gap-6">
        <button className="text-[18px] text-[#64748B]">🔔</button>

        <div className="flex items-center gap-3">
          <div className="text-right">
            <p className="text-[13px] font-semibold text-[#0F172A]">
              {user ? `${user.prenom} ${user.nom}` : ""}
            </p>
            <p className="text-[11px] text-[#64748B]">Propriétaire</p>
          </div>

          {user?.photoUrl ? (
            <img src={user.photoUrl} alt="avatar" className="h-10 w-10 rounded-full object-cover" />
          ) : (
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#EAF3FF] text-[16px] font-semibold text-[#1377EC]">
              {user?.prenom?.[0]?.toUpperCase() || "?"}
            </div>
          )}
        </div>
      </div>
    </header>
  );
}

export default Topbar;
