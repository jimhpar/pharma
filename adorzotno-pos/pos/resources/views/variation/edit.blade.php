@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Variation Edit</h3>                
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Variation Edit</li>
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
                        <h4 class="card-title">Variation Edit Form</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" method="post" action="{{ route('variation.update', $variation->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Variation Type</label>
                                            <input type="text" id="last-name-column" class="form-control @error('type') is-invalid @enderror" placeholder="Enter Type" name="type" value="{{ old('type', $variation->type) }}">
                                            @error('type')
                                            <div class="invalid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                                <strong>{{ $message }}</strong>
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <label for="last-name-column">Variation Value</label>
                                            <input type="text" id="last-name-column" class="form-control @error('value') is-invalid @enderror" placeholder="Enter Value" name="value" value="{{ old('value', $variation->value) }}">
                                            @error('value')
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
