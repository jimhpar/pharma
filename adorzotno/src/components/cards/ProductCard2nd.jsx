"use client";
import { ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import React from "react";
import { useCart } from "../../lib/useCart";

export default function ProductCard2nd({ product }) {
    const { addToCart, getItemQuantity, updateQuantity, removeItem } = useCart();

    const quantity = getItemQuantity(product.id);
    const inCart = quantity > 0;

    const handleAdd = (e) => {
        e.preventDefault();
        e.stopPropagation();
        addToCart(product);
    };

    const handleIncrease = (e) => {
        e.preventDefault();
        e.stopPropagation();
        updateQuantity(product.id, quantity + 1);
    };

    const handleDecrease = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (quantity === 1) {
            removeItem(product.id);
        } else {
            updateQuantity(product.id, quantity - 1);
        }
    };

    return (
        <div className="group relative h-full flex-shrink-0 overflow-hidden border border-slate-200 rounded-lg bg-white transition-all hover:border-primary/20 hover:shadow-md">
            <Link
                href={`/product/${product.name
                    .toLowerCase()
                    .replace(/&/g, "and")
                    .replace(/[^a-z0-9]+/g, "-")
                    .replace(/(^-|-$)/g, "")}`}
            >
                {product.discount && (
                    <span className="absolute top-0 left-2 bg-red-600 text-white text-xs font-bold p-1.5 leading-tight [clip-path:polygon(0%_0%,100%_0%,100%_100%,87.5%_90%,75%_100%,62.5%_90%,50%_100%,37.5%_90%,25%_100%,12.5%_90%,0%_100%)] z-10">
                        {product.discount.split(" ")[0]} <br />
                        {product.discount.split(" ")[1]}
                    </span>
                )}

                <div className="bg-white h-full overflow-hidden">
                    <div className="relative h-40 lg:h-44 bg-gray-50 overflow-hidden">
                        <Image
                            src={product.images[0]}
                            alt={product.name}
                            fill
                            sizes="(max-width: 640px) 50vw, 25vw"
                            className="object-cover"
                        />
                    </div>

                    <div className="p-3 text-center">
                        <h3 className="text-sm font-semibold text-gray-800 mb-2 line-clamp-2 h-10">
                            {product.name}
                        </h3>
                        <div className="flex items-center gap-1 justify-center">
                            <span className="line-through text-gray-400">
                                ৳{product.originalPrice}
                            </span>
                            <span className="font-bold">৳{product.price}</span>
                        </div>

                        <div className="mt-3">

                            {/* Add to Cart Button / Quantity Controls */}
                            {inCart ? (
                                <div
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                    }}
                                    className="flex w-full items-center overflow-hidden rounded-xl border border-primary/15 bg-primary/5 p-1"
                                >
                                    <button
                                        onClick={handleDecrease}
                                        className="flex h-8 w-8  items-center justify-center rounded-lg bg-white text-xl font-bold leading-none text-primary shadow-sm transition-all duration-300 hover:bg-primary hover:text-white"
                                    >
                                        -
                                    </button>
                                    <div className="flex min-w-0 flex-1 items-center justify-center px-3 text-primary">
                                        <span className="text-base font-bold">
                                            {quantity}
                                        </span>
                                    </div>
                                    <button
                                        onClick={handleIncrease}
                                        className="flex h-8 w-8  items-center justify-center rounded-lg bg-primary text-xl font-bold leading-none text-white shadow-sm transition-all duration-300 hover:bg-secondary"
                                    >
                                        +
                                    </button>
                                </div>
                            ) : (
                                <button
                                    onClick={handleAdd}
                                    className="flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-primary/20 bg-white px-4 font-semibold text-primary transition-all duration-100 group-hover:bg-primary group-hover:text-white text-sm sm:text-base whitespace-nowrap"
                                >
                                    <ShoppingCart size={18} />
                                    <span>Add to Cart</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    );
}
