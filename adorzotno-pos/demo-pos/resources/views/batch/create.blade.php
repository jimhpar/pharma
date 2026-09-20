@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Purchase</h3>                
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Purchase</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>       
    <section id="basic-horizontal-layouts">
        <div class="row match-height">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Variation Table</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="table-responsive">
                            <table id="variationTable" class="table table-striped"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Purchase Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form form-horizontal" id="purchaseForm" action="{{route('batch.store')}}" method="post">
                                @csrf
                                <div class="form-body">
                                    <div class="row">                                     
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="position-relative">
                                                    <label for="first-name-horizontal-icon">Sku ID</label>
                                                    <input type="text" class="form-control" placeholder="Sku ID" id="skuId" name="skuId" readonly>                                                 
                                                </div>
                                            </div>
                                        </div>
                                         <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="position-relative">
                                                    <label for="first-name-horizontal-icon">Supplier</label>
                                                    <select class="form-select" name="supplier" id="supplier">
                                                        <option value="">Select Supplier</option>
                                                         <option value="1">Male</option>
                                                        {{-- @foreach ($supplier as $suppliers)
                                                        <option value="{{$suppliers->id}}">{{$suppliers->name}}</option>
                                                        @endforeach --}}
                                                    </select>                                               
                                                </div>
                                            </div>
                                        </div>
                                         <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="position-relative">
                                                    <label for="first-name-horizontal-icon">Purchase Price</label>
                                                    <input type="number" class="form-control" placeholder="Purchase Price" id="purchasePrice" name="purchase_price">                                                 
                                                </div>
                                            </div>
                                        </div>
                                         <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="position-relative">
                                                    <label for="first-name-horizontal-icon">Quantity</label>
                                                    <input type="number" class="form-control" placeholder="Quantity" id="quantity" name="quantity">                                                 
                                                </div>
                                            </div>
                                        </div>                                                                         
                                                                           
                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>                                           
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Batch Table</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="table-responsive">
                            <table id="batchTable" class="table table-striped"></table>
                            </div>
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
 $(document).ready(function () {
    $('#variationTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            "url": "{{route('batch.variationList')}}",
            "type": "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}",
                d.productId = {{@$product->id}}
            },
        },
        columns: [
                {
                    title: 'Select For Purchase',
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return '<input type="radio" name="rowSelect" class="row-select" value="' + data + '">';
                    }
                },
                {
                    title: 'Product Code',
                    data: 'product_code',
                    name: 'product_code',
                    orderable: true,
                    searchable: true
                },
                {
                    title: 'Base Price',
                    data: 'base_price',
                    name: 'base_price',
                    orderable: true,
                    searchable: true
                },
                {
                    title: 'Discount Type',              
                    data: 'discount_type',
                    name: 'discount_type',
                    orderable: true,
                    searchable: true
                },
                {
                    title: 'Discount Amount',              
                    data: 'discount_amount',
                    name: 'discount_amount',
                    orderable: true,
                    searchable: true
                },                                                                    
                                
            ]
    });

    // Batch Table #

     $('#batchTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            "url": "{{route('batch.list')}}",
            "type": "POST",
            data: function (d) {
                d._token = "{{ csrf_token() }}",
                d.productId = {{@$product->id}}
            },
        },
        columns: [ 
                {
                    title: 'Batch ID',
                    data: 'id',
                    name: 'id',
                    orderable: true,
                    searchable: true
                },

                {
                    title: 'Sku ID',
                    data: 'sku_id',
                    name: 'sku_id',
                    orderable: true,
                    searchable: true
                },
              
                {
                    title: 'Purchase Price',              
                    data: 'purchase_price',
                    name: 'purchase_price',
                    orderable: true,
                    searchable: true
                },
                {
                    title: 'Quantity',              
                    data: 'quantity',
                    name: 'quantity',
                    orderable: true,
                    searchable: true
                },                                                  
                                
            ]
    });
});

$(document).on('change', '.row-select', function () {
    let selectedId = $(this).val();
    $('#purchaseForm').trigger('reset')
    
    $.ajax({
        url: '{{ route("batch.selectSkuId") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: selectedId
        },
        success: function (response, textStatus, jqXHR) {
        if (jqXHR.status === 404 || response.status === 404) {
            toastr.error(response.statusText)
        }

        if (jqXHR.status === 200) {
            $('#skuId').val(response.sku.id)          
        }
        },
        error: function () {
            toastr.error("Failed to send selected variation.");
        }
    });
});

$('#purchaseForm').on('submit', function (e) {
    e.preventDefault()
    const skuId = $('#skuId').val().trim();
    if (skuId === '') {
        toastr.error("Sku ID is required before submitting.");
        $('#skuId').addClass('is-invalid');
        return; 
    } else {
        $('#skuId').removeClass('is-invalid');
    }
    let formData = new FormData($('#purchaseForm')[0])        
    $("#purchaseForm").find(".variationTempAjaxErrors").remove()
    $.ajax({
        type: 'POST',
        url: "{!! route('batch.store') !!}",
        cache: false,
        processData: false,
        contentType: false,
        data: formData,
        success: function (response, textStatus, jqXHR) {
            if (jqXHR.status === 404 || response.status === 404) {
                toastr.error(response.statusText)
            }
            if (jqXHR.status === 200) {
                $("#purchaseForm").find(".is-invalid").removeClass('is-invalid')
                $('#purchaseForm').trigger('reset')
                $('#batchTable').DataTable().clear().draw();
                toastr.success(response.statusText)
            }
        },
        error: function (error) {
            if (error.status === 404) {
                toastr.error(response.statusText)
            }
            if (error.status === 422) {
                $.each(error.responseJSON.errors, function (key, value) {
                    let el = $('#purchaseForm').find('[name="' + key + '"]')
                    el.addClass('is-invalid')
                    let errorMSG = value[0]
                    el.after($('<span class="text-danger variationTempAjaxErrors"><b>' + errorMSG + '</b></span>'))
                });
            }
        }
    });
    })


</script>
@endsection
