"use client";

import { createPortal } from "react-dom";

export default function OrderCancelModal({
  isOpen,
  orderNo,
  isLoading = false,
  onClose,
  onConfirm,
}) {
  if (!isOpen || typeof document === "undefined") return null;

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 animate-[orderCancelBackdrop_0.12s_ease-out]">
      <div className="w-full max-w-md overflow-visible rounded-3xl bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] animate-[orderCancelPopup_0.16s_cubic-bezier(0.2,0.9,0.25,1)] sm:p-6">
        <h3 className="text-xl font-bold text-slate-800">Cancel Order?</h3>
        <p className="mt-3 text-sm leading-6 text-slate-500">
          Are you sure you want to cancel{" "}
          <span className="font-semibold text-slate-700">{orderNo}</span>? Only
          pending orders can be cancelled.
        </p>

        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            onClick={onClose}
            disabled={isLoading}
            className="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          >
            Keep Order
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={isLoading}
            className="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {isLoading ? "Cancelling..." : "Yes, Cancel Order"}
          </button>
        </div>
      </div>
      <style jsx global>{`
        @keyframes orderCancelBackdrop {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        @keyframes orderCancelPopup {
          from {
            opacity: 0;
            transform: scale(0.88);
          }
          to {
            opacity: 1;
            transform: scale(1);
          }
        }
      `}</style>
    </div>,
    document.body,
  );
}
