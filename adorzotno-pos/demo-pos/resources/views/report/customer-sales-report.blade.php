@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Customer Sales Report</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Customer Sales Report</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4>Customer-wise Sales List</h4>
                <button type="button" class="btn btn-success fw-bold" onclick="downloadCustomerSalesExcel()">
                    <i class="bi bi-file-earmark-excel pe-1 fs-5"></i>Excel Download
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    @if(!empty($branches) && $branches->isNotEmpty())
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branchId" class="form-select" name="branch_id" onchange="filterChange()">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Customer</label>
                        <select id="customerId" class="form-select" name="customer_id" onchange="filterChange()">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ ($customer->customer_code ?: 'N/A') . ' | ' . $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sales Channel</label>
                        <select id="salesChannel" class="form-select" name="salesChannel" onchange="filterChange()">
                            <option value="">All Channels</option>
                            @foreach($salesChannels as $channel => $label)
                                <option value="{{ $channel }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="startDate" id="startDate" class="form-control" onchange="filterChange()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="endDate" id="endDate" class="form-control" onchange="filterChange()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="customerSalesTable" class="table table-striped">
                        <tfoot>
                            <tr>
                                <th colspan="5"></th>
                                <th id="customer-count">0</th>
                                <th id="sales-total">0.00</th>
                                <th id="paid-total">0.00</th>
                                <th id="due-total">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer.js')
<script>
    let customerSalesTable;

    $(document).ready(function () {
        customerSalesTable = $('#customerSalesTable').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            ajax: {
                url: "{{ route('customerSales.list') }}",
                type: "POST",
                data: function (d) {
                    d._token = "{{ csrf_token() }}";
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.branch_id = $('#branchId').val();
                    d.customer_id = $('#customerId').val();
                    d.salesChannel = $('#salesChannel').val();
                },
                dataSrc: function (json) {
                    $("#customer-count").text(json.customerCount ?? 0);
                    $("#sales-total").text((json.salesTotalSum ?? 0).toFixed(2));
                    $("#paid-total").text((json.paidTotalSum ?? 0).toFixed(2));
                    $("#due-total").text((json.dueTotalSum ?? 0).toFixed(2));

                    return json.data;
                }
            },
            columns: [
                { title: 'Code', data: 'customer_code', name: 'customer_code', className: "text-center", orderable: false, searchable: false },
                { title: 'Customer', data: 'customer_name', name: 'customer_name', className: "text-center", orderable: false, searchable: true },
                { title: 'Group', data: 'group_name', name: 'group_name', className: "text-center", orderable: false, searchable: false },
                { title: 'Channel', data: 'sales_channels', name: 'sales_channels', className: "text-center", orderable: false, searchable: false },
                { title: 'Last Order', data: 'last_order_date', name: 'last_order_date', className: "text-center", orderable: false, searchable: false },
                { title: 'Orders', data: 'order_count', name: 'order_count', className: "text-center", orderable: false, searchable: false },
                { title: 'Sales', data: 'sales_total', name: 'sales_total', className: "text-center", orderable: false, searchable: false },
                { title: 'Collected', data: 'paid_total', name: 'paid_total', className: "text-center", orderable: false, searchable: false },
                { title: 'Due', data: 'due_total', name: 'due_total', className: "text-center", orderable: false, searchable: false },
                { title: 'Returns', data: 'return_total', name: 'return_total', className: "text-center", orderable: false, searchable: false },
                { title: 'Action', data: 'action', name: 'action', className: "text-center", orderable: false, searchable: false },
            ]
        });
    });

    function filterChange() {
        customerSalesTable.ajax.reload();
    }

    function downloadCustomerSalesExcel() {
        const params = new URLSearchParams({
            startDate: $('#startDate').val() || '',
            endDate: $('#endDate').val() || '',
            branch_id: $('#branchId').val() || '',
            customer_id: $('#customerId').val() || '',
            salesChannel: $('#salesChannel').val() || ''
        });

        window.location.href = "{{ route('customerSales.excel') }}?" + params.toString();
    }
</script>
@endsection
