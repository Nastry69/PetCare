import { useState, useEffect, useRef } from "react";
import { createPortal } from "react-dom";
import { Link } from "react-router-dom";
import { Camera, Download, Trash2, X, FileJson, FileText, FileSpreadsheet, Globe, Users } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import api from "../api/axios";

// ── Helpers export ───────────────────────────────────────────────────────────

function toCSV(data) {
  const rows = [];
  rows.push("=== INFORMATIONS PERSONNELLES ===");
  rows.push(["Nom", "Prénom", "Email", "Date inscription"].join(";"));
  rows.push([data.user.nom, data.user.prenom, data.user.email, data.user.dateInscription].join(";"));
  rows.push("");
  rows.push("=== ANIMAUX ===");
  rows.push(["Nom", "Espèce", "Race", "Date naissance", "Sexe"].join(";"));
  (data.animals || []).forEach((a) =>
    rows.push([a.nom, a.espece, a.race || "", a.dateNaissance || "", a.sexe || ""].join(";"))
  );
  rows.push("");
  rows.push("=== ÉVÉNEMENTS ===");
  rows.push(["Animal", "Type", "Date", "Statut", "Commentaire"].join(";"));
  (data.evenements || []).forEach((e) =>
    rows.push([e.animal || "", e.typeEvenement || "", e.dateHeureEvenement || "", e.statut || "", e.commentaire || ""].join(";"))
  );
  return "﻿" + rows.join("\n");
}

function toHTML(data) {
  const animalRows = (data.animals || [])
    .map((a) => `<tr><td>${a.nom}</td><td>${a.espece}</td><td>${a.race||"-"}</td><td>${a.dateNaissance||"-"}</td><td>${a.sexe||"-"}</td></tr>`)
    .join("");
  const eventRows = (data.evenements || [])
    .map((e) => `<tr><td>${e.animal||"-"}</td><td>${e.typeEvenement||"-"}</td><td>${e.dateHeureEvenement||"-"}</td><td>${e.statut||"-"}</td><td>${e.commentaire||"-"}</td></tr>`)
    .join("");
  return `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
  <title>Export PetCare</title>
  <style>body{font-family:sans-serif;max-width:900px;margin:40px auto;color:#0F172A}
  h1{color:#1377EC}h2{color:#334155;margin-top:32px}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th{background:#1377EC;color:#fff;padding:10px 14px;text-align:left;font-size:13px}
  td{padding:9px 14px;border-bottom:1px solid #E2E8F0;font-size:13px}
  tr:nth-child(even) td{background:#F8FAFC}
  .meta{background:#EAF3FF;border-radius:10px;padding:16px 20px;margin-bottom:24px}
  .meta p{margin:4px 0;font-size:14px}</style></head>
  <body>
  <h1>🐾 Export PetCare</h1>
  <div class="meta">
    <p><strong>Nom :</strong> ${data.user.prenom} ${data.user.nom}</p>
    <p><strong>Email :</strong> ${data.user.email}</p>
    <p><strong>Membre depuis :</strong> ${data.user.dateInscription}</p>
    <p><strong>Exporté le :</strong> ${data.exportedAt}</p>
  </div>
  <h2>🐶 Animaux (${(data.animals||[]).length})</h2>
  <table><thead><tr><th>Nom</th><th>Espèce</th><th>Race</th><th>Naissance</th><th>Sexe</th></tr></thead>
  <tbody>${animalRows||"<tr><td colspan='5'>Aucun animal</td></tr>"}</tbody></table>
  <h2>📅 Événements (${(data.evenements||[]).length})</h2>
  <table><thead><tr><th>Animal</th><th>Type</th><th>Date</th><th>Statut</th><th>Commentaire</th></tr></thead>
  <tbody>${eventRows||"<tr><td colspan='5'>Aucun événement</td></tr>"}</tbody></table>
  </body></html>`;
}

function toXLS(data) {
  const animalRows = (data.animals || [])
    .map((a) => `<tr><td>${a.nom}</td><td>${a.espece}</td><td>${a.race||""}</td><td>${a.dateNaissance||""}</td><td>${a.sexe||""}</td></tr>`)
    .join("");
  const eventRows = (data.evenements || [])
    .map((e) => `<tr><td>${e.animal||""}</td><td>${e.typeEvenement||""}</td><td>${e.dateHeureEvenement||""}</td><td>${e.statut||""}</td><td>${e.commentaire||""}</td></tr>`)
    .join("");
  return `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
  <head><meta charset="UTF-8"></head><body>
  <table><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Inscription</th></tr>
  <tr><td>${data.user.nom}</td><td>${data.user.prenom}</td><td>${data.user.email}</td><td>${data.user.dateInscription}</td></tr></table>
  <br/><table><thead><tr><th>Nom</th><th>Espèce</th><th>Race</th><th>Naissance</th><th>Sexe</th></tr></thead>
  <tbody>${animalRows}</tbody></table>
  <br/><table><thead><tr><th>Animal</th><th>Type</th><th>Date</th><th>Statut</th><th>Commentaire</th></tr></thead>
  <tbody>${eventRows}</tbody></table>
  </body></html>`;
}

function downloadBlob(content, filename, mime) {
  const blob = new Blob([content], { type: mime });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

// ── Modale via createPortal ──────────────────────────────────────────────────

function Modal({ onClose, children }) {
  return createPortal(
    <div
      className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4"
      onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div
        className="w-full max-w-md rounded-[20px] bg-white shadow-2xl animate-fade-in"
        onMouseDown={(e) => e.stopPropagation()}
      >
        {children}
      </div>
    </div>,
    document.body
  );
}

// ── Page Settings ────────────────────────────────────────────────────────────

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
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showExportModal, setShowExportModal] = useState(false);
  const [sharedAnimals, setSharedAnimals] = useState([]);
  const [sharedLoading, setSharedLoading] = useState(true);
  const [leavingId, setLeavingId] = useState(null);

  useEffect(() => {
    api.get("/partages")
      .then((res) => setSharedAnimals(res.data))
      .catch(() => {})
      .finally(() => setSharedLoading(false));
  }, []);

  const set = (field) => (e) => {
    setSuccess(""); setError("");
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
  };

  const handlePhotoChange = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    if (!file.type.startsWith("image/")) { setError("Le fichier doit être une image."); return; }
    if (file.size > 5 * 1024 * 1024) { setError("La photo ne doit pas dépasser 5 Mo."); return; }
    const data = new FormData();
    data.append("photo", file);
    setPhotoUploading(true); setError(""); setSuccess("");
    try {
      const res = await api.post("/me/photo", data);
      updateUser(res.data);
      setSuccess("Photo de profil mise à jour.");
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de l'envoi de la photo.");
    } finally { setPhotoUploading(false); }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setError(""); setSuccess("");
    if (form.password && form.password !== form.confirm) { setError("Les mots de passe ne correspondent pas."); return; }
    if (form.password && form.password.length < 8) { setError("Le mot de passe doit contenir au moins 8 caractères."); return; }
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
    } finally { setSaving(false); }
  };

  const handleExport = async (format) => {
    setExporting(true);
    setShowExportModal(false);
    try {
      const res = await api.get("/me/export");
      const data = res.data;
      const date = new Date().toISOString().split("T")[0];
      if (format === "json")     downloadBlob(JSON.stringify(data, null, 2), `petcare_export_${date}.json`, "application/json");
      else if (format === "csv") downloadBlob(toCSV(data),  `petcare_export_${date}.csv`,  "text/csv;charset=utf-8;");
      else if (format === "html")downloadBlob(toHTML(data), `petcare_export_${date}.html`, "text/html;charset=utf-8;");
      else if (format === "xls") downloadBlob(toXLS(data),  `petcare_export_${date}.xls`,  "application/vnd.ms-excel");
    } catch {
      setError("Erreur lors de l'export de vos données.");
    } finally { setExporting(false); }
  };

  const handleDelete = async () => {
    setDeleting(true);
    setShowDeleteModal(false);
    try {
      await api.delete("/me");
      logout();
    } catch {
      setError("Erreur lors de la suppression du compte.");
      setDeleting(false);
    }
  };

  const handleLeave = async (partageId) => {
    setLeavingId(partageId);
    try {
      await api.delete(`/partages/${partageId}`);
      setSharedAnimals((prev) => prev.filter((p) => p.id !== partageId));
    } catch {
      setError("Erreur lors de la suppression du partage.");
    } finally {
      setLeavingId(null);
    }
  };

  return (
    <div className="mx-auto max-w-[640px]">

      {/* ── Modale suppression ── */}
      {showDeleteModal && (
        <Modal onClose={() => setShowDeleteModal(false)}>
          <div className="p-6">
            <div className="mb-4 flex items-start justify-between">
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-[#FEF2F2]">
                <Trash2 size={20} className="text-[#EF4444]" />
              </div>
              <button
                onClick={() => setShowDeleteModal(false)}
                className="rounded-lg p-1.5 text-[#94A3B8] hover:bg-[#F1F5F9] hover:text-[#475569]"
              >
                <X size={18} />
              </button>
            </div>
            <h3 className="mb-2 text-[17px] font-bold text-[#0F172A]">Supprimer mon compte</h3>
            <p className="mb-3 text-[14px] text-[#475569]">
              Vous êtes sur le point de supprimer définitivement votre compte ainsi que :
            </p>
            <ul className="mb-4 space-y-2 text-[13px] text-[#64748B]">
              <li className="flex items-center gap-2"><span className="font-bold text-[#EF4444]">✕</span> Tous vos animaux et leurs informations</li>
              <li className="flex items-center gap-2"><span className="font-bold text-[#EF4444]">✕</span> Tous vos événements et rappels</li>
              <li className="flex items-center gap-2"><span className="font-bold text-[#EF4444]">✕</span> Tous vos partages</li>
              <li className="flex items-center gap-2"><span className="font-bold text-[#EF4444]">✕</span> Votre profil et vos données personnelles</li>
            </ul>
            <div className="mb-5 rounded-[10px] bg-[#FEF2F2] px-4 py-3 text-[13px] font-medium text-[#EF4444]">
              ⚠️ Cette action est irréversible. Vos données ne pourront pas être récupérées.
            </div>
            <div className="flex gap-3">
              <button
                onClick={() => setShowDeleteModal(false)}
                className="h-11 flex-1 rounded-[10px] border border-[#E5EAF3] text-[14px] font-semibold text-[#475569] hover:bg-[#F8FAFC] transition"
              >
                Annuler
              </button>
              <button
                onClick={handleDelete}
                disabled={deleting}
                className="h-11 flex-1 rounded-[10px] bg-[#EF4444] text-[14px] font-semibold text-white hover:bg-[#DC2626] transition disabled:opacity-60"
              >
                {deleting ? "Suppression…" : "Oui, supprimer"}
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* ── Modale export ── */}
      {showExportModal && (
        <Modal onClose={() => setShowExportModal(false)}>
          <div className="p-6">
            <div className="mb-4 flex items-start justify-between">
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-[#EAF3FF]">
                <Download size={20} className="text-[#1377EC]" />
              </div>
              <button
                onClick={() => setShowExportModal(false)}
                className="rounded-lg p-1.5 text-[#94A3B8] hover:bg-[#F1F5F9] hover:text-[#475569]"
              >
                <X size={18} />
              </button>
            </div>
            <h3 className="mb-1 text-[17px] font-bold text-[#0F172A]">Exporter mes données</h3>
            <p className="mb-5 text-[13px] text-[#64748B]">
              Choisissez un format. Vos animaux, événements et informations personnelles seront inclus.
            </p>
            <div className="grid grid-cols-2 gap-3 mb-4">
              {[
                { fmt: "json", icon: FileJson,       label: "JSON",  desc: "Format universel",  color: "#F59E0B", bg: "#FFFBEB", border: "#FDE68A" },
                { fmt: "csv",  icon: FileText,        label: "CSV",   desc: "Tableur / Calc",    color: "#22C55E", bg: "#F0FDF4", border: "#BBF7D0" },
                { fmt: "xls",  icon: FileSpreadsheet, label: "XLS",   desc: "Microsoft Excel",   color: "#1377EC", bg: "#EAF3FF", border: "#BFDBFE" },
                { fmt: "html", icon: Globe,           label: "HTML",  desc: "Rapport navigateur",color: "#8B5CF6", bg: "#F5F3FF", border: "#DDD6FE" },
              ].map(({ fmt, icon: Icon, label, desc, color, bg, border }) => (
                <button
                  key={fmt}
                  onClick={() => handleExport(fmt)}
                  className="flex flex-col items-center gap-2 rounded-[14px] border-2 p-4 text-center transition hover:scale-[1.02] active:scale-[0.98]"
                  style={{ background: bg, borderColor: border, color }}
                >
                  <Icon size={26} />
                  <span className="text-[15px] font-bold">{label}</span>
                  <span className="text-[12px]" style={{ color: "#64748B" }}>{desc}</span>
                </button>
              ))}
            </div>
            <button
              onClick={() => setShowExportModal(false)}
              className="h-10 w-full rounded-[10px] border border-[#E5EAF3] text-[13px] font-medium text-[#64748B] hover:bg-[#F8FAFC] transition"
            >
              Annuler
            </button>
          </div>
        </Modal>
      )}

      <div className="mb-6">
        <h1 className="text-[20px] font-bold text-[#0F172A]">Paramètres</h1>
        <p className="mt-1 text-[14px] text-[#64748B]">Gérez votre profil et vos préférences.</p>
      </div>

      <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <h2 className="mb-5 text-[16px] font-semibold text-[#0F172A]">Informations personnelles</h2>
        {success && <div className="mb-4 rounded-[10px] bg-[#EAF8EF] px-4 py-3 text-[13px] text-[#22C55E]">{success}</div>}
        {error   && <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">{error}</div>}

        <div className="mb-6 flex items-center gap-5">
          <input ref={photoInputRef} type="file" accept="image/*" onChange={handlePhotoChange} className="hidden" />
          <div className="relative">
            <button type="button" onClick={() => photoInputRef.current?.click()} disabled={photoUploading}
              title="Changer la photo de profil"
              className="relative h-[80px] w-[80px] overflow-hidden rounded-full outline-none transition hover:ring-2 hover:ring-[#1377EC] focus:ring-2 focus:ring-[#1377EC] disabled:cursor-wait disabled:opacity-70">
              {user?.photoUrl ? (
                <img src={user.photoUrl} alt="avatar" referrerPolicy="no-referrer" className="h-full w-full object-cover" />
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
            <button type="button" onClick={() => photoInputRef.current?.click()} disabled={photoUploading}
              className="mt-1.5 text-[12px] font-medium text-[#1377EC] hover:underline disabled:opacity-60">
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
                <input type="password" value={form.password} onChange={set("password")} placeholder="8 caractères minimum"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
              </div>
              <div>
                <label className="mb-1.5 block text-[#64748B] text-[13px]">Confirmer</label>
                <input type="password" value={form.confirm} onChange={set("confirm")} placeholder="••••••••"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]" />
              </div>
            </div>
          </div>
          <button type="submit" disabled={saving}
            className="h-11 w-full rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60 transition">
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
          <button onClick={() => setShowExportModal(true)} disabled={exporting}
            className="h-10 flex-1 rounded-[10px] border border-[#1377EC] text-[13px] font-semibold text-[#1377EC] hover:bg-[#F5F9FF] disabled:opacity-60 transition">
            {exporting ? "Export en cours…" : "Exporter mes données"}
          </button>
          <button onClick={() => setShowDeleteModal(true)} disabled={deleting}
            className="h-10 flex-1 rounded-[10px] border border-[#EF4444] text-[13px] font-semibold text-[#EF4444] hover:bg-[#FEF2F2] disabled:opacity-60 transition">
            {deleting ? "Suppression…" : "Supprimer mon compte"}
          </button>
        </div>
      </div>

      <div className="mt-5 rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <div className="mb-4 flex items-center gap-2">
          <Users size={16} className="text-[#1377EC]" />
          <h2 className="text-[16px] font-semibold text-[#0F172A]">Animaux partagés avec moi</h2>
        </div>
        {sharedLoading ? (
          <p className="text-[13px] text-[#94A3B8]">Chargement…</p>
        ) : sharedAnimals.length === 0 ? (
          <p className="text-[13px] text-[#94A3B8]">Aucun animal partagé avec vous pour l'instant.</p>
        ) : (
          <div className="divide-y divide-[#EEF2F7]">
            {sharedAnimals.map((p) => (
              <div key={p.id} className="flex items-center justify-between py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#EAF3FF] text-[18px]">🐾</div>
                  <div>
                    <Link to={`/animals/${p.animal.id}`} className="text-[14px] font-semibold text-[#0F172A] hover:text-[#1377EC]">
                      {p.animal.nom}
                    </Link>
                    <p className="text-[12px] text-[#64748B]">{p.animal.espece}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                    p.rolePartage === "ecriture"
                      ? "bg-[#EAF8EF] text-[#22C55E]"
                      : "bg-[#F1F5F9] text-[#64748B]"
                  }`}>
                    {p.rolePartage === "ecriture" ? "Écriture" : "Lecture"}
                  </span>
                  <button
                    onClick={() => handleLeave(p.id)}
                    disabled={leavingId === p.id}
                    className="h-8 rounded-[8px] border border-[#EF4444] px-3 text-[12px] font-semibold text-[#EF4444] hover:bg-[#FEF2F2] disabled:opacity-60 transition"
                  >
                    {leavingId === p.id ? "…" : "Quitter"}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="mt-5 rounded-[18px] border border-[#E5EAF3] bg-white p-6 shadow-sm">
        <h2 className="mb-2 text-[16px] font-semibold text-[#0F172A]">Session</h2>
        <button onClick={logout}
          className="h-10 rounded-[10px] border border-[#E5EAF3] px-5 text-[13px] font-semibold text-[#EF4444] hover:bg-[#FEF2F2] transition">
          Se déconnecter
        </button>
      </div>
    </div>
  );
}

export default Settings;
