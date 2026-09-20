const baseFlashDeals = [
  {
    id: 101,
    name: "Multivitamin Bundle Pack",
    price: 24.99,
    originalPrice: 39.99,
    image: "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    discount: "38% OFF",
    dealEnds: "2 hours left",
    images: [
      "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    ],
  },
  {
    id: 102,
    name: "First Aid Emergency Kit",
    price: 34.99,
    originalPrice: 54.99,
    image: "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    discount: "36% OFF",
    dealEnds: "5 hours left",
    images: [
      "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    ],
  },
  {
    id: 104,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 105,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 106,
    name: "First Aid Emergency Kit",
    price: 34.99,
    originalPrice: 54.99,
    image: "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    discount: "36% OFF",
    dealEnds: "5 hours left",
    images: [
      "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    ],
  },
  {
    id: 107,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 108,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 109,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 110,
    name: "First Aid Emergency Kit",
    price: 34.99,
    originalPrice: 54.99,
    image: "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    discount: "36% OFF",
    dealEnds: "5 hours left",
    images: [
      "https://images.unsplash.com/photo-1603398938378-e54eab446dde?w=400&h=300&fit=crop",
    ],
  },
  {
    id: 111,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 112,
    name: "Multivitamin Bundle Pack",
    price: 24.99,
    originalPrice: 39.99,
    image: "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    discount: "38% OFF",
    dealEnds: "2 hours left",
    images: [
      "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    ],
  },
  {
    id: 113,
    name: "Pain Relief Value Pack",
    price: 19.99,
    originalPrice: 32.99,
    image: "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    discount: "39% OFF",
    dealEnds: "4 hours left",
    images: [
      "https://png.pngtree.com/png-vector/20240518/ourmid/pngtree-pharmacy-concept-with-medicines-png-image_12484673.png",
    ],
  },
  {
    id: 114,
    name: "Multivitamin Bundle Pack",
    price: 24.99,
    originalPrice: 39.99,
    image: "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    discount: "38% OFF",
    dealEnds: "2 hours left",
    images: [
      "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    ],
  },
  {
    id: 115,
    name: "Multivitamin Bundle Pack",
    price: 24.99,
    originalPrice: 39.99,
    image: "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    discount: "38% OFF",
    dealEnds: "2 hours left",
    images: [
      "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    ],
  },
  {
    id: 116,
    name: "Multivitamin Bundle Pack",
    price: 24.99,
    originalPrice: 39.99,
    image: "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    discount: "38% OFF",
    dealEnds: "2 hours left",
    images: [
      "https://images.rawpixel.com/image_800/cHJpdmF0ZS9sci9pbWFnZXMvd2Vic2l0ZS8yMDI1LTAzL3Jhd3BpeGVsb2ZmaWNlOF9hX2ZvaWxfcGFja19vZl8xMF93aGl0ZV90YWJsZXRzX2ZseWluZ19pbl90aGVfYV9kZWVmMzBjMi0wMzZjLTRhOWMtYjY1Zi0wMDZjZWZlMjMzYmZfMS5qcGc.jpg",
    ],
  },
];

const categories = [
  "Vitamins & Supplements",
  "First Aid",
  "Pain Relief",
  "Personal Care",
];

const flashDeals = baseFlashDeals.map((deal, index) => {
  const category = categories[index % categories.length];
  return {
    ...deal,
    category,
    rating: (4.3 + ((index % 5) * 0.1)).toFixed(1),
    reviews: 120 + index * 17,
    inStock: true,
    stockCount: 25 + index * 3,
    sku: `FD-${deal.id}`,
    manufacturer: "Demo Health Labs",
    description: `${deal.name} is a limited-time offer product under our ${category} lineup, selected for fast-moving seasonal demand.`,
    features: [
      "Limited-time flash deal pricing",
      "Trusted quality from verified suppliers",
      "Easy to reorder from your account",
      "Fast shipping support available",
    ],
    dosage:
      "Use as directed on the product label. Consult a healthcare professional for personalized advice.",
    warnings: [
      "Keep out of reach of children",
      "Do not exceed recommended usage",
      "Store in a cool and dry place",
    ],
  };
});

export default flashDeals;
