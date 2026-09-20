<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name ?? '') }}">
            @error('name')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email ?? '') }}">
            @error('email')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Phone</label>
            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
            @error('phone')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Status</label>
            <select class="form-control @error('status') is-invalid @enderror" name="status">
                <option value="">Select Status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $user->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>{{ !empty($requirePassword) ? 'Password' : 'Password (Leave blank to keep current)' }}</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
            @error('password')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Password Confirmation</label>
            <input type="password" class="form-control" name="password_confirmation">
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
