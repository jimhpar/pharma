"use client";

import React, { useState } from "react";
import { Eye, EyeOff, Lock, Mail, MoveRight, Phone, User } from "lucide-react";
import { useForm, useWatch } from "react-hook-form";
import { toast } from "sonner";
import { useRegisterMutation } from "@/redux/features/auth/authApi";

export default function RegisterForm({ onSubmit, onToggleSignIn }) {
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [registerUser, { isLoading }] = useRegisterMutation();

  const {
    register,
    handleSubmit,
    control,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: {
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      phone: "",
    },
  });

  const passwordValue = useWatch({
    control,
    name: "password",
  });

  const handleRegister = async (formData) => {
    try {
      const response = await registerUser(formData).unwrap();

      reset();
      toast.success(response?.message || "Registration successful");
      onSubmit?.(response?.data);
    } catch (error) {
      const errorMessage =
        error?.data?.message || "Registration failed. Please try again.";
      const fieldErrors = error?.data?.errors;

      if (fieldErrors && typeof fieldErrors === "object") {
        Object.entries(fieldErrors).forEach(([fieldName, messages]) => {
          setError(fieldName, {
            type: "server",
            message: Array.isArray(messages) ? messages[0] : messages,
          });
        });
      }

      toast.error(errorMessage);
    }
  };

  return (
    <>
      <form onSubmit={handleSubmit(handleRegister)}>
        <div className="mb-4">
          <label className="mb-1 block text-sm font-semibold text-gray-700">
            Full Name
          </label>
          <div className="relative">
            <input
              type="text"
              placeholder="John Doe"
              className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-11 transition focus:border-teal-500 focus:outline-none"
              {...register("name", {
                required: "Name is required",
              })}
            />
            <User
              size={18}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
            />
          </div>
          {errors.name && (
            <p className="mt-1 text-sm text-red-500">{errors.name.message}</p>
          )}
        </div>

        <div className="mb-4">
          <label className="mb-1 block text-sm font-semibold text-gray-700">
            Email Address
          </label>
          <div className="relative">
            <input
              type="email"
              placeholder="john@example.com"
              className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-11 transition focus:border-teal-500 focus:outline-none"
              {...register("email", {
                required: "Email is required",
                pattern: {
                  value: /\S+@\S+\.\S+/,
                  message: "Enter a valid email address",
                },
              })}
            />
            <Mail
              size={18}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
            />
          </div>
          {errors.email && (
            <p className="mt-1 text-sm text-red-500">{errors.email.message}</p>
          )}
        </div>

        <div className="mb-4">
          <label className="mb-1 block text-sm font-semibold text-gray-700">
            Phone Number
          </label>
          <div className="relative">
            <input
              type="tel"
              placeholder="01700000000"
              className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-[68px] transition focus:border-teal-500 focus:outline-none"
              maxLength="11"
              {...register("phone", {
                required: "Phone number is required",
                validate: (value) =>
                  /^01\d{9}$/.test(value) || "Enter a valid 11-digit phone number",
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
          {errors.phone && (
            <p className="mt-1 text-sm text-red-500">{errors.phone.message}</p>
          )}
        </div>

        <div className="mb-4">
          <label className="mb-1 block text-sm font-semibold text-gray-700">
            Password
          </label>
          <div className="relative">
            <input
              type={showPassword ? "text" : "password"}
              placeholder="password123"
              className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-11 pr-12 transition focus:border-teal-500 focus:outline-none"
              {...register("password", {
                required: "Password is required",
                minLength: {
                  value: 6,
                  message: "Password must be at least 6 characters",
                },
              })}
            />
            <Lock
              size={18}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
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
            <p className="mt-1 text-sm text-red-500">
              {errors.password.message}
            </p>
          )}
        </div>

        <div className="mb-4">
          <label className="mb-1 block text-sm font-semibold text-gray-700">
            Confirm Password
          </label>
          <div className="relative">
            <input
              type={showConfirmPassword ? "text" : "password"}
              placeholder="password123"
              className="w-full rounded-lg border-2 border-gray-200 px-4 py-2 pl-11 pr-12 transition focus:border-teal-500 focus:outline-none"
              {...register("password_confirmation", {
                required: "Please confirm your password",
                validate: (value) =>
                  value === passwordValue || "Passwords do not match",
              })}
            />
            <Lock
              size={18}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
            />
            <button
              type="button"
              onClick={() => setShowConfirmPassword((prev) => !prev)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
            >
              {showConfirmPassword ? <EyeOff size={20} /> : <Eye size={20} />}
            </button>
          </div>
          {errors.password_confirmation && (
            <p className="mt-1 text-sm text-red-500">
              {errors.password_confirmation.message}
            </p>
          )}
        </div>

        <div className="mb-6">
          <p className="text-xs text-gray-600">
            By continuing you agree to{" "}
            <a href="#" className="text-primary hover:underline">
              Terms & Conditions
            </a>
            ,{" "}
            <a href="#" className="text-primary hover:underline">
              Privacy Policy
            </a>{" "}
            &{" "}
            <a href="#" className="text-primary hover:underline">
              Refund-Return Policy
            </a>
          </p>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          className="group mb-4 flex w-full items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-primary via-primary to-secondary px-5 py-2 font-semibold text-white transition-all duration-300 hover:shadow-[0_14px_30px_rgba(14,165,233,0.10)] disabled:cursor-not-allowed disabled:opacity-70"
        >
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/18 ring-1 ring-white/25">
            <User size={16} />
          </span>
          <span>{isLoading ? "Creating Account..." : "Sign Up"}</span>
          <span className="text-lg transition-transform duration-300 group-hover:translate-x-0.5">
            <MoveRight strokeWidth={1.5} />
          </span>
        </button>
      </form>

      <div className="text-center text-sm">
        <p className="text-gray-600">
          Already have an account?{" "}
          <button
            type="button"
            onClick={onToggleSignIn}
            className="font-semibold text-primary hover:underline"
          >
            Sign In
          </button>
        </p>
      </div>
    </>
  );
}
