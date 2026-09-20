"use client";

import { Home, Sparkles } from "lucide-react";
import Link from "next/link";
import React, { useMemo } from "react";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetTrendingProductsQuery } from "@/redux/features/product/productApi";
import FilteredProductCard from "../category/FilteredProductCard";

export default function RecommendedProductsPage() {
  const {
    data: recommendedProductsResponse = [],
    isLoading,
    isFetching,
  } = useGetTrendingProductsQuery({
    limit: 50,
  });

  const recommendedProducts = useMemo(
    () => recommendedProductsResponse.map(mapApiProductToCard),
    [recommendedProductsResponse],
  );

  return (
    <div>
      <div className="mb-6 rounded-xl border border-teal-100 bg-white p-5 shadow-sm">
        <div className="mb-2 flex items-center gap-2 text-sm text-gray-500">
          <Link href="/" className="inline-flex items-center gap-1 hover:text-primary">
            <Home size={14} />
            Home
          </Link>
          <span>/</span>
          <span className="text-gray-700">Recommended Products</span>
        </div>

        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-teal-100 text-teal-500">
            <Sparkles size={20} />
          </div>
          <div>
            <h1 className="text-2xl font-semibold text-gray-900">
              Recommended Products
            </h1>
            <p className="text-sm text-gray-600">
              Showing {recommendedProducts.length} recommended items
            </p>
          </div>
        </div>
      </div>

      <FilteredProductCard
        filteredProducts={recommendedProducts}
        isLoading={isLoading || isFetching}
      />
    </div>
  );
}
