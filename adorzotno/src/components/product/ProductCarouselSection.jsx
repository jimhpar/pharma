"use client";
import { ChevronLeft, ChevronRight } from "lucide-react";
import Link from "next/link";
import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper/modules";
import ProductCard from "../cards/ProductCard";

import "swiper/css";
import "swiper/css/navigation";

export default function ProductCarouselSection({
  relatedProducts,
  title = "You May Also Like",
  navKey = "you-may-also-like",
  viewAllHref = "#",
}) {
  const prevClass = `${navKey}-prev`;
  const nextClass = `${navKey}-next`;

  return (
    <div className="group/category w-full p-6">
      <div className="mb-4 flex items-center justify-between gap-4">
        <h2 className="text-xl md:text-2xl font-semibold">{title}</h2>
        <Link
          href={viewAllHref}
          className="text-primary font-semibold flex items-center gap-1 hover:gap-2 transition-all text-sm border border-primary/30 px-3 py-1.5 rounded-full hover:bg-primary/5"
        >
          View All <ChevronRight size={16} />
        </Link>
      </div>

      <div className="relative w-full max-w-full">
        <button
          className={`${prevClass} absolute -left-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md opacity-0 transition-all duration-300 group-hover/category:translate-x-0 group-hover/category:opacity-100 md:translate-x-3`}
          aria-label="Previous"
        >
          <ChevronLeft size={18} strokeWidth={3} />
        </button>
        <button
          className={`${nextClass} absolute -right-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white shadow-md opacity-0 transition-all duration-300 group-hover/category:translate-x-0 group-hover/category:opacity-100 md:-translate-x-3`}
          aria-label="Next"
        >
          <ChevronRight size={18} strokeWidth={3} />
        </button>

        <Swiper
          modules={[Navigation]}
          navigation={{
            prevEl: `.${prevClass}`,
            nextEl: `.${nextClass}`,
          }}
          spaceBetween={14}
          slidesPerView={2}
          speed={2000}
          observer
          observeParents
          watchOverflow
          breakpoints={{
            380: { slidesPerView: 2 },
            540: { slidesPerView: 2.5 },
            640: { slidesPerView: 3 },
            768: { slidesPerView: 4 },
            1380: { slidesPerView: 5 },
            1536: {
              slidesPerView: 6,
              spaceBetween: 10,
            },
          }}
          className="w-full max-w-full"
        >
          {relatedProducts?.map((item) => (
            <SwiperSlide key={item?.id} className="h-auto py-4">
              <ProductCard product={item} />
            </SwiperSlide>
          ))}
        </Swiper>
      </div>
    </div>
  );
}
