@extends('layouts.main')
@section('main.content')
<div class="page-heading">
    <div class="page-title"><div class="row"><div class="col-12"><h3>Fund Transfer</h3></div></div></div>
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('accounting.transfer.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6"><label>Date</label><input type="date" name="transfer_date" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" class="form-control @error('transfer_date') is-invalid @enderror">@error('transfer_date')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror</div>
                        <div class="col-md-6"><label>Amount</label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror">@error('amount')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror</div>
                        <div class="col-md-6 mt-2"><label>From Account</label><select name="from_account_id" class="form-control @error('from_account_id') is-invalid @enderror"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }}</option>@endforeach</select>@error('from_account_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror</div>
                        <div class="col-md-6 mt-2"><label>To Account</label><select name="to_account_id" class="form-control @error('to_account_id') is-invalid @enderror"><option value="">Select</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }}</option>@endforeach</select>@error('to_account_id')<div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>@enderror</div>
                        <div class="col-md-6 mt-2"><label>Branch</label><select name="branch_id" class="form-control"><option value="">Global</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                        <div class="col-md-6 mt-2"><label>Note</label><input name="note" value="{{ old('note') }}" class="form-control"></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-primary">Post Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
