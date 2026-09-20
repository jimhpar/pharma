"use client";

import { ChevronRight, Home, Zap } from "lucide-react";
import Link from "next/link";
import React, { useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetFlashDealsQuery } from "@/redux/features/product/productApi";
import FilteredProductCard from "../category/FilteredProductCard";

export default function FlashDealsPage({ initialPage = 1 }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [currentPage, setCurrentPage] = useState(initialPage);

  const {
    data: flashDealsResponse = {},
    isLoading,
    isFetching,
  } = useGetFlashDealsQuery({
    page: currentPage,
    perPage: 20,
    sortBy: "created_at",
    sortOrder: "desc",
  });

  const pagination = flashDealsResponse || null;

  const flashDealsProducts = useMemo(
    () => (flashDealsResponse?.data || []).map(mapApiProductToCard),
    [flashDealsResponse?.data],
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

    const nextQuery = nextSearchParams.toString();
    const nextUrl = nextQuery ? `${pathname}?${nextQuery}` : pathname;

    router.push(nextUrl);
  };

  return (
    <div>
      <div className="mb-6 rounded-md bg-gradient-to-r from-primary to-primary/80 px-4 py-3 text-white">
        <div className="flex items-center gap-4">
          <Link href="/">
            <button className="flex cursor-pointer items-center gap-1 hover:underline hover:underline-offset-2">
              <Home size={16} />
              Home
            </button>
          </Link>
          <ChevronRight size={16} />
          <div className="flex items-center gap-2 space-x-1">
            <Zap size={18} className="text-yellow-300" />
            <span className="font-semibold">Flash Deals</span>
            <span className="text-sm text-gray-100">
              ({pagination?.total ?? flashDealsProducts.length} items)
            </span>
          </div>
        </div>
      </div>

      <FilteredProductCard
        filteredProducts={flashDealsProducts}
        isLoading={isLoading || isFetching}
        pagination={pagination}
        currentPage={currentPage}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
