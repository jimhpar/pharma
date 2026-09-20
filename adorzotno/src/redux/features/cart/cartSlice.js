import { createSlice } from "@reduxjs/toolkit";

export const GUEST_CART_STORAGE_KEY = "guest_cart";

export const getCartStorageKey = (userId) =>
  userId ? `${userId}_cart` : GUEST_CART_STORAGE_KEY;

export const getStoredCart = (storageKey = GUEST_CART_STORAGE_KEY) => {
  if (typeof window === "undefined") {
    return {
      cartItems: [],
      appliedCoupon: null,
      storageKey,
    };
  }

  try {
    const storedValue = window.localStorage.getItem(storageKey);
    if (!storedValue) {
      return {
        cartItems: [],
        appliedCoupon: null,
        storageKey,
      };
    }

    const parsedValue = JSON.parse(storedValue);

    return {
      cartItems: Array.isArray(parsedValue?.cartItems)
        ? parsedValue.cartItems
        : [],
      appliedCoupon: parsedValue?.appliedCoupon || null,
      storageKey,
    };
  } catch {
    return {
      cartItems: [],
      appliedCoupon: null,
      storageKey,
    };
  }
};

export const removeStoredCart = (storageKey) => {
  if (typeof window === "undefined" || !storageKey) return;
  window.localStorage.removeItem(storageKey);
};

const persistCart = (storageKey, cartState) => {
  if (typeof window === "undefined" || !storageKey) return;

  window.localStorage.setItem(storageKey, JSON.stringify(cartState));
};

const initialState = {
  cartItems: [],
  appliedCoupon: null,
  isCartOpen: false,
  isHydrated: false,
  storageKey: GUEST_CART_STORAGE_KEY,
};

const cartSlice = createSlice({
  name: "cart",
  initialState,
  reducers: {
    hydrateCart(state, action) {
      state.cartItems = Array.isArray(action.payload?.cartItems)
        ? action.payload.cartItems
        : [];
      state.appliedCoupon = action.payload?.appliedCoupon || null;
      state.storageKey = action.payload?.storageKey || GUEST_CART_STORAGE_KEY;
      state.isHydrated = true;
    },
    switchCartContext(state, action) {
      state.cartItems = Array.isArray(action.payload?.cartItems)
        ? action.payload.cartItems
        : [];
      state.appliedCoupon = action.payload?.appliedCoupon || null;
      state.storageKey = action.payload?.storageKey || GUEST_CART_STORAGE_KEY;
      state.isHydrated = true;
      persistCart(state.storageKey, {
        cartItems: state.cartItems,
        appliedCoupon: state.appliedCoupon,
      });
    },
    setCartItems(state, action) {
      state.cartItems = action.payload;
      state.isHydrated = true;
      persistCart(state.storageKey, {
        cartItems: state.cartItems,
        appliedCoupon: state.appliedCoupon,
      });
    },
    setAppliedCoupon(state, action) {
      state.appliedCoupon = action.payload || null;
      state.isHydrated = true;
      persistCart(state.storageKey, {
        cartItems: state.cartItems,
        appliedCoupon: state.appliedCoupon,
      });
    },
    setIsCartOpen(state, action) {
      state.isCartOpen = action.payload;
    },
    clearCart(state) {
      state.cartItems = [];
      state.appliedCoupon = null;
      state.isHydrated = true;
      persistCart(state.storageKey, {
        cartItems: [],
        appliedCoupon: null,
      });
    },
  },
});

export const {
  hydrateCart,
  switchCartContext,
  setCartItems,
  setAppliedCoupon,
  setIsCartOpen,
  clearCart,
} = cartSlice.actions;

export default cartSlice.reducer;
