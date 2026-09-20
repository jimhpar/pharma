@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Setting Create</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Setting Create</li>
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
                        <h4 class="card-title">Setting Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('setting.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="company-name">Company Name</label>
                                            <input id="company-name" type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name') }}" placeholder="Enter company name">
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
                                            <input id="phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="Enter phone">
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
                                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Enter email">
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
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="office-address">Office Address</label>
                                            <textarea id="office-address" class="form-control @error('office_address') is-invalid @enderror" name="office_address" rows="4" placeholder="Enter office address">{{ old('office_address') }}</textarea>
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
                                            <label for="homepage-about-text">Homepage About Text</label>
                                            <textarea id="homepage-about-text" class="form-control @error('homepage_about_text') is-invalid @enderror" name="homepage_about_text" rows="6" placeholder="Enter homepage about text">{{ old('homepage_about_text') }}</textarea>
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
<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/super-build/ckeditor.js"></script>
<script>
    CKEDITOR.ClassicEditor
        .create(document.querySelector('#homepage-about-text'), {
            toolbar: {
                items: [
                    'heading',
                    '|',
                    'fontFamily',
                    'fontSize',
                    'fontColor',
                    'fontBackgroundColor',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    '|',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'outdent',
                    'indent',
                    '|',
                    'link',
                    'blockQuote',
                    'insertTable',
                    '|',
                    'undo',
                    'redo'
                ],
                shouldNotGroupWhenFull: true
            },
            fontFamily: {
                options: [
                    'default',
                    'Arial, Helvetica, sans-serif',
                    'Calibri, sans-serif',
                    'Georgia, serif',
                    'Tahoma, Geneva, sans-serif',
                    'Times New Roman, Times, serif',
                    'Trebuchet MS, Helvetica, sans-serif',
                    'Verdana, Geneva, sans-serif'
                ],
                supportAllValues: true
            },
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 24, 28, 32],
                supportAllValues: true
            },
            htmlSupport: {
                allow: [
                    {
                        name: /.*/,
                        styles: true,
                        attributes: true,
                        classes: true
                    }
                ]
            }
        })
        .then(editor => {
            editor.ui.view.editable.element.style.minHeight = '320px';
        })
        .catch(error => {
            console.error(error);
        });
</script>
@endsection
