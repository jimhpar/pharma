import baseApi from "../../baseApi";
import { clearAuth, setCredentials } from "./authSlice";

const getErrorStatus = (error) => error?.error?.status || error?.status || null;

export const authApi = baseApi.injectEndpoints({
    endpoints: (builder) => ({
        register: builder.mutation({
            query: (payload) => ({
                url: "/auth/register",
                method: "POST",
                body: payload,
            }),
        }),
        login: builder.mutation({
            query: (payload) => ({
                url: "/auth/login",
                method: "POST",
                body: payload,
            }),
            async onQueryStarted(_, { dispatch, queryFulfilled }) {
                try {
                    const { data } = await queryFulfilled;
                    const user = data?.data?.user || null;
                    const token = data?.data?.token || null;

                    if (token) {
                        dispatch(baseApi.util.resetApiState());
                        dispatch(
                            setCredentials({
                                user,
                                token,
                            }),
                        );

                        dispatch(authApi.endpoints.getMe.initiate(undefined, { forceRefetch: true }));
                    }
                } catch {
                    // Errors are handled in the consuming UI.
                }
            },
        }),
        getMe: builder.query({
            query: () => ({
                url: "/auth/me",
                method: "GET",
            }),
            async onQueryStarted(_, { dispatch, getState, queryFulfilled }) {
                try {
                    const { data } = await queryFulfilled;
                    const currentToken = getState()?.auth?.token || null;

                    if (currentToken && data?.data?.user) {
                        dispatch(
                            setCredentials({
                                user: data.data.user,
                                token: currentToken,
                            }),
                        );
                    }
                } catch (error) {
                    const status = getErrorStatus(error);

                    if (status === 401 || status === 403) {
                        dispatch(clearAuth());
                    }
                }
            },
        }),
        updateProfile: builder.mutation({
            query: (payload) => ({
                url: "/auth/update-profile",
                method: "POST",
                body: payload,
            }),
            async onQueryStarted(_, { dispatch, getState, queryFulfilled }) {
                try {
                    const { data } = await queryFulfilled;
                    const currentToken = getState()?.auth?.token || null;

                    if (currentToken && data?.data?.user) {
                        dispatch(
                            setCredentials({
                                user: data.data.user,
                                token: currentToken,
                            }),
                        );
                    }
                } catch {
                    // Errors are handled in the consuming UI.
                }
            },
        }),
        changePassword: builder.mutation({
            query: (payload) => ({
                url: "/auth/change-password",
                method: "POST",
                body: payload,
            }),
        }),
        logout: builder.mutation({
            query: () => ({
                url: "/auth/logout",
                method: "POST",
            }),
            async onQueryStarted(_, { dispatch, queryFulfilled }) {
                try {
                    await queryFulfilled;
                    dispatch(baseApi.util.resetApiState());
                    dispatch(clearAuth());
                } catch {
                    // Errors are handled in the consuming UI.
                }
            },
        }),
    }),
});

export const {
    useRegisterMutation,
    useLoginMutation,
    useGetMeQuery,
    useLazyGetMeQuery,
    useUpdateProfileMutation,
    useChangePasswordMutation,
    useLogoutMutation,
} = authApi;
