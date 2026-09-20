"use client";

import Link from "next/link";
import {
  CalendarDays,
  CircleX,
  ChevronLeft,
  ChevronRight,
  CreditCard,
  Eye,
  Package,
  Truck,
} from "lucide-react";
import { useMemo, useState } from "react";
import { toast } from "sonner";
import OrderCancelModal from "./OrderCancelModal";
import {
  useCancelOrderMutation,
  useGetOrdersQuery,
} from "@/redux/features/order/orderApi";

export default function OrdersSection() {
  const [currentPage, setCurrentPage] = useState(1);
  const [cancellingOrderId, setCancellingOrderId] = useState(null);
  const [orderToCancel, setOrderToCancel] = useState(null);
  const [cancelOrder, { isLoading: isCancellingOrder }] = useCancelOrderMutation();
  const { data: ordersResponse, isLoading } = useGetOrdersQuery({
    page: currentPage,
    perPage: 10,
  }, {
    refetchOnMountOrArgChange: true,
  });

  const orders = ordersResponse?.data || [];
  const pagination = useMemo(
    () => ({
      currentPage: ordersResponse?.current_page || 1,
      lastPage: ordersResponse?.last_page || 1,
      total: ordersResponse?.total || 0,
      from: ordersResponse?.from || 0,
      to: ordersResponse?.to || 0,
      hasPrev: Boolean(ordersResponse?.prev_page_url),
      hasNext: Boolean(ordersResponse?.next_page_url),
    }),
    [ordersResponse],
  );

  const formatMoney = (value) => `Tk ${Number(value || 0).toFixed(2)}`;
  const isPendingOrder = (status) => String(status || "").toLowerCase() === "pending";
  const formatDate = (value) => {
    if (!value) return "N/A";

    return new Date(value).toLocaleDateString("en-BD", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };

  const getStatusBadgeClass = (status) => {
    const normalizedStatus = (status || "").toLowerCase();

    if (normalizedStatus === "paid" || normalizedStatus === "completed") {
      return "bg-emerald-50 text-emerald-700";
    }

    if (normalizedStatus === "pending" || normalizedStatus === "unpaid") {
      return "bg-amber-50 text-amber-700";
    }

    if (normalizedStatus === "cancelled" || normalizedStatus === "failed") {
      return "bg-red-50 text-red-700";
    }

    return "bg-slate-100 text-slate-700";
  };

  const confirmCancelOrder = async () => {
    if (!orderToCancel?.id) return;
    try {
      setCancellingOrderId(orderToCancel.id);
      const response = await cancelOrder(orderToCancel.id).unwrap();
      toast.success(response?.message || "Order cancelled successfully.");
      setOrderToCancel(null);
    } catch (error) {
      toast.error(error?.data?.message || "Could not cancel this order.");
    } finally {
      setCancellingOrderId(null);
    }
  };

  return (
    <div className="space-y-5 sm:space-y-6">
      <div className="rounded-[24px] border border-gray-200 bg-white p-4 sm:rounded-[30px] sm:p-7 lg:p-8">
        <div className="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-5 sm:mb-8 sm:pb-6 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary sm:text-sm sm:tracking-[0.2em]">
              Order History
            </p>
            <h2 className="mt-2 text-xl font-bold text-slate-800 sm:text-3xl">
              My Orders
            </h2>
            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
              Review your previous orders, payment status, and shipping progress in one place.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:max-w-[220px]">
            <p className="font-semibold text-slate-800">Total Orders</p>
            <p className="mt-1">{pagination.total}</p>
          </div>
        </div>

        {isLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 3 }).map((_, index) => (
              <div
                key={index}
                className="animate-pulse rounded-2xl border border-slate-100 bg-slate-50 p-4"
              >
                <div className="h-4 w-40 rounded bg-slate-200" />
                <div className="mt-3 h-3 w-28 rounded bg-slate-200" />
                <div className="mt-4 h-12 rounded bg-slate-200" />
              </div>
            ))}
          </div>
        ) : orders.length > 0 ? (
          <>
            <div className="space-y-4">
              {orders.map((order) => (
                <div
                  key={order.id}
                  className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5"
                >
                  <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="space-y-2">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                          {order.order_no}
                        </span>
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${getStatusBadgeClass(
                            order.status,
                          )}`}
                        >
                          {order.status}
                        </span>
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${getStatusBadgeClass(
                            order.payment_status,
                          )}`}
                        >
                          {order.payment_status}
                        </span>
                      </div>

                      <div className="grid gap-2 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="flex items-center gap-2">
                          <CalendarDays size={16} className="text-primary" />
                          <span>{formatDate(order.order_date)}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Package size={16} className="text-primary" />
                          <span>{order.items?.length || 0} item(s)</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Truck size={16} className="text-primary" />
                          <span className="capitalize">{order.fulfillment_status}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <CreditCard size={16} className="text-primary" />
                          <span>{formatMoney(order.grand_total)}</span>
                        </div>
                      </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">

                      {isPendingOrder(order.status) ? (
                        <button
                          type="button"
                          onClick={() => setOrderToCancel(order)}
                          disabled={isCancellingOrder && cancellingOrderId === order.id}
                          className="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          <CircleX size={16} />
                          {isCancellingOrder && cancellingOrderId === order.id
                            ? "Cancelling..."
                            : "Cancel Order"}
                        </button>
                      ) : null}

                      <Link
                        href={`/profile/orders/${order.id}`}
                        className="inline-flex items-center justify-center gap-2 rounded-xl border border-primary/20 bg-white px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white"
                      >
                        <Eye size={16} />
                        View Details
                      </Link>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
              <p className="text-sm text-slate-500">
                Showing {pagination.from}-{pagination.to} of {pagination.total} orders
              </p>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  disabled={!pagination.hasPrev}
                  onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <ChevronLeft size={16} />
                  Prev
                </button>
                <span className="rounded-xl bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                  {pagination.currentPage} / {pagination.lastPage}
                </span>
                <button
                  type="button"
                  disabled={!pagination.hasNext}
                  onClick={() =>
                    setCurrentPage((prev) =>
                      Math.min(prev + 1, pagination.lastPage),
                    )
                  }
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Next
                  <ChevronRight size={16} />
                </button>
              </div>
            </div>
          </>
        ) : (
          <div className="rounded-3xl border border-dashed border-gray-200 bg-gradient-to-br from-white to-slate-50 p-8 text-center sm:p-12">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
              <Package size={24} />
            </div>
            <h3 className="mt-5 text-xl font-bold text-slate-800">No Orders Yet</h3>
            <p className="mt-2 text-sm leading-6 text-slate-500">
              Once you place an order, it will appear here with its payment and delivery status.
            </p>
          </div>
        )}
      </div>

      <OrderCancelModal
        isOpen={Boolean(orderToCancel)}
        orderNo={orderToCancel?.order_no}
        isLoading={isCancellingOrder}
        onClose={() => setOrderToCancel(null)}
        onConfirm={confirmCancelOrder}
      />
    </div>
  );
}
