@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Category Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Category Create</li>
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
                        <h4 class="card-title">Category Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('category.store') }}"
                                enctype="multipart/form-data">
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
                                            <small class="text-muted d-block mt-1">
                                                Last serial: {{ $lastSerial ?? 0 }}
                                            </small>

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
                                            <label for="country-floating">Parent</label>
                                            <select class="form-control @error('parent_id') is-invalid @enderror" name="parent_id">
                                                <option value="">Select Parent Category</option>
                                                @foreach($parent as $p)
                                                    <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->display_name ?? $p->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('parent_id')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Icon</label>
                                            <input type="file" class="form-control @error('icon') is-invalid @enderror" name="icon" accept="image/*" onchange="previewImage(event)">
                                            @error('icon')
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
                                            <label>Image</label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*">
                                            @error('image')
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
                                            <input type="hidden" name="featured" value="0">
                                            <input class="form-check-input" type="checkbox" name="featured" value="1" {{ old('featured', 0) ? 'checked' : '' }}>
                                                                                      
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
                                            <label for="title">Popular</label>
                                            <input type="hidden" name="popular" value="0">
                                            <input class="form-check-input" type="checkbox" name="popular" value="1" {{ old('popular', 0) ? 'checked' : '' }}>
                                                                                      
                                            @error('popular')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div> 

                                     <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Top Deal</label>
                                            <input type="hidden" name="top_deal" value="0">
                                            <input class="form-check-input" type="checkbox" name="top_deal" value="1" {{ old('top_deal', 0) ? 'checked' : '' }}>
                                                                                      
                                            @error('top_deal')
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
