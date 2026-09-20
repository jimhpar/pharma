@extends('layouts.main')

@section('header.css')
<style>
.kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.kpi-info h4 {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}
.kpi-info .val {
    font-size: 26px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1;
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.kpi-blue { background: #eff6ff; color: #2563eb; }
.kpi-green { background: #ecfdf5; color: #059669; }
.kpi-red { background: #fef2f2; color: #dc2626; }
.kpi-purple { background: #faf5ff; color: #7c3aed; }

.badge-central {
    background: #4f46e5;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-branch {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    font-size: 12px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 6px;
    margin-right: 4px;
    margin-bottom: 4px;
    display: inline-block;
}
.badge-branch strong {
    color: #059669;
    font-weight: 700;
}
.badge-branch.zero strong {
    color: #9ca3af;
}

.table-thumb {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
}

.filter-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.filter-item {
    min-width: 170px;
    flex: 1;
}

.modal-branch-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    text-align: center;
}
.modal-branch-card .label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}
.modal-branch-card .qty {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
}

.action-btn {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
    border: none;
}
.btn-manage {
    background: #e0e7ff;
    color: #4338ca;
}
.btn-manage:hover {
    background: #c7d2fe;
}
.btn-quick-add {
    background: #ecfdf5;
    color: #047857;
}
.btn-quick-add:hover {
    background: #d1fae5;
}
</style>
@endsection

@section('main.content')
<div class="page-heading">
    <div class="list-root">

        {{-- Page Header --}}
        <div class="list-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-box-seam" style="color:#4f46e5;font-size:1.4rem"></i> Available Stock Management</h2>
                <p class="text-muted mb-0">Overview and real-time management of Central and Branch-wise inventory</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" id="btnRefreshList">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Data
                </button>
            </div>
        </div>

        {{-- Top KPI Cards --}}
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-info">
                    <h4>Total Products</h4>
                    <p class="val">{{ number_format($totalProducts) }}</p>
                </div>
                <div class="kpi-icon kpi-blue">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h4>In-Stock Products</h4>
                    <p class="val" style="color:#059669">{{ number_format($inStockSkusCount) }}</p>
                </div>
                <div class="kpi-icon kpi-green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h4>Out of Stock</h4>
                    <p class="val" style="color:#dc2626">{{ number_format($outOfStockCount) }}</p>
                </div>
                <div class="kpi-icon kpi-red">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h4>Total Central Units</h4>
                    <p class="val" style="color:#7c3aed">{{ number_format($totalStockUnits) }}</p>
                </div>
                <div class="kpi-icon kpi-purple">
                    <i class="bi bi-layers-fill"></i>
                </div>
            </div>
        </div>

        {{-- Filter and Search Card --}}
        <div class="filter-bar mb-4 p-3 bg-white rounded border">
            <div class="row g-3">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label text-xs fw-semibold text-muted mb-1">Search Product / SKU / Barcode</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="fSearch" class="form-control border-start-0" placeholder="Search by name, SKU ID or barcode…" autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="fSearchClear" style="display:none;"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted mb-1">Branch / Warehouse</label>
                    <select id="fWarehouse" class="form-select">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->branch?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted mb-1">Stock Status</label>
                    <select id="fStockStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="in_stock">In Stock (&gt; 0)</option>
                        <option value="low_stock">Low Stock (&le; 10)</option>
                        <option value="out_of_stock">Out of Stock (= 0)</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted mb-1">Category</label>
                    <select id="fCategory" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Available Stock Table --}}
        <div class="table-card bg-white p-3 rounded border shadow-sm">
            <div class="table-responsive">
                <table id="availableStockTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Image</th>
                            <th style="min-width: 200px;">Product & Manufacturer</th>
                            <th style="min-width: 130px;">SKU & Barcode</th>
                            <th style="width: 120px;" class="text-center">Central Stock</th>
                            <th style="min-width: 250px;">Branch Stock Breakdown</th>
                            <th style="min-width: 140px;">Mfg / Expiry</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 180px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- MODAL: Manage Stock (Central & Branch details + Batch list with Add/Edit/Delete) --}}
<div class="modal fade" id="manageStockModal" tabindex="-1" aria-labelledby="manageStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="manageStockModalLabel">Stock Details &amp; Batches</h5>
                    <p class="text-muted small mb-0" id="modalProductSubtitle">Manage inventory across branches</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- Product Overview & Branch Stock Cards --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="modal-branch-card border-primary" style="background:#eef2ff;">
                            <div class="label fw-bold text-primary">CENTRAL STOCK</div>
                            <div class="qty text-primary" id="modalCentralStock">0</div>
                            <small class="text-muted">Total across all branches</small>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-2" id="modalBranchCards">
                            {{-- Dynamically filled --}}
                        </div>
                    </div>
                </div>

                {{-- Batch List Section --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-stack"></i> Inventory Batches for this SKU</h6>
                    <button type="button" class="btn btn-sm btn-success" id="btnOpenAddFromModal">
                        <i class="bi bi-plus-circle"></i> Add New Stock Batch
                    </button>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch No</th>
                                <th>Branch / Warehouse</th>
                                <th>Available Qty</th>
                                <th>Unit Cost</th>
                                <th>Manufacture Date</th>
                                <th>Expiry Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="modalBatchTableBody">
                            <tr><td colspan="7" class="text-center py-3 text-muted">Loading batches...</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Add Stock --}}
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addStockForm">
                @csrf
                <input type="hidden" name="sku_id" id="addSkuId">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-bold" id="addStockModalLabel"><i class="bi bi-plus-circle"></i> Add Stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-2 bg-light rounded">
                        <strong id="addProductName">Product Name</strong>
                        <div class="text-muted small" id="addProductSku">SKU: N/A</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Warehouse / Branch <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_id" id="addWarehouseId" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->branch?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Batch Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="batch_no" id="addBatchNo" required placeholder="e.g. BAT-2026-001">
                        </div>
                        <div class="col-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btnGenerateBatch">Generate</button>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="addQuantity" min="1" required placeholder="e.g. 50">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Unit Purchase Cost (৳)</label>
                            <input type="number" step="0.01" class="form-control" name="purchase_price" id="addPurchasePrice" placeholder="0.00">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Manufacture Date</label>
                            <input type="date" class="form-control" name="manufacture_date" id="addMfgDate">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" id="addExpDate">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Remarks / Note</label>
                        <input type="text" class="form-control" name="remarks" placeholder="Optional remarks...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitAddStock">
                        <i class="bi bi-check-lg"></i> Save Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Edit Stock Batch --}}
<div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editStockForm">
                @csrf
                <input type="hidden" id="editBatchId">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold" id="editStockModalLabel"><i class="bi bi-pencil-square"></i> Edit Stock Batch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Batch Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="batch_no" id="editBatchNo" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Available Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="editQuantity" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Unit Purchase Cost (৳)</label>
                            <input type="number" step="0.01" class="form-control" name="purchase_price" id="editPurchasePrice">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Manufacture Date</label>
                            <input type="date" class="form-control" name="manufacture_date" id="editMfgDate">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" id="editExpDate">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Adjustment Reason / Remarks</label>
                        <input type="text" class="form-control" name="remarks" id="editRemarks" placeholder="e.g. Stock audit, correction">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitEditStock">
                        <i class="bi bi-check-lg"></i> Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Delete Stock Batch Confirm --}}
<div class="modal fade" id="deleteStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="text-danger mb-2">
                <i class="bi bi-exclamation-octagon" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold mb-2">Delete Batch Stock?</h5>
            <p class="text-muted small mb-4">This will remove this stock batch from the warehouse and update the central inventory immediately.</p>
            <input type="hidden" id="deleteBatchId">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteStock">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var searchTimer;
    var currentSelectedSkuId = null;

    // Initialize DataTable
    var table = $('#availableStockTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        pageLength: 25,
        ajax: {
            url: "{{ route('availableStock.list') }}",
            type: "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}";
                d.q = $('#fSearch').val();
                d.warehouse_id = $('#fWarehouse').val();
                d.stock_status = $('#fStockStatus').val();
                d.category_id = $('#fCategory').val();
            }
        },
        columns: [
            {
                data: 'thumbnail',
                orderable: false,
                render: function (data, type, row) {
                    var src = data ? data : "{{ url('public/admin/dist/assets/compiled/png/AdorzotnoLogo.png') }}";
                    return '<img src="' + src + '" class="table-thumb" alt="Product">';
                }
            },
            {
                data: 'product_name',
                render: function (data, type, row) {
                    return '<div class="fw-bold text-dark">' + data + '</div>' +
                           '<div class="text-muted small">' +
                           '<span class="badge bg-light text-secondary me-1">' + row.category_name + '</span>' +
                           '<i class="bi bi-building"></i> ' + row.manufacturer +
                           '</div>';
                }
            },
            {
                data: 'sku_code',
                render: function (data, type, row) {
                    return '<div class="fw-semibold text-monospace">' + data + '</div>' +
                           '<div class="text-muted small">Barcode: ' + row.barcode + '</div>';
                }
            },
            {
                data: 'central_stock',
                className: 'text-center',
                render: function (data) {
                    var cls = data > 0 ? 'bg-primary' : 'bg-secondary';
                    return '<span class="badge ' + cls + ' fs-6 px-3 py-2">' + data + ' units</span>';
                }
            },
            {
                data: 'branches',
                orderable: false,
                render: function (data) {
                    if (!data || !data.length) return '<span class="text-muted">No branch data</span>';
                    var html = '';
                    data.forEach(function (b) {
                        var isZero = b.stock === 0;
                        html += '<span class="badge-branch ' + (isZero ? 'zero' : '') + '">' +
                                b.branch_name + ': <strong>' + b.stock + '</strong></span>';
                    });
                    return html;
                }
            },
            {
                data: 'nearest_expiry',
                orderable: false,
                render: function (data, type, row) {
                    return '<div class="small"><strong>Exp:</strong> ' + data + '</div>' +
                           '<div class="text-muted small"><strong>Mfg:</strong> ' + row.latest_manufacture + '</div>';
                }
            },
            {
                data: 'status',
                className: 'text-center',
                render: function (data) {
                    if (data === 'in_stock') {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle"></i> In Stock</span>';
                    }
                    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle"></i> Out of Stock</span>';
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return '<div class="d-flex justify-content-center gap-1">' +
                           '<button class="action-btn btn-manage btn-manage-stock" data-sku="' + row.sku_id + '" title="Manage & View Batches">' +
                           '<i class="bi bi-eye"></i> Manage Stock</button>' +
                           '<button class="action-btn btn-quick-add btn-add-stock-quick" data-sku="' + row.sku_id + '" data-name="' + escapeHtml(row.product_name) + '" data-code="' + row.sku_code + '" title="Add Stock">' +
                           '<i class="bi bi-plus"></i> Add</button>' +
                           '</div>';
                }
            }
        ],
        language: {
            emptyTable: "No products or stock records found matching your filters"
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
    }

    // Live Search with Debounce
    $('#fSearch').on('input', function () {
        var val = $(this).val();
        $('#fSearchClear').toggle(val.length > 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            table.draw();
        }, 350);
    });

    $('#fSearchClear').on('click', function () {
        $('#fSearch').val('').trigger('input');
    });

    // Dropdown filters
    $('#fWarehouse, #fStockStatus, #fCategory').on('change', function () {
        table.draw();
    });

    $('#btnResetFilters').on('click', function () {
        $('#fSearch').val('');
        $('#fSearchClear').hide();
        $('#fWarehouse').val('');
        $('#fStockStatus').val('');
        $('#fCategory').val('');
        table.draw();
    });

    $('#btnRefreshList').on('click', function () {
        table.ajax.reload(null, false);
    });

    // Open Manage Stock Modal
    $(document).on('click', '.btn-manage-stock', function () {
        var skuId = $(this).data('sku');
        currentSelectedSkuId = skuId;
        loadProductStockDetails(skuId);
    });

    function loadProductStockDetails(skuId) {
        $('#modalBatchTableBody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading batches...</td></tr>');
        $('#modalBranchCards').html('');
        $('#modalCentralStock').text('...');

        $.ajax({
            url: "{{ url('available-stock/product-details') }}/" + skuId,
            type: "GET",
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Failed to load details');
                    return;
                }
                var p = res.product;
                $('#manageStockModalLabel').text(p.name);
                $('#modalProductSubtitle').html('SKU: <strong>' + p.sku_code + '</strong> | Manufacturer: ' + p.manufacturer + ' | Retail Price: ৳' + p.retail_price);
                $('#modalCentralStock').text(p.central_stock + ' units');

                // Render branch stock breakdown cards
                var cardsHtml = '';
                p.branches.forEach(function (b) {
                    cardsHtml += '<div class="col-4">' +
                                 '<div class="modal-branch-card">' +
                                 '<div class="label text-truncate">' + b.branch_name + '</div>' +
                                 '<div class="qty text-success">' + b.stock + ' <span style="font-size:12px;color:#6b7280;">units</span></div>' +
                                 '</div></div>';
                });
                $('#modalBranchCards').html(cardsHtml);

                // Store info for "Add New Stock" from inside modal
                $('#btnOpenAddFromModal').data('sku', p.sku_id);
                $('#btnOpenAddFromModal').data('name', p.name);
                $('#btnOpenAddFromModal').data('code', p.sku_code);

                // Render batches table
                var batchRows = '';
                if (!p.batches || !p.batches.length) {
                    batchRows = '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-info-circle"></i> No active batches available for this product in any warehouse.</td></tr>';
                } else {
                    p.batches.forEach(function (b) {
                        batchRows += '<tr>' +
                                     '<td><span class="fw-bold text-dark font-monospace">' + b.batch_no + '</span></td>' +
                                     '<td>' + b.warehouse_name + ' (' + b.branch_name + ')</td>' +
                                     '<td><span class="badge bg-success-subtle text-success fs-6">' + b.available_quantity + ' units</span></td>' +
                                     '<td>৳' + b.purchase_price.toFixed(2) + '</td>' +
                                     '<td>' + b.manufacture_date_formatted + '</td>' +
                                     '<td>' + b.expiry_date_formatted + '</td>' +
                                     '<td class="text-end">' +
                                     '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-batch" ' +
                                     'data-id="' + b.id + '" ' +
                                     'data-batch="' + b.batch_no + '" ' +
                                     'data-qty="' + b.available_quantity + '" ' +
                                     'data-cost="' + b.purchase_price + '" ' +
                                     'data-mfg="' + (b.manufacture_date || '') + '" ' +
                                     'data-exp="' + (b.expiry_date || '') + '" title="Edit Batch"><i class="bi bi-pencil"></i></button>' +
                                     '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-batch" data-id="' + b.id + '" title="Delete Stock"><i class="bi bi-trash"></i></button>' +
                                     '</td>' +
                                     '</tr>';
                    });
                }
                $('#modalBatchTableBody').html(batchRows);

                $('#manageStockModal').modal('show');
            },
            error: function () {
                alert('Error fetching product stock details.');
            }
        });
    }

    // Open Quick Add Stock Modal directly from table row
    $(document).on('click', '.btn-add-stock-quick', function () {
        var skuId = $(this).data('sku');
        var name = $(this).data('name');
        var code = $(this).data('code');

        openAddStockModal(skuId, name, code);
    });

    // Open Add Stock Modal from inside Manage modal
    $('#btnOpenAddFromModal').on('click', function () {
        var skuId = $(this).data('sku');
        var name = $(this).data('name');
        var code = $(this).data('code');

        openAddStockModal(skuId, name, code);
    });

    function openAddStockModal(skuId, name, code) {
        $('#addStockForm')[0].reset();
        $('#addSkuId').val(skuId);
        $('#addProductName').text(name);
        $('#addProductSku').text('SKU: ' + code);
        generateBatchNo(code);
        $('#addStockModal').modal('show');
    }

    function generateBatchNo(skuCode) {
        var dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        var rand = Math.floor(1000 + Math.random() * 9000);
        $('#addBatchNo').val('BAT-' + dateStr + '-' + rand);
    }

    $('#btnGenerateBatch').on('click', function () {
        var skuCode = $('#addProductSku').text().replace('SKU: ', '').trim();
        generateBatchNo(skuCode);
    });

    // Submit Add Stock
    $('#addStockForm').on('submit', function (e) {
        e.preventDefault();
        var submitBtn = $('#btnSubmitAddStock');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');

        $.ajax({
            url: "{{ route('availableStock.storeStock') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function (res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save Stock');
                if (res.success) {
                    $('#addStockModal').modal('hide');
                    table.ajax.reload(null, false);
                    if ($('#manageStockModal').is(':visible') && currentSelectedSkuId) {
                        loadProductStockDetails(currentSelectedSkuId);
                    }
                    alert(res.message);
                } else {
                    alert(res.message || 'Could not add stock.');
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save Stock');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Validation or Server Error';
                alert('Error: ' + err);
            }
        });
    });

    // Open Edit Batch Modal
    $(document).on('click', '.btn-edit-batch', function () {
        var bId = $(this).data('id');
        var batchNo = $(this).data('batch');
        var qty = $(this).data('qty');
        var cost = $(this).data('cost');
        var mfg = $(this).data('mfg');
        var exp = $(this).data('exp');

        $('#editBatchId').val(bId);
        $('#editBatchNo').val(batchNo);
        $('#editQuantity').val(qty);
        $('#editPurchasePrice').val(cost);
        $('#editMfgDate').val(mfg);
        $('#editExpDate').val(exp);
        $('#editRemarks').val('');

        $('#editStockModal').modal('show');
    });

    // Submit Edit Stock
    $('#editStockForm').on('submit', function (e) {
        e.preventDefault();
        var bId = $('#editBatchId').val();
        var submitBtn = $('#btnSubmitEditStock');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');

        $.ajax({
            url: "{{ url('available-stock/update-stock') }}/" + bId,
            type: "POST",
            data: $(this).serialize(),
            success: function (res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Update Stock');
                if (res.success) {
                    $('#editStockModal').modal('hide');
                    table.ajax.reload(null, false);
                    if (currentSelectedSkuId) {
                        loadProductStockDetails(currentSelectedSkuId);
                    }
                    alert(res.message);
                } else {
                    alert(res.message || 'Could not update stock.');
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Update Stock');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Update failed.';
                alert('Error: ' + err);
            }
        });
    });

    // Open Delete Confirm Modal
    $(document).on('click', '.btn-delete-batch', function () {
        var bId = $(this).data('id');
        $('#deleteBatchId').val(bId);
        $('#deleteStockModal').modal('show');
    });

    // Submit Delete Stock
    $('#btnConfirmDeleteStock').on('click', function () {
        var bId = $('#deleteBatchId').val();
        var delBtn = $(this);
        delBtn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: "{{ url('available-stock/delete-stock') }}/" + bId,
            type: "POST",
            data: { _token: "{{ csrf_token() }}" },
            success: function (res) {
                delBtn.prop('disabled', false).text('Delete');
                $('#deleteStockModal').modal('hide');
                if (res.success) {
                    table.ajax.reload(null, false);
                    if (currentSelectedSkuId) {
                        loadProductStockDetails(currentSelectedSkuId);
                    }
                    alert(res.message);
                } else {
                    alert(res.message || 'Could not delete stock.');
                }
            },
            error: function (xhr) {
                delBtn.prop('disabled', false).text('Delete');
                $('#deleteStockModal').modal('hide');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'Delete failed.';
                alert('Error: ' + err);
            }
        });
    });
});
</script>
@endsection
