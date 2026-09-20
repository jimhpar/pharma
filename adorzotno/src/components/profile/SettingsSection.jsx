"use client";

import { Eye, EyeOff, Lock } from "lucide-react";
import { useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { toast } from "sonner";
import { useChangePasswordMutation } from "@/redux/features/auth/authApi";

const inputClassName =
  "h-12 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary/10";

export default function SettingsSection({ customer }) {
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [changePassword, { isLoading }] = useChangePasswordMutation();

  const {
    register,
    handleSubmit,
    control,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: {
      current: "",
      new: "",
      confirm: "",
    },
  });

  const newPasswordValue = useWatch({
    control,
    name: "new",
  });

  const handlePasswordUpdate = async (formData) => {
    try {
      const payload = {
        current: formData.current,
        new: formData.new,
        confirm: formData.confirm,
      };

      const response = await changePassword(payload).unwrap();
      reset();
      toast.success(response?.message || "Password changed successfully");
    } catch (error) {
      const errorMessage =
        error?.data?.message || "Password change failed. Please try again.";
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
            Settings
          </p>
          <h2 className="mt-2 text-xl font-bold text-slate-800 sm:text-3xl">
            Change Password
          </h2>
          <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Keep your account secure by updating your password whenever needed.
          </p>
        </div>

        <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:max-w-[220px]">
          <p className="font-semibold text-slate-800">Membership</p>
          <p className="mt-1">
            {customer?.is_member ? "Active member" : "Standard customer"}
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit(handlePasswordUpdate)} className="space-y-5 sm:space-y-6">
        <div className="grid gap-4 sm:gap-5 md:grid-cols-2">
          <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Current Password
            </label>
            <div className="relative">
              <Lock
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type={showCurrentPassword ? "text" : "password"}
                placeholder="Enter current password"
                className={`${inputClassName} pl-11 pr-12`}
                {...register("current", {
                  required: "Current password is required",
                })}
              />
              <button
                type="button"
                onClick={() => setShowCurrentPassword((prev) => !prev)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
              >
                {showCurrentPassword ? <EyeOff size={20} /> : <Eye size={20} />}
              </button>
            </div>
            {errors.current && (
              <p className="mt-1 text-sm text-red-500">{errors.current.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              New Password
            </label>
            <div className="relative">
              <Lock
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type={showNewPassword ? "text" : "password"}
                placeholder="Enter new password"
                className={`${inputClassName} pl-11 pr-12`}
                {...register("new", {
                  required: "New password is required",
                  minLength: {
                    value: 6,
                    message: "Password must be at least 6 characters",
                  },
                })}
              />
              <button
                type="button"
                onClick={() => setShowNewPassword((prev) => !prev)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
              >
                {showNewPassword ? <EyeOff size={20} /> : <Eye size={20} />}
              </button>
            </div>
            {errors.new && (
              <p className="mt-1 text-sm text-red-500">{errors.new.message}</p>
            )}
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">
              Confirm New Password
            </label>
            <div className="relative">
              <Lock
                size={18}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type={showConfirmPassword ? "text" : "password"}
                placeholder="Confirm new password"
                className={`${inputClassName} pl-11 pr-12`}
                {...register("confirm", {
                  required: "Please confirm your new password",
                  validate: (value) =>
                    value === newPasswordValue || "Passwords do not match",
                })}
              />
              <button
                type="button"
                onClick={() => setShowConfirmPassword((prev) => !prev)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
              >
                {showConfirmPassword ? <EyeOff size={20} /> : <Eye size={20} />}
              </button>
            </div>
            {errors.confirm && (
              <p className="mt-1 text-sm text-red-500">{errors.confirm.message}</p>
            )}
          </div>
        </div>

        <div className="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:pt-6 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm leading-6 text-slate-500">
            Use a strong password with a mix of letters, numbers, and symbols.
          </p>
          <button
            type="submit"
            disabled={isLoading}
            className="inline-flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
          >
            {isLoading ? "Updating..." : "Update Password"}
          </button>
        </div>
      </form>
    </div>
  );
}
