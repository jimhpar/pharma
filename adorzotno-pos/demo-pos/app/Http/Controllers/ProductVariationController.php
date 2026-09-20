<?php

namespace App\Http\Controllers;

use App\Models\ProductTemp;
use App\Models\Sku;
use App\Models\Variation;
use App\Traits\ImageTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductVariationController extends Controller
{
    use ImageTrait;

    public function getVariationValues(Request $request)
    {
        $variationType = Variation::query()
            ->where('id', $request->type_id)
            ->value('type');

        $allVariation = Variation::query()
            ->where('type', $variationType)
            ->orderBy('value')
            ->get();

        return response()->json($allVariation);
    }

    public function tempStore(Request $request): JsonResponse
    {
        $validated = $this->validate($request, [
            'temp_id' => 'nullable|integer|min:1',
            'variation_sku_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_skus', 'sku_code'),
            ],
            'variation_barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_skus', 'barcode'),
            ],
            'variation_cost_price' => 'nullable|numeric|min:0',
            'variation_retail_price' => 'required|numeric|min:0',
            'variation_wholesale_price' => 'nullable|numeric|min:0',
            'minimum_selling_price' => 'nullable|numeric|min:0',
            'online_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'variation_units_per_strip' => 'nullable|integer|min:1|max:100000',
            'variation_medicine_unit_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'dosage_details' => 'nullable|string',
            'variation_specifications' => 'nullable|string',
            'variation_additional_description' => 'nullable|string',
            'variation_product_images' => 'nullable|array',
            'variation_product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'variation_value' => 'required|array|min:1',
            'variation_value.*' => 'required|exists:variations,id',
        ]);

        $tempId = !empty($validated['temp_id']) ? (int) $validated['temp_id'] : null;
        $variationTemp = $tempId ? $this->findOwnedTemp($tempId) : null;

        $this->assertTempIdentifiersAreUniqueWithinSession(
            trim((string) $validated['variation_sku_code']),
            trim((string) ($validated['variation_barcode'] ?? '')),
            $tempId
        );
        $this->assertMinimumSellingPrice(
            $validated['variation_retail_price'] ?? null,
            $validated['minimum_selling_price'] ?? null,
            'minimum_selling_price'
        );

        $imagePaths = $variationTemp?->variation_product_images ?? [];
        if ($request->hasFile('variation_product_images')) {
            foreach ($imagePaths as $imagePath) {
                $this->deleteImage($imagePath);
            }

            $imagePaths = [];
            foreach ($request->file('variation_product_images') as $image) {
                $imagePath = $this->save_image('productImage', $image);
                if ($imagePath) {
                    $imagePaths[] = $imagePath;
                }
            }
        }

        $attributes = [
            'variation_ids' => array_values(array_unique($validated['variation_value'])),
            'variation_product_images' => $imagePaths,
            'variation_sku_code' => $validated['variation_sku_code'],
            'variation_barcode' => $validated['variation_barcode'] ?? null,
            'variation_product_code' => $validated['variation_sku_code'],
            'variation_cost_price' => $validated['variation_cost_price'] ?? 0,
            'variation_retail_price' => $validated['variation_retail_price'],
            'variation_wholesale_price' => $validated['variation_wholesale_price'] ?? 0,
            'minimum_selling_price' => $validated['minimum_selling_price'] ?? 0,
            'online_price' => $validated['online_price'] ?? 0,
            'weight' => $validated['weight'] ?? 0,
            'variation_units_per_strip' => max(1, (int) ($validated['variation_units_per_strip'] ?? 1)),
            'variation_medicine_unit_price' => $validated['variation_medicine_unit_price'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'dosage_details' => $validated['dosage_details'] ?? null,
            'variation_specifications' => $validated['variation_specifications'] ?? null,
            'variation_additional_description' => $validated['variation_additional_description'] ?? null,
            'variation_stock_alert' => 0,
            'session_id' => session()->getId(),
        ];

        if ($variationTemp) {
            $variationTemp->update($attributes);
            $variationTemp->refresh();

            return response()->json([
                'success' => 'Variant temp updated successfully.',
                'variationTemp' => $variationTemp,
            ]);
        }

        $variationTemp = ProductTemp::query()->create($attributes);

        return response()->json([
            'success' => 'Variant temp saved successfully.',
            'variationTemp' => $variationTemp,
        ]);
    }

    public function tempList(Request $request)
    {
        $productVariation = ProductTemp::query()
            ->where('session_id', session()->getId())
            ->get()
            ->map(function (ProductTemp $item) {
                $item->variation_values = $item->variation_values;
                return $item;
            });

        return DataTables()->of($productVariation)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->make(true);
    }

    public function createOrUpdateSku(Request $request): JsonResponse
    {
        return $this->tempStore($request);
    }

    public function variationEdit(Request $request): JsonResponse
    {
        $tempId = (int) $request->get('temp_id');
        $variationTemp = $this->findOwnedTemp($tempId);

        if (!$variationTemp) {
            return response()->json(['error' => 'Variation not found'], 404);
        }

        $variationIds = collect($variationTemp->variation_ids ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $variationMap = Variation::query()
            ->whereIn('id', $variationIds)
            ->get(['id', 'type', 'value'])
            ->keyBy('id');

        $selectedVariations = collect($variationIds)
            ->map(fn ($id) => $variationMap->get($id))
            ->filter()
            ->values();

        return response()->json([
            'variation' => $variationTemp,
            'selected_variations' => $selectedVariations,
        ]);
    }

    public function variationDelete(Request $request): JsonResponse
    {
        $tempId = (int) ($request->get('temp_id') ?: $request->get('id'));

        if ($tempId <= 0) {
            return response()->json(['error' => 'Variation not found'], 404);
        }

        $request->merge(['temp_id' => $tempId]);

        return $this->tempvariationDelete($request);
    }

    public function tempvariationDelete(Request $request): JsonResponse
    {
        $validated = $this->validate($request, [
            'temp_id' => 'required|numeric|min:1',
        ]);

        $variationTemp = $this->findOwnedTemp((int) $validated['temp_id']);

        if (!$variationTemp) {
            return response()->json(['error' => 'Variation not found'], 404);
        }

        foreach (($variationTemp->variation_product_images ?? []) as $imagePath) {
            $this->deleteImage($imagePath);
        }

        $variationTemp->delete();

        return response()->json(['success' => 'Variation deleted successfully']);
    }

    private function findOwnedTemp(int $tempId): ?ProductTemp
    {
        if ($tempId <= 0) {
            return null;
        }

        return ProductTemp::query()
            ->where('id', $tempId)
            ->where('session_id', session()->getId())
            ->first();
    }

    private function assertTempIdentifiersAreUniqueWithinSession(string $skuCode, string $barcode = '', ?int $ignoreTempId = null): void
    {
        $errors = [];

        $skuQuery = ProductTemp::query()
            ->where('session_id', session()->getId())
            ->where('variation_sku_code', $skuCode);
        if ($ignoreTempId) {
            $skuQuery->where('id', '!=', $ignoreTempId);
        }
        if ($skuCode !== '' && $skuQuery->exists()) {
            $errors['variation_sku_code'] = ['Variant temp SKU code must be unique in this product draft.'];
        }

        if ($barcode !== '') {
            $barcodeQuery = ProductTemp::query()
                ->where('session_id', session()->getId())
                ->where('variation_barcode', $barcode);
            if ($ignoreTempId) {
                $barcodeQuery->where('id', '!=', $ignoreTempId);
            }
            if ($barcodeQuery->exists()) {
                $errors['variation_barcode'] = ['Variant temp barcode must be unique in this product draft.'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertMinimumSellingPrice($retailPrice, $minimumSellingPrice, string $field): void
    {
        if ($retailPrice === null || $retailPrice === '' || $minimumSellingPrice === null || $minimumSellingPrice === '') {
            return;
        }

        if ((float) $minimumSellingPrice > (float) $retailPrice) {
            throw ValidationException::withMessages([
                $field => ['Minimum selling price cannot exceed retail price.'],
            ]);
        }
    }
}
