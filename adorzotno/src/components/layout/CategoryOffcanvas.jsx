"use client";

import React, { useMemo, useRef, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { ChevronRight, Menu, X } from "lucide-react";
import { getImageUrl } from "@/lib/imageHelpers";
import { useGetCategoriesQuery } from "@/redux/features/category/categoryApi";

export default function CategoryOffcanvas({ sidebarOpen, setSidebarOpen }) {
  const { data: apiCategories = [], isLoading } = useGetCategoriesQuery();
  const [activeParentCategory, setActiveParentCategory] = useState(null);
  const scrollContainerRef = useRef(null);

  const categories = useMemo(() => {
    return apiCategories
      .filter((category) => (category?.status ? category.status === "active" : true))
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

  const isSubcategoryView = Boolean(activeParentCategory);

  const closeSidebar = () => {
    setActiveParentCategory(null);
    setSidebarOpen(false);
  };

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

  const renderCategoryItems = (items, subcategoryView = false) =>
    items?.map((category) => {
      const iconUrl = category?.icon ? getImageUrl(category.icon) : null;
      const hasChildren = !subcategoryView && category.children?.length > 0;

      return (
        <div
          key={category.id}
          className="flex items-center justify-between px-6 py-3 transition group hover:bg-sky-50"
        >
          <Link
            href={`/category/${category.slug}`}
            onClick={closeSidebar}
            className="flex min-w-0 flex-1 items-center gap-3"
          >
            <span
              className={`flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg ${iconUrl ? "bg-transparent" : "bg-slate-100"
                }`}
            >
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
              <span className="block truncate font-bold text-gray-700 group-hover:text-primary">
                {category.name}
              </span>
              <span className="block text-xs text-slate-500">
                {category.children?.length > 0
                  ? `${category.children.length} subcategories`
                  : "Explore products"}
              </span>
            </div>
          </Link>

          {hasChildren ? (
            <button
              type="button"
              onClick={() => handleOpenSubcategories(category)}
              className="ml-3 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-sky-100 hover:text-primary"
              aria-label={`View subcategories of ${category.name}`}
            >
              <ChevronRight size={18} />
            </button>
          ) : (
            <span className="ml-3 h-10 w-10 shrink-0" />
          )}
        </div>
      );
    });

  return (
    <>
      {/* Overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-50"
          onClick={closeSidebar}
        />
      )}

      {/* Sidebar */}
      <div
        className={`fixed lg:hidden top-0 left-0 h-full w-full sm:w-96 bg-white shadow-2xl z-50 transform transition-transform duration-300
        ${sidebarOpen ? "translate-x-0" : "-translate-x-full"}`}
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b bg-primary text-white">
            {isSubcategoryView ? (
              <div className="flex min-w-0 flex-1 items-center justify-between gap-3 pr-3">
                <button
                  type="button"
                  onClick={handleBackToCategories}
                  className="inline-flex items-center gap-1 rounded-lg border border-white/25 px-3 py-2 text-sm font-semibold transition hover:bg-white/10"
                >
                  <ChevronRight size={16} className="rotate-180" />
                  Back
                </button>

                <div className="text-right">
                  <h2 className="text-xl font-bold">{activeParentCategory?.name}</h2>
                  <p className="text-sm text-teal-100">Browse subcategories</p>
                </div>
              </div>
            ) : (
              <div className="flex min-w-0 flex-1 items-center gap-3 pr-3">
                <Menu size={24} />
                <div>
                  <h2 className="text-xl font-bold">Categories</h2>
                  <p className="text-sm text-teal-100">Browse all categories</p>
                </div>
              </div>
            )}

            <button
              onClick={closeSidebar}
              className="p-2 hover:bg-teal-700 rounded-lg"
            >
              <X size={24} />
            </button>
          </div>

          {/* Category List */}
          <div
            ref={scrollContainerRef}
            className="flex-1 overflow-y-auto py-6 space-y-2"
          >
            {isLoading ? (
              Array.from({ length: 8 }).map((_, index) => (
                <div
                  key={index}
                  className="flex items-center justify-between px-6 py-3"
                >
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 animate-pulse rounded-lg bg-slate-100" />
                    <div className="space-y-2">
                      <div className="h-4 w-28 animate-pulse rounded bg-slate-100" />
                      <div className="h-3 w-16 animate-pulse rounded bg-slate-100" />
                    </div>
                  </div>
                  <div className="h-5 w-5 animate-pulse rounded bg-slate-100" />
                </div>
              ))
            ) : (
              <div className="overflow-hidden">
                <div
                  className={`flex w-[200%] transition-transform duration-200 ease-out ${isSubcategoryView ? "-translate-x-1/2" : "translate-x-0"
                    }`}
                >
                  <div className="w-1/2 shrink-0">
                    {renderCategoryItems(categories)}
                  </div>
                  <div className="w-1/2 shrink-0">
                    {renderCategoryItems(activeSubcategories, true)}
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
