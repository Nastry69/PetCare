import { useState, useEffect } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import api from "../api/axios";

/**
 * ResetPassword — Étape 2 du reset.
 * L'utilisateur arrive depuis l'email avec ?token=xxx dans l'URL.
 * Il saisit son nouveau mot de passe.
 */
function ResetPassword() {
  const navigate        = useNavigate();
  const [params]        = useSearchParams();
  const token           = params.get("token") ?? "";

  const [newPassword, setNewPassword] = useState("");
  const [confirm,     setConfirm]     = useState("");
  const [error,       setError]       = useState("");
  const [success,     setSuccess]     = useState(false);
  const [loading,     setLoading]     = useState(false);

  // Si aucun token → redirection immédiate vers forgot-password
  useEffect(() => {
    if (!token) {
      navigate("/forgot-password", { replace: true });
    }
  }, [token, navigate]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    if (newPassword !== confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    if (newPassword.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères.");
      return;
    }

    setLoading(true);
    try {
      await api.post("/auth/reset-password", { token, newPassword });
      setSuccess(true);
      setTimeout(() => navigate("/login"), 3000);
    } catch (err) {
      setError(
        err.response?.data?.message ||
        "Lien invalide ou expiré. Veuillez faire une nouvelle demande."
      );
    } finally {
      setLoading(false);
    }
  };

  if (!token) return null;

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#F6F8FC] px-4">
      <div className="w-full max-w-[420px]">

        {/* Logo + titre */}
        <div className="mb-8 flex flex-col items-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[#1377EC] text-[24px] shadow-md">
            🔐
          </div>
          <h1 className="mt-3 text-[22px] font-bold text-[#0F172A]">Nouveau mot de passe</h1>
          <p className="mt-1 text-center text-[14px] text-[#64748B]">
            Choisissez un mot de passe sécurisé pour votre compte PetCare.
          </p>
        </div>

        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-8 shadow-sm">

          {success ? (
            /* ── Succès ── */
            <div className="flex flex-col items-center gap-4 py-4 text-center">
              <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#EAF8EF] text-[#22C55E] text-2xl">
                ✓
              </div>
              <div>
                <p className="text-[15px] font-semibold text-[#0F172A]">
                  Mot de passe mis à jour !
                </p>
                <p className="mt-1 text-[13px] text-[#64748B]">
                  Redirection vers la connexion…
                </p>
              </div>
            </div>
          ) : (
            /* ── Formulaire ── */
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">
                  {error}
                  {error.toLowerCase().includes("expiré") && (
                    <span>
                      {" "}
                      <Link to="/forgot-password" className="font-semibold underline">
                        Faire une nouvelle demande
                      </Link>
                    </span>
                  )}
                </div>
              )}

              <div>
                <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">
                  Nouveau mot de passe
                </label>
                <input
                  type="password"
                  required
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="8 caractères minimum"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] text-[#0F172A] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]"
                />
              </div>

              <div>
                <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">
                  Confirmer le mot de passe
                </label>
                <input
                  type="password"
                  required
                  value={confirm}
                  onChange={(e) => setConfirm(e.target.value)}
                  placeholder="••••••••"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] text-[#0F172A] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]"
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="mt-2 h-11 w-full rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60"
              >
                {loading ? "Mise à jour…" : "Confirmer le nouveau mot de passe"}
              </button>
            </form>
          )}
        </div>

        {!success && (
          <p className="mt-6 text-center text-[13px] text-[#64748B]">
            <Link to="/login" className="font-semibold text-[#1377EC] hover:underline">
              ← Retour à la connexion
            </Link>
          </p>
        )}
      </div>
    </div>
  );
}

export default ResetPassword;
