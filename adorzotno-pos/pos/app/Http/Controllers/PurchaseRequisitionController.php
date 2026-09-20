<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\PurchaseRequisitionItem;
use App\Models\Sku;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PurchaseRequisitionController extends Controller
{
    public function show()
    {
        $suppliers = Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);
        return view('purchase_requisition.index', compact('suppliers'));
    }

    public function list(): JsonResponse
    {
        $query = PurchaseRequisition::query()
            ->with(['branch', 'warehouse', 'requestedBy'])
            ->withCount('items')
            ->when($this->currentBranchId(), fn ($q, $id) => $q->where('branch_id', $id))
            ->orderByDesc('requisition_date')
            ->orderByDesc('id');

        return DataTables()->of($query)
            ->editColumn('requisition_date', fn ($r) => DateFormatter::date($r->requisition_date))
            ->addColumn('branch_name',    fn ($r) => $r->branch?->name ?? 'N/A')
            ->addColumn('warehouse_name', fn ($r) => $r->warehouse?->name ?? 'N/A')
            ->addColumn('requested_by_name', fn ($r) => $r->requestedBy?->name ?? 'N/A')
            ->addColumn('status_badge',   fn ($r) => $this->statusBadge($r->status))
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        $defaultWarehouse = $this->branchContext()->resolveDefaultWarehouseForCurrentBranch(auth()->user());
        $pr = new PurchaseRequisition([
            'requisition_no'   => $this->generateRequisitionNumber(),
            'requisition_date' => now()->toDateString(),
            'status'           => PurchaseRequisition::STATUS_DRAFT,
            'branch_id'        => $this->currentBranchId(),
            'warehouse_id'     => $defaultWarehouse?->id,
        ]);

        return view('purchase_requisition.create', array_merge(
            $this->formDependencies(),
            compact('pr')
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        $pr = DB::transaction(function () use ($payload) {
            $pr = PurchaseRequisition::create($payload['pr']);
            foreach ($payload['items'] as $item) {
                $pr->items()->create($item);
            }
            return $pr;
        });

        Session::flash('success', 'Purchase Requisition created successfully.');
        return redirect()->route('purchaseRequisition.edit', $pr->id);
    }

    public function edit(int $id)
    {
        $pr = PurchaseRequisition::query()
            ->with(['items.sku.product', 'branch', 'warehouse'])
            ->when($this->currentBranchId(), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->findOrFail($id);

        $suppliers = Supplier::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('purchase_requisition.edit', array_merge(
            $this->formDependencies($pr),
            compact('pr', 'suppliers')
        ));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $pr = PurchaseRequisition::query()
            ->when($this->currentBranchId(), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->findOrFail($id);

        if (!$pr->isEditable()) {
            Session::flash('error', 'This requisition can no longer be edited.');
            return redirect()->route('purchaseRequisition.edit', $pr->id);
        }

        $payload = $this->validatedPayload($request, $pr->id);

        DB::transaction(function () use ($pr, $payload) {
            $pr->update($payload['pr']);
            $pr->items()->delete();
            foreach ($payload['items'] as $item) {
                $pr->items()->create($item);
            }
        });

        Session::flash('success', 'Purchase Requisition updated successfully.');
        return redirect()->route('purchaseRequisition.edit', $pr->id);
    }

    public function submit(int $id): JsonResponse
    {
        $pr = PurchaseRequisition::query()
            ->when($this->currentBranchId(), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->findOrFail($id);

        if ($pr->status !== PurchaseRequisition::STATUS_DRAFT) {
            return response()->json(['message' => 'Only draft requisitions can be submitted.'], 422);
        }

        if ($pr->items()->count() === 0) {
            return response()->json(['message' => 'Add at least one item before submitting.'], 422);
        }

        $pr->update(['status' => PurchaseRequisition::STATUS_SUBMITTED]);

        return response()->json(['success' => true, 'message' => 'Requisition submitted for approval.']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $pr = PurchaseRequisition::query()->findOrFail($id);

        if (!$pr->canBeApproved()) {
            return response()->json(['message' => 'Only submitted requisitions can be approved.'], 422);
        }

        $pr->update([
            'status'      => PurchaseRequisition::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Requisition approved.']);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        $pr = PurchaseRequisition::query()->findOrFail($id);

        if (!$pr->canBeApproved()) {
            return response()->json(['message' => 'Only submitted requisitions can be rejected.'], 422);
        }

        $pr->update([
            'status'         => PurchaseRequisition::STATUS_REJECTED,
            'rejection_note' => $validated['rejection_note'],
            'approved_by'    => auth()->id(),
            'approved_at'    => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Requisition rejected.']);
    }

    public function convertToPo(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $pr = PurchaseRequisition::query()
            ->with('items')
            ->findOrFail($id);

        if (!$pr->canBeConverted()) {
            return response()->json(['message' => 'Only approved requisitions can be converted.'], 422);
        }

        $po = DB::transaction(function () use ($pr, $validated) {
            $po = PurchaseOrder::create([
                'branch_id'          => $pr->branch_id,
                'warehouse_id'       => $pr->warehouse_id,
                'supplier_id'        => $validated['supplier_id'],
                'purchase_no'        => $this->generatePoNumber(),
                'purchase_date'      => now()->toDateString(),
                'status'             => 'draft',
                'sub_total'          => 0,
                'discount_total'     => 0,
                'tax_total'          => 0,
                'other_charge_total' => 0,
                'grand_total'        => 0,
                'paid_total'         => 0,
                'due_total'          => 0,
                'note'               => 'Converted from PR #' . $pr->requisition_no,
                'created_by'         => auth()->id(),
            ]);

            foreach ($pr->items as $item) {
                $po->items()->create([
                    'sku_id'    => $item->sku_id,
                    'quantity'  => $item->requested_quantity,
                    'unit_cost' => $item->estimated_unit_cost ?? 0,
                    'line_total'=> ($item->estimated_unit_cost ?? 0) * $item->requested_quantity,
                ]);
            }

            $pr->update(['status' => PurchaseRequisition::STATUS_CONVERTED]);

            return $po;
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Converted to Purchase Order #' . $po->purchase_no,
            'redirect' => route('purchaseOrder.edit', $po->id),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:purchase_requisitions,id',
        ]);

        $pr = PurchaseRequisition::query()
            ->when($this->currentBranchId(), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->findOrFail($validated['id']);

        if (!in_array($pr->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_REJECTED])) {
            return response()->json(['message' => 'Only draft or rejected requisitions can be deleted.'], 422);
        }

        DB::transaction(function () use ($pr) {
            $pr->items()->delete();
            $pr->delete();
        });

        return response()->json(['success' => 'Requisition deleted successfully.']);
    }

    public function skuSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $skus = Sku::query()
            ->with('product:id,name,generic_name')
            ->where('status', 'active')
            ->where('track_stock', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('sku_code', 'like', "%{$q}%")
                       ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$q}%"));
                });
            })
            ->with(['stockBalances' => function ($query) use ($warehouseId) {
                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }
            }])
            ->select(['id', 'sku_code', 'product_id', 'retail_price'])
            ->orderBy('sku_code')
            ->limit(40)
            ->get();

        return response()->json([
            'results' => $skus->map(fn (Sku $sku) => [
                'id'            => $sku->id,
                'text'          => $sku->display_name,
                'retail_price'  => (float) ($sku->retail_price ?? 0),
                'current_stock' => (int) $sku->stockBalances->sum('available_quantity'),
            ]),
        ]);
    }

    public function downloadPdf(int $id)
    {
        $pr = PurchaseRequisition::query()
            ->with(['items.sku.product', 'branch', 'warehouse', 'requestedBy', 'approvedBy'])
            ->when($this->currentBranchId(), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->findOrFail($id);

        $setting     = \App\Models\Setting::first();
        $companyName = $setting?->company_name ?? config('app.name');
        $companyAddr = $setting?->address ?? '';

        $pdf      = $this->buildPrPdf($pr, $companyName, $companyAddr);
        $filename = 'PR-' . $pr->requisition_no . '-' . now()->format('Ymd') . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildPrPdf(PurchaseRequisition $pr, string $companyName, string $companyAddr): string
    {
        $W = 595; $H = 842;
        $L = 40;  $R = 555;

        $pages  = [];
        $items  = $pr->items;
        $chunks = $items->chunk(20); // max 20 items per page

        foreach ($chunks as $chunkIndex => $chunk) {
            $content = '';
            $y       = $H - 40;

            // ── Header bar ───────────────────────────────────────────
            $this->pdfRect($content, 0, $H - 75, $W, 75, '0.10 0.24 0.48 rg');
            $this->pdfTextAt($content, $L, $y - 4,  16, strtoupper($companyName), 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, $L, $y - 18,  8, $companyAddr,             'F1', '0.8 0.85 0.95 rg');
            $this->pdfTextAt($content, 390, $y - 4,  18, 'PURCHASE REQUISITION',  'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 390, $y - 22,  9, 'PR # ' . $pr->requisition_no, 'F1', '0.8 0.85 0.95 rg');

            $y = $H - 95;

            // ── Meta boxes ───────────────────────────────────────────
            $this->pdfRect($content, $L,   $y - 42, 155, 42, '0.95 0.97 1.0 rg');
            $this->pdfRect($content, 205,  $y - 42, 155, 42, '0.95 0.97 1.0 rg');
            $this->pdfRect($content, 370,  $y - 42, 185, 42, '0.95 0.97 1.0 rg');

            $this->pdfTextAt($content, 45,  $y - 12, 7, 'DATE',      'F2', '0.40 0.45 0.55 rg');
            $this->pdfTextAt($content, 45,  $y - 26, 9, $pr->requisition_date->format('d M Y'), 'F2');
            $this->pdfTextAt($content, 45,  $y - 38, 7, 'STATUS: ' . strtoupper($pr->status), 'F1', '0.10 0.24 0.48 rg');

            $this->pdfTextAt($content, 210, $y - 12, 7, 'BRANCH',    'F2', '0.40 0.45 0.55 rg');
            $this->pdfTextAt($content, 210, $y - 26, 9, $this->shortPdfText($pr->branch?->name ?? 'N/A', 24), 'F2');

            $this->pdfTextAt($content, 375, $y - 12, 7, 'WAREHOUSE', 'F2', '0.40 0.45 0.55 rg');
            $this->pdfTextAt($content, 375, $y - 26, 9, $this->shortPdfText($pr->warehouse?->name ?? 'N/A', 28), 'F2');

            $y -= 52;

            if ($pr->reason) {
                $this->pdfTextAt($content, $L, $y, 8, 'Reason: ' . $this->shortPdfText($pr->reason, 90));
                $y -= 14;
            }

            $y -= 6;

            // ── Table header ─────────────────────────────────────────
            $this->pdfRect($content, $L, $y - 14, $R - $L, 18, '0.10 0.24 0.48 rg');
            $cols = [40, 70, 320, 375, 425, 480, 515];
            // col x positions: #, Product, SKU, Req Qty, Stock, Est.Cost, Total
            $hdrs = ['#', 'Product / Generic', 'SKU', 'Req Qty', 'Stock', 'Est Cost', 'Total'];
            foreach ($hdrs as $i => $h) {
                $align = $i >= 3 ? '0.80 0.85 0.95 rg' : '1 1 1 rg';
                $this->pdfTextAt($content, $cols[$i], $y - 10, 7, $h, 'F2', '1 1 1 rg');
            }
            $y -= 18;

            // ── Table rows ───────────────────────────────────────────
            $totalEst = 0;
            $rowNum   = ($chunkIndex * 20) + 1;

            foreach ($chunk as $item) {
                $productName = $this->shortPdfText($item->sku?->product?->name ?? 'N/A', 38);
                $genericName = $this->shortPdfText($item->sku?->product?->generic_name ?? '', 28);
                $skuCode     = $this->shortPdfText($item->sku?->sku_code ?? '', 14);
                $qty         = (int) $item->requested_quantity;
                $stock       = (int) $item->current_stock;
                $cost        = (float) ($item->estimated_unit_cost ?? 0);
                $lineTotal   = round($cost * $qty, 2);
                $totalEst   += $lineTotal;

                // Alternate row background
                if ($rowNum % 2 === 0) {
                    $this->pdfRect($content, $L, $y - 12, $R - $L, 14, '0.97 0.98 1.0 rg');
                }

                $this->pdfTextAt($content, $cols[0], $y - 9, 8, (string) $rowNum);
                $this->pdfTextAt($content, $cols[1], $y - 9, 8, $productName);
                if ($genericName) {
                    $this->pdfTextAt($content, $cols[1], $y - 18, 6, $genericName, 'F1', '0.35 0.45 0.65 rg');
                }
                $this->pdfTextAt($content, $cols[2], $y - 9, 8, $skuCode);
                $this->pdfTextAt($content, $cols[3], $y - 9, 8, (string) $qty, 'F2');
                $this->pdfTextAt($content, $cols[4], $y - 9, 8,
                    $stock > 0 ? (string) $stock : '0',
                    'F1',
                    $stock <= 0 ? '0.8 0.1 0.1 rg' : '0.08 0.09 0.12 rg'
                );
                $this->pdfTextAt($content, $cols[5], $y - 9, 8, $cost > 0 ? number_format($cost, 2) : '-');
                $this->pdfTextAt($content, $cols[6], $y - 9, 8, $lineTotal > 0 ? number_format($lineTotal, 2) : '-', 'F2');
                $this->pdfLine($content, $L, $y - 13, $R, $y - 13, '0.88 0.88 0.88 RG', 0.3);

                $y -= ($genericName ? 22 : 14);
                $rowNum++;
            }

            // ── Total row ────────────────────────────────────────────
            $y -= 4;
            $this->pdfLine($content,  380, $y + 2, $R, $y + 2, '0.10 0.24 0.48 RG', 1);
            $this->pdfTextAt($content, 382, $y - 8, 9,  'TOTAL ESTIMATED VALUE', 'F2');
            $this->pdfTextAt($content, 480, $y - 8, 10, number_format($totalEst, 2), 'F2', '0.10 0.24 0.48 rg');

            // ── Signatures (only last page) ──────────────────────────
            if ($chunkIndex === count($chunks) - 1) {
                $sigY = max($y - 60, 120);
                $this->pdfLine($content, $L,  $sigY, 180, $sigY, '0.5 0.5 0.5 RG', 0.6);
                $this->pdfLine($content, 220, $sigY, 380, $sigY, '0.5 0.5 0.5 RG', 0.6);
                $this->pdfLine($content, 420, $sigY, 555, $sigY, '0.5 0.5 0.5 RG', 0.6);

                $this->pdfTextAt($content, $L,  $sigY - 12, 7, 'Requested By', 'F2', '0.40 0.45 0.55 rg');
                $this->pdfTextAt($content, $L,  $sigY - 23, 7, $this->shortPdfText($pr->requestedBy?->name ?? '', 26));
                $this->pdfTextAt($content, 220, $sigY - 12, 7, 'Approved By', 'F2', '0.40 0.45 0.55 rg');
                $this->pdfTextAt($content, 220, $sigY - 23, 7, $this->shortPdfText($pr->approvedBy?->name ?? '—', 26));
                $this->pdfTextAt($content, 420, $sigY - 12, 7, 'Authorised By', 'F2', '0.40 0.45 0.55 rg');
            }

            // ── Footer ───────────────────────────────────────────────
            $this->pdfRect($content, 0, 0, $W, 28, '0.95 0.97 1.0 rg');
            $this->pdfLine($content, 0, 28, $W, 28, '0.10 0.24 0.48 RG', 0.8);
            $this->pdfTextAt($content, $L, 10, 7, 'Generated: ' . now()->format('d M Y, h:i A') . '  |  System Generated — ' . $companyName);
            $pageLabel = 'Page ' . ($chunkIndex + 1) . ' of ' . count($chunks);
            $this->pdfTextAt($content, 500, 10, 7, $pageLabel, 'F2');

            $pages[] = $content;
        }

        return $this->makePdf($pages, $W, $H);
    }

    // ── PDF helpers (same as ExpenseController) ──────────────────────

    private function makePdf(array $pageStreams, int $W, int $H): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $pageIds   = [];
        $nextId    = 5;
        foreach ($pageStreams as $stream) {
            $cid = $nextId++; $pid = $nextId++;
            $objects[$cid] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pid] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $W . ' ' . $H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $cid . ' 0 R >>';
            $pageIds[] = $pid . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n"; $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_keys($objects) as $id) {
            $pdf .= str_pad((string) $offsets[$id], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }

    private function pdfTextAt(string &$s, int $x, int $y, int $size, string $text, string $font = 'F1', string $color = '0.08 0.09 0.12 rg'): void
    {
        $s .= "$color\nBT /$font $size Tf $x $y Td (" . $this->pdfEsc($text) . ") Tj ET\n0 0 0 rg\n";
    }

    private function pdfRect(string &$s, int $x, int $y, int $w, int $h, string $fill): void
    {
        $s .= "q\n$fill\n$x $y $w $h re f\nQ\n";
    }

    private function pdfLine(string &$s, int $x1, int $y1, int $x2, int $y2, string $stroke, float $w = 0.5): void
    {
        $s .= "q\n$stroke\n$w w\n$x1 $y1 m $x2 $y2 l S\nQ\n";
    }

    private function pdfEsc(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function shortPdfText(string $text, int $len): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        return strlen($text) > $len ? substr($text, 0, $len - 3) . '...' : $text;
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function formDependencies(?PurchaseRequisition $pr = null): array
    {
        $existingSkus = collect();
        if ($pr && $pr->exists) {
            $skuIds = $pr->items->pluck('sku_id')->filter()->unique();
            if ($skuIds->isNotEmpty()) {
                $existingSkus = Sku::query()
                    ->with('product:id,name')
                    ->whereIn('id', $skuIds)
                    ->select(['id', 'sku_code', 'product_id'])
                    ->get();
            }
        }

        return [
            'branches'   => $this->branchContext()->accessibleBranches(auth()->user()),
            'warehouses' => $this->branchContext()->accessibleWarehouses(auth()->user()),
            'skus'       => $existingSkus,
        ];
    }

    private function validatedPayload(Request $request, ?int $prId = null): array
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($i) => !empty($i['sku_id']))
            ->values()->all();
        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'requisition_no'   => 'required|string|max:50|unique:purchase_requisitions,requisition_no' . ($prId ? ",{$prId}" : ''),
            'requisition_date' => 'required|date',
            'branch_id'        => 'required|exists:branches,id',
            'warehouse_id'     => 'required|exists:warehouses,id',
            'status'           => 'required|in:draft,submitted',
            'reason'           => 'nullable|string|max:1000',
            'note'             => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.sku_id'             => 'required|exists:product_skus,id',
            'items.*.requested_quantity' => 'required|integer|min:1',
            'items.*.estimated_unit_cost'=> 'nullable|numeric|min:0',
            'items.*.item_note'          => 'nullable|string|max:500',
        ]);

        return [
            'pr' => [
                'requisition_no'   => $validated['requisition_no'],
                'requisition_date' => $validated['requisition_date'],
                'branch_id'        => $validated['branch_id'],
                'warehouse_id'     => $validated['warehouse_id'],
                'status'           => $validated['status'],
                'reason'           => $validated['reason'] ?? null,
                'note'             => $validated['note'] ?? null,
                'requested_by'     => $prId ? PurchaseRequisition::find($prId)?->requested_by ?? auth()->id() : auth()->id(),
            ],
            'items' => collect($validated['items'])->map(fn ($i) => [
                'sku_id'               => $i['sku_id'],
                'requested_quantity'   => $i['requested_quantity'],
                'estimated_unit_cost'  => $i['estimated_unit_cost'] ?? null,
                'item_note'            => $i['item_note'] ?? null,
            ])->all(),
        ];
    }

    private function generateRequisitionNumber(): string
    {
        $last = PurchaseRequisition::query()->orderByDesc('id')->value('requisition_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'PR-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    private function generatePoNumber(): string
    {
        $last = PurchaseOrder::query()->orderByDesc('id')->value('purchase_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'PO-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'draft'     => '<span class="badge bg-secondary">Draft</span>',
            'submitted' => '<span class="badge bg-primary">Submitted</span>',
            'approved'  => '<span class="badge bg-success">Approved</span>',
            'rejected'  => '<span class="badge bg-danger">Rejected</span>',
            'converted' => '<span class="badge bg-info text-dark">Converted to PO</span>',
            default     => '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>',
        };
    }
}
