"use client";

import React, { useEffect } from "react";
import { Provider, useDispatch, useSelector } from "react-redux";
import { getStoredAuth } from "@/lib/authSession";
import { store } from "./store";
import { authApi } from "./features/auth/authApi";
import { hydrateAuth } from "./features/auth/authSlice";
import {
  getCartStorageKey,
  getStoredCart,
  hydrateCart,
  removeStoredCart,
  switchCartContext,
} from "./features/cart/cartSlice";

function AuthHydrator() {
  const dispatch = useDispatch();

  useEffect(() => {
    const storedAuth = getStoredAuth();
    dispatch(hydrateAuth(storedAuth));

    if (storedAuth?.token) {
      dispatch(authApi.endpoints.getMe.initiate(undefined, { forceRefetch: true }));
    }
  }, [dispatch]);

  return null;
}

function CartHydrator() {
  const dispatch = useDispatch();
  const { user, isAuthenticated, isHydrated } = useSelector((state) => state.auth);

  useEffect(() => {
    const storedCart = getStoredCart();
    dispatch(hydrateCart(storedCart));
  }, [dispatch]);

  useEffect(() => {
    if (!isHydrated) {
      return;
    }

    const userId = isAuthenticated ? user?.id : null;
    const storageKey = getCartStorageKey(userId);
    const storedCart = getStoredCart(storageKey);
    const guestCart = getStoredCart(getCartStorageKey(null));
    const shouldPromoteGuestCart =
      Boolean(userId) &&
      storedCart.cartItems.length === 0 &&
      guestCart.cartItems.length > 0;

    dispatch(
      switchCartContext({
        cartItems: shouldPromoteGuestCart
          ? guestCart.cartItems
          : storedCart.cartItems,
        storageKey,
      }),
    );

    if (shouldPromoteGuestCart) {
      removeStoredCart(getCartStorageKey(null));
    }
  }, [dispatch, isAuthenticated, isHydrated, user?.id]);

  return null;
}

export default function Providers({ children }) {
  return (
    <Provider store={store}>
      <AuthHydrator />
      <CartHydrator />
      {children}
    </Provider>
  );
}
