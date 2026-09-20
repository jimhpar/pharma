const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export const getAverageReviewRating = (reviews = []) => {
  const approvedRatings = reviews
    .filter((review) => review?.status === "approved")
    .map((review) => toNumber(review?.rating))
    .filter((rating) => rating > 0);

  if (approvedRatings.length === 0) {
    return 0;
  }

  const total = approvedRatings.reduce((sum, rating) => sum + rating, 0);
  return total / approvedRatings.length;
};

export const getProductRating = (product) => {
  const primarySku = product?.sku?.[0] || {};
  return toNumber(primarySku?.rating) || getAverageReviewRating(product?.reviews || []);
};
