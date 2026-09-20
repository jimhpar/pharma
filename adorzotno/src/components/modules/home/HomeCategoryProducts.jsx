"use client";

import React, { useMemo } from "react";
import { useGetCategoriesQuery } from "@/redux/features/category/categoryApi";
import CategoryProduct from "./CategoryProductSlider";
import HomeCategoryBannerSectionOne from "./HomeCategoryBannerSectionOne";
import HomeCategoryBannerSectionTwo from "./HomeCategoryBannerSectionTwo";

const getSortedParentCategories = (categories = []) =>
  categories
    .filter(
      (category) =>
        category?.status === "active" && (category?.parent_id === null || category?.parent_id === undefined),
    )
    .sort((a, b) => (a?.sort_order || 0) - (b?.sort_order || 0));

export default function HomeCategoryProducts() {
  const { data: apiCategories = [] } = useGetCategoriesQuery();

  const categories = useMemo(
    () => getSortedParentCategories(apiCategories).slice(0, 8),
    [apiCategories],
  );

  const firstCategoryGroup = categories.slice(0, 3);
  const secondCategoryGroup = categories.slice(3, 6);
  const thirdCategoryGroup = categories.slice(6, 8);

  return (
    <div>
      {firstCategoryGroup.map((category) => (
        <CategoryProduct key={category.id} category={category} />
      ))}

      <HomeCategoryBannerSectionOne />

      {secondCategoryGroup.map((category) => (
        <CategoryProduct key={category.id} category={category} />
      ))}

      <HomeCategoryBannerSectionTwo />

      {thirdCategoryGroup.map((category) => (
        <CategoryProduct key={category.id} category={category} />
      ))}
    </div>
  );
}
