import React from "react";
import FeaturedDealsPage from "@/components/product/FeaturedDealsPage";

const normalizePage = (value) => {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

export const metadata = {
  title: "Featured Deals | Adorzotno",
  description:
    "Explore the latest featured deals and highlighted offers available at Adorzotno.",
  keywords: "featured deals, offers, discounts, adorzotno",
  openGraph: {
    title: "Featured Deals | Adorzotno",
    description:
      "Browse all featured deals and highlighted offers available right now.",
    type: "website",
  },
};

export default async function page({ searchParams }) {
  const resolvedSearchParams = await searchParams;
  const initialPage = normalizePage(resolvedSearchParams?.page);

  return (
    <div>
      <FeaturedDealsPage
        key={`featured-deals-${initialPage}`}
        initialPage={initialPage}
      />
    </div>
  );
}
