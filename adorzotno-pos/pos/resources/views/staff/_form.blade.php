<div class="row">
    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>User</label>
            <select class="form-control @error('user_id') is-invalid @enderror" name="user_id">
                <option value="">Select User</option>
                @foreach ($users as $userOption)
                    <option value="{{ $userOption->id }}" {{ (string) old('user_id', $staff->user_id ?? '') === (string) $userOption->id ? 'selected' : '' }}>
                        {{ $userOption->name }} ({{ $userOption->email }})
                    </option>
                @endforeach
            </select>
            @error('user_id')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Employee Code</label>
            <input class="form-control @error('employee_code') is-invalid @enderror" name="employee_code" value="{{ old('employee_code', $staff->employee_code ?? '') }}">
            @error('employee_code')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Default Branch</label>
            <select class="form-control @error('default_branch_id') is-invalid @enderror" name="default_branch_id">
                <option value="">Select Branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) old('default_branch_id', $staff->default_branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('default_branch_id')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Commission Plan</label>
            <select class="form-control @error('commission_plan_id') is-invalid @enderror" name="commission_plan_id">
                <option value="">Select Plan</option>
                @foreach ($commissionPlans as $plan)
                    <option value="{{ $plan->id }}" {{ (string) old('commission_plan_id', $staff->commission_plan_id ?? '') === (string) $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
            @error('commission_plan_id')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Staff Type</label>
            <select class="form-control @error('is_salesperson') is-invalid @enderror" name="is_salesperson">
                <option value="yes" {{ old('is_salesperson', ($staff->is_salesperson ?? true) ? 'yes' : 'no') === 'yes' ? 'selected' : '' }}>Salesperson</option>
                <option value="no" {{ old('is_salesperson', ($staff->is_salesperson ?? true) ? 'yes' : 'no') === 'no' ? 'selected' : '' }}>Support Staff</option>
            </select>
            @error('is_salesperson')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        <div class="form-group">
            <label>Status</label>
            <select class="form-control @error('status') is-invalid @enderror" name="status">
                <option value="active" {{ old('status', ($staff->is_active ?? true) ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', ($staff->is_active ?? true) ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
            <div class="invalid-feedback d-block"><strong>{{ $message }}</strong></div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Branch Role Assignments</label>
            <button type="button" class="btn btn-sm btn-primary" id="addRoleAssignmentBtn">Add Assignment</button>
        </div>
        @error('role_assignments')
        <div class="invalid-feedback d-block mb-2"><strong>{{ $message }}</strong></div>
        @enderror
        <div id="roleAssignmentContainer">
            @foreach ($assignmentRows as $index => $assignment)
                @include('staff._assignment_row', ['index' => $index, 'assignment' => $assignment])
            @endforeach
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-1 mb-1">Submit</button>
    </div>
</div>
