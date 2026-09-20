import { getImageUrl } from "./imageHelpers";
import { getProductRating } from "./getProductRating";

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export const mapApiProductToCard = (product) => {
  const primarySku = product?.sku?.[0] || {};
  const skuSellingPrice = toNumber(primarySku?.selling_price);
  const productSellingPrice = toNumber(product?.selling_price);
  const basePrice = skuSellingPrice || productSellingPrice;
  const discountType = product?.default_discount_type;
  const discountValue = toNumber(product?.default_discount_value);

  let originalPrice = basePrice > 0 ? basePrice : null;
  let price = basePrice;
  let discountAmount = 0;
  let discountLabel = null;

  if (basePrice > 0 && discountValue > 0) {
    if (discountType === "amount") {
      discountAmount = Math.min(discountValue, basePrice);
      price = Math.max(basePrice - discountAmount, 0);
      discountLabel = `\u09F3${discountAmount.toFixed(0)} off`;
    } else if (discountType === "percent" && discountValue < 100) {
      discountAmount = (basePrice * discountValue) / 100;
      price = Math.max(basePrice - discountAmount, 0);
      discountLabel = `${Math.round(discountValue)}% off`;
    }
  }

  const imagePath = product?.thumbnail_image;

  return {
    id: product?.id,
    skuId: primarySku?.id || product?.sku_id || null,
    slug: product?.slug,
    name: product?.name,
    rating: getProductRating(product),
    price,
    originalPrice:
      originalPrice && originalPrice > price ? originalPrice : null,
    discountAmount: discountAmount > 0 ? discountAmount : null,
    discountLabel,
    images: [
      imagePath
        ? getImageUrl(imagePath)
        : "/images/no-image-available.png",
    ],
    brand: product?.brand?.name || "",
    category: product?.category?.name || "",
    inStock:
      Boolean(product?.in_stock) ||
      Boolean(primarySku?.in_stock) ||
      Boolean(product?.stock?.in_stock) ||
      Boolean(primarySku?.stock?.in_stock) ||
      toNumber(product?.available_stock) > 0 ||
      toNumber(primarySku?.available_stock) > 0,
  };
};
