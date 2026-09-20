"use client";

import { Check } from "lucide-react";

const steps = [
  { id: 1, label: "Shipping" },
  { id: 2, label: "Payment" },
  { id: 3, label: "Review" },
];

export default function CheckoutProgressSteps({ currentStep }) {
  return (
    <div className="mb-5 pt-3 sm:mb-8 sm:pt-8">
      <div className="flex items-center justify-center">
        <div className="flex items-center">
          {steps.map((step, index) => (
            <div key={step.id} className="flex items-center">
              <div className="flex flex-col items-center gap-1.5 sm:flex-row sm:gap-2">
                <div
                  className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold sm:h-10 sm:w-10 sm:text-base ${currentStep >= step.id
                    ? "bg-primary text-white"
                    : "bg-gray-200 text-gray-500"
                    }`}
                >
                  {currentStep > step.id ? <Check size={16} className="sm:h-5 sm:w-5" /> : String(step.id)}
                </div>
                <span
                  className={`text-xs font-semibold sm:ml-2 sm:text-sm ${currentStep >= step.id ? "text-primary" : "text-gray-500"
                    }`}
                >
                  {step.label}
                </span>
              </div>

              {index < steps.length - 1 && (
                <div
                  className={`mx-1.5 h-0.5 w-8 sm:mx-2 sm:h-1 sm:w-16 ${currentStep >= step.id + 1 ? "bg-primary" : "bg-gray-200"
                    }`}
                />
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
