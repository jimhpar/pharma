"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Search, Grid3x3, List, Package } from "lucide-react";
import InteractiveRatingStars from "../shared/InteractiveRatingStars";
import { getImageUrl } from "@/lib/imageHelpers";
import { useGetBrandsQuery } from "@/redux/features/brand/brandApi";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export default function BrandPage() {
  const [searchQuery, setSearchQuery] = useState("");
  const [viewMode, setViewMode] = useState("grid");
  const [sortBy, setSortBy] = useState("name-asc");
  const { data: brands = [], isLoading } = useGetBrandsQuery();

  const activeBrands = (brands || []).filter(
    (brand) => (brand?.status ? brand.status === "active" : true),
  );

  const filteredBrands = activeBrands.filter((brand) => {
    const query = searchQuery.trim().toLowerCase();
    if (!query) return true;

    return (
      brand?.name?.toLowerCase().includes(query) ||
      (brand?.description || "").toLowerCase().includes(query) ||
      (brand?.headquarter_address || "").toLowerCase().includes(query)
    );
  });

  const sortedBrands = [...filteredBrands].sort((a, b) => {
    switch (sortBy) {
      case "name-asc":
        return a.name.localeCompare(b.name);
      case "name-desc":
        return b.name.localeCompare(a.name);
      case "products-high":
        return toNumber(b.products_count) - toNumber(a.products_count);
      case "products-low":
        return toNumber(a.products_count) - toNumber(b.products_count);
      case "rating-high":
        return toNumber(b.rating) - toNumber(a.rating);
      case "popular":
        return toNumber(b.reviews_count) - toNumber(a.reviews_count);
      default:
        return (a.sort_order ?? 9999) - (b.sort_order ?? 9999);
    }
  });

  const totalProducts = activeBrands.reduce(
    (sum, brand) => sum + toNumber(brand.products_count),
    0,
  );
  const averageRatingSource = activeBrands.filter((brand) => toNumber(brand.rating) > 0);
  const averageRating = averageRatingSource.length
    ? (
      averageRatingSource.reduce((sum, brand) => sum + toNumber(brand.rating), 0) /
      averageRatingSource.length
    ).toFixed(1)
    : "0.0";
  const verifiedBrandsCount = activeBrands.filter((brand) => brand.is_verified).length;

  return (
    <div className="min-h-screen">
      <div className="bg-gradient-to-r from-primary to-primary/80 py-16 text-white">
        <div className="container mx-auto px-4">
          <div className="mx-auto max-w-3xl text-center">
            <h1 className="mb-4 text-4xl font-bold md:text-5xl">Shop by Brand</h1>
            <p className="mb-8 text-lg text-white/90 md:text-xl">
              Discover trusted pharmaceutical brands and healthcare products
              from leading companies worldwide
            </p>

            <div className="relative mx-auto max-w-2xl">
              <Search
                className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                size={24}
              />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search brands..."
                className="w-full rounded-xl py-4 pl-14 pr-4 text-lg text-gray-800 shadow-xl focus:outline-none focus:ring-4 focus:ring-white/30"
              />
            </div>
          </div>
        </div>
      </div>

      <div className="border-b bg-white shadow-sm">
        <div className="container mx-auto px-4 py-6">
          <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
            <div className="text-center">
              <div className="mb-1 text-3xl font-bold text-primary">
                {activeBrands.length}
              </div>
              <div className="text-sm text-gray-600">Total Brands</div>
            </div>
            <div className="text-center">
              <div className="mb-1 text-3xl font-bold text-primary">
                {totalProducts}
              </div>
              <div className="text-sm text-gray-600">Total Products</div>
            </div>
            <div className="text-center">
              <div className="mb-1 text-3xl font-bold text-primary">
                {averageRating}
              </div>
              <div className="text-sm text-gray-600">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="mb-1 text-3xl font-bold text-primary">
                {verifiedBrandsCount}
              </div>
              <div className="text-sm text-gray-600">Verified Brands</div>
            </div>
          </div>
        </div>
      </div>

      <div className="container mx-auto py-8">
        <div className="mb-6 rounded-xl bg-white p-4 shadow-md">
          <div className="flex flex-col flex-wrap items-center justify-between gap-4 md:flex-row">
            <div className="flex w-full items-center gap-4 md:w-auto">
              <span className="font-semibold text-gray-700">
                {sortedBrands.length} Brands
              </span>
              {searchQuery ? (
                <span className="text-sm text-gray-500">
                  searching for &quot;{searchQuery}&quot;
                </span>
              ) : null}
            </div>

            <div className="flex w-full items-center gap-3 md:w-auto">
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary md:flex-none"
              >
                <option value="name-asc">Name: A-Z</option>
                <option value="name-desc">Name: Z-A</option>
                <option value="products-high">Most Products</option>
                <option value="products-low">Least Products</option>
                <option value="rating-high">Highest Rated</option>
                <option value="popular">Most Popular</option>
              </select>

              <div className="flex gap-2">
                <button
                  onClick={() => setViewMode("grid")}
                  className={`rounded-lg p-2 transition-colors ${viewMode === "grid"
                    ? "bg-primary text-white"
                    : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                    }`}
                >
                  <Grid3x3 size={20} />
                </button>
                <button
                  onClick={() => setViewMode("list")}
                  className={`rounded-lg p-2 transition-colors ${viewMode === "list"
                    ? "bg-primary text-white"
                    : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                    }`}
                >
                  <List size={20} />
                </button>
              </div>
            </div>
          </div>
        </div>

        {isLoading ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
            {Array.from({ length: 10 }).map((_, index) => (
              <div
                key={index}
                className="overflow-hidden rounded-2xl border-2 border-slate-100 bg-white"
              >
                <div className="h-48 animate-pulse bg-slate-100" />
                <div className="space-y-3 p-6">
                  <div className="h-6 w-2/3 animate-pulse rounded bg-slate-100" />
                  <div className="h-4 w-full animate-pulse rounded bg-slate-100" />
                  <div className="h-4 w-1/2 animate-pulse rounded bg-slate-100" />
                </div>
              </div>
            ))}
          </div>
        ) : sortedBrands.length === 0 ? (
          <div className="rounded-xl bg-white p-12 text-center shadow-md">
            <Package className="mx-auto mb-4 text-gray-300" size={64} />
            <h3 className="mb-2 text-xl font-bold text-gray-800">No brands found</h3>
            <p className="mb-6 text-gray-600">Try adjusting your search query</p>
            <button
              onClick={() => setSearchQuery("")}
              className="rounded-lg bg-primary px-6 py-2 font-semibold text-white hover:bg-primary/90"
            >
              Clear Search
            </button>
          </div>
        ) : viewMode === "grid" ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
            {sortedBrands.map((brand) => (
              <Link key={brand.id} href={`/brand/${brand.slug}`}>
                <div className="group flex h-full cursor-pointer flex-col overflow-hidden rounded-2xl border-2 border-transparent bg-white shadow-md transition-all duration-300 hover:border-primary/20 hover:shadow-2xl">
                  <div className="relative flex h-48 items-center justify-center bg-gradient-to-br from-slate-50 to-white p-8 transition-all duration-300">
                    <div className="relative flex h-32 w-full items-center justify-center">
                      <Image
                        src={getImageUrl(brand.logo)}
                        alt={brand.name}
                        width={200}
                        height={100}
                        className="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-110"
                        unoptimized
                      />
                    </div>

                    <div className="absolute top-3 right-3 rounded-full bg-white/90 px-3 py-1 text-sm font-bold text-primary shadow-md backdrop-blur-sm">
                      {toNumber(brand.products_count)} Products
                    </div>
                  </div>

                  <div className="flex flex-1 flex-col p-6">
                    <h3 className="mb-2 text-xl font-bold text-gray-800 transition-colors group-hover:text-primary">
                      {brand.name}
                    </h3>
                    <p className="mb-4 line-clamp-2 flex-1 text-sm text-gray-600">
                      {brand.description || "No description available yet."}
                    </p>

                    <div className="mb-4 flex items-center gap-2">
                      <InteractiveRatingStars
                        value={brand.rating}
                        readonly
                        size={16}
                        className="text-sm"
                      />
                      <span className="text-sm text-gray-500">
                        ({toNumber(brand.reviews_count).toLocaleString()} reviews)
                      </span>
                    </div>

                    <div className="flex items-center justify-between border-t pt-4 text-sm text-gray-600">
                      <span>
                        Founded {brand.founded_year || "N/A"}
                      </span>
                      <span className="font-semibold">
                        {brand.is_verified ? "Verified" : "Brand"}
                      </span>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                      {(brand.brand_tags || []).slice(0, 2).map((item) => (
                        <span
                          key={item.id || item.tag}
                          className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                        >
                          {item.tag}
                        </span>
                      ))}
                      {(brand.brand_tags || []).length > 2 ? (
                        <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                          +{brand.brand_tags.length - 2}
                        </span>
                      ) : null}
                    </div>
                  </div>

                  <div className="p-6 pt-0">
                    <button className="w-full rounded-lg bg-primary py-3 font-semibold text-white transition-all hover:bg-primary/90 group-hover:shadow-lg">
                      View Products
                    </button>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        ) : (
          <div className="space-y-4">
            {sortedBrands.map((brand) => (
              <Link key={brand.id} href={`/brand/${brand.slug}`}>
                <div className="group mb-3 cursor-pointer rounded-2xl border-2 border-transparent bg-white p-6 shadow-md transition-all duration-300 hover:border-primary/20 hover:shadow-2xl">
                  <div className="flex gap-6">
                    <div className="flex w-48 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-50 to-white p-6">
                      <Image
                        src={getImageUrl(brand.logo)}
                        alt={brand.name}
                        width={150}
                        height={75}
                        className="max-h-full max-w-full object-contain"
                        unoptimized
                      />
                    </div>

                    <div className="flex-1">
                      <div className="mb-3 flex items-start justify-between">
                        <div>
                          <h3 className="mb-1 text-2xl font-bold text-gray-800 transition-colors group-hover:text-primary">
                            {brand.name}
                          </h3>
                          <p className="mb-2 text-gray-600">
                            {brand.description || "No description available yet."}
                          </p>
                        </div>
                        <div className="text-right">
                          <div className="mb-1 text-2xl font-bold text-primary">
                            {toNumber(brand.products_count)}
                          </div>
                          <div className="text-sm text-gray-500">Products</div>
                        </div>
                      </div>

                      <div className="mb-4 flex items-center gap-4">
                        <div className="flex items-center gap-2">
                          <InteractiveRatingStars
                            value={brand.rating}
                            readonly
                            size={16}
                            className="text-sm"
                          />
                        </div>
                        <span className="text-gray-500">
                          {toNumber(brand.reviews_count).toLocaleString()} reviews
                        </span>
                        <span className="text-gray-400">•</span>
                        <span className="text-gray-600">
                          Founded {brand.founded_year || "N/A"}
                        </span>
                        <span className="text-gray-400">•</span>
                        <span className="font-semibold text-gray-600">
                          {brand.is_verified ? "Verified" : "Brand"}
                        </span>
                      </div>

                      <div className="flex flex-wrap gap-2">
                        {(brand.brand_tags || []).map((item) => (
                          <span
                            key={item.id || item.tag}
                            className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                          >
                            {item.tag}
                          </span>
                        ))}
                      </div>
                    </div>

                    <div className="flex items-center">
                      <button className="whitespace-nowrap rounded-lg bg-primary px-8 py-3 font-semibold text-white transition-all hover:bg-primary/90">
                        View Products
                      </button>
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
