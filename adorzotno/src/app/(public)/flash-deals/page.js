import React from "react";
import FlashDealsPage from "@/components/product/FlashDealsPage";

const normalizePage = (value) => {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

export const metadata = {
  title: "Flash Deals | Adorzotno",
  description:
    "Explore the latest flash deals and limited-time offers available at Adorzotno.",
  keywords: "flash deals, offers, discounts, adorzotno",
  openGraph: {
    title: "Flash Deals | Adorzotno",
    description:
      "Browse all flash deals and limited-time offers available right now.",
    type: "website",
  },
};

export default async function page({ searchParams }) {
  const resolvedSearchParams = await searchParams;
  const initialPage = normalizePage(resolvedSearchParams?.page);

  return (
    <div>
      <FlashDealsPage key={`flash-deals-${initialPage}`} initialPage={initialPage} />
    </div>
  );
}
