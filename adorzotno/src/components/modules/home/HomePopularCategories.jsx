"use client";

import Link from "next/link";
import { ChevronRight, Flame } from "lucide-react";
import { useMemo } from "react";
import CategoryCard from "@/components/cards/CategoryCard";
import { useGetCategoriesQuery } from "@/redux/features/category/categoryApi";

export default function HomePopularCategories() {
    const { data: apiCategories = [], isLoading } = useGetCategoriesQuery();

    const popularCategories = useMemo(() => {
        const activeCategories = apiCategories
            .filter((category) => (category?.status ? category.status === "active" : true))
            .sort((a, b) => (a?.sort_order ?? 9999) - (b?.sort_order ?? 9999));

        const prioritizedCategories = activeCategories.filter(
            (category) => Number(category?.is_popular) === 1,
        );

        return (prioritizedCategories.length > 0 ? prioritizedCategories : activeCategories).slice(0, 8);
    }, [apiCategories]);

    return (
        <section className="mb-10 bg-white p-4 sm:p-5 border border-slate-200 rounded-lg">
            <div className="mb-5 flex justify-between gap-4 lg:items-end lg:justify-between">
                <div className="flex items-center gap-2">
                    <h2 className="text-lg sm:text-xl md:text-2xl font-semibold">
                        Popular Categories
                    </h2>
                    <Flame className="text-orange-500" size={24} />
                </div>

                <Link
                    href="/category"
                    className="inline-flex w-fit items-center gap-2 rounded-full border border-primary/20 bg-white px-4 py-2.5 text-sm font-semibold text-primary transition-all duration-300 hover:border-primary hover:bg-primary hover:text-white"
                >
                    <span className="sm:hidden whitespace-nowrap">View all</span>
                    <span className="hidden sm:inline">Explore all categories</span>
                    <ChevronRight size={16} />
                </Link>
            </div>

            <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8">
                {isLoading
                    ? Array.from({ length: 8 }).map((_, index) => (
                        <div
                            key={index}
                            className="flex flex-col items-center rounded-lg bg-transparent p-2 text-center sm:p-3"
                        >
                            <div className="flex w-full items-center justify-center">
                                <div className="relative aspect-square w-28 sm:w-32">
                                    <div className="absolute inset-0 animate-pulse rounded-full bg-slate-100" />
                                </div>
                            </div>
                            <div className="mt-3 h-4 w-20 animate-pulse rounded bg-slate-100 sm:w-24" />
                        </div>
                    ))
                    : popularCategories.map((category, index) => (
                        <CategoryCard
                            key={category.id ?? `${category.name}-${index}`}
                            category={category}
                            index={index}
                        />
                    ))}
            </div>
        </section>
    );
}
