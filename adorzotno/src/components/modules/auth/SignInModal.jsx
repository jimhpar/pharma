"use client";

import React, { useState } from "react";
import { X } from "lucide-react";
import Image from "next/image";
import { toast } from "sonner";
import LoginForm from "./LoginForm";
import OtpForm from "./OtpForm";
import RegisterForm from "./RegisterForm";

export default function SignInModal({
  isSignInOpen,
  setSignInOpen,
  onLoginSuccess,
}) {
  const [loginMethod, setLoginMethod] = useState("email");
  const [isSignUp, setIsSignUp] = useState(false);
  const [otpSent, setOtpSent] = useState(false);
  const [contactInfo, setContactInfo] = useState({
    phoneNumber: "",
    email: "",
  });

  const handleAuthSubmit = (responseData) => {
    const loggedInUser = responseData?.user;

    setContactInfo({
      phoneNumber: loggedInUser?.phone || "",
      email: loggedInUser?.email || "",
    });
    setOtpSent(false);
    setIsSignUp(false);
    setSignInOpen(false);
    onLoginSuccess?.();
  };

  const handleRegisterSubmit = (responseData) => {
    const registeredUser = responseData?.user;

    setContactInfo({
      phoneNumber: registeredUser?.phone || "",
      email: registeredUser?.email || "",
    });
    setLoginMethod("email");
    setIsSignUp(false);
    setOtpSent(false);
  };

  const handleVerifyOTP = (otpValue) => {
    toast.success(`Verifying OTP: ${otpValue}`);
  };

  const handleResendOTP = () => {
    toast.success(`OTP resent to +88${contactInfo.phoneNumber}`);
  };

  const handleChangeNumber = () => {
    setOtpSent(false);
    setContactInfo((prev) => ({
      ...prev,
      phoneNumber: "",
    }));
  };

  return (
    <div>
      {isSignInOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50"
          onClick={() => setSignInOpen(false)}
        />
      )}

      {isSignInOpen && (
        <div
          className={`fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 transition-transform duration-300 ${isSignInOpen ? "translate-x-0" : "-translate-x-full"
            }`}
        >
          <div className="max-w-4xl w-full max-h-[95vh] overflow-y-auto rounded-2xl bg-white shadow-2xl animate-fadeIn">
            <div className="flex flex-col md:flex-row">
              <div className="relative hidden overflow-hidden bg-primary p-8 text-white md:block md:w-2/5">
                <button
                  onClick={() => setSignInOpen(false)}
                  className="absolute right-4 top-4 rounded-full p-2 transition hover:bg-white/20 md:hidden"
                >
                  <X size={24} />
                </button>

                <div className="mb-8">
                  <div className="flex items-center gap-2 mb-4">
                    <div className="">
                      <Image
                        src="/images/logo.png"
                        alt="adorzotno Logo"
                        width={60}
                        height={40}
                      />
                    </div>
                    <h1 className="text-3xl font-bold">AdorZotno</h1>
                  </div>
                  <p className="text-teal-100 text-lg">
                    দেশের সেবায় ডিজিটাল লেয়ার স্বাস্থ্য
                  </p>
                  <p className="text-white/90 mt-2">
                    এখন আপনার সকল সব মানের
                    <br />
                    ডিজিটাল হেলথ ফার্মেসি
                  </p>
                </div>

                <div className="mt-8 flex justify-center">
                  <Image
                    src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=300&h=500&fit=crop"
                    alt="Phone Mockup"
                    width={200}
                    height={400}
                    className="max-w-[200px] rounded-2xl border-4 border-white/20 shadow-xl"
                  />
                </div>

                <div className="absolute -bottom-10 -right-10 h-40 w-40 rounded-full bg-white/10"></div>
                <div className="absolute -left-10 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
              </div>

              <div className="relative p-8 md:w-3/5">
                <button
                  onClick={() => setSignInOpen(false)}
                  className="absolute right-4 top-4 rounded-full p-2 transition hover:bg-gray-100"
                >
                  <X size={24} className="text-gray-600" />
                </button>

                <div className="mx-auto max-w-md">
                  <div className="mb-8">
                    <h2 className="mb-2 text-3xl font-bold text-gray-800">
                      {otpSent && loginMethod === "phone"
                        ? "Verify OTP"
                        : isSignUp
                          ? "Create Account"
                          : "PLEASE LOG IN"}
                    </h2>
                    <p className="text-gray-600">
                      {otpSent && loginMethod === "phone"
                        ? `Enter the 6-digit code sent to +88${contactInfo.phoneNumber}`
                        : isSignUp
                          ? ""
                          : "Welcome back! Please enter your details"}
                    </p>
                  </div>

                  {otpSent && loginMethod === "phone" ? (
                    <OtpForm
                      onVerify={handleVerifyOTP}
                      onResend={handleResendOTP}
                      onChangeNumber={handleChangeNumber}
                    />
                  ) : isSignUp ? (
                    <RegisterForm
                      onSubmit={handleRegisterSubmit}
                      onToggleSignIn={() => setIsSignUp(false)}
                    />
                  ) : (
                    <LoginForm
                      loginMethod={loginMethod}
                      setLoginMethod={setLoginMethod}
                      onSubmit={handleAuthSubmit}
                      onToggleSignUp={() => setIsSignUp(true)}
                    />
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
