"use client";

import {
    Home,
    LayoutGrid,
    ShoppingCart,
    Package,
    CircleUser,
} from "lucide-react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState, useSyncExternalStore } from "react";
import { useSelector } from "react-redux";
import { useCart } from "../../lib/useCart";
import SignInModal from "../modules/auth/SignInModal";

export default function MobileNav() {
    const hasMounted = useSyncExternalStore(
        () => () => { },
        () => true,
        () => false,
    );
    const router = useRouter();
    const pathname = usePathname();
    const { cartItems, isHydrated: isCartHydrated, setIsCartOpen } = useCart();
    const { isAuthenticated, isHydrated: isAuthHydrated } = useSelector(
        (state) => state.auth,
    );
    const [isSignInOpen, setSignInOpen] = useState(false);
    const [postLoginRedirect, setPostLoginRedirect] = useState("/");
    const cartCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const showCartCount = hasMounted && isCartHydrated && cartCount > 0;

    const homeActive = pathname === "/";
    const categoryActive = pathname === "/category" || pathname.startsWith("/category/");
    const profileActive = pathname === "/profile" || pathname.startsWith("/profile/");

    const openSignInFor = (redirectPath) => {
        setPostLoginRedirect(redirectPath);
        setSignInOpen(true);
    };

    const handleAccountClick = () => {
        if (hasMounted && isAuthHydrated && isAuthenticated) {
            router.push("/profile");
            return;
        }

        openSignInFor("/profile");
    };

    const handleOrdersClick = () => {
        if (hasMounted && isAuthHydrated && isAuthenticated) {
            router.push("/profile?tab=orders");
            return;
        }

        openSignInFor("/profile?tab=orders");
    };

    const handleLoginSuccess = () => {
        setSignInOpen(false);
        router.push(postLoginRedirect || "/profile");
    };

    return (
        <>
            <nav className="fixed bottom-0 left-0 right-0 z-40 border-t bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85 md:hidden">
                <div className="grid h-16 grid-cols-5 items-center px-2">
                    {/* Home */}
                    <Link
                        href="/"
                        className={`flex flex-col items-center justify-center gap-1 transition ${homeActive ? "text-primary" : "text-gray-500 hover:text-primary"
                            }`}
                    >
                        <Home size={20} strokeWidth={2.2} />
                        <span className="text-[11px] font-medium">Home</span>
                    </Link>

                    {/* Categories */}
                    <Link
                        href="/category"
                        className={`flex flex-col items-center justify-center gap-1 transition ${categoryActive ? "text-primary" : "text-gray-500 hover:text-primary"
                            }`}
                    >
                        <LayoutGrid size={20} />
                        <span className="text-[11px] font-medium">
                            Categories
                        </span>
                    </Link>

                    {/* Cart */}
                    <button
                        onClick={() => setIsCartOpen(true)}
                        className="relative flex flex-col items-center justify-center gap-1 text-gray-500 transition hover:text-primary"
                    >
                        <div className="relative">
                            <ShoppingCart size={20} />

                            {showCartCount && (
                                <span className="absolute -right-2 -top-2 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] text-white">
                                    {cartCount}
                                </span>
                            )}
                        </div>

                        <span className="text-[11px] font-medium">Cart</span>
                    </button>

                    {/* Orders */}
                    <button
                        onClick={handleOrdersClick}
                        className={`flex flex-col items-center justify-center gap-1 transition ${profileActive ? "text-primary" : "text-gray-500 hover:text-primary"
                            }`}
                    >
                        <Package size={20} />
                        <span className="text-[11px] font-medium">Orders</span>
                    </button>

                    {/* Account */}
                    <button
                        onClick={handleAccountClick}
                        className={`flex flex-col items-center justify-center gap-1 transition ${profileActive ? "text-primary" : "text-gray-500 hover:text-primary"
                            }`}
                    >
                        <CircleUser size={20} />
                        <span className="text-[11px] font-medium">Account</span>
                    </button>
                </div>
            </nav>

            <SignInModal
                isSignInOpen={isSignInOpen}
                setSignInOpen={setSignInOpen}
                onLoginSuccess={handleLoginSuccess}
            />
        </>
    );
}
