"use client";

import React, { useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import {
  ChevronRight,
  Home,
  Grid3x3,
  List,
  Award,
  Shield,
  Package,
  MapPin,
  Building2,
  CalendarDays,
} from "lucide-react";
import ProductCard from "../cards/ProductCard";
import Pagination from "../shared/Pagination";
import InteractiveRatingStars from "../shared/InteractiveRatingStars";
import { getImageUrl } from "@/lib/imageHelpers";
import { getProductRating } from "@/lib/getProductRating";
import {
  useGetBrandProductsQuery,
  useGetBrandsQuery,
} from "@/redux/features/brand/brandApi";

const BRAND_SORT_OPTIONS = [
  { value: "most_popular", label: "Most Popular" },
  { value: "price_low_to_high", label: "Price: Low to High" },
  { value: "price_high_to_low", label: "Price: High to Low" },
  { value: "highest_rated", label: "Highest Rated" },
];

const normalizePage = (value) => {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

const normalizeSort = (value) => {
  const matchedOption = BRAND_SORT_OPTIONS.find((option) => option.value === value);
  return matchedOption?.value || "most_popular";
};

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const formatPrice = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : "0.00";
};

export default function BrandProductsPage({
  slug,
  initialPage = 1,
  initialSort = "most_popular",
}) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [viewMode, setViewMode] = useState("grid");
  const [sortBy, setSortBy] = useState(normalizeSort(initialSort));
  const [currentPage, setCurrentPage] = useState(normalizePage(initialPage));

  const { data: brands = [], isLoading: isBrandsLoading } = useGetBrandsQuery();

  const selectedBrand = useMemo(() => {
    return brands.find((brand) => brand.slug === slug);
  }, [brands, slug]);

  const {
    data: brandProductsResponse = {},
    isLoading: isProductsLoading,
    isFetching: isProductsFetching,
  } = useGetBrandProductsQuery(
    {
      brandSlug: slug,
      page: currentPage,
      perPage: 20,
      sortBy,
    },
    {
      skip: !slug,
    },
  );

  const brand = brandProductsResponse?.brand || selectedBrand;
  const pagination = brandProductsResponse?.products || null;

  const apiProducts = pagination?.data || [];

  const mappedProducts = apiProducts
    .map((product) => {
      const primarySku = product?.sku?.[0] || {};
      const skuSellingPrice = toNumber(primarySku?.selling_price);
      const productSellingPrice = toNumber(product?.selling_price);
      const basePrice = skuSellingPrice || productSellingPrice;
      const discountType = product?.default_discount_type;
      const discountValue = toNumber(product?.default_discount_value);

      let originalPrice = basePrice > 0 ? basePrice : null;
      let effectivePrice = basePrice;
      let discountAmount = 0;
      let discountLabel = null;

      if (basePrice > 0 && discountValue > 0) {
        if (discountType === "amount") {
          discountAmount = Math.min(discountValue, basePrice);
          effectivePrice = Math.max(basePrice - discountAmount, 0);
          discountLabel = `\u09F3${discountAmount.toFixed(0)} off`;
        } else if (discountType === "percent" && discountValue < 100) {
          discountAmount = (basePrice * discountValue) / 100;
          effectivePrice = Math.max(basePrice - discountAmount, 0);
          discountLabel = `${Math.round(discountValue)}% off`;
        }
      }

      return {
        id: product.id,
        slug: product.slug,
        name: product.name,
        rating: getProductRating(product),
        price: effectivePrice,
        originalPrice:
          originalPrice && originalPrice > effectivePrice ? originalPrice : null,
        discountAmount: discountAmount > 0 ? discountAmount : null,
        discountLabel,
        images: [getImageUrl(product?.thumbnail_image)],
        category: product?.category?.name || "",
        shortDescription: product?.short_description || "",
      };
    });

  const isLoading = isBrandsLoading || isProductsLoading || isProductsFetching;

  const handlePageChange = (page) => {
    if (!pagination?.last_page) return;

    const nextPage = Math.min(Math.max(page, 1), pagination.last_page);
    if (nextPage === currentPage) return;

    setCurrentPage(nextPage);

    const nextSearchParams = new URLSearchParams(searchParams.toString());
    if (nextPage <= 1) {
      nextSearchParams.delete("page");
    } else {
      nextSearchParams.set("page", String(nextPage));
    }

    const nextQuery = nextSearchParams.toString();
    router.push(nextQuery ? `${pathname}?${nextQuery}` : pathname);
  };

  const handleSortChange = (nextSort) => {
    const normalizedSort = normalizeSort(nextSort);

    if (normalizedSort === sortBy) {
      return;
    }

    setSortBy(normalizedSort);
    setCurrentPage(1);

    const nextSearchParams = new URLSearchParams(searchParams.toString());

    if (normalizedSort === "most_popular") {
      nextSearchParams.delete("sort_by");
    } else {
      nextSearchParams.set("sort_by", normalizedSort);
    }

    nextSearchParams.delete("page");

    const nextQuery = nextSearchParams.toString();
    router.push(nextQuery ? `${pathname}?${nextQuery}` : pathname);
  };

  return (
    <div className="min-h-screen">
      <div className="border-b bg-white">
        <div className="container mx-auto px-4 py-2">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Link href="/" className="flex items-center gap-1 hover:text-primary">
              <Home size={16} />
              Home
            </Link>
            <ChevronRight size={16} />
            <Link href="/brand" className="hover:text-primary">
              Brands
            </Link>
            <ChevronRight size={16} />
            <span className="font-semibold text-gray-800">
              {brand?.name || "Brand"}
            </span>
          </div>
        </div>
      </div>

      <div className="mb-6 border-b bg-white">
        <div className="container mx-auto py-8">
          <div className="relative mb-6 h-48 overflow-hidden rounded-2xl md:h-64">
            <Image
              src={getImageUrl(brand?.background_image)}
              alt={brand?.name || "Brand banner"}
              fill
              className="object-cover"
              unoptimized
            />
            <div className="absolute inset-0 bg-gradient-to-r from-primary/85 to-primary/45" />
            <div className="absolute inset-0 flex items-center">
              <div className="container mx-auto px-8">
                <div className="flex items-center gap-6">
                  <div className="rounded-2xl bg-white p-6 shadow-2xl">
                    <Image
                      src={getImageUrl(brand?.logo)}
                      alt={brand?.name || "Brand logo"}
                      width={120}
                      height={60}
                      className="object-contain"
                      unoptimized
                    />
                  </div>
                  <div className="text-white">
                    <h1 className="mb-2 text-4xl font-bold md:text-5xl">
                      {brand?.name || "Brand"}
                    </h1>
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                      <span className="flex items-center gap-1">
                        <InteractiveRatingStars
                          value={brand?.rating}
                          readonly
                          size={16}
                          className="text-white"
                        />
                      </span>
                      <span>•</span>
                      <span>{brand?.products_count || pagination?.total || 0} Products</span>
                      <span>•</span>
                      <span>{toNumber(brand?.reviews_count).toLocaleString()} Reviews</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
            <div className="md:col-span-2">
              <h2 className="mb-3 text-xl font-bold text-gray-800">
                About {brand?.name}
              </h2>
              <p className="mb-4 text-sm leading-relaxed text-gray-600">
                {brand?.description || "No brand description available yet."}
              </p>
              <div className="flex flex-wrap gap-2">
                {(brand?.brand_tags || []).map((item) => (
                  <span
                    key={item.id || item.tag}
                    className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                  >
                    {item.tag}
                  </span>
                ))}
              </div>
            </div>

            <div>
              <h3 className="mb-3 text-lg font-bold text-gray-800">Company Info</h3>
              <div className="space-y-3 text-sm text-gray-700">
                <div className="flex items-start gap-2">
                  <CalendarDays size={16} className="mt-0.5 text-primary" />
                  <div>
                    <p className="text-xs text-gray-500">Founded</p>
                    <p className="font-semibold">{brand?.founded_year || "N/A"}</p>
                  </div>
                </div>
                <div className="flex items-start gap-2">
                  <MapPin size={16} className="mt-0.5 text-primary" />
                  <div>
                    <p className="text-xs text-gray-500">Headquarters</p>
                    <p className="font-semibold">
                      {brand?.headquarter_address || "N/A"}
                    </p>
                  </div>
                </div>
                <div className="flex items-start gap-2">
                  <Building2 size={16} className="mt-0.5 text-primary" />
                  <div>
                    <p className="text-xs text-gray-500">Employees</p>
                    <p className="font-semibold">
                      {brand?.employees_count
                        ? `${brand.employees_count.toLocaleString()}+`
                        : "N/A"}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <h3 className="mb-3 text-lg font-bold text-gray-800">
                Certifications
              </h3>
              <div className="space-y-2">
                {(brand?.brand_certifications || []).map((item) => (
                  <div key={item.id || item.certification} className="flex items-center gap-2">
                    <Shield className="text-green-600" size={16} />
                    <span className="text-sm text-gray-700">{item.certification}</span>
                  </div>
                ))}
              </div>
              {brand?.is_verified ? (
                <div className="mt-4 rounded-lg bg-green-50 p-4">
                  <div className="flex items-center gap-2 text-green-700">
                    <Award size={20} />
                    <span className="text-sm font-semibold">Verified Brand</span>
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </div>
      </div>

      <div className="container mx-auto pb-12">
        <div className="mb-6 rounded-lg border bg-white p-4 shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <h2 className="text-lg font-bold text-gray-800">
              {pagination?.total || mappedProducts.length} Products
            </h2>

            <div className="flex items-center gap-3">
              <select
                value={sortBy}
                onChange={(e) => handleSortChange(e.target.value)}
                className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
              >
                {BRAND_SORT_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>

              <div className="hidden gap-2 md:flex">
                <button
                  onClick={() => setViewMode("grid")}
                  className={`rounded-lg p-2 ${viewMode === "grid"
                    ? "bg-primary text-white"
                    : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                    }`}
                >
                  <Grid3x3 size={20} />
                </button>
                <button
                  onClick={() => setViewMode("list")}
                  className={`rounded-lg p-2 ${viewMode === "list"
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
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
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
        ) : mappedProducts.length === 0 ? (
          <div className="rounded-lg border bg-white p-12 text-center shadow-sm">
            <Package className="mx-auto mb-4 text-gray-300" size={64} />
            <h3 className="mb-2 text-xl font-bold text-gray-800">No Products Found</h3>
          </div>
        ) : (
          <>
            <div
              className={
                viewMode === "grid"
                  ? "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6"
                  : "space-y-3"
              }
            >
              {mappedProducts.map((product) => (
                <div key={product.id}>
                  {viewMode === "grid" ? (
                    <ProductCard product={product} />
                  ) : (
                    <div className="flex gap-4 rounded-lg border bg-white p-4 transition hover:shadow-xl">
                      <Image
                        src={product.images[0]}
                        alt={product.name}
                        width={150}
                        height={150}
                        className="rounded-lg object-cover"
                        unoptimized
                      />
                      <div className="flex-1">
                        <div className="mb-2">
                          <span className="text-xs font-semibold text-primary">
                            {product.category}
                          </span>
                          <h3 className="text-lg font-bold text-gray-800">
                            {product.name}
                          </h3>
                        </div>
                        <div className="mb-3">
                          <InteractiveRatingStars
                            value={product.rating}
                            readonly
                            size={16}
                          />
                        </div>
                        <div className="flex items-center justify-between">
                          <div>
                            <div className="mb-2 flex items-center gap-2">
                              <span className="text-2xl font-bold text-gray-800">
                                {"\u09F3"}{formatPrice(product.price)}
                              </span>
                              {product.originalPrice ? (
                                <span className="text-sm text-gray-400 line-through">
                                  {"\u09F3"}{formatPrice(product.originalPrice)}
                                </span>
                              ) : null}
                              {product.discountLabel ? (
                                <span className="rounded bg-red-100 px-2 py-1 text-xs font-bold text-red-600">
                                  {product.discountLabel}
                                </span>
                              ) : null}
                            </div>
                            <span className="text-sm font-semibold text-green-600">
                              In Stock
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>

            <Pagination
              currentPage={currentPage}
              totalPages={pagination?.last_page || 0}
              hasPrev={Boolean(pagination?.prev_page_url)}
              hasNext={Boolean(pagination?.next_page_url)}
              onPageChange={handlePageChange}
            />
          </>
        )}
      </div>
    </div>
  );
}
