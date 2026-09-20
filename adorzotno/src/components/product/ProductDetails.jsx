'use client';

import { ChevronRight, Home } from "lucide-react";
import Link from "next/link";
import { useParams } from "next/navigation";
import React, { useMemo, useState } from "react";
import { getImageUrl } from "@/lib/imageHelpers";
import { getProductRating } from "@/lib/getProductRating";
import { mapApiProductToCard } from "@/lib/mapApiProductToCard";
import { useGetBrandProductsQuery } from "@/redux/features/brand/brandApi";
import {
  useGetProductQuery,
  useGetProductsByGenericNameQuery,
  useGetRelatedProductsQuery,
  useGetTrendingProductsQuery,
} from "@/redux/features/product/productApi";
import products from "../../../public/data/data";
import flashDeals from "../../../public/data/flashDeals";
import ProductCarouselSection from "./ProductCarouselSection";
import ProductDetailsTab from "./ProductDetailsTab";
import ProductGrid from "./ProductGrid";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const stripHtml = (value = "") =>
  value
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/\s+/g, " ")
    .trim();

const buildProductImages = (product) => {
  const thumbnailImage = product?.thumbnail_image
    ? [getImageUrl(product.thumbnail_image)]
    : [];
  const productImages = (product?.product_images || [])
    .map((image) => image?.image_path)
    .filter(Boolean)
    .map((path) => getImageUrl(path));
  const combinedImages = Array.from(
    new Set([...thumbnailImage, ...productImages]),
  );

  return combinedImages.length > 0
    ? combinedImages
    : ["/images/no-image-available.png"];
};

const buildWarnings = (warnings = []) => {
  const apiWarnings = warnings
    .map((warning) => warning?.warning || warning?.title || warning?.message)
    .filter(Boolean);

  if (apiWarnings.length > 0) {
    return apiWarnings;
  }

  return [
    "Use only as directed.",
    "Keep out of reach of children.",
    "Consult a doctor if symptoms persist.",
  ];
};

const buildFeatures = (product) => {
  const features = [
    product?.dosage_form,
    product?.strength,
    product?.brand?.name ? `Brand: ${product.brand.name}` : null,
    product?.category?.name ? `Category: ${product.category.name}` : null,
  ].filter(Boolean);

  if (features.length > 0) {
    return features;
  }

  return [
    "Quality-assured product",
    "Suitable for everyday care",
    "Carefully sourced from trusted suppliers",
  ];
};

const getPricing = (product) => {
  const primarySku = product?.sku?.[0] || {};
  const skuSellingPrice = toNumber(primarySku?.selling_price);
  const productSellingPrice = toNumber(product?.selling_price);
  const basePrice = skuSellingPrice || productSellingPrice;
  const discountType = product?.default_discount_type;
  const discountValue = toNumber(product?.default_discount_value);

  let originalPrice = basePrice > 0 ? basePrice : null;
  let price = basePrice;
  let discountAmount = 0;
  let discountLabel = null;

  if (basePrice > 0 && discountValue > 0) {
    if (discountType === "amount") {
      discountAmount = Math.min(discountValue, basePrice);
      price = Math.max(basePrice - discountAmount, 0);
      discountLabel = `\u09F3${discountAmount.toFixed(0)} off`;
    } else if (discountType === "percent" && discountValue < 100) {
      discountAmount = (basePrice * discountValue) / 100;
      price = Math.max(basePrice - discountAmount, 0);
      discountLabel = `${Math.round(discountValue)}% off`;
    }
  }

  return {
    price,
    originalPrice: originalPrice && originalPrice > price ? originalPrice : null,
    discountAmount,
    discountLabel,
  };
};

const getStockInfo = (product) => {
  const primarySku = product?.sku?.[0] || {};
  const productAvailableStock = toNumber(product?.available_stock);
  const skuAvailableStock = toNumber(primarySku?.available_stock);
  const productStockAvailableQuantity = toNumber(
    product?.stock?.available_quantity,
  );
  const skuStockAvailableQuantity = toNumber(
    primarySku?.stock?.available_quantity,
  );

  const stockCount =
    productAvailableStock ||
    skuAvailableStock ||
    productStockAvailableQuantity ||
    skuStockAvailableQuantity;

  const inStock =
    Boolean(product?.in_stock) ||
    Boolean(primarySku?.in_stock) ||
    Boolean(product?.stock?.in_stock) ||
    Boolean(primarySku?.stock?.in_stock) ||
    stockCount > 0;

  return {
    inStock,
    stockCount,
  };
};

const allProducts = [...products, ...flashDeals];

export default function ProductDetails() {
  const { id } = useParams();
  const [quantity, setQuantity] = useState(1);
  const [selectedImage, setSelectedImage] = useState(0);
  const [activeTab, setActiveTab] = useState("description");

  const {
    data: product,
    isLoading,
    isFetching,
  } = useGetProductQuery(
    { productSlug: id },
    { skip: !id },
  );

  const selectedBrandSlug = product?.brand?.slug;

  const { data: sameBrandProductsResponse = {} } = useGetBrandProductsQuery(
    {
      brandSlug: selectedBrandSlug,
      perPage: 20,
      sortBy: "most_popular",
    },
    {
      skip: !selectedBrandSlug,
    },
  );

  const { data: trendingProductsResponse = [] } = useGetTrendingProductsQuery({
    limit: 10,
  });

  const { data: relatedProductsResponse = [] } = useGetRelatedProductsQuery(
    { productSlug: id },
    { skip: !id },
  );

  const selectedProduct = useMemo(() => {
    if (!product) {
      return null;
    }

    const primarySku = product?.sku?.[0] || {};
    const categories = (product?.categories || []).map((category) => ({
      id: category.id,
      name: category.name,
      slug: category.slug,
    }));
    const pricing = getPricing(product);
    const stockInfo = getStockInfo(product);

    return {
      id: product.id,
      skuId: primarySku?.id || null,
      slug: product.slug,
      name: product.name,
      productType: product.product_type,
      category: product?.category?.name || categories?.[0]?.name || "",
      categories,
      rating: getProductRating(product),
      reviews: product?.reviews?.length || 0,
      inStock: stockInfo.inStock,
      stockCount: stockInfo.stockCount,
      sku: primarySku?.sku_code || "N/A",
      manufacturer:
        product?.manufacturer_name || product?.brand?.name || "Healthcare",
      generic: product?.generic_name || "",
      images: buildProductImages(product),
      description:
        stripHtml(product?.short_description) ||
        stripHtml(product?.long_description) ||
        "Product description will be updated soon.",
      shortDescriptionHtml:
        product?.short_description ||
        "<p>Product information will be updated soon.</p>",
      longDescriptionHtml:
        product?.long_description ||
        "<p>Detailed product information will be updated soon.</p>",
      features: buildFeatures(product),
      dosage:
        primarySku?.dosage_details ||
        "Use as directed by your physician or follow the label instructions.",
      warnings: buildWarnings(product?.product_warnings),
      reviewItems: product?.reviews || [],
      brand: product?.brand || null,
      ...pricing,
    };
  }, [product]);

  const genericName = selectedProduct?.generic?.trim() || "";

  const { data: genericProductsResponse = {} } = useGetProductsByGenericNameQuery(
    {
      genericName,
      perPage: 20,
    },
    {
      skip: !genericName,
    },
  );

  const productsExcludingSelected = useMemo(
    () =>
      allProducts.filter((item) => {
        const selectedSlug = selectedProduct?.slug?.toLowerCase();
        const itemSlug = item?.slug?.toLowerCase();

        if (selectedSlug && itemSlug) {
          return itemSlug !== selectedSlug;
        }

        return item?.name !== selectedProduct?.name;
      }),
    [selectedProduct?.name, selectedProduct?.slug],
  );

  const youMayAlsoLikeProducts = useMemo(
    () =>
      trendingProductsResponse
        .filter((trendingProduct) => trendingProduct?.id !== selectedProduct?.id)
        .map(mapApiProductToCard)
        .slice(0, 10),
    [selectedProduct?.id, trendingProductsResponse],
  );

  const manufacturerProducts = useMemo(
    () =>
    ((sameBrandProductsResponse?.products?.data || [])
      .filter((brandProduct) => brandProduct?.id !== selectedProduct?.id)
      .map((brandProduct) => {
        const pricing = getPricing(brandProduct);

        return {
          id: brandProduct.id,
          slug: brandProduct.slug,
          name: brandProduct.name,
          rating: getProductRating(brandProduct),
          price: pricing.price,
          originalPrice: pricing.originalPrice,
          discountAmount: pricing.discountAmount > 0 ? pricing.discountAmount : null,
          discountLabel: pricing.discountLabel,
          images: buildProductImages(brandProduct),
          brand: brandProduct?.brand?.name || "",
          category: brandProduct?.category?.name || "",
        };
      })
      .slice(0, 12)),
    [sameBrandProductsResponse?.products?.data, selectedProduct?.id],
  );

  const fallbackManufacturerProducts = useMemo(
    () =>
      productsExcludingSelected
        .filter((product) => product?.rating >= 4.5)
        .slice(4, 15),
    [productsExcludingSelected],
  );

  const frequentlyBoughtTogetherProducts = useMemo(
    () =>
      relatedProductsResponse
        .filter((relatedProduct) => relatedProduct?.id !== selectedProduct?.id)
        .map(mapApiProductToCard)
        .slice(0, 12),
    [relatedProductsResponse, selectedProduct?.id],
  );

  const alternativeBrandProducts = useMemo(
    () =>
      (genericProductsResponse?.data || [])
        .filter((genericProduct) => genericProduct?.id !== selectedProduct?.id)
        .map((genericProduct) => {
          const pricing = getPricing(genericProduct);

          return {
            id: genericProduct.id,
            slug: genericProduct.slug,
            name: genericProduct.name,
            manufacturer:
              genericProduct?.manufacturer_name ||
              genericProduct?.brand?.name ||
              "Healthcare",
            price: pricing.price,
            originalPrice: pricing.originalPrice,
            images: buildProductImages(genericProduct),
          };
        }),
    [genericProductsResponse?.data, selectedProduct?.id],
  );

  const previouslyBrowsedProducts = useMemo(
    () => [...productsExcludingSelected].reverse().slice(0, 12),
    [productsExcludingSelected],
  );

  const incrementQuantity = () => {
    if (quantity < (selectedProduct?.stockCount || 0)) {
      setQuantity((prev) => prev + 1);
    }
  };

  const decrementQuantity = () => {
    if (quantity > 1) {
      setQuantity((prev) => prev - 1);
    }
  };

  const isPageLoading = isLoading || isFetching;

  if (isPageLoading) {
    return (
      <div className="space-y-6">
        <div className="h-5 w-64 animate-pulse rounded bg-slate-100" />
        <div className="grid gap-4 xl:grid-cols-7">
          <div className="xl:col-span-4">
            <div className="h-[420px] animate-pulse rounded-lg bg-slate-100 xl:h-[500px]" />
          </div>
          <div className="xl:col-span-3 space-y-4 rounded-lg border bg-white p-4">
            <div className="h-6 w-24 animate-pulse rounded bg-slate-100" />
            <div className="h-10 w-3/4 animate-pulse rounded bg-slate-100" />
            <div className="h-8 w-40 animate-pulse rounded bg-slate-100" />
            <div className="h-20 w-full animate-pulse rounded bg-slate-100" />
          </div>
        </div>
      </div>
    );
  }

  if (!selectedProduct) {
    return (
      <div className="rounded-lg border bg-white p-10 text-center">
        <h2 className="text-2xl font-bold text-gray-800">Product Not Found</h2>
        <p className="mt-2 text-gray-600">
          We could not load this product right now.
        </p>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6 flex items-center gap-2 text-sm text-gray-600">
        <Link href="/">
          <button className="flex cursor-pointer items-center gap-1 hover:text-primary">
            <Home size={16} /> Home
          </button>
        </Link>
        <ChevronRight size={16} />
        <Link href={`/category/${selectedProduct?.categories?.[0]?.slug || ""}`}>
          <button className="hover:text-primary">
            {selectedProduct?.categories?.[0]?.name || selectedProduct?.category}
          </button>
        </Link>
        <ChevronRight size={16} />
        <span className="text-gray-800">{selectedProduct?.name}</span>
      </div>

      <ProductGrid
        selectedProduct={selectedProduct}
        selectedImage={selectedImage}
        setSelectedImage={setSelectedImage}
        quantity={quantity}
        incrementQuantity={incrementQuantity}
        decrementQuantity={decrementQuantity}
        activeTab={activeTab}
        setActiveTab={setActiveTab}
        genericName={genericName}
        alternativeBrandProducts={alternativeBrandProducts}
      />

      <div className="xl:hidden">
        <ProductDetailsTab
          selectedProduct={selectedProduct}
          activeTab={activeTab}
          setActiveTab={setActiveTab}
        />
      </div>

      <div className="bg-red-50">
        <ProductCarouselSection
          relatedProducts={youMayAlsoLikeProducts}
          title="You May Also Like"
          navKey="you-may-also-like"
          viewAllHref="/recommended-products"
        />
      </div>

      <ProductCarouselSection
        relatedProducts={
          manufacturerProducts.length > 0
            ? manufacturerProducts
            : fallbackManufacturerProducts
        }
        title={`More from ${selectedProduct?.manufacturer || "Incepta Pharmaceuticals Ltd."}`}
        navKey="more-from-manufacturer"
        viewAllHref={
          selectedProduct?.brand?.slug ? `/brand/${selectedProduct.brand.slug}` : "#"
        }
      />

      <div className="bg-sky-50">
        <ProductCarouselSection
          relatedProducts={frequentlyBoughtTogetherProducts}
          title="Frequently Bought Together"
          navKey="frequently-bought-together"
        />
      </div>

      <ProductCarouselSection
        relatedProducts={previouslyBrowsedProducts}
        title="Previously Browsed Items"
        navKey="previously-browsed-items"
      />
    </div>
  );
}
