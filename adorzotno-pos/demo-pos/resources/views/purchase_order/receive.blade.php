@extends('layouts.main')
@section('main.content')
<style>
.rx-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:.85rem; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.05); }
.rx-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.75rem 1.1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.rx-head-left { display:flex; align-items:center; gap:.65rem; }
.rx-num { width:26px; height:26px; border-radius:50%; background:#6366f1; color:#fff; font-size:.65rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rx-title { font-size:.92rem; font-weight:800; color:#1e293b; }
.rx-sub { font-size:.7rem; color:#64748b; margin-top:.1rem; }
.rx-stats { display:flex; gap:1.25rem; text-align:center; }
.rx-stat-lbl { font-size:.58rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; }
.rx-stat-val { font-size:1.1rem; font-weight:800; line-height:1; }

.rx-body { padding:.9rem 1.1rem; }
.rx-field-label { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; display:block; margin-bottom:.25rem; }

/* carton strip — same style as PO form */
.rx-carton-wrap { margin-top:.75rem; padding-top:.75rem; border-top:1px solid #f1f5f9; }
.rx-carton-title { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:#a5b4fc; margin-bottom:.55rem; display:flex; align-items:center; gap:.35rem; }
.rx-carton-row { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; padding:.45rem .65rem; background:#fafbff; border:1px solid #e8ecf8; border-radius:9px; margin-bottom:.4rem; }
.rx-carton-row .form-control { font-size:.85rem; padding:.35rem .5rem; border:1.5px solid #e2e8f0; border-radius:8px; background:#fff; }
.rx-carton-row .form-control:focus { border-color:#818cf8; box-shadow:0 0 0 3px rgba(129,140,248,.12); outline:none; }
.rx-carton-seq { font-size:.68rem; font-weight:800; color:#6366f1; background:#eff0ff; border-radius:5px; padding:2px 8px; white-space:nowrap; flex-shrink:0; }
.rx-carton-del { width:26px; height:26px; border:none; border-radius:7px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; font-size:.8rem; }
.rx-carton-del:hover { background:#fecaca; }
.rx-add-carton { display:flex; align-items:center; gap:.4rem; background:#f0f1ff; color:#4f46e5; border:1.5px dashed #c7d2fe; border-radius:8px; padding:.4rem 1rem; font-size:.8rem; font-weight:700; cursor:pointer; width:fit-content; transition:background .15s; }
.rx-add-carton:hover { background:#e0e7ff; }
.rx-carton-warn { font-size:.72rem; color:#dc2626; margin-top:.35rem; display:none; }

/* done badge */
.rx-done { text-align:center; padding:.9rem; color:#16a34a; font-size:.875rem; font-weight:700; }

/* summary sidebar */
.rx-summary { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem; box-shadow:0 1px 4px rgba(0,0,0,.04); position:sticky; top:75px; }
.rx-sum-row { display:flex; justify-content:space-between; font-size:.82rem; border-bottom:1px solid #f1f5f9; padding:.38rem 0; }
.rx-sum-row .l { color:#64748b; }
.rx-sum-row .v { font-weight:700; color:#0f172a; }
</style>

<div class="page-heading">
    <div class="page-title mb-3">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last"><h3>Receive Stock</h3></div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchaseOrder.show') }}">Purchase Orders</a></li>
                        <li class="breadcrumb-item active">Receive Stock</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @error('purchase_order')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">

        {{-- ── Sidebar summary ── --}}
        <div class="col-lg-3 col-12">
            <div class="rx-summary">
                <div style="font-size:.85rem;font-weight:800;color:#1e293b;margin-bottom:.75rem">
                    <i class="bi bi-receipt me-1 text-primary"></i> Purchase Summary
                </div>
                @foreach([
                    ['Purchase No',  $purchaseOrder->purchase_no],
                    ['Date',         \App\Support\DateFormatter::date($purchaseOrder->purchase_date)],
                    ['Supplier',     $purchaseOrder->supplier?->name  ?? 'N/A'],
                    ['Branch',       $purchaseOrder->branch?->name    ?? 'N/A'],
                    ['Warehouse',    $purchaseOrder->warehouse?->name ?? 'N/A'],
                    ['Grand Total',  '৳ '.number_format((float)$purchaseOrder->grand_total, 2)],
                ] as [$label, $val])
                <div class="rx-sum-row"><span class="l">{{ $label }}</span><span class="v">{{ $val }}</span></div>
                @endforeach
                <div style="margin-top:.75rem">
                    @php
                        $sc = match($purchaseOrder->status){
                            'received'=>['#dcfce7','#15803d'],
                            'partial' =>['#fef3c7','#92400e'],
                            'ordered' =>['#dbeafe','#1e40af'],
                            default   =>['#f1f5f9','#475569'],
                        };
                    @endphp
                    <span style="background:{{ $sc[0] }};color:{{ $sc[1] }};font-size:.72rem;font-weight:800;border-radius:5px;padding:3px 10px">
                        {{ ucfirst($purchaseOrder->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ── Items ── --}}
        <div class="col-lg-9 col-12">
            <form method="post" action="{{ route('purchaseOrder.receiveStore', $purchaseOrder->id) }}" id="receiveForm">
                @csrf

                @foreach($purchaseOrder->items as $index => $item)
                @php
                    $remaining = max(0, (int)$item->quantity - (int)$item->received_quantity);
                    $done = $remaining === 0;
                    $cp   = $item->carton_plan[0] ?? [];
                @endphp

                <div class="rx-card">
                    <div class="rx-head">
                        <div class="rx-head-left">
                            <span class="rx-num">{{ $index + 1 }}</span>
                            <div>
                                <div class="rx-title">{{ $item->sku?->product?->name ?? 'N/A' }}</div>
                                <div class="rx-sub">
                                    <span style="background:#1d4ed8;color:#fff;font-size:.62rem;font-weight:800;border-radius:4px;padding:1px 6px">{{ $item->sku?->sku_code ?? '—' }}</span>
                                    @if($item->sku?->variant_name)
                                    <span style="background:#e0e7ff;color:#3730a3;font-size:.62rem;font-weight:700;border-radius:4px;padding:1px 6px;margin-left:3px">{{ $item->sku->variant_name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="rx-stats">
                            <div>
                                <div class="rx-stat-lbl">Ordered</div>
                                <div class="rx-stat-val" style="color:#475569">{{ $item->quantity }}</div>
                            </div>
                            <div>
                                <div class="rx-stat-lbl">Received</div>
                                <div class="rx-stat-val" style="color:#059669">{{ $item->received_quantity }}</div>
                            </div>
                            <div>
                                <div class="rx-stat-lbl">Remaining</div>
                                <div class="rx-stat-val" style="color:{{ $remaining > 0 ? '#dc2626' : '#9ca3af' }}">{{ $remaining }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rx-body">
                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">

                        @if($done)
                        <div class="rx-done"><i class="bi bi-check-circle-fill me-1"></i> Fully received</div>
                        @else

                        <div class="row g-3">
                            <div class="col-md-2 col-6">
                                <span class="rx-field-label">Receive Now</span>
                                <input type="number" min="0" max="{{ $remaining }}"
                                    class="form-control receive-qty-input"
                                    name="items[{{ $index }}][received_quantity]"
                                    data-index="{{ $index }}"
                                    value="{{ old("items.$index.received_quantity", $remaining) }}"
                                    style="font-weight:800;font-size:1rem">
                                @error("items.$index.received_quantity")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="rx-field-label">Batch No</span>
                                <input class="form-control"
                                    name="items[{{ $index }}][batch_no]"
                                    placeholder="Auto if blank"
                                    value="{{ old("items.$index.batch_no", $item->batch_no) }}">
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="rx-field-label">Expiry Date</span>
                                <input type="date" class="form-control"
                                    name="items[{{ $index }}][expiry_date]"
                                    value="{{ old("items.$index.expiry_date", optional($item->expiry_date)->format('Y-m-d')) }}">
                            </div>
                        </div>

                        {{-- Carton tracking --}}
                        <div class="rx-carton-wrap">
                            <div class="rx-carton-title">
                                <i class="bi bi-box2-fill"></i> Carton Tracking
                                <em style="font-style:normal;font-weight:500;color:#c7d2fe;text-transform:none;letter-spacing:0">— optional</em>
                            </div>

                            <div id="carton-list-{{ $index }}">
                            @php
                                $defaultCartons = collect($item->carton_plan ?? [])->map(fn($p) => [
                                    'carton_code'      => $p['name'] ?? '',
                                    'boxes_per_carton' => $p['boxes'] ?? '',
                                    'units_per_box'    => $p['units_per_box'] ?? '',
                                    'unit_cost'        => $p['unit_cost'] ?? '',
                                    'batch_no'         => $p['batch_no'] ?? '',
                                    'expiry_date'      => $p['expiry_date'] ?? '',
                                ])->all();
                                if(empty($defaultCartons)) $defaultCartons = [[]];
                                $oldCartons = old("items.$index.cartons", $defaultCartons);
                            @endphp
                            @foreach($oldCartons as $ci => $carton)
                            <div class="rx-carton-row" data-item="{{ $index }}" data-ci="{{ $ci }}">
                                <span class="rx-carton-seq">{{ $ci + 1 }}</span>
                                <input type="text" class="form-control" style="flex:1;min-width:100px"
                                    placeholder="Carton code (optional)"
                                    name="items[{{ $index }}][cartons][{{ $ci }}][carton_code]"
                                    value="{{ $carton['carton_code'] ?? '' }}">
                                <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap">Boxes</span>
                                <input type="number" min="0" class="form-control carton-boxes" style="width:75px"
                                    placeholder="0"
                                    name="items[{{ $index }}][cartons][{{ $ci }}][boxes_per_carton]"
                                    value="{{ $carton['boxes_per_carton'] ?? '' }}"
                                    oninput="updateCartonCalc(this)">
                                <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap">Units/Box</span>
                                <input type="number" min="0" class="form-control carton-units" style="width:75px"
                                    placeholder="0"
                                    name="items[{{ $index }}][cartons][{{ $ci }}][units_per_box]"
                                    value="{{ $carton['units_per_box'] ?? '' }}"
                                    oninput="updateCartonCalc(this)">
                                <span style="font-size:.8rem;font-weight:700;color:#6366f1;white-space:nowrap;min-width:60px" class="carton-total-display">
                                    @php $bx=$carton['boxes_per_carton']??0; $ux=$carton['units_per_box']??0; @endphp
                                    {{ ($bx && $ux) ? '= '.number_format($bx*$ux) : '' }}
                                </span>
                                @if($ci > 0)
                                <button type="button" class="rx-carton-del" onclick="removeCarton(this)" title="Remove"><i class="bi bi-x"></i></button>
                                @endif
                            </div>
                            @endforeach
                            </div>

                            <button type="button" class="rx-add-carton mt-1" onclick="addCarton({{ $index }})">
                                <i class="bi bi-plus-circle"></i> Add Carton
                            </button>
                            <div class="rx-carton-warn" id="warn-{{ $index }}"></div>
                        </div>

                        @endif
                    </div>
                </div>
                @endforeach

                @if(!in_array($purchaseOrder->status, ['received','canceled']))
                <div class="d-flex justify-content-end gap-2 mt-2 mb-4">
                    <a href="{{ route('purchaseOrder.show') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5">
                        <i class="bi bi-check2-circle me-1"></i> Receive Stock
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer.js')
<script>
var cartonCounts = {};
@foreach($purchaseOrder->items as $index => $item)
@php $cnt = max(1, count(old("items.$index.cartons", [[]]))) @endphp
cartonCounts[{{ $index }}] = {{ $cnt }};
@endforeach

function addCarton(itemIndex) {
    var ci = cartonCounts[itemIndex] || 0;
    cartonCounts[itemIndex] = ci + 1;
    var html = `<div class="rx-carton-row" data-item="${itemIndex}" data-ci="${ci}">
        <span class="rx-carton-seq">${ci + 1}</span>
        <input type="text" class="form-control" style="flex:1;min-width:100px" placeholder="Carton code (optional)"
            name="items[${itemIndex}][cartons][${ci}][carton_code]">
        <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap">Boxes</span>
        <input type="number" min="0" class="form-control carton-boxes" style="width:75px" placeholder="0"
            name="items[${itemIndex}][cartons][${ci}][boxes_per_carton]" oninput="updateCartonCalc(this)">
        <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap">Units/Box</span>
        <input type="number" min="0" class="form-control carton-units" style="width:75px" placeholder="0"
            name="items[${itemIndex}][cartons][${ci}][units_per_box]" oninput="updateCartonCalc(this)">
        <span class="carton-total-display" style="font-size:.8rem;font-weight:700;color:#6366f1;white-space:nowrap;min-width:60px"></span>
        <button type="button" class="rx-carton-del" onclick="removeCarton(this)"><i class="bi bi-x"></i></button>
    </div>`;
    $('#carton-list-' + itemIndex).append(html);
    renumberCartons(itemIndex);
}

function removeCarton(btn) {
    var row = $(btn).closest('.rx-carton-row');
    var idx = row.data('item');
    row.remove();
    renumberCartons(idx);
    checkTotals(idx);
}

function renumberCartons(idx) {
    $('#carton-list-' + idx + ' .rx-carton-row').each(function(i) {
        $(this).find('.rx-carton-seq').text(i + 1);
        $(this).attr('data-ci', i);
        $(this).find('input').each(function() {
            var n = $(this).attr('name');
            if(n) $(this).attr('name', n.replace(/cartons\[\d+\]/, 'cartons[' + i + ']'));
        });
    });
}

function updateCartonCalc(input) {
    var row = $(input).closest('.rx-carton-row');
    var b = parseInt(row.find('.carton-boxes').val()) || 0;
    var u = parseInt(row.find('.carton-units').val()) || 0;
    var total = b * u;
    row.find('.carton-total-display').text(total > 0 ? '= ' + total.toLocaleString() : '');
    checkTotals(row.data('item'));
}

function checkTotals(idx) {
    var receiveQty = parseInt($('input[name="items[' + idx + '][received_quantity]"]').val()) || 0;
    var cartonTotal = 0;
    $('#carton-list-' + idx + ' .rx-carton-row').each(function() {
        var b = parseInt($(this).find('.carton-boxes').val()) || 0;
        var u = parseInt($(this).find('.carton-units').val()) || 0;
        cartonTotal += b * u;
    });
    var warn = $('#warn-' + idx);
    if(cartonTotal > 0 && receiveQty > 0 && cartonTotal !== receiveQty) {
        warn.show().text('⚠ Carton total (' + cartonTotal + ') ≠ Receive qty (' + receiveQty + ')');
    } else {
        warn.hide();
    }
}

$(document).on('input', '.receive-qty-input', function() {
    checkTotals($(this).data('index'));
});
</script>
@endsection
