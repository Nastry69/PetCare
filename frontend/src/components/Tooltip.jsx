export default function Tooltip({ text, children }) {
  return (
    <div className="group relative inline-flex">
      {children}
      <span className="pointer-events-none absolute bottom-[calc(100%+7px)] left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-[6px] bg-[#1E293B] px-2.5 py-[5px] text-[11px] font-semibold text-white opacity-0 shadow-md transition-opacity duration-150 group-hover:opacity-100">
        {text}
        <span className="absolute left-1/2 top-full -translate-x-1/2 border-[5px] border-x-transparent border-b-transparent border-t-[#1E293B]" />
      </span>
    </div>
  );
}
