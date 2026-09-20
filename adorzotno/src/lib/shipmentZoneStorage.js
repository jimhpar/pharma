"use client";

import { useEffect, useMemo, useState } from "react";

export const SHIPMENT_ZONE_STORAGE_KEY = "adorzotno_shipment_zone_id";
const SHIPMENT_ZONE_EVENT = "adorzotno:shipment-zone-change";

const getWindowObject = () =>
  typeof window === "undefined" ? null : window;

export const getStoredShipmentZoneId = () => {
  const windowObject = getWindowObject();
  if (!windowObject) return null;

  const storedValue = windowObject.localStorage.getItem(
    SHIPMENT_ZONE_STORAGE_KEY,
  );
  const parsedValue = Number(storedValue);

  return Number.isFinite(parsedValue) ? parsedValue : null;
};

export const setStoredShipmentZoneId = (shipmentZoneId) => {
  const windowObject = getWindowObject();
  if (!windowObject) return;

  windowObject.localStorage.setItem(
    SHIPMENT_ZONE_STORAGE_KEY,
    String(shipmentZoneId),
  );
  windowObject.dispatchEvent(
    new CustomEvent(SHIPMENT_ZONE_EVENT, {
      detail: { shipmentZoneId },
    }),
  );
};

export const useSelectedShipmentZone = (locations = []) => {
  const [selectedShipmentZoneId, setSelectedShipmentZoneIdState] = useState(
    () => getStoredShipmentZoneId(),
  );

  useEffect(() => {
    const windowObject = getWindowObject();
    if (!windowObject) return undefined;

    const handleShipmentZoneChange = (event) => {
      const nextId = Number(event?.detail?.shipmentZoneId);
      setSelectedShipmentZoneIdState(Number.isFinite(nextId) ? nextId : null);
    };

    const handleStorage = (event) => {
      if (event.key !== SHIPMENT_ZONE_STORAGE_KEY) return;
      setSelectedShipmentZoneIdState(getStoredShipmentZoneId());
    };

    windowObject.addEventListener(SHIPMENT_ZONE_EVENT, handleShipmentZoneChange);
    windowObject.addEventListener("storage", handleStorage);

    return () => {
      windowObject.removeEventListener(
        SHIPMENT_ZONE_EVENT,
        handleShipmentZoneChange,
      );
      windowObject.removeEventListener("storage", handleStorage);
    };
  }, []);

  useEffect(() => {
    if (!locations.length) return;

    const hasStoredSelection = locations.some(
      (location) => Number(location.id) === Number(selectedShipmentZoneId),
    );

    if (!hasStoredSelection) {
      const fallbackZoneId = locations[0]?.id;

      if (fallbackZoneId) {
        setStoredShipmentZoneId(fallbackZoneId);
      }
    }
  }, [locations, selectedShipmentZoneId]);

  const selectedShipmentZone = useMemo(
    () =>
      locations.find(
        (location) => Number(location.id) === Number(selectedShipmentZoneId),
      ) || locations[0] || null,
    [locations, selectedShipmentZoneId],
  );

  return {
    selectedShipmentZoneId: selectedShipmentZone?.id || null,
    selectedShipmentZone,
    setSelectedShipmentZoneId: setStoredShipmentZoneId,
  };
};
