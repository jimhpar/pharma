"use client";

import Image from "next/image";
import Link from "next/link";
import React, { useMemo } from "react";
import { getImageUrl } from "@/lib/imageHelpers";
import { useGetBannersQuery } from "@/redux/features/banner/bannerApi";

const BannerImg = ({ banner, sizes = "50vw", className = "" }) => {
  return (
    <Link
      href={banner.link}
      className={`group relative block h-full overflow-hidden rounded-xl ${className}`}
    >
      <Image
        src={banner.image}
        alt={banner.title}
        fill
        sizes={sizes}
        className="object-fill transition-transform duration-500 group-hover:scale-105"
        unoptimized
      />
    </Link>
  );
};

export default function HomeCategoryBannerSectionOne() {
  const { data: banners = [] } = useGetBannersQuery();

  const sectionBanners = useMemo(() => {
    const filteredBanners = banners.filter(
      (banner) =>
        banner?.status === "active" &&
        banner?.banner_type === "homepage_middle_1",
    );

    const toBannerItem = (banner, fallbackId) =>
    ({
      id: banner?.id || fallbackId,
      image: getImageUrl(banner?.image_path),
      title: banner?.title || "Homepage banner",
      link: banner?.banner_url || "#",
    });

    return {
      smallTop: toBannerItem(
        filteredBanners.find((banner) => banner?.position === "small_top"),
        "homepage-middle-1-small-top",
      ),
      smallBottom: toBannerItem(
        filteredBanners.find((banner) => banner?.position === "small_bottom"),
        "homepage-middle-1-small-bottom",
      ),
      large: toBannerItem(
        filteredBanners.find((banner) => banner?.position === "large"),
        "homepage-middle-1-large",
      ),
    };
  }, [banners]);

  return (
    <div
      className="mb-8 grid h-[250px] grid-rows-[1.6fr_1fr] gap-3 sm:h-[340px] md:h-[420px] lg:h-[380px] lg:grid-cols-[1fr_2fr] lg:grid-rows-1 xl:h-[420px]"
    >
      <div className="order-2 grid h-full min-h-0 grid-cols-2 gap-3 lg:order-1 lg:grid-cols-1 lg:grid-rows-2">
        <div className="min-h-0">
          <BannerImg banner={sectionBanners.smallTop} sizes="25vw" className="h-full" />
        </div>
        <div className="min-h-0">
          <BannerImg banner={sectionBanners.smallBottom} sizes="25vw" className="h-full" />
        </div>
      </div>
      <div className="order-1 h-full min-h-0 overflow-hidden rounded-xl lg:order-2">
        <BannerImg banner={sectionBanners.large} sizes="50vw" className="h-full" />
      </div>
    </div>
  );
}
