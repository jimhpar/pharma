"use client"

import React, { Suspense, useState } from "react";
import "../../app/globals.css";
import Header from "./Header";
import SideBar from "./SideBar";
import Footer from "./Footer";
import CartOffcanvas from "./CartOffcanvas";
import ScrollToTop from "../ScrollToTop";
import StickyCartButton from "../StickyCartButton";
import LiveChatWidget from "./LiveChatWidget";
import MobileNav from "./MobileNav";

export default function MainLayout({ children }) {
  const [chatOpen, setChatOpen] = useState(false);
  const [chatExpanded, setChatExpanded] = useState(false);

  return (
    <div className="flex flex-col min-h-screen">
      <Suspense fallback={<div className="sticky top-0 z-50 h-[74px] w-full bg-white shadow-md" />}>
        <Header />
      </Suspense>
      <CartOffcanvas />
      <div className="container mx-auto relative flex-1">
        <div className="flex lg:gap-0 items-start">
          {/* Sidebar - LEFT SIDE ON DESKTOP */}
          {/* <SideBar /> */}

          {/* Main Content */}
          <main className="relative min-w-0 flex-1 overflow-x-hidden border-blue-50 px-4 py-6 pb-24 md:pb-6">
            {children}

            <ScrollToTop />
            <StickyCartButton />
            <MobileNav />
            <LiveChatWidget
              chatOpen={chatOpen}
              setChatOpen={setChatOpen}
              chatExpanded={chatExpanded}
            />

          </main>
        </div>
      </div>
      <Footer />
    </div>
  );
}
