<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Support\Currency;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function show()
    {
        return view('coupon.index');
    }

    public function list(Request $request)
    {
        $coupons = Coupon::query()
            ->when($request->filter_status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->filter_type, fn ($query, $type) => $query->where('discount_type', $type))
            ->orderByDesc('id');

        return DataTables()->of($coupons)
            ->addColumn('discount', function (Coupon $coupon) {
                if ($coupon->discount_type === Coupon::TYPE_PERCENTAGE) {
                    return rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.') . '%';
                }

                return Currency::format($coupon->discount_value);
            })
            ->addColumn('minimum', fn (Coupon $coupon) => $coupon->min_order_amount !== null ? Currency::format($coupon->min_order_amount) : '-')
            ->addColumn('maximum', fn (Coupon $coupon) => $coupon->max_discount_amount !== null ? Currency::format($coupon->max_discount_amount) : '-')
            ->addColumn('validity', function (Coupon $coupon) {
                $start = $coupon->start_at?->format('d M Y H:i') ?? 'Any time';
                $end = $coupon->end_at?->format('d M Y H:i') ?? 'No expiry';

                return $start . '<br><span class="text-muted">to</span><br>' . $end;
            })
            ->addColumn('usage', function (Coupon $coupon) {
                $limit = $coupon->usage_limit !== null ? $coupon->usage_limit : 'Unlimited';

                return (int) $coupon->used_count . ' / ' . $limit;
            })
            ->addColumn('status', function (Coupon $coupon) {
                if (strtolower((string) $coupon->status) === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['validity', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('coupon.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['code'] = strtoupper($validated['code']);
        $validated['used_count'] = 0;

        Coupon::query()->create($this->normalizeDates($validated));

        return redirect()->route('coupon.show')->with('success', 'Coupon created successfully.');
    }

    public function edit($id)
    {
        $coupon = Coupon::query()->findOrFail($id);

        return view('coupon.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::query()->findOrFail($id);
        $validated = $this->validatedData($request, $coupon->id);
        $validated['code'] = strtoupper($validated['code']);

        $coupon->update($this->normalizeDates($validated));

        return redirect()->route('coupon.show')->with('success', 'Coupon updated successfully.');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:coupons,id',
        ]);

        try {
            Coupon::query()->where('id', $validated['id'])->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Coupon cannot be deleted because it has order usage history.',
            ], 409);
        }

        return response()->json(['success' => 'Coupon deleted successfully.']);
    }

    private function validatedData(Request $request, ?int $couponId = null): array
    {
        $codeRule = Rule::unique('coupons', 'code');
        if ($couponId !== null) {
            $codeRule->ignore($couponId);
        }

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                $codeRule,
            ],
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'status' => 'required|in:active,inactive',
        ]);
    }

    private function normalizeDates(array $data): array
    {
        foreach (['start_at', 'end_at'] as $field) {
            $data[$field] = !empty($data[$field])
                ? Carbon::parse($data[$field])->format('Y-m-d H:i:s')
                : null;
        }

        foreach (['min_order_amount', 'max_discount_amount', 'usage_limit'] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        return $data;
    }
}
