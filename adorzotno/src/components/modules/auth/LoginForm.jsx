"use client";

import React, { useState } from "react";
import { Lock, Mail, MoveRight, Phone } from "lucide-react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { useLoginMutation } from "@/redux/features/auth/authApi";
import EmailLoginFields from "./EmailLoginFields";
import PhoneLoginFields from "./PhoneLoginFields";
import SocialLoginButtons from "./SocialLoginButtons";

export default function LoginForm({
  loginMethod,
  setLoginMethod,
  onSubmit,
  onToggleSignUp,
}) {
  const [showPassword, setShowPassword] = useState(false);
  const [loginUser, { isLoading }] = useLoginMutation();
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: {
      phoneNumber: "",
      email: "",
      password: "",
    },
  });

  const isEmailLogin = loginMethod === "email";

  const handleLogin = async (formData) => {
    if (!isEmailLogin) {
      toast.info("Phone login API will be connected later.");
      return;
    }

    try {
      const response = await loginUser({
        email: formData.email,
        password: formData.password,
      }).unwrap();

      toast.success(response?.message || "Login successful");
      onSubmit?.(response?.data);

      if (response?.data?.is_admin && response?.data?.admin_redirect_url) {
        toast.info("Redirecting to Admin Dashboard...");
        window.location.href = response.data.admin_redirect_url;
        return;
      }
    } catch (error) {
      const errorMessage =
        error?.data?.message || "Login failed. Please try again.";

      setError("root", {
        type: "server",
        message: errorMessage,
      });

      toast.error(errorMessage);
    }
  };

  return (
    <>
      <div className="mb-6 flex gap-2">
        <button
          type="button"
          onClick={() => setLoginMethod("phone")}
          // className={`flex-1 rounded-lg px-4 py-2 font-semibold transition ${loginMethod === "phone"
          //   ? "bg-primary text-white"
          //   : "bg-gray-100 text-gray-600 hover:bg-gray-200"
          //   }`}
          disabled
          className="flex-1 cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 font-semibold text-gray-400"
        >
          <Phone size={18} className="mr-2 inline" />
          Phone
        </button>
        <button
          type="button"
          onClick={() => setLoginMethod("email")}
          className={`flex-1 rounded-lg px-4 py-2 font-semibold transition ${loginMethod === "email"
            ? "bg-primary text-white"
            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
        >
          <Mail size={18} className="mr-2 inline" />
          Email
        </button>
      </div>

      <form onSubmit={handleSubmit(handleLogin)}>
        {loginMethod === "phone" ? (
          <PhoneLoginFields register={register} errors={errors} />
        ) : (
          <EmailLoginFields
            register={register}
            errors={errors}
            showPassword={showPassword}
            setShowPassword={setShowPassword}
          />
        )}

        {errors.root?.message && (
          <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
            {errors.root.message}
          </div>
        )}

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
            {isEmailLogin ? <Lock size={16} /> : <Phone size={16} />}
          </span>
          <span>
            {isEmailLogin
              ? isLoading
                ? "Signing In..."
                : "Sign In"
              : "Continue with Phone"}
          </span>
          <span className="text-lg transition-transform duration-300 group-hover:translate-x-0.5">
            <MoveRight strokeWidth={1.5} />
          </span>
        </button>
      </form>

      <div className="mb-4 text-center">
        <button type="button" className="text-sm text-primary hover:underline">
          Forgot Password?
        </button>
      </div>

      <div className="relative my-4">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full border-t border-gray-300"></div>
        </div>
        <div className="relative flex justify-center text-sm">
          <span className="bg-white px-4 text-gray-500">or continue with</span>
        </div>
      </div>

      <SocialLoginButtons />

      <div className="text-center text-sm">
        <p className="text-gray-600">
          Don’t have an account?{" "}
          <button
            type="button"
            onClick={onToggleSignUp}
            className="font-semibold text-primary hover:underline"
          >
            Sign Up
          </button>
        </p>
      </div>
    </>
  );
}
