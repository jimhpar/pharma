"use client";

export const CHECKOUT_ORDER_STORAGE_KEY = "adorzotno_last_checkout_order";

export const storeCheckoutOrder = (orderData) => {
  if (typeof window === "undefined") return;

  window.localStorage.setItem(
    CHECKOUT_ORDER_STORAGE_KEY,
    JSON.stringify(orderData),
  );
};

export const getStoredCheckoutOrder = () => {
  if (typeof window === "undefined") return null;

  try {
    const storedValue = window.localStorage.getItem(CHECKOUT_ORDER_STORAGE_KEY);
    return storedValue ? JSON.parse(storedValue) : null;
  } catch {
    return null;
  }
};

