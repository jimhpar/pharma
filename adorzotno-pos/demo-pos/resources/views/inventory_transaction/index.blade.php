@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Inventory Ledger</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventory Ledger</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title">Inventory Transactions</h5>
                <button type="button" class="btn btn-success fw-bold" onclick="downloadInventoryExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i>Excel Download
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branchId" class="form-select" onchange="reloadLedgerTable()">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Sales Channel</label>
                        <select id="salesChannel" class="form-select" onchange="reloadLedgerTable()">
                            <option value="">All Channels</option>
                            @foreach($salesChannels as $channel => $label)
                                <option value="{{ $channel }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="inventoryLedgerTable" class="table table-striped"></table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    $(document).ready(function () {
        $('#inventoryLedgerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('inventoryTransaction.list') }}",
                type: 'POST',
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                    d.branch_id = $('#branchId').val();
                    d.salesChannel = $('#salesChannel').val();
                },
            },
            columns: [
                { title: 'Date', data: 'occurred_at', name: 'occurred_at', className: 'text-center', orderable: true, searchable: true },
                { title: 'Branch', data: 'branch', name: 'branch', className: 'text-center', orderable: false, searchable: false },
                { title: 'Warehouse', data: 'warehouse', name: 'warehouse', className: 'text-center', orderable: false, searchable: false },
                { title: 'Product', data: 'product_name', name: 'product_name', className: 'text-center', orderable: false, searchable: false },
                { title: 'SKU', data: 'sku_code', name: 'sku_code', className: 'text-center', orderable: false, searchable: false },
                { title: 'Batch', data: 'batch_no', name: 'batch_no', className: 'text-center', orderable: false, searchable: false },
                { title: 'Sales Channel', data: 'sales_channel', name: 'sales_channel', className: 'text-center', orderable: false, searchable: false },
                { title: 'Movement', data: 'movement_type', name: 'movement_type', className: 'text-center', orderable: true, searchable: true },
                { title: 'Quantity', data: 'quantity_display', name: 'quantity', className: 'text-center', orderable: false, searchable: false },
                { title: 'Balance After', data: 'balance_after', name: 'balance_after', className: 'text-center', orderable: true, searchable: true },
                { title: 'Reference', data: 'reference_type', name: 'reference_type', className: 'text-center', orderable: true, searchable: true },
                { title: 'Remarks', data: 'remarks', name: 'remarks', className: 'text-center', orderable: false, searchable: false }
            ]
    });

    window.reloadLedgerTable = function () {
        $('#inventoryLedgerTable').DataTable().ajax.reload();
    };

    window.downloadInventoryExcel = function () {
        const params = new URLSearchParams({
            branch_id: $('#branchId').val() || '',
            salesChannel: $('#salesChannel').val() || ''
        });

        window.location.href = "{{ route('inventoryTransaction.excel') }}?" + params.toString();
    };
});
</script>
@endsection
