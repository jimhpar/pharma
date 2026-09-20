"use client";

import { ChevronRight, TrendingUp } from "lucide-react";
import Link from "next/link";
import ProductCard from "../../cards/ProductCard";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetTrendingProductsQuery } from "@/redux/features/product/productApi";

export default function RecommendedSection() {
  const { data: trendingProductsResponse = [], isLoading, isFetching } =
    useGetTrendingProductsQuery({
      limit: 20,
    });

  const recommendedProducts = trendingProductsResponse
    .map(mapApiProductToCard)
    .slice(0, 12);

  return (
    <div className="mb-8">
      <div className="mb-4 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <h2 className="text-lg font-semibold sm:text-xl md:text-2xl">
            Recommended For You
          </h2>
          <TrendingUp className="text-teal-500" size={24} />
        </div>

        <Link
          href="/recommended-products"
          className="flex items-center gap-1 rounded-full border border-primary/30 px-3 py-1.5 text-sm font-semibold text-primary transition-all hover:gap-2 hover:bg-primary/5"
        >
          View All <ChevronRight size={16} />
        </Link>
      </div>

      {isLoading || isFetching ? (
        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
          {Array.from({ length: 12 }).map((_, index) => (
            <div
              key={index}
              className="overflow-hidden rounded-lg border border-slate-200 bg-white"
            >
              <div className="h-40 animate-pulse bg-slate-100 lg:h-44" />
              <div className="space-y-3 p-3">
                <div className="h-4 w-3/4 animate-pulse rounded bg-slate-100" />
                <div className="h-4 w-1/2 animate-pulse rounded bg-slate-100" />
                <div className="h-5 w-1/3 animate-pulse rounded bg-slate-100" />
              </div>
            </div>
          ))}
        </div>
      ) : recommendedProducts.length === 0 ? (
        <div className="rounded-lg border border-dashed border-slate-200 bg-white py-10 text-center text-gray-500">
          No recommended products available right now.
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
          {recommendedProducts.map((product) => (
            <div key={product.id}>
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
