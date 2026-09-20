"use client";

import Link from "next/link";
import { ChevronLeft, ChevronRight, RotateCcw } from "lucide-react";
import { useMemo } from "react";
import { useSelector } from "react-redux";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import ProductCard from "../../cards/ProductCard";
import { getProductRating } from "@/lib/getProductRating";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetOrdersQuery } from "@/redux/features/order/orderApi";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const mapOrderedItemToCard = (item) => {
  const product = item?.sku?.product;
  const orderedSku = item?.sku || null;

  if (!product?.id || !product?.slug) {
    return null;
  }

  const normalizedProduct = {
    ...product,
    sku: orderedSku ? [{ ...orderedSku }] : product?.sku || [],
  };

  const mappedProduct = mapApiProductToCard(normalizedProduct);
  const currentPrice = toNumber(mappedProduct?.price);
  const orderedUnitPrice = toNumber(item?.unit_price);
  const rating = getProductRating(normalizedProduct);

  return {
    ...mappedProduct,
    productId: product.id,
    skuId: orderedSku?.id || mappedProduct?.skuId || null,
    price: currentPrice > 0 ? currentPrice : orderedUnitPrice,
    image: mappedProduct?.images?.[0] || "",
    rating: rating || mappedProduct?.rating || 0,
    inStock: true,
    in_stock: true,
  };
};

function SkeletonCard() {
  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div className="h-40 animate-pulse bg-slate-100 lg:h-44" />
      <div className="space-y-3 p-3">
        <div className="h-4 w-3/4 animate-pulse rounded bg-slate-100" />
        <div className="h-4 w-1/2 animate-pulse rounded bg-slate-100" />
        <div className="h-5 w-1/3 animate-pulse rounded bg-slate-100" />
      </div>
    </div>
  );
}

export default function HomeOrderAgain() {
  const { isAuthenticated, isHydrated } = useSelector((state) => state.auth);
  const { data: ordersResponse, isLoading } = useGetOrdersQuery(
    {
      page: 1,
      perPage: 10,
    },
    {
      skip: !isHydrated || !isAuthenticated,
      refetchOnMountOrArgChange: true,
    },
  );

  const orderedProducts = useMemo(() => {
    const uniqueProducts = new Map();
    const orders = ordersResponse?.data || [];

    orders.forEach((order) => {
      order?.items?.forEach((item) => {
        const mappedProduct = mapOrderedItemToCard(item);

        if (!mappedProduct || uniqueProducts.has(mappedProduct.id)) {
          return;
        }

        uniqueProducts.set(mappedProduct.id, mappedProduct);
      });
    });

    return Array.from(uniqueProducts.values()).slice(0, 14);
  }, [ordersResponse]);

  if (!isHydrated || !isAuthenticated) {
    return null;
  }

  if (!isLoading && orderedProducts.length === 0) {
    return null;
  }

  return (
    <section className="group/category mb-8 w-full">
      <div className="mb-4 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <h2 className="text-lg font-semibold sm:text-xl md:text-2xl">
            Order Again
          </h2>
          <RotateCcw className="text-primary" size={24} />
        </div>

        <Link
          href="/profile?tab=orders"
          className="flex items-center gap-1 rounded-full border border-primary/30 px-3 py-1.5 text-sm font-semibold text-primary transition-all hover:gap-2 hover:bg-primary/5"
        >
          View Orders <ChevronRight size={16} />
        </Link>
      </div>

      <div className="relative">
        {!isLoading && orderedProducts.length > 0 && (
          <>
            <button
              className="order-again-prev absolute -left-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md opacity-0 transition-all duration-300 group-hover/category:translate-x-0 group-hover/category:opacity-100 md:translate-x-3"
              aria-label="Previous"
            >
              <ChevronLeft size={18} strokeWidth={3} />
            </button>
            <button
              className="order-again-next absolute -right-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md opacity-0 transition-all duration-300 group-hover/category:translate-x-0 group-hover/category:opacity-100 md:-translate-x-3"
              aria-label="Next"
            >
              <ChevronRight size={18} strokeWidth={3} />
            </button>
          </>
        )}

        {isLoading ? (
          <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
            {Array.from({ length: 6 }).map((_, index) => (
              <SkeletonCard key={index} />
            ))}
          </div>
        ) : (
          <Swiper
            modules={[Navigation]}
            navigation={{
              prevEl: ".order-again-prev",
              nextEl: ".order-again-next",
            }}
            spaceBetween={12}
            slidesPerView={2}
            observer
            observeParents
            watchOverflow
            breakpoints={{
              640: { slidesPerView: 3 },
              768: { slidesPerView: 4 },
              1224: { slidesPerView: 5 },
              1400: { slidesPerView: 6 },
            }}
          >
            {orderedProducts.map((product) => (
              <SwiperSlide key={product.id} className="h-auto py-4">
                <ProductCard product={product} />
              </SwiperSlide>
            ))}
          </Swiper>
        )}
      </div>
    </section>
  );
}
