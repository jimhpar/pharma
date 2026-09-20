"use client";

import { Heart, Package, Settings, User } from "lucide-react";
import { useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import { useLogoutMutation } from "@/redux/features/auth/authApi";
import LogoutNavItem from "./LogoutNavItem";
import OrdersSection from "./OrdersSection";
import ProfileSection from "./ProfileSection";
import SettingsSection from "./SettingsSection";
import WishlistSection from "./WishlistSection";

const navItems = [
  { id: "profile", label: "My Profile", icon: User },
  { id: "orders", label: "My Orders", icon: Package },
  { id: "wishlist", label: "Wishlist", icon: Heart },
  { id: "settings", label: "Settings", icon: Settings },
];
const validSectionIds = new Set(navItems.map((item) => item.id));

export default function ProfilePageClient({ user: initialUser }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const authUser = useSelector((state) => state.auth.user);
  const [logoutUser, { isLoading: isLoggingOut }] = useLogoutMutation();
  const [manualActiveSection, setManualActiveSection] = useState("profile");

  const requestedSection = searchParams.get("tab");
  const activeSection = validSectionIds.has(requestedSection)
    ? requestedSection
    : manualActiveSection;

  const user = authUser || initialUser;
  const customer = user?.customer || {};
  const initials = useMemo(() => {
    return (
      user?.name
        ?.split(" ")
        ?.filter(Boolean)
        ?.slice(0, 2)
        ?.map((part) => part[0]?.toUpperCase())
        ?.join("") || "AZ"
    );
  }, [user?.name]);

  const handleLogout = async () => {
    try {
      const response = await logoutUser().unwrap();
      toast.success(response?.message || "Logout successful");
      router.push("/");
    } catch (error) {
      toast.error(error?.data?.message || "Logout failed. Please try again.");
    }
  };

  const handleSectionChange = (sectionId) => {
    setManualActiveSection(sectionId);

    const nextParams = new URLSearchParams(searchParams.toString());
    nextParams.set("tab", sectionId);
    router.replace(`${pathname}?${nextParams.toString()}`, { scroll: false });
  };

  const renderActiveSection = () => {
    switch (activeSection) {
      case "orders":
        return <OrdersSection />;
      case "wishlist":
        return <WishlistSection />;
      case "settings":
        return <SettingsSection customer={customer} />;
      case "profile":
      default:
        return <ProfileSection user={user} customer={customer} />;
    }
  };

  return (
    <div className="bg-white">
      <div className="container mx-auto py-4">
        <div className="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-8">
          <aside className="min-w-0 space-y-4">
            <div className="overflow-hidden rounded-[28px] border border-gray-200 bg-white">
              <div className="bg-gradient-to-r from-primary to-secondary p-4 text-white sm:p-6">
                <div className="flex items-center gap-3 sm:gap-4">
                  <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold ring-1 ring-white/20 backdrop-blur-sm sm:h-16 sm:w-16 sm:text-xl">
                    {initials}
                  </div>
                  <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">
                      Account
                    </p>
                    <h1 className="truncate text-lg font-bold sm:text-xl capitalize">{user?.name}</h1>
                    <p className="truncate text-xs text-white/80 sm:text-sm">{user?.email}</p>
                  </div>
                </div>
              </div>

              <div className="border-b border-gray-100 px-4 py-4 sm:px-6">
                <div className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                  <div className="rounded-2xl bg-slate-50 px-4 py-3">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                      Customer ID
                    </p>
                    <p className="mt-1 font-semibold text-slate-700">
                      {customer?.customer_code || "N/A"}
                    </p>
                  </div>
                  <div className="rounded-2xl bg-slate-50 px-4 py-3">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                      Status
                    </p>
                    <p className="mt-1 font-semibold capitalize text-slate-700">
                      {user?.status || "active"}
                    </p>
                  </div>
                </div>
              </div>

              <div className="p-3 sm:p-4">
                <div className="hidden space-y-2 lg:block">
                  {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = activeSection === item.id;

                    return (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => handleSectionChange(item.id)}
                        className={`flex w-full items-center gap-3 rounded-lg px-4 text-left text-sm font-semibold transition-all duration-200 ${isActive
                          ? "bg-primary text-white"
                          : "text-slate-700 hover:bg-slate-50 hover:text-primary"
                          }`}
                      >
                        <span
                          className={`flex h-10 w-10 items-center justify-center rounded-xl ${isActive ? "text-white" : "text-primary"
                            }`}
                        >
                          <Icon size={18} />
                        </span>
                        <span className="flex-1">{item.label}</span>
                      </button>
                    );
                  })}

                  <LogoutNavItem
                    isLoggingOut={isLoggingOut}
                    onClick={handleLogout}
                  />
                </div>

                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:hidden">
                  {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = activeSection === item.id;

                    return (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => handleSectionChange(item.id)}
                        className={`flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold transition-all duration-200 ${isActive
                          ? "bg-primary text-white"
                          : "bg-slate-50 text-slate-700"
                          }`}
                      >
                        <Icon size={18} />
                        <span>{item.label}</span>
                      </button>
                    );
                  })}

                  <LogoutNavItem
                    isLoggingOut={isLoggingOut}
                    onClick={handleLogout}
                    isMobile
                  />
                </div>
              </div>
            </div>
          </aside>

          <section className="min-w-0 w-full">{renderActiveSection()}</section>
        </div>
      </div>
    </div>
  );
}
