@extends('layouts.main')
@php
    use App\Support\Currency;
@endphp
<style>
    :root {
        --pos-ink: #17324d;
        --pos-muted: #6b7c93;
        --pos-line: #d9e4ef;
        --pos-surface: #ffffff;
        --pos-accent: #0f62fe;
        --pos-accent-dark: #0a4ecc;
        --pos-success-soft: #ecfdf3;
        --pos-teal: #0f766e;
        --pos-teal-soft: #e6fffb;
        --pos-amber: #b45309;
        --pos-amber-soft: #fff7ed;
        --pos-rose: #be123c;
        --pos-rose-soft: #fff1f2;
        --pos-violet: #6d28d9;
        --pos-violet-soft: #f5f3ff;
    }

    .page-heading.pos-page-heading {
        display: none;
    }

    #app .sidebar-expand-btn,
    #app .burger-btn {
        display: none !important;
    }

    #main header {
        display: none ;
    }

    #main {
        padding: 0.5rem 0.5rem 0;
    }

    .page-content.pos-workspace {
        background:
            radial-gradient(circle at top right, rgba(15, 98, 254, 0.08), transparent 28%),
            linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
        border-radius: 1.1rem;
        padding: 0.4rem;
        min-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        overflow: visible;
        margin-bottom: 0 !important;
    }

    .pos-hero {
        background: linear-gradient(135deg, #103b63 0%, #174d7d 55%, #23639d 100%);
        border-radius: 0.9rem;
        color: #fff;
        padding: 0.45rem 0.7rem;
        margin-bottom: 0;
        box-shadow: 0 10px 26px rgba(16, 59, 99, 0.14);
    }

    .pos-hero-title {
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 0.05rem;
    }

    .pos-hero-subtitle {
        font-size: 0.7rem;
        opacity: 0.82;
        margin-bottom: 0;
    }

    .toolbar-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 0.75rem;
        padding: 0.3rem 0.55rem;
        min-width: 108px;
    }

    .toolbar-label {
        display: block;
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.7;
        margin-bottom: 0.1rem;
    }

    .toolbar-value {
        font-size: 0.9rem;
        font-weight: 700;
    }

    .hero-action-btn {
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-radius: 999px;
        padding: 0.32rem 0.62rem;
        font-size: 0.7rem;
        font-weight: 600;
        transition: background-color 0.15s ease-in-out, transform 0.15s ease-in-out;
    }

    .pos-main-grid {
        flex: 1;
        min-height: 0;
        align-items: flex-start;
    }

    .pos-main-grid > [class*="col-"] {
        display: flex;
        min-height: 0;
    }

    .pos-main-grid .cart-section,
    .pos-main-grid .products-panel {
        height: auto !important;
        width: 100%;
    }

    .pos-main-grid > [class*="col-"] > .h-100,
    .pos-main-grid > [class*="col-"] > .card {
        width: 100%;
    }

    .hero-action-btn:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        transform: translateY(-1px);
    }

    .cart-section {
        background: #ffffff;
        border-radius: 1.1rem;
        border: 1px solid #dce6f1;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
        backdrop-filter: blur(8px);
        min-height: 0;
        overflow: hidden;
    }

    .cart-item {
        border: 1px solid #dce6f1;
        border-radius: 0.72rem;
        padding: 0.55rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, transform 0.15s ease-in-out;
    }

    .cart-item:hover {
        border-color: #b8c9da;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
    }

    .cart-item-name {
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--pos-ink);
        margin-bottom: 0.15rem;
    }

    .cart-item-code {
        font-size: 0.76rem;
        color: var(--pos-muted);
    }

    .cart-item-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: start;
    }

    .cart-item-details {
        min-width: 0;
    }

    .cart-item-code {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cart-item-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cart-item-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: 0.38rem;
    }

    .cart-item-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border-radius: 999px;
        background: #f2f6fb;
        color: #5f7185;
        padding: 0.28rem 0.58rem;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1;
    }

    .cart-item-chip:nth-child(1) {
        background: var(--pos-teal-soft);
        color: var(--pos-teal);
    }

    .cart-item-chip:nth-child(2) {
        background: var(--pos-amber-soft);
        color: var(--pos-amber);
    }

    .cart-item-chip strong {
        color: var(--pos-ink);
        font-weight: 700;
    }

    .cart-item-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.4rem;
    }

    .cart-item-total {
        text-align: right;
        min-width: 5.2rem;
    }

    .cart-item-total-label {
        display: block;
        font-size: 0.62rem;
        color: var(--pos-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
    }

    .cart-price-total {
        display: block;
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--pos-ink);
        line-height: 1.15;
    }

    .cart-remove-btn {
        width: 1.7rem;
        height: 1.7rem;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff1f3;
        border: 1px solid #ffd3da;
        color: #e11d48;
        padding: 0;
        font-size: 0.78rem;
    }

    .cart-remove-btn:hover {
        background: #ffe4e9;
        color: #be123c;
    }

    .prescription-icon {
        font-size: 0.75rem;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.22rem;
        padding: 0.16rem;
        border: 1px solid #dce6f1;
        border-radius: 999px;
        background: #f8fbff;
        width: max-content;
        max-width: 100%;
        margin: 0 auto;
    }

    .quantity-btn {
        width: 1.35rem;
        height: 1.35rem;
        padding: 0;
        border: none;
        background: #ffffff;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        color: #52677d;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
        flex: 0 0 auto;
    }

    .quantity-btn:hover {
        background: #e8f1ff;
        color: var(--pos-accent-dark);
    }

    .quantity-input {
        width: 1.55rem;
        text-align: center;
        font-size: 0.82rem;
        font-weight: 700;
        border: none;
        padding: 0.125rem;
        height: 1.45rem;
        background: transparent;
        color: var(--pos-ink);
        flex: 0 0 auto;
    }

    .quantity-input:focus {
        outline: none;
        box-shadow: 0 0 0 0.125rem rgba(13, 110, 253, 0.25);
    }

    .product-card {
        border: 1px solid #dbe5ef;
        border-radius: 0.8rem;
        padding: 0.42rem;
        cursor: pointer;
        transition: border-color 0.15s ease-in-out, transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        height: 100%;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .product-card:hover {
        border-color: var(--pos-accent);
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(15, 98, 254, 0.12);
    }
    .product-thumb {
        width: 100%;
        height: 5.5rem;
        border-radius: 0.65rem;
        overflow: hidden;
        background: linear-gradient(135deg, #f7f9fc 0%, #edf3f9 100%);
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .product-card-body {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        min-width: 0;
        overflow: hidden;
    }
    .product-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--pos-ink);
        margin-bottom: 0;
    }
    .pos-pharma-sub {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        margin-top: 2px;
        margin-bottom: 0;
        line-height: 1.4;
    }
    .pc-divider {
        margin: 0.4rem 0 0.35rem;
        border: none;
        border-top: 1px solid #edf2f7;
    }
    .product-card-meta {
        display: grid;
        gap: 0.3rem;
        margin-bottom: 0.45rem;
    }
    .product-card-category {
        font-size: 0.74rem;
        color: var(--pos-muted);
        line-height: 1.45;
    }
    .product-card-code-row,
    .product-card-stats,
    .product-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        min-width: 0;
    }
    .product-card-code-row > * { min-width: 0; }
    .product-card-stats {
        margin-top: auto;
        padding-top: 0.35rem;
        border-top: 1px solid #edf2f7;
        font-size: 0.82rem;
    }
    .product-card-footer {
        margin-top: 0.32rem;
    }
    .product-card-stock,
    .product-card-sku-count {
        font-size: 0.76rem;
        color: var(--pos-muted);
        white-space: nowrap;
    }
    .product-thumb-fallback {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .variant-modal-dialog {
        max-width: 520px;
    }

    .variant-modal-content {
        border: 0;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
    }

    .variant-modal-header {
        padding: 0.85rem 1rem;
        background: linear-gradient(135deg, #ffffff 0%, #eef6ff 100%);
        border-bottom: 1px solid #dbe7f3;
    }

    .variant-modal-title {
        color: var(--pos-ink);
        font-weight: 800;
        font-size: 0.98rem;
    }

    .variant-product-head {
        display: grid;
        grid-template-columns: 76px minmax(0, 1fr);
        gap: 0.85rem;
        align-items: center;
        margin-bottom: 0.9rem;
    }

    .variant-product-thumb {
        width: 76px;
        height: 76px;
        border-radius: 0.9rem;
        overflow: hidden;
        background: #f2f6fb;
        display: grid;
        place-items: center;
        color: #6b7c93;
        font-size: 0.66rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .variant-product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .variant-product-title {
        color: var(--pos-ink);
        font-size: 1.05rem;
        font-weight: 800;
        margin-bottom: 0.15rem;
    }

    .variant-product-meta {
        color: var(--pos-muted);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .variant-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
        color: #65758a;
        margin-bottom: 0.45rem;
    }

    .variant-options {
        display: grid;
        gap: 0.45rem;
        margin-bottom: 0.85rem;
    }

    .variant-option-btn {
        border: 1px solid #d9e4ef;
        background: #fff;
        border-radius: 0.75rem;
        padding: 0.62rem 0.75rem;
        text-align: left;
        color: var(--pos-ink);
        transition: all 0.15s ease-in-out;
    }

    .variant-option-btn.active {
        background: #eef5ff;
        border-color: #2f6fed;
        box-shadow: 0 0 0 2px rgba(47, 111, 237, 0.12);
    }

    .variant-option-title {
        display: block;
        font-size: 0.86rem;
        font-weight: 800;
    }

    .variant-option-meta {
        display: block;
        margin-top: 0.12rem;
        font-size: 0.72rem;
        color: var(--pos-muted);
    }

    .variant-summary {
        border: 1px solid #d9e4ef;
        border-radius: 0.9rem;
        padding: 0.8rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .sale-unit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        margin: 0.65rem 0;
    }

    .sale-unit-btn {
        border: 1px solid #d9e4ef;
        border-radius: 0.75rem;
        background: #fff;
        color: var(--pos-ink);
        padding: 0.62rem;
        text-align: left;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .sale-unit-btn.active {
        background: #1f4fc2;
        border-color: #1f4fc2;
        color: #fff;
    }

    .sale-unit-btn small {
        display: block;
        font-size: 0.68rem;
        font-weight: 600;
        opacity: 0.78;
        margin-top: 0.08rem;
    }

    .variant-price-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding-top: 0.55rem;
        border-top: 1px solid #e6edf5;
        font-size: 0.9rem;
        color: var(--pos-ink);
    }

    .variant-price-row strong {
        font-weight: 900;
    }

    .product-action-chip {
        background: var(--pos-success-soft);
        color: #198754;
        font-size: 0.76rem;
        padding: 0.12rem 0.35rem;
        border-radius: 999px;
        font-weight: 600;
    }

    .rx-badge {
        background: #eff6ff;
        color: var(--pos-accent-dark);
        font-size: 0.72rem;
        padding: 0.18rem 0.45rem;
        border-radius: 6px;
        font-weight: 600;
        display: inline-block;
        min-width: 0;
        max-width: 100%;
        word-break: break-all;
        line-height: 1.4;
    }

    .payment-plan-card {
        border: 1px solid rgba(109, 40, 217, 0.18);
        border-radius: 0.6rem;
        background: linear-gradient(135deg, #f5f3ff 0%, #fbfaff 100%);
        padding: 0.32rem;
    }

    .payment-row {
        border: 1px solid #e3ebf3;
        border-radius: 0.65rem;
        background: #fff;
        padding: 0.3rem;
    }

    .payment-summary-card {
        border: 1px solid rgba(15, 98, 254, 0.18);
        border-radius: 0.7rem;
        background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 100%);
        padding: 0.48rem 0.55rem;
    }

    .payment-summary-card.summary-paid {
        border-color: rgba(15, 118, 110, 0.22);
        background: linear-gradient(135deg, #e6fffb 0%, #f8fffd 100%);
    }

    .payment-summary-card.summary-due {
        border-color: rgba(180, 83, 9, 0.24);
        background: linear-gradient(135deg, #fff7ed 0%, #fffbf5 100%);
    }

    .payment-summary-label {
        display: block;
        font-size: 0.58rem;
        color: var(--pos-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        margin-bottom: 0.12rem;
    }

    .payment-summary-value {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--pos-ink);
    }

    .payment-summary-value.is-due {
        color: #b42318;
    }

    .shortcuts-btn {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        z-index: 1000;
    }

    #cart-container,
    #cart-container > .p-3,
    .cart-scroll,
    .products-panel,
    .products-scroll {
        min-height: 0;
    }

    #cart-container {
        flex: 1 1 auto !important;
        overflow: visible !important;
    }

    #cart-container > .p-3 {
        display: flex;
        flex-direction: column;
        height: auto;
        padding: 0.55rem 0.85rem !important;
    }

    .products-scroll {
        flex: 1;
        overflow-y: auto;
        max-height: calc(100vh - 14rem);
    }

    .cart-scroll {
        flex: 1 1 auto;
        min-height: 4rem;
        max-height: clamp(12rem, 34vh, 21rem);
        overflow-y: auto;
        overflow-x: auto;
        padding-right: 0;
    }

    .cart-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0 0.58rem;
        margin: 0;
    }

    .cart-table th:nth-child(1),
    .cart-table td:nth-child(1) {
        width: 26%;
    }

    .cart-table th:nth-child(2),
    .cart-table td:nth-child(2) {
        width: 14%;
    }

    .cart-table th:nth-child(3),
    .cart-table td:nth-child(3) {
        width: 16%;
    }

    .cart-table th:nth-child(4),
    .cart-table td:nth-child(4) {
        width: 20%;
    }

    .cart-table th:nth-child(5),
    .cart-table td:nth-child(5) {
        width: 15%;
    }

    .cart-table th:nth-child(6),
    .cart-table td:nth-child(6) {
        width: 9%;
    }

    .cart-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #fff;
        color: var(--pos-muted);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.48rem 0.62rem;
        border-bottom: 1px solid #edf2f7;
        white-space: nowrap;
    }

    .cart-table tbody tr.cart-item {
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }

    .cart-table tbody td {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border-top: 1px solid #dce6f1;
        border-bottom: 1px solid #dce6f1;
        padding: 0.62rem 0.5rem;
        vertical-align: middle;
        overflow: hidden;
    }

    .cart-table tbody tr:nth-child(odd) td {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .cart-line-index {
        width: 2rem;
        height: 2rem;
        border-radius: 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eaf2ff;
        color: var(--pos-accent-dark);
        font-size: 0.86rem;
        font-weight: 800;
        flex: 0 0 auto;
    }

    .cart-product-cell {
        display: flex;
        align-items: flex-start;
        gap: 0.72rem;
        min-width: 0;
    }

    .cart-product-main {
        min-width: 0;
    }

    .cart-sku-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #eef4fb;
        color: #58708a;
        padding: 0.18rem 0.52rem;
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 0.18rem;
    }

    .cart-total-cell {
        border-left: 1px solid #edf2f7;
    }

    .cart-table tbody td:first-child {
        border-left: 1px solid #dce6f1;
        border-top-left-radius: 0.9rem;
        border-bottom-left-radius: 0.9rem;
    }

    .cart-table tbody td:last-child {
        border-right: 1px solid #dce6f1;
        border-top-right-radius: 0.9rem;
        border-bottom-right-radius: 0.9rem;
    }

    .cart-discount-input {
        width: 100%;
        max-width: 5.4rem;
        min-height: 2.2rem;
        border: 1px solid #f4c99c;
        border-radius: 0.55rem;
        background: var(--pos-amber-soft);
        color: var(--pos-amber);
        font-size: 0.86rem;
        font-weight: 700;
        padding: 0.32rem 0.55rem;
    }

    .cart-qty-cell {
        overflow: visible !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .search-container {
        background: linear-gradient(180deg, #f8fbff 0%, #eef5fc 100%);
        padding: 0.55rem 0.75rem;
        border-bottom: 1px solid #e3ebf3;
    }

    .customer-panel {
        position: relative;
    }

    .customer-modal-dialog {
        max-width: 920px;
    }

    .customer-modal-content {
        border: 0;
        border-radius: 1.2rem;
        overflow: hidden;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
    }

    .customer-modal-header {
        padding: 1rem 1.1rem 0.85rem;
        border-bottom: 1px solid #e6edf5;
        background: linear-gradient(135deg, #ffffff 0%, #f4f9ff 100%);
    }

    .customer-modal-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #15396b;
        margin-bottom: 0.15rem;
    }

    .customer-modal-subtitle {
        font-size: 0.82rem;
        color: #60748a;
        margin: 0;
    }

    .panel-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }

    .panel-eyebrow {
        font-size: 0.66rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--pos-muted);
        font-weight: 700;
        margin-bottom: 0.1rem;
    }

    .panel-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--pos-ink);
        line-height: 1.15;
    }

    .panel-subtitle {
        font-size: 0.72rem;
        color: var(--pos-muted);
        margin-top: 0.1rem;
    }

    .panel-shortcut {
        display: inline-flex;
        align-items: center;
        border: 1px solid #d9e4ef;
        background: #fff;
        border-radius: 999px;
        color: var(--pos-muted);
        font-size: 0.68rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        white-space: nowrap;
    }

    .customer-action-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.6rem;
        margin-top: 0.6rem;
    }

    .customer-action-row .btn {
        white-space: nowrap;
    }

    .suggestions-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 0.25rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        z-index: 1050;
        max-height: 16rem;
        overflow-y: auto;
        display: none;
    }

    .suggestions-dropdown.show {
        display: block;
    }

    .suggestion-item {
        padding: 0.9rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.15s ease-in-out;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover {
        background-color: #f8f9fa;
    }

    .suggestion-item.create-option {
        background-color: #e7f3ff;
        color: #0d6efd;
        border-top: 1px solid #dee2e6;
        border-bottom: none;
    }

    .suggestion-item.create-option:hover {
        background-color: #cce7ff;
    }

    .suggestion-name {
        font-weight: 700;
        font-size: 0.96rem;
        margin-bottom: 0.18rem;
    }

    .suggestion-phone {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .create-customer-form {
        margin-top: 0.9rem;
        padding: 1rem;
        border: 1px solid #d9e6f2;
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        display: none;
        max-height: 28rem;
        overflow-y: auto;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }

    .create-customer-form.show {
        display: block;
    }

    .form-title {
        font-size: 1rem;
        font-weight: 800;
        color: #173c6d;
        margin-bottom: 0.2rem;
    }

    .customer-form-subtitle {
        font-size: 0.8rem;
        color: #6a7d90;
        margin-bottom: 0.85rem;
    }

    .customer-form-section {
        border: 1px solid #e3ebf3;
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.85rem;
    }

    .customer-form-label {
        display: block;
        font-size: 0.74rem;
        font-weight: 800;
        color: #5d7086;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.32rem;
    }

    .customer-form-section .form-control,
    .customer-form-section .form-select {
        min-height: 2.8rem;
        border-radius: 0.8rem;
        border-color: #d7e2ed;
        font-size: 0.95rem;
        padding: 0.75rem 0.9rem;
    }

    .customer-form-section textarea.form-control {
        min-height: 5.4rem;
        resize: vertical;
    }

    .cart-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.38rem;
        padding-bottom: 0.32rem;
        border-bottom: 1px solid #edf2f7;
    }

    .cart-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--pos-ink);
        margin-bottom: 0;
    }

    .cart-subtitle {
        font-size: 0.72rem;
        color: var(--pos-muted);
    }

    .cart-count-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.34rem 0.6rem;
        background: var(--pos-violet-soft);
        color: var(--pos-violet);
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .ghost-action-btn {
        border: 1px solid #d9e4ef;
        background: #fff;
        color: #5f7185;
        border-radius: 999px;
        padding: 0.28rem 0.58rem;
        font-size: 0.68rem;
        font-weight: 600;
        transition: border-color 0.15s ease-in-out, background-color 0.15s ease-in-out, color 0.15s ease-in-out;
    }

    .ghost-action-btn:hover {
        border-color: #bfd3e6;
        color: var(--pos-ink);
        background: #f8fbff;
    }

    .form-control-sm {
        font-size: 0.875rem;
    }

    .btn-sm {
        font-size: 0.875rem;
    }

    .search-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.125rem rgba(13, 110, 253, 0.25);
    }

    .sale-panel-header {
        padding: 0.62rem 0.8rem;
        border-bottom: 1px solid #e3ebf3;
        background: linear-gradient(135deg, #ffffff 0%, #f5f9fe 100%);
    }

    .sale-panel-title {
        font-size: 0.98rem;
        font-weight: 800;
        color: #1f3f75;
        margin-bottom: 0.14rem;
    }

    .sale-panel-subtitle {
        font-size: 0.68rem;
        color: var(--pos-muted);
        margin-bottom: 0;
    }

    .customer-search-shell {
        position: relative;
    }

    .customer-search-icon {
        position: absolute;
        left: 0.82rem;
        top: 50%;
        transform: translateY(-50%);
        color: #8a9aab;
        pointer-events: none;
        font-size: 0.82rem;
    }

    .customer-search-shell .search-input {
        min-height: 3.15rem;
        border-radius: 0.95rem;
        border-color: #d8e4ef;
        padding-left: 2.8rem;
        padding-right: 1rem;
        font-size: 0.98rem;
        font-weight: 600;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
    }

    .customer-search-caption {
        margin-top: 0.55rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .customer-search-caption-text {
        font-size: 0.82rem;
        color: #64748b;
    }

    .customer-action-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 0.45rem;
    }

    .customer-picker-btn {
        min-height: 2rem;
        border: 1px solid #cfddec;
        background: #fff;
        color: var(--pos-ink);
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.38rem 0.72rem;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .customer-picker-header-btn {
        min-height: auto;
        border-color: rgba(15, 118, 110, 0.22);
        background: var(--pos-teal-soft);
        color: var(--pos-teal);
        border-radius: 999px;
        padding: 0.28rem 0.65rem;
        font-size: 0.7rem;
        white-space: nowrap;
    }

    .customer-picker-btn:hover {
        border-color: var(--pos-accent);
        background: #f8fbff;
    }

    .customer-primary-action {
        min-height: 2.9rem;
        border-radius: 0.85rem;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .customer-secondary-action {
        min-height: 2.9rem;
        border-radius: 0.85rem;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .customer-picker-copy {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .customer-picker-copy span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .selected-customer-slot:empty {
        display: none;
    }

    .selected-customer-slot {
        padding: 0.55rem 0.75rem 0;
    }

    .cart-empty-state {
        display: grid;
        place-items: center;
        min-height: 8rem;
        border: 1px dashed #cbd8e6;
        border-radius: 0.9rem;
        background: #f8fbff;
        color: var(--pos-muted);
        font-size: 0.84rem;
        font-weight: 600;
    }

    .products-panel {
        display: flex;
        flex-direction: column;
        border-radius: 1.25rem;
        border: 1px solid rgba(217, 228, 239, 0.9);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .products-panel-header {
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid #e3ebf3;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.98) 0%, rgba(242, 246, 251, 0.98) 100%);
    }

    /* #skuList {
        display: grid !important;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.5rem;
        margin: 0;
    } */

    /* #skuList > [class*="col-"] {
        width: auto !important;
        max-width: none !important;
        flex: none !important;
        padding: 0 !important;
    } */

    .products-toolbar .form-control,
    .products-toolbar .form-select {
        min-height: calc(1.8em + 0.55rem + 2px);
        font-size: 0.875rem;
    }

    .products-toolbar .btn {
        min-height: calc(1.8em + 0.55rem + 2px);
    }

    .section-kicker {
        display: block;
        font-size: 0.66rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--pos-muted);
        margin-bottom: 0.1rem;
        font-weight: 700;
    }

    .section-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--pos-ink);
        margin-bottom: 0;
    }

    .section-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        background: var(--pos-amber-soft);
        color: var(--pos-amber);
        padding: 0.32rem 0.62rem;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .total-strip {
        border-radius: 0.9rem;
        background: linear-gradient(135deg, rgba(15, 98, 254, 0.08) 0%, rgba(15, 98, 254, 0.02) 100%);
        border: 1px solid rgba(15, 98, 254, 0.15);
    }

    .checkout-panel {
        padding: 0.22rem !important;
        flex: 0 0 auto;
    }

    .checkout-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }

    .checkout-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--pos-ink);
        margin-bottom: 0.1rem;
    }

    .checkout-subtitle {
        font-size: 0.72rem;
        color: var(--pos-muted);
    }

    .checkout-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        background: #ecfdf3;
        color: #0f8a4b;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .checkout-panel label {
        font-size: 0.58rem;
        font-weight: 700;
        color: var(--pos-muted);
        margin-bottom: 0.08rem;
        display: block;
    }

    .checkout-panel .form-control,
    .checkout-panel .form-select {
        min-height: calc(1.2em + 0.24rem + 2px);
        padding: 0.12rem 0.34rem;
        font-size: 0.68rem;
    }

    .checkout-panel .sale-action-btn {
        font-size: 0.7rem;
        padding-top: 0.34rem !important;
        padding-bottom: 0.34rem !important;
    }

    body.invoice-preview-open .modal-backdrop.show {
        opacity: 0.18;
    }

    #invoicePreviewModal .modal-content,
    #invoicePreviewModal .modal-header,
    #invoicePreviewModal .modal-footer {
        background: #ffffff;
        color: #111827;
    }

    #invoicePreviewModal .modal-body {
        background: #f8fafc;
    }

    #invoicePreviewFrame {
        background: #ffffff;
        color-scheme: light;
    }

    .checkout-panel .sale-action-btn[data-sale-type="Sale"] {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
        border-color: #0f766e;
        color: #fff;
    }

    .checkout-primary-action {
        min-height: 2rem;
        font-size: 0.82rem !important;
        font-weight: 800;
    }

    .checkout-panel .sale-action-btn[data-sale-type="Sale"]:hover {
        background: linear-gradient(135deg, #115e59 0%, #0f766e 100%);
        border-color: #115e59;
    }

    .checkout-panel .mb-3 {
        margin-bottom: 0.28rem !important;
    }

    .checkout-panel .payment-plan-card > .small {
        display: none;
    }

    .checkout-panel .payment-plan-card .mt-2 {
        margin-top: 0.22rem !important;
    }

    .checkout-panel .row.g-2 {
        --bs-gutter-y: 0.14rem;
    }

    .checkout-panel .row.g-1 {
        --bs-gutter-y: 0.18rem;
    }

    .selected-customer-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 0.7rem;
        border: 1px solid #cfe2ff;
        border-radius: 0.75rem;
        background: linear-gradient(180deg, #f7fbff 0%, #eef5ff 100%);
    }

    .selected-customer-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--pos-ink);
        margin-bottom: 0.08rem;
    }

    .selected-customer-meta {
        font-size: 0.72rem;
        color: var(--pos-muted);
    }

    .selected-customer-card .btn {
        white-space: nowrap;
    }

    @media (min-width: 992px) {
        .pos-main-grid {
            --bs-gutter-x: 0;
        }

        .pos-main-grid .products-panel {
            border-top-left-radius: 0.7rem;
            border-bottom-left-radius: 0.7rem;
        }

        .pos-main-grid .cart-section {
            border-top-right-radius: 0.7rem;
            border-bottom-right-radius: 0.7rem;
            border-left-width: 0;
        }
    }

    @media (max-width: 575.98px) {
        .cart-item-grid {
            grid-template-columns: 1fr;
        }

        .cart-item-actions {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .cart-item-total {
            text-align: left;
        }

        .customer-action-row {
            align-items: stretch;
            flex-direction: column;
        }

        .customer-action-row .ghost-action-btn {
            width: 100%;
        }
    }

    @media (max-width: 991.98px) {
        /* #skuList {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        } */

        .cart-table,
        .cart-table thead,
        .cart-table tbody,
        .cart-table tr,
        .cart-table th,
        .cart-table td {
            display: block;
            width: 100% !important;
        }

        .cart-table {
            table-layout: auto;
            border-spacing: 0;
        }

        .cart-table thead {
            display: none;
        }

        .cart-table tbody tr.cart-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            border: 1px solid #dce6f1;
            border-radius: 0.95rem;
            padding: 0.7rem;
            margin-bottom: 0.65rem;
            background: #fff;
        }

        .cart-table tbody td {
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0 !important;
            overflow: visible;
        }

        .cart-table tbody td:first-child {
            grid-column: 1 / -1;
        }

        .cart-total-cell {
            border-left: 0;
        }

        .cart-table tbody td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 0.18rem;
            color: var(--pos-muted);
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .cart-table tbody td:first-child::before {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        /* #skuList {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        } */
    }

    html[data-bs-theme="dark"] .page-content.pos-workspace {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.12), transparent 28%),
            linear-gradient(180deg, #0f172a 0%, #111c33 100%);
        border: 1px solid rgba(148, 163, 184, 0.1);
    }

    html[data-bs-theme="dark"] .cart-section,
    html[data-bs-theme="dark"] .products-panel {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(148, 163, 184, 0.14);
        box-shadow: 0 18px 40px rgba(2, 6, 23, 0.32);
    }

    html[data-bs-theme="dark"] .search-container,
    html[data-bs-theme="dark"] .products-panel-header,
    html[data-bs-theme="dark"] .sale-panel-header {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.96) 0%, rgba(18, 28, 48, 0.96) 100%);
        border-color: rgba(148, 163, 184, 0.14);
    }

    html[data-bs-theme="dark"] .section-kicker,
    html[data-bs-theme="dark"] .toolbar-label,
    html[data-bs-theme="dark"] .panel-eyebrow,
    html[data-bs-theme="dark"] .product-thumb-fallback,
    html[data-bs-theme="dark"] .variant-label,
    html[data-bs-theme="dark"] .suggestion-phone {
        color: #94a3b8;
    }

    html[data-bs-theme="dark"] .section-title,
    html[data-bs-theme="dark"] .toolbar-value,
    html[data-bs-theme="dark"] .cart-item-name,
    html[data-bs-theme="dark"] .form-title,
    html[data-bs-theme="dark"] .panel-title,
    html[data-bs-theme="dark"] .cart-title,
    html[data-bs-theme="dark"] .checkout-title,
    html[data-bs-theme="dark"] .sale-panel-title,
    html[data-bs-theme="dark"] .cart-price-total,
    html[data-bs-theme="dark"] .cart-item-chip strong {
        color: #f8fafc;
    }

    html[data-bs-theme="dark"] .panel-subtitle,
    html[data-bs-theme="dark"] .cart-subtitle,
    html[data-bs-theme="dark"] .checkout-subtitle,
    html[data-bs-theme="dark"] .selected-customer-meta,
    html[data-bs-theme="dark"] .panel-shortcut,
    html[data-bs-theme="dark"] .sale-panel-subtitle,
    html[data-bs-theme="dark"] .cart-item-code,
    html[data-bs-theme="dark"] .cart-item-total-label {
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .product-card {
        background: linear-gradient(180deg, #172033 0%, #111827 100%);
        border-color: rgba(71, 85, 105, 0.68);
        box-shadow: 0 12px 28px rgba(2, 6, 23, 0.22);
    }

    html[data-bs-theme="dark"] .product-card:hover {
        border-color: #38bdf8;
        box-shadow: 0 18px 34px rgba(14, 165, 233, 0.2);
    }

    html[data-bs-theme="dark"] .product-card-title,
    html[data-bs-theme="dark"] .product-card .fw-semibold,
    html[data-bs-theme="dark"] .product-card .small:not(.text-muted) {
        color: #f8fafc;
    }

    html[data-bs-theme="dark"] .product-card-stats {
        border-top-color: rgba(71, 85, 105, 0.54);
    }

    html[data-bs-theme="dark"] .product-thumb {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    html[data-bs-theme="dark"] .product-action-chip {
        background: rgba(34, 197, 94, 0.14);
        color: #86efac;
    }

    html[data-bs-theme="dark"] .rx-badge {
        background: rgba(59, 130, 246, 0.16);
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .section-meta-chip {
        background: rgba(59, 130, 246, 0.14);
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .panel-shortcut,
    html[data-bs-theme="dark"] .ghost-action-btn {
        background: #172033;
        border-color: rgba(71, 85, 105, 0.72);
    }

    html[data-bs-theme="dark"] .cart-count-pill {
        background: rgba(59, 130, 246, 0.16);
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .payment-plan-card,
    html[data-bs-theme="dark"] .payment-row {
        background: #172033;
        border-color: rgba(71, 85, 105, 0.72);
    }

    html[data-bs-theme="dark"] .payment-summary-card {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(96, 165, 250, 0.22);
    }

    html[data-bs-theme="dark"] .payment-summary-value {
        color: #f8fafc;
    }

    html[data-bs-theme="dark"] .selected-customer-card {
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.95) 100%);
        border-color: rgba(59, 130, 246, 0.28);
    }

    html[data-bs-theme="dark"] .selected-customer-name {
        color: #f8fafc;
    }

    html[data-bs-theme="dark"] .variant-option-btn,
    html[data-bs-theme="dark"] .variant-summary,
    html[data-bs-theme="dark"] .suggestions-dropdown,
    html[data-bs-theme="dark"] .create-customer-form {
        background: #0f172a;
        border-color: rgba(71, 85, 105, 0.7);
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] .variant-option-btn.active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    html[data-bs-theme="dark"] .suggestion-item {
        border-color: rgba(71, 85, 105, 0.45);
    }

    html[data-bs-theme="dark"] .suggestion-item:hover {
        background-color: rgba(59, 130, 246, 0.12);
    }

    html[data-bs-theme="dark"] .customer-modal-header {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%);
        border-color: #263445;
    }

    html[data-bs-theme="dark"] .customer-modal-title {
        color: #e5eefb;
    }

    html[data-bs-theme="dark"] .customer-modal-subtitle,
    html[data-bs-theme="dark"] .customer-search-caption-text,
    html[data-bs-theme="dark"] .customer-form-subtitle,
    html[data-bs-theme="dark"] .customer-form-label {
        color: #9db0c3;
    }

    html[data-bs-theme="dark"] .customer-form-section {
        background: rgba(15, 23, 42, 0.55);
        border-color: #314256;
    }

    html[data-bs-theme="dark"] .suggestion-item.create-option {
        background-color: rgba(37, 99, 235, 0.2);
        color: #bfdbfe;
        border-top-color: rgba(71, 85, 105, 0.55);
    }

    html[data-bs-theme="dark"] .suggestion-item.create-option:hover {
        background-color: rgba(37, 99, 235, 0.28);
    }

    html[data-bs-theme="dark"] .cart-item,
    html[data-bs-theme="dark"] .total-strip {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(71, 85, 105, 0.68);
    }

    html[data-bs-theme="dark"] .cart-item:hover {
        border-color: rgba(96, 165, 250, 0.65);
    }

    html[data-bs-theme="dark"] .cart-table thead th {
        background: #0f172a;
        border-bottom-color: rgba(71, 85, 105, 0.55);
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .cart-table tbody td {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(71, 85, 105, 0.68);
    }

    @media (max-width: 991.98px) {
        html[data-bs-theme="dark"] .cart-table tbody tr.cart-item {
            background: rgba(15, 23, 42, 0.74);
            border-color: rgba(71, 85, 105, 0.68);
        }

        html[data-bs-theme="dark"] .cart-table tbody td::before {
            color: #94a3b8;
        }
    }

    html[data-bs-theme="dark"] .cart-discount-input {
        background: rgba(180, 83, 9, 0.14);
        border-color: rgba(251, 146, 60, 0.35);
        color: #fed7aa;
    }

    html[data-bs-theme="dark"] .cart-line-index {
        background: rgba(59, 130, 246, 0.16);
        color: #bfdbfe;
    }

    html[data-bs-theme="dark"] .cart-sku-badge {
        background: rgba(148, 163, 184, 0.16);
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .cart-total-cell {
        border-left-color: rgba(71, 85, 105, 0.55);
    }

    html[data-bs-theme="dark"] .cart-item-chip,
    html[data-bs-theme="dark"] .quantity-controls,
    html[data-bs-theme="dark"] .cart-empty-state {
        background: rgba(30, 41, 59, 0.84);
        border-color: rgba(71, 85, 105, 0.7);
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .cart-remove-btn {
        background: rgba(225, 29, 72, 0.14);
        border-color: rgba(244, 63, 94, 0.35);
        color: #fb7185;
    }

    html[data-bs-theme="dark"] .quantity-btn {
        background: #1e293b;
        color: #cbd5e1;
    }

    html[data-bs-theme="dark"] .quantity-btn:hover {
        background: #334155;
    }

    html[data-bs-theme="dark"] .quantity-input,
    html[data-bs-theme="dark"] .search-input,
    html[data-bs-theme="dark"] .form-control-sm,
    html[data-bs-theme="dark"] .form-select,
    html[data-bs-theme="dark"] #delivery_charge,
    html[data-bs-theme="dark"] #order_discount {
        background-color: #0f172a;
        border-color: rgba(71, 85, 105, 0.7);
        color: #e2e8f0;
    }

    html[data-bs-theme="dark"] #posWorkspace:fullscreen {
        background: linear-gradient(180deg, #0f172a 0%, #111c33 100%);
    }

    #posWorkspace:fullscreen {
        background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
        padding: 1.25rem;
        overflow: auto;
    }

    #posWorkspace:fullscreen .products-scroll {
        max-height: none;
    }

    #posWorkspace:fullscreen .cart-scroll {
        height: auto;
        max-height: clamp(19rem, 42vh, 23rem);
    }

    #app.pos-focus-mode #main {
        width: 100%;
    }

    #app.pos-focus-mode .navbar-top,
    #app.pos-focus-mode .page-heading {
        display: none;
    }

    #app.pos-focus-mode #posWorkspace {
        margin-top: 0.25rem;
    }

    /* ── Process Sale panel enhancements ── */
    .sale-panel-header {
        padding: 0.34rem 0.85rem 0.34rem 1.05rem;
        border-bottom: 1px solid #e3ebf3;
        background: linear-gradient(135deg, #f0f7ff 0%, #f5f9fe 60%, #ffffff 100%);
        position: relative;
        overflow: hidden;
    }

    .sale-panel-header::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3.5px;
        background: linear-gradient(180deg, #0f62fe 0%, #0d9488 100%);
        border-radius: 0 2px 2px 0;
    }

    .sale-panel-title {
        font-size: 0.9rem;
        font-weight: 800;
        color: #1a3d72;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.42rem;
    }

    .sale-panel-title-icon {
        width: 1.32rem;
        height: 1.32rem;
        border-radius: 0.45rem;
        background: linear-gradient(135deg, #0f62fe 0%, #3b82f6 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.72rem;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(15, 98, 254, 0.3);
    }

    .checkout-section-label {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.58rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--pos-muted);
        margin-bottom: 0.25rem;
    }

    .checkout-section-label .csl-icon {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: #fff;
        flex-shrink: 0;
    }

    .csl-icon.csl-ship  { background: linear-gradient(135deg, #0f766e, #0d9488); }
    .csl-icon.csl-pay   { background: linear-gradient(135deg, #6d28d9, #8b5cf6); }
    .csl-icon.csl-sum   { background: linear-gradient(135deg, #1d4ed8, #3b82f6); }

    /* Payment summary improvements */
    .payment-summary-card {
        border: 1px solid rgba(15, 98, 254, 0.18);
        border-radius: 0.7rem;
        background: linear-gradient(150deg, #eef4ff 0%, #f8fbff 100%);
        padding: 0.38rem 0.5rem 0.32rem;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .payment-summary-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 2.5px;
        border-radius: 0 0 0.9rem 0.9rem;
    }

    .payment-summary-card.summary-invoice::after { background: linear-gradient(90deg, #0f62fe, #60a5fa); }
    .payment-summary-card.summary-paid::after    { background: linear-gradient(90deg, #0f766e, #14b8a6); }
    .payment-summary-card.summary-due::after     { background: linear-gradient(90deg, #b45309, #fb923c); }

    .payment-summary-card.summary-paid {
        border-color: rgba(15, 118, 110, 0.22);
        background: linear-gradient(150deg, #e6fffb 0%, #f8fffd 100%);
    }

    .payment-summary-card.summary-due {
        border-color: rgba(180, 83, 9, 0.24);
        background: linear-gradient(150deg, #fff7ed 0%, #fffbf5 100%);
    }

    .payment-summary-label {
        display: block;
        font-size: 0.57rem;
        color: var(--pos-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        margin-bottom: 0.28rem;
    }

    .payment-summary-value {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--pos-ink);
        line-height: 1.1;
    }

    .payment-summary-card.summary-invoice .payment-summary-value { color: #1d4ed8; }
    .payment-summary-card.summary-paid    .payment-summary-value { color: #0f766e; }
    .payment-summary-card.summary-due     .payment-summary-value { color: #b45309; }

    .payment-summary-card-icon {
        font-size: 0.75rem;
        margin-bottom: 0.12rem;
        opacity: 0.75;
    }

    .summary-invoice .payment-summary-card-icon { color: #2563eb; }
    .summary-paid    .payment-summary-card-icon { color: #0f766e; }
    .summary-due     .payment-summary-card-icon { color: #b45309; }

    /* Complete Sale button */
    .checkout-panel .sale-action-btn[data-sale-type="Sale"] {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 60%, #14b8a6 100%);
        border: none;
        color: #fff;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        padding: 0.42rem 1rem !important;
        border-radius: 0.6rem;
        box-shadow: 0 3px 12px rgba(15, 118, 110, 0.35);
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .checkout-panel .sale-action-btn[data-sale-type="Sale"]:hover {
        background: linear-gradient(135deg, #115e59 0%, #0f766e 60%, #0d9488 100%);
        box-shadow: 0 6px 20px rgba(15, 118, 110, 0.48);
        transform: translateY(-1px);
    }

    .checkout-panel .sale-action-btn[data-sale-type="Sale"] .btn-check-icon {
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 50%;
        background: rgba(255,255,255,0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        flex-shrink: 0;
    }

    /* Secondary action buttons */
    .checkout-panel .sale-action-btn:not([data-sale-type="Sale"]) {
        font-size: 0.63rem;
        padding: 0.28rem 0.35rem !important;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 600;
        transition: all 0.15s ease-in-out;
    }

    .checkout-panel .sale-action-btn:not([data-sale-type="Sale"]):hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }

    /* Dark mode additions */
    html[data-bs-theme="dark"] .sale-panel-header {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.97) 0%, rgba(18, 28, 48, 0.97) 100%);
    }

    html[data-bs-theme="dark"] .checkout-section-label {
        color: #94a3b8;
    }

    html[data-bs-theme="dark"] .payment-summary-card.summary-invoice .payment-summary-value { color: #93c5fd; }
    html[data-bs-theme="dark"] .payment-summary-card.summary-paid    .payment-summary-value { color: #5eead4; }
    html[data-bs-theme="dark"] .payment-summary-card.summary-due     .payment-summary-value { color: #fed7aa; }

    /* ── Compact checkout summary bar ── */
    .co-summary-bar {
        display: flex;
        align-items: stretch;
        gap: 0.35rem;
    }

    .co-chip {
        flex: 1;
        display: flex;
        flex-direction: column;
        border-radius: 0.55rem;
        padding: 0.22rem 0.42rem;
        border: 1px solid transparent;
        min-width: 0;
    }

    .co-chip-invoice {
        background: linear-gradient(135deg, #eef4ff, #f8fbff);
        border-color: rgba(15, 98, 254, 0.18);
    }

    .co-chip-paid {
        background: linear-gradient(135deg, #e6fffb, #f8fffd);
        border-color: rgba(15, 118, 110, 0.2);
    }

    .co-chip-due {
        background: linear-gradient(135deg, #fff7ed, #fffbf5);
        border-color: rgba(180, 83, 9, 0.2);
    }

    .co-chip-label {
        font-size: 0.52rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--pos-muted);
        margin-bottom: 0.04rem;
    }

    .co-chip-value {
        font-size: 0.76rem;
        font-weight: 800;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .co-chip-invoice .co-chip-value { color: #1d4ed8; }
    .co-chip-paid    .co-chip-value { color: #0f766e; }
    .co-chip-due     .co-chip-value { color: #b45309; }

    .checkout-inline-grid .form-label {
        font-size: 0.54rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--pos-muted);
        margin-bottom: 0.08rem;
    }

    .co-setup-btn {
        width: 100%;
        min-height: calc(1.2em + 0.24rem + 2px);
        border-radius: 0.55rem;
        border: 1px solid #d9e4ef;
        background: #f8fbff;
        color: #5f7185;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-size: 0.62rem;
        font-weight: 700;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
        padding: 0.16rem 0.5rem;
    }

    .co-setup-btn:hover {
        background: #e8f1ff;
        color: var(--pos-accent);
        border-color: #b8d0f0;
    }

    .payment-inline-box {
        border: 1px solid rgba(109, 40, 217, 0.14);
        border-radius: 0.65rem;
        background: linear-gradient(135deg, #f8f6ff 0%, #ffffff 100%);
        padding: 0.28rem 0.4rem;
    }

    .payment-inline-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.16rem;
    }

    .payment-inline-title {
        font-size: 0.58rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--pos-muted);
    }

    .payment-inline-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.28rem;
        margin-top: 0.22rem;
    }

    .payment-inline-actions .ghost-action-btn {
        font-size: 0.62rem;
        padding: 0.18rem 0.42rem;
    }

    html[data-bs-theme="dark"] .co-chip-invoice { background: rgba(59,130,246,0.1); border-color: rgba(96,165,250,0.2); }
    html[data-bs-theme="dark"] .co-chip-paid    { background: rgba(20,184,166,0.1); border-color: rgba(94,234,212,0.2); }
    html[data-bs-theme="dark"] .co-chip-due     { background: rgba(180,83,9,0.12);  border-color: rgba(251,146,60,0.2); }
    html[data-bs-theme="dark"] .co-chip-label   { color: #94a3b8; }
    html[data-bs-theme="dark"] .co-chip-invoice .co-chip-value { color: #93c5fd; }
    html[data-bs-theme="dark"] .co-chip-paid    .co-chip-value { color: #5eead4; }
    html[data-bs-theme="dark"] .co-chip-due     .co-chip-value { color: #fed7aa; }
    html[data-bs-theme="dark"] .payment-inline-box { background: rgba(109, 40, 217, 0.08); border-color: rgba(139, 92, 246, 0.18); }
    html[data-bs-theme="dark"] .co-setup-btn { background: #172033; border-color: rgba(71,85,105,0.7); color: #94a3b8; }

    /* ── Card-based cart items ── */
    .cart-card-list {
        display: flex;
        flex-direction: column;
        gap: 0.22rem;
    }

    /* ── Cart table view ─────────────────────── */
    .cart-table-view {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
        table-layout: fixed;
        background: #ffffff;
    }

    .cart-table-view thead {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .cart-table-view thead th {
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        padding: 0.36rem 0.5rem;
        font-size: 0.62rem;
        font-weight: 800;
        color: #42526b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
        border-bottom: 1px solid #d6e2ef;
        text-align: left;
    }

    .cart-table-view thead th.th-right { text-align: right; }
    .cart-table-view thead th.th-center { text-align: center; }

    .ct-col-num    { width: 6%; }
    .ct-col-product{ width: 26%; min-width: 0; }
    .ct-col-price  { width: 14%; }
    .ct-col-disc   { width: 16%; }
    .ct-col-qty    { width: 18%; }
    .ct-col-total  { width: 15%; }
    .ct-col-action { width: 5%; }

    .cart-item {
        display: table-row !important;
        background: #ffffff;
        transition: background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .cart-item:nth-child(even) { background: #fbfdff; }

    .cart-item:hover {
        background-color: #f5faff !important;
    }

    .cart-item td {
        padding: 0.34rem 0.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9f0f7;
    }

    .cart-item:last-child td { border-bottom: none; }

    .ct-product-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #102a43;
        line-height: 1.18;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        overflow-wrap: break-word;
        word-break: normal;
        min-width: 0;
    }

    .ci-img-col {
        flex-shrink: 0;
        width: 58px;
        position: relative;
    }

    .ci-img {
        width: 58px;
        height: 58px;
        object-fit: cover;
        border-radius: 0.55rem;
        display: block;
    }

    .ci-img-placeholder {
        width: 58px;
        height: 58px;
        border-radius: 0.55rem;
        background: linear-gradient(135deg, #f0f5fb 0%, #e8f0f8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a0b3c5;
        font-size: 1.15rem;
    }

    .ci-off-badge {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%);
        color: #fff;
        font-size: 0.55rem;
        font-weight: 800;
        padding: 0.18rem 0.38rem;
        border-radius: 999px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3);
        margin-top: 0.1rem;
    }

    .ci-details-col,
    .ci-body {
        flex: 1;
        min-width: 0;
    }

    .ci-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #102a43;
        line-height: 1.15;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-width: 0;
    }

    .ci-meta-line {
        display: flex;
        align-items: center;
        gap: 0.24rem;
        min-width: 0;
        margin-top: 0.06rem;
        margin-bottom: 0;
        flex-wrap: wrap;
    }

    .ci-variation,
    .ci-brand {
        font-size: 0.72rem;
        color: #486581;
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        overflow-wrap: anywhere;
        margin: 0;
        flex-shrink: 1;
        min-width: 0;
    }

    .ci-meta-sep {
        font-size: 0.72rem;
        color: #486581;
        white-space: nowrap;
        opacity: 0.7;
        flex-shrink: 0;
    }

    .ci-sku {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #eef4fb;
        color: #334e68;
        padding: 0.08rem 0.36rem;
        font-size: 0.68rem;
        font-weight: 800;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .ci-price-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: 0.12rem;
    }

    .ci-price-original {
        font-size: 0.7rem;
        color: var(--pos-muted);
        text-decoration: line-through;
    }

    .ci-price-final {
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--pos-teal);
    }

    .ci-discount-label {
        font-size: 0.57rem;
        font-weight: 700;
        color: var(--pos-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .ci-num {
        flex-shrink: 0;
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 0.4rem;
        background: #eaf2ff;
        color: var(--pos-accent-dark);
        font-size: 0.8rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .ci-body {
        flex: 1;
        min-width: 0;
    }

    .ci-price-group {
        display: flex;
        align-items: center;
        gap: 0.2rem;
        flex-shrink: 0;
    }

    .ci-price-original {
        font-size: 0.62rem;
        color: var(--pos-muted);
        text-decoration: line-through;
    }

    .ci-price-final {
        font-size: 0.88rem;
        font-weight: 800;
        color: #0f766e;
        line-height: 1.18;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .ci-disc-row {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff8f0;
        border: 1px solid #f3bf8a;
        border-radius: 0.5rem;
        padding: 0.12rem 0.3rem;
        min-height: 2.05rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
    }

    .ci-disc-row .ci-discount-input {
        width: 100%;
        min-height: 1.62rem;
        border: none;
        background: transparent;
        color: #9a3412;
        font-size: 0.9rem;
        font-weight: 800;
        padding: 0;
        text-align: center;
        outline: none;
    }

    .cart-item td .quantity-controls {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        width: 6.25rem;
        height: 2.05rem;
        padding: 0;
        margin: 0 auto;
        overflow: hidden;
        border-radius: 0.65rem;
        background: #ffffff;
        border: 1px solid #cfe0f3;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .cart-item td .quantity-btn {
        width: 1.75rem;
        height: 100%;
        border-radius: 0;
        background: #f8fbff;
        color: #486581;
        border: 0;
        font-size: 0.76rem;
        box-shadow: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .cart-item td .quantity-btn:first-child {
        border-right: 1px solid #dbe8f6;
    }

    .cart-item td .quantity-btn:last-child {
        border-left: 1px solid #dbe8f6;
    }

    .cart-item td .quantity-input {
        width: 2.75rem;
        font-size: 0.9rem;
        font-weight: 800;
        color: #102a43;
        height: 100%;
        text-align: center;
        flex: 0 0 2.75rem;
        border-radius: 0;
        background: #ffffff;
        box-shadow: none;
        appearance: textfield;
        -moz-appearance: textfield;
    }

    .cart-item td .quantity-input::-webkit-outer-spin-button,
    .cart-item td .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .cart-item td .ci-discount-input {
        appearance: textfield;
        -moz-appearance: textfield;
    }

    .cart-item td .ci-discount-input::-webkit-outer-spin-button,
    .cart-item td .ci-discount-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .ci-line-total {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.06rem;
        font-size: 0.86rem;
        font-weight: 800;
        color: #102a43;
        line-height: 1.08;
        font-variant-numeric: tabular-nums;
    }

    .ci-line-currency {
        display: block;
        font-size: 0.78rem;
        color: #183b56;
    }

    .ci-line-amount {
        display: block;
        white-space: nowrap;
        color: #102a43;
    }

    .checkout-inline-grid .form-label,
    .payment-inline-title,
    .payment-row label {
        font-size: 0.58rem;
        color: #334e68;
        font-weight: 800;
    }

    .checkout-panel .form-control,
    .checkout-panel .form-select,
    .payment-amount-input,
    .payment-method-select {
        color: #102a43;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .ghost-action-btn {
        color: #334e68;
        font-weight: 700;
    }

    /* dark mode */
    html[data-bs-theme="dark"] .cart-table-view thead th {
        background: #0f1e32;
        color: #7da8cc;
        border-bottom-color: #243854;
    }

    html[data-bs-theme="dark"] .cart-item {
        background: #0d1b2a;
    }

    html[data-bs-theme="dark"] .cart-item:nth-child(even) {
        background: #0f2035;
    }

    html[data-bs-theme="dark"] .cart-item:hover {
        background-color: #162b44 !important;
    }

    html[data-bs-theme="dark"] .cart-item td {
        border-bottom-color: #1e3650;
    }

    html[data-bs-theme="dark"] .ci-img-placeholder {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #475569;
    }

    html[data-bs-theme="dark"] .ci-num {
        background: rgba(59, 130, 246, 0.16);
        color: #93c5fd;
    }
    html[data-bs-theme="dark"] .ci-disc-row {
        background: rgba(180, 83, 9, 0.14);
        border-color: rgba(251, 146, 60, 0.35);
    }
    html[data-bs-theme="dark"] .ci-disc-row .ci-discount-label,
    html[data-bs-theme="dark"] .ci-disc-row .ci-discount-input { color: #fed7aa; }
    html[data-bs-theme="dark"] .ci-line-total { color: #f8fafc; }
    html[data-bs-theme="dark"] .ci-name { color: #f8fafc; }
    html[data-bs-theme="dark"] .ci-variation { color: #94a3b8; }
    html[data-bs-theme="dark"] .ci-brand,
    html[data-bs-theme="dark"] .ci-meta-sep { color: #64748b; }
    html[data-bs-theme="dark"] .ci-sku { background: rgba(148,163,184,0.15); color: #cbd5e1; }
    html[data-bs-theme="dark"] .ci-price-original { color: #64748b; }
    html[data-bs-theme="dark"] .ci-price-final { color: #5eead4; }
    html[data-bs-theme="dark"] .ci-line-total { color: #f8fafc; }
    html[data-bs-theme="dark"] .ci-discount-input {
        background: rgba(180, 83, 9, 0.14);
        border-color: rgba(251, 146, 60, 0.35);
        color: #fed7aa;
    }

    /* ── Box Picker ─────────────────────────────────────────────── */
    .bp-list { display: flex; flex-direction: column; gap: 6px; max-height: 340px; overflow-y: auto; padding-right: 2px; }
    .bp-item {
        display: flex; align-items: center; gap: 10px;
        border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px 13px;
        cursor: pointer; transition: border-color .15s, background .15s;
        background: #fff; user-select: none;
    }
    .bp-item input[type="radio"] { display: none; }
    .bp-item:hover { border-color: #a5b4fc; background: #f8faff; }
    .bp-item.bp-active { border-color: var(--pos-accent); background: #eef4ff; }
    .bp-body { flex: 1; min-width: 0; }
    .bp-code { font-weight: 700; font-size: .85rem; color: #17324d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bp-sub { font-size: .7rem; color: #6b7c93; margin-top: 2px; }
    .bp-right { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0; }
    .bp-units { font-weight: 700; font-size: .9rem; color: #166534; }
    .bp-units.bp-low { color: #b91c1c; }
    .bp-badge { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 2px 6px; border-radius: 4px; }
    /* Inline box picker inside variant modal */
    .vbs-block { border-top: 1px solid #e9eef5; padding-top: 12px; margin-top: 4px; }
    .vbs-label { font-size: .72rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .vbs-label small { font-weight: 400; text-transform: none; letter-spacing: 0; color: #94a3b8; font-size: .72rem; }
    .vbs-loading { font-size: .78rem; color: #6b7c93; padding: 6px 0; display: flex; align-items: center; gap: 6px; }
</style>
@section('main.content')
{{-- <div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>POS</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">POS</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div> --}}

<div class="page-content pos-workspace" id="posWorkspace">
    <div class="pos-hero">
        <div class="row g-3 align-items-center">
            <div class="col-lg-6">
                <div class="pos-hero-title">Counter Sales Workspace</div>
                <p class="pos-hero-subtitle">Manage customer billing, variants, fast checkout, and instant invoice printing from one screen.</p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <div class="toolbar-card">
                        <span class="toolbar-label">Visible Products</span>
                        <span class="toolbar-value" id="productCountLabel">{{ count($products) }} Loaded</span>
                    </div>
                    <button type="button" class="hero-action-btn" id="toggleFocusModeBtn">Focus Mode</button>
                    <button type="button" class="hero-action-btn" id="toggleFullscreenBtn">Full Screen</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row h-100 pos-main-grid">
        <!-- Cart Section -->
        <div class="col-lg-6 order-2 order-lg-2">
            <div class="card cart-section h-100 d-flex flex-column">
                <div class="sale-panel-header">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2 min-width-0">
                            <h1 class="sale-panel-title">
                                <span class="sale-panel-title-icon"><i class="fas fa-cash-register"></i></span>
                                Process Sale
                            </h1>
                            <p class="sale-panel-subtitle d-none d-xl-block mb-0">Customer, cart, and checkout</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <button type="button" class="customer-picker-btn customer-picker-header-btn" data-bs-toggle="modal" data-bs-target="#customerSearchModal">
                                <i class="fas fa-user"></i>
                                <span>Select customer</span>
                            </button>
                            <span class="section-meta-chip d-none d-sm-inline-flex">Live cart</span>
                        </div>
                    </div>
                </div>
                <div id="selectedCustomer" class="selected-customer-slot"></div>

                <!-- Customer Note + Prescription Panel -->
                <div id="customerExtraPanel" class="d-none" style="padding: 0 0.6rem 0.4rem;">
                    <div style="background:linear-gradient(135deg,#f0f7ff 0%,#e8f4fd 100%); border:1px solid #c8e0f5; border-radius:0.75rem; overflow:hidden;">

                        <!-- Customer Note -->
                        <div style="padding:0.6rem 0.75rem 0.5rem;">
                            <div style="font-size:0.68rem; font-weight:700; color:#3b6fa0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px;">
                                <i class="fas fa-user-pen me-1" style="font-size:0.65rem;"></i>Customer Note
                            </div>
                            <textarea id="customerNoteField" rows="2"
                                placeholder="Permanent note for this customer (e.g. diabetic, prefers strips)..."
                                style="width:100%; resize:none; font-size:0.76rem; border:1px solid #b8d4ee; border-radius:0.45rem; padding:0.35rem 0.5rem; background:#fff; color:#1e3a5f; outline:none; line-height:1.5; font-family:inherit; margin-bottom:5px;"></textarea>
                            <button type="button" id="saveCustomerNoteBtn"
                                style="display:none; width:100%; background:linear-gradient(135deg,#1a5fa8,#2d7ec4); color:#fff; border:none; border-radius:0.45rem; padding:0.38rem; font-size:0.78rem; font-weight:600; cursor:pointer; letter-spacing:.01em;">
                                <i class="fas fa-save me-1"></i> Save Customer Note
                            </button>
                        </div>

                        <!-- Prescription Row -->
                        <div style="border-top:1px dashed #c0d8f0; padding:0.45rem 0.75rem; display:flex; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:7px;">
                                <i class="fas fa-file-medical" style="font-size:0.8rem; color:#2d7ec4;"></i>
                                <span style="font-size:0.73rem; font-weight:600; color:#1e3a5f;">Prescriptions</span>
                                <span style="font-size:0.68rem; color:#94aec5;">(<span id="rxCount">0</span> saved)</span>
                            </div>
                            <button type="button" id="managePrescriptionsBtn"
                                style="background:#2d7ec4; color:#fff; border:none; border-radius:0.45rem; padding:0.28rem 0.7rem; font-size:0.72rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <i class="fas fa-eye" style="font-size:0.65rem;"></i> View / Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-grow-1 overflow-auto" id="cart-container">
                    <div class="p-3">                        
                        <div class="cart-header-bar">
                            <div>
                                <div class="cart-title">Active cart</div>
                                {{-- <div class="cart-subtitle">Review items, quantities, and totals before billing.</div> --}}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                {{-- <button class="btn btn-outline-warning btn-sm" title="Hold Transaction (F10)">
                                    ⏸️ Hold
                                </button> --}}
                                <span class="cart-count-pill" id="total-cart-items">{{ \Cart::getContent()->count() }}</span>
                                <button class="ghost-action-btn" id="clearCartBtn" type="button">Clear All</button>
                            </div>
                        </div>

                        <div class="cart-scroll">
                            @if (\Cart::isEmpty())
                            <div id="cartList">
                                <div class="cart-empty-state">Your cart is empty.</div>
                            </div>
                            @else
                            <div id="cartList">
                                <div class="table-responsive">
                                    <table class="cart-table-view">
                                        <thead>
                                            <tr>
                                                <th class="ct-col-num th-center">#</th>
                                                <th class="ct-col-product">Product</th>
                                                <th class="ct-col-price th-right">Unit Price</th>
                                                <th class="ct-col-disc th-center">Discount</th>
                                                <th class="ct-col-qty th-center">Qty</th>
                                                <th class="ct-col-total th-right">Total</th>
                                                <th class="ct-col-action"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (\Cart::getContent() as $cartItem)
                                            @php
                                                $unitDiscount = (float) ($cartItem->attributes->discount_amount ?? $cartItem->attributes->discount ?? 0);
                                                $lineTotal = max(0, ((float) $cartItem->price * (int) $cartItem->quantity) - $unitDiscount);
                                                $discountPercent = (int) ($cartItem->attributes->discount_percent ?? 0);
                                                $brandName = $cartItem->attributes->brand_name ?? '';
                                                $variationSummary = $cartItem->attributes->variation_summary ?? '';
                                                $saleUnitLabel = $cartItem->attributes->sale_unit_label ?? '';
                                            @endphp
                                            <tr class="cart-item" data-id="{{ $cartItem->id }}">
                                                <td class="ct-col-num" style="text-align:center">
                                                    <span class="ci-num">{{ $loop->iteration }}</span>
                                                </td>
                                                <td class="ct-col-product">
                                                    <div class="ct-product-name" title="{{ $cartItem->name }}">{{ $cartItem->name }}</div>
                                                    <div class="ci-meta-line">
                                                        @if (!empty($variationSummary))
                                                            <span class="ci-variation">{{ $variationSummary }}</span>
                                                            <span class="ci-meta-sep">|</span>
                                                    @endif
                                                    @if (!empty($brandName))
                                                        <span class="ci-brand">{{ $brandName }}</span>
                                                        <span class="ci-meta-sep">|</span>
                                                    @endif
                                                    <span class="ci-sku">{{ $cartItem->attributes->product_code ?? 'N/A' }}</span>
                                                    @if (!empty($saleUnitLabel))
                                                        <span class="ci-meta-sep">|</span>
                                                        <span class="ci-sku">{{ $saleUnitLabel }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="ct-col-price" style="text-align:right">
                                                @if ($unitDiscount > 0)
                                                    <div class="ci-price-original">{{ Currency::format($cartItem->price) }}</div>
                                                @endif
                                                <div class="ci-price-final">{{ Currency::format(max(0, (float)$cartItem->price - $unitDiscount)) }}</div>
                                            </td>
                                            <td class="ct-col-disc" style="text-align:center">
                                                <div class="ci-disc-row">
                                                    <input type="number" class="ci-discount-input"
                                                        id="discount_{{ $cartItem->id }}"
                                                        value="{{ number_format($unitDiscount, 2, '.', '') }}"
                                                        data-previous="{{ number_format($unitDiscount, 2, '.', '') }}"
                                                        min="0"
                                                        max="{{ number_format((float) $cartItem->price * (int) $cartItem->quantity, 2, '.', '') }}"
                                                        step="0.01"
                                                        onchange="updateItemDiscount('{{ $cartItem->id }}')">
                                                </div>
                                            </td>
                                            <td class="ct-col-qty" style="text-align:center">
                                                <div class="quantity-controls">
                                                    <button class="quantity-btn" onclick="updateQuantity('{{ $cartItem->id }}', -1)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="quantity-input" id="quantity_{{ $cartItem->id }}"
                                                        onchange="updateQuantity('{{ $cartItem->id }}', 0)"
                                                        value="{{ $cartItem->quantity }}" min="1">
                                                    <button class="quantity-btn" onclick="updateQuantity('{{ $cartItem->id }}', 1)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="ct-col-total" style="text-align:right">
                                                <span class="cart-price-total ci-line-total" data-sku="{{ $cartItem->id }}">
                                                    <span class="ci-line-currency">{{ Currency::code() }}</span>
                                                    <span class="ci-line-amount">{{ number_format($lineTotal, 2) }}</span>
                                                </span>
                                                @if ($discountPercent > 0)
                                                    <div><span class="ci-off-badge">{{ $discountPercent }}% OFF</span></div>
                                                @endif
                                            </td>
                                            <td class="ct-col-action" style="text-align:center">
                                                <button class="cart-remove-btn" title="Remove item" onclick="removeCartItem('{{ $cartItem->id }}')" type="button">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>                               

                <!-- Checkout Panel (compact) -->
                <div class="border-top card p-3 mt-auto checkout-panel">
                    <form id="checkout-form">
                    <input type="hidden" name="customer_id" id="customer_id">
                    <input type="hidden" id="sub_total" name="sub_total" value="{{ \Cart::getSubTotal() }}">
                    <input type="hidden" name="payment_method" id="payment_method" value="Cash">
                    <input type="hidden" id="grand_total_amount" value="{{ number_format((float) \Cart::getTotal(), 2, '.', '') }}">
                    <input type="hidden" id="base-subtotal" value="{{ \Cart::getSubTotal() }}">
                    <input type="hidden" id="sale_type" value="Sale">

                    <div class="row g-2 checkout-inline-grid mb-2">
                        <div class="col-4">
                            <label class="form-label" for="order_discount">Discount</label>
                            <input type="number" class="form-control form-control-sm" id="order_discount" name="order_discount" placeholder="0.00" min="0" step="0.01">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="vat_percent">VAT %</label>
                            <input type="number" class="form-control form-control-sm" id="vat_percent" name="vat_percent" placeholder="0" min="0" max="100" step="0.01" value="{{ $vatRule ? number_format((float) $vatRule->rate_percent, 2, '.', '') : '0' }}" readonly>
                            <small class="text-muted d-block mt-1">
                                {{ $vatRule ? $vatRule->name . ($vatRule->is_inclusive ? ' (Inclusive)' : ' (Exclusive)') : 'No active VAT rule' }}
                            </small>
                        </div>
                        <div class="col-4 d-flex align-items-end">
                            <button type="button" class="co-setup-btn" data-bs-toggle="modal" data-bs-target="#checkoutSetupModal">
                                <i class="fas fa-shipping-fast"></i>
                                <span>Shipping</span>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="vat_amount" name="vat_amount" value="0">
                    <input type="hidden" id="vat_is_inclusive" name="vat_is_inclusive" value="{{ $vatRule && $vatRule->is_inclusive ? 1 : 0 }}">
                    <input type="hidden" id="redeemed_points" name="redeemed_points" value="0">

                    {{-- Loyalty Points Panel (shown only when customer is selected and loyalty is enabled) --}}
                    <div id="loyaltyPanel" class="d-none mb-2" style="border:1px solid rgba(109,40,217,0.18);border-radius:0.65rem;background:linear-gradient(135deg,#f5f3ff 0%,#ffffff 100%);padding:0.4rem 0.5rem;">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <span style="font-size:0.58rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--pos-muted);">
                                <i class="fas fa-star text-warning"></i> Loyalty Points
                            </span>
                            <span id="loyaltyMemberBadge" class="badge bg-success d-none" style="font-size:0.6rem;">Member</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span style="font-size:0.75rem;color:#6d28d9;">Available: <strong id="loyaltyPointsAvailable">0</strong> pts</span>
                            <span style="font-size:0.75rem;color:#6d28d9;">= <strong id="loyaltyMaxDiscount">৳0.00</strong></span>
                            <div class="d-flex align-items-center gap-1 ms-auto">
                                <input type="number" id="redeemPointsInput" min="0" step="1" value="0"
                                    class="form-control form-control-sm"
                                    style="width:80px;font-size:0.8rem;font-weight:700;"
                                    placeholder="0 pts">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="applyRedeemBtn"
                                    style="font-size:0.7rem;padding:0.2rem 0.5rem;">
                                    Apply
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger d-none" id="clearRedeemBtn"
                                    style="font-size:0.7rem;padding:0.2rem 0.5rem;">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div id="loyaltyDiscountRow" class="d-none mt-1" style="font-size:0.72rem;color:#0f766e;">
                            <i class="fas fa-check-circle"></i> Point discount: <strong id="loyaltyDiscountDisplay">৳0.00</strong>
                        </div>
                    </div>

                    <div class="payment-inline-box mb-2">
                        <div class="payment-inline-header">
                            <span class="payment-inline-title">Payment Method</span>
                        </div>
                        <div class="d-flex flex-column gap-2" id="paymentRows"></div>
                        <div class="payment-inline-actions">
                            <button type="button" class="ghost-action-btn" id="addPaymentRowBtn">Add line</button>
                            <button type="button" class="ghost-action-btn" id="fillRemainingPaymentBtn">Pay due</button>
                            <button type="button" class="ghost-action-btn" id="resetPaymentsBtn">Reset</button>
                        </div>
                    </div>

                    {{-- Sale Note --}}
                    <div class="mb-2 mt-1">
                        <button type="button" id="toggleSaleNoteBtn"
                            style="width:100%; background:#f7f9fc; border:1px dashed #c8d8e8; border-radius:0.45rem; padding:0.28rem 0.6rem; font-size:0.74rem; font-weight:600; color:#6b7c93; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background .15s;">
                            <i class="fas fa-pen-to-square" style="font-size:0.68rem;"></i>
                            <span id="saleNoteToggleLabel">Add Sale Note</span>
                        </button>
                        <div id="saleNoteBox" style="display:none; margin-top:5px;">
                            <textarea
                                id="customer_note"
                                name="customer_note"
                                rows="2"
                                placeholder="Write a note for this sale..."
                                style="width:100%; resize:none; font-size:0.76rem; border:1px solid #c8d8e8; border-radius:0.45rem; padding:0.35rem 0.5rem; color:#2d3748; outline:none; line-height:1.5; font-family:inherit; background:#fafcff;"></textarea>
                        </div>
                    </div>

                    {{-- Compact summary row --}}
                    <div class="co-summary-bar mb-2">
                        <div class="co-chip co-chip-invoice">
                            <span class="co-chip-label"><i class="fas fa-file-invoice"></i> Total</span>
                            <span class="co-chip-value" id="grand-total">{{ Currency::format(\Cart::getTotal()) }}</span>
                        </div>
                        <div class="co-chip co-chip-paid">
                            <span class="co-chip-label"><i class="fas fa-check-circle"></i> Paid</span>
                            <span class="co-chip-value" id="payment-total-display">{{ Currency::format(\Cart::getTotal()) }}</span>
                        </div>
                        <div class="co-chip co-chip-due">
                            <span class="co-chip-label"><i class="fas fa-clock"></i> Due</span>
                            <span class="co-chip-value" id="payment-due-display">{{ Currency::format(0) }}</span>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="row g-2">
                        <div class="col-12">
                            <button class="btn btn-primary w-100 sale-action-btn checkout-primary-action" type="submit" data-sale-type="Sale">
                                <span class="btn-check-icon"><i class="fas fa-check"></i></span>
                                Complete Sale
                            </button>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="btn btn-outline-warning w-100 sale-action-btn" type="button" data-sale-type="Credit Sale">
                                <i class="fas fa-hand-holding-usd"></i>
                                Credit Sale
                            </button>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="btn btn-outline-info w-100 sale-action-btn" type="button" data-sale-type="Quotation">
                                <i class="fas fa-file-alt"></i>
                                Quotation
                            </button>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="btn btn-outline-secondary w-100 sale-action-btn" type="button" data-sale-type="Draft">
                                <i class="fas fa-save"></i>
                                Draft
                            </button>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="btn btn-outline-dark w-100 sale-action-btn" type="button" data-sale-type="Suspend">
                                <i class="fas fa-pause-circle"></i>
                                Suspend
                            </button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="col-lg-6 order-1 order-lg-1">
            <div class="h-100 d-flex flex-column ">
                <!-- Products Grid -->
                <div class="flex-grow-1 card products-panel">
                    <div class="products-panel-header">
                        <div class="filter-bar" style="border-radius:10px;padding:10px 12px">
                            {{-- Row 1: Search + Count --}}
                            <div class="filter-row" style="gap:6px">
                                <div class="filter-search" style="max-width:none;flex:1;min-width:120px">
                                    <i class="bi bi-search"></i>
                                    <input type="text" id="skuSearch" placeholder="Search or scan barcode…" autocomplete="off">
                                    <button class="fs-clear" id="skuSearchClear"><i class="bi bi-x-circle-fill"></i></button>
                                </div>
                                <span class="section-meta-chip flex-shrink-0" id="productCountBadge" style="height:40px;display:flex;align-items:center;padding:0 10px;border-radius:8px;white-space:nowrap">{{ count($products) }} items</span>
                            </div>
                            {{-- Row 2: Category fdd + Brand select + Reset + Products --}}
                            <div class="filter-row mt-2" style="gap:6px">

                                {{-- Category multi-select dropdown --}}
                                <div class="fdd" id="posDdCat" style="flex:1;min-width:0">
                                    <button class="fdd-btn" id="posDdCatBtn" style="width:100%;justify-content:space-between">
                                        <span style="display:flex;align-items:center;gap:7px;overflow:hidden">
                                            <i class="bi bi-tag" style="font-size:13px;flex-shrink:0"></i>
                                            <span id="posCatLabel" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Category</span>
                                        </span>
                                        <span style="display:flex;align-items:center;gap:5px;flex-shrink:0">
                                            <span class="fdd-count d-none" id="posCatCount">0</span>
                                            <i class="bi bi-chevron-down fdd-chevron"></i>
                                        </span>
                                    </button>
                                    <div class="fdd-panel" id="posDdCatPanel" style="min-width:260px;max-width:300px">
                                        <div class="fdd-panel-head">
                                            <span class="fdd-panel-title">Select Categories</span>
                                            <button class="fdd-panel-clear" id="posCatClear">Clear</button>
                                        </div>
                                        <div class="fdd-panel-search">
                                            <i class="bi bi-search"></i>
                                            <input type="text" id="posCatSearch" placeholder="Search categories…">
                                        </div>
                                        <div class="fdd-options" id="posCatOptions">
                                            @foreach ($categories as $cat)
                                            <div class="fdd-option" data-value="{{ $cat->id }}" data-text="{{ $cat->name }}" data-search="{{ strtolower($cat->name) }}">
                                                <span class="fdd-checkbox"><i class="bi bi-check2"></i></span>
                                                <span class="fdd-opt-text">{{ $cat->name }}</span>
                                            </div>
                                            @endforeach
                                            <div class="fdd-no-opts d-none">No categories found</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Brand single-select --}}
                                <select id="brandFilter" class="filter-select no-select2" style="flex:1;min-width:0">
                                    <option value="">All Brands</option>
                                    @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>

                                <button class="btn-reset" id="posFilterReset" style="height:40px;padding:0 10px;font-size:12px;gap:4px" title="Reset filters">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <a href="{{route('product.show')}}" class="btn-new" style="height:40px;padding:0 12px;font-size:13px;border-radius:9px;gap:6px;box-shadow:none;white-space:nowrap">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Products</span>
                                </a>
                            </div>

                            {{-- Active chips --}}
                            <div class="active-bar d-none" id="posActiveBar" style="padding-top:8px;margin-top:8px">
                                <span class="active-lbl">Active:</span>
                                <div id="posActiveChips" style="display:flex;flex-wrap:wrap;gap:4px"></div>
                            </div>
                        </div>
                    </div>
                    <div class="products-scroll p-3">
                        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2" id="skuList">                          
                            @foreach ($products as $product)
                            <div class="col"
                                data-search="{{ strtolower($product->name . ' ' . ($product->card_badge ?? '')) }}"
                                onclick="openProductSelection({{ $product->id }}, {{ $product->action_sku_id ?? 'null' }}, {{ $product->is_variant_product ? 'true' : 'false' }}, {{ $product->is_medicine ? 'true' : 'false' }})">
                                <div class="product-card">
                                    <div class="product-thumb">
                                        @if (!empty($product->thumbnail_url))
                                        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}">
                                        @else
                                        <span class="product-thumb-fallback">No Image</span>
                                        @endif
                                    </div>
                                    <div class="product-card-body">
                                        <h3 class="product-card-title line-clamp-2">{{ $product->name }}</h3>
                                        @php $pharmaParts = array_filter([$product->dosage_form, $product->strength, $product->coating_type]); @endphp
                                        @if($pharmaParts)
                                        <div class="pos-pharma-sub">{{ implode(' · ', $pharmaParts) }}</div>
                                        @endif
                                        <hr class="pc-divider">
                                        <div class="product-card-meta">
                                            <div class="product-card-code-row">
                                                <span class="rx-badge">{{ $product->card_badge }}</span>
                                            </div>
                                            <p class="product-card-category mb-0">{{ $product->category_name ?? 'Uncategorized' }} | {{ $product->brand_name ?? 'No Brand' }}</p>
                                        </div>
                                        <div class="product-card-stats">
                                            <p class="small fw-semibold mb-0">{{ $product->price_label }}</p>
                                            <span class="product-card-stock">Stock: {{ $product->available_stock }}</span>
                                        </div>
                                        <div class="product-card-footer">
                                            <span class="product-action-chip">{{ $product->is_variant_product ? 'Choose Options' : 'Tap To Add' }}</span>
                                            <span class="product-card-sku-count">{{ $product->sku_count }} SKU</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="py-3 text-center d-none" id="productLoadingIndicator">
                            <span class="text-muted small">Loading products...</span>
                        </div>
                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 pt-3" id="productPagerBar">
                            <span class="text-muted small" id="productPageSummary">Showing {{ $products->count() > 0 ? '1' : '0' }}-{{ $products->count() }} of {{ $products->count() }}</span>
                            <div id="loadMoreProductsWrap" class="{{ $products->count() < 20 ? 'd-none' : '' }}">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="loadMoreProductsBtn">Load More</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="checkoutSetupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-scrollable modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="fas fa-shipping-fast me-2 text-primary"></i>Shipping & Delivery
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1" for="shipment_zone_id">Shipment Zone</label>
                        <select class="form-select form-select-sm" id="shipment_zone_id" name="shipment_zone_id">
                            <option value="">Select Zone</option>
                            @foreach ($shipmentZones as $shipmentZone)
                            <option value="{{ $shipmentZone->id }}" data-charge="{{ $shipmentZone->charge }}">
                                {{ $shipmentZone->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1" for="delivery_charge">Delivery Charge</label>
                        <input type="number" class="form-control form-control-sm" id="delivery_charge" name="delivery_charge" placeholder="0.00" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="customerSearchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-scrollable customer-modal-dialog">
        <div class="modal-content customer-modal-content">
            <div class="modal-header customer-modal-header">
                <div>
                    <h5 class="modal-title customer-modal-title">Customer & Member</h5>
                    <p class="customer-modal-subtitle">Search fast by phone number, or create a new customer with the quick form below.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="search-container customer-panel border-0">
                    <div class="customer-search-shell">
                        <i class="fas fa-user customer-search-icon"></i>
                        <input
                            type="text"
                            class="form-control search-input"
                            placeholder="Search by phone number or create new..."
                            id="customerSearch"
                            value=""
                            autocomplete="off">
                        <div class="suggestions-dropdown" id="suggestionsDropdown"></div>
                    </div>
                    <div class="customer-search-caption">
                        <span class="customer-search-caption-text">Enter a phone number to search existing customers or start a new one in seconds.</span>
                    </div>
                    <div class="customer-action-row">
                        <span class="panel-subtitle">No saved customer? Continue as walk-in.</span>
                        <button type="button" class="ghost-action-btn" id="walkInCustomerBtn">Walk-in customer</button>
                    </div>

                    <div class="create-customer-form mb-2" id="createCustomerForm">
                        <h4 class="form-title">Add New Customer</h4>
                        <div class="customer-form-subtitle">Phone number is enough to save. Everything else here is optional.</div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <div class="customer-form-section h-100">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerPhone">Phone Number</label>
                                            <input
                                                type="tel"
                                                class="form-control form-control-sm"
                                                placeholder="01XXXXXXXXX"
                                                id="customerPhone"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerName">Customer Name</label>
                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="Optional display name"
                                                id="customerName">
                                        </div>
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerEmail">Email</label>
                                            <input
                                                type="email"
                                                class="form-control form-control-sm"
                                                placeholder="Optional email"
                                                id="customerEmail">
                                        </div>
                                        @if (($loyaltySettings['enabled'] ?? false) === true)
                                        <div class="col-12">
                                            <div class="form-check rounded border px-3 py-3 bg-light-subtle">
                                                <input class="form-check-input" type="checkbox" id="customerIsMember">
                                                <label class="form-check-label fw-semibold" for="customerIsMember">
                                                    Add as loyalty member
                                                </label>
                                                <div class="small text-muted mt-1">
                                                    Mark this customer as a member directly from POS.
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="customer-form-section h-100">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerShipmentZone">Shipment Zone</label>
                                            <select class="form-select form-select-sm" id="customerShipmentZone">
                                                <option value="">Select Shipment Zone</option>
                                                @foreach ($shipmentZones as $shipmentZone)
                                                <option value="{{ $shipmentZone->id }}" data-charge="{{ $shipmentZone->charge }}">
                                                    {{ $shipmentZone->name }} ({{ Currency::format($shipmentZone->charge) }})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerBillingAddress">Billing Address</label>
                                            <textarea
                                                class="form-control form-control-sm"
                                                placeholder="Optional billing address"
                                                id="customerBillingAddress"
                                                rows="2"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="customer-form-label" for="customerShippingAddress">Shipping Address</label>
                                            <textarea
                                                class="form-control form-control-sm"
                                                placeholder="Optional shipping address"
                                                id="customerShippingAddress"
                                                rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 pt-1">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill customer-secondary-action" id="cancelBtn">
                                        Cancel
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm flex-fill customer-primary-action" id="saveBtn">
                                        Save Customer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Prescription Modal -->
<div class="modal" id="prescriptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:560px;">
        <div class="modal-content" style="border:none; border-radius:1rem; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.18);">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#1a5fa8 0%,#2d7ec4 100%); padding:1rem 1.2rem; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-file-medical" style="color:#fff; font-size:1rem;"></i>
                    </div>
                    <div>
                        <div style="color:#fff; font-weight:700; font-size:1rem; line-height:1.2;">Prescriptions</div>
                        <div id="rxModalSubtitle" style="color:rgba(255,255,255,0.75); font-size:0.72rem;"></div>
                    </div>
                </div>
                <button type="button" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.15); border:none; border-radius:50%; width:30px; height:30px; color:#fff; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">&times;</button>
            </div>

            <div class="modal-body" style="padding:0; background:#f7fafd;">

                <!-- Add Form Toggle -->
                <div style="padding:0.9rem 1.2rem 0;">
                    <button type="button" id="toggleAddRxBtn"
                        style="width:100%; background:#fff; border:2px dashed #a8cff0; border-radius:0.75rem; padding:0.6rem; color:#2d7ec4; font-size:0.82rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .15s;">
                        <i class="fas fa-plus-circle" style="font-size:0.9rem;"></i> Add New Prescription
                    </button>
                </div>

                <!-- Add Form (collapsed by default) -->
                <div id="rxAddForm" style="display:none; padding:0.9rem 1.2rem 0;">
                    <div style="background:#fff; border-radius:0.9rem; border:1px solid #d0e8f8; padding:1rem;">
                        <div style="font-size:0.75rem; font-weight:700; color:#3b6fa0; text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.75rem;">
                            <i class="fas fa-pen me-1"></i> New Prescription
                        </div>
                        <div style="margin-bottom:0.65rem;">
                            <label style="font-size:0.75rem; font-weight:600; color:#555; display:block; margin-bottom:3px;">Title <span style="color:#999; font-weight:400;">(optional)</span></label>
                            <input type="text" id="rxTitle"
                                style="width:100%; border:1px solid #cde0f5; border-radius:0.5rem; padding:0.4rem 0.6rem; font-size:0.82rem; background:#f8fcff; outline:none; font-family:inherit;"
                                placeholder="e.g. Dr. Rahman — 12 May 2026">
                        </div>
                        <div style="margin-bottom:0.65rem;">
                            <label style="font-size:0.75rem; font-weight:600; color:#555; display:block; margin-bottom:3px;">Notes <span style="color:#999; font-weight:400;">(medicine list, instructions...)</span></label>
                            <textarea id="rxNotes" rows="4"
                                style="width:100%; resize:vertical; border:1px solid #cde0f5; border-radius:0.5rem; padding:0.4rem 0.6rem; font-size:0.82rem; background:#f8fcff; outline:none; font-family:inherit; line-height:1.6;"
                                placeholder="Napa 500mg — 2 times daily&#10;Antacid — before meal..."></textarea>
                        </div>
                        <div style="margin-bottom:0.85rem;">
                            <label style="font-size:0.75rem; font-weight:600; color:#555; display:block; margin-bottom:3px;">
                                <i class="fas fa-image me-1 text-muted"></i>Prescription Image <span style="color:#999; font-weight:400;">(optional, max 10MB)</span>
                            </label>
                            <label id="rxImageLabel" style="display:flex; align-items:center; gap:8px; border:2px dashed #c0daf5; border-radius:0.6rem; padding:0.55rem 0.75rem; cursor:pointer; background:#f0f8ff; transition:border-color .15s;">
                                <i class="fas fa-cloud-upload-alt" style="color:#2d7ec4; font-size:1.1rem;"></i>
                                <span id="rxImageName" style="font-size:0.78rem; color:#4a7fa8;">Click to choose image...</span>
                                <input type="file" id="rxImage" accept="image/*" style="display:none;">
                            </label>
                            <div id="rxImagePreview" style="margin-top:6px; display:none;">
                                <img id="rxPreviewImg" src="" alt="Preview" style="max-height:100px; max-width:100%; border-radius:6px; border:1px solid #c0daf5;">
                            </div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="button" id="cancelAddRxBtn"
                                style="flex:1; background:#f0f4f8; border:1px solid #d0dde8; border-radius:0.6rem; padding:0.45rem; font-size:0.8rem; color:#5a7a96; cursor:pointer; font-weight:600;">
                                Cancel
                            </button>
                            <button type="button" id="saveRxBtn"
                                style="flex:2; background:linear-gradient(135deg,#1a5fa8,#2d7ec4); border:none; border-radius:0.6rem; padding:0.45rem; font-size:0.82rem; color:#fff; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:6px;">
                                <i class="fas fa-save"></i> Save Prescription
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Prescription List -->
                <div id="rxList" style="padding:0.9rem 1.2rem 1.2rem;">
                    <div style="text-align:center; color:#94aec5; padding:2rem 0; font-size:0.85rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size:1.2rem; display:block; margin-bottom:6px;"></i>Loading...
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="variantSelectorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-scrollable variant-modal-dialog">
        <div class="modal-content variant-modal-content">
            <div class="modal-header variant-modal-header">
                <h5 class="modal-title variant-modal-title">Choose Product Option</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="variantSelectorBody">
                <div class="text-center text-muted py-4">Loading product options...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="variantAddToCartBtn" disabled>Add To Cart</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="boxPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog" style="max-width:460px">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-box-seam me-2"></i>Select Box to Sell From</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="boxPickerBody">
                <div class="text-center py-3 text-muted">Loading...</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="boxPickerConfirmBtn">
                    <i class="bi bi-cart-plus me-1"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="invoicePreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:360px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 75vh;">
                <iframe
                    id="invoicePreviewFrame"
                    title="Invoice Preview"
                    style="width: 100%; height: 100%; border: 0;"
                    src="about:blank"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="invoicePrintBtn">Print</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('footer.js')
@include('pos.posJs')
@include('pos.customerJs')
<script>
// ── Loyalty Points UI ────────────────────────────────────────────────────────
(function () {
    const loyaltyInfoUrl   = @json(route('pos.customerLoyaltyInfo'));
    const loyaltyPanel     = document.getElementById('loyaltyPanel');
    const memberBadge      = document.getElementById('loyaltyMemberBadge');
    const pointsAvailable  = document.getElementById('loyaltyPointsAvailable');
    const maxDiscount      = document.getElementById('loyaltyMaxDiscount');
    const redeemInput      = document.getElementById('redeemPointsInput');
    const applyBtn         = document.getElementById('applyRedeemBtn');
    const clearBtn         = document.getElementById('clearRedeemBtn');
    const discountRow      = document.getElementById('loyaltyDiscountRow');
    const discountDisplay  = document.getElementById('loyaltyDiscountDisplay');
    const hiddenPoints     = document.getElementById('redeemed_points');

    let loyaltyState = {
        enabled: false,
        redemptionEnabled: false,
        availablePoints: 0,
        pointValue: 1,
        appliedPoints: 0,
        appliedDiscount: 0,
    };

    window.onCustomerSelected = function (customerId) {
        if (!customerId) { hideLoyaltyPanel(); return; }
        $.getJSON(loyaltyInfoUrl, { customer_id: customerId }, function (data) {
            if (!data.enabled) { hideLoyaltyPanel(); return; }
            loyaltyState.enabled          = true;
            loyaltyState.redemptionEnabled = data.redemption_enabled;
            loyaltyState.availablePoints   = data.loyalty_points;
            loyaltyState.pointValue        = data.point_value;
            loyaltyState.appliedPoints     = 0;
            loyaltyState.appliedDiscount   = 0;
            if (hiddenPoints) hiddenPoints.value = 0;

            if (pointsAvailable) pointsAvailable.textContent = data.loyalty_points.toLocaleString();
            if (maxDiscount) maxDiscount.textContent     = formatCurrency(data.max_discount);
            if (redeemInput) { redeemInput.max = data.loyalty_points; redeemInput.value = 0; }
            discountRow?.classList.add('d-none');
            clearBtn?.classList.add('d-none');
            memberBadge?.classList.toggle('d-none', !data.is_member);
            if (memberBadge) memberBadge.textContent = data.member_label;
            loyaltyPanel?.classList.toggle('d-none', !data.redemption_enabled || data.loyalty_points <= 0);
        });
    };

    window.onCustomerCleared = function () {
        hideLoyaltyPanel();
        clearAppliedDiscount();
    };

    function hideLoyaltyPanel() {
        loyaltyState = { enabled: false, redemptionEnabled: false, availablePoints: 0, pointValue: 1, appliedPoints: 0, appliedDiscount: 0 };
        loyaltyPanel?.classList.add('d-none');
        clearAppliedDiscount();
    }

    function clearAppliedDiscount() {
        if (loyaltyState.appliedDiscount > 0) {
            loyaltyState.appliedPoints  = 0;
            loyaltyState.appliedDiscount = 0;
            hiddenPoints.value = 0;
            discountRow.classList.add('d-none');
            clearBtn.classList.add('d-none');
            redeemInput.value = 0;
            // Recompute cart total which will use the canonical grand_total_amount
            updateCartTotal();
        } else {
            hiddenPoints.value = 0;
        }
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            if (!loyaltyState.enabled || !loyaltyState.redemptionEnabled) return;
            const pts = parseInt(redeemInput.value, 10) || 0;
            if (pts <= 0) { toastr.warning('Enter points to redeem.'); return; }
            if (pts > loyaltyState.availablePoints) {
                toastr.error('Cannot redeem more points than available (' + loyaltyState.availablePoints + ').');
                return;
            }
            const grandTotal = getGrandTotalAmount();
            const discount   = Math.round(pts * loyaltyState.pointValue * 100) / 100;
            if (discount > grandTotal) {
                toastr.error('Point discount (' + formatCurrency(discount) + ') cannot exceed invoice total (' + formatCurrency(grandTotal) + ').');
                return;
            }
            loyaltyState.appliedPoints   = pts;
            loyaltyState.appliedDiscount = discount;
            hiddenPoints.value = pts;
            discountDisplay.textContent = formatCurrency(discount);
            discountRow.classList.remove('d-none');
            clearBtn.classList.remove('d-none');
            // Show new payable in grand-total chip
            const payable = Math.max(0, grandTotal - discount);
            document.getElementById('grand-total').textContent = formatCurrency(payable);
            // Adjust payment row if auto-filled
            syncSingleAutoFillPayment(grandTotal, payable);
            capPaymentsToGrandTotal(payable);
            syncPaymentState();
            toastr.success(pts + ' points applied — ' + formatCurrency(discount) + ' discount!');
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            loyaltyState.appliedPoints  = 0;
            loyaltyState.appliedDiscount = 0;
            hiddenPoints.value = 0;
            discountRow.classList.add('d-none');
            clearBtn.classList.add('d-none');
            redeemInput.value = 0;
            updateCartTotal(); // restore original grand total
            toastr.info('Point redemption cleared.');
        });
    }

    // After completeSale resets the form, clear loyalty state too
    const _origCompleteSale = window.completeSale;
    if (typeof _origCompleteSale === 'function') {
        window.completeSale = function (saleType) {
            _origCompleteSale(saleType);
        };
    }

    // Reset loyalty on successful sale (cart clear triggers this)
    $(document).on('loyaltyClear', function () { hideLoyaltyPanel(); });
})();
</script>
<script>
    document.addEventListener('click', function (event) {
        const card = event.target.closest('.product-card');

        if (!card) {
            return;
        }

        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            card.style.transform = 'scale(1)';
        }, 100);
    });
</script>
@endsection
