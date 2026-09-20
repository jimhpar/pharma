<?php

namespace App\Http\Controllers;

use App\Models\TaxRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class TaxRulesController extends Controller
{
    public function show()
    {
        $taxRules = TaxRule::query()->orderBy('id', 'desc')->get();

        return view('tax_rule.index', compact('taxRules'));
    }

    public function list()
    {
        $taxRules = TaxRule::query()->orderBy('id', 'desc');

        return DataTables()->of($taxRules)
            ->addColumn('rate_percent', function (TaxRule $taxRule) {
                return number_format((float) $taxRule->rate_percent, 2) . '%';
            })
            ->addColumn('is_inclusive', function (TaxRule $taxRule) {
                if ((bool) $taxRule->is_inclusive) {
                    return '<label class="btn btn-info">Inclusive</label>';
                }

                return '<label class="btn btn-secondary">Exclusive</label>';
            })
            ->addColumn('status', function (TaxRule $taxRule) {
                if ($taxRule->status === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['is_inclusive', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('tax_rule.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255|unique:tax_rules,name',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'required|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        TaxRule::query()->create($validated);

        return redirect()->route('taxRule.show')->with('success', 'VAT rule created successfully.');
    }

    public function edit($id)
    {
        $taxRule = TaxRule::query()->findOrFail($id);

        return view('tax_rule.edit', compact('taxRule'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_rules', 'name')->ignore($id),
            ],
            'rate_percent' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'required|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $taxRule = TaxRule::query()->findOrFail($id);
        $taxRule->update($validated);

        Session::flash('success', 'VAT rule updated successfully.');

        return redirect()->route('taxRule.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $taxRule = TaxRule::query()->where('id', $request->id)->first();
        if (!empty($taxRule)) {
            $taxRule->delete();
        }

        return response()->json(['success' => 'VAT rule deleted successfully.']);
    }
}
