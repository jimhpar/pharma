import baseApi from "@/redux/baseApi";

export const settingsApi = baseApi.injectEndpoints({
    endpoints: (builder) => ({
        getShipmentZones: builder.query({
            query: () => "/shipment-zones",
            transformResponse: (response) =>
                response?.data?.shipment_zones?.filter(
                    (zone) => zone?.status === "active"
                ) || [],
        }),
    }),
});

export const { useGetShipmentZonesQuery } = settingsApi;

