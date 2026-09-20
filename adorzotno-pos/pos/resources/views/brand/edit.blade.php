@extends('layouts.main')
@php
    $tagsText = old('tags', $brand->brandTags->pluck('tag')->implode("\n"));
    $certificationsText = old('certifications', $brand->brandCertifications->pluck('certification')->implode("\n"));
@endphp
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Brand Edit</h3>                
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Brand Edit</li>
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
                        <h4 class="card-title">Brand Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('brand.update', $brand->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Brand Name</label>
                                            <input type="text" id="last-name-column" class="form-control @error('name') is-invalid @enderror" placeholder="Enter Name" name="name" value="{{ old('name', $brand->name) }}">
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
                                            <label for="last-name-column">Brand Slug</label>
                                            <input type="text" id="last-name-column" class="form-control @error('slug') is-invalid @enderror" placeholder="Enter Slug" name="slug" value="{{ old('slug', $brand->slug) }}">
                                            @error('slug')
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
                                                value="{{ old('sort_order', $brand->sort_order ?? 0) }}">
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
                                            <label for="country-floating">Status</label><span class="text-danger"> *</span>
                                            <select class="form-control @error('status') is-invalid @enderror" name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status', $brand->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $brand->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                            <label for="title">Featured</label>
                                            <div>
                                                <input type="hidden" name="featured" value="0">
                                                <input class="form-check-input @error('featured') is-invalid @enderror"
                                                    type="checkbox"
                                                    name="featured"
                                                    value="1"
                                                    {{ old('featured', $brand->is_featured) ? 'checked' : '' }}>
                                            </div>

                                            @error('featured')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="first-name-column">Logo</label>
                                            <input type="file" id="first-name-column" class="form-control @error('logo') is-invalid @enderror" placeholder="Logo" name="logo">
                                            @error('logo')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(isset($brand->logo))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ !empty($brand?->logo) ? url($brand->logo) : '' }}" alt="">
                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Background Image</label>
                                            <input type="file" class="form-control @error('background_image') is-invalid @enderror" name="background_image" accept="image/*">
                                            @error('background_image')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                        @if(isset($brand->background_image))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ !empty($brand?->background_image) ? url($brand->background_image) : '' }}" alt="">
                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Rating</label>
                                            <input type="number" min="0" max="5" step="0.01" class="form-control @error('rating') is-invalid @enderror" name="rating" value="{{ old('rating', $brand->rating) }}">
                                            @error('rating')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Products Count</label>
                                            <input type="number" min="0" class="form-control @error('products_count') is-invalid @enderror" name="products_count" value="{{ old('products_count', $brand->products_count) }}">
                                            @error('products_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Reviews Count</label>
                                            <input type="number" min="0" class="form-control @error('reviews_count') is-invalid @enderror" name="reviews_count" value="{{ old('reviews_count', $brand->reviews_count) }}">
                                            @error('reviews_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Founded Year</label>
                                            <input type="number" min="1800" max="2100" class="form-control @error('founded_year') is-invalid @enderror" name="founded_year" value="{{ old('founded_year', $brand->founded_year) }}">
                                            @error('founded_year')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Employees Count</label>
                                            <input type="number" min="0" class="form-control @error('employees_count') is-invalid @enderror" name="employees_count" value="{{ old('employees_count', $brand->employees_count) }}">
                                            @error('employees_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Verified</label>
                                            <div>
                                                <input type="hidden" name="is_verified" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_verified" value="1" {{ old('is_verified', $brand->is_verified) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $brand->description) }}</textarea>
                                            @error('description')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Headquarter Address</label>
                                            <textarea class="form-control @error('headquarter_address') is-invalid @enderror" name="headquarter_address" rows="3">{{ old('headquarter_address', $brand->headquarter_address) }}</textarea>
                                            @error('headquarter_address')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Tags</label>
                                            <textarea class="form-control @error('tags') is-invalid @enderror" name="tags" rows="3">{{ $tagsText }}</textarea>
                                            @error('tags')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Certifications</label>
                                            <textarea class="form-control @error('certifications') is-invalid @enderror" name="certifications" rows="3">{{ $certificationsText }}</textarea>
                                            @error('certifications')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
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
