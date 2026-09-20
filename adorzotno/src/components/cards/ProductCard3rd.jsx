"use client";
import { ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import React from "react";
import { useCart } from "../../lib/useCart";

export default function ProductCard({ product }) {
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
        <div className="relative h-full flex-shrink-0 overflow-hidden border border-slate-200 rounded-lg bg-white transition-all hover:border-primary/20 hover:shadow-md">
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
                            className="object-cover transform transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>

                    <div className="p-3">
                        <h3 className="text-sm font-semibold text-gray-800 mb-2 line-clamp-2 h-10">
                            {product.name}
                        </h3>

                        <div className="flex items-end justify-between">


                            <div className="flex flex-col">
                                <span className="font-bold text-red-600 text-lg lg:text-xl">৳{product.price}</span>
                                <span className="line-through text-gray-400 text-sm">
                                    ৳{product.originalPrice}
                                </span>
                                <span>
                                    <span className="text-xs text-gray-500">
                                        <span className="text-sm md:text-base 2xl:text-lg text-orange-400">★★★★★</span> {product.rating} <span className="max-sm:hidden">(5)</span>
                                    </span>
                                </span>
                            </div>


                        </div>
                    </div>
                </div>
            </Link>

            <div
                className="absolute bottom-3 right-3"
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }}
            >
                {!inCart ? (
                    // Normal Add button
                    <button
                        onClick={handleAdd}
                        className="flex h-10 w-12 items-center justify-center rounded-lg border border-primary/20 bg-white text-primary transition-all hover:bg-primary hover:text-white"
                    >
                        <ShoppingCart size={18} />
                    </button>
                ) : (
                    // Cart state
                    <div className="group relative">

                        {/* Default */}
                        <div className="relative h-10 w-12 overflow-hidden rounded-xl border border-primary/15 bg-primary text-white shadow-sm transition-all duration-300 ease-out group-hover:w-[128px] group-hover:bg-primary/8">
                            <div className="absolute inset-0 flex items-center justify-center transition-all duration-200 group-hover:scale-90 group-hover:opacity-0">
                                <span className="font-bold">{quantity}</span>
                            </div>
                            <div className="absolute inset-0 flex items-center justify-between gap-1 p-1 opacity-0 transition-all duration-300 ease-out group-hover:opacity-100">
                                <button
                                    onClick={handleDecrease}
                                    className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-lg sm:text-xl leading-none text-primary shadow-sm transition-all duration-200 hover:bg-secondary hover:text-white"
                                >
                                    -
                                </button>

                                <span className="min-w-0 flex-1 text-center font-bold text-white">
                                    {quantity}
                                </span>

                                <button
                                    onClick={handleIncrease}
                                    className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-lg sm:text-xl leading-none text-primary shadow-sm transition-all duration-200 hover:bg-secondary hover:text-white"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>


        </div>
    );
}
