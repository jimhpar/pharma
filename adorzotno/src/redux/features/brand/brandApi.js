import baseApi from "../../baseApi";

export const brandApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getBrands: builder.query({
      query: () => ({
        url: "/brands",
        method: "GET",
      }),
      transformResponse: (response) => response?.data?.brands || [],
    }),
    getBrandProducts: builder.query({
      query: ({
        brandSlug,
        page = 1,
        perPage = 20,
        sortBy = "most_popular",
      }) => ({
        url: `/brands/${brandSlug}/products`,
        method: "GET",
        params: {
          page,
          per_page: perPage,
          sort_by: sortBy,
        },
      }),
      transformResponse: (response) => response?.data || {},
    }),
  }),
});

export const { useGetBrandsQuery, useGetBrandProductsQuery } = brandApi;
