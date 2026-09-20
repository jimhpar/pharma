<style>
/* ═══════════════════════════════════════════════
   Shared Filter Bar — Professional Design
   ═══════════════════════════════════════════════ */
.ix-root { display:flex; flex-direction:column; gap:18px; }

.ix-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.ix-head h2 { font-size:1.45rem; font-weight:800; color:#111827; margin:0 0 3px; display:flex; align-items:center; gap:10px; }
.ix-head p  { font-size:13px; color:#6b7280; margin:0; }
.btn-new-ix {
    display:inline-flex; align-items:center; gap:8px;
    background:#4f46e5; color:#fff; font-size:14px; font-weight:700;
    padding:10px 22px; border-radius:10px; border:none;
    text-decoration:none; cursor:pointer; white-space:nowrap;
    box-shadow:0 2px 8px rgba(79,70,229,.3); transition:all .15s;
}
.btn-new-ix:hover { background:#4338ca; color:#fff; transform:translateY(-1px); }

/* Filter Bar */
.ix-filter-bar {
    background:#fff; border:1.5px solid #e5e7eb;
    border-radius:14px; padding:14px 16px;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
}
.ix-filter-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* Search input */
.ix-search {
    display:flex; align-items:center; gap:8px;
    border:1.5px solid #e5e7eb; border-radius:9px;
    padding:0 12px; height:40px; background:#f9fafb;
    flex:1; min-width:200px; max-width:300px; transition:all .15s;
}
.ix-search:focus-within { border-color:#4f46e5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
.ix-search i { color:#9ca3af; font-size:14px; flex-shrink:0; }
.ix-search input { border:none; outline:none; background:transparent; font-size:13.5px; color:#111827; flex:1; min-width:0; }
.ix-search input::placeholder { color:#9ca3af; }
.ix-s-clear { background:none; border:none; color:#d1d5db; cursor:pointer; padding:0; font-size:15px; line-height:1; display:none; }
.ix-s-clear:hover { color:#ef4444; }

/* Filter select */
.ix-select {
    height:40px; padding:0 12px;
    border:1.5px solid #e5e7eb; border-radius:9px;
    background:#f9fafb; font-size:13.5px; color:#374151;
    outline:none; cursor:pointer; transition:all .15s; font-weight:600;
    -webkit-appearance:none; appearance:none;
    background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat:no-repeat; background-position:right 10px center;
    background-size:14px; padding-right:32px;
}
.ix-select:focus { border-color:#4f46e5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
.ix-select.has-value { border-color:#4f46e5; background:#eef2ff; color:#4338ca; }

/* Divider */
.ix-divider { width:1px; height:28px; background:#e5e7eb; flex-shrink:0; }

/* Reset */
.ix-btn-reset {
    display:inline-flex; align-items:center; gap:7px;
    height:40px; padding:0 16px;
    border:1.5px solid #fecaca; border-radius:9px;
    background:#fff5f5; color:#ef4444;
    font-size:13.5px; font-weight:700;
    cursor:pointer; white-space:nowrap; transition:all .15s;
}
.ix-btn-reset:hover { background:#ef4444; color:#fff; border-color:#ef4444; }

/* Active chips */
.ix-active-bar {
    display:flex; align-items:center; flex-wrap:wrap;
    gap:6px; padding-top:12px; margin-top:12px;
    border-top:1px solid #f3f4f6;
}
.ix-active-lbl { font-size:12px; font-weight:700; color:#6b7280; white-space:nowrap; }
.ix-chip {
    display:inline-flex; align-items:center; gap:6px;
    background:#eef2ff; color:#4338ca;
    border:1px solid #c7d2fe; border-radius:20px;
    font-size:12px; font-weight:700;
    padding:4px 6px 4px 12px;
}
.ix-chip-x {
    width:18px; height:18px; border-radius:50%;
    background:#c7d2fe; color:#4338ca;
    border:none; cursor:pointer; font-size:13px;
    font-weight:900; display:flex; align-items:center;
    justify-content:center; line-height:1; padding:0;
    transition:all .13s;
}
.ix-chip-x:hover { background:#4f46e5; color:#fff; }

/* Table card */
.ix-table-card {
    background:#fff; border:1.5px solid #e5e7eb;
    border-radius:14px; overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
}
</style>
