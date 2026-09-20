"use client";

import { ChevronRight, Truck } from "lucide-react";
import { useEffect } from "react";
import { useForm } from "react-hook-form";
import {
  buildShippingAddress,
  getDefaultShippingValues,
} from "@/lib/checkoutUtils";

const inputClassName =
  "w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-secondary sm:px-4 sm:py-3";

export default function CheckoutShippingStep({ user, onSubmit }) {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    defaultValues: getDefaultShippingValues(user),
  });

  useEffect(() => {
    reset(getDefaultShippingValues(user));
  }, [reset, user]);

  const handleShippingSubmit = (formData) => {
    onSubmit({
      ...formData,
      fullAddress: buildShippingAddress(formData),
    });
  };

  return (
    <div className="rounded-lg bg-white p-4 shadow-md sm:p-6">
      <div className="mb-5 flex items-center gap-2.5 sm:mb-6 sm:gap-3">
        <Truck className="h-6 w-6 text-primary sm:h-7 sm:w-7" />
        <h2 className="text-lg font-bold text-gray-800 sm:text-2xl">Shipping Information</h2>
      </div>

      <form onSubmit={handleSubmit(handleShippingSubmit)}>
        <div className="mb-5 grid gap-4 sm:mb-6 sm:gap-6 md:grid-cols-2">
          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              First Name *
            </label>
            <input
              type="text"
              placeholder="John"
              className={inputClassName}
              {...register("firstName", {
                required: "First name is required",
              })}
            />
            {errors.firstName && (
              <p className="mt-1.5 text-xs text-red-500 sm:mt-2 sm:text-sm">{errors.firstName.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              Last Name *
            </label>
            <input
              type="text"
              placeholder="Doe"
              className={inputClassName}
              {...register("lastName", {
                required: "Last name is required",
              })}
            />
            {errors.lastName && (
              <p className="mt-1.5 text-xs text-red-500 sm:mt-2 sm:text-sm">{errors.lastName.message}</p>
            )}
          </div>
        </div>

        <div className="mb-5 grid gap-4 sm:mb-6 sm:gap-6 md:grid-cols-2">
          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              Email Address *
            </label>
            <input
              type="email"
              placeholder="john@example.com"
              className={inputClassName}
              {...register("email", {
                required: "Email is required",
                pattern: {
                  value: /\S+@\S+\.\S+/,
                  message: "Enter a valid email address",
                },
              })}
            />
            {errors.email && (
              <p className="mt-1.5 text-xs text-red-500 sm:mt-2 sm:text-sm">{errors.email.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              Phone Number *
            </label>
            <input
              type="tel"
              placeholder="01XXXXXXXXX"
              className={inputClassName}
              {...register("phone", {
                required: "Phone number is required",
                validate: (value) =>
                  /^01\d{9}$/.test(value) || "Enter a valid 11-digit phone number",
                onChange: (event) => {
                  event.target.value = event.target.value
                    .replace(/[^0-9]/g, "")
                    .slice(0, 11);
                },
              })}
            />
            {errors.phone && (
              <p className="mt-1.5 text-xs text-red-500 sm:mt-2 sm:text-sm">{errors.phone.message}</p>
            )}
          </div>
        </div>

        <div className="mb-5 sm:mb-6">
          <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
            Shipping Address *
          </label>
          <input
            type="text"
            placeholder="House 1, Street 1, Ward 1"
            className={inputClassName}
            {...register("address", {
              required: "Shipping address is required",
            })}
          />
          {errors.address && (
            <p className="mt-1.5 text-xs text-red-500 sm:mt-2 sm:text-sm">{errors.address.message}</p>
          )}
        </div>

        <div className="mb-5 grid gap-4 sm:mb-6 sm:gap-6 md:grid-cols-3">
          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              City
            </label>
            <input
              type="text"
              placeholder="Dhaka"
              className={inputClassName}
              {...register("city")}
            />
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              State
            </label>
            <input
              type="text"
              placeholder="Dhaka Division"
              className={inputClassName}
              {...register("state")}
            />
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
              ZIP Code
            </label>
            <input
              type="text"
              placeholder="1219"
              className={inputClassName}
              {...register("zipCode")}
            />
          </div>
        </div>

        <div className="mb-5 sm:mb-6">
          <label className="mb-1.5 block text-xs font-semibold text-gray-700 sm:mb-2 sm:text-sm">
            Delivery Notes
          </label>
          <textarea
            rows={3}
            placeholder="Please deliver in the morning"
            className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-secondary sm:px-4 sm:py-3"
            {...register("notes")}
          />
        </div>

        <button
          type="submit"
          className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary py-3 text-sm font-semibold text-white transition hover:bg-secondary sm:py-4 sm:text-base"
        >
          Continue to Payment
          <ChevronRight size={18} className="sm:h-5 sm:w-5" />
        </button>
      </form>
    </div>
  );
}
