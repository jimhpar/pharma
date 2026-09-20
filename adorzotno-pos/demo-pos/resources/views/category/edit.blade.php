@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Category Edit</h3>                
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Category Edit</li>
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
                        <h4 class="card-title">Category Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('category.update', $category->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Category Name</label>
                                            <input type="text" id="last-name-column" class="form-control @error('name') is-invalid @enderror" placeholder="Enter Name" name="name" value="{{ old('name', $category->name) }}">
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
                                            <label for="last-name-column">Category Slug</label>
                                            <input type="text" id="last-name-column" class="form-control @error('slug') is-invalid @enderror" placeholder="Enter Slug" name="slug" value="{{ old('slug', $category->slug) }}">
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
                                                value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                                            <small class="text-muted d-block mt-1">
                                                Last serial: {{ $lastSerial ?? 0 }}
                                            </small>
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
                                        <label for="country-floating">Parent</label>
                                        <select class="form-control @error('parent_id') is-invalid @enderror" name="parent_id">
                                            <option value="">Select Parent Category</option>
                                            @foreach ($parent as $parents)
                                            <option value="{{$parents->id}}" {{ (string) old('parent_id', $category->parent_id) === (string) $parents->id ? 'selected' : '' }}>{{$parents->display_name ?? $parents->name}}</option>
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
                                            <label for="country-floating">Status</label><span class="text-danger"> *</span>
                                            <select class="form-control @error('status') is-invalid @enderror" name="status">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status', $category->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $category->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                                    {{ old('featured', $category->is_featured) ? 'checked' : '' }}>
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
                                            <label for="title">Popular</label>
                                            <div>
                                                <input type="hidden" name="popular" value="0">
                                                <input class="form-check-input @error('popular') is-invalid @enderror"
                                                    type="checkbox"
                                                    name="popular"
                                                    value="1"
                                                    {{ old('popular', $category->is_popular) ? 'checked' : '' }}>
                                            </div>

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
                                            <div>
                                                <input type="hidden" name="top_deal" value="0">
                                                <input class="form-check-input @error('top_deal') is-invalid @enderror"
                                                    type="checkbox"
                                                    name="top_deal"
                                                    value="1"
                                                    {{ old('top_deal', $category->is_top_deal) ? 'checked' : '' }}>
                                            </div>

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
                                            <label for="first-name-column">Icon</label>
                                            <input type="file" id="first-name-column" class="form-control @error('icon') is-invalid @enderror" placeholder="Icon" name="icon">
                                            @error('icon')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(isset($category->icon))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ !empty($category?->icon) ? url($category->icon) : '' }}" alt="">
                                        </div>
                                        @endif
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
                                        @if(isset($category->image))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ !empty($category?->image) ? url($category->image) : '' }}" alt="">
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
