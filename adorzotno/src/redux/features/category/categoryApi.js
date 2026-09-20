import baseApi from "../../baseApi";

export const categoryApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getCategories: builder.query({
      query: () => ({
        url: "/categories",
        method: "GET",
      }),
      transformResponse: (response) => response?.data?.categories || [],
    }),
    getCategoryProducts: builder.query({
      query: ({
        categorySlug,
        page = 1,
        perPage = 20,
        sortBy = "created_at",
        sortOrder = "desc",
      }) => ({
        url: `/categories/${categorySlug}/products`,
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
  }),
});

export const { useGetCategoriesQuery, useGetCategoryProductsQuery } = categoryApi;
