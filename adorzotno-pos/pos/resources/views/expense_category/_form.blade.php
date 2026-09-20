@csrf
<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Enter category name">
            @error('name')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Code</label>
            <input class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $category->code ?? '') }}" placeholder="Optional code">
            @error('code')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Parent Category</label>
            <select class="form-control @error('parent_id') is-invalid @enderror" name="parent_id">
                <option value="">Main Category</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ (string) old('parent_id', $category->parent_id ?? '') === (string) $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
            @error('parent_id')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Status</label>
            <select class="form-control @error('status') is-invalid @enderror" name="status">
                @php($status = old('status', isset($category) && !$category->is_active ? 'inactive' : 'active'))
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>
    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('expenseCategory.show') }}" class="btn btn-light-secondary me-1 mb-1">Cancel</a>
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
