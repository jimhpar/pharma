@extends('layouts.main')
@section('main.content')
@php
    $transferItems = collect(old('items', [[
        'batch_id' => '',
        'requested_quantity' => 1,
    ]]));
    $batchOptions = $batches->map(function ($batch) {
        return [
            'id' => $batch->id,
            'branch_id' => $batch->warehouse?->branch_id,
            'warehouse_id' => $batch->warehouse_id,
            'label' => ($batch->batch_no ?? 'N/A') . ' | ' . ($batch->sku?->display_name ?? 'N/A') . ' | WH: ' . ($batch->warehouse?->name ?? 'N/A') . ' | Stock: ' . (int) $batch->available_quantity,
        ];
    })->values();
    $warehouseOptions = $warehouses->map(function ($warehouse) {
        return [
            'id' => $warehouse->id,
            'branch_id' => $warehouse->branch_id,
            'name' => $warehouse->name,
            'branch_name' => $warehouse->branch?->name ?? 'N/A',
        ];
    })->values();
@endphp
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Stock Transfer Request</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('stockTransfer.show') }}">Stock Transfers</a></li>
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
                        <h4 class="card-title">Warehouse and Branch Transfer Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('stockTransfer.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Transfer No</label>
                                            <input class="form-control @error('transfer_no') is-invalid @enderror" name="transfer_no" value="{{ old('transfer_no', $transfer->transfer_no) }}">
                                            @error('transfer_no')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>From Branch</label>
                                            <select class="form-control @error('from_branch_id') is-invalid @enderror" name="from_branch_id" id="fromBranchId">
                                                <option value="">Select Branch</option>
                                                @foreach ($fromBranches as $branch)
                                                    <option value="{{ $branch->id }}" {{ (string) old('from_branch_id', $transfer->from_branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('from_branch_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>From Warehouse</label>
                                            <select class="form-control @error('from_warehouse_id') is-invalid @enderror" name="from_warehouse_id" id="fromWarehouseId"></select>
                                            @error('from_warehouse_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>To Branch</label>
                                            <select class="form-control @error('to_branch_id') is-invalid @enderror" name="to_branch_id" id="toBranchId">
                                                <option value="">Select Branch</option>
                                                @foreach ($toBranches as $branch)
                                                    <option value="{{ $branch->id }}" {{ (string) old('to_branch_id', $transfer->to_branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('to_branch_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>To Warehouse</label>
                                            <select class="form-control @error('to_warehouse_id') is-invalid @enderror" name="to_warehouse_id" id="toWarehouseId"></select>
                                            @error('to_warehouse_id')
                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h5 class="mb-0">Requested Items</h5>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addTransferRow">Add Item</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Source Batch</th>
                                                        <th>Requested Qty</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="transferItemsBody">
                                                    @foreach ($transferItems as $index => $item)
                                                    <tr>
                                                        <td>
                                                            <select class="form-control source-batch-select @error("items.$index.batch_id") is-invalid @enderror" name="items[{{ $index }}][batch_id]" data-selected-value="{{ $item['batch_id'] ?? '' }}"></select>
                                                            @error("items.$index.batch_id")
                                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="number" min="1" class="form-control @error("items.$index.requested_quantity") is-invalid @enderror" name="items[{{ $index }}][requested_quantity]" value="{{ $item['requested_quantity'] ?? 1 }}">
                                                            @error("items.$index.requested_quantity")
                                                            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-danger removeTransferRow">Remove</button>
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
    const transferBatchOptions = @json($batchOptions);
    const warehouseOptions = @json($warehouseOptions);
    const initialFromWarehouseId = @json(old('from_warehouse_id', $transfer->from_warehouse_id));
    const initialToWarehouseId = @json(old('to_warehouse_id', $transfer->to_warehouse_id));
    let transferItemIndex = {{ $transferItems->count() }};

    function buildWarehouseOptions(branchId, selectedId = '') {
        let options = '<option value="">Select Warehouse</option>';

        warehouseOptions
            .filter((warehouse) => String(warehouse.branch_id) === String(branchId))
            .forEach((warehouse) => {
                const selected = String(selectedId) === String(warehouse.id) ? 'selected' : '';
                options += `<option value="${warehouse.id}" ${selected}>${warehouse.name} (${warehouse.branch_name})</option>`;
            });

        return options;
    }

    function syncWarehouseDropdown(branchSelector, warehouseSelector, selectedId = '') {
        const branchId = $(branchSelector).val();
        const $warehouseSelect = $(warehouseSelector);

        if (!branchId) {
            $warehouseSelect.html('<option value="">Select Warehouse</option>').trigger('change');
            return;
        }

        $warehouseSelect.html(buildWarehouseOptions(branchId, selectedId)).trigger('change');

        if (selectedId && !$warehouseSelect.val()) {
            $warehouseSelect.val('');
        }
    }

    function buildTransferBatchOptions(selectedId = '') {
        const fromBranchId = $('#fromBranchId').val();
        const fromWarehouseId = $('#fromWarehouseId').val();
        let options = '<option value="">Select Source Batch</option>';

        transferBatchOptions
            .filter((batch) => String(batch.branch_id) === String(fromBranchId) && String(batch.warehouse_id) === String(fromWarehouseId))
            .forEach((batch) => {
                const selected = String(selectedId) === String(batch.id) ? 'selected' : '';
                options += `<option value="${batch.id}" ${selected}>${batch.label}</option>`;
            });

        return options;
    }

    function refreshSourceBatchSelectors() {
        $('.source-batch-select').each(function () {
            const selectedId = $(this).data('selected-value') || $(this).val() || '';
            $(this).html(buildTransferBatchOptions(selectedId)).trigger('change');

            if (selectedId && !$(this).val()) {
                $(this).data('selected-value', '');
                $(this).val('');
            }
        });
    }

    function appendTransferRow() {
        $('#transferItemsBody').append(`
            <tr>
                <td><select class="form-control source-batch-select" name="items[${transferItemIndex}][batch_id]" data-selected-value="">${buildTransferBatchOptions()}</select></td>
                <td><input type="number" min="1" class="form-control" name="items[${transferItemIndex}][requested_quantity]" value="1"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger removeTransferRow">Remove</button></td>
            </tr>
        `);
        if (typeof initSelect2 === 'function') initSelect2($('#transferItemsBody tr:last')[0]);
        transferItemIndex++;
    }

    $(document).on('click', '#addTransferRow', function () {
        appendTransferRow();
    });

    $(document).on('click', '.removeTransferRow', function () {
        if ($('#transferItemsBody tr').length <= 1) {
            return;
        }

        $(this).closest('tr').remove();
    });

    $(document).on('change', '#fromBranchId', function () {
        syncWarehouseDropdown('#fromBranchId', '#fromWarehouseId');
        refreshSourceBatchSelectors();
    });

    $(document).on('change', '#toBranchId', function () {
        syncWarehouseDropdown('#toBranchId', '#toWarehouseId');
    });

    $(document).on('change', '#fromWarehouseId', function () {
        refreshSourceBatchSelectors();
    });

    $(document).on('change', '.source-batch-select', function () {
        $(this).data('selected-value', $(this).val());
    });

    $(document).ready(function () {
        syncWarehouseDropdown('#fromBranchId', '#fromWarehouseId', initialFromWarehouseId);
        syncWarehouseDropdown('#toBranchId', '#toWarehouseId', initialToWarehouseId);
        refreshSourceBatchSelectors();
    });
</script>
@endsection
