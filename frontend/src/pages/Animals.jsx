import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Plus, MoreHorizontal, CalendarPlus, SquarePen, Camera, X, Users } from "lucide-react";
import api from "../api/axios";
import { useAuth } from "../context/AuthContext";
import Tooltip from "../components/Tooltip";

function Animals() {
  const [animals, setAnimals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ nom: "", espece: "", race: "", sexe: "", dateNaissance: "" });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const navigate = useNavigate();

  const load = () => {
    api.get("/animals")
      .then((res) => setAnimals(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const set = (field) => (e) => setForm((prev) => ({ ...prev, [field]: e.target.value }));

  const handleCreate = async (e) => {
    e.preventDefault();
    if (!form.nom || !form.espece) {
      setError("Le nom et l'espèce sont obligatoires.");
      return;
    }
    setSubmitting(true);
    setError("");
    try {
      await api.post("/animals", form);
      setShowForm(false);
      setForm({ nom: "", espece: "", race: "", sexe: "", dateNaissance: "" });
      load();
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de la création.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="w-full">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-[44px] font-bold leading-none text-[#0F172A]">Mes Animaux</h1>
          <p className="mt-4 text-[24px] text-[#5B7FA9]">
            Gérez la santé et le bien-être de vos compagnons au quotidien.
          </p>
        </div>

        <button
          onClick={() => setShowForm(true)}
          className="inline-flex h-[52px] items-center gap-2 rounded-[12px] bg-[#1377EC] px-5 text-[16px] font-semibold text-white shadow-[0_6px_18px_rgba(19,119,236,0.25)] transition hover:bg-[#0E68D0]"
        >
          <Plus size={18} />
          Ajouter un animal
        </button>
      </div>

      {showForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-[480px] rounded-[18px] bg-white p-6 shadow-xl">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="text-[18px] font-bold text-[#0F172A]">Nouvel animal</h2>
              <button onClick={() => setShowForm(false)} className="text-[#94A3B8] hover:text-[#64748B]">
                <X size={20} />
              </button>
            </div>

            {error && (
              <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{error}</div>
            )}

            <form onSubmit={handleCreate} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="mb-1 block text-[13px] font-medium text-[#334155]">Nom *</label>
                  <input value={form.nom} onChange={set("nom")} placeholder="Rex" required
                    className="h-10 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                </div>
                <div>
                  <label className="mb-1 block text-[13px] font-medium text-[#334155]">Espèce *</label>
                  <input value={form.espece} onChange={set("espece")} placeholder="Chien, Chat…" required
                    className="h-10 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="mb-1 block text-[13px] font-medium text-[#334155]">Race</label>
                  <input value={form.race} onChange={set("race")} placeholder="Labrador…"
                    className="h-10 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                </div>
                <div>
                  <label className="mb-1 block text-[13px] font-medium text-[#334155]">Sexe</label>
                  <select value={form.sexe} onChange={set("sexe")}
                    className="h-10 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]">
                    <option value="">Non précisé</option>
                    <option value="male">Mâle</option>
                    <option value="femelle">Femelle</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="mb-1 block text-[13px] font-medium text-[#334155]">Date de naissance</label>
                <input type="date" value={form.dateNaissance} onChange={set("dateNaissance")}
                  className="h-10 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
              </div>

              <div className="flex gap-3 pt-2">
                <button type="submit" disabled={submitting}
                  className="h-10 flex-1 rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60">
                  {submitting ? "Enregistrement…" : "Ajouter"}
                </button>
                <button type="button" onClick={() => setShowForm(false)}
                  className="h-10 flex-1 rounded-[10px] border border-[#E5EAF3] text-[14px] font-medium text-[#64748B] hover:bg-[#F8FAFC]">
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {loading ? (
        <p className="mt-12 text-center text-[#94A3B8]">Chargement…</p>
      ) : (
        <>
          <section className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
            {animals.map((animal) => (
              <AnimalCard key={animal.id} animal={animal} onRefresh={load} />
            ))}
          </section>

          {animals.length === 0 && (
            <div className="mt-8 flex min-h-[180px] flex-col items-center justify-center rounded-[18px] border-2 border-dashed border-[#D7DEE9] bg-[#FAFBFD] px-6 text-center">
              <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#94A3B8] shadow-sm">
                <Camera size={22} />
              </div>
              <h3 className="mt-5 text-[22px] font-semibold text-[#475569]">Nouvel arrivant ?</h3>
              <p className="mt-3 max-w-[360px] text-[14px] leading-6 text-[#94A3B8]">
                Cliquez sur le bouton pour ajouter un nouveau membre à votre famille.
              </p>
            </div>
          )}
        </>
      )}
    </div>
  );
}

function AnimalCard({ animal, onRefresh }) {
  const navigate = useNavigate();
  const { user } = useAuth();
  const isOwner = user && animal.proprietaireId === user.id;

  return (
    <div className="overflow-hidden rounded-[18px] border border-[#E5EAF3] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
      <div className="relative h-[200px] w-full overflow-hidden bg-[#EAF3FF]">
        {animal.photoUrl ? (
          <img src={animal.photoUrl} alt={animal.nom} className="h-full w-full object-cover" />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-[64px]">🐾</div>
        )}
        {!isOwner && (
          <span className="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-[#64748B] shadow-sm backdrop-blur-sm">
            <Users size={11} />Partagé
          </span>
        )}
      </div>

      <div className="p-4">
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-[18px] font-bold text-[#0F172A]">{animal.nom}</h2>
            <p className="mt-1 text-[14px] text-[#64748B]">
              {animal.espece}{animal.race ? ` • ${animal.race}` : ""}
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
              <Tooltip text="Ajouter un événement">
                <button
                  onClick={() => navigate(`/events/new?animalId=${animal.id}`)}
                  className="rounded-full bg-[#F8FAFC] p-2 hover:bg-[#EEF2F7]"
                >
                  <CalendarPlus size={15} />
                </button>
              </Tooltip>
              <Tooltip text="Voir le profil">
                <button
                  onClick={() => navigate(`/animals/${animal.id}`)}
                  className="rounded-full bg-[#F8FAFC] p-2 hover:bg-[#EEF2F7]"
                >
                  <SquarePen size={15} />
                </button>
              </Tooltip>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Animals;
