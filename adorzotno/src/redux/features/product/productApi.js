import baseApi from "../../baseApi";

export const productApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getProduct: builder.query({
      query: ({ productSlug }) => ({
        url: `/products/${productSlug}`,
        method: "GET",
      }),
      providesTags: (_, __, { productSlug }) => [
        { type: "Product", id: productSlug },
      ],
      transformResponse: (response) => response?.data?.product || null,
    }),
    getFlashDeals: builder.query({
      query: ({
        page = 1,
        perPage = 20,
        sortBy = "created_at",
        sortOrder = "desc",
      } = {}) => ({
        url: "/products/flash-deals",
        method: "GET",
        params: {
          page,
          per_page: perPage,
          sort_by: sortBy,
          sort_order: sortOrder,
        },
      }),
      transformResponse: (response) => response?.data || {},
    }),
    getFeaturedDeals: builder.query({
      query: ({
        page = 1,
        perPage = 20,
        sortBy = "created_at",
        sortOrder = "desc",
      } = {}) => ({
        url: "/products/featured",
        method: "GET",
        params: {
          page,
          per_page: perPage,
          sort_by: sortBy,
          sort_order: sortOrder,
        },
      }),
      transformResponse: (response) => response?.data || {},
    }),
    getTrendingProducts: builder.query({
      query: ({ limit = 10 } = {}) => ({
        url: "/products/trending",
        method: "GET",
        params: {
          limit,
        },
      }),
      transformResponse: (response) => response?.data?.products || [],
    }),
    getRelatedProducts: builder.query({
      query: ({ productSlug }) => ({
        url: `/products/related/${productSlug}`,
        method: "GET",
      }),
      transformResponse: (response) => response?.data?.related || [],
    }),
    getProductsByGenericName: builder.query({
      query: ({ genericName, perPage = 20 }) => ({
        url: `/products/generic-name/${genericName}`,
        method: "GET",
        params: {
          per_page: perPage,
        },
      }),
      transformResponse: (response) => response?.data || {},
    }),
    searchProducts: builder.query({
      query: ({
        query,
        page = 1,
        perPage = 20,
      } = {}) => ({
        url: "/products/search",
        method: "GET",
        params: {
          page,
          per_page: perPage,
          q: query,
        },
      }),
      transformResponse: (response) => response?.data || {},
    }),
    createReview: builder.mutation({
      query: (payload) => ({
        url: "/reviews",
        method: "POST",
        body: payload,
      }),
    }),
    updateReview: builder.mutation({
      query: ({ reviewId, ...payload }) => ({
        url: `/reviews/${reviewId}`,
        method: "PUT",
        body: payload,
      }),
    }),
    deleteReview: builder.mutation({
      query: ({ reviewId }) => ({
        url: `/reviews/${reviewId}`,
        method: "DELETE",
      }),
    }),
  }),
});

export const {
  useGetProductQuery,
  useGetFlashDealsQuery,
  useGetFeaturedDealsQuery,
  useGetTrendingProductsQuery,
  useGetRelatedProductsQuery,
  useGetProductsByGenericNameQuery,
  useSearchProductsQuery,
  useCreateReviewMutation,
  useUpdateReviewMutation,
  useDeleteReviewMutation,
} = productApi;
