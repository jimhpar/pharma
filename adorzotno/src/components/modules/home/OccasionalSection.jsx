"use client";
import React from "react";
import products from "../../../../public/data/data";
import ProductCard from "../../cards/ProductCard";

export default function OccasionalSection() {
  const occasions = [
    {
      id: 201,
      title: "Winter Care Essentials",
      icon: "❄️",
      products: products.slice(0, 6).map((p) => ({ ...p, occasion: "Winter" })),
    },
    {
      id: 202,
      title: "New Year Health Goals",
      icon: "🎉",
      products: products.slice(4, 10).map((p) => ({ ...p, occasion: "New Year" })),
    },
  ];

  return (
    <div className="mb-8">
      {occasions.map((occasion) => (
        <div key={occasion.id} className="mb-8">
          <div className="bg-gradient-to-r from-primary/20 to-blue-50 rounded-t-lg px-4 py-2">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-1">
                <div>
                  <h2 className="text-lg sm:text-xl md:text-2xl font-bold">{occasion.title}</h2>
                </div>
                <div className="text-xl">{occasion.icon}</div>
              </div>
              <button className="bg-white text-primary px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                Shop Collection
              </button>
            </div>
          </div>

          <div className="bg-white rounded-b-lg shadow-md p-6">
            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
              {occasion.products.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
