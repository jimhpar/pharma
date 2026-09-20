import { ChevronLeft, ChevronRight } from "lucide-react";
import React, { useMemo, useState } from "react";
import ProductCard from "../../cards/ProductCard";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import { useGetCategoryProductsQuery } from "@/redux/features/category/categoryApi";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";

// Import Swiper styles
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import Link from "next/link";

export default function CategoryProduct({ category, products = [] }) {
  const [swiperInstance, setSwiperInstance] = useState(null);
  const [isBeginning, setIsBeginning] = useState(true);
  const [isEnd, setIsEnd] = useState(false);
  const [isLocked, setIsLocked] = useState(false);
  const categoryName = category?.name || "";
  const categorySlug = category?.slug || "";

  const {
    data: categoryProductsResponse = {},
    isLoading,
    isFetching,
  } = useGetCategoryProductsQuery(
    {
      categorySlug,
      perPage: 12,
      sortBy: "created_at",
      sortOrder: "desc",
    },
    {
      skip: !categorySlug,
    },
  );

  const resolvedProducts = useMemo(() => {
    if (products.length > 0) {
      return products;
    }

    return (categoryProductsResponse?.products?.data || []).map(mapApiProductToCard);
  }, [categoryProductsResponse?.products?.data, products]);

  const handleSlideChange = (swiper) => {
    setIsBeginning(swiper.isBeginning);
    setIsEnd(swiper.isEnd);
    setIsLocked(swiper.isLocked);
  };

  const handlePrev = () => {
    if (swiperInstance) {
      swiperInstance.slidePrev();
    }
  };

  const handleNext = () => {
    if (swiperInstance) {
      swiperInstance.slideNext();
    }
  };

  return (
    <div className={`group/category mb-8`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-2">
          <h2 className="text-lg sm:text-xl md:text-2xl font-semibold">{categoryName}</h2>
        </div>
        <Link
          href={`/category/${categorySlug}`}
        >
          <div className="text-primary font-semibold flex items-center gap-1 hover:gap-2 transition-all text-sm border border-primary/30 px-3 py-1.5 rounded-full hover:bg-primary/5">
            View all <ChevronRight size={16} />
          </div>
        </Link>
      </div>

      {isLoading || isFetching ? (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
          {Array.from({ length: 6 }).map((_, index) => (
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
      ) : resolvedProducts.length === 0 ? (
        <div className="rounded-lg border border-dashed border-slate-200 bg-white py-8 text-center text-gray-500">
          No products available in this category right now.
        </div>
      ) : (
        <>

          <div className="relative">
            {/* Previous Button */}
            {!isLocked && (
              <button
                onClick={handlePrev}
                disabled={isBeginning}
                className={`absolute -left-2 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md transition-all duration-300 ${isBeginning
                  ? "cursor-not-allowed opacity-0 md:-translate-x-3"
                  : "opacity-0 md:translate-x-3 group-hover/category:translate-x-0 group-hover/category:opacity-100"
                  }`}
                aria-label="Previous products"
              >
                <ChevronLeft size={18} strokeWidth={3} />
              </button>
            )}

            {/* Swiper Container */}
            <Swiper
              modules={[Navigation, Pagination, Autoplay]}
              spaceBetween={14}
              slidesPerView={2}
              onSwiper={(swiper) => {
                setSwiperInstance(swiper);
                setIsLocked(swiper.isLocked); // ✅ initial check
              }}
              onSlideChange={handleSlideChange}
              watchOverflow={true}
              speed={1200}
              breakpoints={{
                // Mobile - Medium (375px)
                375: {
                  slidesPerView: 2,
                  spaceBetween: 12,
                },
                // Mobile - Large (425px)
                425: {
                  slidesPerView: 2,
                  spaceBetween: 12,
                },
                // Mobile - Large (480px)
                480: {
                  slidesPerView: 2.2,
                  spaceBetween: 12,
                },
                // Tablet - Medium (560px)
                560: {
                  slidesPerView: 2.4,
                  spaceBetween: 12,
                },
                // Tablet - Medium (768px)
                768: {
                  slidesPerView: 3,
                  spaceBetween: 12,
                },
                // Tablet - Medium (840px)
                840: {
                  slidesPerView: 3.5,
                  spaceBetween: 12,
                },
                // Laptop - Small (1024px)
                1024: {
                  slidesPerView: 2.5,
                  spaceBetween: 12,
                },
                // Laptop - Medium (1280px)
                1280: {
                  slidesPerView: 5,
                  spaceBetween: 12,
                },
                // Desktop (1536px)
                1536: {
                  slidesPerView: 6,
                  spaceBetween: 12,
                },
              }}
              className="!pb-4"
            >
              {resolvedProducts.map((product) => (
                <SwiperSlide key={product.id} className="h-auto">
                  <ProductCard product={product} />
                </SwiperSlide>
              ))}
            </Swiper>

            {/* Next Button */}
            {!isLocked && (
              <button
                onClick={handleNext}
                disabled={isEnd}
                className={`absolute -right-2 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md transition-all duration-300 ${isEnd
                  ? "cursor-not-allowed opacity-0 md:translate-x-3"
                  : "opacity-0 md:-translate-x-3 group-hover/category:translate-x-0 group-hover/category:opacity-100"
                  }`}
                aria-label="Next products"
              >
                <ChevronRight size={18} strokeWidth={3} />
              </button>
            )}
          </div>

          {/* Mobile Navigation Dots */}
          <div className="flex md:hidden justify-center gap-2 mt-4">
            {Array.from({ length: Math.ceil(resolvedProducts.length / 2) }).map(
              (_, index) => (
                <button
                  key={index}
                  onClick={() => swiperInstance?.slideTo(index * 2)}
                  className={`h-2 rounded-full transition-all duration-300 ${Math.floor((swiperInstance?.activeIndex || 0) / 2) === index
                    ? "w-8 bg-primary"
                    : "w-2 bg-gray-300 hover:bg-gray-400"
                    }`}
                  aria-label={`Go to slide ${index + 1}`}
                />
              ),
            )}
          </div>

          {/* Styles */}
          <style jsx global>{`
        .swiper-slide {
          display: flex;
          height: auto;
        }

        .swiper-slide > * {
          width: 100%;
        }
      `}</style>
        </>
      )}
    </div>
  );
}
