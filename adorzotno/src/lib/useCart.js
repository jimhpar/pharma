'use client';
import { useDispatch, useSelector } from "react-redux";
import {
  clearCart as clearCartAction,
  setAppliedCoupon as setAppliedCouponAction,
  setCartItems as setCartItemsAction,
  setIsCartOpen as setIsCartOpenAction,
} from "../redux/features/cart/cartSlice";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const normalizeCartItem = (product, quantity = 1) => ({
  id: product.id,
  productId: product.productId || product.id,
  name: product.name,
  price: Number(product.price) || 0,
  quantity,
  image: product.image || product.images?.[0] || "",
  category: product.category || "",
  slug: product.slug || "",
  stockCount:
    toNumber(product.stockCount) ||
    toNumber(product.availableStock) ||
    toNumber(product.available_stock) ||
    null,
  inStock:
    typeof product.inStock === "boolean"
      ? product.inStock
      : typeof product.in_stock === "boolean"
        ? product.in_stock
        : true,
});

export function useCart() {
  const dispatch = useDispatch();
  const cartItems = useSelector((state) => state.cart.cartItems);
  const appliedCoupon = useSelector((state) => state.cart.appliedCoupon);
  const isCartOpen = useSelector((state) => state.cart.isCartOpen);
  const isHydrated = useSelector((state) => state.cart.isHydrated);

  const setCartItems = (valueOrUpdater) => {
    const nextValue =
      typeof valueOrUpdater === "function"
        ? valueOrUpdater(cartItems)
        : valueOrUpdater;
    dispatch(setCartItemsAction(nextValue));
  };

  const setIsCartOpen = (value) => {
    dispatch(setIsCartOpenAction(value));
  };

  const setAppliedCoupon = (value) => {
    dispatch(setAppliedCouponAction(value));
  };

  const addToCart = (product, quantity = 1) => {
    const normalizedQuantity = Math.max(Number(quantity) || 1, 1);
    const normalizedProduct = normalizeCartItem(product, normalizedQuantity);

    if (normalizedProduct.inStock === false) {
      return { success: false, reason: "out_of_stock" };
    }

    const existingItem = cartItems.find(
      (item) => Number(item.id) === Number(normalizedProduct.id),
    );

    const nextCartItems = existingItem
      ? cartItems.map((item) => {
          if (Number(item.id) !== Number(normalizedProduct.id)) {
            return item;
          }

          const maxAllowedQuantity = item.stockCount || normalizedProduct.stockCount;
          const nextQuantity = item.quantity + normalizedQuantity;

          return {
            ...item,
            quantity:
              maxAllowedQuantity && maxAllowedQuantity > 0
                ? Math.min(nextQuantity, maxAllowedQuantity)
                : nextQuantity,
          };
        })
      : [...cartItems, normalizedProduct];

    dispatch(setCartItemsAction(nextCartItems));
    if (appliedCoupon) {
      dispatch(setAppliedCouponAction(null));
    }

    dispatch(setIsCartOpenAction(true));
    return { success: true, cartItems: nextCartItems };
  };

  const updateQuantity = (id, quantity) => {
    const nextQuantity = Number(quantity) || 0;

    if (nextQuantity < 1) {
      dispatch(
        setCartItemsAction(
          cartItems.filter((item) => Number(item.id) !== Number(id)),
        ),
      );
      if (appliedCoupon) {
        dispatch(setAppliedCouponAction(null));
      }
      return { success: true };
    }

    dispatch(
      setCartItemsAction(
        cartItems.map((item) => {
          if (Number(item.id) !== Number(id)) {
            return item;
          }

          const maxAllowedQuantity = item.stockCount;
          return {
            ...item,
            quantity:
              maxAllowedQuantity && maxAllowedQuantity > 0
                ? Math.min(nextQuantity, maxAllowedQuantity)
                : nextQuantity,
          };
        }),
      ),
    );
    if (appliedCoupon) {
      dispatch(setAppliedCouponAction(null));
    }

    return { success: true };
  };

  const removeItem = (id) => {
    dispatch(
      setCartItemsAction(cartItems.filter((item) => Number(item.id) !== Number(id))),
    );
    if (appliedCoupon) {
      dispatch(setAppliedCouponAction(null));
    }
    return { success: true };
  };

  const clearCart = () => {
    dispatch(clearCartAction());
  };

  const getItemQuantity = (id) => {
    return cartItems.find((item) => Number(item.id) === Number(id))?.quantity ?? 0;
  };

  return {
    cartItems,
    appliedCoupon,
    isHydrated,
    setCartItems,
    isCartOpen,
    setIsCartOpen,
    setAppliedCoupon,
    addToCart,
    updateQuantity,
    removeItem,
    clearCart,
    getItemQuantity,
  };
}
