@extends('layouts.main')
@section('main.content')
<style>
/* ── Stat cards ─────────────────────────── */
.stat-card {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid transparent;
}
.stat-card .stat-icon {
    width: 46px; height: 46px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.stat-card .stat-value { font-size: 1.45rem; font-weight: 800; line-height: 1; }
.stat-card .stat-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: .2rem; }

.stat-total   { background: #f0f4ff; border-color: #c7d7f9; }
.stat-total   .stat-icon { background: #dbe4ff; color: #3b5bdb; }
.stat-total   .stat-value { color: #3b5bdb; }
.stat-total   .stat-label { color: #5c7cfa; }

.stat-in      { background: #eefcf3; border-color: #bbf0ce; }
.stat-in      .stat-icon { background: #d3f9e0; color: #2f9e44; }
.stat-in      .stat-value { color: #2f9e44; }
.stat-in      .stat-label { color: #40c057; }

.stat-low     { background: #fff8f0; border-color: #ffd8a8; }
.stat-low     .stat-icon { background: #ffe8cc; color: #e67700; }
.stat-low     .stat-value { color: #e67700; }
.stat-low     .stat-label { color: #fd7e14; }

.stat-out     { background: #fff1f2; border-color: #ffc9cc; }
.stat-out     .stat-icon { background: #ffe0e3; color: #c92a2a; }
.stat-out     .stat-value { color: #c92a2a; }
.stat-out     .stat-label { color: #fa5252; }

.stat-value-card { background: #f3f0ff; border-color: #c5b3f9; }
.stat-value-card .stat-icon { background: #e5dbff; color: #7048e8; }
.stat-value-card .stat-value { color: #7048e8; font-size: 1.15rem; }
.stat-value-card .stat-label { color: #9775fa; }

/* ── Filter card ────────────────────────── */
.stock-report-card {
    border: 1px solid #e3ebf3;
    border-radius: 1rem;
    box-shadow: 0 8px 28px rgba(15,23,42,.07);
    overflow: visible;
}
.stock-report-card .card-header {
    background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
    border-bottom: 1px solid #e3ebf3;
}
.filter-section {
    background: #f8fafc;
    border-bottom: 1px solid #e8eef5;
    padding: 1rem 1.25rem;
}
.stock-filter-label {
    color: #40546a;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* ── Table ──────────────────────────────── */
#stockTable { width: 100% !important; }

#stockTable thead th {
    background: #f0f4fa;
    color: #3a5068;
    border-bottom: 2px solid #d4e0ee;
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .75rem .75rem;
    white-space: nowrap;
}
#stockTable tbody td {
    padding: .7rem .75rem;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}
#stockTable tbody tr:hover { background: #f5f8ff; }
#stockTable tbody tr:nth-child(even) { background: #fafbfd; }
#stockTable tbody tr:nth-child(even):hover { background: #f5f8ff; }

/* Product cell */
.sp-name {
    font-size: .875rem;
    font-weight: 800;
    color: #0f2137;
    line-height: 1.3;
    white-space: nowrap;
    max-width: 175px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sp-meta {
    font-size: .68rem;
    font-weight: 500;
    color: #94a3b8;
    margin-top: .15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 175px;
}
.sp-variant {
    display: inline-block;
    margin-top: .2rem;
    background: #e0e7ff;
    color: #3730a3;
    font-size: .62rem;
    font-weight: 700;
    border-radius: 4px;
    padding: 1px 6px;
    white-space: nowrap;
}

/* SKU cell */
.sp-sku-code {
    display: inline-block;
    background: #1d4ed8;
    color: #fff;
    font-size: .72rem;
    font-weight: 800;
    border-radius: 5px;
    padding: 2px 8px;
    letter-spacing: .03em;
    white-space: nowrap;
}
.sp-barcode {
    font-size: .68rem;
    font-weight: 500;
    color: #94a3b8;
    margin-top: .2rem;
    white-space: nowrap;
}

/* Location cell */
.sp-branch {
    font-size: .82rem;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
}
.sp-warehouse {
    font-size: .7rem;
    font-weight: 500;
    color: #64748b;
    margin-top: .1rem;
    white-space: nowrap;
}

/* Available qty */
.sp-qty {
    display: inline-block;
    min-width: 44px;
    text-align: center;
    background: #ecfdf5;
    color: #065f46;
    font-size: 1rem;
    font-weight: 900;
    border-radius: 8px;
    padding: 3px 10px;
    border: 1px solid #a7f3d0;
}
.sp-qty.zero {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fca5a5;
}
.sp-qty.low {
    background: #fffbeb;
    color: #92400e;
    border-color: #fcd34d;
}

/* Reserved */
.sp-reserved {
    font-size: .85rem;
    font-weight: 700;
    color: #64748b;
}

/* Strip/Pcs */
.sp-strip-num { font-size: .85rem; font-weight: 800; color: #1e293b; }
.sp-strip-pcs { font-size: .68rem; font-weight: 500; color: #94a3b8; }

/* Prices */
.sp-cost   { font-size: .82rem; font-weight: 700; color: #374151; }
.sp-retail { font-size: .82rem; font-weight: 700; color: #059669; }
.sp-val    { font-size: .85rem; font-weight: 800; color: #6d28d9; }

/* Reorder level */
.sp-reorder { font-size: .8rem; font-weight: 600; color: #64748b; }

/* Last updated */
.sp-date-day  { font-size: .8rem; font-weight: 700; color: #374151; white-space: nowrap; }
.sp-date-time { font-size: .7rem; font-weight: 500; color: #94a3b8; white-space: nowrap; margin-top: .05rem; }

/* Status */
.stock-status {
    display: inline-flex;
    align-items: center;
    border-radius: 6px;
    padding: .28rem .65rem;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .03em;
    white-space: nowrap;
}
.stock-status-in  { background: #d1fae5; color: #065f46; }
.stock-status-low { background: #fef3c7; color: #92400e; }
.stock-status-out { background: #fee2e2; color: #991b1b; }

.qty-range-sep { line-height: 34px; color: #adb5bd; font-weight: 700; text-align: center; }

/* ── DataTables layout ──────────────────── */
#stockTable_wrapper {
    width: 100%;
}
.stock-table-scroll {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    width: 100%;
}
.stock-table-scroll::-webkit-scrollbar { height: 6px; }
.stock-table-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
.stock-table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
#stockTable_wrapper .dataTables_info,
#stockTable_wrapper .dataTables_paginate {
    padding: .75rem 1.25rem;
}
</style>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Product Stock Report</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Stock Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    {{-- ── Summary Stat Cards ─────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="stat-value" id="stat-total">—</div>
                    <div class="stat-label">Total SKUs</div>
                    <div style="font-size:.6rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-in">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value" id="stat-in">—</div>
                    <div class="stat-label">In Stock</div>
                    <div style="font-size:.6rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-low">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-value" id="stat-low">—</div>
                    <div class="stat-label">Low Stock</div>
                    <div style="font-size:.6rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-out">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value" id="stat-out">—</div>
                    <div class="stat-label">Out of Stock</div>
                    <div style="font-size:.6rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="stat-card stat-value-card">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <div class="stat-value" id="stat-value">—</div>
                    <div class="stat-label">Total Stock Value</div>
                    <div style="font-size:.6rem;color:#94a3b8;font-weight:700;margin-top:.1rem"><i class="bi bi-dot"></i>Live Data</div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card stock-report-card">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-1">Current Product Stock</h5>
                    <div class="text-muted small">Product / SKU wise available stock by branch and warehouse.</div>
                </div>
                <button type="button" class="btn btn-success fw-bold" onclick="downloadStockExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i> Excel Export
                </button>
            </div>

            {{-- ── Filters ────────────────────────────────── --}}
            <div class="filter-section">
                <div class="row g-2 align-items-end">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Branch</label>
                        <select id="branchId" class="form-select form-select-sm stock-filter">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Warehouse</label>
                        <select id="warehouseId" class="form-select form-select-sm stock-filter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->code ? ' ('.$warehouse->code.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Category</label>
                        <select id="categoryId" class="form-select form-select-sm stock-filter">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Brand</label>
                        <select id="brandId" class="form-select form-select-sm stock-filter">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Stock Status</label>
                        <select id="stockStatus" class="form-select form-select-sm stock-filter">
                            <option value="">All Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Qty Range</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="minQty" class="form-control stock-filter" placeholder="Min" min="0" style="width:50px">
                            <span class="input-group-text px-1 text-muted">–</span>
                            <input type="number" id="maxQty" class="form-control stock-filter" placeholder="Max" min="0" style="width:50px">
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">Cost Price Range</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="minCost" class="form-control" placeholder="Min" min="0" step="0.01" style="width:50px">
                            <span class="input-group-text px-1 text-muted">–</span>
                            <input type="number" id="maxCost" class="form-control" placeholder="Max" min="0" step="0.01" style="width:50px">
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label stock-filter-label mb-1">SKU / Barcode</label>
                        <input type="text" id="skuSearch" class="form-control form-control-sm" placeholder="Search SKU or barcode...">
                    </div>
                    <div class="col-md-2 col-6 d-flex align-items-end gap-1">
                        <button type="button" id="applyFilters" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel-fill me-1"></i>Apply
                        </button>
                        <button type="button" id="resetStockFilters" class="btn btn-light btn-sm w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="stock-table-scroll">
                    <table id="stockTable" class="table align-middle mb-0" style="min-width:1100px">
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
let stockTable;
let filterValues = {};

$(document).ready(function () {
    stockTable = $('#stockTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        dom: 'frt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 pb-3"ip>',
        ajax: {
            url: "{{ route('stocks.list') }}",
            type: "POST",
            data: function (d) {
                d._token       = "{{ csrf_token() }}";
                d.branch_id    = $('#branchId').val();
                d.warehouse_id = $('#warehouseId').val();
                d.category_id  = $('#categoryId').val();
                d.brand_id     = $('#brandId').val();
                d.stock_status = $('#stockStatus').val();
                d.min_qty      = $('#minQty').val();
                d.max_qty      = $('#maxQty').val();
                d.min_cost     = filterValues.minCost || '';
                d.max_cost     = filterValues.maxCost || '';
                d.sku_search   = filterValues.skuSearch || '';
            },
        },
        columns: [
            {
                title: 'Product',
                data: 'product',
                name: 'product_name',
                className: 'text-start',
                orderable: false,
                searchable: true,
                width: '180px',
            },
            {
                title: 'SKU / Barcode',
                data: 'sku',
                name: 'sku_code',
                className: 'text-start',
                orderable: false,
                searchable: true,
                width: '120px',
            },
            {
                title: 'Location',
                data: null,
                className: 'text-start',
                orderable: false,
                searchable: false,
                width: '140px',
                render: function (data) {
                    return '<div class="sp-branch"><i class="bi bi-geo-alt-fill me-1 text-primary" style="font-size:.7rem"></i>' + (data.branch || 'N/A') + '</div>'
                        + '<div class="sp-warehouse"><i class="bi bi-building me-1" style="font-size:.65rem"></i>' + (data.warehouse || 'N/A') + '</div>';
                }
            },
            {
                title: 'Available',
                data: 'available_stock',
                name: 'available_stock',
                className: 'text-center',
                orderable: true,
                searchable: false,
                width: '80px',
                render: function (data, type, row) {
                    var cls = 'sp-qty';
                    if (data <= 0) cls += ' zero';
                    else if (row.reorder_level > 0 && data <= row.reorder_level) cls += ' low';
                    return '<span class="' + cls + '">' + data + '</span>';
                }
            },
            {
                title: 'Reserved',
                data: 'reserved_stock',
                name: 'reserved_stock',
                className: 'text-center',
                orderable: true,
                searchable: false,
                width: '80px',
                render: function (data) {
                    return '<span class="sp-reserved">' + data + '</span>';
                }
            },
            {
                title: 'Strip/Pcs',
                data: 'stock_pack',
                name: 'stock_pack',
                className: 'text-center',
                orderable: false,
                searchable: false,
                width: '80px',
            },
            {
                title: 'Cost',
                data: 'cost_price',
                name: 'cost_price',
                className: 'text-end',
                orderable: false,
                searchable: false,
                width: '80px',
                render: function (data) {
                    return '<div style="font-size:.62rem;color:#94a3b8;font-weight:600">COST</div>'
                        + '<span class="sp-cost">৳ ' + data + '</span>';
                }
            },
            {
                title: 'Retail',
                data: 'retail_price',
                name: 'retail_price',
                className: 'text-end',
                orderable: false,
                searchable: false,
                width: '80px',
                render: function (data) {
                    return '<div style="font-size:.62rem;color:#94a3b8;font-weight:600">RETAIL</div>'
                        + '<span class="sp-retail">৳ ' + data + '</span>';
                }
            },
            {
                title: 'Stock Value',
                data: 'stock_value',
                name: 'stock_value',
                className: 'text-end',
                orderable: false,
                searchable: false,
                width: '95px',
                render: function (data) {
                    return '<span class="sp-val">৳ ' + data + '</span>';
                }
            },
            {
                title: 'Reorder',
                data: 'reorder_level',
                name: 'reorder_level',
                className: 'text-center',
                orderable: true,
                searchable: false,
                width: '75px',
                render: function (data) {
                    return '<span class="sp-reorder"><i class="bi bi-bell me-1" style="font-size:.65rem"></i>' + data + '</span>';
                }
            },
            {
                title: 'Updated',
                data: 'updated_at',
                name: 'updated_at',
                className: 'text-center',
                orderable: true,
                searchable: false,
                width: '100px',
                render: function (data) {
                    if (!data) return '<span class="sp-date-time">—</span>';
                    var parts = data.split('|');
                    return '<div class="sp-date-day">' + (parts[0] || data) + '</div>'
                         + '<div class="sp-date-time">' + (parts[1] || '') + '</div>';
                }
            },
            {
                title: 'Status',
                data: 'status_badge',
                name: 'status',
                className: 'text-center',
                orderable: false,
                searchable: false,
                width: '80px',
            },
        ],
        drawCallback: function () {
            loadSummary();
        },
        language: {
            processing: '<div class="text-primary fw-bold py-3"><i class="bi bi-arrow-repeat me-1"></i> Loading stock data...</div>',
            emptyTable: '<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No stock records found</div>',
            zeroRecords: '<div class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-2"></i>No matching records found</div>',
        },
    });

    // Apply button
    $('#applyFilters').on('click', function () {
        filterValues.minCost   = $('#minCost').val();
        filterValues.maxCost   = $('#maxCost').val();
        filterValues.skuSearch = $('#skuSearch').val();
        reloadStockTable();
    });

    // Live filter on select change
    $('.stock-filter').on('change', reloadStockTable);

    // Enter key on text/number inputs
    $('#minQty, #maxQty, #minCost, #maxCost, #skuSearch').on('keydown', function (e) {
        if (e.key === 'Enter') $('#applyFilters').trigger('click');
    });

    // Reset
    $('#resetStockFilters').on('click', function () {
        $('#branchId, #warehouseId, #categoryId, #brandId, #stockStatus').val('');
        $('#minQty, #maxQty, #minCost, #maxCost, #skuSearch').val('');
        filterValues = {};
        reloadStockTable();
    });
});

function reloadStockTable() {
    stockTable.ajax.reload();
}

function loadSummary() {
    $.post("{{ route('stocks.summary') }}", {
        _token:       "{{ csrf_token() }}",
        branch_id:    $('#branchId').val(),
        warehouse_id: $('#warehouseId').val(),
        category_id:  $('#categoryId').val(),
        brand_id:     $('#brandId').val(),
        stock_status: $('#stockStatus').val(),
        min_qty:      $('#minQty').val(),
        max_qty:      $('#maxQty').val(),
        min_cost:     filterValues.minCost || '',
        max_cost:     filterValues.maxCost || '',
        sku_search:   filterValues.skuSearch || '',
    }, function (data) {
        $('#stat-total').text(data.total);
        $('#stat-in').text(data.in_stock);
        $('#stat-low').text(data.low_stock);
        $('#stat-out').text(data.out_of_stock);
        $('#stat-value').text('৳ ' + data.total_value);
    });
}

function downloadStockExcel() {
    const params = new URLSearchParams({
        branch_id:    $('#branchId').val()    || '',
        warehouse_id: $('#warehouseId').val() || '',
        category_id:  $('#categoryId').val()  || '',
        brand_id:     $('#brandId').val()     || '',
        stock_status: $('#stockStatus').val() || '',
        min_qty:      $('#minQty').val()      || '',
        max_qty:      $('#maxQty').val()      || '',
    });
    window.location.href = "{{ route('stocks.excel') }}?" + params.toString();
}
</script>
@endsection
