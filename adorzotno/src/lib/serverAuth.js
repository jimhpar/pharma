import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { AUTH_COOKIE_KEY } from "./authSession";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_URL;

export const buildSignInRedirect = (redirectPath) =>
  `/?signin=1&redirect=${encodeURIComponent(redirectPath)}`;

export async function requireAuthenticatedUser(redirectPath) {
  const cookieStore = await cookies();
  const token = cookieStore.get(AUTH_COOKIE_KEY)?.value?.trim();

  if (!token || !apiBaseUrl) {
    redirect(buildSignInRedirect(redirectPath));
  }

  try {
    const response = await fetch(`${apiBaseUrl}/auth/me`, {
      method: "GET",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });

    if (!response.ok) {
      redirect(buildSignInRedirect(redirectPath));
    }

    const payload = await response.json();
    const user = payload?.data?.user;

    if (!user) {
      redirect(buildSignInRedirect(redirectPath));
    }

    return user;
  } catch {
    redirect(buildSignInRedirect(redirectPath));
  }
}
