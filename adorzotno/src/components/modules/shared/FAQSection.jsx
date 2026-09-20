"use client";

import { ChevronDown, ChevronUp } from "lucide-react";
import React, { useState } from "react";
import { useGetFaqsQuery } from "@/redux/features/faq/faqApi";

export default function FAQSection() {
  const [openFAQ, setOpenFAQ] = useState(null);
  const { data: faqs = [], isLoading, isFetching } = useGetFaqsQuery();

  return (
    <div className="my-8">
      <div className="mb-8 text-center">
        <h2 className="mb-3 text-3xl font-bold text-gray-800">
          Frequently Asked Questions
        </h2>
        <p className="text-gray-600">Got questions? We have got answers!</p>
      </div>
      <div className="mx-auto">
        <div className="grid gap-4 md:grid-cols-2">
          {isLoading || isFetching
            ? Array.from({ length: 6 }).map((_, index) => (
              <div
                key={index}
                className="overflow-hidden rounded-lg border bg-white shadow-sm"
              >
                <div className="space-y-3 p-4">
                  <div className="h-5 w-3/4 animate-pulse rounded bg-slate-100" />
                  <div className="h-4 w-full animate-pulse rounded bg-slate-100" />
                  <div className="h-4 w-5/6 animate-pulse rounded bg-slate-100" />
                </div>
              </div>
            ))
            : faqs.map((faq, index) => (
              <div key={faq?.id || index}>
                <div className="overflow-hidden rounded-lg border bg-white shadow-sm">
                  <button
                    onClick={() => setOpenFAQ(openFAQ === index ? null : index)}
                    className="flex w-full items-center justify-between p-4 text-left transition hover:bg-blue-50"
                  >
                    <span className="pr-4 font-semibold text-gray-800">
                      {faq.question}
                    </span>
                    {openFAQ === index ? (
                      <ChevronUp
                        className="flex-shrink-0 text-teal-600"
                        size={24}
                      />
                    ) : (
                      <ChevronDown
                        className="flex-shrink-0 text-gray-400"
                        size={24}
                      />
                    )}
                  </button>
                  {openFAQ === index && (
                    <div className="border-t px-5 pb-5 text-gray-600 leading-relaxed">
                      <p className="pt-4">{faq.answer}</p>
                    </div>
                  )}
                </div>
              </div>
            ))}
        </div>
      </div>
    </div>
  );
}
