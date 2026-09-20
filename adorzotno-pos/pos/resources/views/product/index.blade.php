@extends('layouts.main')

@section('header.css')
<style>
/* ── Product List specific styles ─── */
.table-card {
    background:#fff; border:1.5px solid #e5e7eb;
    border-radius:14px; overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
}
.table-meta {
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
    padding:13px 20px; background:#fafafa;
    border-bottom:1.5px solid #f3f4f6;
}
.tbl-info { font-size:14px; color:#6b7280; }
.tbl-info strong { color:#111827; font-weight:700; }
.tbl-info .filtered-from { font-size:12px; color:#4f46e5; font-weight:700; }
.pp-wrap { display:flex; align-items:center; gap:8px; font-size:13px; color:#6b7280; }
.pp-sel {
    height:32px; border:1.5px solid #e5e7eb; border-radius:7px;
    background:#fff; font-size:13px; font-weight:600;
    color:#374151; padding:0 8px; outline:none; cursor:pointer;
}

/* Table */
#prodTbl { width:100%; border-collapse:collapse; }
#prodTbl thead th {
    background:#f9fafb; padding:12px 16px;
    font-size:11px; font-weight:800;
    text-transform:uppercase; letter-spacing:.07em;
    color:#6b7280; border-bottom:2px solid #e5e7eb;
    white-space:nowrap; text-align:left;
}
#prodTbl thead th.tc { text-align:center; }
#prodTbl tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
#prodTbl tbody tr:last-child { border-bottom:none; }
#prodTbl tbody tr:hover { background:#fafaff; }
#prodTbl tbody td { padding:13px 16px; vertical-align:middle; }
#prodTbl tbody td.tc { text-align:center; }

.p-img { width:64px; height:64px; border-radius:10px; object-fit:cover; border:2px solid #e5e7eb; box-shadow:0 2px 8px rgba(0,0,0,.09); display:block; }
.p-img-ph { width:64px; height:64px; border-radius:10px; background:linear-gradient(135deg,#f3f4f6,#e5e7eb); border:2px dashed #d1d5db; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:22px; }
.p-name { font-size:14.5px; font-weight:700; color:#111827; line-height:1.35; margin-bottom:4px; }
.p-sku  { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#9ca3af; font-weight:500; background:#f3f4f6; border-radius:5px; padding:2px 8px; }
.p-pharma { font-size:12px; font-weight:500; color:#64748b; margin-top:2px; margin-bottom:2px; }
.cat-wrap { display:flex; flex-wrap:wrap; gap:4px; max-width:300px; }
.c-chip { display:inline-block; background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:5px; font-size:11px; font-weight:700; padding:2px 8px; white-space:nowrap; }
.p-brand { font-size:13px; color:#374151; font-weight:500; }
.t-b { display:inline-flex; align-items:center; border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700; white-space:nowrap; }
.t-standard { background:#dbeafe; color:#1e40af; }
.t-variant  { background:#d1fae5; color:#065f46; }
.t-combo    { background:#fed7aa; color:#92400e; }
.t-service  { background:#ede9fe; color:#5b21b6; }
.s-b { display:inline-flex; align-items:center; gap:6px; border-radius:20px; padding:5px 12px; font-size:12px; font-weight:700; }
.s-b .dot { width:7px; height:7px; border-radius:50%; }
.s-on  { background:#d1fae5; color:#065f46; } .s-on  .dot { background:#059669; }
.s-off { background:#fee2e2; color:#991b1b; } .s-off .dot { background:#dc2626; }
.acts { display:flex; align-items:center; justify-content:center; gap:6px; }
.act { width:34px; height:34px; border-radius:8px; border:1.5px solid; display:inline-flex; align-items:center; justify-content:center; font-size:14px; text-decoration:none; cursor:pointer; transition:all .14s; }
.act-e { color:#92400e; border-color:#fbbf24; background:#fffbeb; }
.act-e:hover { background:#f59e0b; border-color:#f59e0b; color:#fff; }
.act-p { color:#3730a3; border-color:#a5b4fc; background:#eef2ff; }
.act-p:hover { background:#4f46e5; border-color:#4f46e5; color:#fff; }

/* Pagination */
.pager { display:flex; align-items:center; justify-content:center; gap:4px; padding:16px 20px; border-top:1.5px solid #f3f4f6; flex-wrap:wrap; }
.pn { min-width:38px; height:38px; border:1.5px solid #e5e7eb; border-radius:9px; background:#fff; color:#374151; font-size:13px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; padding:0 10px; cursor:pointer; transition:all .13s; }
.pn:hover:not([disabled]):not(.pn-on) { border-color:#4f46e5; color:#4f46e5; background:#f0f0ff; }
.pn.pn-on { background:#4f46e5; border-color:#4f46e5; color:#fff; font-weight:800; }
.pn[disabled] { opacity:.4; cursor:default; pointer-events:none; }
.pn-sep { color:#9ca3af; padding:0 4px; }

/* Loading */
.tbl-state { padding:70px 20px; text-align:center; color:#9ca3af; }
.tbl-state .si { font-size:44px; display:block; opacity:.25; margin-bottom:12px; }
.tbl-state .st { font-size:16px; font-weight:600; color:#6b7280; margin:0 0 5px; }
.tbl-state .ss { font-size:13px; color:#9ca3af; margin:0; }
.spin { width:28px; height:28px; border:3px solid #e5e7eb; border-top-color:#4f46e5; border-radius:50%; animation:sp .7s linear infinite; display:inline-block; }
@keyframes sp { to { transform:rotate(360deg); } }
</style>
@endsection

@section('main.content')
<div class="page-heading">
<div class="list-root">

    {{-- Header --}}
    <div class="list-head">
        <div>
            <h2><i class="bi bi-box-seam" style="color:#4f46e5;font-size:1.3rem"></i>Products</h2>
            <p>Manage your complete product catalogue</p>
        </div>
        <a href="{{ route('product.create') }}" class="btn-new">
            <i class="bi bi-plus-lg" style="font-size:15px"></i> Add Product
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <div class="filter-row">

            {{-- Search --}}
            <div class="filter-search" id="fSearchWrap">
                <i class="bi bi-search"></i>
                <input type="text" id="fS" placeholder="Search name, SKU or barcode…" autocomplete="off">
                <button class="fs-clear" id="fSClear"><i class="bi bi-x-circle-fill"></i></button>
            </div>

            <div class="filter-divider"></div>

            {{-- Category dropdown --}}
            <div class="fdd" id="ddCat">
                <button class="fdd-btn" id="ddCatBtn">
                    <i class="bi bi-tag" style="font-size:13px"></i>
                    Category
                    <span class="fdd-count d-none" id="catCount">0</span>
                    <i class="bi bi-chevron-down fdd-chevron"></i>
                </button>
                <div class="fdd-panel" id="ddCatPanel">
                    <div class="fdd-panel-head">
                        <span class="fdd-panel-title">Select Categories</span>
                        <button class="fdd-panel-clear" id="catClear">Clear</button>
                    </div>
                    <div class="fdd-panel-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="catSearch" placeholder="Search categories…">
                    </div>
                    <div class="fdd-options" id="catOptions">
                        @foreach($categories as $cat)
                        <div class="fdd-option" data-value="{{ $cat->id }}" data-text="{{ $cat->name }}" data-search="{{ strtolower($cat->name) }}" style="padding-left:{{ 10 + ($cat->level ?? 0) * 16 }}px">
                            <span class="fdd-checkbox"><i class="bi bi-check2"></i></span>
                            <span class="fdd-opt-text" title="{{ $cat->display_name ?? $cat->name }}">
                                {{ str_repeat('— ', $cat->level ?? 0) }}{{ $cat->name }}
                            </span>
                        </div>
                        @endforeach
                        <div class="fdd-no-opts d-none">No categories found</div>
                    </div>
                </div>
            </div>

            {{-- Brand dropdown --}}
            <div class="fdd" id="ddBrand">
                <button class="fdd-btn" id="ddBrandBtn">
                    <i class="bi bi-building" style="font-size:13px"></i>
                    Brand
                    <span class="fdd-count d-none" id="brandCount">0</span>
                    <i class="bi bi-chevron-down fdd-chevron"></i>
                </button>
                <div class="fdd-panel" id="ddBrandPanel">
                    <div class="fdd-panel-head">
                        <span class="fdd-panel-title">Select Brands</span>
                        <button class="fdd-panel-clear" id="brandClear">Clear</button>
                    </div>
                    <div class="fdd-panel-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="brandSearch" placeholder="Search brands…">
                    </div>
                    <div class="fdd-options" id="brandOptions">
                        @foreach($brands as $brand)
                        <div class="fdd-option" data-value="{{ $brand->id }}" data-text="{{ $brand->name }}" data-search="{{ strtolower($brand->name) }}">
                            <span class="fdd-checkbox"><i class="bi bi-check2"></i></span>
                            <span class="fdd-opt-text">{{ $brand->name }}</span>
                        </div>
                        @endforeach
                        <div class="fdd-no-opts d-none">No brands found</div>
                    </div>
                </div>
            </div>

            <div class="filter-divider"></div>

            {{-- Type --}}
            <select class="filter-select no-select2" id="fT">
                <option value="">All Types</option>
                <option value="standard">Standard</option>
                <option value="variant_parent">Variant</option>
                <option value="combo">Combo</option>
                <option value="service">Service</option>
            </select>

            {{-- Status --}}
            <select class="filter-select no-select2" id="fSt">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <div class="filter-divider"></div>

            <button class="btn-reset" id="btnReset">
                <i class="bi bi-x-circle"></i> Reset
            </button>
        </div>

        {{-- Active chips --}}
        <div class="active-bar d-none" id="activeBar">
            <span class="active-lbl">Active:</span>
            <div id="activeChips" style="display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="table-card">
        <div class="table-meta">
            <div class="tbl-info" id="tblInfo"><span class="spin" style="width:18px;height:18px;border-width:2px;vertical-align:middle;margin-right:6px"></span>Loading…</div>
            <div class="pp-wrap">
                Show
                <select class="pp-sel no-select2" id="plPP">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                per page
            </div>
        </div>
        <div class="table-responsive">
            <table id="prodTbl">
                <thead>
                    <tr>
                        <th style="width:80px" class="tc">Image</th>
                        <th style="min-width:180px">Product</th>
                        <th style="min-width:200px">Category</th>
                        <th style="min-width:140px">Brand</th>
                        <th class="tc" style="width:110px">Type</th>
                        <th class="tc" style="width:110px">Status</th>
                        <th class="tc" style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody id="tblBody">
                    <tr><td colspan="7"><div class="tbl-state"><span class="spin"></span></div></td></tr>
                </tbody>
            </table>
        </div>
        <div class="pager" id="tblPager"></div>
    </div>

</div>
</div>
@endsection

@section('footer.js')
<script>
(function () {
    var KEY  = 'prodF_v3';
    var EU   = '{{ route("product.edit",  ":id") }}';
    var PU   = '{{ route("batch.preview", ":id") }}';
    var LU   = '{{ route("product.list") }}';
    var TOK  = '{{ csrf_token() }}';
    var TYPE = { Standard:'t-standard', Variant:'t-variant', Combo:'t-combo', Service:'t-service' };

    var st   = { page:1, pp:25, total:0, filtered:0, busy:false };
    var sel  = { cats:[], brands:[] };
    var _dt;

    /* ── Dropdown logic ───────────────────────── */
    function makeDropdown(ddId, btnId, panelId, searchId, clearId, countId, selKey) {
        var $btn    = $('#' + btnId);
        var $panel  = $('#' + panelId);
        var $search = $('#' + searchId);
        var $clear  = $('#' + clearId);
        var $count  = $('#' + countId);
        var $opts   = $panel.find('.fdd-option');
        var $noOpts = $panel.find('.fdd-no-opts');

        function open() {
            $('.fdd-panel').not($panel).removeClass('open');
            $('.fdd-btn').not($btn).removeClass('is-open');
            $panel.addClass('open');
            $btn.addClass('is-open');
            $search.focus();
        }
        function close() { $panel.removeClass('open'); $btn.removeClass('is-open'); }
        function toggle() { $panel.hasClass('open') ? close() : open(); }

        $btn.on('click', function(e) { e.stopPropagation(); toggle(); });

        $search.on('input', function () {
            var q = $(this).val().toLowerCase().trim();
            var vis = 0;
            $opts.each(function () {
                var match = !q || $(this).data('search').includes(q);
                $(this).toggle(match);
                if (match) vis++;
            });
            $noOpts.toggleClass('d-none', vis > 0);
        });

        $opts.on('click', function () {
            var val  = String($(this).data('value'));
            var idx  = sel[selKey].indexOf(val);
            if (idx === -1) sel[selKey].push(val);
            else sel[selKey].splice(idx, 1);
            $(this).toggleClass('checked', idx === -1);
            updateDropdownState($btn, $count, selKey);
            save(); renderChips(); reload(true);
        });

        $clear.on('click', function () {
            sel[selKey] = [];
            $opts.removeClass('checked');
            updateDropdownState($btn, $count, selKey);
            save(); renderChips(); reload(true);
        });

        // Restore saved state
        function restoreOptions() {
            $opts.each(function () {
                var val = String($(this).data('value'));
                $(this).toggleClass('checked', sel[selKey].indexOf(val) !== -1);
            });
            updateDropdownState($btn, $count, selKey);
        }
        return { restoreOptions: restoreOptions, close: close };
    }

    function updateDropdownState($btn, $count, selKey) {
        var n = sel[selKey].length;
        $count.text(n).toggleClass('d-none', n === 0);
        $btn.toggleClass('has-value', n > 0);
    }

    var ddCat   = makeDropdown('ddCat',   'ddCatBtn',   'ddCatPanel',   'catSearch',   'catClear',   'catCount',   'cats');
    var ddBrand = makeDropdown('ddBrand', 'ddBrandBtn', 'ddBrandPanel', 'brandSearch', 'brandClear', 'brandCount', 'brands');

    // Close dropdowns on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.fdd').length) {
            ddCat.close(); ddBrand.close();
        }
    });

    /* ── Simple selects ──────────────────────── */
    function syncSelectStyle(sel) {
        $(sel).toggleClass('has-value', !!$(sel).val());
    }
    $('#fT, #fSt').on('change', function () {
        syncSelectStyle(this);
        save(); renderChips(); reload(true);
    });

    /* ── Search ──────────────────────────────── */
    $('#fS').on('input', function () {
        var v = $(this).val();
        $('#fSClear').toggle(!!v);
        $('#fSearchWrap').toggleClass('has-value', !!v);
        clearTimeout(_dt);
        _dt = setTimeout(function () { save(); renderChips(); reload(true); }, 320);
    });
    $('#fSClear').on('click', function () {
        $('#fS').val('').trigger('input');
    });

    /* ── Reset ───────────────────────────────── */
    $('#btnReset').on('click', function () {
        sel.cats = []; sel.brands = [];
        ddCat.restoreOptions(); ddBrand.restoreOptions();
        $('#fS').val(''); $('#fSClear').hide(); $('#fSearchWrap').removeClass('has-value');
        $('#fT, #fSt').val('').removeClass('has-value');
        $('#catSearch, #brandSearch').val('');
        $('#ddCatPanel .fdd-option, #ddBrandPanel .fdd-option').show();
        $('#ddCatPanel .fdd-no-opts, #ddBrandPanel .fdd-no-opts').addClass('d-none');
        save(); renderChips(); reload(true);
    });

    $('#plPP').on('change', function () { st.pp = +$(this).val() || 25; reload(true); });

    /* ── Persist ─────────────────────────────── */
    function save() {
        try {
            localStorage.setItem(KEY, JSON.stringify({
                s: $('#fS').val(), c: sel.cats, b: sel.brands,
                t: $('#fT').val(), st: $('#fSt').val(),
            }));
        } catch(e) {}
    }

    function load() {
        try {
            var f = JSON.parse(localStorage.getItem(KEY) || '{}');
            if (f.s) { $('#fS').val(f.s); $('#fSClear').show(); $('#fSearchWrap').addClass('has-value'); }
            sel.cats   = f.c  || [];
            sel.brands = f.b  || [];
            if (f.t)  { $('#fT').val(f.t).addClass('has-value'); }
            if (f.st) { $('#fSt').val(f.st).addClass('has-value'); }
            ddCat.restoreOptions();
            ddBrand.restoreOptions();
        } catch(e) {}
    }

    /* ── Active chips ────────────────────────── */
    function getOptionText(panelId, value) {
        return $('#' + panelId + ' .fdd-option[data-value="' + value + '"]').data('text') || value;
    }

    function renderChips() {
        var chips = [];
        if ($('#fS').val().trim()) chips.push({ label:'Search: "' + $('#fS').val().trim() + '"', key:'search', val:null });

        sel.cats.forEach(function (v) {
            chips.push({ label:'Category: ' + getOptionText('ddCatPanel', v), key:'cat', val:v });
        });
        sel.brands.forEach(function (v) {
            chips.push({ label:'Brand: ' + getOptionText('ddBrandPanel', v), key:'brand', val:v });
        });
        if ($('#fT').val()) chips.push({ label:'Type: ' + $('#fT option:selected').text(), key:'type', val:null });
        if ($('#fSt').val()) chips.push({ label:'Status: ' + $('#fSt option:selected').text(), key:'status', val:null });

        $('#activeBar').toggleClass('d-none', chips.length === 0);
        $('#activeChips').html(chips.map(function (ch) {
            return '<span class="a-chip">' + ch.label +
                '<button class="a-chip-x" data-key="' + ch.key + '" data-val="' + (ch.val||'') + '">×</button></span>';
        }).join(''));
    }

    $(document).on('click', '.a-chip-x', function () {
        var key = $(this).data('key'), val = String($(this).data('val'));
        if      (key === 'search') { $('#fS').val('').trigger('input'); return; }
        else if (key === 'cat')    { sel.cats   = sel.cats.filter(function(v){ return v !== val; }); ddCat.restoreOptions(); }
        else if (key === 'brand')  { sel.brands = sel.brands.filter(function(v){ return v !== val; }); ddBrand.restoreOptions(); }
        else if (key === 'type')   { $('#fT').val('').removeClass('has-value'); }
        else if (key === 'status') { $('#fSt').val('').removeClass('has-value'); }
        save(); renderChips(); reload(true);
    });

    /* ── AJAX load ───────────────────────────── */
    function fetch() {
        if (st.busy) return;
        st.busy = true;
        $('#tblBody').html('<tr><td colspan="7"><div class="tbl-state"><span class="spin"></span></div></td></tr>');
        $('#tblInfo').html('<span class="spin" style="width:18px;height:18px;border-width:2px;vertical-align:middle;margin-right:6px"></span>Loading…');
        $('#tblPager').html('');

        $.ajax({
            url: LU, type: 'POST',
            data: {
                _token: TOK, draw: 1,
                start:  (st.page - 1) * st.pp, length: st.pp,
                search_query: $('#fS').val().trim(),
                category_ids: sel.cats,
                brand_ids:    sel.brands,
                product_type: $('#fT').val(),
                status:       $('#fSt').val(),
            },
            success: function (r) {
                st.total    = r.recordsTotal    || 0;
                st.filtered = r.recordsFiltered || 0;
                renderRows(r.data || []);
                renderInfo();
                renderPager();
            },
            error: function () {
                $('#tblBody').html('<tr><td colspan="7"><div class="tbl-state"><i class="bi bi-wifi-off si"></i><p class="st">Connection error</p><p class="ss">Check your connection and try again.</p></div></td></tr>');
                $('#tblInfo').text('Failed to load');
            },
            complete: function () { st.busy = false; }
        });
    }

    function renderRows(rows) {
        var hasFilter = $('#fS').val().trim() || sel.cats.length || sel.brands.length || $('#fT').val() || $('#fSt').val();
        if (!rows.length) {
            var icon  = hasFilter ? 'bi-filter-circle' : 'bi-inbox-fill';
            var title = hasFilter ? 'No products match your filters' : 'No products yet';
            var sub   = hasFilter ? 'Try adjusting or clearing your filters.' : 'Click "Add Product" to get started.';
            $('#tblBody').html('<tr><td colspan="7"><div class="tbl-state"><i class="bi ' + icon + ' si"></i><p class="st">' + title + '</p><p class="ss">' + sub + '</p></div></td></tr>');
            return;
        }
        $('#tblBody').html(rows.map(function (r) {
            var img  = r.thumbnail_image
                ? '<div style="width:64px;margin:0 auto"><img src="' + r.thumbnail_image + '" class="p-img"></div>'
                : '<div style="width:64px;margin:0 auto"><div class="p-img-ph"><i class="bi bi-image-fill"></i></div></div>';
            var sku  = r.sku_summary ? '<span class="p-sku"><i class="bi bi-upc-scan"></i>' + r.sku_summary + '</span>' : '';
            var pharmaParts = [r.dosage_form, r.strength, r.coating_type].filter(Boolean);
            var pharmaTags  = pharmaParts.length ? '<div class="p-pharma">' + pharmaParts.join(' · ') + '</div>' : '';
            var cats = (r.category && r.category !== 'N/A')
                ? '<div class="cat-wrap">' + r.category.split(', ').map(function(c){ return '<span class="c-chip">'+c+'</span>'; }).join('') + '</div>'
                : '<span class="text-muted" style="font-size:13px">—</span>';
            var brand = (r.brand && r.brand !== 'N/A') ? '<span class="p-brand">' + r.brand + '</span>' : '<span class="text-muted">—</span>';
            var tc    = TYPE[r.type_label] || 't-standard';
            var type  = r.type_label ? '<span class="t-b ' + tc + '">' + r.type_label + '</span>' : '<span class="text-muted">—</span>';
            var stat  = r.raw_status === 'active'
                ? '<span class="s-b s-on"><span class="dot"></span>Active</span>'
                : '<span class="s-b s-off"><span class="dot"></span>Inactive</span>';
            return '<tr>' +
                '<td class="tc">' + img + '</td>' +
                '<td><div class="p-name">' + r.name + '</div>' + pharmaTags + sku + '</td>' +
                '<td>' + cats + '</td>' +
                '<td>' + brand + '</td>' +
                '<td class="tc">' + type + '</td>' +
                '<td class="tc">' + stat + '</td>' +
                '<td><div class="acts">' +
                    '<a class="act act-e" title="Edit" href="' + EU.replace(':id', r.id) + '"><i class="bi bi-pencil-square"></i></a>' +
                    '<a class="act act-p" title="Purchase" href="' + PU.replace(':id', r.id) + '"><i class="bi bi-bag-plus-fill"></i></a>' +
                '</div></td></tr>';
        }).join(''));
    }

    function renderInfo() {
        if (!st.filtered) { $('#tblInfo').html('<span style="color:#6b7280">No results found</span>'); return; }
        var s = Math.min((st.page-1)*st.pp+1, st.filtered);
        var e = Math.min(st.page*st.pp, st.filtered);
        var extra = st.filtered !== st.total ? ' <span class="filtered-from">(filtered from ' + st.total.toLocaleString() + ')</span>' : '';
        $('#tblInfo').html('Showing <strong>' + s.toLocaleString() + '–' + e.toLocaleString() + '</strong> of <strong>' + st.filtered.toLocaleString() + '</strong> products' + extra);
    }

    function renderPager() {
        var tp = Math.ceil(st.filtered / st.pp) || 1;
        if (tp <= 1) { $('#tblPager').html(''); return; }
        var c = st.page, rng = pageRange(c, tp), h = '';
        h += '<button class="pn" id="pgP" ' + (c<=1?'disabled':'') + '><i class="bi bi-chevron-left"></i></button>';
        rng.forEach(function(p) {
            if (p==='…') h += '<span class="pn-sep">…</span>';
            else h += '<button class="pn' + (p===c?' pn-on':'') + '" data-pg="' + p + '">' + p + '</button>';
        });
        h += '<button class="pn" id="pgN" ' + (c>=tp?'disabled':'') + '><i class="bi bi-chevron-right"></i></button>';
        $('#tblPager').html(h);
    }
    function pageRange(c,tp) {
        if(tp<=7) return Array.from({length:tp},function(_,i){return i+1;});
        var a=[1]; if(c>3) a.push('…');
        for(var i=Math.max(2,c-1);i<=Math.min(tp-1,c+1);i++) a.push(i);
        if(c<tp-2) a.push('…'); a.push(tp); return a;
    }
    $(document).on('click','.pn[data-pg]',function(){var p=+$(this).data('pg');if(p&&p!==st.page){st.page=p;fetch();}});
    $(document).on('click','#pgP',function(){if(st.page>1){st.page--;fetch();}});
    $(document).on('click','#pgN',function(){var tp=Math.ceil(st.filtered/st.pp);if(st.page<tp){st.page++;fetch();}});

    function reload(reset) { if (reset) st.page = 1; fetch(); }

    /* ── Boot ────────────────────────────────── */
    load();
    renderChips();
    fetch();
})();
</script>
@endsection
