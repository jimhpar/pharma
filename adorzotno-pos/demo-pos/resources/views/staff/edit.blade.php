@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Staff Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Staff Edit</li>
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
                        <h4 class="card-title">Staff Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('staff.update', $staff->id) }}">
                                @csrf
                                @include('staff._form')
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
    let roleAssignmentIndex = {{ count($assignmentRows) }};
    const assignmentTemplate = @json($assignmentTemplate);

    $('#addRoleAssignmentBtn').on('click', function () {
        $('#roleAssignmentContainer').append(assignmentTemplate.replaceAll('__INDEX__', roleAssignmentIndex));
        if (typeof initSelect2 === 'function') initSelect2($('#roleAssignmentContainer .role-assignment-row:last')[0]);
        roleAssignmentIndex++;
    });

    $(document).on('click', '.remove-role-assignment', function () {
        if ($('#roleAssignmentContainer .role-assignment-row').length <= 1) {
            return;
        }

        $(this).closest('.role-assignment-row').remove();
    });
</script>
@endsection
