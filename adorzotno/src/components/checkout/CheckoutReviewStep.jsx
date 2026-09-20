"use client";

import { CreditCard, Package, Shield } from "lucide-react";
import Image from "next/image";
import Link from "next/link";

export default function CheckoutReviewStep({
  shippingInfo,
  paymentMethod,
  paymentInfo,
  cartItems,
  subtotal,
  appliedCoupon,
  couponDiscount,
  deliveryCharge,
  grandTotal,
  totalItems,
  shipmentZone,
  onEditShipping,
  onEditPayment,
  onBack,
  onPlaceOrder,
  isPlacingOrder,
}) {
  return (
    <div className="grid gap-4 sm:gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
      <div className="space-y-4 sm:space-y-6">
        <div className="rounded-lg border bg-white p-4 shadow-sm sm:p-6">
          <div className="mb-3 flex items-center justify-between sm:mb-4">
            <h3 className="text-lg font-bold text-gray-800 sm:text-xl">Shipping Address</h3>
            <button
              onClick={onEditShipping}
              className="text-sm font-semibold text-primary hover:text-secondary"
            >
              Edit
            </button>
          </div>
          <div className="text-sm text-gray-600 sm:text-base">
            <p className="font-semibold text-gray-800">
              {shippingInfo.firstName} {shippingInfo.lastName}
            </p>
            <p>{shippingInfo.fullAddress}</p>
            {shipmentZone?.name && (
              <p className="mt-2 text-xs text-slate-500 sm:text-sm">
                Delivery Area: {shipmentZone.name}
              </p>
            )}
            {shippingInfo.notes && (
              <p className="mt-2 text-xs text-slate-500 sm:text-sm">
                Note: {shippingInfo.notes}
              </p>
            )}
            <p className="mt-2">{shippingInfo.email}</p>
            <p>{shippingInfo.phone}</p>
          </div>
        </div>

        <div className="rounded-lg border bg-white p-4 shadow-sm sm:p-6">
          <div className="mb-3 flex items-center justify-between sm:mb-4">
            <h3 className="text-lg font-bold text-gray-800 sm:text-xl">Payment Method</h3>
            <button
              onClick={onEditPayment}
              className="text-sm font-semibold text-primary hover:text-secondary"
            >
              Edit
            </button>
          </div>
          <div className="flex items-center gap-2.5 text-sm text-gray-600 sm:gap-3 sm:text-base">
            {paymentMethod === "card" && (
              <>
                <CreditCard className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                <span className="text-gray-600">
                  Credit Card ending in {paymentInfo.cardNumber.slice(-4)}
                </span>
              </>
            )}
            {paymentMethod === "paypal" && (
              <>
                <div className="h-6 w-6 rounded bg-primary" />
                <span className="text-gray-600">PayPal</span>
              </>
            )}
            {paymentMethod === "cod" && (
              <>
                <Package className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                <span className="text-gray-600">Cash on Delivery</span>
              </>
            )}
          </div>
        </div>

        <div className="rounded-lg border bg-white p-4 shadow-sm sm:p-6">
          <h3 className="mb-3 text-lg font-bold text-gray-800 sm:mb-4 sm:text-xl">Order Items</h3>
          <div className="space-y-3 sm:space-y-4">
            {cartItems.length > 0 ? (
              cartItems.map((item) => (
                <div key={item.id} className="flex gap-3 border-b pb-3 last:border-b-0 sm:gap-4 sm:pb-4">
                  <Link
                    href={item.slug ? `/product/${item.slug}` : "#"}
                    className="shrink-0"
                  >
                    <Image
                      src={item.image}
                      alt={item.name}
                      width={80}
                      height={80}
                      className="h-16 w-16 rounded-lg object-cover sm:h-20 sm:w-20"
                      unoptimized
                    />
                  </Link>
                  <div className="min-w-0 flex-1">
                    <Link
                      href={item.slug ? `/product/${item.slug}` : "#"}
                      className="block"
                    >
                      <h4 className="line-clamp-2 text-sm font-semibold text-gray-800 transition hover:text-primary sm:text-base">
                        {item.name}
                      </h4>
                    </Link>
                    <p className="mt-1 text-xs text-gray-600 sm:text-sm">Quantity: {item.quantity}</p>
                    <p className="mt-1 text-[11px] text-slate-500 sm:text-xs">
                      Unit price: Tk {item.price.toFixed(2)}
                    </p>
                  </div>
                  <div className="shrink-0 text-right">
                    <p className="text-sm font-bold text-gray-800 sm:text-base">
                      Tk {(item.price * item.quantity).toFixed(2)}
                    </p>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-sm text-slate-500">Your cart is currently empty.</p>
            )}
          </div>
        </div>
      </div>

      <div className="space-y-4 sm:space-y-6">
        <div className="rounded-lg border bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-24">
          <div className="mb-4 border-b border-slate-100 pb-3 sm:mb-5 sm:pb-4">
            <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-primary sm:text-xs sm:tracking-[0.18em]">
              Order Summary
            </p>
            <h3 className="mt-1.5 text-lg font-bold text-slate-800 sm:mt-2 sm:text-2xl">Review Totals</h3>
          </div>

          <div className="space-y-3 sm:space-y-4">
            <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
              <span>Total Items</span>
              <span className="font-semibold text-slate-800">{totalItems}</span>
            </div>
            <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
              <span>Subtotal</span>
              <span className="font-semibold text-slate-800">
                Tk {subtotal.toFixed(2)}
              </span>
            </div>
            {appliedCoupon ? (
              <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
                <span>Coupon ({appliedCoupon.code})</span>
                <span className="font-semibold text-emerald-600">
                  -Tk {Number(couponDiscount || 0).toFixed(2)}
                </span>
              </div>
            ) : null}
            <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
              <span>Shipment Zone</span>
              <span className="font-semibold text-slate-800">
                {shipmentZone?.name || "N/A"}
              </span>
            </div>
            <div className="flex items-center justify-between text-xs text-slate-600 sm:text-sm">
              <span>Delivery Charge</span>
              <span className="font-semibold text-slate-800">
                Tk {deliveryCharge.toFixed(2)}
              </span>
            </div>
            <div className="rounded-xl bg-slate-50 px-3 py-3 sm:px-4 sm:py-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-semibold text-slate-700 sm:text-base">Total Amount</span>
                <span className="text-xl font-bold text-primary sm:text-2xl">
                  Tk {grandTotal.toFixed(2)}
                </span>
              </div>
            </div>
          </div>

          <div className="mt-5 flex flex-col gap-3 sm:mt-6">
            <button
              onClick={onPlaceOrder}
              disabled={isPlacingOrder}
              className="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-3 font-semibold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-70 sm:gap-2 sm:px-4 sm:py-4 sm:text-sm"
            >
              <Shield size={16} className="sm:h-5 sm:w-5" />
              {isPlacingOrder ? "Placing..." : "Place Order"}
            </button>
            <button
              onClick={onBack}
              className="flex-1 rounded-lg border border-gray-300 px-3 py-3 font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 sm:px-4 sm:py-4 sm:text-sm"
            >
              Back to Payment
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
