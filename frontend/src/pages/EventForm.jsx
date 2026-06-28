import { useState, useEffect } from "react";
import { useNavigate, useSearchParams, useParams } from "react-router-dom";
import api from "../api/axios";

function EventForm() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [searchParams] = useSearchParams();
  const defaultAnimalId = searchParams.get("animalId") || "";
  const isEdit = Boolean(id);

  const [animals, setAnimals] = useState([]);
  const [types, setTypes] = useState([]);
  const [form, setForm] = useState({
    animal_id: defaultAnimalId,
    type_evenement_id: "",
    date: "",
    heure: "",
    statut: "a_confirmer",
    commentaire: "",
    rappelActif: false,
    rappelJoursAvant: "",
  });
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    const requests = [api.get("/animals"), api.get("/type-evenements")];
    if (isEdit) requests.push(api.get(`/evenements/${id}`));

    Promise.all(requests)
      .then(([animalsRes, typesRes, eventRes]) => {
        setAnimals(animalsRes.data);
        setTypes(typesRes.data);
        if (eventRes) {
          const e = eventRes.data;
          const dt = e.dateHeureEvenement ? new Date(e.dateHeureEvenement) : null;
          setForm({
            animal_id: String(e.animal?.id || ""),
            type_evenement_id: String(e.typeEvenement?.id || ""),
            date: dt ? dt.toISOString().slice(0, 10) : "",
            heure: dt
              ? dt.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })
              : "",
            statut: e.statut || "a_confirmer",
            commentaire: e.commentaire || "",
            rappelActif: e.rappelActif || false,
            rappelJoursAvant: e.rappelJoursAvant != null ? String(e.rappelJoursAvant) : "",
          });
        }
      })
      .catch(() => navigate("/calendar"))
      .finally(() => setLoading(false));
  }, [id]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({ ...prev, [name]: type === "checkbox" ? checked : value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.animal_id || !form.type_evenement_id || !form.date) {
      setError("L'animal, le type d'événement et la date sont obligatoires.");
      return;
    }

    setError("");
    setSubmitting(true);
    try {
      const dateHeure = form.heure
        ? `${form.date} ${form.heure}:00`
        : `${form.date} 00:00:00`;

      const payload = {
        animal_id: parseInt(form.animal_id),
        type_evenement_id: parseInt(form.type_evenement_id),
        dateHeureEvenement: dateHeure,
        statut: form.statut,
        commentaire: form.commentaire || null,
        rappelActif: form.rappelActif,
        rappelJoursAvant: form.rappelJoursAvant ? parseInt(form.rappelJoursAvant) : null,
      };

      if (isEdit) {
        await api.patch(`/evenements/${id}`, payload);
      } else {
        await api.post("/evenements", payload);
      }

      navigate("/calendar");
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de l'enregistrement de l'événement.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <div className="flex items-center justify-center py-20 text-[#64748B]">Chargement…</div>;
  }

  return (
    <div className="mx-auto max-w-[720px]">
      <div className="mb-6">
        <p className="text-[13px] font-semibold text-[#1377EC]">Événement</p>
        <h1 className="mt-1 text-[28px] font-bold text-[#0F172A]">
          {isEdit ? "Modifier l'événement" : "Ajouter un événement"}
        </h1>
        <p className="mt-1 text-[14px] text-[#64748B]">
          {isEdit
            ? "Modifiez les informations de cet événement."
            : "Complétez les informations pour programmer un nouvel événement."}
        </p>
      </div>

      <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        {error && (
          <div className="mb-5 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{error}</div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          <div className="grid gap-5 md:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Animal *</label>
              <select name="animal_id" value={form.animal_id} onChange={handleChange} required
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]">
                <option value="">Sélectionner un animal</option>
                {animals.map((a) => (
                  <option key={a.id} value={a.id}>{a.nom} ({a.espece})</option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Type d'événement *</label>
              <select name="type_evenement_id" value={form.type_evenement_id} onChange={handleChange} required
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]">
                <option value="">Sélectionner un type</option>
                {types.map((t) => (
                  <option key={t.id} value={t.id}>{t.libelle}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Date *</label>
              <input type="date" name="date" value={form.date} onChange={handleChange} required
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
            </div>

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Heure</label>
              <input type="time" name="heure" value={form.heure} onChange={handleChange}
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
            </div>

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Statut</label>
              <select name="statut" value={form.statut} onChange={handleChange}
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]">
                <option value="a_confirmer">À confirmer</option>
                <option value="prevu">Prévu</option>
                <option value="effectue">Effectué</option>
                <option value="annule">Annulé</option>
              </select>
            </div>

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Rappel (jours avant)</label>
              <input type="number" name="rappelJoursAvant" value={form.rappelJoursAvant} onChange={handleChange}
                min="0" max="365" placeholder="ex: 3"
                disabled={!form.rappelActif}
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC] disabled:opacity-50" />
            </div>
          </div>

          <label className="flex cursor-pointer items-center gap-3 rounded-[10px] border border-[#E5EAF3] px-4 py-3">
            <input type="checkbox" name="rappelActif" checked={form.rappelActif} onChange={handleChange}
              className="h-4 w-4 accent-[#1377EC]" />
            <span className="text-[13px] font-medium text-[#334155]">Activer le rappel par email</span>
          </label>

          <div>
            <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Commentaire</label>
            <textarea name="commentaire" value={form.commentaire} onChange={handleChange}
              rows="4"
              placeholder="Ajouter une note ou une précision..."
              className="w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 py-3 text-[14px] text-[#0F172A] outline-none focus:border-[#1377EC]"
            />
          </div>

          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={submitting}
              className="h-11 rounded-[10px] bg-[#1377EC] px-6 text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60">
              {submitting ? "Enregistrement…" : isEdit ? "Mettre à jour" : "Enregistrer"}
            </button>
            <button type="button" onClick={() => navigate(-1)}
              className="h-11 rounded-[10px] border border-[#E5EAF3] px-6 text-[14px] font-medium text-[#64748B] hover:bg-[#F8FAFC]">
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default EventForm;
