"use client";
import { Timer, Zap } from "lucide-react";
import Image from "next/image";
import React, { useEffect, useState } from "react";
import products from "../../../../public/data/data";
import Link from "next/link";
import { useCart } from "../../../lib/useCart";

export default function FlashSaleSection() {
  const { addToCart, getItemQuantity, updateQuantity, removeItem } = useCart();
  const [hoveredId, setHoveredId] = useState(null);
  const [timeLeft, setTimeLeft] = useState({ hours: 2, minutes: 45, seconds: 30 });

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev.seconds > 0) return { ...prev, seconds: prev.seconds - 1 };
        else if (prev.minutes > 0) return { ...prev, minutes: prev.minutes - 1, seconds: 59 };
        else if (prev.hours > 0) return { hours: prev.hours - 1, minutes: 59, seconds: 59 };
        return prev;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const flashSaleProducts = products.slice(30, 44);

  return (
    <div className="mb-8">
      <div className="bg-gradient-to-r from-primary to-blue-500 rounded-t-lg p-4 text-white">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Zap className="text-yellow-300" size={32} />
            <div>
              <h2 className="text-lg sm:text-xl md:text-2xl font-bold">Flash Sale</h2>
              <p className="text-sm text-white/90">Limited time offers - Hurry up!</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Timer size={24} />
            <div className="flex gap-2">
              {[timeLeft.hours, timeLeft.minutes, timeLeft.seconds].map((val, i) => (
                <React.Fragment key={i}>
                  {i > 0 && <span className="text-2xl">:</span>}
                  <div className="bg-white text-red-600 px-3 py-2 rounded-lg font-bold text-lg">
                    {String(val).padStart(2, "0")}
                  </div>
                </React.Fragment>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-b-lg shadow-md p-6">
        <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
          {flashSaleProducts.map((product) => {
            const quantity = getItemQuantity(product.id);
            const isHovered = hoveredId === product.id;
            return (
              <div key={product.id} className="border h-full border-gray-200 rounded-lg p-3 hover:shadow-lg transition">
                <Link href={`/product/${product.name.toLowerCase().replace(/&/g, "and").replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "")}`}>
                  <div className="relative mb-3">
                    <Image height={200} width={200} src={product.images[0]} alt={product.name} className="w-full h-32 object-cover rounded" />
                    <span className="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full animate-pulse">
                      {product.discount}
                    </span>
                  </div>
                  <h3 className="text-sm font-semibold text-gray-800 mb-2 line-clamp-2">{product.name}</h3>
                  
                </Link>
                  <div className="flex justify-between flex-wrap items-center gap-2 mb-3">
                    <div className="flex items-center gap-1 ">
                    <span className="text-lg font-bold text-red-600">৳{product.price}</span>
                    <span className="text-xs text-gray-400 line-through">৳{product.originalPrice}</span>
                  </div>
                  <div className="flex justify-end ">
                  {quantity > 0 ? (
                    <div className="flex items-center gap-1 bg-primary rounded-lg overflow-hidden">
                      <button onClick={() => quantity === 1 ? removeItem(product.id) : updateQuantity(product.id, quantity - 1)}
                        className="text-white px-3 py-1.5 hover:bg-white/20 transition font-bold text-lg leading-none">−</button>
                      <span className="text-white font-semibold text-sm min-w-[20px] text-center">{quantity}</span>
                      <button onClick={() => updateQuantity(product.id, quantity + 1)}
                        className="text-white px-3 py-1.5 hover:bg-white/20 transition font-bold text-lg leading-none">+</button>
                    </div>
                  ) : (
                    <button
                      onClick={() => addToCart(product)}
                      onMouseEnter={() => setHoveredId(product.id)}
                      onMouseLeave={() => setHoveredId(null)}
                      className={`flex items-center bg-primary text-white rounded-lg font-semibold transition-all duration-300 overflow-hidden px-4 py-1.5 gap-2}`}
                    >
                      <span className="text-xl leading-none font-bold">+</span>
                    </button>
                  )}
                </div>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div className="bg-red-500 h-2 rounded-full" style={{ width: "70%" }} />
                  </div>
                  <p className="text-xs text-gray-500 mb-2">Sold: 70%</p>

                
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
