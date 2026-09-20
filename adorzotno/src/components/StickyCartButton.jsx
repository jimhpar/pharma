"use client"

import React from 'react'
import { useSyncExternalStore } from 'react';
import { ShoppingCart } from 'lucide-react';
import { useCart } from '@/lib/useCart';

export default function StickyCartButton() {
    const hasMounted = useSyncExternalStore(
        () => () => { },
        () => true,
        () => false,
    );
    const { cartItems, isHydrated, isCartOpen, setIsCartOpen } = useCart();

    const totalItem = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const showCartSummary = hasMounted && isHydrated;

    return (
        <>
            <div
                onClick={() => setIsCartOpen(true)}
                className={`hidden md:fixed md:flex md:flex-col md:gap-1 md:items-center md:right-0 md:top-80 md:bg-sky-600 md:pt-2 md:rounded-l md:text-sm hover:cursor-pointer ${isCartOpen ? 'z-40' : ''}`}
            >
                <ShoppingCart size={18} className="text-white" />
                <p className="px-2 text-white">{showCartSummary ? totalItem : 0} Item</p>
                <div className="bg-sky-200 w-full text-center rounded-bl border-t border-sky-300 px-2">
                    <p className="font-semibold">৳{showCartSummary ? totalPrice.toFixed(2) : "0.00"}</p>
                </div>
            </div>

            {/* Mobile Sticky Cart Button */}
            {/* {cartItems.length > 0 && (
                <div
                    className={`md:hidden fixed flex flex-row bottom-0 left-0 right-0 items-center justify-between bg-sky-600 px-5 py-3 cursor-pointer z-40 rounded-t-xl`}
                >
                    <div className="flex flex-col">
                        <p className="text-sm font-semibold text-white border-b">{totalItem} Items</p>
                        <p className="text-sm font-semibold text-white">৳{totalPrice.toFixed(2)}</p>
                    </div>
                    <div
                        onClick={() => setIsCartOpen(true)}
                        className="text-sm font-bold bg-sky-300 p-2 rounded-md flex items-center gap-2">
                        <ShoppingCart size={18} />
                        View Cart
                    </div>
                </div>
            )} */}

        </>
    )
}
