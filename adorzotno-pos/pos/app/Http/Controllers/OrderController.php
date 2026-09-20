<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\User;
use App\Support\Currency;
use App\Support\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class OrderController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();
        $branches = $this->branchContext()->accessibleBranches(auth()->user());
        $cashiers = User::query()
            ->select('users.id', 'users.name')
            ->join('sales_orders', 'sales_orders.cashier_id', '=', 'users.id')
            ->when(
                !$this->branchContext()->hasCrossBranchAccess($user),
                fn ($query) => $query->where('sales_orders.branch_id', $this->currentBranchId())
            )
            ->when(
                $this->branchContext()->hasCrossBranchAccess($user),
                fn ($query) => $query->whereIn('sales_orders.branch_id', $this->branchContext()->accessibleBranchIds($user))
            )
            ->distinct()
            ->orderBy('users.name')
            ->get();

        $saleTypes = ['Sale', 'Credit Sale', 'Quotation', 'Draft', 'Suspend'];
        $salesChannels = SalesOrder::channelOptions();

        return view('order.index', compact('branches', 'cashiers', 'saleTypes', 'salesChannels'));
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $branchId = $this->resolveReportBranchId($request);

        $orders = SalesOrder::query()
            ->leftJoin('customers', 'customers.id', '=', 'sales_orders.customer_id')
            ->leftJoin('users', 'users.id', '=', 'customers.user_id')
            ->leftJoin('branches', 'branches.id', '=', 'sales_orders.branch_id')
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'sales_orders.cashier_id')
            ->when($branchId, fn ($query, $selectedBranchId) => $query->where('sales_orders.branch_id', $selectedBranchId))
            ->when(!$branchId && !$this->branchContext()->hasCrossBranchAccess($user), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('status'), fn ($query) => $query->where('sales_orders.status', (string) $request->input('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('sales_orders.payment_status', (string) $request->input('payment_status')))
            ->when($request->filled('fulfillment_status'), fn ($query) => $query->where('sales_orders.fulfillment_status', (string) $request->input('fulfillment_status')))
            ->when($request->filled('cashier_id'), fn ($query) => $query->where('sales_orders.cashier_id', (int) $request->input('cashier_id')))
            ->when($request->filled('sales_channel'), fn ($query) => $query->where('sales_orders.sales_channel', (string) $request->input('sales_channel')))
            ->when($request->filled('sale_type'), fn ($query) => $query->where('sales_orders.internal_note', 'like', '%Sale Type: ' . (string) $request->input('sale_type') . '%'))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('sales_orders.order_date', '>=', (string) $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('sales_orders.order_date', '<=', (string) $request->input('end_date')))
            ->when($request->filled('min_total'), fn ($query) => $query->where('sales_orders.grand_total', '>=', (float) $request->input('min_total')))
            ->when($request->filled('max_total'), fn ($query) => $query->where('sales_orders.grand_total', '<=', (float) $request->input('max_total')))
            ->orderByDesc('sales_orders.id')
            ->select([
                'sales_orders.*',
                DB::raw("COALESCE(customers.name, users.name, 'Walk-in Customer') as customer_name"),
                DB::raw("COALESCE(branches.name, 'N/A') as branch_name"),
                DB::raw("COALESCE(cashiers.name, 'N/A') as cashier_name"),
                DB::raw("(SELECT COUNT(*) FROM sales_order_items WHERE sales_order_items.sales_order_id = sales_orders.id) as items_count"),
                DB::raw("(SELECT GROUP_CONCAT(coupons.code ORDER BY coupons.code SEPARATOR ', ') FROM order_coupon_usages JOIN coupons ON coupons.id = order_coupon_usages.coupon_id WHERE order_coupon_usages.sales_order_id = sales_orders.id) as coupon_codes"),
                DB::raw("(SELECT COALESCE(SUM(order_coupon_usages.discount_amount), 0) FROM order_coupon_usages WHERE order_coupon_usages.sales_order_id = sales_orders.id) as coupon_discount_total"),
            ]);

        return DataTables()->of($orders)
            ->filterColumn('id', function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('sales_orders.id', 'like', '%' . $keyword . '%')
                        ->orWhere('sales_orders.order_no', 'like', '%' . $keyword . '%')
                        ->orWhere('sales_orders.invoice_no', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('customer', function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('customers.name', 'like', '%' . $keyword . '%')
                        ->orWhere('customers.email', 'like', '%' . $keyword . '%')
                        ->orWhere('customers.phone', 'like', '%' . $keyword . '%')
                        ->orWhere('sales_orders.order_no', 'like', '%' . $keyword . '%')
                        ->orWhere('sales_orders.invoice_no', 'like', '%' . $keyword . '%')
                        ->orWhere('users.name', 'like', '%' . $keyword . '%')
                        ->orWhere('users.email', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('sale_type', function ($query, $keyword) {
                $query->where('sales_orders.internal_note', 'like', '%sale type%'.$keyword.'%');
            })
            ->filterColumn('branch_name', function ($query, $keyword) {
                $query->where('branches.name', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('cashier_name', function ($query, $keyword) {
                $query->where('cashiers.name', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('order_date', function ($query, $keyword) {
                $query->whereDate('sales_orders.order_date', $keyword);
            })
            ->orderColumn('customer', 'customer_name $1')
            ->orderColumn('branch_name', 'branch_name $1')
            ->orderColumn('cashier_name', 'cashier_name $1')
            ->addColumn('customer', fn ($order) => $order->customer_name ?: 'Walk-in Customer')
            ->addColumn('branch_name', fn ($order) => $order->branch_name ?: 'N/A')
            ->addColumn('cashier_name', fn ($order) => $order->cashier_name ?: 'N/A')
            ->editColumn('order_date', fn (SalesOrder $order) => DateFormatter::dateTime($order->order_date))
            ->addColumn('sale_type', fn (SalesOrder $order) => $order->sale_type)
            ->addColumn('sales_channel_label', fn (SalesOrder $order) => $order->sales_channel_label)
            ->addColumn('order_last_status', fn (SalesOrder $order) => $order->status_label)
            ->addColumn('payment_last_status', fn (SalesOrder $order) => $order->payment_status_label)
            ->addColumn('fulfillment_last_status', fn (SalesOrder $order) => $order->fulfillment_status_label)
            ->editColumn('grand_total', function (SalesOrder $order) {
                return Currency::format($order->grand_total);
            })
            ->setRowAttr([
                'align'=>'center',
            ])
            ->rawColumns(['customer'])
            ->make(true);
    }

    public function details($id)
    {
        $order = $this->findOrderWithRelations($id, [
            'branch',
            'warehouse',
            'cashier',
            'customer',
            'customer.user',
            'items.sku.product.brand',
            'items.sku.product.category',
            'items.sku.stockBalances.branch',
            'items.sku.stockBalances.warehouse',
            'items.sku.stockBalances.batch',
            'items.warehouse',
            'items.batch.supplier',
            'items.batch.warehouse',
            'items.batch.stockBalances.branch',
            'items.batch.stockBalances.warehouse',
            'payments.receivedBy',
            'latestPayment',
            'coupons',
        ]);

        return view('order.details', compact('order'));
    }

    public function invoice($id)
    {
        $order = $this->findOrderWithRelations($id, [
            'branch',
            'warehouse',
            'cashier',
            'customer',
            'customer.user',
            'items.sku.product.brand',
            'payments',
            'latestPayment',
            'coupons',
        ]);

        return view('order.invoice-a4', compact('order'));
    }

    public function thermalInvoice($id)
    {
        $order = $this->findOrderWithRelations($id, [
            'branch',
            'warehouse',
            'cashier',
            'customer',
            'customer.user',
            'items.sku.product.brand',
            'payments',
            'latestPayment',
            'coupons',
        ]);

        return view('order.invoice', compact('order'));
    }

    public function directThermalPrint($id): JsonResponse
    {
        $order = $this->findOrderWithRelations($id, [
            'branch',
            'warehouse',
            'cashier',
            'customer',
            'customer.user',
            'items.sku.product.brand',
            'payments',
            'coupons',
        ]);

        $printerName = env('POS_THERMAL_PRINTER_NAME', 'RONGTA 80mm Series Printer');
        $scriptPath = base_path('scripts/print-raw.ps1');
        $powershell = env('POS_POWERSHELL_PATH', 'powershell.exe');

        if (!File::exists($scriptPath)) {
            return response()->json(['message' => 'Raw printer script is missing.'], 500);
        }

        $directory = storage_path('app/thermal-receipts');
        File::ensureDirectoryExists($directory);

        $filePath = $directory . DIRECTORY_SEPARATOR . 'receipt-' . $order->id . '-' . uniqid('', true) . '.bin';
        File::put($filePath, $this->buildEscposReceipt($order));

        $process = new Process([
            $powershell,
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $scriptPath,
            '-PrinterName',
            $printerName,
            '-FilePath',
            $filePath,
        ]);
        $process->setTimeout(20);
        $process->run();

        File::delete($filePath);

        if (!$process->isSuccessful()) {
            report(new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput())));

            return response()->json(['message' => 'Direct printer job failed.'], 500);
        }

        return response()->json(['message' => 'Receipt sent to thermal printer.']);
    }

    private function findOrderWithRelations($id, array $relations): SalesOrder
    {
        $user = auth()->user();

        return SalesOrder::with($relations)
            ->when(
                !$this->branchContext()->hasCrossBranchAccess($user),
                fn ($query) => $query->where('branch_id', $this->currentBranchId())
            )
            ->when(
                $this->branchContext()->hasCrossBranchAccess($user),
                fn ($query) => $query->where(function ($branchQuery) use ($user) {
                    $branchQuery
                        ->whereIn('branch_id', $this->branchContext()->accessibleBranchIds($user))
                        ->orWhereNull('branch_id');
                })
            )
            ->findOrFail($id);
    }

    private function buildEscposReceipt(SalesOrder $order): string
    {
        $lineWidth = 48;
        $settings = \App\Models\Setting::query()->latest('id')->first();
        $branch = $order->branch;
        $companyName = $settings?->company_name ?: ($branch?->name ?? config('app.name', 'POS System'));
        $companyAddress = $settings?->office_address ?: ($branch?->address ?? null);
        $companyPhone = $settings?->phone ?: ($branch?->phone ?? null);
        $companyEmail = $settings?->email ?: ($branch?->email ?? null);
        $customerName = $order->customer?->name ?? $order->customer?->user?->name ?? 'Walk-in Customer';
        $customerPhone = $order->customer?->phone ?? null;
        $orderDate = DateFormatter::dateTime($order->order_date ?? $order->created_at);
        $printedAt = DateFormatter::dateTime(now());
        $totalDiscount = (float) ($order->item_discount_total ?? 0) + (float) ($order->cart_discount_total ?? 0);
        $couponCodes = $order->coupons->pluck('code')->implode(', ');
        $couponDiscountTotal = (float) $order->coupons->sum(fn ($coupon) => (float) ($coupon->pivot?->discount_amount ?? 0));
        $receiptGrandTotal = max(0, round((float) ($order->sub_total ?? 0) - $totalDiscount + (float) ($order->tax_total ?? 0) + (float) ($order->shipping_fee ?? 0), 2));
        $paidAmount = (float) $order->payments->sum('amount');
        $changeAmount = max(0, $paidAmount - $receiptGrandTotal);
        $dueAmount = max(0, $receiptGrandTotal - $paidAmount);
        $money = fn ($amount) => number_format((float) $amount, 2);
        $moneyWithCurrency = fn ($amount) => 'BDT ' . number_format((float) $amount, 2);
        $rule = str_repeat('-', $lineWidth) . "\r\n";

        $receipt = "\x1B@";
        $receipt .= "\x1B\x61\x01";
        $receipt .= $this->thermalBold($this->thermalCenter(strtoupper($companyName), $lineWidth));

        foreach ([$branch?->name, $companyAddress, $companyPhone ? 'Phone: ' . $companyPhone : null, $companyEmail] as $line) {
            if ($line) {
                foreach ($this->thermalWrap($line, $lineWidth) as $wrappedLine) {
                    $receipt .= $this->thermalCenter($wrappedLine, $lineWidth);
                }
            }
        }

        $receipt .= "\r\n";
        $receipt .= $this->thermalBold($this->thermalCenter($order->sale_type === 'Quotation' ? 'QUOTATION' : 'SALES RECEIPT', $lineWidth));
        $receipt .= "\x1B\x61\x00";
        $receipt .= $rule;
        $receipt .= $this->thermalPair('Invoice', $order->invoice_no ?? $order->order_no, $lineWidth);
        $receipt .= $this->thermalPair('Date', $orderDate, $lineWidth);
        $receipt .= $this->thermalPair('Customer', $customerName, $lineWidth);
        if ($customerPhone) {
            $receipt .= $this->thermalPair('Mobile', $customerPhone, $lineWidth);
        }
        $receipt .= $this->thermalPair('Cashier', $order->cashier?->name ?? 'N/A', $lineWidth);
        $receipt .= $this->thermalPair('Payment', $order->payment_method, $lineWidth);
        if ($couponCodes !== '') {
            $receipt .= $this->thermalPair('Coupon', $couponCodes, $lineWidth);
        }
        $receipt .= $rule;
        $receipt .= $this->thermalBold($this->thermalPair('Item / Qty x Rate', 'Total', $lineWidth));

        foreach ($order->items as $index => $item) {
            $itemName = $item->sku?->product?->name ?? 'N/A';
            $variant = $item->sku?->variant_name ?: '';
            $productCode = $item->sku?->product_code ?? $item->sku?->sku_code ?? '';
            $brandName = $item->sku?->product?->brand?->name ?? '';
            $name = ($index + 1) . '. ' . $itemName;

            foreach ($this->thermalWrap($name, $lineWidth) as $line) {
                $receipt .= $line . "\r\n";
            }

            $meta = trim(implode(' | ', array_filter([
                $productCode ? 'Code: ' . $productCode : null,
                $brandName ? 'Brand: ' . $brandName : null,
                $variant ?: null,
            ])));

            if ($meta !== '') {
                foreach ($this->thermalWrap($meta, $lineWidth) as $line) {
                    $receipt .= $line . "\r\n";
                }
            }

            $qty = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');
            $discountAmount = (float) ($item->discount_amount ?? 0);
            $grossAmount = (float) $item->unit_price * (float) $item->quantity;
            $receipt .= $this->thermalPair(
                $qty . ' x ' . $money($item->unit_price),
                $money($discountAmount > 0 ? $grossAmount : $item->line_total),
                $lineWidth
            );

            if ($discountAmount > 0) {
                $receipt .= $this->thermalPair('  Discount on this item', '- ' . $money($discountAmount), $lineWidth);
                $receipt .= $this->thermalBold($this->thermalPair('  Net total', $money($item->line_total), $lineWidth));
            }
        }

        $receipt .= $rule;
        $receipt .= $this->thermalPair('Subtotal', $moneyWithCurrency($order->sub_total ?? 0), $lineWidth);
        $receipt .= $this->thermalPair('Discount', '- ' . $moneyWithCurrency($totalDiscount), $lineWidth);
        if ($couponCodes !== '') {
            $receipt .= $this->thermalPair('Coupon in discount', '- ' . $moneyWithCurrency($couponDiscountTotal), $lineWidth);
        }

        $receipt .= $this->thermalPair('VAT', '+ ' . $moneyWithCurrency($order->tax_total ?? 0), $lineWidth);

        if (($order->shipping_fee ?? 0) > 0) {
            $receipt .= $this->thermalPair('Delivery', '+ ' . $moneyWithCurrency($order->shipping_fee), $lineWidth);
        }

        $receipt .= $rule;
        $receipt .= $this->thermalBold($this->thermalPair('GRAND TOTAL', $moneyWithCurrency($receiptGrandTotal), $lineWidth));
        $receipt .= $rule;
        $receipt .= $this->thermalPair('Paid', $moneyWithCurrency($paidAmount), $lineWidth);

        if ($changeAmount > 0) {
            $receipt .= $this->thermalPair('Change', $moneyWithCurrency($changeAmount), $lineWidth);
        }

        if ($dueAmount > 0) {
            $receipt .= $this->thermalBold($this->thermalPair('DUE', $moneyWithCurrency($dueAmount), $lineWidth));
        }

        $receipt .= $rule;
        $receipt .= "\x1B\x61\x01";
        $receipt .= $this->thermalCenter('Thank you for shopping with us', $lineWidth);
        $receipt .= "\r\n";
        $receipt .= $this->thermalCenter($order->invoice_no ?? $order->order_no, $lineWidth);
        $receipt .= $this->thermalCenter('Printed: ' . $printedAt, $lineWidth);
        $receipt .= "\r\n\r\n\r\n";
        $receipt .= "\x1D\x56\x41\x10";

        return $receipt;
    }

    private function thermalCenter(?string $text, int $width): string
    {
        $text = $this->thermalText($text ?? '');

        if (strlen($text) >= $width) {
            return $text . "\r\n";
        }

        return str_pad($text, $width, ' ', STR_PAD_BOTH) . "\r\n";
    }

    private function thermalPair(?string $left, ?string $right, int $width): string
    {
        $left = $this->thermalText($left ?? '');
        $right = $this->thermalText($right ?? '');
        $available = max(1, $width - strlen($right));

        if (strlen($left) > $available) {
            $left = substr($left, 0, max(0, $available - 1)) . ' ';
        }

        return str_pad($left, $available) . $right . "\r\n";
    }

    private function thermalWrap(?string $text, int $width): array
    {
        $wrapped = wordwrap($this->thermalText($text ?? ''), $width, "\n", true);

        return explode("\n", $wrapped);
    }

    private function thermalBold(string $text): string
    {
        return "\x1B\x45\x01" . $text . "\x1B\x45\x00";
    }

    private function thermalText(?string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $converted === false ? $text : $converted;
    }
}
