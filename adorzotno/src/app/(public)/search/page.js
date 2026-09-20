import React from "react";
import SearchResultsPage from "@/components/product/SearchResultsPage";

const normalizePage = (value) => {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

export const metadata = {
  title: "Search Products | Adorzotno",
  description: "Search products available at Adorzotno.",
  keywords: "search products, pharmacy search, adorzotno",
  openGraph: {
    title: "Search Products | Adorzotno",
    description: "Find products and browse matching results at Adorzotno.",
    type: "website",
  },
};

export default async function page({ searchParams }) {
  const resolvedSearchParams = await searchParams;
  const initialPage = normalizePage(resolvedSearchParams?.page);
  const initialQuery = resolvedSearchParams?.q || "";

  return (
    <div>
      <SearchResultsPage
        key={`search-${initialQuery}-${initialPage}`}
        initialQuery={initialQuery}
        initialPage={initialPage}
      />
    </div>
  );
}
