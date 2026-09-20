export const AUTH_STORAGE_KEY = "adorzotno_auth";
export const AUTH_COOKIE_KEY = "adorzotno_token";

const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const parseCookieString = (cookieString = "") =>
  cookieString
    .split(";")
    .map((part) => part.trim())
    .filter(Boolean)
    .reduce((accumulator, part) => {
      const separatorIndex = part.indexOf("=");

      if (separatorIndex === -1) {
        accumulator[part] = "";
        return accumulator;
      }

      const key = part.slice(0, separatorIndex).trim();
      const value = part.slice(separatorIndex + 1).trim();
      accumulator[key] = value;
      return accumulator;
    }, {});

export const getTokenFromCookieString = (cookieString = "") => {
  const cookies = parseCookieString(cookieString);
  const token = cookies[AUTH_COOKIE_KEY];
  return token ? decodeURIComponent(token) : null;
};

export const getClientToken = () => {
  if (typeof document === "undefined") return null;
  return getTokenFromCookieString(document.cookie);
};

export const getStoredAuth = () => {
  if (typeof window === "undefined") {
    return {
      token: null,
      user: null,
    };
  }

  const token = getClientToken();

  try {
    const storedValue = window.localStorage.getItem(AUTH_STORAGE_KEY);
    const parsedValue = storedValue ? JSON.parse(storedValue) : null;

    return {
      token,
      user: token ? parsedValue?.user || null : null,
    };
  } catch {
    return {
      token,
      user: null,
    };
  }
};

export const persistAuthSession = ({ token, user }) => {
  if (typeof window === "undefined") return;

  window.localStorage.setItem(
    AUTH_STORAGE_KEY,
    JSON.stringify({
      user: user || null,
    }),
  );

  if (token) {
    document.cookie = `${AUTH_COOKIE_KEY}=${encodeURIComponent(token)}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax`;
  }
};

export const clearStoredAuthSession = () => {
  if (typeof window === "undefined") return;

  window.localStorage.removeItem(AUTH_STORAGE_KEY);
  document.cookie = `${AUTH_COOKIE_KEY}=; path=/; max-age=0; samesite=lax`;
};
