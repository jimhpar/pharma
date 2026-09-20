@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>VAT Rule Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">VAT Rule Edit</li>
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
                        <h4 class="card-title">VAT Rule Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('taxRule.update', $taxRule->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="name">VAT Rule Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Enter VAT rule name" name="name" value="{{ old('name', $taxRule->name) }}">
                                            @error('name')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="rate_percent">VAT Percent</label>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control @error('rate_percent') is-invalid @enderror" placeholder="Enter VAT rate" name="rate_percent" value="{{ old('rate_percent', $taxRule->rate_percent) }}">
                                            @error('rate_percent')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="is_inclusive">Inclusive VAT</label>
                                            <div>
                                                <input type="hidden" name="is_inclusive" value="0">
                                                <input class="form-check-input @error('is_inclusive') is-invalid @enderror" type="checkbox" name="is_inclusive" value="1" {{ old('is_inclusive', $taxRule->is_inclusive) ? 'checked' : '' }}>
                                                <span class="ms-2">Apply as inclusive VAT</span>
                                            </div>
                                            @error('is_inclusive')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control @error('status') is-invalid @enderror" name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status', $taxRule->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $taxRule->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('status')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 d-flex justify-content-end">
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
</script>

@endsection
