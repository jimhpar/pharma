<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchPrice;
use App\Models\Product;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BranchPriceController extends Controller
{
    public function show()
    {
        return view('branch_price.index');
    }

    public function list()
    {
        $branchPrices = $this->branchPriceQueryForUser(auth()->user())
            ->with(['branch', 'sku.product'])
            ->orderBy('branch_id')
            ->orderBy('sku_id');

        return DataTables()->of($branchPrices)
            ->addColumn('branch_name', fn (BranchPrice $branchPrice) => $branchPrice->branch?->name ?? 'N/A')
            ->addColumn('product_name', fn (BranchPrice $branchPrice) => $branchPrice->sku?->product?->name ?? 'N/A')
            ->addColumn('sku_code', fn (BranchPrice $branchPrice) => $branchPrice->sku?->sku_code ?? 'N/A')
            ->addColumn('status_badge', function (BranchPrice $branchPrice) {
                if ($branchPrice->is_active) {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->editColumn('retail_price', fn (BranchPrice $branchPrice) => $this->formatPrice($branchPrice->retail_price))
            ->editColumn('wholesale_price', fn (BranchPrice $branchPrice) => $this->formatPrice($branchPrice->wholesale_price))
            ->editColumn('minimum_selling_price', fn (BranchPrice $branchPrice) => $this->formatPrice($branchPrice->minimum_selling_price))
            ->editColumn('online_price', fn (BranchPrice $branchPrice) => $this->formatPrice($branchPrice->online_price))
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        [$branches, $products] = $this->getFormOptions();

        return view('branch_price.create', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateBranchPrice($request);

        BranchPrice::query()->create($validated);

        return redirect()->route('branchPrice.show')->with('success', 'Branch price created successfully.');
    }

    public function edit($id)
    {
        $branchPrice = $this->branchPriceQueryForUser(auth()->user())
            ->with(['sku.product'])
            ->findOrFail($id);

        [$branches, $products] = $this->getFormOptions($branchPrice->sku_id);

        return view('branch_price.edit', compact('branchPrice', 'branches', 'products'));
    }

    public function update(Request $request, $id)
    {
        $branchPrice = $this->branchPriceQueryForUser(auth()->user())
            ->findOrFail($id);
        $validated = $this->validateBranchPrice($request, $branchPrice->id);

        $branchPrice->update($validated);

        Session::flash('success', 'Branch price updated successfully.');

        return redirect()->route('branchPrice.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $branchPrice = $this->branchPriceQueryForUser(auth()->user())
            ->find($request->filled('id'));

        if ($branchPrice === null) {
            return response()->json(['message' => 'Branch price not found.'], 404);
        }

        $branchPrice->delete();

        return response()->json(['success' => 'Branch price deleted successfully.']);
    }

    private function branchPriceQueryForUser(?User $user): Builder
    {
        $query = BranchPrice::query();
        if ($user?->hasRoleSlug('super-admin')) {
            return $query;
        }

        return $query->whereIn('branch_id', $this->branchContext()->accessibleBranchIds($user));
    }

    private function getFormOptions(?int $selectedSkuId = null): array
    {
        $branches = $this->branchContext()->accessibleBranches(auth()->user());

        $products = Product::query()
            ->with(['sku' => function ($skuQuery) use ($selectedSkuId) {
                $skuQuery->orderBy('sku_code');

                if ($selectedSkuId !== null) {
                    $skuQuery->where(function ($query) use ($selectedSkuId) {
                        $query->where('status', 'active')
                            ->orWhere('id', $selectedSkuId);
                    });
                } else {
                    $skuQuery->where('status', 'active');
                }
            }])
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => $product->sku->isNotEmpty())
            ->values();

        return [$branches, $products];
    }

    private function validateBranchPrice(Request $request, ?int $branchPriceId = null): array
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'sku_id' => [
                'required',
                'exists:product_skus,id',
                Rule::unique('branch_prices', 'sku_id')
                    ->ignore($branchPriceId)
                    ->where(fn ($query) => $query->where('branch_id', $request->input('branch_id'))),
            ],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_selling_price' => ['nullable', 'numeric', 'min:0'],
            'online_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (!$this->branchContext()->hasBranchAccess(auth()->user(), (int) $validated['branch_id'])) {
            abort(403, 'You do not have access to the selected branch.');
        }

        $validator = validator($validated, []);
        $this->addPricePresenceRule($validator);
        $this->addProductSkuConsistencyRule($validator, $validated);
        $validator->validate();

        return [
            'branch_id' => (int) $validated['branch_id'],
            'sku_id' => (int) $validated['sku_id'],
            'retail_price' => $this->normalizeNullablePrice($validated['retail_price'] ?? null),
            'wholesale_price' => $this->normalizeNullablePrice($validated['wholesale_price'] ?? null),
            'minimum_selling_price' => $this->normalizeNullablePrice($validated['minimum_selling_price'] ?? null),
            'online_price' => $this->normalizeNullablePrice($validated['online_price'] ?? null),
            'is_active' => $validated['status'] === 'active',
        ];
    }

    private function addPricePresenceRule(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $priceFields = [
                request()->input('retail_price'),
                request()->input('wholesale_price'),
                request()->input('minimum_selling_price'),
                request()->input('online_price'),
            ];

            $hasAtLeastOnePrice = collect($priceFields)->contains(function ($value) {
                return $value !== null && $value !== '';
            });

            if (!$hasAtLeastOnePrice) {
                $validator->errors()->add('retail_price', 'Enter at least one branch-specific price.');
            }
        });
    }

    private function addProductSkuConsistencyRule(Validator $validator, array $validated): void
    {
        $validator->after(function (Validator $validator) use ($validated) {
            $productId = $validated['product_id'] ?? null;
            $skuId = $validated['sku_id'] ?? null;

            if (empty($productId) || empty($skuId)) {
                return;
            }

            $skuBelongsToProduct = Sku::query()
                ->where('id', $skuId)
                ->where('product_id', $productId)
                ->exists();

            if (!$skuBelongsToProduct) {
                $validator->errors()->add('sku_id', 'The selected SKU does not belong to the selected product.');
            }
        });
    }

    private function normalizeNullablePrice($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function formatPrice($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return number_format((float) $value, 2);
    }
}
