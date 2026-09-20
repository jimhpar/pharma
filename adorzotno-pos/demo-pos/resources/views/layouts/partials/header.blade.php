<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adorzotno Dashboard</title>
    <link rel="shortcut icon" href="" type="image/x-icon">
    <link rel="shortcut icon" href="{{ url('public/admin/dist/assets/compiled/png/favicon.ico')}}" type="image/png">
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/compiled/css/app.css')}}">
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/compiled/css/app-dark.css')}}">
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/compiled/css/iconly.css')}}">
    <!-- Datatable CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/extensions/summernote/summernote-lite.css')}}">
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/compiled/css/form-editor-summernote.css')}}">
   
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/extensions/@fortawesome/fontawesome-free/css/all.min.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/extensions/choices.js/public/assets/styles/choices.css')}}">
    <link rel="stylesheet" href="{{ url('public/admin/dist/assets/compiled/css/admin-shell.css') }}">

    @yield('header.css')

    <style>
    /* ══════════════════════════════════════════════════
       Global List / Index Page — Filter Bar Components
       ══════════════════════════════════════════════════ */
    .list-root { display:flex; flex-direction:column; gap:18px; }

    /* Page header */
    .list-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .list-head h2 { font-size:1.45rem; font-weight:800; color:#111827; margin:0 0 3px; display:flex; align-items:center; gap:10px; }
    .list-head p  { font-size:13px; color:#6b7280; margin:0; }
    .btn-new {
        display:inline-flex; align-items:center; gap:8px;
        background:#4f46e5; color:#fff; font-size:14px; font-weight:700;
        padding:10px 22px; border-radius:10px; border:none;
        text-decoration:none; cursor:pointer; white-space:nowrap;
        box-shadow:0 2px 8px rgba(79,70,229,.3); transition:all .15s;
    }
    .btn-new:hover { background:#4338ca; color:#fff; transform:translateY(-1px); }

    /* Filter Bar container */
    .filter-bar {
        background:#fff; border:1.5px solid #e5e7eb;
        border-radius:14px; padding:14px 16px;
        box-shadow:0 1px 4px rgba(0,0,0,.05);
    }
    .filter-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

    /* Search input */
    .filter-search {
        display:flex; align-items:center; gap:8px;
        border:1.5px solid #e5e7eb; border-radius:9px;
        padding:0 12px; height:40px; background:#f9fafb;
        flex:1; min-width:200px; max-width:280px; transition:all .15s;
    }
    .filter-search:focus-within { border-color:#4f46e5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
    .filter-search i { color:#9ca3af; font-size:14px; flex-shrink:0; }
    .filter-search input { border:none; outline:none; background:transparent; font-size:13.5px; color:#111827; flex:1; min-width:0; }
    .filter-search input::placeholder { color:#9ca3af; }
    .fs-clear { background:none; border:none; color:#d1d5db; cursor:pointer; padding:0; font-size:15px; line-height:1; display:none; }
    .fs-clear:hover { color:#ef4444; }

    /* Custom dropdown filter (.fdd) */
    .fdd { position:relative; }
    .fdd-btn {
        display:inline-flex; align-items:center; gap:7px;
        height:40px; padding:0 14px;
        border:1.5px solid #e5e7eb; border-radius:9px;
        background:#f9fafb; color:#374151;
        font-size:13.5px; font-weight:600;
        cursor:pointer; white-space:nowrap;
        transition:all .15s; user-select:none;
    }
    .fdd-btn:hover { border-color:#4f46e5; background:#fff; color:#4f46e5; }
    .fdd-btn.is-open { border-color:#4f46e5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); color:#4f46e5; }
    .fdd-btn.has-value { border-color:#4f46e5; background:#eef2ff; color:#4338ca; }
    .fdd-btn.has-value:hover { background:#e0e7ff; }
    .fdd-count { background:#4f46e5; color:#fff; border-radius:20px; font-size:11px; font-weight:800; padding:1px 7px; min-width:20px; text-align:center; }
    .fdd-chevron { font-size:11px; color:#9ca3af; transition:transform .2s; flex-shrink:0; }
    .fdd-btn.is-open .fdd-chevron { transform:rotate(180deg); }
    .fdd-panel {
        position:absolute; top:calc(100% + 6px); left:0;
        z-index:1060; background:#fff;
        border:1.5px solid #e5e7eb; border-radius:12px;
        box-shadow:0 8px 30px rgba(0,0,0,.12);
        min-width:240px; max-width:320px;
        display:none; animation:fddFadeIn .12s ease;
    }
    .fdd-panel.open { display:block; }
    @keyframes fddFadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .fdd-panel-head { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid #f3f4f6; }
    .fdd-panel-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; }
    .fdd-panel-clear { font-size:12px; color:#ef4444; font-weight:700; background:none; border:none; cursor:pointer; padding:2px 6px; border-radius:5px; }
    .fdd-panel-clear:hover { background:#fee2e2; }
    .fdd-panel-search { display:flex; align-items:center; gap:7px; padding:8px 12px; border-bottom:1px solid #f3f4f6; background:#fafafa; }
    .fdd-panel-search i { color:#9ca3af; font-size:13px; }
    .fdd-panel-search input { border:none; outline:none; background:transparent; font-size:13px; color:#111827; flex:1; }
    .fdd-panel-search input::placeholder { color:#9ca3af; }
    .fdd-options { max-height:240px; overflow-y:auto; padding:6px; }
    .fdd-options::-webkit-scrollbar { width:4px; }
    .fdd-options::-webkit-scrollbar-thumb { background:#e5e7eb; border-radius:4px; }
    .fdd-option { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; user-select:none; transition:background .1s; }
    .fdd-option:hover { background:#f3f4f6; }
    .fdd-option.checked { background:#eef2ff; }
    .fdd-checkbox { width:18px; height:18px; flex-shrink:0; border:2px solid #d1d5db; border-radius:5px; display:flex; align-items:center; justify-content:center; background:#fff; transition:all .12s; }
    .fdd-option.checked .fdd-checkbox { background:#4f46e5; border-color:#4f46e5; }
    .fdd-checkbox i { font-size:11px; color:#fff; display:none; }
    .fdd-option.checked .fdd-checkbox i { display:block; }
    .fdd-opt-text { font-size:13px; color:#374151; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .fdd-option.checked .fdd-opt-text { color:#4338ca; font-weight:600; }
    .fdd-no-opts { text-align:center; padding:20px; color:#9ca3af; font-size:13px; }

    /* Simple single-select */
    .filter-select {
        height:40px; padding:0 12px;
        border:1.5px solid #e5e7eb; border-radius:9px;
        background:#f9fafb; font-size:13.5px; color:#374151;
        outline:none; cursor:pointer; transition:all .15s; font-weight:600;
        -webkit-appearance:none; appearance:none;
        background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat:no-repeat; background-position:right 10px center;
        background-size:14px; padding-right:32px;
    }
    .filter-select:focus { border-color:#4f46e5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
    .filter-select.has-value { border-color:#4f46e5; background:#eef2ff; color:#4338ca; }

    /* Divider */
    .filter-divider { width:1px; height:28px; background:#e5e7eb; flex-shrink:0; }

    /* Reset button */
    .btn-reset {
        display:inline-flex; align-items:center; gap:7px;
        height:40px; padding:0 16px;
        border:1.5px solid #fecaca; border-radius:9px;
        background:#fff5f5; color:#ef4444;
        font-size:13.5px; font-weight:700;
        cursor:pointer; white-space:nowrap; transition:all .15s;
    }
    .btn-reset:hover { background:#ef4444; color:#fff; border-color:#ef4444; }

    /* Active filter chips */
    .active-bar { display:flex; align-items:center; flex-wrap:wrap; gap:6px; padding-top:12px; margin-top:12px; border-top:1px solid #f3f4f6; }
    .active-lbl { font-size:12px; font-weight:700; color:#6b7280; white-space:nowrap; }
    .a-chip { display:inline-flex; align-items:center; gap:6px; background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:20px; font-size:12px; font-weight:700; padding:4px 6px 4px 12px; }
    .a-chip-x { width:18px; height:18px; border-radius:50%; background:#c7d2fe; color:#4338ca; border:none; cursor:pointer; font-size:13px; font-weight:900; display:flex; align-items:center; justify-content:center; line-height:1; padding:0; transition:all .13s; }
    .a-chip-x:hover { background:#4f46e5; color:#fff; }

    /* Table card wrapper */
    .table-card { background:#fff; border:1.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.06); }
    .table-card .table-responsive { margin:0; }
    .table-card .table { margin-bottom:0; }
    </style>
</head>
