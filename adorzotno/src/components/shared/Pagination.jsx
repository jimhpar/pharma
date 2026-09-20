import React from "react";

export default function Pagination({
  currentPage = 1,
  totalPages = 0,
  hasPrev = false,
  hasNext = false,
  onPageChange = () => { },
}) {
  if (totalPages <= 1) {
    return null;
  }

  const buildVisiblePages = () => {
    if (totalPages <= 5) {
      return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const pages = new Set([
      1,
      totalPages,
      Math.max(currentPage - 1, 1),
      currentPage,
      Math.min(currentPage + 1, totalPages),
    ]);

    return Array.from(pages)
      .filter((page) => page >= 1 && page <= totalPages)
      .sort((a, b) => a - b);
  };

  const visiblePages = buildVisiblePages();

  return (
    <div className="mt-8 flex flex-wrap items-center justify-center gap-2">
      <button
        type="button"
        disabled={!hasPrev}
        onClick={() => onPageChange(currentPage - 1)}
        className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-50"
      >
        Previous
      </button>

      {visiblePages.map((page, index) => {
        const isActive = page === currentPage;
        const previousPage = visiblePages[index - 1];
        const showEllipsis = index > 0 && page - previousPage > 1;

        return (
          <React.Fragment key={page}>
            {showEllipsis ? (
              <span className="px-2 text-sm font-semibold text-slate-400">
                ...
              </span>
            ) : null}
            <button
              type="button"
              onClick={() => onPageChange(page)}
              className={`h-10 min-w-10 rounded-lg border px-3 text-sm font-semibold transition ${isActive
                ? "border-primary bg-primary text-white"
                : "border-slate-200 text-slate-600 hover:border-primary hover:text-primary"
                }`}
            >
              {page}
            </button>
          </React.Fragment>
        );
      })}

      <button
        type="button"
        disabled={!hasNext}
        onClick={() => onPageChange(currentPage + 1)}
        className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-50"
      >
        Next
      </button>
    </div>
  );
}
