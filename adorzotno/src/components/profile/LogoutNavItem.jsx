"use client";

import { LogOut } from "lucide-react";

export default function LogoutNavItem({
  isLoggingOut,
  onClick,
  isMobile = false,
}) {
  if (isMobile) {
    return (
      <button
        type="button"
        onClick={onClick}
        className="flex w-full items-center gap-3 rounded-2xl bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-600 transition-all duration-200"
      >
        <LogOut size={18} />
        <span>Logout</span>
        {isLoggingOut && <span className="text-xs font-medium">...</span>}
      </button>
    );
  }

  return (
    <button
      type="button"
      onClick={onClick}
      className="flex w-full items-center gap-3 rounded-lg px-4 text-left text-sm font-semibold text-red-600 transition-all duration-200 hover:bg-red-50"
    >
      <span className="flex h-10 w-10 items-center justify-center rounded-xl text-red-500">
        <LogOut size={18} />
      </span>
      <span className="flex-1">Logout</span>
      {isLoggingOut && <span className="text-xs font-medium">...</span>}
    </button>
  );
}
