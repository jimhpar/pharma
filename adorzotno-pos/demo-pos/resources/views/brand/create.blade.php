@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Brand Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Brand Create</li>
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
                        <h4 class="card-title">Brand Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('brand.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">                                
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Name</label>
                                            <input class="form-control" name="name" value="{{old('name')}}">                                           

                                            @error('name')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Slug</label>
                                            <input class="form-control" name="slug" value="{{old('slug')}}">                                           

                                            @error('slug')
                                            <div class="invalid-feedback d-block">
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
                                                class="form-control @error('sort_order') is-invalid @enderror"
                                                id="sort_order"
                                                name="sort_order"
                                                value="{{ old('sort_order') }}"
                                                placeholder="Enter serial">

                                            @error('sort_order')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Logo</label>
                                            <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*" onchange="previewImage(event)">
                                            @error('logo')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                            <img id="imagePreview" src="#" alt="Preview" class="img-fluid mt-2 d-none" style="max-height: 100px; max-width: 100px; border-radius: 6px;">
                                        </div>
                                    </div>                                  

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Background Image</label>
                                            <input type="file" class="form-control @error('background_image') is-invalid @enderror" name="background_image" accept="image/*">
                                            @error('background_image')
                                            <div class="invalid-feedback"><strong>{{ $message }}</strong></div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Rating</label>
                                            <input type="number" min="0" max="5" step="0.01" class="form-control @error('rating') is-invalid @enderror" name="rating" value="{{ old('rating') }}">
                                            @error('rating')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Products Count</label>
                                            <input type="number" min="0" class="form-control @error('products_count') is-invalid @enderror" name="products_count" value="{{ old('products_count', 0) }}">
                                            @error('products_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Reviews Count</label>
                                            <input type="number" min="0" class="form-control @error('reviews_count') is-invalid @enderror" name="reviews_count" value="{{ old('reviews_count', 0) }}">
                                            @error('reviews_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group">
                                            <label>Founded Year</label>
                                            <input type="number" min="1800" max="2100" class="form-control @error('founded_year') is-invalid @enderror" name="founded_year" value="{{ old('founded_year') }}">
                                            @error('founded_year')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Employees Count</label>
                                            <input type="number" min="0" class="form-control @error('employees_count') is-invalid @enderror" name="employees_count" value="{{ old('employees_count') }}">
                                            @error('employees_count')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="form-group">
                                            <label>Verified</label>
                                            <div>
                                                <input type="hidden" name="is_verified" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_verified" value="1" {{ old('is_verified', 0) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                                            @error('description')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Headquarter Address</label>
                                            <textarea class="form-control @error('headquarter_address') is-invalid @enderror" name="headquarter_address" rows="3">{{ old('headquarter_address') }}</textarea>
                                            @error('headquarter_address')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Tags</label>
                                            <textarea class="form-control @error('tags') is-invalid @enderror" name="tags" rows="3">{{ old('tags') }}</textarea>
                                            @error('tags')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Certifications</label>
                                            <textarea class="form-control @error('certifications') is-invalid @enderror" name="certifications" rows="3">{{ old('certifications') }}</textarea>
                                            @error('certifications')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Featured</label>
                                            <input type="hidden" name="featured" value="0">
                                            <input class="form-check-input" type="checkbox" name="featured" value="1" {{ old('featured', $product->featured ?? false) ? 'checked' : '' }}>
                                                                                      
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
                                            <label for="country-floating">Status</label>
                                            <select class="form-control @error('status') is-invalid @enderror"
                                                name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status')=='active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive" {{ old('status')=='inactive' ? 'selected' : ''
                                                    }}>Inactive</option>
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
    function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };

        reader.readAsDataURL(input.files[0]);
    }
}

$(document).ready(function() {
$('.summernote').summernote({
  height: 250,
});
});
</script>

@endsection
