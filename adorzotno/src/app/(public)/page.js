import React from 'react'
import HeroBanner from "@/components/modules/home/HeroBanner";
import HomeFeatures from "@/components/modules/home/HomeFeatures";
import FeaturedDeals from "@/components/modules/home/FeaturedDeals";
import HomeCategoryProducts from "@/components/modules/home/HomeCategoryProducts";
import Newsletter from "@/components/modules/shared/Newsletter";
import FlashSaleSection from "@/components/modules/home/FlashSaleSection";
import FlashDealSection from "@/components/modules/home/FlashDealSection";
import FAQSection from "@/components/modules/shared/FAQSection";
import BrandSection from "@/components/modules/shared/BrandSection";
import RecommendedSection from "@/components/modules/home/RecommendedSection";
import OccasionalSection from "@/components/modules/home/OccasionalSection";
import HomeOrderAgain from "@/components/modules/home/HomeOrderAgain";
import HomePopularCategories from '@/components/modules/home/HomePopularCategories';

export default function page() {
  return (
    <div>
      <div className="min-h-screen">
        {/* Main Content */}
        <main className="">
          {/* Hero Banner */}
          <HeroBanner />

          {/* Features */}
          {/* <HomeFeatures /> */}

          {/* Categories Section */}
          <HomePopularCategories />

          <HomeOrderAgain />

          <FlashDealSection />

          <RecommendedSection />

          <HomeCategoryProducts />


          {/* Featured Deals */}
          <FeaturedDeals />

          <OccasionalSection />

          <BrandSection />

          {/* Faq Section */}
          <FAQSection />

          {/* Newsletter Section */}
          <Newsletter />

        </main >
      </div >
    </div>
  )
}
