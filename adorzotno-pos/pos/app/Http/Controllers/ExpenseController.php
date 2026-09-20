<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Models\Branch;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function show()
    {
        $filters   = $this->filterOptions();
        $thisMonth = $this->thisMonthStats();

        return view('expense.index', array_merge($filters, compact('thisMonth')));
    }

    public function report()
    {
        $filters   = $this->filterOptions();
        $thisMonth = $this->thisMonthStats();

        return view('expense.report', array_merge($filters, compact('thisMonth')));
    }

    private function thisMonthStats(): array
    {
        $now = now();
        $expenses = Expense::query()
            ->whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)
            ->get();

        $byCategory = Expense::query()
            ->with('category')
            ->whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)
            ->get()
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc()
            ->take(5);

        return [
            'total'       => $expenses->sum('amount'),
            'count'       => $expenses->count(),
            'avg'         => $expenses->count() ? $expenses->sum('amount') / $expenses->count() : 0,
            'by_category' => $byCategory,
            'month_label' => $now->format('F Y'),
        ];
    }

    public function downloadPdf(Request $request)
    {
        $expenses = $this->expenseQuery($request)->get();
        $totalAmount = (float) $expenses->sum('amount');
        $pdf = $this->buildExpenseReportPdf($expenses, $totalAmount, $request);
        $filename = 'expense-report-' . now()->format('Y-m-d-His') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadExcel(Request $request)
    {
        $rows = $this->expenseQuery($request)->get()->map(fn (Expense $expense) => [
            DateFormatter::date($expense->expense_date),
            $expense->branch?->name ?? 'N/A',
            $expense->category?->name ?? 'N/A',
            $expense->payment_source,
            $expense->vendor_name,
            (float) $expense->amount,
            $expense->note,
        ]);

        return $this->downloadCsv('expense-report-' . now()->format('Y-m-d-His'), [
            'Date', 'Branch', 'Category', 'Payment Source', 'Vendor', 'Amount', 'Note',
        ], $rows);
    }

    public function list(Request $request)
    {
        $query = $this->expenseQuery($request);
        $totalAmount = (clone $query)->sum('amount');

        return DataTables()->of($query)
            ->addColumn('branch_name', fn (Expense $expense) => $expense->branch?->name ?? 'N/A')
            ->addColumn('category_name', fn (Expense $expense) => $expense->category?->name ?? 'N/A')
            ->editColumn('expense_date', fn (Expense $expense) => DateFormatter::date($expense->expense_date))
            ->editColumn('amount', fn (Expense $expense) => Currency::format($expense->amount))
            ->addColumn('attachment', function (Expense $expense) {
                if (empty($expense->attachment_path)) {
                    return '';
                }

                return '<a class="btn btn-info btn-xs" target="_blank" href="' . url($expense->attachment_path) . '"><i class="fa fa-paperclip"></i></a>';
            })
            ->setRowAttr(['align' => 'center'])
            ->rawColumns(['attachment'])
            ->with(['totalAmount' => (float) $totalAmount])
            ->make(true);
    }

    public function create()
    {
        $formData = $this->formOptions();

        return view('expense.create', $formData);
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);

        Expense::query()->create($validated);

        return redirect()->route('expense.show')->with('success', 'Expense created successfully.');
    }

    public function edit(int $id)
    {
        $expense = $this->findAccessibleExpense($id);
        $formData = $this->formOptions();

        return view('expense.edit', array_merge($formData, compact('expense')));
    }

    public function update(Request $request, int $id)
    {
        $expense = $this->findAccessibleExpense($id);
        $validated = $this->validateExpense($request, $expense);

        if (!empty($validated['attachment_path']) && !empty($expense->attachment_path)) {
            $this->deleteAttachment($expense->attachment_path);
        }

        $expense->update($validated);

        Session::flash('success', 'Expense updated successfully.');

        return redirect()->route('expense.show');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expenses,id'],
        ]);

        $expense = $this->findAccessibleExpense((int) $validated['id']);
        $this->deleteAttachment($expense->attachment_path);
        $expense->delete();

        return response()->json(['success' => 'Expense deleted successfully.']);
    }

    private function validateExpense(Request $request, ?Expense $expense = null): array
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'branch_id' => ['required', 'exists:branches,id'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'payment_source' => ['required', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:4096'],
        ]);

        if (!$this->branchContext()->hasBranchAccess($request->user(), (int) $validated['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to the selected branch.',
            ]);
        }

        $data = [
            'expense_date' => Carbon::parse($validated['expense_date']),
            'amount' => $validated['amount'],
            'branch_id' => $validated['branch_id'],
            'expense_category_id' => $validated['expense_category_id'],
            'account_id' => null,
            'payment_source' => $validated['payment_source'],
            'vendor_name' => $validated['vendor_name'] ?? null,
            'note' => $validated['note'] ?? null,
            'created_by' => $expense?->created_by ?? $request->user()?->id,
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $this->saveAttachment($request->file('attachment'));
        }

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'branches' => $this->branchContext()->accessibleBranches(auth()->user()),
            'categories' => ExpenseCategory::query()
                ->where('is_active', true)
                ->with('parent:id,name')
                ->orderBy('parent_id')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function filterOptions(): array
    {
        return array_merge($this->formOptions(), [
            'currentMonth' => '',
        ]);
    }

    private function expenseQuery(Request $request)
    {
        $branchId = $this->resolveReportBranchId($request);

        return Expense::query()
            ->with(['branch:id,name', 'category:id,name,parent_id'])
            ->when($branchId, fn ($query, $selectedBranchId) => $query->where('branch_id', $selectedBranchId))
            ->when(!$branchId && !$this->branchContext()->hasCrossBranchAccess($request->user()), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('expense_category_id', $request->category_id))
            ->when($request->filled('payment_source'), fn ($query) => $query->where('payment_source', 'like', '%' . $request->payment_source . '%'))
            ->when($request->filled('month'), function ($query) use ($request) {
                $month = Carbon::parse($request->month . '-01');
                $query->whereBetween('expense_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            })
            ->when($request->filled('startDate'), fn ($query) => $query->whereDate('expense_date', '>=', $request->startDate))
            ->when($request->filled('endDate'), fn ($query) => $query->whereDate('expense_date', '<=', $request->endDate))
            ->orderByDesc('expense_date')
            ->orderByDesc('id');
    }

    private function findAccessibleExpense(int $id): Expense
    {
        return Expense::query()
            ->whereIn('branch_id', $this->branchContext()->accessibleBranchIds(auth()->user()))
            ->findOrFail($id);
    }

    private function saveAttachment($file): string
    {
        $folder = 'public/expenseAttachment';

        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0777, true, true);
        }

        $filename = uniqid('', false) . '.' . $file->getClientOriginalExtension();
        $file->move($folder, $filename);

        return $folder . '/' . $filename;
    }

    private function deleteAttachment(?string $path): void
    {
        if (!empty($path) && File::exists($path)) {
            File::delete($path);
        }
    }

    private function buildExpenseReportPdf($expenses, float $totalAmount, Request $request): string
    {
        $pages = [];
        $content = '';
        $pageWidth = 842;
        $pageHeight = 595;
        $left = 32;
        $right = 810;
        $top = 560;
        $bottom = 54;
        $rowHeight = 20;
        $pageNo = 0;
        $settings = Setting::query()->first();
        $companyName = $settings?->company_name ?: config('app.name', 'Company');
        $companyPhone = $settings?->phone ?: '';
        $companyEmail = $settings?->email ?: '';
        $companyAddress = $settings?->address ?: ($settings?->office_address ?? '');
        $filters = $this->pdfFilterSummary($request);
        $reportPeriod = $this->pdfReportPeriod($request);
        $generatedAt = DateFormatter::dateTime(now());
        $totalRows = $expenses->count();
        $y = $top;

        $startPage = function () use (
            &$content,
            &$y,
            &$pageNo,
            $pageWidth,
            $pageHeight,
            $left,
            $right,
            $top,
            $companyName,
            $companyPhone,
            $companyEmail,
            $companyAddress,
            $reportPeriod,
            $generatedAt,
            $totalRows,
            $totalAmount,
            $filters
        ): void {
            $pageNo++;
            $content = '';
            $y = $top;

            $this->pdfRect($content, 0, $pageHeight - 78, $pageWidth, 78, '0.95 0.97 1 rg');
            $this->pdfLine($content, $left, $pageHeight - 78, $right, $pageHeight - 78, '0.18 0.31 0.55 RG', 1.2);
            $this->pdfTextAt($content, $left, 548, 18, $companyName, 'F2');
            $companyLines = array_values(array_filter([$companyAddress, $companyPhone ? 'Phone: ' . $companyPhone : '', $companyEmail ? 'Email: ' . $companyEmail : '']));
            $companyY = 532;
            foreach ($companyLines as $line) {
                $this->pdfTextAt($content, $left, $companyY, 8, $this->shortPdfText($line, 92));
                $companyY -= 11;
            }

            $this->pdfTextAt($content, 650, 548, 17, 'EXPENSE REPORT', 'F2');
            $this->pdfTextAt($content, 650, 531, 9, 'Period: ' . $reportPeriod);
            $this->pdfTextAt($content, 650, 518, 9, 'Generated: ' . $generatedAt);

            $this->pdfRect($content, $left, 458, 244, 48, '0.98 0.98 0.98 rg');
            $this->pdfRect($content, 299, 458, 244, 48, '0.98 0.98 0.98 rg');
            $this->pdfRect($content, 566, 458, 244, 48, '0.98 0.98 0.98 rg');
            $this->pdfTextAt($content, 44, 488, 8, 'TOTAL EXPENSE', 'F2');
            $this->pdfTextAt($content, 44, 470, 15, 'BDT ' . number_format($totalAmount, 2), 'F2');
            $this->pdfTextAt($content, 311, 488, 8, 'TOTAL RECORDS', 'F2');
            $this->pdfTextAt($content, 311, 470, 15, (string) $totalRows, 'F2');
            $this->pdfTextAt($content, 578, 488, 8, 'FILTERS', 'F2');
            $this->pdfTextAt($content, 578, 470, 8, $this->shortPdfText($filters ?: 'All expense records', 44));

            $this->pdfRect($content, $left, 426, 778, 22, '0.18 0.31 0.55 rg');
            $this->pdfTextAt($content, 40, 433, 8, 'SL', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 70, 433, 8, 'DATE', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 132, 433, 8, 'BRANCH', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 250, 433, 8, 'CATEGORY', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 376, 433, 8, 'PAYMENT SOURCE', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 516, 433, 8, 'VENDOR', 'F2', '1 1 1 rg');
            $this->pdfTextAt($content, 710, 433, 8, 'AMOUNT', 'F2', '1 1 1 rg');
            $y = 407;
        };

        $finishPage = function () use (&$pages, &$content, &$pageNo, $left, $right): void {
            $this->pdfLine($content, $left, 40, $right, 40, '0.75 0.75 0.75 RG', 0.7);
            $this->pdfTextAt($content, $left, 25, 8, 'This is a system generated expense report.');
            $this->pdfTextAt($content, 740, 25, 8, 'Page ' . $pageNo);
            $pages[] = $content;
        };

        $startPage();

        foreach ($expenses as $index => $expense) {
            if ($y < $bottom) {
                $finishPage();
                $startPage();
            }

            if ($index % 2 === 0) {
                $this->pdfRect($content, $left, $y - 5, 778, $rowHeight, '0.985 0.985 0.985 rg');
            }

            $this->pdfTextAt($content, 40, $y, 8, (string) ($index + 1));
            $this->pdfTextAt($content, 70, $y, 8, DateFormatter::date($expense->expense_date));
            $this->pdfTextAt($content, 132, $y, 8, $this->shortPdfText($expense->branch?->name ?? 'N/A', 22));
            $this->pdfTextAt($content, 250, $y, 8, $this->shortPdfText($expense->category?->name ?? 'N/A', 22));
            $this->pdfTextAt($content, 376, $y, 8, $this->shortPdfText($expense->payment_source ?? '', 24));
            $this->pdfTextAt($content, 516, $y, 8, $this->shortPdfText($expense->vendor_name ?? '', 34));
            $this->pdfTextAt($content, 710, $y, 8, 'BDT ' . number_format((float) $expense->amount, 2), 'F2');
            $this->pdfLine($content, $left, $y - 8, $right, $y - 8, '0.88 0.88 0.88 RG', 0.3);
            $y -= $rowHeight;
        }

        if ($y < 88) {
            $finishPage();
            $startPage();
        }

        $this->pdfLine($content, 620, $y - 2, $right, $y - 2, '0.18 0.31 0.55 RG', 1);
        $this->pdfTextAt($content, 622, $y - 20, 10, 'Grand Total', 'F2');
        $this->pdfTextAt($content, 710, $y - 20, 11, 'BDT ' . number_format($totalAmount, 2), 'F2');
        $finishPage();

        return $this->makePdf($pages, $pageWidth, $pageHeight);
    }

    private function pdfFilterSummary(Request $request): string
    {
        $filters = [];

        if ($request->filled('month')) {
            $filters[] = 'Month: ' . $request->month;
        }
        if ($request->filled('branch_id')) {
            $branchName = Branch::query()->where('id', $request->branch_id)->value('name');
            $filters[] = 'Branch: ' . ($branchName ?: $request->branch_id);
        }
        if ($request->filled('category_id')) {
            $categoryName = ExpenseCategory::query()->where('id', $request->category_id)->value('name');
            $filters[] = 'Category: ' . ($categoryName ?: $request->category_id);
        }
        if ($request->filled('startDate')) {
            $filters[] = 'From: ' . $request->startDate;
        }
        if ($request->filled('endDate')) {
            $filters[] = 'To: ' . $request->endDate;
        }
        if ($request->filled('payment_source')) {
            $filters[] = 'Payment Source: ' . $request->payment_source;
        }

        return implode(' | ', $filters);
    }

    private function pdfReportPeriod(Request $request): string
    {
        if ($request->filled('month')) {
            return Carbon::parse($request->month . '-01')->format('F Y');
        }

        if ($request->filled('startDate') || $request->filled('endDate')) {
            return ($request->startDate ?: 'Beginning') . ' to ' . ($request->endDate ?: 'Today');
        }

        return 'All Time';
    }

    private function makePdf(array $pageStreams, int $pageWidth, int $pageHeight): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pageObjectIds = [];
        $nextObjectId = 5;

        foreach ($pageStreams as $stream) {
            $contentId = $nextObjectId++;
            $pageId = $nextObjectId++;
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $pageObjectIds[] = $pageId . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageObjectIds) . '] /Count ' . count($pageObjectIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_keys($objects) as $id) {
            $pdf .= str_pad((string) $offsets[$id], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfTextAt(string &$stream, int $x, int $y, int $size, string $text, string $font = 'F1', string $color = '0.08 0.09 0.12 rg'): void
    {
        $stream .= $color . "\n";
        $stream .= 'BT /' . $font . ' ' . $size . ' Tf ' . $x . ' ' . $y . ' Td (' . $this->pdfText($text) . ") Tj ET\n";
        $stream .= "0 0 0 rg\n";
    }

    private function pdfRect(string &$stream, int $x, int $y, int $width, int $height, string $fillColor): void
    {
        $stream .= "q\n" . $fillColor . "\n" . $x . ' ' . $y . ' ' . $width . ' ' . $height . " re f\nQ\n";
    }

    private function pdfLine(string &$stream, int $x1, int $y1, int $x2, int $y2, string $strokeColor, float $width = 0.5): void
    {
        $stream .= "q\n" . $strokeColor . "\n" . $width . " w\n" . $x1 . ' ' . $y1 . ' m ' . $x2 . ' ' . $y2 . " l S\nQ\n";
    }

    private function pdfText(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function shortPdfText(string $text, int $length): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }
}
