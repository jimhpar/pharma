'use client';

import { ChevronLeft, ChevronRight } from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';
import React, { useMemo, useState } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { useGetBrandsQuery } from '@/redux/features/brand/brandApi';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { getImageUrl } from '@/lib/imageHelpers';

export default function BrandSection() {
  const [swiperInstance, setSwiperInstance] = useState(null);
  const { data: brands = [], isLoading } = useGetBrandsQuery();


  const activeBrands = useMemo(() => {
    return brands
      .filter((brand) => brand?.status === 'active')
      .sort((a, b) => (a?.sort_order ?? 9999) - (b?.sort_order ?? 9999));
  }, [brands]);

  const handlePrev = () => {
    swiperInstance?.slidePrev();
  };

  const handleNext = () => {
    swiperInstance?.slideNext();
  };

  return (
    <div className="mb-8">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-primary">Shop by Brand</h2>
          <p className="text-sm text-gray-600 md:text-base">
            Trusted brands, quality assured
          </p>
        </div>

        <Link href="/brand">
          <button className="group hidden items-center gap-2 text-sm font-semibold text-primary transition-colors hover:text-primary/80 md:flex md:text-base">
            View All Brands
            <ChevronRight
              size={20}
              className="transition-transform group-hover:translate-x-1"
            />
          </button>
        </Link>
      </div>

      <div className="group/brands relative">
        <button
          onClick={handlePrev}
          className="absolute -left-2 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white opacity-0 transition-all duration-300 hover:bg-primary md:translate-x-3 group-hover/brands:translate-x-0 group-hover/brands:opacity-100"
          aria-label="Previous brands"
        >
          <ChevronLeft size={18} strokeWidth={3} />
        </button>

        <button
          onClick={handleNext}
          className="absolute -right-2 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary/85 text-white opacity-0 transition-all duration-300 hover:bg-primary md:-translate-x-3 group-hover/brands:translate-x-0 group-hover/brands:opacity-100"
          aria-label="Next brands"
        >
          <ChevronRight size={18} strokeWidth={3} />
        </button>

        {isLoading ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
            {Array.from({ length: 6 }).map((_, index) => (
              <div
                key={index}
                className="overflow-hidden rounded-2xl border border-gray-200 bg-white"
              >
                <div className="flex h-[128px] items-center justify-center bg-slate-50 p-6">
                  <div className="h-12 w-24 animate-pulse rounded-xl bg-slate-200" />
                </div>
                <div className="p-4">
                  <div className="mx-auto h-4 w-24 animate-pulse rounded bg-slate-200" />
                </div>
              </div>
            ))}
          </div>
        ) : (
          <Swiper
            modules={[Navigation, Autoplay, Pagination]}
            spaceBetween={16}
            slidesPerView={2}
            onSwiper={setSwiperInstance}
            autoplay={{
              delay: 3000,
              disableOnInteraction: false,
              pauseOnMouseEnter: true,
            }}
            loop={activeBrands.length > 6}
            speed={800}
            breakpoints={{
              375: {
                slidesPerView: 2,
                spaceBetween: 12,
              },
              480: {
                slidesPerView: 2.5,
                spaceBetween: 16,
              },
              640: {
                slidesPerView: 3,
                spaceBetween: 16,
              },
              768: {
                slidesPerView: 4,
                spaceBetween: 16,
              },
              1024: {
                slidesPerView: 5,
                spaceBetween: 20,
              },
              1280: {
                slidesPerView: 6,
                spaceBetween: 20,
              },
              1536: {
                slidesPerView: 7,
                spaceBetween: 20,
              },
            }}
            className="!pb-6"
          >
            {activeBrands.map((brand) => {
              const logoUrl = getImageUrl(brand?.logo);

              return (
                <SwiperSlide key={brand.id}>
                  <Link href={`/brand/${brand.slug}`}>
                    <div className="group relative h-full cursor-pointer overflow-hidden rounded-2xl shadow-md border border-gray-50 bg-white transition-all duration-300 hover:border-primary/20">
                      <div className="relative flex h-36 items-center justify-center overflow-hidden p-6 transition-all duration-300">
                        <div className="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-10">
                          <div
                            className="absolute inset-0"
                            style={{
                              backgroundImage:
                                'radial-gradient(circle, currentColor 1px, transparent 1px)',
                              backgroundSize: '20px 20px',
                            }}
                          />
                        </div>

                        {logoUrl ? (
                          <Image
                            src={logoUrl}
                            alt={brand.name}
                            fill
                            sizes="(max-width: 640px) 40vw, (max-width: 1024px) 20vw, 12vw"
                            className="relative object-contain transition-transform duration-300 group-hover:scale-110 p-4"
                            unoptimized
                          />
                        ) : (
                          <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-xl font-bold text-primary ring-1 ring-slate-200">
                            {brand?.name?.slice(0, 2)?.toUpperCase()}
                          </div>
                        )}
                      </div>

                      <div className="p-2 text-center">
                        <h3 className="mb-1 line-clamp-1 text-sm font-bold text-gray-800 transition-colors group-hover:text-primary md:text-base">
                          {brand.name}
                        </h3>
                      </div>

                      <div className="pointer-events-none absolute inset-0 rounded-2xl border-2 border-primary opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                    </div>
                  </Link>
                </SwiperSlide>
              );
            })}
          </Swiper>
        )}
      </div>

      <div className="mt-6 flex justify-center md:hidden">
        <Link href="/brand">
          <button className="group flex items-center gap-2 text-sm font-semibold text-primary transition-colors hover:text-primary/80">
            View All Brands
            <ChevronRight
              size={18}
              className="transition-transform group-hover:translate-x-1"
            />
          </button>
        </Link>
      </div>

      <style jsx global>{`
        .swiper-slide {
          height: auto;
        }
      `}</style>
    </div>
  );
}
