"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import {
  ArrowLeft,
  CalendarDays,
  CircleX,
  CreditCard,
  Package,
  ReceiptText,
  Truck,
} from "lucide-react";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/imageHelpers";
import {
  useCancelOrderMutation,
  useGetOrderDetailsQuery,
} from "@/redux/features/order/orderApi";
import OrderCancelModal from "./OrderCancelModal";

const formatMoney = (value) => `Tk ${Number(value || 0).toFixed(2)}`;

const formatDate = (value) => {
  if (!value) return "N/A";

  return new Date(value).toLocaleDateString("en-BD", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

export default function OrderDetailsPageClient({ orderId }) {
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelOrder, { isLoading: isCancellingOrder }] = useCancelOrderMutation();
  const {
    data: selectedOrder,
    isLoading,
    isError,
    error,
  } = useGetOrderDetailsQuery(orderId, {
    refetchOnMountOrArgChange: true,
  });

  const errorMessage =
    error?.data?.message ||
    (isError ? "We could not load this order right now." : "");
  const isOrderUnavailable =
    isError && errorMessage.toLowerCase() === "order not found";
  const isPendingOrder =
    String(selectedOrder?.status || "").toLowerCase() === "pending";

  const handleCancelOrder = async () => {
    if (!selectedOrder?.id) return;
    try {
      const response = await cancelOrder(selectedOrder.id).unwrap();
      toast.success(response?.message || "Order cancelled successfully.");
      setShowCancelModal(false);
    } catch (cancelError) {
      toast.error(
        cancelError?.data?.message || "Could not cancel this order.",
      );
    }
  };

  return (
    <div className="bg-white">
      <div className="container mx-auto py-4">
        <div className="mb-4">
          <Link
            href="/profile?tab=orders"
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
          >
            <ArrowLeft size={16} />
            Back to My Orders
          </Link>
        </div>

        <div className="rounded-[24px] border border-gray-200 bg-white p-4 sm:rounded-[30px] sm:p-7 lg:p-8">
          <div className="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-5 sm:mb-8 sm:pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary sm:text-sm sm:tracking-[0.2em]">
                Order Details
              </p>
              <h1 className="mt-2 text-xl font-bold text-slate-800 sm:text-3xl">
                {selectedOrder?.order_no || "Loading Order..."}
              </h1>
              <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Review the items, status, totals, and notes for this order.
              </p>
            </div>

            {selectedOrder && isPendingOrder ? (
              <button
                type="button"
                onClick={() => setShowCancelModal(true)}
                disabled={isCancellingOrder}
                className="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <CircleX size={16} />
                {isCancellingOrder ? "Cancelling..." : "Cancel Order"}
              </button>
            ) : null}
          </div>

          {isLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="animate-pulse rounded-2xl bg-slate-100 p-4">
                  <div className="h-4 w-48 rounded bg-slate-200" />
                  <div className="mt-3 h-20 rounded bg-slate-200" />
                </div>
              ))}
            </div>
          ) : isError ? (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center sm:p-10">
              <h2 className="text-lg font-bold text-slate-800 sm:text-xl">
                {isOrderUnavailable ? "Order not found" : "Unable to load order details"}
              </h2>
              <p className="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                {isOrderUnavailable
                  ? "This order does not exist or it does not belong to your account."
                  : errorMessage}
              </p>
              <Link
                href="/profile?tab=orders"
                className="mt-5 inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
              >
                <ArrowLeft size={16} />
                Back to My Orders
              </Link>
            </div>
          ) : selectedOrder ? (
            <div className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-2xl bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-slate-400">
                    <CalendarDays size={16} />
                    <p className="text-xs font-semibold uppercase tracking-[0.14em]">
                      Order Date
                    </p>
                  </div>
                  <p className="mt-2 font-semibold text-slate-800">
                    {formatDate(selectedOrder.order_date)}
                  </p>
                </div>
                <div className="rounded-2xl bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-slate-400">
                    <Package size={16} />
                    <p className="text-xs font-semibold uppercase tracking-[0.14em]">
                      Status
                    </p>
                  </div>
                  <p className="mt-2 font-semibold capitalize text-slate-800">
                    {selectedOrder.status}
                  </p>
                </div>
                <div className="rounded-2xl bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-slate-400">
                    <CreditCard size={16} />
                    <p className="text-xs font-semibold uppercase tracking-[0.14em]">
                      Payment
                    </p>
                  </div>
                  <p className="mt-2 font-semibold capitalize text-slate-800">
                    {selectedOrder.payment_status}
                  </p>
                </div>
                <div className="rounded-2xl bg-slate-50 p-4">
                  <div className="flex items-center gap-2 text-slate-400">
                    <Truck size={16} />
                    <p className="text-xs font-semibold uppercase tracking-[0.14em]">
                      Fulfillment
                    </p>
                  </div>
                  <p className="mt-2 font-semibold capitalize text-slate-800">
                    {selectedOrder.fulfillment_status}
                  </p>
                </div>
              </div>

              <div className="rounded-2xl border border-slate-200 p-4 sm:p-5">
                <div className="mb-4 flex items-center gap-2">
                  <ReceiptText size={18} className="text-primary" />
                  <h2 className="text-lg font-bold text-slate-800">Items</h2>
                </div>
                <div className="space-y-4">
                  {selectedOrder.items?.map((item) => {
                    const product = item?.sku?.product;
                    const productHref = product?.slug ? `/product/${product.slug}` : "#";
                    return (
                      <div
                        key={item.id}
                        className="flex gap-3 border-b border-slate-100 pb-4 last:border-b-0"
                      >
                        <Link href={productHref} className="shrink-0">
                          <Image
                            src={getImageUrl(product?.thumbnail_image)}
                            alt={product?.name || "Ordered product"}
                            width={72}
                            height={72}
                            className="h-[72px] w-[72px] rounded-xl object-cover"
                            unoptimized
                          />
                        </Link>
                        <div className="min-w-0 flex-1">
                          <Link href={productHref} className="block">
                            <p className="line-clamp-2 font-semibold text-slate-800 transition hover:text-primary">
                              {product?.name || "Product"}
                            </p>
                          </Link>
                          <div className="mt-2 grid gap-1 text-sm text-slate-500 sm:grid-cols-2">
                            <p>SKU: {item?.sku?.sku_code || "N/A"}</p>
                            <p>Quantity: {item.quantity}</p>
                            <p>Unit Price: {formatMoney(item.unit_price)}</p>
                            <p>Line Total: {formatMoney(item.line_total)}</p>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div className="rounded-2xl border border-slate-200 p-4 sm:p-5">
                  <h2 className="text-lg font-bold text-slate-800">Order Notes</h2>
                  <p className="mt-3 text-sm leading-6 text-slate-500">
                    {selectedOrder.customer_note ||
                      selectedOrder.internal_note ||
                      "No notes available for this order."}
                  </p>
                </div>

                <div className="rounded-2xl border border-slate-200 p-4 sm:p-5">
                  <h2 className="text-lg font-bold text-slate-800">Summary</h2>
                  <div className="mt-4 space-y-3 text-sm text-slate-600">
                    <div className="flex items-center justify-between">
                      <span>Subtotal</span>
                      <span className="font-semibold text-slate-800">
                        {formatMoney(selectedOrder.sub_total)}
                      </span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Shipping Fee</span>
                      <span className="font-semibold text-slate-800">
                        {formatMoney(selectedOrder.shipping_fee)}
                      </span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Paid</span>
                      <span className="font-semibold text-slate-800">
                        {formatMoney(selectedOrder.paid_total)}
                      </span>
                    </div>
                    <div className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                      <span className="font-semibold text-slate-700">
                        Due Total
                      </span>
                      <span className="text-lg font-bold text-primary">
                        {formatMoney(selectedOrder.due_total)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500">
              Unable to load order details right now.
            </div>
          )}
        </div>
      </div>

      <OrderCancelModal
        isOpen={showCancelModal && Boolean(selectedOrder)}
        orderNo={selectedOrder?.order_no}
        isLoading={isCancellingOrder}
        onClose={() => setShowCancelModal(false)}
        onConfirm={handleCancelOrder}
      />
    </div>
  );
}
