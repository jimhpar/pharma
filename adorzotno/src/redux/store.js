import { configureStore } from "@reduxjs/toolkit";
import { baseApi } from "./baseApi";
import cartReducer from "./features/cart/cartSlice";
import authReducer from "./features/auth/authSlice";

export const store = configureStore({
    reducer: {
        auth: authReducer,
        cart: cartReducer,
        [baseApi.reducerPath]: baseApi.reducer,
    },
    middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(baseApi.middleware),
});

export default store;
