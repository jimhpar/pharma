"use client";

import React from "react";
import { IoStar, IoStarHalf, IoStarOutline } from "react-icons/io5";

const getRatingValue = (value) => {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) return 0;
  return Math.min(Math.max(parsed, 0), 5);
};

const getRoundedHalfRating = (value) => Math.round(value * 2) / 2;

const formatRating = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : "0.00";
};

export default function DemoRatingStars({
  rating = 0,
  showValue = true,
  showCount = true,
  countLabel = "(5)",
  className = "",
}) {
  const ratingValue = getRatingValue(rating);
  const roundedRatingValue = getRoundedHalfRating(ratingValue);

  return (
    <span className={`inline-flex items-center gap-1 text-xs text-gray-500 ${className}`}>
      <span className="inline-flex items-center text-orange-400">
        {Array.from({ length: 5 }).map((_, index) => {
          const starNumber = index + 1;
          const showFullStar = roundedRatingValue >= starNumber;
          const showHalfStar =
            !showFullStar && roundedRatingValue >= starNumber - 0.5;

          if (showFullStar) {
            return (
              <span
                key={index}
                className="inline-flex h-4 w-4 items-center justify-center md:h-[18px] md:w-[18px] 2xl:h-5 2xl:w-5"
              >
                <IoStar className="text-sm text-orange-400 md:text-base" />
              </span>
            );
          }

          if (showHalfStar) {
            return (
              <span
                key={index}
                className="inline-flex h-4 w-4 items-center justify-center md:h-[18px] md:w-[18px] 2xl:h-5 2xl:w-5"
              >
                <IoStarHalf className="text-sm text-orange-400 md:text-base" />
              </span>
            );
          }

          return (
            <span
              key={index}
              className="inline-flex h-4 w-4 items-center justify-center md:h-[18px] md:w-[18px] 2xl:h-5 2xl:w-5"
            >
              <IoStarOutline className="text-sm text-orange-400 md:text-base" />
            </span>
          );
        })}
      </span>
      {showValue ? <span>{formatRating(ratingValue)}</span> : null}
      {showCount ? <span className="max-sm:hidden">{countLabel}</span> : null}
    </span>
  );
}
