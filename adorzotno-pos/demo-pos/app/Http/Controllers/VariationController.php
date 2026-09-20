<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationController extends Controller
{
    public function show()
    {
        $variation = Variation::orderBy('id', 'desc')->get();
        return view('variation.index', compact('variation'));
    }

    public function list()
    {     
        $variation = Variation::query();
        return DataTables()->of($variation)           
            ->setRowAttr([
                'align'=>'center',
            ])          
            ->make(true);
    }

    public function create()
    {       
        return view('variation.create');
    }

    public function store(Request $request)
    {         
        $validated = $this->validate($request, [
            'type' => 'required|string|max:255',
            'value' => 'required|string|max:255',           
        ]);    

        Variation::create([
            'type' => $request->type,
            'value' => $request->value,
        ]);

        return redirect()->route('variation.show')->with('success', 'Variation created successfully.');
    }

    public function edit($id)
    {
        $variation = Variation::findOrFail($id);
        return view('variation.edit', compact('variation'));     
        
    }   

    public function update(Request $request, $id)
    {
        $variation = Variation::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);       

        $variation->update($validated);

        return redirect()->route('variation.show')->with('success', 'Variation updated successfully.');
    }
           
    
    public function delete(Request $request): JsonResponse
    {
        $variation = Variation::query()->where('id', $request->id)->first();
        if (!empty($variation)) {
            $variation->delete();
        }
        return response()->json(['success' => 'Variation deleted successfully.']);
    }
}
