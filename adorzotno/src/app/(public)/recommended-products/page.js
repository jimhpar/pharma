import React from "react";
import RecommendedProductsPage from "@/components/product/RecommendedProductsPage";

export const metadata = {
  title: "Recommended Products | Adorzotno",
  description:
    "Explore recommended products picked for shoppers on Adorzotno.",
  keywords: "recommended products, suggested products, adorzotno",
  openGraph: {
    title: "Recommended Products | Adorzotno",
    description: "Browse recommended products available right now.",
    type: "website",
  },
};

export default function page() {
  return (
    <div>
      <RecommendedProductsPage />
    </div>
  );
}
