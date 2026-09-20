"use client";

import { Eye, EyeOff } from "lucide-react";

export default function EmailLoginFields({
  register,
  errors,
  showPassword,
  setShowPassword,
}) {
  return (
    <>
      <div className="mb-4">
        <label className="mb-1 block text-sm font-semibold text-gray-700">
          Email Address
        </label>
        <input
          type="email"
          placeholder="your.email@example.com"
          className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 transition focus:border-teal-500 focus:outline-none"
          {...register("email", {
            required: "Email is required",
            pattern: {
              value: /\S+@\S+\.\S+/,
              message: "Enter a valid email address",
            },
          })}
        />
        {errors.email && (
          <p className="mt-1 text-sm text-red-500">{errors.email.message}</p>
        )}
      </div>

      <div className="mb-4">
        <label className="mb-1 block text-sm font-semibold text-gray-700">
          Password
        </label>
        <div className="relative">
          <input
            type={showPassword ? "text" : "password"}
            placeholder="Enter your password"
            className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pr-12 transition focus:border-teal-500 focus:outline-none"
            {...register("password", {
              required: "Password is required",
              minLength: {
                value: 6,
                message: "Password must be at least 6 characters",
              },
            })}
          />
          <button
            type="button"
            onClick={() => setShowPassword((prev) => !prev)}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
          >
            {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
          </button>
        </div>
        {errors.password && (
          <p className="mt-1 text-sm text-red-500">{errors.password.message}</p>
        )}
      </div>
    </>
  );
}
