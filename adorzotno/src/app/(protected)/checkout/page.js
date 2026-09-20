import Checkout from "@/components/checkout/Checkout";
import { requireAuthenticatedUser } from "@/lib/serverAuth";

export const metadata = {
  title: "Checkout | Adorzotno Limited",
  description:
    "Secure checkout at Adorzotno Limited. Complete your order with safe payment options and fast delivery.",
  keywords: [
    "checkout",
    "adorzotno",
    "online pharmacy checkout",
    "secure payment",
    "medical products order",
  ],
  openGraph: {
    title: "Checkout | Adorzotno Limited",
    description:
      "Complete your healthcare purchase securely at Adorzotno Limited.",
    url: "https://adorzotno.health/checkout",
    siteName: "Adorzotno Limited",
    images: [
      {
        url: "/images/AdorzotnoLogo.png",
        width: 1200,
        height: 630,
        alt: "Adorzotno Limited Logo",
      },
    ],
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Checkout | Adorzotno Limited",
    description:
      "Safe and secure checkout for healthcare products at Adorzotno Limited.",
    images: ["/images/AdorzotnoLogo.png"],
  },
};

export default async function page() {
  const user = await requireAuthenticatedUser("/checkout");
  return <Checkout user={user} />;
}
