"use client";

import { Phone } from "lucide-react";

export default function PhoneLoginFields({ register, errors }) {
  return (
    <div className="mb-4">
      <label className="mb-1 block text-sm font-semibold text-gray-700">
        Your Contact Number
      </label>

      <div className="relative flex gap-2">
        <input
          type="tel"
          placeholder="01XXXXXXXXX"
          className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-[68px] transition focus:border-teal-500 focus:outline-none"
          maxLength="11"
          {...register("phoneNumber", {
            validate: (value) =>
              !value || /^01\d{9}$/.test(value) || "Enter a valid 11-digit phone number",
            onChange: (e) => {
              e.target.value = e.target.value
                .replace(/[^0-9]/g, "")
                .slice(0, 11);
            },
          })}
        />
        <span className="absolute left-4 top-1/2 flex -translate-y-1/2 items-center gap-1 text-gray-400">
          <Phone size={18} />
          <span>+88</span>
        </span>
      </div>

      {errors.phoneNumber && (
        <p className="mt-1 text-sm text-red-500">{errors.phoneNumber.message}</p>
      )}

      <div className="mt-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600">
        Phone login design is kept here. API connection can be added later.
      </div>
    </div>
  );
}
