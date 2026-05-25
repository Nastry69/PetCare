import { useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const oauthError = searchParams.get("error");
  const oauthReason = searchParams.get("reason");
  const initialError =
    oauthError === "oauth_failed"
      ? `Connexion Google impossible${oauthReason ? ` : ${oauthReason}` : "."}`
      : "";
  const [error, setError] = useState(initialError);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await login(email, password);
      navigate("/dashboard");
    } catch (err) {
      setError(err.response?.data?.message || "Email ou mot de passe incorrect.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#F6F8FC] px-4">
      <div className="w-full max-w-[420px]">
        <div className="mb-8 flex flex-col items-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[#1377EC] text-[24px] shadow-md">🐾</div>
          <h1 className="mt-3 text-[22px] font-bold text-[#0F172A]">PetCare</h1>
          <p className="mt-1 text-[14px] text-[#64748B]">Connectez-vous à votre compte</p>
        </div>

        <div className="rounded-[18px] border border-[#E5EAF3] bg-white p-8 shadow-sm">
          {error && (
            <div className="mb-4 rounded-[10px] bg-[#FEECEC] px-4 py-3 text-[13px] text-[#EF4444]">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
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

            <div>
              <label className="mb-1.5 block text-[13px] font-medium text-[#334155]">Mot de passe</label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="h-11 w-full rounded-[10px] border border-[#E5EAF3] bg-[#F8FAFC] px-4 text-[14px] text-[#0F172A] outline-none focus:border-[#1377EC] focus:ring-2 focus:ring-[#EAF3FF]"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="mt-2 h-11 w-full rounded-[10px] bg-[#1377EC] text-[14px] font-semibold text-white hover:bg-[#0E68D0] disabled:opacity-60"
            >
              {loading ? "Connexion…" : "Se connecter"}
            </button>

            <Link to="/forgot-password" className="block text-center text-[12px] text-[#1377EC] hover:underline">
              Mot de passe oublié ?
            </Link>
          </form>
        </div>

        <div className="mt-4">
          <div className="relative mb-4 flex items-center">
            <div className="flex-1 border-t border-[#E5EAF3]" />
            <span className="mx-3 text-[12px] text-[#94A3B8]">ou</span>
            <div className="flex-1 border-t border-[#E5EAF3]" />
          </div>

          <a
            href="http://localhost:8000/api/auth/google"
            className="flex h-11 w-full items-center justify-center gap-3 rounded-[10px] border border-[#E5EAF3] bg-white text-[14px] font-medium text-[#334155] hover:bg-[#F8FAFC]"
          >
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
              <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
              <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
              <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
            </svg>
            Continuer avec Google
          </a>
        </div>

        <p className="mt-6 text-center text-[13px] text-[#64748B]">
          Pas encore de compte ?{" "}
          <Link to="/register" className="font-semibold text-[#1377EC] hover:underline">
            S'inscrire
          </Link>
        </p>
      </div>
    </div>
  );
}

export default Login;
