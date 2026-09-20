<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $role->name ?? '') }}">
            @error('name')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Slug</label>
            <input class="form-control @error('slug') is-invalid @enderror" name="slug" value="{{ old('slug', $role->slug ?? '') }}">
            @error('slug')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>System Role</label>
            <select class="form-control @error('is_system') is-invalid @enderror" name="is_system">
                <option value="no" {{ old('is_system', ($role->is_system ?? false) ? 'yes' : 'no') === 'no' ? 'selected' : '' }}>No</option>
                <option value="yes" {{ old('is_system', ($role->is_system ?? false) ? 'yes' : 'no') === 'yes' ? 'selected' : '' }}>Yes</option>
            </select>
            @error('is_system')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-12 col-12">
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $role->description ?? '') }}</textarea>
            @error('description')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label>Permissions</label>
            @error('permission_ids')
            <div class="invalid-feedback d-block mb-2"><strong>{{ $message }}</strong></div>
            @enderror
            <div class="row">
                @foreach ($permissions as $module => $groupedPermissions)
                    <div class="col-md-6 col-12">
                        <div class="card border">
                            <div class="card-header">
                                <strong>{{ ucwords(str_replace('_', ' ', $module)) }}</strong>
                            </div>
                            <div class="card-body">
                                @foreach ($groupedPermissions as $permission)
                                    <div class="form-check mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permission_ids[]"
                                            value="{{ $permission->id }}"
                                            id="permission_{{ $permission->id }}"
                                            {{ in_array($permission->id, old('permission_ids', $selectedPermissionIds ?? []), true) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ ucwords(str_replace('_', ' ', $permission->action)) }}
                                            <small class="text-muted d-block">{{ $permission->slug }}</small>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
