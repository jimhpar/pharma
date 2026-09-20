@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Banner Edit</h3>                
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Banner Edit</li>
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
                        <h4 class="card-title">Banner Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h5 class="alert-heading mb-3">
                                        <i class="bx bx-info-circle"></i> <strong>Validation Errors!</strong>
                                    </h5>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            <form class="form" method="post" action="{{ route('banner.update', $banner->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Banner Title</label>
                                            <input type="text" id="last-name-column" class="form-control @error('title') is-invalid @enderror" placeholder="Enter Title" name="title" value="{{ old('title', $banner->title) }}">
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
                                            <label for="country-floating">Banner Type</label><span class="text-danger"> *</span>
                                            <select class="form-control no-select2 @error('banner_type') is-invalid @enderror" name="banner_type" id="bannerType">
                                                <option value="">Select Banner Type</option>
                                                <option value="slider" {{ old('banner_type', $banner->banner_type) === 'slider' ? 'selected' : '' }}>Slider</option>
                                                <option value="homepage_middle_1" {{ old('banner_type', $banner->banner_type) === 'homepage_middle_1' ? 'selected' : '' }}>Homepage Middle 1</option>
                                                <option value="homepage_middle_2" {{ old('banner_type', $banner->banner_type) === 'homepage_middle_2' ? 'selected' : '' }}>Homepage Middle 2</option>
                                            </select>                                            
                                            @error('banner_type')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12" id="positionWrapper">
                                        <div class="form-group">
                                            <label for="position">Position</label>
                                            <select class="form-control no-select2 @error('position') is-invalid @enderror" name="position" id="position">
                                                <option value="">Select Position</option>
                                                <option value="small_top" {{ old('position', $banner->position) === 'small_top' ? 'selected' : '' }}>Small Top</option>
                                                <option value="small_bottom" {{ old('position', $banner->position) === 'small_bottom' ? 'selected' : '' }}>Small Bottom</option>
                                                <option value="large" {{ old('position', $banner->position) === 'large' ? 'selected' : '' }}>Large</option>
                                            </select>
                                            @error('position')
                                            <div class="invalid-feedback d-block">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="first-name-column">Banner Image</label>
                                            <input type="file" id="first-name-column" class="form-control @error('image') is-invalid @enderror" placeholder="Banner Image" name="image" accept="image/*" onchange="previewImage(event)">
                                            @error('image')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(isset($banner->image_path))
                                        <div class="mb-3">
                                            <img id="imagePreview" height="100px" width="100px" src="{{ url($banner->image_path) }}" alt="" style="object-fit: cover; border-radius: 6px;">
                                        </div>
                                        @else
                                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid mt-2 d-none" style="max-height: 100px; max-width: 100px; border-radius: 6px;">
                                        @endif
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Banner URL</label>
                                            <input type="text" id="last-name-column" class="form-control @error('banner_url') is-invalid @enderror" placeholder="Enter Banner URL" name="banner_url" value="{{ old('banner_url', $banner->banner_url) }}">
                                            @error('banner_url')
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
                                                <option value="active" {{ old('status', strtolower($banner->status)) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', strtolower($banner->status)) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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

    function togglePositionField() {
        const bannerTypeSelect = document.getElementById('bannerType');
        const wrapper = document.getElementById('positionWrapper');
        const position = document.getElementById('position');

        if (!bannerTypeSelect || !wrapper || !position) {
            return;
        }

        const bannerType = bannerTypeSelect.value;
        const showPosition = bannerType === 'homepage_middle_1' || bannerType === 'homepage_middle_2';

        wrapper.classList.toggle('d-none', !showPosition);
        if (!showPosition) {
            position.value = '';
        }
    }

    function bindBannerPositionToggle() {
        const bannerTypeSelect = document.getElementById('bannerType');
        if (bannerTypeSelect) {
            bannerTypeSelect.addEventListener('change', togglePositionField);
        }
        togglePositionField();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindBannerPositionToggle);
    } else {
        bindBannerPositionToggle();
    }
</script>

@endsection
