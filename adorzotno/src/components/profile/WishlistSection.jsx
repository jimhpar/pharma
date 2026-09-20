"use client";

import { Heart, ShoppingBag, Trash2 } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/imageHelpers";
import {
  useGetWishlistQuery,
  useRemoveFromWishlistMutation,
} from "@/redux/features/wishlist/wishlistApi";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const formatPrice = (value) => toNumber(value).toFixed(2);

const getWishlistPrice = (sku, product) => {
  const skuSellingPrice = toNumber(sku?.selling_price);
  const productSellingPrice = toNumber(product?.selling_price);
  const basePrice = skuSellingPrice || productSellingPrice;
  const discountType = product?.default_discount_type;
  const discountValue = toNumber(product?.default_discount_value);

  let price = basePrice;
  let originalPrice = basePrice;

  if (basePrice > 0 && discountValue > 0) {
    if (discountType === "amount") {
      price = Math.max(basePrice - discountValue, 0);
    } else if (discountType === "percent" && discountValue < 100) {
      price = Math.max(basePrice - (basePrice * discountValue) / 100, 0);
    }
  }

  return {
    price,
    originalPrice: originalPrice > price ? originalPrice : null,
  };
};

export default function WishlistSection() {
  const { data: wishlistItems = [], isLoading, isFetching } = useGetWishlistQuery();
  const [removeFromWishlist, { isLoading: isRemoving }] =
    useRemoveFromWishlistMutation();

  const handleRemove = async (skuId) => {
    try {
      const response = await removeFromWishlist({ skuId }).unwrap();
      toast.success(response?.message || "Removed from wishlist");
    } catch (error) {
      toast.error(error?.data?.message || "Could not remove wishlist item.");
    }
  };

  if (isLoading || isFetching) {
    return (
      <div className="space-y-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <div
            key={index}
            className="flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-4"
          >
            <div className="h-20 w-20 animate-pulse rounded-2xl bg-slate-100" />
            <div className="flex-1 space-y-3">
              <div className="h-4 w-1/3 animate-pulse rounded bg-slate-100" />
              <div className="h-5 w-3/4 animate-pulse rounded bg-slate-100" />
              <div className="h-4 w-1/4 animate-pulse rounded bg-slate-100" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (wishlistItems.length === 0) {
    return (
      <div className="rounded-3xl border border-dashed border-gray-200 bg-gradient-to-br from-white to-slate-50 p-8 text-center sm:p-12">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
          <Heart size={24} />
        </div>
        <h3 className="mt-5 text-xl font-bold text-slate-800">Your wishlist is empty</h3>
        <p className="mt-2 text-sm leading-6 text-slate-500">
          Save products you love and they will show up here for quick access later.
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-[28px] border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
      <div className="mb-5 flex items-center justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-primary/70">
            Saved Items
          </p>
          <h2 className="mt-1 text-2xl font-bold text-slate-900">My Wishlist</h2>
        </div>
        <div className="rounded-2xl bg-primary/10 px-4 py-2 text-sm font-semibold text-primary">
          {wishlistItems.length} items
        </div>
      </div>

      <div className="space-y-4">
        {wishlistItems.map((item) => {
          const sku = item?.sku || {};
          const product = sku?.product || {};
          const imageSrc = product?.thumbnail_image
            ? getImageUrl(product.thumbnail_image)
            : "/images/no-image-available.png";
          const { price, originalPrice } = getWishlistPrice(sku, product);

          return (
            <div
              key={item?.id || item?.sku_id}
              className="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-primary/20 hover:bg-white sm:flex-row sm:items-center"
            >
              <Link
                href={`/product/${product?.slug}`}
                className="flex min-w-0 flex-1 items-center gap-4"
              >
                <div className="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border bg-white">
                  <Image
                    src={imageSrc}
                    alt={product?.name || "Wishlist product"}
                    fill
                    sizes="80px"
                    className="object-cover"
                    unoptimized
                  />
                </div>

                <div className="min-w-0 flex-1">
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                    {product?.brand?.name || "Saved Product"}
                  </p>
                  <h3 className="mt-1 line-clamp-2 text-base font-semibold text-slate-900">
                    {product?.name}
                  </h3>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="text-lg font-bold text-primary">
                      {"\u09F3"}
                      {formatPrice(price)}
                    </span>
                    {originalPrice ? (
                      <span className="text-sm text-slate-400 line-through">
                        {"\u09F3"}
                        {formatPrice(originalPrice)}
                      </span>
                    ) : null}
                    <span className="text-sm text-slate-500">
                      SKU: {sku?.sku_code || sku?.id}
                    </span>
                  </div>
                </div>
              </Link>

              <div className="flex items-center justify-end gap-3">
                <Link
                  href={`/product/${product?.slug}`}
                  className="inline-flex items-center gap-2 rounded-2xl border border-primary/20 bg-white px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary/5"
                >
                  <ShoppingBag size={16} />
                  View Product
                </Link>

                <button
                  type="button"
                  onClick={() => handleRemove(item?.sku_id)}
                  disabled={isRemoving}
                  className="inline-flex items-center gap-2 rounded-2xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <Trash2 size={16} />
                  Remove
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
