@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Setting Edit</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Setting Edit</li>
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
                        <h4 class="card-title">Setting Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('setting.update', $setting->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="company-name">Company Name</label>
                                            <input id="company-name" type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $setting->company_name) }}" placeholder="Enter company name">
                                            @error('company_name')
                                                <div class="invalid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="phone">Phone</label>
                                            <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $setting->phone) }}" placeholder="Enter phone">
                                            @error('phone')
                                                <div class="invalid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $setting->email) }}" placeholder="Enter email">
                                            @error('email')
                                                <div class="invalid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="header-logo">Header Logo</label>
                                            <input id="header-logo" type="file" class="form-control @error('header_logo') is-invalid @enderror" name="header_logo" accept="image/*">
                                            @error('header_logo')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(!empty($setting->header_logo))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ url($setting->header_logo) }}" alt="">
                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="footer-logo">Footer Logo</label>
                                            <input id="footer-logo" type="file" class="form-control @error('footer_logo') is-invalid @enderror" name="footer_logo" accept="image/*">
                                            @error('footer_logo')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(!empty($setting->footer_logo))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ url($setting->footer_logo) }}" alt="">
                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="footer-gateway-banner">Footer Gateway Banner</label>
                                            <input id="footer-gateway-banner" type="file" class="form-control @error('footer_gateway_banner') is-invalid @enderror" name="footer_gateway_banner" accept="image/*">
                                            @error('footer_gateway_banner')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                        @if(!empty($setting->footer_gateway_banner))
                                        <div class="mb-3">
                                            <img height="100px" width="100px" src="{{ url($setting->footer_gateway_banner) }}" alt="">
                                        </div>
                                        @endif
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="office-address">Office Address</label>
                                            <textarea id="office-address" class="form-control @error('office_address') is-invalid @enderror" name="office_address" rows="4" placeholder="Enter office address">{{ old('office_address', $setting->office_address) }}</textarea>
                                            @error('office_address')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="homepage-notice">Homepage Notice</label>
                                            <textarea id="homepage-notice" class="form-control @error('homepage_notice') is-invalid @enderror" name="homepage_notice" rows="4" placeholder="Enter homepage notice">{{ old('homepage_notice', $setting->homepage_notice) }}</textarea>
                                            @error('homepage_notice')
                                            <div class="invalid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="homepage-about-text">Homepage About Text</label>
                                            <textarea id="homepage-about-text" class="form-control summernote @error('homepage_about_text') is-invalid @enderror" name="homepage_about_text" rows="6" placeholder="Enter homepage about text">{{ old('homepage_about_text', $setting->homepage_about_text) }}</textarea>
                                            @error('homepage_about_text')
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
$(document).ready(function() {
$('.summernote').summernote({
  height: 250,
});
});
</script>
@endsection
