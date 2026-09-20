"use client";

import Image from "next/image";
import Link from "next/link";
import { ChevronRight } from "lucide-react";
import { useMemo, useRef, useState } from "react";
import { useGetCategoriesQuery } from "@/redux/features/category/categoryApi";
import { getImageUrl } from "@/lib/imageHelpers";

export default function HeaderCategoryMenu({
  isOpen,
  onClose,
}) {
  const { data: apiCategories = [], isLoading } = useGetCategoriesQuery();
  const [activeParentCategory, setActiveParentCategory] = useState(null);
  const scrollContainerRef = useRef(null);

  const categories = useMemo(() => {
    return apiCategories
      .filter((category) => category?.status ? category.status === "active" : true)
      .sort((a, b) => (a?.sort_order ?? 9999) - (b?.sort_order ?? 9999));
  }, [apiCategories]);

  const activeSubcategories = useMemo(() => {
    if (!activeParentCategory?.children?.length) {
      return [];
    }

    return activeParentCategory.children
      .filter((category) => (category?.status ? category.status === "active" : true))
      .sort((a, b) => (a?.sort_order ?? 9999) - (b?.sort_order ?? 9999));
  }, [activeParentCategory]);

  if (!isOpen) return null;

  const isSubcategoryView = Boolean(activeParentCategory);
  const buildCategoryColumns = (items) => {
    const midpoint = Math.ceil(items.length / 2);

    return [
      items.slice(0, midpoint),
      items.slice(midpoint),
    ];
  };

  const categoryColumns = buildCategoryColumns(categories);
  const subcategoryColumns = buildCategoryColumns(activeSubcategories);

  const scrollMenuToTop = () => {
    if (scrollContainerRef.current) {
      scrollContainerRef.current.scrollTo({ top: 0, behavior: "instant" });
    }
  };

  const handleOpenSubcategories = (category) => {
    scrollMenuToTop();
    setActiveParentCategory(category);
  };

  const handleBackToCategories = () => {
    scrollMenuToTop();
    setActiveParentCategory(null);
  };

  const renderCategoryColumns = (columns, subcategoryView = false) => (
    <div className="grid gap-x-3 gap-y-2 md:grid-cols-2">
      {columns.map((column, columnIndex) => (
        <div key={columnIndex} className="space-y-2">
          {column.map((category) => {
            const iconUrl = category?.icon ? getImageUrl(category.icon) : null;
            const hasChildren = !subcategoryView && category.children?.length > 0;

            return (
              <div
                key={category.id}
                className="group/item flex items-center justify-between rounded-lg px-3 py-2.5 transition-all duration-300 hover:bg-gradient-to-r hover:from-primary/20 hover:to-primary/10"
              >
                <Link
                  href={`/category/${category.slug}`}
                  onClick={onClose}
                  className="flex min-w-0 flex-1 items-center gap-3"
                >
                  <span className={`flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg ${iconUrl ? "bg-transparent" : "bg-slate-100"}`}>
                    {iconUrl ? (
                      <Image
                        src={iconUrl}
                        alt={category.name}
                        width={40}
                        height={40}
                        className="h-full w-full object-cover"
                        unoptimized
                      />
                    ) : (
                      <span className="text-sm font-bold text-primary">
                        {category?.name?.slice(0, 2)?.toUpperCase()}
                      </span>
                    )}
                  </span>
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-800 transition-colors group-hover/item:text-primary">
                      {category.name}
                    </p>
                    <p className="text-xs text-slate-500">
                      {hasChildren
                        ? `${category.children.length} subcategories`
                        : "Explore products"}
                    </p>
                  </div>
                </Link>

                {hasChildren ? (
                  <button
                    type="button"
                    onClick={() => handleOpenSubcategories(category)}
                    className="ml-3 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-all duration-300 hover:bg-white/80 hover:text-primary group-hover/item:translate-x-0.5"
                    aria-label={`View subcategories of ${category.name}`}
                  >
                    <ChevronRight size={18} />
                  </button>
                ) : (
                  <span className="ml-3 h-10 w-10 shrink-0" />
                )}
              </div>
            );
          })}
        </div>
      ))}
    </div>
  );

  return (
    <div className="absolute left-0 top-full z-50 mt-1 hidden w-[min(92vw,560px)] max-w-[560px] overflow-hidden border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.16)] lg:block">
      <div
        ref={scrollContainerRef}
        className="p-5 lg:max-h-[min(78vh,630px)] lg:overflow-y-auto [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:bg-gray-300"
      >
        <div className="mb-4 flex items-center justify-between transition-all duration-200 ease-out">
          {isSubcategoryView ? (
            <div className="flex w-full items-center justify-between gap-3">
              <button
                type="button"
                onClick={handleBackToCategories}
                className="inline-flex items-center gap-1 rounded-full border border-primary/20 px-3 py-1.5 text-sm font-semibold text-primary transition-all hover:bg-primary hover:text-white"
              >
                <ChevronRight size={16} className="rotate-180" />
                Back
              </button>

              <div className="text-right">
                <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                  Subcategories
                </p>
                <h3 className="mt-1 text-lg font-bold text-slate-600">
                  {activeParentCategory?.name}
                </h3>
              </div>
            </div>
          ) : (
            <>
              <div>
                <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                  Shop Faster
                </p>
                <h3 className="mt-1 text-lg font-bold text-slate-600">
                  Explore by category
                </h3>
              </div>
              <Link
                href="/category"
                onClick={onClose}
                className="inline-flex items-center gap-1 rounded-full border border-primary/20 px-3 py-1.5 text-sm font-semibold text-primary transition-all hover:bg-primary hover:text-white"
              >
                View all
                <ChevronRight size={16} />
              </Link>
            </>
          )}
        </div>

        {isLoading ? (
          <div className="grid gap-x-3 gap-y-2 md:grid-cols-2">
            {Array.from({ length: 8 }).map((_, index) => (
              <div
                key={index}
                className="flex items-center justify-between rounded-lg px-3 py-2.5"
              >
                <div className="flex min-w-0 items-center gap-3">
                  <div className="h-10 w-10 animate-pulse rounded-lg bg-slate-100" />
                  <div className="min-w-0 space-y-2">
                    <div className="h-4 w-28 animate-pulse rounded bg-slate-100" />
                    <div className="h-3 w-20 animate-pulse rounded bg-slate-100" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="overflow-hidden">
            <div
              className={`flex w-[200%] transition-transform duration-200 ease-out ${isSubcategoryView ? "-translate-x-1/2" : "translate-x-0"
                }`}
            >
              <div className="w-1/2 shrink-0">
                {renderCategoryColumns(categoryColumns)}
              </div>
              <div className="w-1/2 shrink-0">
                {renderCategoryColumns(subcategoryColumns, true)}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
