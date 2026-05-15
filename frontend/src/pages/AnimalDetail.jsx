import { useState, useEffect, useRef } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import {
  CalendarDays, Camera, Pencil, Syringe, Pill, Scissors,
  Stethoscope, Save, X, UserPlus, Users,
} from "lucide-react";
import api from "../api/axios";
import { useAuth } from "../context/AuthContext";

const typeIcons = {
  Vaccin: Syringe,
  Traitement: Pill,
  Toilettage: Scissors,
  Consultation: Stethoscope,
};

function formatDate(str) {
  if (!str) return "";
  return new Date(str).toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit", year: "numeric" });
}

function AnimalDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [animal, setAnimal] = useState(null);
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [form, setForm] = useState({});
  const [saving, setSaving] = useState(false);
  const [photoUploading, setPhotoUploading] = useState(false);
  const [error, setError] = useState("");
  const photoInputRef = useRef(null);

  const [partages, setPartages] = useState([]);
  const [inviteEmail, setInviteEmail] = useState("");
  const [inviteRole, setInviteRole] = useState("lecture");
  const [inviting, setInviting] = useState(false);
  const [removingId, setRemovingId] = useState(null);
  const [partageError, setPartageError] = useState("");
  const [partageSuccess, setPartageSuccess] = useState("");

  const isOwner = animal !== null && user !== null && animal.proprietaireId === user.id;

  useEffect(() => {
    Promise.all([
      api.get(`/animals/${id}`),
      api.get("/evenements"),
      api.get(`/partages/animal/${id}`).catch(() => ({ data: [] })),
    ])
      .then(([animalRes, eventsRes, partagesRes]) => {
        setAnimal(animalRes.data);
        setForm({
          nom: animalRes.data.nom,
          espece: animalRes.data.espece,
          race: animalRes.data.race || "",
          sexe: animalRes.data.sexe || "",
          dateNaissance: animalRes.data.dateNaissance || "",
        });
        const animalEvents = eventsRes.data.filter((e) => e.animal?.id === parseInt(id));
        setEvents(animalEvents);
        setPartages(partagesRes.data);
      })
      .catch(() => navigate("/animals"))
      .finally(() => setLoading(false));
  }, [id]);

  const handleSave = async () => {
    setSaving(true);
    setError("");
    try {
      const res = await api.patch(`/animals/${id}`, form);
      setAnimal(res.data);
      setEditing(false);
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de la mise à jour.");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm(`Supprimer ${animal.nom} ? Cette action est irréversible.`)) return;
    try {
      await api.delete(`/animals/${id}`);
      navigate("/animals");
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de la suppression.");
    }
  };

  const handlePhotoChange = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    if (!file.type.startsWith("image/")) { setError("Le fichier doit être une image."); return; }
    if (file.size > 5 * 1024 * 1024) { setError("La photo ne doit pas dépasser 5 Mo."); return; }
    const data = new FormData();
    data.append("photo", file);
    setPhotoUploading(true);
    setError("");
    try {
      const res = await api.post(`/animals/${id}/photo`, data);
      setAnimal(res.data);
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.detail || "Erreur lors de l'envoi de la photo.");
    } finally {
      setPhotoUploading(false);
    }
  };

  const handleInvite = async (e) => {
    e.preventDefault();
    if (!inviteEmail.trim()) return;
    setInviting(true);
    setPartageError("");
    setPartageSuccess("");
    try {
      const res = await api.post("/partages", {
        animal_id: parseInt(id),
        email: inviteEmail,
        rolePartage: inviteRole,
      });
      setPartages((prev) => [...prev, res.data]);
      setInviteEmail("");
      setPartageSuccess(`${res.data.utilisateur.prenom} ${res.data.utilisateur.nom} a bien été invité(e).`);
    } catch (err) {
      setPartageError(err.response?.data?.message || "Erreur lors de l'invitation.");
    } finally {
      setInviting(false);
    }
  };

  const handleRemovePartage = async (partageId) => {
    if (!confirm("Retirer l'accès à cette personne ?")) return;
    setRemovingId(partageId);
    try {
      await api.delete(`/partages/${partageId}`);
      setPartages((prev) => prev.filter((p) => p.id !== partageId));
    } catch {
      // silently ignore
    } finally {
      setRemovingId(null);
    }
  };

  const handleUpdateRole = async (partageId, newRole) => {
    try {
      const res = await api.patch(`/partages/${partageId}`, { rolePartage: newRole });
      setPartages((prev) => prev.map((p) => (p.id === partageId ? res.data : p)));
    } catch {
      // silently ignore
    }
  };

  if (loading) {
    return <div className="flex items-center justify-center py-20 text-[#64748B]">Chargement…</div>;
  }

  if (!animal) return null;

  const upcomingEvents = events.filter((e) => new Date(e.dateHeureEvenement) >= new Date());
  const pastEvents = events.filter((e) => new Date(e.dateHeureEvenement) < new Date());
  const set = (field) => (e) => setForm((prev) => ({ ...prev, [field]: e.target.value }));

  return (
    <div className="w-full">
      {/* ── Header ── */}
      <section className="border-b border-[#DDE5F0] pb-6">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
          <div className="relative h-[160px] w-[160px]">
            <input ref={photoInputRef} type="file" accept="image/*" onChange={handlePhotoChange} className="hidden" />
            <button
              type="button"
              onClick={() => photoInputRef.current?.click()}
              disabled={photoUploading}
              className="relative h-full w-full overflow-hidden rounded-[14px] bg-[#EAF3FF] text-[#64748B] outline-none transition hover:ring-2 hover:ring-[#1377EC] focus:ring-2 focus:ring-[#1377EC] disabled:cursor-wait disabled:opacity-70"
              title="Changer la photo"
            >
              {animal.photoUrl ? (
                <img src={animal.photoUrl} alt={animal.nom} className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-[64px]">🐾</div>
              )}
              <span className="absolute bottom-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#475569] shadow">
                {photoUploading ? (
                  <span className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#CBD5E1] border-t-[#1377EC]" />
                ) : (
                  <Camera size={14} />
                )}
              </span>
            </button>
          </div>

          <div className="flex-1 pt-1">
            {editing ? (
              <div className="space-y-3">
                {error && (
                  <div className="rounded-[10px] bg-[#FEECEC] px-4 py-2 text-[13px] text-[#EF4444]">{error}</div>
                )}
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="mb-1 block text-[12px] font-medium text-[#64748B]">Nom</label>
                    <input value={form.nom} onChange={set("nom")} required
                      className="h-10 w-full rounded-[10px] border border-[#E5EAF3] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                  </div>
                  <div>
                    <label className="mb-1 block text-[12px] font-medium text-[#64748B]">Espèce</label>
                    <input value={form.espece} onChange={set("espece")} required
                      className="h-10 w-full rounded-[10px] border border-[#E5EAF3] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                  </div>
                  <div>
                    <label className="mb-1 block text-[12px] font-medium text-[#64748B]">Race</label>
                    <input value={form.race} onChange={set("race")}
                      className="h-10 w-full rounded-[10px] border border-[#E5EAF3] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                  </div>
                  <div>
                    <label className="mb-1 block text-[12px] font-medium text-[#64748B]">Sexe</label>
                    <select value={form.sexe} onChange={set("sexe")}
                      className="h-10 w-full rounded-[10px] border border-[#E5EAF3] px-3 text-[14px] outline-none focus:border-[#1377EC]">
                      <option value="">Non précisé</option>
                      <option value="male">Mâle</option>
                      <option value="femelle">Femelle</option>
                    </select>
                  </div>
                  <div>
                    <label className="mb-1 block text-[12px] font-medium text-[#64748B]">Date de naissance</label>
                    <input type="date" value={form.dateNaissance} onChange={set("dateNaissance")}
                      className="h-10 w-full rounded-[10px] border border-[#E5EAF3] px-3 text-[14px] outline-none focus:border-[#1377EC]" />
                  </div>
                </div>
                <div className="flex gap-3">
                  <button onClick={handleSave} disabled={saving}
                    className="inline-flex items-center gap-1.5 rounded-[10px] bg-[#1377EC] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60">
                    <Save size={14} />{saving ? "Enregistrement…" : "Sauvegarder"}
                  </button>
                  <button onClick={() => { setEditing(false); setError(""); }}
                    className="inline-flex items-center gap-1.5 rounded-[10px] border border-[#E5EAF3] px-4 py-2 text-[13px] font-medium text-[#64748B] hover:bg-[#F8FAFC]">
                    <X size={14} />Annuler
                  </button>
                </div>
              </div>
            ) : (
              <>
                <h1 className="text-[56px] font-bold leading-none text-[#0F172A]">{animal.nom.toUpperCase()}</h1>
                <div className="mt-3 flex flex-wrap items-center gap-4 text-[18px] text-[#64748B]">
                  <span>🐾 {animal.espece}{animal.race ? ` · ${animal.race}` : ""}</span>
                  {animal.dateNaissance && <span>🎂 Né(e) le {formatDate(animal.dateNaissance)}</span>}
                </div>
                {animal.sexe && (
                  <div className="mt-4">
                    <span className="rounded-full bg-[#EAF3FF] px-3 py-1 text-[12px] font-semibold tracking-[0.04em] text-[#1377EC]">
                      {animal.sexe === "male" ? "MÂLE" : "FEMELLE"}
                    </span>
                  </div>
                )}
                {!isOwner && (
                  <div className="mt-3">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-[#F1F5F9] px-3 py-1 text-[12px] font-medium text-[#64748B]">
                      <Users size={12} />
                      Accès partagé
                    </span>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
        {!editing && error && (
          <div className="mt-4 rounded-[10px] bg-[#FEECEC] px-4 py-2 text-[13px] text-[#EF4444]">{error}</div>
        )}
      </section>

      {/* ── Events ── */}
      <section className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1fr]">
        <div>
          <h2 className="mb-4 flex items-center gap-2 text-[18px] font-semibold text-[#94A3B8]">
            <CalendarDays size={18} />Prochains événements
          </h2>
          <div className="overflow-hidden rounded-[14px] border border-[#DDE5F0] bg-white">
            {upcomingEvents.length === 0 ? (
              <p className="p-6 text-center text-[13px] text-[#94A3B8]">Aucun événement à venir.</p>
            ) : (
              <table className="min-w-full">
                <thead>
                  <tr className="bg-[#F8FAFC] text-left text-[11px] uppercase tracking-[0.08em] text-[#94A3B8]">
                    <th className="px-4 py-3 font-semibold">Date</th>
                    <th className="px-4 py-3 font-semibold">Type</th>
                    <th className="px-4 py-3 font-semibold">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  {upcomingEvents.map((event) => {
                    const Icon = typeIcons[event.typeEvenement?.libelle] || CalendarDays;
                    return (
                      <tr key={event.id} className="border-t border-[#EEF2F7]">
                        <td className="px-4 py-4 text-[14px] text-[#475569]">{formatDate(event.dateHeureEvenement)}</td>
                        <td className="px-4 py-4">
                          <span className="inline-flex items-center gap-1 rounded-[6px] bg-[#EAF3FF] px-2 py-1 text-[11px] font-medium text-[#1377EC]">
                            <Icon size={12} />{event.typeEvenement?.libelle}
                          </span>
                        </td>
                        <td className="px-4 py-4 text-[13px] text-[#475569]">{event.statut}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </div>
        </div>

        <div>
          <h2 className="mb-4 flex items-center gap-2 text-[18px] font-semibold text-[#94A3B8]">
            <span className="text-[20px]">↻</span>Historique
          </h2>
          <div className="rounded-[14px] border border-[#DDE5F0] bg-white p-3">
            {pastEvents.length === 0 ? (
              <p className="p-6 text-center text-[13px] text-[#94A3B8]">Aucun historique.</p>
            ) : (
              <div className="space-y-3">
                {pastEvents.slice(0, 3).map((event) => {
                  const Icon = typeIcons[event.typeEvenement?.libelle] || CalendarDays;
                  return (
                    <div key={event.id} className="flex items-start justify-between rounded-[12px] border border-[#EEF2F7] bg-[#FCFDFE] p-4">
                      <div className="flex gap-4">
                        <div className="mt-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#F5F9FF] text-[#1377EC]">
                          <Icon size={18} />
                        </div>
                        <div>
                          <h3 className="text-[16px] font-semibold text-[#1E293B]">{event.typeEvenement?.libelle}</h3>
                          {event.commentaire && (
                            <p className="mt-2 max-w-[320px] text-[14px] leading-6 text-[#64748B]">"{event.commentaire}"</p>
                          )}
                        </div>
                      </div>
                      <div className="pl-4 text-[12px] text-[#94A3B8]">{formatDate(event.dateHeureEvenement)}</div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* ── Action buttons ── */}
      <section className="mt-10 flex flex-wrap gap-3">
        <Link
          to={`/events/new?animalId=${id}`}
          className="flex h-[56px] items-center justify-center gap-2 rounded-[12px] bg-[#1377EC] px-6 text-[16px] font-semibold text-white shadow-[0_6px_18px_rgba(19,119,236,0.25)] transition hover:bg-[#0E68D0]"
        >
          <CalendarDays size={18} />Ajouter un événement
        </Link>

        {isOwner && !editing && (
          <button
            onClick={() => setEditing(true)}
            className="flex h-[56px] items-center justify-center gap-2 rounded-[12px] border-2 border-[#1377EC] bg-white px-6 text-[16px] font-semibold text-[#1377EC] transition hover:bg-[#F5F9FF]"
          >
            <Pencil size={18} />Modifier le profil
          </button>
        )}

        {isOwner && (
          <button
            onClick={handleDelete}
            className="flex h-[56px] items-center justify-center gap-2 rounded-[12px] border-2 border-[#EF4444] bg-white px-6 text-[16px] font-semibold text-[#EF4444] transition hover:bg-[#FEF2F2]"
          >
            Supprimer l'animal
          </button>
        )}
      </section>

      {/* ── Sharing section (owner only) ── */}
      {isOwner && (
        <section className="mt-8 rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
          <h2 className="mb-5 flex items-center gap-2 text-[16px] font-semibold text-[#0F172A]">
            <Users size={17} className="text-[#1377EC]" />
            Gestion des accès
          </h2>

          {partageError && (
            <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{partageError}</div>
          )}
          {partageSuccess && (
            <div className="mb-4 rounded-[10px] bg-[#EAF8EF] px-4 py-3 text-[13px] text-[#22C55E]">{partageSuccess}</div>
          )}

          {partages.length > 0 ? (
            <div className="mb-6 space-y-2">
              {partages.map((partage) => (
                <div
                  key={partage.id}
                  className="flex items-center justify-between gap-3 rounded-[12px] border border-[#EEF2F7] bg-[#F8FAFC] px-4 py-3"
                >
                  <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#EAF3FF] text-[13px] font-bold text-[#1377EC]">
                      {partage.utilisateur.prenom?.[0]?.toUpperCase() || "?"}
                    </div>
                    <div>
                      <p className="text-[14px] font-medium text-[#0F172A]">
                        {partage.utilisateur.prenom} {partage.utilisateur.nom}
                      </p>
                      <p className="text-[12px] text-[#64748B]">{partage.utilisateur.email}</p>
                    </div>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    <select
                      value={partage.rolePartage}
                      onChange={(e) => handleUpdateRole(partage.id, e.target.value)}
                      className="h-8 rounded-[8px] border border-[#E5EAF3] bg-white px-2 text-[12px] outline-none focus:border-[#1377EC]"
                    >
                      <option value="lecture">Lecture</option>
                      <option value="ecriture">Écriture</option>
                    </select>
                    <button
                      onClick={() => handleRemovePartage(partage.id)}
                      disabled={removingId === partage.id}
                      className="h-8 rounded-[8px] border border-[#EF4444] px-3 text-[12px] font-medium text-[#EF4444] hover:bg-[#FEF2F2] disabled:opacity-60"
                    >
                      {removingId === partage.id ? "…" : "Retirer"}
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="mb-6 text-[13px] text-[#94A3B8]">Aucune personne invitée pour l'instant.</p>
          )}

          <div className="border-t border-[#EEF2F7] pt-5">
            <p className="mb-3 flex items-center gap-2 text-[14px] font-semibold text-[#334155]">
              <UserPlus size={15} />Inviter une personne
            </p>
            <form onSubmit={handleInvite} className="flex flex-col gap-3 sm:flex-row">
              <input
                type="email"
                value={inviteEmail}
                onChange={(e) => { setInviteEmail(e.target.value); setPartageError(""); setPartageSuccess(""); }}
                placeholder="email@exemple.fr"
                required
                className="h-11 flex-1 rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC]"
              />
              <select
                value={inviteRole}
                onChange={(e) => setInviteRole(e.target.value)}
                className="h-11 rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-3 text-[14px] outline-none focus:border-[#1377EC]"
              >
                <option value="lecture">Lecture seule</option>
                <option value="ecriture">Écriture (gérer les événements)</option>
              </select>
              <button
                type="submit"
                disabled={inviting || !inviteEmail.trim()}
                className="inline-flex h-11 items-center gap-2 rounded-[10px] bg-[#1377EC] px-5 text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60"
              >
                <UserPlus size={15} />{inviting ? "Envoi…" : "Inviter"}
              </button>
            </form>
            <p className="mt-2 text-[11px] text-[#94A3B8]">
              La personne doit avoir un compte PetCare.
              <strong className="font-semibold text-[#64748B]"> Lecture</strong> = consulter ;
              <strong className="font-semibold text-[#64748B]"> Écriture</strong> = gérer les événements (pas supprimer l'animal).
            </p>
          </div>
        </section>
      )}
    </div>
  );
}

export default AnimalDetail;
