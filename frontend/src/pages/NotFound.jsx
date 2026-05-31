import { Link } from "react-router-dom";
import { PawPrint, ArrowLeft } from "lucide-react";

function NotFound() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-[#F6F8FC] px-4 text-center">
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-[#EAF3FF] text-[40px]">
        🐾
      </div>

      <h1 className="text-[72px] font-extrabold leading-none text-[#1377EC]">404</h1>
      <h2 className="mt-3 text-[22px] font-bold text-[#0F172A]">Page introuvable</h2>
      <p className="mt-2 max-w-sm text-[14px] text-[#64748B]">
        La page que vous cherchez n'existe pas ou a été déplacée.
      </p>

      <div className="mt-8 flex flex-col items-center gap-3 sm:flex-row">
        <Link
          to="/"
          className="inline-flex h-11 items-center gap-2 rounded-[10px] border border-[#E5EAF3] bg-white px-5 text-[14px] font-medium text-[#475569] transition hover:border-[#1377EC] hover:text-[#1377EC]"
        >
          <ArrowLeft size={16} />
          Retour à l'accueil
        </Link>
        <Link
          to="/dashboard"
          className="inline-flex h-11 items-center gap-2 rounded-[10px] bg-[#1377EC] px-5 text-[14px] font-semibold text-white transition hover:bg-[#0E68D0]"
        >
          <PawPrint size={16} />
          Mon tableau de bord
        </Link>
      </div>
    </div>
  );
}

export default NotFound;
