'use client';

import { ChevronLeft, ChevronRight } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import React, { useEffect, useMemo, useState } from "react";
import { getImageUrl } from "@/lib/imageHelpers";
import { useGetBannersQuery } from "@/redux/features/banner/bannerApi";

export default function HeroBanner() {
  const [currentSlide, setCurrentSlide] = useState(0);
  const { data: banners = [], isLoading, isFetching } = useGetBannersQuery();

  const heroSlides = useMemo(() => {
    return (banners || [])
      .filter(
        (banner) =>
          banner?.status === "active" && banner?.banner_type === "slider",
      )
      .map((banner) => ({
        id: banner.id,
        title: banner.title || "Hero banner",
        image: getImageUrl(banner.image_path),
        href: banner.banner_url || null,
      }));
  }, [banners]);

  const slideCount = heroSlides.length;
  const activeSlideIndex = slideCount > 0 ? currentSlide % slideCount : 0;

  useEffect(() => {
    if (slideCount <= 1) {
      return undefined;
    }

    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slideCount);
    }, 5000);

    return () => clearInterval(timer);
  }, [slideCount]);

  if (isLoading || isFetching) {
    return (
      <div className="relative mb-6 w-full overflow-hidden rounded-lg">
        <div className="h-[180px] w-full animate-pulse rounded-lg bg-slate-100 sm:h-[260px] lg:h-[380px] xl:h-[500px]" />
      </div>
    );
  }

  if (slideCount === 0) {
    return null;
  }

  const nextSlide = () => {
    if (slideCount <= 1) {
      return;
    }

    setCurrentSlide((prev) => (prev + 1) % slideCount);
  };

  const prevSlide = () => {
    if (slideCount <= 1) {
      return;
    }

    setCurrentSlide((prev) => (prev - 1 + slideCount) % slideCount);
  };

  return (
    <div className="relative mb-6 w-full overflow-hidden rounded-lg">
      {heroSlides.map((slide, index) => {
        const slideImage = (
          <Image
            src={slide.image}
            alt={slide.title}
            width={1500}
            height={500}
            sizes="100vw"
            className="h-auto max-h-[500px] min-h-36 w-full object-cover"
            priority={index === 0}
            unoptimized
          />
        );

        return (
          <div
            key={slide.id}
            className={`transition-opacity duration-1000 ${index === activeSlideIndex
              ? "relative opacity-100"
              : "absolute inset-0 opacity-0"
              }`}
          >
            {slide.href ? (
              <Link href={slide.href} className="block">
                {slideImage}
              </Link>
            ) : (
              slideImage
            )}
          </div>
        );
      })}

      {slideCount > 1 ? (
        <>
          <button
            onClick={prevSlide}
            className="absolute top-1/2 left-1 z-20 -translate-y-1/2 cursor-pointer rounded-xl bg-white/60 p-0.5 shadow-lg transition hover:bg-white sm:left-2 sm:p-2"
          >
            <ChevronLeft className="h-4 w-4 text-gray-800 sm:h-6 sm:w-6" />
          </button>

          <button
            onClick={nextSlide}
            className="absolute top-1/2 right-1 z-20 -translate-y-1/2 cursor-pointer rounded-xl bg-white/60 p-0.5 shadow-lg transition hover:bg-white sm:right-2 sm:p-2"
          >
            <ChevronRight className="h-4 w-4 text-gray-800 sm:h-6 sm:w-6" />
          </button>

          <div className="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 gap-2">
            {heroSlides.map((_, index) => (
              <button
                key={index}
                onClick={() => setCurrentSlide(index)}
                className={`h-2 cursor-pointer rounded-full transition-all duration-300 ${index === activeSlideIndex
                  ? "w-8 bg-white"
                  : "w-2 bg-white/50"
                  }`}
              />
            ))}
          </div>
        </>
      ) : null}
    </div>
  );
}
