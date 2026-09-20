<style>
/* ═══ Shared Report Design System ═══════════════════════════════ */

/* Stat cards */
.rpt-stat {
    border-radius: 12px;
    padding: 1rem 1.2rem;
    display: flex;
    align-items: center;
    gap: .9rem;
    border: 1px solid transparent;
}
.rpt-stat .rsi {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.rpt-stat .rsv { font-size: 1.28rem; font-weight: 800; line-height: 1.1; }
.rpt-stat .rsl { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: .18rem; opacity: .75; }
.rpt-stat .rsb { font-size: .6rem; font-weight: 600; color: #94a3b8; margin-top: .1rem; }

/* color variants */
.rpt-c1 { background:#eff6ff; border-color:#bfdbfe; }
.rpt-c1 .rsi { background:#dbeafe; color:#1d4ed8; } .rpt-c1 .rsv { color:#1d4ed8; } .rpt-c1 .rsl { color:#1d4ed8; }

.rpt-c2 { background:#f0fdf4; border-color:#bbf7d0; }
.rpt-c2 .rsi { background:#dcfce7; color:#15803d; } .rpt-c2 .rsv { color:#15803d; } .rpt-c2 .rsl { color:#15803d; }

.rpt-c3 { background:#fff7ed; border-color:#fed7aa; }
.rpt-c3 .rsi { background:#ffedd5; color:#c2410c; } .rpt-c3 .rsv { color:#c2410c; } .rpt-c3 .rsl { color:#c2410c; }

.rpt-c4 { background:#fdf4ff; border-color:#e9d5ff; }
.rpt-c4 .rsi { background:#f3e8ff; color:#7e22ce; } .rpt-c4 .rsv { color:#7e22ce; } .rpt-c4 .rsl { color:#7e22ce; }

.rpt-c5 { background:#fef2f2; border-color:#fecaca; }
.rpt-c5 .rsi { background:#fee2e2; color:#dc2626; } .rpt-c5 .rsv { color:#dc2626; } .rpt-c5 .rsl { color:#dc2626; }

/* Main card */
.rpt-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(15,23,42,.06);
    overflow: visible;
}
.rpt-card .rpt-head {
    background: linear-gradient(135deg,#f8faff 0%,#eef3ff 100%);
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
}
.rpt-head-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.rpt-head-sub   { font-size: .78rem; color: #64748b; margin: .15rem 0 0; }

/* Filter bar */
.rpt-filter {
    background: #f8fafc;
    border-bottom: 1px solid #e8eef5;
    padding: .9rem 1.25rem;
}
.rpt-flabel {
    font-size: .64rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #475569;
    margin-bottom: .25rem;
    display: block;
}

/* Table scroll wrapper */
.rpt-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.rpt-scroll::-webkit-scrollbar { height: 5px; }
.rpt-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
.rpt-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

/* Table base */
.rpt-table { min-width: 800px; }
.rpt-table thead th {
    background: #f0f4fa;
    color: #374151;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 2px solid #d1d5db;
    padding: .65rem .75rem;
    white-space: nowrap;
}
.rpt-table tbody td {
    padding: .6rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: .875rem;
    color: #374151;
}
.rpt-table tbody tr:hover { background: #f8faff; }
.rpt-table tbody tr:nth-child(even) { background: #fafbfd; }
.rpt-table tbody tr:nth-child(even):hover { background: #f0f5ff; }

.rpt-table tfoot th {
    background: #f0f4fa;
    font-size: .76rem;
    font-weight: 800;
    color: #1e293b;
    border-top: 2px solid #d1d5db;
    padding: .55rem .75rem;
}
.tfl { font-size: .6rem; color: #94a3b8; font-weight: 600; display: block; text-transform: uppercase; }

/* Cell helpers */
.rpt-id   { display:inline-block; background:#1e293b; color:#fff; font-size:.7rem; font-weight:800; border-radius:5px; padding:2px 8px; letter-spacing:.03em; }
.rpt-bold { font-weight:800; color:#0f172a; }
.rpt-mute { font-size:.72rem; color:#94a3b8; margin-top:.1rem; }
.rpt-green{ font-weight:800; color:#15803d; white-space:nowrap; }
.rpt-red  { font-weight:800; color:#dc2626; white-space:nowrap; }
.rpt-blue { font-weight:700; color:#1d4ed8; white-space:nowrap; }
.rpt-amt  { font-weight:800; color:#0f766e; white-space:nowrap; }
.rpt-sku  { display:inline-block; background:#1d4ed8; color:#fff; font-size:.7rem; font-weight:800; border-radius:5px; padding:2px 8px; }

/* DataTables pagination */
.rpt-table_wrapper .dataTables_info,
.rpt-table_wrapper .dataTables_paginate,
#productSalesTable_wrapper .dataTables_info,
#productSalesTable_wrapper .dataTables_paginate,
#purchaseTable_wrapper .dataTables_info,
#purchaseTable_wrapper .dataTables_paginate,
#supplierDueTable_wrapper .dataTables_info,
#supplierDueTable_wrapper .dataTables_paginate,
#customerDueTable_wrapper .dataTables_info,
#customerDueTable_wrapper .dataTables_paginate,
#lowStockTable_wrapper .dataTables_info,
#lowStockTable_wrapper .dataTables_paginate,
#expiryTable_wrapper .dataTables_info,
#expiryTable_wrapper .dataTables_paginate {
    padding: .7rem 1.25rem;
}
</style>
