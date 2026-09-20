"use client";

import { Mail, MapPin, Phone, User } from "lucide-react";
import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { useUpdateProfileMutation } from "@/redux/features/auth/authApi";

const inputClassName =
  "h-12 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary/10";
const disabledInputClassName =
  "h-12 w-full rounded-xl border border-gray-200 bg-slate-50 px-4 text-sm text-slate-500 outline-none cursor-not-allowed";

const textareaClassName =
  "w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary/10";

export default function ProfileSection({ user, customer }) {
  const [updateProfile, { isLoading }] = useUpdateProfileMutation();
  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: {
      name: user?.name || "",
      phone: user?.phone || "",
      email: user?.email || "",
      billing_address: customer?.billing_address || "",
      shipping_address: customer?.shipping_address || "",
      customer_code: customer?.customer_code || "",
    },
  });

  useEffect(() => {
    reset({
      name: user?.name || "",
      phone: user?.phone || "",
      email: user?.email || "",
      billing_address: customer?.billing_address || "",
      shipping_address: customer?.shipping_address || "",
      customer_code: customer?.customer_code || "",
    });
  }, [
    customer?.billing_address,
    customer?.customer_code,
    customer?.shipping_address,
    reset,
    user?.email,
    user?.name,
    user?.phone,
  ]);

  const handleProfileUpdate = async (formData) => {
    try {
      const payload = {
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        billing_address: formData.billing_address,
        shipping_address: formData.shipping_address,
      };

      const response = await updateProfile(payload).unwrap();
      toast.success(response?.message || "Profile updated successfully");
    } catch (error) {
      const errorMessage =
        error?.data?.message || "Profile update failed. Please try again.";
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
    <div className="rounded-[24px] border border-gray-200 bg-white p-4 sm:rounded-[30px] sm:p-7 lg:p-8">
      <div className="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-5 sm:mb-8 sm:pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary sm:text-sm sm:tracking-[0.2em]">
            Personal Information
          </p>
          <h2 className="mt-2 text-xl font-bold text-slate-800 sm:text-3xl">
            Manage Your Account Details
          </h2>
          <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Review your personal information, contact details, and saved addresses in one place.
          </p>
        </div>

        <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:max-w-[220px]">
          <p className="font-semibold text-slate-800">Membership</p>
          <p className="mt-1">
            {customer?.is_member ? "Active member" : "Standard customer"}
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit(handleProfileUpdate)} className="space-y-5 sm:space-y-6">
        <div className="grid gap-4 sm:gap-5 md:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Full Name
            </label>
            <div className="relative">
              <User
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type="text"
                className={`${inputClassName} pl-11`}
                {...register("name", {
                  required: "Name is required",
                })}
              />
            </div>
            {errors.name && (
              <p className="mt-1 text-sm text-red-500">{errors.name.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Phone Number
            </label>
            <div className="relative">
              <Phone
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type="tel"
                className={`${inputClassName} pl-11`}
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
            </div>
            {errors.phone && (
              <p className="mt-1 text-sm text-red-500">{errors.phone.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Email
            </label>
            <div className="relative">
              <Mail
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type="email"
                className={`${inputClassName} pl-11`}
                {...register("email", {
                  required: "Email is required",
                  pattern: {
                    value: /\S+@\S+\.\S+/,
                    message: "Enter a valid email address",
                  },
                })}
              />
            </div>
            {errors.email && (
              <p className="mt-1 text-sm text-red-500">{errors.email.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Customer Code
            </label>
            <input
              type="text"
              className={disabledInputClassName}
              disabled
              {...register("customer_code")}
            />
          </div>

          <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Billing Address
            </label>
            <div className="relative">
              <MapPin
                size={18}
                className="pointer-events-none absolute left-4 top-4 text-slate-400"
              />
              <textarea
                rows={4}
                placeholder="Add your billing address"
                className={`${textareaClassName} pl-11`}
                {...register("billing_address")}
              />
            </div>
          </div>

          <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Shipping Address
            </label>
            <div className="relative">
              <MapPin
                size={18}
                className="pointer-events-none absolute left-4 top-4 text-slate-400"
              />
              <textarea
                rows={4}
                placeholder="Add your shipping address"
                className={`${textareaClassName} pl-11`}
                {...register("shipping_address")}
              />
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:pt-6 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm leading-6 text-slate-500">
            Keep your account information up to date for a smoother checkout experience.
          </p>
          <button
            type="submit"
            disabled={isLoading}
            className="inline-flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
          >
            {isLoading ? "Saving..." : "Save Changes"}
          </button>
        </div>
      </form>
    </div>
  );
}
