import baseApi from "../../baseApi";

export const bannerApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getBanners: builder.query({
      query: () => ({
        url: "/banners",
        method: "GET",
      }),
      transformResponse: (response) => response?.data?.banners || [],
    }),
  }),
});

export const { useGetBannersQuery } = bannerApi;
