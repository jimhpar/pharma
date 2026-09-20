"use client";

import { ChevronRight, Home, LayoutGrid } from "lucide-react";
import Link from "next/link";
import { useMemo } from "react";
import CategoryCard from "@/components/cards/CategoryCard";
import { useGetCategoriesQuery } from "@/redux/features/category/categoryApi";

function CategoryCardSkeleton() {
  return (
    <div className="flex flex-col items-center rounded-lg bg-transparent p-2 text-center sm:p-3">
      <div className="flex w-full items-center justify-center">
        <div className="relative aspect-square w-28 sm:w-32">
          <div className="absolute inset-0 animate-pulse rounded-full bg-slate-100" />
        </div>
      </div>
      <div className="mt-3 h-4 w-20 animate-pulse rounded bg-slate-100 sm:w-24" />
    </div>
  );
}

export default function AllCategoriesPage() {
  const { data: apiCategories = [], isLoading } = useGetCategoriesQuery();

  const categories = useMemo(() => {
    return apiCategories
      .filter((category) => (category?.status ? category.status === "active" : true))
      .sort((a, b) => (a?.sort_order ?? 9999) - (b?.sort_order ?? 9999));
  }, [apiCategories]);

  return (
    <div>
      <div className="mb-8">
        <div className="mb-6 overflow-hidden rounded-[28px] bg-gradient-to-br from-white via-slate-50 to-primary/5 p-5 shadow-sm md:p-6">
          <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <Link
              href="/"
              className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 font-medium text-slate-600 transition-colors hover:text-primary"
            >
              <Home size={16} />
              Home
            </Link>
            <ChevronRight size={16} className="text-slate-300" />
            <span className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5 font-semibold text-primary">
              <LayoutGrid size={16} />
              Categories
            </span>
          </div>

          <div className="mt-4">
            <h1 className="text-2xl font-bold text-slate-900 md:text-3xl">
              All Categories
            </h1>
            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
              Browse every health, wellness, and pharmacy category in one place
              to quickly find the products you need.
            </p>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
          {isLoading
            ? Array.from({ length: 12 }).map((_, index) => (
              <CategoryCardSkeleton key={index} />
            ))
            : categories.map((category, index) => (
              <CategoryCard
                key={category.id ?? `${category.name}-${index}`}
                category={category}
                index={index}
              />
            ))}
        </div>
      </div>
    </div>
  );
}
