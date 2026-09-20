import React from "react";
import ProductCard from "../cards/ProductCard";
import Pagination from "../shared/Pagination";

export default function FilteredProductCard({
  filteredProducts,
  isLoading = false,
  pagination = null,
  currentPage = 1,
  onPageChange = () => { },
}) {
  const totalPages = pagination?.last_page || 0;

  return (
    <div>
      {isLoading ? (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
          {Array.from({ length: 12 }).map((_, index) => (
            <div
              key={index}
              className="overflow-hidden rounded-lg border border-slate-200 bg-white"
            >
              <div className="h-40 animate-pulse bg-slate-100 lg:h-44" />
              <div className="space-y-3 p-3">
                <div className="h-4 w-3/4 animate-pulse rounded bg-slate-100" />
                <div className="h-4 w-1/2 animate-pulse rounded bg-slate-100" />
                <div className="h-5 w-1/3 animate-pulse rounded bg-slate-100" />
              </div>
            </div>
          ))}
        </div>
      ) : filteredProducts?.length === 0 ? (
        <div className="py-16 text-center text-gray-500">No products found.</div>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
            {filteredProducts?.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>

          <Pagination
            currentPage={currentPage}
            totalPages={totalPages}
            hasPrev={Boolean(pagination?.prev_page_url)}
            hasNext={Boolean(pagination?.next_page_url)}
            onPageChange={onPageChange}
          />
        </>
      )}
    </div>
  );
}
