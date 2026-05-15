import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

function AuthCallback() {
  const navigate = useNavigate();
  const { refreshUser } = useAuth();

  useEffect(() => {
    const hash = window.location.hash;
    const params = new URLSearchParams(hash.replace("#", ""));
    const token = params.get("token");

    if (token) {
      localStorage.setItem("token", token);
      refreshUser().then(() => navigate("/dashboard", { replace: true }));
    } else {
      navigate("/login?error=oauth_failed", { replace: true });
    }
  }, []);

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#F6F8FC]">
      <p className="text-[14px] text-[#64748B]">Connexion en cours…</p>
    </div>
  );
}

export default AuthCallback;
