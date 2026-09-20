// app/layout.jsx
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import MainLayout from "@/components/layout/MainLayout";
import Providers from "@/redux/Providers";
import { Toaster } from "sonner";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata = {
  title: "Adorzotno Limited | Medicine E-commerce App",
  description:
    "Secure contact at Adorzotno Limited. Complete your order with safe payment options and fast delivery.",
  keywords: [
    "contact",
    "adorzotno",
    "online pharmacy contact",
    "secure payment",
    "medical products order",
  ],
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased relative bg-white`}
      >
        <Providers>
          <MainLayout>
            {children}
          </MainLayout>
          <Toaster position="top-center" richColors />
        </Providers>
      </body>
    </html>
  );
}
