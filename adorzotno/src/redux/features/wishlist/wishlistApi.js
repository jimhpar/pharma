import baseApi from "../../baseApi";

export const wishlistApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getWishlist: builder.query({
      query: () => ({
        url: "/wishlist",
        method: "GET",
      }),
      providesTags: ["Wishlist"],
      transformResponse: (response) => response?.data?.items || [],
    }),
    addToWishlist: builder.mutation({
      query: ({ skuId }) => ({
        url: "/wishlist/add",
        method: "POST",
        body: {
          sku_id: skuId,
        },
      }),
      invalidatesTags: ["Wishlist"],
    }),
    removeFromWishlist: builder.mutation({
      query: ({ skuId }) => ({
        url: `/wishlist/${skuId}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Wishlist"],
    }),
  }),
});

export const {
  useGetWishlistQuery,
  useAddToWishlistMutation,
  useRemoveFromWishlistMutation,
} = wishlistApi;
