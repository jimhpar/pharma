import React from "react";
import ProductCategoryCard from "@/components/category/ProductCategoryCard";

const normalizePage = (value) => {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const readableName = slug
    .replace(/-/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

  return {
    title: `${readableName || "Category"} - Shop Our Collection`,
    description: `Explore our wide range of ${readableName || "products"} and discover quality items from this category.`,
    keywords: `${readableName}, products, shop, online store, buy ${readableName}`,
    openGraph: {
      title: `${readableName || "Category"} - Shop Our Collection`,
      description: `Browse products from ${readableName || "this category"} available now`,
      type: "website",
    },
  };
}

export default async function page({ params, searchParams }) {
  const { slug } = await params;
  const resolvedSearchParams = await searchParams;
  const initialPage = normalizePage(resolvedSearchParams?.page);

  return (
    <div>
      <ProductCategoryCard
        key={`${slug}-${initialPage}`}
        slug={slug}
        initialPage={initialPage}
      />
    </div>
  );
}
