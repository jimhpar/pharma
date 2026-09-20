"use client";

import React, { useMemo, useState } from "react";
import {
  X,
  Plus,
  Minus,
  Trash2,
  ShoppingBag,
  ArrowRight,
  Tag,
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { toast } from "sonner";
import { useCart } from "../../lib/useCart";
import { useApplyCouponMutation } from "@/redux/features/cart/cartApi";

const formatMoney = (value) => `\u09F3${Number(value || 0).toFixed(2)}`;

const CartOffcanvas = () => {
  const {
    cartItems,
    appliedCoupon,
    isCartOpen,
    setIsCartOpen,
    setAppliedCoupon,
    updateQuantity,
    removeItem,
  } = useCart();
  const [couponCode, setCouponCode] = useState("");
  const [applyCoupon, { isLoading: isApplyingCoupon }] = useApplyCouponMutation();

  const subtotal = useMemo(
    () =>
      cartItems.reduce((total, item) => total + item.price * item.quantity, 0),
    [cartItems],
  );

  const isCouponStale = Boolean(
    appliedCoupon &&
    Number(appliedCoupon?.appliedOnTotal || 0).toFixed(2) !== subtotal.toFixed(2),
  );

  const effectiveCoupon = isCouponStale ? null : appliedCoupon;

  const discountAmount = Number(effectiveCoupon?.discountAmount || 0);
  const shippingCost = subtotal > 50 ? 0 : 5.99;
  const total = subtotal - discountAmount + shippingCost;

  const removeCoupon = () => setAppliedCoupon(null);

  const handleApplyCoupon = async () => {
    const trimmedCode = couponCode.trim();

    if (!trimmedCode) {
      toast.error("Please enter a coupon code.");
      return;
    }

    try {
      const response = await applyCoupon({
        coupon_code: trimmedCode,
        total_amount: Number(subtotal.toFixed(2)),
      }).unwrap();

      const couponData = response?.data?.coupon;
      setAppliedCoupon({
        code: couponData?.code || trimmedCode.toUpperCase(),
        discountAmount: Number(response?.data?.discount_amount || 0),
        discountPercentage: Number(response?.data?.discount_percentage || 0),
        finalTotal: Number(response?.data?.final_total || subtotal),
        appliedOnTotal: subtotal,
        coupon: couponData || null,
      });
      setCouponCode("");
      toast.success(response?.message || "Coupon applied successfully.");
    } catch (error) {
      setAppliedCoupon(null);
      toast.error(error?.data?.message || "Could not apply coupon.");
    }
  };

  return (
    <div>
      {isCartOpen && (
        <div
          className="fixed inset-0 z-50 bg-black/40 transition-opacity"
          onClick={() => setIsCartOpen(false)}
        />
      )}

      <div
        className={`fixed top-0 right-0 z-50 h-full w-full transform bg-white shadow-2xl transition-transform duration-300 sm:w-96 ${isCartOpen ? "translate-x-0" : "translate-x-full"
          }`}
      >
        <div className="flex h-full flex-col">
          <div className="flex items-center justify-between border-b bg-primary p-6 text-white">
            <div className="flex items-center gap-3">
              <ShoppingBag size={24} />
              <div>
                <h2 className="text-xl font-bold">Shopping Cart</h2>
                <p className="text-sm text-teal-100">{cartItems.length} items</p>
              </div>
            </div>
            <button
              onClick={() => setIsCartOpen(false)}
              className="rounded-lg p-2 transition hover:bg-white/20"
            >
              <X size={24} />
            </button>
          </div>

          <div className="flex-1 overflow-y-auto p-4">
            {cartItems.length === 0 ? (
              <div className="py-12 text-center">
                <ShoppingBag size={64} className="mx-auto mb-4 text-gray-300" />
                <h3 className="mb-2 text-xl font-semibold text-gray-800">
                  Your cart is empty
                </h3>
                <p className="mb-6 text-gray-600">
                  Add some items to get started!
                </p>
                <button
                  onClick={() => setIsCartOpen(false)}
                  className="rounded-lg bg-secondary px-6 py-3 text-white transition hover:bg-primary"
                >
                  Continue Shopping
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                {cartItems.map((item) => (
                  <div
                    key={item.id}
                    className="relative rounded-lg bg-gray-50 p-4"
                  >
                    <button
                      onClick={() => removeItem(item.id)}
                      className="absolute top-2 right-2 rounded p-1 text-red-500 transition hover:bg-red-50"
                    >
                      <Trash2 size={18} />
                    </button>
                    <div className="flex gap-4">
                      <Link
                        href={item.slug ? `/product/${item.slug}` : "#"}
                        onClick={() => setIsCartOpen(false)}
                        className="shrink-0"
                      >
                        <Image
                          src={item.image}
                          alt={item.name}
                          width={80}
                          height={80}
                          className="h-20 w-20 rounded-lg object-cover"
                          unoptimized
                        />
                      </Link>
                      <div className="flex-1">
                        <Link
                          href={item.slug ? `/product/${item.slug}` : "#"}
                          onClick={() => setIsCartOpen(false)}
                          className="block"
                        >
                          <h3 className="mb-1 pr-2 text-sm font-semibold text-gray-800 transition hover:text-primary">
                            {item.name}
                          </h3>
                        </Link>
                        <p className="font-bold text-primary">
                          {formatMoney(item.price)}
                        </p>
                        <div className="mt-2 flex items-center gap-2">
                          <button
                            onClick={() =>
                              updateQuantity(item.id, item.quantity - 1)
                            }
                            className="rounded border border-gray-300 p-1 transition hover:bg-gray-100"
                          >
                            <Minus size={16} />
                          </button>
                          <span className="rounded border border-gray-300 bg-white px-3 py-1 text-sm font-semibold">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() =>
                              updateQuantity(item.id, item.quantity + 1)
                            }
                            className="rounded border border-gray-300 p-1 transition hover:bg-gray-100"
                          >
                            <Plus size={16} />
                          </button>
                          <span className="ml-auto text-sm text-gray-600">
                            = {formatMoney(item.price * item.quantity)}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {cartItems.length > 0 ? (
              <div className="mt-6 border-t pt-6">
                <h3 className="mb-3 flex items-center gap-2 font-semibold text-gray-800">
                  <Tag size={18} className="text-primary" />
                  Have a Coupon Code?
                </h3>

                {appliedCoupon && !isCouponStale ? (
                  <div className="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-3">
                    <div>
                      <p className="text-sm font-semibold text-primary">
                        {appliedCoupon.code} Applied!
                      </p>
                      <p className="text-xs text-primary">
                        Saved {formatMoney(appliedCoupon.discountAmount)}
                        {appliedCoupon.discountPercentage
                          ? ` (${appliedCoupon.discountPercentage.toFixed(2)}%)`
                          : ""}
                      </p>
                    </div>
                    <button
                      onClick={removeCoupon}
                      className="text-sm font-semibold text-red-500 hover:text-red-700"
                    >
                      Remove
                    </button>
                  </div>
                ) : (
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={couponCode}
                      onChange={(e) => setCouponCode(e.target.value)}
                      placeholder="Enter code"
                      className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    />
                    <button
                      onClick={handleApplyCoupon}
                      disabled={isApplyingCoupon}
                      className="rounded-lg bg-secondary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      {isApplyingCoupon ? "Applying..." : "Apply"}
                    </button>
                  </div>
                )}

                {isCouponStale ? (
                  <p className="mt-2 text-xs text-amber-600">
                    Cart total changed. Please apply the coupon again.
                  </p>
                ) : null}
              </div>
            ) : null}
          </div>

          {cartItems.length > 0 ? (
            <div className="border-t bg-white p-4">
              <div className="mb-4 space-y-2">
                <div className="flex justify-between text-sm text-gray-600">
                  <span>Subtotal:</span>
                  <span>{formatMoney(subtotal)}</span>
                </div>

                {effectiveCoupon ? (
                  <div className="flex justify-between text-sm text-primary">
                    <span>Coupon Discount:</span>
                    <span>-{formatMoney(discountAmount)}</span>
                  </div>
                ) : null}

                <div className="flex justify-between text-sm text-gray-600">
                  <span>Shipping:</span>
                  <span>
                    {shippingCost === 0 ? (
                      <span className="font-semibold text-primary">FREE</span>
                    ) : (
                      formatMoney(shippingCost)
                    )}
                  </span>
                </div>

                {subtotal < 50 ? (
                  <p className="text-xs text-orange-600">
                    Add {formatMoney(50 - subtotal)} more for free shipping!
                  </p>
                ) : null}
              </div>

              <div className="mb-4 flex justify-between border-t pt-4 text-lg font-bold text-gray-800">
                <span>Total:</span>
                <span className="text-primary">{formatMoney(total)}</span>
              </div>

              <Link href="/checkout" onClick={() => setIsCartOpen(false)}>
                <button className="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-primary py-3 font-semibold text-white transition hover:bg-secondary">
                  Proceed to Checkout <ArrowRight size={18} />
                </button>
              </Link>
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
};

export default CartOffcanvas;
