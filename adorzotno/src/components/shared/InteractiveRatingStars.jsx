"use client";

import React from "react";
import { Rating } from "react-simple-star-rating";

const getRoundedHalfRating = (value) => {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) return 0;
  return Math.min(Math.max(Math.round(parsed * 2) / 2, 0), 5);
};

export default function InteractiveRatingStars({
  value = 0,
  onChange,
  size = 26,
  showValue = true,
  readonly = false,
  className = "",
  fillColor = "#f1b345",
  emptyColor = "#cbd5e1",
}) {
  const safeValue = getRoundedHalfRating(value);

  return (
    <div
      className={`inline-flex flex-nowrap items-center align-middle gap-1 text-slate-600 ${className}`}
    >
      <div
        style={{
          direction: "ltr",
          fontFamily: "sans-serif",
          touchAction: "none",
        }}
        className="flex shrink-0 items-center leading-none"
      >
        <Rating
          key={safeValue}
          initialValue={safeValue}
          allowFraction
          readonly={readonly}
          transition
          onClick={(nextValue) => onChange?.(getRoundedHalfRating(nextValue))}
          SVGstyle={{ display: "inline" }}
          size={size}
          fillColor={fillColor}
          emptyColor={emptyColor}
        />
      </div>

      {showValue ? (
        <span className="flex shrink-0 items-center text-sm font-medium leading-none">
          ({safeValue.toFixed(1)})
        </span>
      ) : null}
    </div>
  );
}

export { getRoundedHalfRating };
