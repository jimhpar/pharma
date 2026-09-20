"use client";

import { Pencil, Trash2, X } from "lucide-react";
import React, { useMemo, useState } from "react";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import InteractiveRatingStars from "../shared/InteractiveRatingStars";
import { useGetOrdersQuery } from "@/redux/features/order/orderApi";
import {
  useCreateReviewMutation,
  useDeleteReviewMutation,
  useUpdateReviewMutation,
} from "@/redux/features/product/productApi";

const formatReviewDate = (value) => {
  if (!value) return "Recently";

  return new Date(value).toLocaleDateString("en-BD", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

export default function ProductReviewsTab({ selectedProduct }) {
  const { isAuthenticated, isHydrated, user } = useSelector((state) => state.auth);
  const reviewItems = useMemo(
    () => selectedProduct?.reviewItems || [],
    [selectedProduct?.reviewItems],
  );
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState("");
  const [editingReview, setEditingReview] = useState(null);
  const [editRating, setEditRating] = useState(5);
  const [editComment, setEditComment] = useState("");
  const [reviewToDelete, setReviewToDelete] = useState(null);
  const [hasPendingReviewSubmission, setHasPendingReviewSubmission] =
    useState(false);
  const [hiddenReviewIds, setHiddenReviewIds] = useState([]);
  const [createReview, { isLoading: isSubmittingReview }] =
    useCreateReviewMutation();
  const [updateReview, { isLoading: isUpdatingReview }] =
    useUpdateReviewMutation();
  const [deleteReview, { isLoading: isDeletingReview }] =
    useDeleteReviewMutation();

  const { data: ordersResponse } = useGetOrdersQuery(
    {
      page: 1,
      perPage: 100,
    },
    {
      skip: !isHydrated || !isAuthenticated || !selectedProduct?.id,
      refetchOnMountOrArgChange: true,
    },
  );

  const hasOrderedProduct = useMemo(() => {
    const orders = ordersResponse?.data || [];

    return orders.some((order) =>
      order?.items?.some((item) => {
        const orderedProduct = item?.sku?.product;
        return (
          Number(orderedProduct?.id) === Number(selectedProduct?.id) ||
          String(orderedProduct?.slug || "").toLowerCase() ===
          String(selectedProduct?.slug || "").toLowerCase()
        );
      }),
    );
  }, [ordersResponse?.data, selectedProduct?.id, selectedProduct?.slug]);

  const visibleReviewItems = useMemo(
    () =>
      reviewItems.filter(
        (review) => !hiddenReviewIds.includes(Number(review?.id)),
      ),
    [hiddenReviewIds, reviewItems],
  );

  const currentUserReview = useMemo(
    () =>
      visibleReviewItems.find(
        (review) =>
          Number(review?.customer?.user_id) === Number(user?.id) ||
          Number(review?.customer_id) === Number(user?.customer?.id),
      ) || null,
    [user?.customer?.id, user?.id, visibleReviewItems],
  );

  const handleSubmitReview = async (event) => {
    event.preventDefault();

    const trimmedComment = reviewComment.trim();

    if (!trimmedComment) {
      toast.error("Please write your review comment.");
      return;
    }

    try {
      const response = await createReview({
        product_slug: selectedProduct?.slug,
        rating: reviewRating,
        comment: trimmedComment,
      }).unwrap();

      toast.success(
        response?.message ||
        "Review submitted successfully. It will appear after admin approval.",
      );
      setHasPendingReviewSubmission(true);
      setReviewComment("");
      setReviewRating(5);
    } catch (error) {
      if (
        String(error?.data?.message || "").toLowerCase() ===
        "you have already reviewed this product"
      ) {
        setHasPendingReviewSubmission(true);
      }
      toast.error(error?.data?.message || "Could not submit your review.");
    }
  };

  const openEditModal = (review) => {
    setEditingReview(review);
    setEditRating(Number(review?.rating) || 5);
    setEditComment(review?.comment || "");
  };

  const closeEditModal = () => {
    setEditingReview(null);
    setEditRating(5);
    setEditComment("");
  };

  const openDeleteConfirmation = (review) => {
    setReviewToDelete(review);
  };

  const closeDeleteConfirmation = () => {
    setReviewToDelete(null);
  };

  const handleUpdateReview = async (event) => {
    event.preventDefault();

    const trimmedComment = editComment.trim();
    if (!editingReview?.id || !trimmedComment) {
      toast.error("Please write your review comment.");
      return;
    }

    try {
      const response = await updateReview({
        reviewId: editingReview.id,
        rating: editRating,
        comment: trimmedComment,
      }).unwrap();

      toast.success(
        response?.message ||
        "Review updated successfully. It will appear after admin approval.",
      );
      setHasPendingReviewSubmission(true);
      setHiddenReviewIds((prev) =>
        prev.includes(Number(editingReview.id))
          ? prev
          : [...prev, Number(editingReview.id)],
      );
      closeEditModal();
    } catch (error) {
      toast.error(error?.data?.message || "Could not update your review.");
    }
  };

  const handleDeleteReview = async (reviewId) => {
    try {
      const response = await deleteReview({ reviewId }).unwrap();
      toast.success(response?.message || "Review deleted successfully.");
      setHasPendingReviewSubmission(false);
      setHiddenReviewIds((prev) =>
        prev.includes(Number(reviewId)) ? prev : [...prev, Number(reviewId)],
      );
      closeDeleteConfirmation();
    } catch (error) {
      toast.error(error?.data?.message || "Could not delete your review.");
    }
  };

  return (
    <div>
      <h3 className="mb-4 text-xl font-bold text-gray-800">Customer Reviews</h3>

      {hasOrderedProduct && !currentUserReview && !hasPendingReviewSubmission ? (
        <form
          onSubmit={handleSubmitReview}
          className="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:p-5"
        >
          <div className="mb-4">
            <p className="text-base font-semibold text-slate-800">
              Write a Review
            </p>
            <p className="mt-1 text-sm text-slate-500">
              Thanks for ordering this product. Your review will appear after admin approval.
            </p>
          </div>

          <div className="mb-4">
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Your Rating
            </label>
            <InteractiveRatingStars
              value={reviewRating}
              onChange={setReviewRating}
            />

          </div>

          <div className="mb-4">
            <label
              htmlFor="review-comment"
              className="mb-2 block text-sm font-semibold text-slate-700"
            >
              Your Review
            </label>
            <textarea
              id="review-comment"
              value={reviewComment}
              onChange={(event) => setReviewComment(event.target.value)}
              rows={4}
              placeholder="Share your experience with this product"
              className="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
            />
          </div>

          <button
            type="submit"
            disabled={isSubmittingReview}
            className="rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-70"
          >
            {isSubmittingReview ? "Submitting..." : "Submit Review"}
          </button>
        </form>
      ) : null}

      {hasPendingReviewSubmission && !currentUserReview ? (
        <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 sm:p-5">
          <p className="text-base font-semibold text-slate-800">
            Review Submitted
          </p>
          <p className="mt-1 text-sm text-slate-600">
            Your review is pending admin approval, so it will not appear in the public review list yet.
          </p>
        </div>
      ) : null}

      {currentUserReview ? (
        <div className="mb-6 rounded-lg border border-primary/15 bg-primary/5 p-4 sm:p-5">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="text-base font-semibold text-slate-800">
                Your Review
              </p>
              <p className="mt-1 text-sm text-slate-500">
                You have already reviewed this product. You can edit or delete it.
              </p>
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => openEditModal(currentUserReview)}
                className="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-white px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary/5"
              >
                <Pencil size={16} />
                Edit
              </button>
              <button
                type="button"
                onClick={() => openDeleteConfirmation(currentUserReview)}
                disabled={isDeletingReview}
                className="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-70"
              >
                <Trash2 size={16} />
                Delete
              </button>
            </div>
          </div>
        </div>
      ) : null}

      {visibleReviewItems.length > 0 ? (
        <div className="space-y-4">
          {visibleReviewItems.map((review, index) => (
            <div
              key={review?.id || `${review?.customer?.name || review?.author}-${index}`}
              className="border-b pb-4 last:border-b-0"
            >
              <div className="mb-2 flex flex-wrap items-center gap-3">
                <InteractiveRatingStars
                  value={review?.rating}
                  readonly
                  showValue
                  size={18}
                  className="shrink-0"
                />
                <span className="font-semibold text-gray-800">
                  {review?.customer?.name ||
                    review?.author ||
                    review?.user?.name ||
                    "Customer"}
                </span>
                {Number(review?.id) === Number(currentUserReview?.id) ? (
                  <button
                    type="button"
                    onClick={() => openEditModal(review)}
                    className="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:text-secondary"
                  >
                    <Pencil size={14} />
                    Edit
                  </button>
                ) : null}
                {review?.is_verified_purchase ? (
                  <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                    Verified Purchase
                  </span>
                ) : null}
                <span className="text-sm text-gray-500">
                  {formatReviewDate(review?.approved_at || review?.created_at)}
                </span>
              </div>
              <p className="text-gray-600">
                {review?.comment || review?.review || "No review comment."}
              </p>
            </div>
          ))}
        </div>
      ) : (
        <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
          No reviews yet for this product.
        </div>
      )}

      {editingReview ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
            <div className="mb-5 flex items-start justify-between gap-4">
              <div>
                <h4 className="text-xl font-bold text-slate-800">Edit Review</h4>
                <p className="mt-1 text-sm text-slate-500">
                  Update your rating or comment. Changes will be pending approval again.
                </p>
              </div>
              <button
                type="button"
                onClick={closeEditModal}
                className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleUpdateReview}>
              <div className="mb-4">
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Your Rating
                </label>
                <InteractiveRatingStars
                  value={editRating}
                  onChange={setEditRating}
                />
              </div>

              <div className="mb-5">
                <label
                  htmlFor="edit-review-comment"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Your Review
                </label>
                <textarea
                  id="edit-review-comment"
                  value={editComment}
                  onChange={(event) => setEditComment(event.target.value)}
                  rows={4}
                  className="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                />
              </div>

              <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  onClick={closeEditModal}
                  className="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isUpdatingReview}
                  className="rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-70"
                >
                  {isUpdatingReview ? "Saving..." : "Save Changes"}
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {reviewToDelete ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
            <div className="mb-5">
              <h4 className="text-xl font-bold text-slate-800">
                Delete Review?
              </h4>
              <p className="mt-2 text-sm text-slate-500">
                Are you sure you want to delete your review? This action cannot
                be undone.
              </p>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={closeDeleteConfirmation}
                className="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => handleDeleteReview(reviewToDelete.id)}
                disabled={isDeletingReview}
                className="rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {isDeletingReview ? "Deleting..." : "Delete Review"}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
