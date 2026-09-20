"use client";

import { ChevronRight, Zap } from "lucide-react";
import Link from "next/link";
import ProductCard from "../../cards/ProductCard";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetFlashDealsQuery } from "@/redux/features/product/productApi";

export default function FlashDealSection() {
  const { data: flashDealsResponse = {}, isLoading, isFetching } =
    useGetFlashDealsQuery({
      perPage: 20,
      sortBy: "created_at",
      sortOrder: "desc",
    });

  const flashDeals = (flashDealsResponse?.data || [])
    .map(mapApiProductToCard)
    .slice(0, 12);

  return (
    <div className="mb-8">
      <div className="mb-4 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <h2 className="text-lg font-semibold sm:text-xl md:text-2xl">
            Flash Deals
          </h2>
          <Zap className="text-red-500" size={24} />
        </div>

        <Link
          href="/flash-deals"
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
      ) : flashDeals.length === 0 ? (
        <div className="rounded-lg border border-dashed border-slate-200 bg-white py-10 text-center text-gray-500">
          No flash deals available right now.
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
          {flashDeals.map((deal) => (
            <div key={deal.id}>
              <ProductCard product={deal} />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
