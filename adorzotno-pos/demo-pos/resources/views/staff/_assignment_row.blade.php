<div class="role-assignment-row border rounded p-3 mb-3">
    <div class="row">
        <div class="col-md-5 col-12">
            <div class="form-group">
                <label>Branch</label>
                <select class="form-control" name="role_assignments[{{ $index }}][branch_id]">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) ($assignment['branch_id'] ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-5 col-12">
            <div class="form-group">
                <label>Role</label>
                <select class="form-control" name="role_assignments[{{ $index }}][role_id]">
                    <option value="">Select Role</option>
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption->id }}" {{ (string) ($assignment['role_id'] ?? '') === (string) $roleOption->id ? 'selected' : '' }}>{{ $roleOption->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2 col-12 d-flex align-items-end">
            <button type="button" class="btn btn-danger w-100 remove-role-assignment">Remove</button>
        </div>
    </div>
</div>
