<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Module</label>
            <input class="form-control @error('module') is-invalid @enderror" name="module" value="{{ old('module', $permission->module ?? '') }}">
            @error('module')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Action</label>
            <input class="form-control @error('action') is-invalid @enderror" name="action" value="{{ old('action', $permission->action ?? '') }}">
            @error('action')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-12 col-12">
        <div class="form-group">
            <label>Slug</label>
            <input class="form-control @error('slug') is-invalid @enderror" name="slug" value="{{ old('slug', $permission->slug ?? '') }}">
            @error('slug')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-12 col-12">
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $permission->description ?? '') }}</textarea>
            @error('description')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
