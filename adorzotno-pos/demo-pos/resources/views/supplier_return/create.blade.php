@extends('layouts.main')
@section('main.content')
@php
    $returnItems = collect(old('items', [[
        'batch_id' => '',
        'quantity' => 1,
        'reason' => '',
    ]]));
    $batchOptions = $batches->map(function ($batch) {
        return [
            'id' => $batch->id,
            'label' => ($batch->batch_no ?? 'N/A') . ' | ' . ($batch->sku?->display_name ?? 'N/A') . ' | WH: ' . ($batch->warehouse?->name ?? 'N/A') . ' | Supplier: ' . ($batch->supplier?->name ?? 'N/A') . ' | Stock: ' . (int) $batch->available_quantity,
        ];
    })->values();
@endphp
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Supplier Return Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('supplierReturn.show') }}">Supplier Returns</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section id="multiple-column-form">
        <div class="row match-height">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Supplier Return Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('supplierReturn.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Return No</label>
                                            <input class="form-control @error('return_no') is-invalid @enderror" name="return_no" value="{{ old('return_no', $supplierReturn->return_no) }}">
                                            @error('return_no')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Return Date</label>
                                            <input type="date" class="form-control @error('return_date') is-invalid @enderror" name="return_date" value="{{ old('return_date', $supplierReturn->return_date) }}">
                                            @error('return_date')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Supplier</label>
                                            <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id">
                                                <option value="">Select Supplier</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('supplier_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Branch</label>
                                            <select class="form-control @error('branch_id') is-invalid @enderror" name="branch_id">
                                                <option value="">Select Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('branch_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Warehouse</label>
                                            <select class="form-control @error('warehouse_id') is-invalid @enderror" name="warehouse_id">
                                                <option value="">Select Warehouse</option>
                                                @foreach ($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                                        {{ $warehouse->name }} ({{ $warehouse->branch?->name ?? 'N/A' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('warehouse_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Note</label>
                                            <input class="form-control @error('note') is-invalid @enderror" name="note" value="{{ old('note', $supplierReturn->note) }}">
                                            @error('note')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h5 class="mb-0">Return Items</h5>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addSupplierReturnRow">Add Item</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Batch</th>
                                                        <th>Qty</th>
                                                        <th>Reason</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="supplierReturnItemsBody">
                                                    @foreach ($returnItems as $index => $item)
                                                    <tr>
                                                        <td>
                                                            <select class="form-control @error("items.$index.batch_id") is-invalid @enderror" name="items[{{ $index }}][batch_id]">
                                                                <option value="">Select Batch</option>
                                                                @foreach ($batches as $batch)
                                                                    <option value="{{ $batch->id }}" {{ (string) ($item['batch_id'] ?? '') === (string) $batch->id ? 'selected' : '' }}>
                                                                        {{ ($batch->batch_no ?? 'N/A') . ' | ' . ($batch->sku?->display_name ?? 'N/A') . ' | WH: ' . ($batch->warehouse?->name ?? 'N/A') . ' | Supplier: ' . ($batch->supplier?->name ?? 'N/A') . ' | Stock: ' . (int) $batch->available_quantity }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error("items.$index.batch_id")
                                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="number" min="1" class="form-control @error("items.$index.quantity") is-invalid @enderror" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}">
                                                            @error("items.$index.quantity")
                                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input class="form-control @error("items.$index.reason") is-invalid @enderror" name="items[{{ $index }}][reason]" value="{{ $item['reason'] ?? '' }}">
                                                            @error("items.$index.reason")
                                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-danger removeSupplierReturnRow">Remove</button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @error('items')
                                        <div class="text-danger"><strong>{{ $message }}</strong></div>
                                        @enderror
                                    </div>

                                    <div class="col-12 d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer.js')
<script>
    const supplierReturnBatchOptions = @json($batchOptions);
    let supplierReturnItemIndex = {{ $returnItems->count() }};

    function buildSupplierReturnBatchOptions(selectedId = '') {
        let options = '<option value="">Select Batch</option>';
        supplierReturnBatchOptions.forEach((batch) => {
            const selected = String(selectedId) === String(batch.id) ? 'selected' : '';
            options += `<option value="${batch.id}" ${selected}>${batch.label}</option>`;
        });
        return options;
    }

    function appendSupplierReturnRow() {
        $('#supplierReturnItemsBody').append(`
            <tr>
                <td><select class="form-control" name="items[${supplierReturnItemIndex}][batch_id]">${buildSupplierReturnBatchOptions()}</select></td>
                <td><input type="number" min="1" class="form-control" name="items[${supplierReturnItemIndex}][quantity]" value="1"></td>
                <td><input class="form-control" name="items[${supplierReturnItemIndex}][reason]" value=""></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger removeSupplierReturnRow">Remove</button></td>
            </tr>
        `);
        if (typeof initSelect2 === 'function') initSelect2($('#supplierReturnItemsBody tr:last')[0]);
        supplierReturnItemIndex++;
    }

    $(document).on('click', '#addSupplierReturnRow', function () {
        appendSupplierReturnRow();
    });

    $(document).on('click', '.removeSupplierReturnRow', function () {
        if ($('#supplierReturnItemsBody tr').length <= 1) {
            return;
        }
        $(this).closest('tr').remove();
    });
</script>
@endsection
