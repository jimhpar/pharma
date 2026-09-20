import { createSlice } from "@reduxjs/toolkit";
import {
    clearStoredAuthSession,
    getStoredAuth,
    persistAuthSession,
} from "@/lib/authSession";

const initialState = {
    token: null,
    user: null,
    isAuthenticated: false,
    isHydrated: false,
};

const authSlice = createSlice({
    name: "auth",
    initialState,
    reducers: {
        hydrateAuth: (state, action) => {
            state.token = action.payload?.token || null;
            state.user = action.payload?.user || null;
            state.isAuthenticated = Boolean(action.payload?.token);
            state.isHydrated = true;
        },
        setCredentials: (state, action) => {
            state.token = action.payload?.token || null;
            state.user = action.payload?.user || null;
            state.isAuthenticated = Boolean(action.payload?.token);
            state.isHydrated = true;

            persistAuthSession({
                token: state.token,
                user: state.user,
            });
        },
        clearAuth: (state) => {
            state.token = null;
            state.user = null;
            state.isAuthenticated = false;
            state.isHydrated = true;

            clearStoredAuthSession();
        },
    },
});

export const { hydrateAuth, setCredentials, clearAuth } = authSlice.actions;
export default authSlice.reducer;
