import { NextResponse } from "next/server";
import { AUTH_COOKIE_KEY } from "@/lib/authSession";

export function middleware(request) {
    const authToken = request.cookies.get(AUTH_COOKIE_KEY)?.value?.trim();

    if (!authToken) {
        const homeUrl = new URL("/", request.url);
        homeUrl.searchParams.set("signin", "1");
        homeUrl.searchParams.set("redirect", request.nextUrl.pathname);

        return NextResponse.redirect(homeUrl);
    }

    return NextResponse.next();
}

export const config = {
    matcher: ["/profile/:path*", "/checkout/:path*"],
};
