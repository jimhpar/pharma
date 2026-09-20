<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function show()
    {
        return view('faq.index');
    }

    public function list()
    {
        $faqs = Faq::query()->orderByDesc('id');

        return DataTables()->of($faqs)
            ->setRowAttr([
                'align' => 'center',
            ])
            ->make(true);
    }

    public function create()
    {
        return view('faq.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        Faq::query()->create($validated);

        return redirect()->route('faq.show')->with('success', 'FAQ created successfully.');
    }

    public function edit($id)
    {
        $faq = Faq::query()->findOrFail($id);

        return view('faq.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::query()->findOrFail($id);
        $faq->update($validated);

        return redirect()->route('faq.show')->with('success', 'FAQ updated successfully.');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:faqs,id',
        ]);

        Faq::query()->where('id', $validated['id'])->delete();

        return response()->json(['success' => 'FAQ deleted successfully.']);
    }
}
