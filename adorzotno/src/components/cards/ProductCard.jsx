"use client";

import { ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import React from "react";
import InteractiveRatingStars from "../shared/InteractiveRatingStars";
import { useCart } from "../../lib/useCart";

const formatPrice = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : "0.00";
};

export default function ProductCard({ product }) {
  const { addToCart, getItemQuantity, updateQuantity, removeItem } = useCart();

  const quantity = getItemQuantity(product.id);
  const inCart = quantity > 0;
  const productHref = `/product/${product.slug}`;
  const imageSrc = product.images?.[0] || "/images/no-image-available.png";
  const discountTag = product.discountLabel || product.discount;

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
    <div className="relative h-full flex-shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white transition-all hover:border-primary/20 hover:shadow-md">
      <Link href={productHref}>
        {discountTag ? (
          <span className="absolute top-0 left-2 z-10 bg-red-600 p-1.5 text-xs font-bold leading-tight text-white [clip-path:polygon(0%_0%,100%_0%,100%_100%,87.5%_90%,75%_100%,62.5%_90%,50%_100%,37.5%_90%,25%_100%,12.5%_90%,0%_100%)]">
            {discountTag.split(" ")[0]} <br />
            {discountTag.split(" ")[1]}
          </span>
        ) : null}

        <div className="flex h-full flex-col overflow-hidden bg-white">
          <div className="relative h-40 overflow-hidden bg-gray-50 lg:h-44">
            <Image
              src={imageSrc}
              alt={product.name}
              fill
              sizes="(max-width: 640px) 50vw, 25vw"
              className="object-cover transform transition-transform duration-300 group-hover:scale-105"
              unoptimized
            />
          </div>

          <div className="flex flex-1 flex-col p-3">
            <h3 className="mb-2 line-clamp-2 h-10 text-sm font-semibold text-gray-800">
              {product.name}
            </h3>

            <div className="mt-auto flex items-end justify-between">
              <div className="flex flex-col">
                <span className="text-lg font-bold text-red-600 lg:text-xl">
                  {"\u09F3"}{formatPrice(product.price)}
                </span>
                {product.originalPrice ? (
                  <span className="text-sm text-gray-400 line-through">
                    {"\u09F3"}{formatPrice(product.originalPrice)}
                  </span>
                ) : null}
                <InteractiveRatingStars
                  value={product.rating}
                  readonly
                  size={16}
                />
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
          <button
            onClick={handleAdd}
            className="flex h-10 w-12 items-center justify-center rounded-lg border border-primary/20 bg-white text-primary transition-all hover:bg-primary hover:text-white"
          >
            <ShoppingCart size={18} />
          </button>
        ) : (
          <div className="group relative">
            <div className="relative h-10 w-12 overflow-hidden rounded-xl border border-primary/15 bg-primary text-white shadow-sm transition-all duration-300 ease-out group-hover:w-[128px] group-hover:bg-primary/8">
              <div className="absolute inset-0 flex items-center justify-center transition-all duration-200 group-hover:scale-90 group-hover:opacity-0">
                <span className="font-bold">{quantity}</span>
              </div>
              <div className="absolute inset-0 flex items-center justify-between gap-1 p-1 opacity-0 transition-all duration-300 ease-out group-hover:opacity-100">
                <button
                  onClick={handleDecrease}
                  className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-lg leading-none text-primary shadow-sm transition-all duration-200 hover:bg-secondary hover:text-white sm:text-xl"
                >
                  -
                </button>

                <span className="min-w-0 flex-1 text-center font-bold text-white">
                  {quantity}
                </span>

                <button
                  onClick={handleIncrease}
                  className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-lg leading-none text-primary shadow-sm transition-all duration-200 hover:bg-secondary hover:text-white sm:text-xl"
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
