@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Banner Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Banner Create</li>
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
                        <h4 class="card-title">Banner Form</h4>
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
                            <form class="form" method="post" action="{{ route('banner.store') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">                                
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{old('title')}}">                                           

                                            @error('title')
                                            <div class="invalid-feedback d-block">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="country-floating">Banner Type</label>
                                            <select class="form-control @error('banner_type') is-invalid @enderror" name="banner_type">
                                                <option value="">Select Banner Type</option>
                                                <option value="slider" {{ old('banner_type')=='slider' ? 'selected' : '' }}>Slider</option>
                                                <option value="static" {{ old('banner_type')=='static' ? 'selected' : '' }}>Static</option>
                                                <option value="discount" {{ old('banner_type')=='discount' ? 'selected' : '' }}>Discount</option>
                                                <option value="service" {{ old('banner_type')=='service' ? 'selected' : '' }}>Service</option>
                                                {{-- <option value="static" {{ old('banner_type')=='static' ? 'selected' : '' }}>Static</option>
                                                <option value="home_hero_side_top" {{ old('banner_type')=='home_hero_side_top' ? 'selected' : '' }}>Home Hero Side Top</option>
                                                <option value="home_hero_side_bottom" {{ old('banner_type')=='home_hero_side_bottom' ? 'selected' : '' }}>Home Hero Side Bottom</option>
                                                <option value="home_mid_promo" {{ old('banner_type')=='home_mid_promo' ? 'selected' : '' }}>Home Mid Promo</option>
                                                <option value="service" {{ old('banner_type')=='service' ? 'selected' : ''}}>Service</option> --}}
                                            </select>
                                            @error('banner_type')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label>Image</label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*" onchange="previewImage(event)">
                                            @error('image')
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
                                            <label for="title">Banner URL</label>
                                            <input class="form-control @error('banner_url') is-invalid @enderror" name="banner_url" value="{{old('banner_url')}}">                                           

                                            @error('banner_url')
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
                                                <option value="Active" {{ old('status')=='Active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="Inactive" {{ old('status')=='Inactive' ? 'selected' : ''
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
            return;
        }

        preview.src = '#';
        preview.classList.add('d-none');
}
</script>

@endsection
