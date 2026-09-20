"use client";

import { Search } from "lucide-react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import React, { useEffect, useRef } from "react";

export default function HeaderSearch() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const searchTimeoutRef = useRef(null);
  const inputRef = useRef(null);
  const currentQuery = searchParams.get("q") || "";

  useEffect(() => {
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
      searchTimeoutRef.current = null;
    }
  }, [pathname]);

  useEffect(() => {
    if (!inputRef.current) {
      return;
    }

    if (inputRef.current.value !== currentQuery) {
      inputRef.current.value = currentQuery;
    }
  }, [currentQuery]);

  const handleChange = (event) => {
    const nextQuery = event.target.value;

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    searchTimeoutRef.current = setTimeout(() => {
      const trimmedQuery = nextQuery.trim();
      if (!trimmedQuery) {
        if (pathname === "/search") {
          router.replace("/search");
        }
        return;
      }

      const nextSearchParams = new URLSearchParams();
      nextSearchParams.set("q", trimmedQuery);
      router.replace(`/search?${nextSearchParams.toString()}`);
    }, 350);
  };

  const runSearch = () => {
    const trimmedQuery = inputRef.current?.value?.trim() || "";

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
      searchTimeoutRef.current = null;
    }

    if (!trimmedQuery) {
      if (pathname === "/search") {
        router.replace("/search");
      }
      return;
    }

    const nextSearchParams = new URLSearchParams();
    nextSearchParams.set("q", trimmedQuery);
    router.replace(`/search?${nextSearchParams.toString()}`);
  };

  const handleBlur = () => {
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
      searchTimeoutRef.current = null;
    }
  };

  return (
    <div className="flex max-w-4xl flex-1">
      <div className="relative flex w-full">
        <input
          ref={inputRef}
          type="text"
          defaultValue={currentQuery}
          onChange={handleChange}
          onBlur={handleBlur}
          placeholder="Search for products..."
          className="w-full rounded-l-lg border border-primary/40 bg-blue-50/50 px-4 py-2 shadow-sm focus:outline-none sm:py-3.5"
        />

        <button
          type="button"
          onClick={runSearch}
          className="flex items-center justify-center rounded-r-lg bg-primary px-4 text-white disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500"
        >
          <Search size={20} />
        </button>
      </div>
    </div>
  );
}
