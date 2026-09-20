"use client";

import React, { useMemo, useState } from "react";
import { ChevronRight, Home } from "lucide-react";
import { useRouter } from "next/navigation";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import { useCart } from "@/lib/useCart";
import { storeCheckoutOrder } from "@/lib/checkoutOrderStorage";
import { useSelectedShipmentZone } from "@/lib/shipmentZoneStorage";
import { useCheckoutOrderMutation } from "@/redux/features/order/orderApi";
import { useGetShipmentZonesQuery } from "@/redux/features/settings/settingsApi";
import CheckoutPaymentStep from "./CheckoutPaymentStep";
import CheckoutProgressSteps from "./CheckoutProgressSteps";
import CheckoutReviewStep from "./CheckoutReviewStep";
import CheckoutShippingStep from "./CheckoutShippingStep";
import { buildShippingAddress } from "@/lib/checkoutUtils";

export default function Checkout({ user: initialUser }) {
  const router = useRouter();
  const authUser = useSelector((state) => state.auth.user);
  const user = authUser || initialUser;
  const { cartItems, appliedCoupon, clearCart } = useCart();
  const { data: shipmentZones = [] } = useGetShipmentZonesQuery();
  const { selectedShipmentZone } = useSelectedShipmentZone(shipmentZones);
  const [checkoutOrder, { isLoading: isPlacingOrder }] = useCheckoutOrderMutation();
  const [currentStep, setCurrentStep] = useState(1);
  const [paymentMethod, setPaymentMethod] = useState("cod");
  const [shippingInfo, setShippingInfo] = useState(() => {
    const customer = user?.customer || {};
    const nameParts = (user?.name || "").trim().split(/\s+/).filter(Boolean);
    const firstName = nameParts[0] || "";
    const lastName = nameParts.slice(1).join(" ");
    const baseInfo = {
      firstName,
      lastName,
      email: user?.email || "",
      phone: user?.phone || "",
      address: customer?.shipping_address || "",
      city: "",
      state: "",
      zipCode: "",
      notes: "",
    };

    return {
      ...baseInfo,
      fullAddress: buildShippingAddress(baseInfo),
    };
  });
  const [paymentInfo, setPaymentInfo] = useState({
    cardNumber: "",
    cardName: "",
    expiryDate: "",
    cvv: "",
  });

  const normalizedCartItems = useMemo(
    () =>
      cartItems.map((item) => ({
        id: item.id,
        productId: Number(item.productId || item.id),
        name: item.name,
        slug: item.slug || "",
        price: Number(item.price) || 0,
        quantity: Number(item.quantity) || 0,
        image: item.image || "/images/no-image-available.png",
      })),
    [cartItems],
  );

  const subtotal = useMemo(
    () =>
      normalizedCartItems.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
      ),
    [normalizedCartItems],
  );

  const totalItems = useMemo(
    () =>
      normalizedCartItems.reduce((total, item) => total + item.quantity, 0),
    [normalizedCartItems],
  );

  const finalDeliveryCharge =
    normalizedCartItems.length > 0
      ? Number(selectedShipmentZone?.charge) || 0
      : 0;
  const couponDiscount = Number(appliedCoupon?.discountAmount || 0);
  const grandTotal = subtotal - couponDiscount + finalDeliveryCharge;

  const handleShippingSubmit = (formData) => {
    setShippingInfo(formData);
    setCurrentStep(2);
  };

  const handlePlaceOrder = async () => {
    if (!normalizedCartItems.length) {
      toast.error("Your cart is empty.");
      return;
    }

    if (!selectedShipmentZone?.id) {
      toast.error("Please select a delivery area before placing the order.");
      return;
    }

    if (!shippingInfo?.fullAddress || !shippingInfo?.phone) {
      toast.error("Shipping address and phone number are required.");
      return;
    }

    const payload = {
      items: normalizedCartItems.map((item) => ({
        product_id: item.productId,
        quantity: item.quantity,
      })),
      shipping_address: shippingInfo.fullAddress,
      phone: shippingInfo.phone,
      coupon_code: appliedCoupon?.code || "",
      shipment_zone_id: selectedShipmentZone.id,
      notes: shippingInfo.notes?.trim() || "",
    };

    try {
      const response = await checkoutOrder(payload).unwrap();
      toast.success(response?.message || "Order placed successfully.");
      storeCheckoutOrder(response?.data || null);
      clearCart();
      router.push(
        `/checkout/confirmation?order=${encodeURIComponent(
          response?.data?.order_number || "",
        )}`,
      );
    } catch (error) {
      toast.error(error?.data?.message || "Unable to place the order right now.");
    }
  };

  return (
    <div className="px-3 py-5 sm:px-4 sm:py-8">
      <div className="mb-5 flex items-center gap-1.5 text-xs text-gray-600 sm:mb-8 sm:gap-2 sm:text-sm">
        <button className="hover:text-primary">
          <Home size={14} className="sm:h-4 sm:w-4" />
        </button>
        <ChevronRight size={14} className="sm:h-4 sm:w-4" />
        <button className="hover:text-primary">Cart</button>
        <ChevronRight size={14} className="sm:h-4 sm:w-4" />
        <span className="font-semibold text-gray-800">Checkout</span>
      </div>

      <div className="mx-auto max-w-6xl">
        <CheckoutProgressSteps currentStep={currentStep} />

        {currentStep === 1 && (
          <CheckoutShippingStep user={user} onSubmit={handleShippingSubmit} />
        )}

        {currentStep === 2 && (
          <CheckoutPaymentStep
            paymentMethod={paymentMethod}
            setPaymentMethod={setPaymentMethod}
            paymentInfo={paymentInfo}
            setPaymentInfo={setPaymentInfo}
            onBack={() => setCurrentStep(1)}
            onReview={() => setCurrentStep(3)}
          />
        )}

        {currentStep === 3 && (
          <CheckoutReviewStep
            shippingInfo={shippingInfo}
            paymentMethod={paymentMethod}
            paymentInfo={paymentInfo}
            cartItems={normalizedCartItems}
            subtotal={subtotal}
            appliedCoupon={appliedCoupon}
            couponDiscount={couponDiscount}
            deliveryCharge={finalDeliveryCharge}
            grandTotal={grandTotal}
            totalItems={totalItems}
            shipmentZone={selectedShipmentZone}
            onEditShipping={() => setCurrentStep(1)}
            onEditPayment={() => setCurrentStep(2)}
            onBack={() => setCurrentStep(2)}
            onPlaceOrder={handlePlaceOrder}
            isPlacingOrder={isPlacingOrder}
          />
        )}
      </div>
    </div>
  );
}
