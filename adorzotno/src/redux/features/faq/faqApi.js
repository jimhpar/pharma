import baseApi from "../../baseApi";

export const faqApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getFaqs: builder.query({
      query: () => ({
        url: "/faqs",
        method: "GET",
      }),
      transformResponse: (response) => response?.data?.faqs || [],
    }),
  }),
});

export const { useGetFaqsQuery } = faqApi;
