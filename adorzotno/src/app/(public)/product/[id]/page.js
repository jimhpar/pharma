import React from "react";
import ProductDetails from "@/components/product/ProductDetails";

const getReadableName = (value = "") =>
  value
    .replace(/[_-]+/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

export async function generateMetadata({ params }) {
  const { id } = await params;
  const readableName = getReadableName(id);

  return {
    title: `${readableName || "Product"} - Shop Our Collection`,
    description: `Explore details, pricing, and product information for ${readableName || "this product"}.`,
    keywords: `${readableName}, products, shop, online store, buy ${readableName}`,
    openGraph: {
      title: `${readableName || "Product"} - Shop Our Collection`,
      description: `Browse ${readableName || "this product"} available now`,
      type: "website",
    },
  };
}

export default async function page({ params }) {
  const { id } = await params;

  return (
    <div>
      <ProductDetails key={id} />
    </div>
  );
}
