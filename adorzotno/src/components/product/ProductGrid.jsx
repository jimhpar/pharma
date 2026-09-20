'use client';
import {
  ShoppingCart,
  Heart,
  Truck,
  Shield,
  Plus,
  Minus,
  Check,
  Share2,
  ChevronRight,
} from "lucide-react";
import React, { useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import ProductDetailsTab from "./ProductDetailsTab";
import { useCart } from "../../lib/useCart";
import InteractiveRatingStars from "../shared/InteractiveRatingStars";
import {
  useAddToWishlistMutation,
  useGetWishlistQuery,
  useRemoveFromWishlistMutation,
} from "@/redux/features/wishlist/wishlistApi";

const formatPrice = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : "0.00";
};

export default function ProductGrid({
  selectedProduct,
  selectedImage,
  setSelectedImage,
  quantity,
  incrementQuantity,
  decrementQuantity,
  activeTab,
  setActiveTab,
  genericName,
  alternativeBrandProducts = [],
}) {
  const router = useRouter();
  const pathname = usePathname();
  const { addToCart, setIsCartOpen } = useCart();
  const { isAuthenticated, isHydrated } = useSelector((state) => state.auth);
  const [alternativeSort, setAlternativeSort] = useState("relevance");
  const [expandedAlternativesKey, setExpandedAlternativesKey] = useState("");
  const { data: wishlistItems = [] } = useGetWishlistQuery(undefined, {
    skip: !isAuthenticated,
  });
  const [addToWishlist, { isLoading: isAddingToWishlist }] =
    useAddToWishlistMutation();
  const [removeFromWishlist, { isLoading: isRemovingFromWishlist }] =
    useRemoveFromWishlistMutation();

  const sortedAlternativeBrandProducts = useMemo(() => {
    const items = [...alternativeBrandProducts];
    if (alternativeSort === "price-low") {
      return items.sort((a, b) => a.price - b.price);
    }
    if (alternativeSort === "price-high") {
      return items.sort((a, b) => b.price - a.price);
    }
    return items;
  }, [alternativeBrandProducts, alternativeSort]);

  const alternativesViewKey = `${selectedProduct?.id || "product"}-${alternativeSort}-${alternativeBrandProducts.length}`;
  const showAllAlternatives = expandedAlternativesKey === alternativesViewKey;

  const visibleAlternativeBrandProducts = showAllAlternatives
    ? sortedAlternativeBrandProducts
    : sortedAlternativeBrandProducts.slice(0, 8);

  const isWishlisted = useMemo(
    () =>
      wishlistItems.some((item) => Number(item?.sku_id) === Number(selectedProduct?.skuId)),
    [selectedProduct?.skuId, wishlistItems],
  );

  const isWishlistLoading = isAddingToWishlist || isRemovingFromWishlist;

  const handleAddToCart = () => {
    if (!selectedProduct?.id || !selectedProduct?.inStock) return;

    const result = addToCart(selectedProduct, quantity);
    if (result?.success) {
      setIsCartOpen(true);
    }
  };

  const handleWishlistToggle = async () => {
    if (!selectedProduct?.skuId) {
      toast.error("This product is not available for wishlist yet.");
      return;
    }

    if (!isHydrated || !isAuthenticated) {
      router.push(`/?signin=1&redirect=${encodeURIComponent(pathname)}`);
      return;
    }

    try {
      if (isWishlisted) {
        const response = await removeFromWishlist({
          skuId: selectedProduct.skuId,
        }).unwrap();
        toast.success(response?.message || "Removed from wishlist");
      } else {
        const response = await addToWishlist({
          skuId: selectedProduct.skuId,
        }).unwrap();
        toast.success(response?.message || "Added to wishlist");
      }
    } catch (error) {
      toast.error(error?.data?.message || "Wishlist update failed.");
    }
  };

  return (
    <div>
      <div className="mb-4 grid gap-4 xl:grid-cols-7">
        <div className="xl:col-span-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-12">
            <div className="order-2 md:order-1 md:col-span-2">
              <div className="flex gap-3 overflow-x-auto pb-1 md:max-h-[420] md:flex-col md:gap-4 md:overflow-x-hidden md:overflow-y-auto xl:max-h-[500px] [&::-webkit-scrollbar]:h-1 [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:bg-gray-300">
                {selectedProduct?.images?.map((image, index) => (
                  <button
                    key={index}
                    onClick={() => setSelectedImage(index)}
                    className={`shrink-0 overflow-hidden rounded-lg border bg-white transition-all md:w-full ${selectedImage === index
                      ? "border-secondary"
                      : "border-gray-200 hover:border-primary"
                      }`}
                  >
                    <Image
                      src={image}
                      alt={`${selectedProduct?.name} ${index + 1}`}
                      width={300}
                      height={300}
                      className="h-20 w-20 object-contain md:h-24 md:w-full"
                      unoptimized
                    />
                  </button>
                ))}
              </div>
            </div>

            <div className="order-1 md:order-2 md:col-span-10 md:mb-4">
              <div className="relative flex h-[320px] items-center justify-center overflow-hidden rounded-lg border bg-white p-4 md:h-[420px] xl:h-[500px]">
                <Image
                  src={
                    selectedProduct?.images?.[selectedImage] ||
                    "/images/no-image-available.png"
                  }
                  alt={selectedProduct?.name}
                  fill
                  sizes="(max-width: 768px) 100vw, 50vw"
                  className="object-contain p-4"
                  unoptimized
                />
                {selectedProduct?.discountLabel ? (
                  <span className="absolute top-4 right-4 rounded-full bg-red-500 px-3 py-1 text-sm font-bold text-white">
                    {selectedProduct.discountLabel}
                  </span>
                ) : null}
              </div>
            </div>
          </div>

          <div className="mt-6 max-xl:hidden">
            <ProductDetailsTab
              selectedProduct={selectedProduct}
              activeTab={activeTab}
              setActiveTab={setActiveTab}
            />
          </div>
        </div>

        <div className="xl:col-span-3">
          <div className="rounded-lg border bg-white p-4">
            <div className="mb-3 flex flex-wrap gap-2">
              {(selectedProduct?.categories?.length
                ? selectedProduct.categories
                : [{ name: selectedProduct?.category }]).map((category, index) => (
                  <span
                    key={category?.id || category?.slug || `${category?.name}-${index}`}
                    className="inline-block rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-primary"
                  >
                    {category?.name}
                  </span>
                ))}
            </div>

            <h1 className="mb-3 text-3xl font-bold text-gray-800">
              {selectedProduct?.name}
            </h1>

            <div className="mb-4 flex flex-wrap items-center gap-2">
              <InteractiveRatingStars
                value={selectedProduct?.rating}
                readonly
                size={18}
                className="shrink-0"
              />
              <span className="text-gray-600 sm:hidden text-xs">
                ({selectedProduct?.reviews} reviews)
              </span>
              <span className="max-sm:hidden text-xs text-gray-500">
                ({selectedProduct?.reviews} reviews)
              </span>
            </div>

            <div className="mb-6 flex flex-wrap items-center gap-3">
              <span className="text-4xl font-bold text-primary">
                {"\u09F3"}
                {formatPrice(selectedProduct?.price)}
              </span>
              {selectedProduct?.originalPrice ? (
                <span className="text-xl text-gray-400 line-through">
                  {"\u09F3"}
                  {formatPrice(selectedProduct?.originalPrice)}
                </span>
              ) : null}
              {selectedProduct?.discountLabel ? (
                <span className="rounded bg-red-100 px-3 py-1 text-sm font-semibold text-red-600">
                  {selectedProduct.discountLabel}
                </span>
              ) : null}
            </div>

            <div className="mb-6">
              {selectedProduct?.inStock ? (
                <div className="flex items-center gap-2 text-green-600">
                  <Check size={20} />
                  <span className="font-semibold">
                    In Stock ({selectedProduct?.stockCount} available)
                  </span>
                </div>
              ) : (
                <span className="font-semibold text-red-600">Out of Stock</span>
              )}
            </div>

            <p className="mb-6 leading-relaxed text-gray-600">
              {selectedProduct?.description}
            </p>

            <div className="mb-6">
              <label className="mb-2 block text-sm font-semibold text-gray-700">
                Quantity
              </label>
              <div className="flex items-center gap-4">
                <div className="flex items-center rounded-lg border-2 border-gray-300">
                  <button
                    onClick={decrementQuantity}
                    className="rounded-lg p-3 transition hover:bg-gray-100"
                    disabled={quantity <= 1}
                  >
                    <Minus size={20} />
                  </button>
                  <span className="px-6 py-2 text-lg font-semibold">
                    {quantity}
                  </span>
                  <button
                    onClick={incrementQuantity}
                    className="rounded-lg p-3 transition hover:bg-gray-100"
                    disabled={quantity >= selectedProduct?.stockCount}
                  >
                    <Plus size={20} />
                  </button>
                </div>
                <span className="text-gray-600">
                  Total:{" "}
                  <span className="font-bold text-primary">
                    {"\u09F3"}
                    {formatPrice((selectedProduct?.price || 0) * quantity)}
                  </span>
                </span>
              </div>
            </div>

            <div className="mb-6 flex gap-4">
              <button
                onClick={handleAddToCart}
                disabled={!selectedProduct?.inStock}
                className={`max-sm:flex-1 flex items-center justify-center gap-2 rounded-lg py-3 font-semibold text-white transition sm:px-10 ${selectedProduct?.inStock
                  ? "bg-primary hover:bg-secondary"
                  : "cursor-not-allowed bg-gray-300 text-gray-500"
                  }`}
              >
                <ShoppingCart size={20} />
                Add to Cart
              </button>

              <button
                onClick={handleWishlistToggle}
                disabled={isWishlistLoading}
                className={`rounded-lg border-2 p-3 transition ${isWishlisted
                  ? "border-red-500 bg-red-50 text-red-500"
                  : "border-primary text-primary hover:bg-teal-50"
                  } ${isWishlistLoading ? "cursor-not-allowed opacity-60" : ""}`}
              >
                <Heart
                  size={24}
                  className={isWishlisted ? "fill-current" : ""}
                />
              </button>

              <button className="rounded-lg border-2 border-gray-300 p-3 text-gray-600 transition hover:bg-gray-50">
                <Share2 size={24} />
              </button>
            </div>

            <div className="grid grid-cols-1 gap-6 border-t md:grid-cols-2">
              <div className="space-y-2 pt-6 text-sm">
                <div className="flex gap-2">
                  <span className="text-gray-600">SKU:</span>
                  <span className="font-semibold text-gray-800">
                    {selectedProduct?.sku}
                  </span>
                </div>
                <div className="flex gap-2">
                  <span className="text-gray-600">Manufacturer:</span>
                  <span className="font-semibold text-primary">
                    {selectedProduct?.manufacturer}
                  </span>
                </div>
                {genericName ? (
                  <div className="flex gap-2">
                    <span className="text-gray-600">Generic:</span>
                    <span className="font-semibold text-primary">
                      {genericName}
                    </span>
                  </div>
                ) : null}
                <div className="flex gap-2">
                  <span className="text-gray-600">Available Stock:</span>
                  <span
                    className={`font-semibold ${selectedProduct?.inStock ? "text-green-600" : "text-red-600"
                      }`}
                  >
                    {selectedProduct?.stockCount || 0}
                  </span>
                </div>
              </div>

              <div className="space-y-3 pt-6">
                <div className="flex items-center gap-3">
                  <Truck className="text-primary" size={24} />
                  <div>
                    <p className="font-semibold text-gray-800">Free Delivery</p>
                    <p className="text-sm text-gray-600">On orders over {"\u09F3"}50</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Shield className="text-primary" size={24} />
                  <div>
                    <p className="font-semibold text-gray-800">Secure Payment</p>
                    <p className="text-sm text-gray-600">
                      100% secure transactions
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {genericName && sortedAlternativeBrandProducts.length > 0 ? (
              <div>
                <div className="my-6 flex flex-col gap-4 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
                  <h2 className="text-sm text-gray-500">
                    Available products for{" "}
                    <span className="font-semibold text-black">{genericName}</span>
                  </h2>

                  <select
                    value={alternativeSort}
                    onChange={(e) => setAlternativeSort(e.target.value)}
                    className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="relevance">Relevance</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                  </select>
                </div>

                <div className="space-y-3">
                  {visibleAlternativeBrandProducts.map((product) => (
                    <div
                      key={product.id}
                      className="flex items-center gap-3 rounded-xl transition-all hover:border-primary/20 hover:shadow-sm"
                    >
                      <Link
                        href={`/product/${product.slug}`}
                        className="flex min-w-0 flex-1 items-center gap-3"
                      >
                        <div className="relative h-10 w-10 overflow-hidden rounded-lg border bg-gray-50 sm:h-14 sm:w-14">
                          <Image
                            src={product.images[0]}
                            alt={product.name}
                            fill
                            sizes="96px"
                            className="object-cover"
                            unoptimized
                          />
                        </div>

                        <div className="min-w-0 flex-1">
                          <h3 className="line-clamp-2 text-sm font-semibold text-gray-800 transition hover:text-primary sm:text-base">
                            {product.name}
                          </h3>
                          <p className="mt-1 text-xs text-gray-500 sm:text-sm">
                            By {product.manufacturer}
                          </p>
                        </div>
                      </Link>

                      <div className="flex items-end gap-1 sm:text-right">
                        <p className="text-lg font-bold sm:text-xl">
                          {"\u09F3"}
                          {formatPrice(product.price)}
                        </p>
                        {product.originalPrice ? (
                          <p className="text-xs text-gray-400 line-through sm:text-sm">
                            {"\u09F3"}
                            {formatPrice(product.originalPrice)}
                          </p>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
                {sortedAlternativeBrandProducts.length > 8 && !showAllAlternatives ? (
                  <div className="mt-5">
                    <button
                      onClick={() => setExpandedAlternativesKey(alternativesViewKey)}
                      className="flex rounded-lg py-2.5 font-semibold text-primary"
                    >
                      View More <ChevronRight />
                    </button>
                  </div>
                ) : null}
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </div>
  );
}
