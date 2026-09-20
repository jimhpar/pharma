import baseApi from "@/redux/baseApi";

export const cartApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    applyCoupon: builder.mutation({
      query: ({ coupon_code, total_amount }) => ({
        url: "/cart/apply-coupon",
        method: "POST",
        body: {
          coupon_code,
          total_amount,
        },
      }),
    }),
  }),
});

export const { useApplyCouponMutation } = cartApi;
