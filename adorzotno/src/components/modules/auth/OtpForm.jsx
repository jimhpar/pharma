"use client";

import React, { useState } from "react";
import { Lock } from "lucide-react";
import { toast } from "sonner";

export default function OtpForm({ onVerify, onResend, onChangeNumber }) {
  const [otp, setOtp] = useState(["", "", "", "", "", ""]);

  const handleOtpChange = (index, value) => {
    if (value.length > 1) return;

    const nextOtp = [...otp];
    nextOtp[index] = value;
    setOtp(nextOtp);

    if (value && index < 5) {
      const nextInput = document.getElementById(`otp-${index + 1}`);
      if (nextInput) nextInput.focus();
    }
  };

  const handleOtpKeyDown = (index, event) => {
    if (event.key === "Backspace" && !otp[index] && index > 0) {
      const prevInput = document.getElementById(`otp-${index - 1}`);
      if (prevInput) prevInput.focus();
    }
  };

  const handleVerify = () => {
    const otpValue = otp.join("");
    if (otpValue.length === 6) {
      onVerify(otpValue);
      return;
    }

    toast.error("Please enter complete OTP");
  };

  const handleResend = () => {
    setOtp(["", "", "", "", "", ""]);
    onResend();
  };

  const handleChangeNumberClick = () => {
    setOtp(["", "", "", "", "", ""]);
    onChangeNumber();
  };

  return (
    <>
      <div className="mb-6">
        <label className="mb-3 block text-sm font-semibold text-gray-700">
          Enter OTP
        </label>
        <div className="flex justify-center gap-2">
          {otp.map((digit, index) => (
            <input
              key={index}
              id={`otp-${index}`}
              type="text"
              inputMode="numeric"
              maxLength="1"
              value={digit}
              onChange={(e) =>
                handleOtpChange(index, e.target.value.replace(/[^0-9]/g, ""))
              }
              onKeyDown={(e) => handleOtpKeyDown(index, e)}
              className="h-12 w-12 rounded-lg border-2 border-gray-300 text-center text-xl font-bold transition focus:border-teal-500 focus:outline-none"
            />
          ))}
        </div>
      </div>

      <button
        type="button"
        onClick={handleVerify}
        className="group mb-4 flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-primary via-primary to-secondary px-5 py-2 font-semibold text-white transition-all duration-300 hover:shadow-[0_14px_30px_rgba(14,165,233,0.10)]"
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/18 ring-1 ring-white/25">
          <Lock size={16} />
        </span>
        <span>Verify OTP</span>
        <span className="text-lg transition-transform duration-300 group-hover:translate-x-0.5">
          →
        </span>
      </button>

      <div className="mb-4 text-center">
        <p className="mb-2 text-sm text-gray-600">Did not receive the code?</p>
        <button
          type="button"
          onClick={handleResend}
          className="font-semibold text-primary hover:underline"
        >
          Resend OTP
        </button>
      </div>

      <div className="text-center">
        <button
          type="button"
          onClick={handleChangeNumberClick}
          className="text-sm text-gray-600 hover:underline"
        >
          Change Phone Number
        </button>
      </div>
    </>
  );
}
