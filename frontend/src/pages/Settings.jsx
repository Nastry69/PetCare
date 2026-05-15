import { useState, useRef } from "react";
import { Camera } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import api from "../api/axios";

function Settings() {
  const { user, updateUser, logout } = useAuth();
  const photoInputRef = useRef(null);

  const [form, setForm] = useState({
    nom: user?.nom || "",
    prenom: user?.prenom || "",
    email: user?.email || "",
    password: "",
    confirm: "",
  });
  const [saving, setSaving] = useState(false);
  const [photoUploading, setPhotoUploading] = useState(false);
  const [success, setSuccess] = useState("");
  const [error, setError] = useState("");
  const [deleting, setDeleting] = useState(false);
  const [exporting, setExporting] = useState(false);

  const set = (field) => (e) => {
    setSuccess("");
    setError("");
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
  };

  const handlePhotoChange = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      setError("Le fichier doit être une image.");
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      setError("La photo ne doit pas dépasser 5 Mo.");
      return;
    }

    const data = new FormData();
    data.append("photo", file);
    setPhotoUploading(true);
    setError("");
    setSuccess("");
    try {
      const res = await api.post("/me/photo", data);
      updateUser(res.data);
      setSuccess("Photo de profil mise à jour.");
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de l'envoi de la photo.");
    } finally {
      setPhotoUploading(false);
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (form.password && form.password !== form.confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    if (form.password && form.password.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères.");
      return;
    }

    setSaving(true);
    try {
      const payload = { nom: form.nom, prenom: form.prenom, email: form.email };
      if (form.password) payload.password = form.password;

      const res = await api.put("/me", payload);
      updateUser(res.data);
      setForm((prev) => ({ ...prev, password: "", confirm: "" }));
      setSuccess("Profil mis à jour avec succès.");
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de la mise à jour.");
    } finally {
      setSaving(false);
    }
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const res = await api.get("/me/export");
      const blob = new Blob([JSON.stringify(res.data, null, 2)], { type: "application/json" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `petcare_export_${new Date().toISOString().split("T")[0]}.json`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError("Erreur lors de l'export de vos données.");
    } finally {
      setExporting(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm("Supprimer définitivement votre compte et toutes vos données ? Cette action est irréversible.")) return;
    setDeleting(true);
    try {
      await api.delete("/me");
      logout();
    } catch {
      setError("Erreur lors de la suppression du compte.");
      setDeleting(false);
    }
  };

  return (
    <div className="mx-auto max-w-[640px]">
      <div className="mb-6">
        <h1 className="text-[20px] font-bold text-[#0F172A]">Paramètres</h1>
        <p className="mt-1 text-[14px] text-[#64748B]">Gérez votre profil et vos préférences.</p>
      </div>

      <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <h2 className="mb-5 text-[16px] font-semibold text-[#0F172A]">Informations personnelles</h2>

        {success && (
          <div className="mb-4 rounded-[10px] bg-[#EAF8EF] px-4 py-3 text-[13px] text-[#22C55E]">{success}</div>
        )}
        {error && (
          <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{error}</div>
        )}

        <div className="mb-6 flex items-center gap-5">
          <input
            ref={photoInputRef}
            type="file"
            accept="image/*"
            onChange={handlePhotoChange}
            className="hidden"
          />
          <div className="relative">
            <button
              type="button"
              onClick={() => photoInputRef.current?.click()}
              disabled={photoUploading}
              title="Changer la photo de profil"
              className="relative h-[80px] w-[80px] overflow-hidden rounded-full outline-none transition hover:ring-2 hover:ring-[#1377EC] focus:ring-2 focus:ring-[#1377EC] disabled:cursor-wait disabled:opacity-70"
            >
              {user?.photoUrl ? (
                <img src={user.photoUrl} alt="avatar" className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full w-full items-center justify-center rounded-full bg-[#EAF3FF] text-[30px] font-bold text-[#1377EC]">
                  {user?.prenom?.[0]?.toUpperCase() || "?"}
                </div>
              )}
              {photoUploading && (
                <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black/30">
                  <span className="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                </div>
              )}
            </button>
            <span className="absolute bottom-0 right-0 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-[#1377EC] text-white shadow">
              <Camera size={11} />
            </span>
          </div>

          <div>
            <p className="text-[15px] font-semibold text-[#0F172A]">{user?.prenom} {user?.nom}</p>
            <p className="text-[13px] text-[#64748B]">{user?.email}</p>
            <button
              type="button"
              onClick={() => photoInputRef.current?.click()}
              disabled={photoUploading}
              className="mt-1.5 text-[12px] font-medium text-[#1377EC] hover:underline disabled:opacity-60"
            >
              {photoUploading ? "Upload en cours…" : "Changer la photo"}
            </button>
          </div>
        </div>

        <form onSubmit={handleSave} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Prénom</label>
              <input value={form.prenom} onChange={set("prenom")} required
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
            </div>
            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Nom</label>
              <input value={form.nom} onChange={set("nom")} required
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
            </div>
          </div>

          <div>
            <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Adresse email</label>
            <input type="email" value={form.email} onChange={set("email")} required
              className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
          </div>

          <div className="border-t border-[#EEF2F7] pt-4">
            <p className="mb-3 text-[13px] font-medium text-[#334155]">
              Changer le mot de passe{" "}
              <span className="font-normal text-[#94A3B8]">(laisser vide pour ne pas modifier)</span>
            </p>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1.5 block text-[13px] text-[#64748B]">Nouveau mot de passe</label>
                <input type="password" value={form.password} onChange={set("password")}
                  placeholder="8 caractères minimum"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
              </div>
              <div>
                <label className="mb-1.5 block text-[#64748B] text-[13px]">Confirmer</label>
                <input type="password" value={form.confirm} onChange={set("confirm")}
                  placeholder="••••••••"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
              </div>
            </div>
          </div>

          <button type="submit" disabled={saving}
            className="h-11 w-full rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60">
            {saving ? "Enregistrement…" : "Sauvegarder les modifications"}
          </button>
        </form>
      </div>

      <div className="mt-5 rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <h2 className="mb-2 text-[16px] font-semibold text-[#0F172A]">Confidentialité & RGPD</h2>
        <p className="mb-5 text-[13px] text-[#64748B]">
          Conformément au RGPD, vous pouvez exporter ou supprimer l'ensemble de vos données personnelles.
        </p>
        <div className="flex flex-col gap-3 sm:flex-row">
          <button onClick={handleExport} disabled={exporting}
            className="h-10 flex-1 rounded-[10px] border border-[#1377EC] text-[13px] font-semibold text-[#1377EC] hover:bg-[#F5F9FF] disabled:opacity-60">
            {exporting ? "Export en cours…" : "Exporter mes données"}
          </button>
          <button onClick={handleDelete} disabled={deleting}
            className="h-10 flex-1 rounded-[10px] border border-[#EF4444] text-[13px] font-semibold text-[#EF4444] hover:bg-[#FEF2F2] disabled:opacity-60">
            {deleting ? "Suppression…" : "Supprimer mon compte"}
          </button>
        </div>
      </div>

      <div className="mt-5 rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <h2 className="mb-2 text-[16px] font-semibold text-[#0F172A]">Session</h2>
        <button
          onClick={logout}
          className="h-10 rounded-[10px] border border-[#E5EAF3] px-5 text-[13px] font-semibold text-[#EF4444] hover:bg-[#FEF2F2]"
        >
          Se déconnecter
        </button>
      </div>
    </div>
  );
}

export default Settings;
