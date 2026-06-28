import { useState } from "react";
import { Link } from "react-router-dom";
import api from "../api/axios";

/**
 * ForgotPassword — Étape 1 du reset.
 */
function ForgotPassword() {
  const [email,   setEmail]   = useState("");
  const [error,   setError]   = useState("");
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await api.post("/auth/forgot-password", { email });
      setSuccess(true);
    } catch {
      setSuccess(true);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#F6F8FC] px-4">
      <div className="w-full max-w-[420px]">

        {/* Logo + titre */}
        <div className="mb-8 flex flex-col items-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[#1377EC] text-[24px] shadow-md">
            🐾
          </div>
          <h1 className="mt-3 text-[22px] font-bold text-[#0F172A]">Mot de passe oublié</h1>
          <p className="mt-1 text-center text-[14px] text-[#64748B]">
            Saisissez votre adresse email pour recevoir un lien de réinitialisation.
          </p>
        </div>

        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-8 shadow-sm">

          {success ? (
            /* ── Confirmation envoyée ── */
            <div className="flex flex-col items-center gap-4 py-4 text-center">
              <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#EAF8EF] text-[#22C55E] text-2xl">
                ✓
              </div>
              <div>
                <p className="text-[15px] font-semibold text-[#0F172A]">Email envoyé !</p>
                <p className="mt-1 text-[13px] text-[#64748B]">
                  Si un compte existe avec cette adresse, vous recevrez un lien de réinitialisation
                  valable <strong>1 heure</strong>.
                </p>
                <p className="mt-3 text-[12px] text-[#94A3B8]">
                  Pensez à vérifier vos spams.
                </p>
              </div>
              <Link
                to="/login"
                className="mt-2 text-[13px] font-semibold text-[#1377EC] hover:underline"
              >
                ← Retour à la connexion
              </Link>
            </div>
          ) : (
            /* ── Formulaire email ── */
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">
                  {error}
                </div>
              )}

              <div>
                <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">
                  Adresse email
                </label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="vous@exemple.fr"
                  className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] text-[#0F172A] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]"
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="mt-2 h-11 w-full rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60"
              >
                {loading ? "Envoi en cours…" : "Envoyer le lien de réinitialisation"}
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

export default ForgotPassword;
