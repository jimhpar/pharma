"use client";

import Image from "next/image";
import Link from "next/link";
import { useMemo } from "react";
import {
  CheckCircle2,
  ChevronRight,
  Home,
  Package,
  ShoppingBag,
} from "lucide-react";
import { getStoredCheckoutOrder } from "@/lib/checkoutOrderStorage";
import { getImageUrl } from "@/lib/imageHelpers";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export default function CheckoutConfirmationPage({ orderNumber }) {
  const storedOrder = getStoredCheckoutOrder();

  const orderSummary = useMemo(() => {
    if (!storedOrder) return null;
    if (orderNumber && storedOrder?.order_number !== orderNumber) return null;
    return storedOrder;
  }, [orderNumber, storedOrder]);

  const order = orderSummary?.order || null;
  const orderItems = order?.items || [];

  if (!orderSummary || !order) {
    return (
      <div className="px-3 py-6 sm:px-4 sm:py-10">
        <div className="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
          <h1 className="text-xl font-bold text-slate-800 sm:text-2xl">
            Order confirmation not found
          </h1>
          <p className="mt-3 text-sm text-slate-500 sm:text-base">
            We could not find the latest order summary. You can continue shopping from the home page.
          </p>
          <Link
            href="/"
            className="mt-6 inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-secondary"
          >
            Back to Home
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="px-3 py-5 sm:px-4 sm:py-8">
      <div className="mb-5 flex items-center gap-1.5 text-xs text-gray-600 sm:mb-8 sm:gap-2 sm:text-sm">
        <Link href="/" className="hover:text-primary">
          <Home size={14} className="sm:h-4 sm:w-4" />
        </Link>
        <ChevronRight size={14} className="sm:h-4 sm:w-4" />
        <span className="font-semibold text-gray-800">Order Confirmation</span>
      </div>

      <div className="mx-auto max-w-6xl space-y-5 sm:space-y-6">
        <div className="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 p-5 shadow-sm sm:p-7">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-3">
              <span className="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 sm:h-14 sm:w-14">
                <CheckCircle2 className="h-6 w-6 sm:h-7 sm:w-7" />
              </span>
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">
                  Order Confirmed
                </p>
                <h1 className="mt-1 text-xl font-bold text-slate-800 sm:text-3xl">
                  Thank you for your order
                </h1>
                <p className="mt-2 text-sm text-slate-600 sm:text-base">
                  Your order has been placed successfully and is now waiting for confirmation.
                </p>
              </div>
            </div>
            <div className="rounded-xl bg-white px-4 py-3 shadow-sm">
              <p className="text-xs uppercase tracking-[0.14em] text-slate-400">
                Order Number
              </p>
              <p className="mt-1 text-sm font-bold text-slate-800 sm:text-base">
                {orderSummary.order_number}
              </p>
            </div>
          </div>
        </div>

        <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-6">
          <div className="space-y-4 sm:space-y-6">
            <div className="rounded-2xl border bg-white p-4 shadow-sm sm:p-6">
              <div className="mb-4 flex items-center gap-2">
                <Package className="h-5 w-5 text-primary" />
                <h2 className="text-lg font-bold text-slate-800 sm:text-xl">Order Items</h2>
              </div>

              <div className="space-y-4">
                {orderItems.map((item) => {
                  const product = item?.sku?.product;
                  const imageSrc = getImageUrl(product?.thumbnail_image || "");
                  const productHref = product?.slug ? `/product/${product.slug}` : "#";

                  return (
                    <div
                      key={item.id}
                      className="flex gap-3 border-b border-slate-100 pb-4 last:border-b-0"
                    >
                      <Link href={productHref} className="shrink-0">
                        <Image
                          src={imageSrc}
                          alt={product?.name || "Ordered product"}
                          width={72}
                          height={72}
                          className="h-16 w-16 rounded-lg object-cover sm:h-[72px] sm:w-[72px]"
                          unoptimized
                        />
                      </Link>
                      <div className="min-w-0 flex-1">
                        <Link href={productHref} className="block">
                          <p className="line-clamp-2 text-sm font-semibold text-slate-800 transition hover:text-primary sm:text-base">
                            {product?.name || "Product"}
                          </p>
                        </Link>
                        <p className="mt-1 text-xs text-slate-500 sm:text-sm">
                          SKU: {item?.sku?.sku_code || "N/A"}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 sm:text-sm">
                          Quantity: {item.quantity}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-bold text-slate-800 sm:text-base">
                          Tk {toNumber(item.line_total).toFixed(2)}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          <div className="space-y-4 sm:space-y-6">
            <div className="rounded-2xl border bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-24">
              <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-primary sm:text-xs">
                Order Summary
              </p>
              <h2 className="mt-1.5 text-lg font-bold text-slate-800 sm:text-2xl">
                Purchase Details
              </h2>

              <div className="mt-5 space-y-3 sm:space-y-4">
                <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
                  <span>Status</span>
                  <span className="font-semibold capitalize text-slate-800">
                    {order.status}
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
                  <span>Payment Status</span>
                  <span className="font-semibold capitalize text-slate-800">
                    {order.payment_status}
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
                  <span>Subtotal</span>
                  <span className="font-semibold text-slate-800">
                    Tk {toNumber(orderSummary.sub_total).toFixed(2)}
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
                  <span>Delivery Charge</span>
                  <span className="font-semibold text-slate-800">
                    Tk {toNumber(orderSummary.shipping_fee).toFixed(2)}
                  </span>
                </div>
                <div className="rounded-xl bg-slate-50 px-3 py-3 sm:px-4 sm:py-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-semibold text-slate-700 sm:text-base">
                      Total Amount
                    </span>
                    <span className="text-xl font-bold text-primary sm:text-2xl">
                      Tk {toNumber(orderSummary.total_amount).toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>

              <div className="mt-6 grid gap-3">
                <Link
                  href="/profile?tab=orders"
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-secondary"
                >
                  <ShoppingBag className="h-4 w-4" />
                  View My Orders
                </Link>
                <Link
                  href="/"
                  className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  Continue Shopping
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
