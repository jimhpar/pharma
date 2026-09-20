<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">Branch</label>
        <select id="branchId" class="form-select" onchange="filterChange()">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Month</label>
        <input type="month" id="month" class="form-control" value="{{ $currentMonth ?? '' }}" onchange="filterChange()">
    </div>
    <div class="col-md-3">
        <label class="form-label">Start Date</label>
        <input type="date" id="startDate" class="form-control" onchange="filterChange()">
    </div>
    <div class="col-md-3">
        <label class="form-label">End Date</label>
        <input type="date" id="endDate" class="form-control" onchange="filterChange()">
    </div>
    <div class="col-md-3">
        <label class="form-label">Category</label>
        <select id="categoryId" class="form-select" onchange="filterChange()">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">
                    {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Payment Source</label>
        <input type="text" id="paymentSource" class="form-control" placeholder="Cash, Bank, bKash" onkeyup="filterChange()">
    </div>
</div>
