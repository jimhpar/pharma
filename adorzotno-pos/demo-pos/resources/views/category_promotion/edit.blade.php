@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Category Promotion Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Category Promotion Edit</li>
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
                        <h4 class="card-title">Category Promotion Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('categoryPromotion.update', $categoryPromotion->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" id="title" class="form-control @error('title') is-invalid @enderror" placeholder="Enter Title" name="title" value="{{ old('title', $categoryPromotion->title) }}">
                                            @error('title')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="category_id">Category</label>
                                            <select class="form-control @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" {{ (string) old('category_id', $categoryPromotion->category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="sort_order">Serial</label>
                                            <input
                                                type="number"
                                                min="0"
                                                id="sort_order"
                                                class="form-control @error('sort_order') is-invalid @enderror"
                                                placeholder="Enter Serial"
                                                name="sort_order"
                                                value="{{ old('sort_order', $categoryPromotion->sort_order ?? 0) }}">
                                            @error('sort_order')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="status">Status</label><span class="text-danger"> *</span>
                                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                                <option value="">Select Status</option>
                                                <option value="Active" {{ old('status', $categoryPromotion->status) === 'Active' ? 'selected' : '' }}>Active</option>
                                                <option value="Inactive" {{ old('status', $categoryPromotion->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('status')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="icon">Icon</label>
                                            <input type="file" id="icon" class="form-control @error('icon') is-invalid @enderror" name="icon" accept="image/*">
                                            @error('icon')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(isset($categoryPromotion->icon))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ !empty($categoryPromotion?->icon) ? url($categoryPromotion->icon) : '' }}" alt="">
                                        </div>
                                        @endif
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
