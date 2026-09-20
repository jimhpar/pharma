<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPrescription;
use Illuminate\Http\Request;

class CustomerPrescriptionController extends Controller
{
    public function index(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $prescriptions = $customer->prescriptions()->latest()->get()->map(fn ($p) => [
            'id'        => $p->id,
            'title'     => $p->title,
            'notes'     => $p->notes,
            'image_url' => $p->image_url,
            'created_at'=> $p->created_at->format('d M Y'),
        ]);

        return response()->json($prescriptions);
    }

    public function store(Request $request, int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validated = $request->validate([
            'title'  => 'nullable|string|max:255',
            'notes'  => 'nullable|string|max:5000',
            'image'  => 'nullable|image|max:10240',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('prescriptions', 'public');
            $imagePath = 'storage/' . $imagePath;
        }

        $prescription = CustomerPrescription::create([
            'customer_id' => $customer->id,
            'title'       => $validated['title'] ?? null,
            'notes'       => $validated['notes'] ?? null,
            'image_path'  => $imagePath,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Prescription saved.',
            'id'        => $prescription->id,
            'title'     => $prescription->title,
            'notes'     => $prescription->notes,
            'image_url' => $prescription->image_url,
            'created_at'=> $prescription->created_at->format('d M Y'),
        ]);
    }

    public function destroy(int $customerId, int $prescriptionId)
    {
        $prescription = CustomerPrescription::where('customer_id', $customerId)
            ->findOrFail($prescriptionId);

        if ($prescription->image_path) {
            $relativePath = str_replace('storage/', '', $prescription->image_path);
            \Storage::disk('public')->delete($relativePath);
        }

        $prescription->delete();

        return response()->json(['success' => true, 'message' => 'Prescription deleted.']);
    }
}
