@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Stock Transfer Details</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('stockTransfer.show') }}">Stock Transfers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $transfer->transfer_no }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @error('transfer')
    <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $transfer->transfer_no }}</h5>
                <span class="badge bg-secondary">{{ $transfer->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <div class="form-control">{{ $transfer->fromBranch?->name ?? 'N/A' }} / {{ $transfer->fromWarehouse?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <div class="form-control">{{ $transfer->toBranch?->name ?? 'N/A' }} / {{ $transfer->toWarehouse?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Requested By</label>
                        <div class="form-control">{{ $transfer->requester?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Requested At</label>
                        <div class="form-control">{{ \App\Support\DateFormatter::dateTime($transfer->requested_at, 'N/A') }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Requested</th>
                                <th>Approved</th>
                                <th>Dispatched</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                            <tr>
                                <td>{{ $item->sku?->display_name ?? $item->sku?->product?->name ?? 'N/A' }}</td>
                                <td>{{ $item->batch?->batch_no ?? 'N/A' }}</td>
                                <td>{{ (int) $item->requested_quantity }}</td>
                                <td>{{ (int) $item->approved_quantity }}</td>
                                <td>{{ (int) $item->dispatched_quantity }}</td>
                                <td>{{ (int) $item->received_quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($transfer->discrepancy_note)
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Discrepancy Note:</strong> {{ $transfer->discrepancy_note }}
                    </div>
                @endif
            </div>
        </div>

        @if($canApprove && $transfer->status === \App\Models\InterBranchTransfer::STATUS_REQUESTED)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Approve Transfer</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('stockTransfer.approve', $transfer->id) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Requested Qty</th>
                                    <th>Approved Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfer->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                        {{ $item->sku?->display_name ?? 'N/A' }}
                                    </td>
                                    <td>{{ (int) $item->requested_quantity }}</td>
                                    <td>
                                        <input type="number" min="0" max="{{ (int) $item->requested_quantity }}" class="form-control @error("items.$index.approved_quantity") is-invalid @enderror" name="items[{{ $index }}][approved_quantity]" value="{{ old("items.$index.approved_quantity", $item->requested_quantity) }}">
                                        @error("items.$index.approved_quantity")
                                        <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                        @enderror
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('items')
                    <div class="text-danger mb-2"><strong>{{ $message }}</strong></div>
                    @enderror
                    <button type="submit" class="btn btn-primary">Approve</button>
                </form>
            </div>
        </div>
        @endif

        @if($canDispatch && $transfer->status === \App\Models\InterBranchTransfer::STATUS_APPROVED)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Dispatch Transfer</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('stockTransfer.dispatch', $transfer->id) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Approved Qty</th>
                                    <th>Dispatch Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfer->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                        {{ $item->sku?->display_name ?? 'N/A' }}
                                    </td>
                                    <td>{{ (int) $item->approved_quantity }}</td>
                                    <td>
                                        <input type="number" min="0" max="{{ (int) $item->approved_quantity }}" class="form-control @error("items.$index.dispatched_quantity") is-invalid @enderror" name="items[{{ $index }}][dispatched_quantity]" value="{{ old("items.$index.dispatched_quantity", $item->approved_quantity) }}">
                                        @error("items.$index.dispatched_quantity")
                                        <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                        @enderror
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('items')
                    <div class="text-danger mb-2"><strong>{{ $message }}</strong></div>
                    @enderror
                    <button type="submit" class="btn btn-warning">Dispatch</button>
                </form>
            </div>
        </div>
        @endif

        @if($canReceive && $transfer->status === \App\Models\InterBranchTransfer::STATUS_DISPATCHED)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Receive Transfer</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('stockTransfer.receive', $transfer->id) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Dispatched Qty</th>
                                    <th>Received Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfer->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                        {{ $item->sku?->display_name ?? 'N/A' }}
                                    </td>
                                    <td>{{ (int) $item->dispatched_quantity }}</td>
                                    <td>
                                        <input type="number" min="0" max="{{ (int) $item->dispatched_quantity }}" class="form-control @error("items.$index.received_quantity") is-invalid @enderror" name="items[{{ $index }}][received_quantity]" value="{{ old("items.$index.received_quantity", $item->dispatched_quantity) }}">
                                        @error("items.$index.received_quantity")
                                        <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                                        @enderror
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group mt-3">
                        <label>Discrepancy Note</label>
                        <textarea class="form-control @error('discrepancy_note') is-invalid @enderror" name="discrepancy_note" rows="3">{{ old('discrepancy_note') }}</textarea>
                        @error('discrepancy_note')
                        <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
                        @enderror
                    </div>
                    @error('items')
                    <div class="text-danger mb-2"><strong>{{ $message }}</strong></div>
                    @enderror
                    <button type="submit" class="btn btn-success mt-3">Receive</button>
                </form>
            </div>
        </div>
        @endif

        @if($canCancel && in_array($transfer->status, [\App\Models\InterBranchTransfer::STATUS_REQUESTED, \App\Models\InterBranchTransfer::STATUS_APPROVED], true))
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('stockTransfer.cancel', $transfer->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">Cancel Transfer</button>
                </form>
            </div>
        </div>
        @endif
    </section>
</div>
@endsection
