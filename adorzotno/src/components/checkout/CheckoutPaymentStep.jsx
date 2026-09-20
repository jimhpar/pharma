"use client";

import { CreditCard, Package } from "lucide-react";

export default function CheckoutPaymentStep({
  paymentMethod,
  setPaymentMethod,
  onBack,
  onReview,
}) {
  return (
    <div className="rounded-lg bg-white p-4 shadow-md sm:p-6">
      <div className="mb-5 flex items-center gap-2.5 sm:mb-6 sm:gap-3">
        <Package className="h-6 w-6 text-primary sm:h-7 sm:w-7" />
        <h2 className="text-lg font-bold text-gray-800 sm:text-2xl">Payment Method</h2>
      </div>

      <div className="mb-5 grid gap-3 sm:mb-6 sm:gap-4 md:grid-cols-2">
        <button
          type="button"
          disabled
          className="cursor-not-allowed rounded-lg border-2 border-gray-200 bg-gray-50 p-3 opacity-60 sm:p-4"
        >
          <CreditCard className="mx-auto mb-1.5 h-7 w-7 text-primary sm:mb-2 sm:h-8 sm:w-8" />
          <p className="text-sm font-semibold text-gray-800 sm:text-base">Credit Card</p>
          <p className="mt-1 text-xs text-gray-500">Coming later</p>
        </button>
        <button
          type="button"
          onClick={() => setPaymentMethod("cod")}
          className={`rounded-lg border-2 p-3 transition sm:p-4 ${paymentMethod === "cod" ? "border-secondary bg-teal-50" : "border-gray-300"
            }`}
        >
          <Package className="mx-auto mb-1.5 h-7 w-7 text-primary sm:mb-2 sm:h-8 sm:w-8" />
          <p className="text-sm font-semibold text-gray-800 sm:text-base">Cash on Delivery</p>
        </button>
      </div>

      {paymentMethod === "cod" && (
        <div>
          <div className="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 p-4 sm:mb-6 sm:p-6">
            <h3 className="mb-1.5 text-sm font-semibold text-gray-800 sm:mb-2 sm:text-base">Cash on Delivery</h3>
            <p className="text-xs text-gray-600 sm:text-sm">
              You will pay for your order when it is delivered to your address.
              Please keep exact change ready.
            </p>
          </div>
          <div className="flex gap-3 sm:gap-4">
            <button
              onClick={onBack}
              className="flex-1 rounded-lg border-2 border-gray-300 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:py-4 sm:text-base"
            >
              Back to Shipping
            </button>
            <button
              onClick={onReview}
              className="flex-1 rounded-lg bg-primary py-3 text-sm font-semibold text-white transition hover:bg-secondary sm:py-4 sm:text-base"
            >
              Review Order
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
