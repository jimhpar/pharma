<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Sku;
use App\Models\Stock;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function preview($id)
    {       
        $product=Product::with('sku','sku.batch')->find($id);      
        return view('batch.create',compact('product'));
    }

    public function variationList(Request $request)
    {
        $productVariation = Sku::where('product_id', $request->productId);

        return DataTables()->of($productVariation)
            ->addColumn('product_code', fn (Sku $sku) => $sku->sku_code ?? 'N/A')
            ->addColumn('base_price',   fn (Sku $sku) => $sku->retail_price ?? 0)
            ->addColumn('discount_type',   fn (Sku $sku) => '—')
            ->addColumn('discount_amount', fn (Sku $sku) => '—')
            ->setRowAttr(['align' => 'center'])
            ->make(true);
    }

    public function list(Request $request)
    {
        $productId = $request->productId;
        $batch = Batch::with(['sku', 'sku.product'])
        ->whereHas('sku.product', function ($query) use ($productId) {
            $query->where('id', $productId);
        });

        return DataTables()->of($batch)       
        // ->addColumn('supplierName', function ($batch)
        // {   
        //     return $batch->supplier->name ?? 'N/A';
        // })
        ->setRowAttr([
            'align'=>'center',
        ])      
        // ->rawColumns(['supplierName'])
        ->make(true);
    }

    public function selectSkuId(Request $request)
    {
        $sku = Sku::where('id', $request->get('id'))->first();
        if (!$sku) {
            return response()->json(['statusText' => 'Variation Not Found!'], 404);
        }
        return response()->json(['sku' => $sku]);

    }

    public function store(Request $request)
    {        
        // dd($request->all());
        $validated = $this->validate($request, [
            'skuId' => 'required|exists:sku,id',
            'supplier' => 'required',
            'purchase_price' => 'required',
            'quantity'=>'required',
        ]);

        $sku = Sku::query()->findOrFail($validated['skuId']);

        $batch= Batch::create([
            'sku_id'=> $validated['skuId'],
            'supplier_id'=>$validated['supplier'],
            'purchase_price'=> $validated['purchase_price'],
            'quantity'=> $validated['quantity'],
            'current_quantity'=> $validated['quantity'],
        ]);

        $stock = Stock::create([
            'product_id'=> $sku->product_id,
            'sku_id'=>$validated['skuId'],
            'batch_id'=> $batch->id,
            'type'=> 'In',
            'identifier'=> 'Purchase',
            'quantity'=> $batch->quantity,           
        ]);

        return response()->json(['statusText' => 'Purchase saved successfully!']);
    }





}
