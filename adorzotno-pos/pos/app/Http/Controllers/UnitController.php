<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function show()
    {
        $units = Unit::query()->orderBy('id', 'desc')->get();

        return view('unit.index', compact('units'));
    }

    public function list(Request $request)
    {
        $units = Unit::query()
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('id', 'desc');

        return DataTables()->of($units)
            ->addColumn('status', function (Unit $unit) {
                if ($unit->status === 'active') {
                    return '<label class="btn btn-success">Active</label>';
                }

                return '<label class="btn btn-danger">Inactive</label>';
            })
            ->setRowAttr([
                'align' => 'center',
            ])
            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        return view('unit.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255|unique:units,name',
            'short_name' => 'required|string|max:50|unique:units,short_name',
            'status' => 'required|in:active,inactive',
        ]);

        Unit::query()->create($validated);

        return redirect()->route('unit.show')->with('success', 'Unit created successfully.');
    }

    public function edit($id)
    {
        $unit = Unit::query()->findOrFail($id);

        return view('unit.edit', compact('unit'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->ignore($id),
            ],
            'short_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'short_name')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $unit = Unit::query()->findOrFail($id);
        $unit->update($validated);

        Session::flash('success', 'Unit updated successfully.');

        return redirect()->route('unit.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $unit = Unit::query()->where('id', $request->id)->first();
        if (!empty($unit)) {
            $unit->delete();
        }

        return response()->json(['success' => 'Unit deleted successfully.']);
    }
}
