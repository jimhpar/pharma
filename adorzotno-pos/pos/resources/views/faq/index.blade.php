@extends('layouts.main')
@section('main.content')
<div class="page-heading">
<div class="list-root">

    <div class="list-head">
        <div>
            <h2><i class="bi bi-question-circle-fill" style="color:#4f46e5;font-size:1.3rem"></i> FAQs</h2>
            <p>Manage storefront frequently asked questions</p>
        </div>
        <a href="{{ route('faq.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add FAQ
        </a>
    </div>

    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search question or answer..." autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="filter-divider"></div>
            <button class="btn-reset" id="btnReset"><i class="bi bi-x-circle"></i> Reset</button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table id="faqTable" class="table table-striped mb-0"></table>
        </div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
$(document).ready(function () {
    var _dt;
    var tbl = $('#faqTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: "{{ route('faq.list') }}", type: "POST",
            data: function (d) { d._token = "{{ csrf_token() }}"; },
        },
        columns: [
            { title:'Question', data:'question', name:'question', className:"text-center" },
            { title:'Answer', data:'answer', name:'answer', className:"text-center" },
            { title:'Action', className:"text-center", data:function(d){ return '<a title="edit" class="btn btn-warning btn-xs me-2" data-panel-id="'+d.id+'" onclick="editFaq(this)"><i class="fa fa-edit"></i></a><a title="delete" class="btn btn-danger btn-xs" data-panel-id="'+d.id+'" onclick="deleteFaq(this)"><i class="fa fa-trash"></i></a>'; }, orderable:false, searchable:false }
        ]
    });

    $('#fS').on('input', function () { $('#fSClear').toggle(!!$(this).val()); clearTimeout(_dt); _dt = setTimeout(function(){ tbl.search($('#fS').val()).draw(); }, 320); });
    $('#fSClear').on('click', function () { $('#fS').val('').trigger('input'); });
    $('#btnReset').on('click', function () { $('#fS').val(''); $('#fSClear').hide(); tbl.search('').ajax.reload(); });
});
function editFaq(x) { window.location.href = '{{ route("faq.edit", ":id") }}'.replace(':id', $(x).data('panel-id')); }
function deleteFaq(x) {
    if (!confirm("Delete This FAQ?")) return false;
    $.ajax({ type:'POST', url:"{!! route('faq.delete') !!}", data:{_token:"{{ csrf_token() }}",id:$(x).data('panel-id')},
        success:function(){ toastr.success('FAQ Deleted Successfully!'); $('#faqTable').DataTable().ajax.reload(null,false); }
    });
}
</script>
@endsection
