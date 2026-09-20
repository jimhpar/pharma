@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title"><div class="row"><div class="col-12"><h3>Account Edit</h3></div></div></div>
    <section id="multiple-column-form">
        <div class="row match-height"><div class="col-12"><div class="card">
            <div class="card-header"><h4 class="card-title">Account Edit Form</h4></div>
            <div class="card-body">
                <form method="post" action="{{ route('account.update', $account->id) }}">@include('account._form')</form>
            </div>
        </div></div></div>
    </section>
</div>
@endsection
