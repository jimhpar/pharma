"use client";

import React from "react";
import { FcGoogle } from "react-icons/fc";

export default function SocialLoginButtons() {
  return (
    <div className="mb-6 flex gap-3">
      <button
        type="button"
        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-gray-300 py-2 transition hover:bg-gray-50"
      >
        <svg className="h-5 w-5" viewBox="0 0 24 24">
          <path
            fill="#1877F2"
            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
          />
        </svg>
        <span className="font-semibold text-gray-700">Facebook</span>
      </button>
      <button
        type="button"
        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-gray-300 py-2 transition hover:bg-gray-50"
      >
        <FcGoogle className="h-5 w-5" />
        <span className="font-semibold text-gray-700">Google</span>
      </button>
    </div>
  );
}
