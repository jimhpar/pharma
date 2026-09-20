<?php

namespace App\Http\Controllers;

use App\Models\ShipmentZone;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ShipmentZoneController extends Controller
{
    public function show()
    {
        $shipmentZones = ShipmentZone::query()->orderBy('id', 'desc')->get();

        return view('shipment_zone.index', compact('shipmentZones'));
    }

    public function list(Request $request)
    {
        $shipmentZones = ShipmentZone::query()
            ->when($request->filter_status, fn ($q, $v) => $q->where('status', ucfirst($v)));

        return DataTables()->of($shipmentZones)
            ->addColumn('charge', function (ShipmentZone $shipmentZone) {
                return Currency::format($shipmentZone->charge);
            })
            ->addColumn('status', function (ShipmentZone $shipmentZone) {
                if (strtolower($shipmentZone->status) === 'active') {
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
        return view('shipment_zone.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        ShipmentZone::query()->create($validated);

        return redirect()->route('shipmentZone.show')->with('success', 'Shipment zone created successfully.');
    }

    public function edit($id)
    {
        $shipmentZone = ShipmentZone::query()->findOrFail($id);

        return view('shipment_zone.edit', compact('shipmentZone'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $shipmentZone = ShipmentZone::query()->findOrFail($id);
        $shipmentZone->update($validated);

        Session::flash('success', 'Shipment zone updated successfully.');

        return redirect()->route('shipmentZone.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $shipmentZone = ShipmentZone::query()->where('id', $request->id)->first();
        if (!empty($shipmentZone)) {
            $shipmentZone->delete();
        }

        return response()->json(['success' => 'Shipment zone deleted successfully.']);
    }
}
