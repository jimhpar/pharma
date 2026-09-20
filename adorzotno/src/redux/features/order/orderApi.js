import baseApi from "@/redux/baseApi";

export const orderApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    checkoutOrder: builder.mutation({
      query: (payload) => ({
        url: "/orders/checkout",
        method: "POST",
        body: payload,
      }),
    }),
    getOrders: builder.query({
      query: ({ page = 1, perPage = 10 } = {}) => ({
        url: "/orders",
        method: "GET",
        params: {
          page,
          per_page: perPage,
        },
      }),
      providesTags: (result) => [
        { type: "Order", id: "LIST" },
        ...((result?.data || []).map((order) => ({
          type: "Order",
          id: order.id,
        }))),
      ],
      transformResponse: (response) => response?.data || null,
    }),
    getOrderDetails: builder.query({
      query: (orderId) => ({
        url: `/orders/${orderId}`,
        method: "GET",
      }),
      providesTags: (_, __, orderId) => [{ type: "Order", id: orderId }],
      transformResponse: (response) => response?.data?.order || null,
    }),
    cancelOrder: builder.mutation({
      query: (orderId) => ({
        url: `/orders/${orderId}/cancel`,
        method: "PUT",
      }),
      invalidatesTags: (_, __, orderId) => [
        { type: "Order", id: "LIST" },
        { type: "Order", id: orderId },
      ],
    }),
  }),
});

export const {
  useCheckoutOrderMutation,
  useGetOrdersQuery,
  useGetOrderDetailsQuery,
  useCancelOrderMutation,
} = orderApi;
