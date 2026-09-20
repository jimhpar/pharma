const sanitizeValue = (value) => (typeof value === "string" ? value.trim() : "");

export const getNameParts = (fullName = "") => {
  const parts = sanitizeValue(fullName).split(/\s+/).filter(Boolean);

  return {
    firstName: parts[0] || "",
    lastName: parts.slice(1).join(" "),
  };
};

export const getDefaultShippingValues = (user) => {
  const customer = user?.customer || {};
  const { firstName, lastName } = getNameParts(user?.name || "");

  return {
    firstName,
    lastName,
    email: sanitizeValue(user?.email),
    phone: sanitizeValue(user?.phone),
    address: sanitizeValue(customer?.shipping_address),
    city: "",
    state: "",
    zipCode: "",
    notes: "",
  };
};

export const buildShippingAddress = ({
  address = "",
  city = "",
  state = "",
  zipCode = "",
}) => {
  const baseAddress = sanitizeValue(address);
  const locationSuffix = [sanitizeValue(city), sanitizeValue(state), sanitizeValue(zipCode)]
    .filter(Boolean)
    .join(" ");

  if (!baseAddress) {
    return locationSuffix;
  }

  return locationSuffix ? `${baseAddress}, ${locationSuffix}` : baseAddress;
};

