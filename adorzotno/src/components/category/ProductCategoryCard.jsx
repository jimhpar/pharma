"use client";

import { ChevronRight, Home } from "lucide-react";
import Link from "next/link";
import React, { useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { getImageUrl } from "@/lib/imageHelpers";
import { getProductRating } from "@/lib/getProductRating";
import {
  useGetCategoriesQuery,
  useGetCategoryProductsQuery,
} from "@/redux/features/category/categoryApi";
import FilteredProductCard from "./FilteredProductCard";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export default function ProductCategoryCard({ slug, initialPage = 1 }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [currentPage, setCurrentPage] = useState(initialPage);
  const { data: apiCategories = [], isLoading: isCategoriesLoading } =
    useGetCategoriesQuery();

  const flattenedCategories = useMemo(() => {
    return (apiCategories || []).flatMap((category) => [
      category,
      ...(category.children || []),
    ]);
  }, [apiCategories]);

  const selectedCategory = useMemo(() => {
    return flattenedCategories.find((category) => category.slug === slug);
  }, [flattenedCategories, slug]);

  const {
    data: categoryProductsResponse = {},
    isLoading: isProductsLoading,
    isFetching: isProductsFetching,
  } = useGetCategoryProductsQuery(
    {
      categorySlug: slug,
      page: currentPage,
      perPage: 20,
      sortBy: "created_at",
      sortOrder: "desc",
    },
    {
      skip: !slug,
    },
  );

  const categoryData = categoryProductsResponse?.category || selectedCategory;
  const productPagination = categoryProductsResponse?.products || null;

  const filteredProducts = useMemo(() => {
    const apiProducts = productPagination?.data || [];

    return apiProducts.map((product) => {
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

      const imagePath = product?.thumbnail_image;

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
        images: [
          imagePath
            ? getImageUrl(imagePath)
            : "/images/no-image-available.png",
        ],
        brand: product?.brand?.name || "",
        category: product?.category?.name || categoryData?.name || "",
      };
    });
  }, [categoryData?.name, productPagination?.data]);

  const isLoading =
    isCategoriesLoading || isProductsLoading || isProductsFetching;

  const handlePageChange = (page) => {
    if (!productPagination?.last_page) {
      return;
    }

    const nextPage = Math.min(Math.max(page, 1), productPagination.last_page);

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
          <div className="space-x-1">
            <span className="font-semibold">
              {categoryData?.name || "Category"}
            </span>
            <span className="text-sm text-gray-100">
              ({productPagination?.total ?? filteredProducts.length} items)
            </span>
          </div>
        </div>
      </div>

      <FilteredProductCard
        filteredProducts={filteredProducts}
        isLoading={isLoading}
        pagination={productPagination}
        currentPage={currentPage}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
