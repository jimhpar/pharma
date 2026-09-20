@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-ticket-perforated-fill" style="color:#4f46e5;font-size:1.3rem"></i> Coupons</h2>
            <p>Manage storefront coupon codes and redemption limits</p>
        </div>
        <a href="{{ route('coupon.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Coupon
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search coupon code..." autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="filter-divider"></div>
            <select class="filter-select no-select2" id="fType">
                <option value="">All Types</option>
                <option value="fixed">Fixed</option>
                <option value="percent">Percent</option>
            </select>
            <div class="filter-divider"></div>
            <select class="filter-select no-select2" id="fSt">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <div class="filter-divider"></div>
            <button class="btn-reset" id="btnReset"><i class="bi bi-x-circle"></i> Reset</button>
        </div>
        <div class="active-bar d-none" id="activeBar">
            <span class="active-lbl">Active:</span>
            <div id="activeChips" style="display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table id="couponTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#couponTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url:"{{ route('coupon.list') }}", type:"POST",
            data:function(d){
                d._token="{{ csrf_token() }}";
                d.filter_status=$('#fSt').val();
                d.filter_type=$('#fType').val();
            }
        },
        columns: [
            { title:'Code', data:'code', name:'code', className:"text-center" },
            { title:'Type', data:'discount_type', name:'discount_type', className:"text-center" },
            { title:'Discount', data:'discount', name:'discount_value', className:"text-center", searchable:false },
            { title:'Minimum', data:'minimum', name:'min_order_amount', className:"text-center", searchable:false },
            { title:'Maximum', data:'maximum', name:'max_discount_amount', className:"text-center", searchable:false },
            { title:'Validity', data:'validity', name:'start_at', className:"text-center", searchable:false },
            { title:'Usage', data:'usage', name:'used_count', className:"text-center", searchable:false },
            { title:'Status', data:'status', name:'status', className:"text-center", searchable:false },
            { title:'Action', className:"text-center", data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editCoupon(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteCoupon(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
        ]
    });

    $('#fS').on('input', function () { $('#fSClear').toggle(!!$(this).val()); clearTimeout(_dt); _dt = setTimeout(function(){ tbl.search($('#fS').val()).draw(); renderChips(); },320); });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });
    $('#fSt,#fType').on('change', function () { $(this).toggleClass('has-value',!!$(this).val()); tbl.ajax.reload(); renderChips(); });
    $('#btnReset').on('click', function () { $('#fS').val(''); $('#fSClear').hide(); $('#fSt,#fType').val('').removeClass('has-value'); tbl.search('').ajax.reload(); renderChips(); });
    function renderChips() {
        var c=[];
        if ($('#fS').val().trim()) c.push('Search: "'+$('#fS').val().trim()+'"');
        if ($('#fType').val()) c.push('Type: '+$('#fType option:selected').text());
        if ($('#fSt').val()) c.push('Status: '+$('#fSt option:selected').text());
        $('#activeBar').toggleClass('d-none',!c.length);
        $('#activeChips').html(c.map(function(x){ return '<span class="a-chip">'+x+'</span>'; }).join(''));
    }
});
function editCoupon(x) { window.location.href = '{{ route("coupon.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteCoupon(x) {
    if (!confirm("Delete This Coupon?")) return false;
    $.ajax({ type:'POST', url:"{!! route('coupon.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('Coupon Deleted Successfully!'); $('#couponTable').DataTable().ajax.reload(null,false); },
        error:function(xhr){ toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Coupon delete failed.'); }
    });
}
</script>
@endsection
