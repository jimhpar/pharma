import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_URL || "/";

const getApiOrigin = () => {
    try {
        return new URL(apiBaseUrl).origin;
    } catch {
        return "";
    }
};

const getXsrfTokenFromCookie = () => {
    if (typeof document === "undefined") return null;

    const xsrfCookie = document.cookie
        .split("; ")
        .find((cookie) => cookie.startsWith("XSRF-TOKEN="));

    if (!xsrfCookie) return null;

    return decodeURIComponent(xsrfCookie.split("=").slice(1).join("="));
};

const rawBaseQuery = fetchBaseQuery({
    baseUrl: apiBaseUrl,
    credentials: "include",
    prepareHeaders: (headers, { getState }) => {
        headers.set("Accept", "application/json");
        headers.set("X-Requested-With", "XMLHttpRequest");

        const xsrfToken = getXsrfTokenFromCookie();
        if (xsrfToken) {
            headers.set("X-XSRF-TOKEN", xsrfToken);
        }

        const stateToken = getState?.()?.auth?.token;
        if (stateToken) {
            headers.set("Authorization", `Bearer ${stateToken}`);
        }

        return headers;
    },
});

const baseQueryWithCsrf = async (args, api, extraOptions) => {
    const method =
        typeof args === "string"
            ? "GET"
            : (args?.method || "GET").toUpperCase();

    if (["POST", "PUT", "PATCH", "DELETE"].includes(method)) {
        const apiOrigin = getApiOrigin();
        const xsrfToken = getXsrfTokenFromCookie();

        if (apiOrigin && !xsrfToken) {
            await fetch(`${apiOrigin}/sanctum/csrf-cookie`, {
                method: "GET",
                credentials: "include",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
        }
    }

    return rawBaseQuery(args, api, extraOptions);
};

export const baseApi = createApi({
    reducerPath: "baseApi",
    baseQuery: baseQueryWithCsrf,
    tagTypes: ["Wishlist", "Product", "Order"],
    endpoints: () => ({}),
});

export default baseApi;
