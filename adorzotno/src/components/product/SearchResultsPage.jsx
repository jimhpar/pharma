"use client";

import { Search, Sparkles, TrendingUp } from "lucide-react";
import React, { useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import {
  useGetTrendingProductsQuery,
  useSearchProductsQuery,
} from "@/redux/features/product/productApi";
import FilteredProductCard from "../category/FilteredProductCard";

const popularSearches = [
  "vitamin",
  "face wash",
  "pain relief",
  "baby care",
  "first aid",
  "supplements",
];

export default function SearchResultsPage({ initialQuery = "", initialPage = 1 }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [currentPage, setCurrentPage] = useState(initialPage);

  const trimmedQuery = initialQuery.trim();

  const {
    data: searchResponse = {},
    isLoading,
    isFetching,
  } = useSearchProductsQuery(
    {
      query: trimmedQuery,
      page: currentPage,
      perPage: 20,
    },
    {
      skip: !trimmedQuery,
    },
  );

  const pagination = searchResponse || null;

  const searchResults = useMemo(
    () => (searchResponse?.data || []).map(mapApiProductToCard),
    [searchResponse?.data],
  );

  const {
    data: trendingProductsResponse = [],
    isLoading: isTrendingLoading,
    isFetching: isTrendingFetching,
  } = useGetTrendingProductsQuery(
    {
      limit: 12,
    },
    {
      skip: Boolean(trimmedQuery),
    },
  );

  const trendingProducts = useMemo(
    () => trendingProductsResponse.map(mapApiProductToCard),
    [trendingProductsResponse],
  );

  const handlePageChange = (page) => {
    if (!pagination?.last_page) {
      return;
    }

    const nextPage = Math.min(Math.max(page, 1), pagination.last_page);

    if (nextPage === currentPage) {
      return;
    }

    setCurrentPage(nextPage);

    const nextSearchParams = new URLSearchParams(searchParams.toString());

    if (nextPage <= 1) {
      nextSearchParams.delete("page");
    } else {
      nextSearchParams.set("page", String(nextPage));
    }

    if (trimmedQuery) {
      nextSearchParams.set("q", trimmedQuery);
    }

    const nextQuery = nextSearchParams.toString();
    const nextUrl = nextQuery ? `${pathname}?${nextQuery}` : pathname;

    router.push(nextUrl);
  };

  const handleSuggestionClick = (suggestion) => {
    const nextSearchParams = new URLSearchParams();
    nextSearchParams.set("q", suggestion);
    router.push(`/search?${nextSearchParams.toString()}`);
  };

  if (!trimmedQuery) {
    return (
      <div className="space-y-8">
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-start gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
              <Search size={22} />
            </div>
            <div>
              <p className="mb-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary/80">
                Search Products
              </p>
              <h1 className="text-2xl font-bold text-slate-900 sm:text-3xl">
                Start typing to search
              </h1>
              <p className="mt-2 text-sm text-slate-500">
                Search for products, brands, and categories to find what you need faster.
              </p>
            </div>
          </div>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="mb-4 flex items-center gap-2">
            <Sparkles size={18} className="text-primary" />
            <h2 className="text-lg font-semibold text-slate-900">Popular Searches</h2>
          </div>

          <div className="flex flex-wrap gap-3">
            {popularSearches.map((suggestion) => (
              <button
                key={suggestion}
                type="button"
                onClick={() => handleSuggestionClick(suggestion)}
                className="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-primary/40 hover:bg-primary/5 hover:text-primary"
              >
                {suggestion}
              </button>
            ))}
          </div>
        </div>

        <div>
          <div className="mb-4 flex items-center gap-2">
            <TrendingUp size={22} className="text-teal-500" />
            <h2 className="text-lg font-semibold sm:text-xl md:text-2xl">
              Trending Products
            </h2>
          </div>

          <FilteredProductCard
            filteredProducts={trendingProducts}
            isLoading={isTrendingLoading || isTrendingFetching}
          />
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="flex items-start gap-4 px-4 py-5 sm:px-6">
          <div>
            <p className="mb-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary/80">
              Search Results
            </p>
            <h1 className="text-2xl font-bold text-slate-900 sm:text-3xl">
              Results for &quot;{trimmedQuery}&quot;
            </h1>
            <p className="mt-2 text-sm text-slate-500">
              Showing {pagination?.from ?? 0}-{pagination?.to ?? searchResults.length} of{" "}
              {pagination?.total ?? searchResults.length} items
            </p>
          </div>
        </div>
      </div>

      <FilteredProductCard
        filteredProducts={searchResults}
        isLoading={isLoading || isFetching}
        pagination={pagination}
        currentPage={currentPage}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
