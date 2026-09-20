@php
    $isDashboard = request()->routeIs('dashboard');
    $isContentMenu = request()->routeIs('banner.*', 'categoryPromotion.*', 'coupon.*', 'faq.*', 'review.*', 'setting.*');
    $isCatalogMenu = request()->routeIs('category.*', 'brand.*', 'variation.*', 'unit.*', 'taxRule.*', 'product.*', 'discountManager.*');
    $isLocationMenu = request()->routeIs('branch.*', 'branchPrice.*', 'warehouse.*', 'supplier.*', 'customer.*', 'shipmentZone.*');
    $isInventoryMenu = request()->routeIs('purchaseOrder.*', 'purchaseRequisition.*', 'inventoryAdjustment.*', 'stockTransfer.*', 'supplierReturn.*', 'availableStock.*');
    $isSalesMenu = request()->routeIs('order.*', 'orderStatus.*', 'transaction.*', 'pos.*', 'posCustomer.*', 'cart.*', 'loyalty.*');
    $isExpenseMenu = request()->routeIs('expense.*', 'expenseCategory.*');
    $isAccountingMenu = request()->routeIs('account.*', 'accounting.*');
    $isReportMenu = request()->routeIs('sales.*', 'customerSales.*', 'stocks.*', 'inventoryTransaction.*', 'report.*');
    $isAdminMenu = request()->routeIs('user.*', 'role.*', 'permission.*', 'staff.*', 'salesCommissionPlan.*');
    $brandName = $settings?->company_name ?? config('app.name', 'Adorzotno');
    $logoPath = !empty($settings?->header_logo) ? url($settings->header_logo) : null;
@endphp

<div id="sidebar">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="sidebar-brand-card">
                <div class="sidebar-brand-top">
                    <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
                        <div class="sidebar-brand-logo">
                            @if($logoPath)
                                <img src="{{ $logoPath }}" alt="{{ $brandName }}">
                            @else
                                <img src="{{ url('public/admin/dist/assets/compiled/png/AdorzotnoLogo.png')}}" alt="{{ $brandName }}">
                            @endif
                        </div>
                    </a>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="action-icon-btn sidebar-collapse-btn d-none d-xl-inline-flex" id="sidebarCollapseBtn" data-sidebar-collapse aria-label="Hide sidebar">
                            <i class="bi bi-layout-sidebar-inset-reverse"></i>
                        </button>

                        <div class="sidebar-toggler x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>

                <div class="sidebar-utility">
                    <div class="theme-toggle">
                        <div class="theme-toggle-label">
                            <strong>Theme</strong>
                            <span>Dark / Light mode</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="18" height="18" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2" opacity=".3"></path>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6 mb-0">
                                <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="18" height="18" viewBox="0 0 24 24">
                                <path fill="currentColor" d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Overview</li>
                <li class="sidebar-item {{ $isDashboard ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-title">Setup</li>
                <li class="sidebar-item has-sub {{ $isLocationMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-diagram-3-fill"></i>
                        <span>Business Setup</span>
                    </a>
                    <ul class="submenu {{ $isLocationMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('branch.*') ? 'active' : '' }}">
                            <a href="{{ route('branch.show') }}" class="submenu-link">Branches</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('warehouse.*') ? 'active' : '' }}">
                            <a href="{{ route('warehouse.show') }}" class="submenu-link">Warehouses</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('branchPrice.*') ? 'active' : '' }}">
                            <a href="{{ route('branchPrice.show') }}" class="submenu-link">Branch Prices</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('supplier.*') ? 'active' : '' }}">
                            <a href="{{ route('supplier.show') }}" class="submenu-link">Suppliers</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('customer.*') ? 'active' : '' }}">
                            <a href="{{ route('customer.show') }}" class="submenu-link">Customers</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('shipmentZone.*') ? 'active' : '' }}">
                            <a href="{{ route('shipmentZone.show') }}" class="submenu-link">Shipment Zones</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item has-sub {{ $isCatalogMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-tags-fill"></i>
                        <span>Medicine Setup</span>
                    </a>
                    <ul class="submenu {{ $isCatalogMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('category.*') ? 'active' : '' }}">
                            <a href="{{ route('category.show') }}" class="submenu-link">Categories</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('brand.*') ? 'active' : '' }}">
                            <a href="{{ route('brand.show') }}" class="submenu-link">Brands</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('variation.*') ? 'active' : '' }}">
                            <a href="{{ route('variation.show') }}" class="submenu-link">Variations</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('unit.*') ? 'active' : '' }}">
                            <a href="{{ route('unit.show') }}" class="submenu-link">Units</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('taxRule.*') ? 'active' : '' }}">
                            <a href="{{ route('taxRule.show') }}" class="submenu-link">VAT Rules</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('product.*') ? 'active' : '' }}">
                            <a href="{{ route('product.show') }}" class="submenu-link">Products</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('discountManager.*') ? 'active' : '' }}">
                            <a href="{{ route('discountManager.show') }}" class="submenu-link">Discount Manager</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Purchase & Inventory</li>
                <li class="sidebar-item has-sub {{ $isInventoryMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Inventory</span>
                    </a>
                    <ul class="submenu {{ $isInventoryMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('purchaseRequisition.*') ? 'active' : '' }}">
                            <a href="{{ route('purchaseRequisition.show') }}" class="submenu-link">Purchase Requisitions</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('purchaseOrder.*') ? 'active' : '' }}">
                            <a href="{{ route('purchaseOrder.show') }}" class="submenu-link">Purchase Orders</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inventoryAdjustment.openingStock.*') ? 'active' : '' }}">
                            <a href="{{ route('inventoryAdjustment.openingStock.create') }}" class="submenu-link">Opening Stock</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inventoryAdjustment.stockIssue.*') ? 'active' : '' }}">
                            <a href="{{ route('inventoryAdjustment.stockIssue.create') }}" class="submenu-link">Stock Issue</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('availableStock.*') ? 'active' : '' }}">
                            <a href="{{ route('availableStock.show') }}" class="submenu-link">Available Stock</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inventoryAdjustment.show', 'inventoryAdjustment.create', 'inventoryAdjustment.store') ? 'active' : '' }}">
                            <a href="{{ route('inventoryAdjustment.show') }}" class="submenu-link">Adjustments</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('stockTransfer.*') ? 'active' : '' }}">
                            <a href="{{ route('stockTransfer.show') }}" class="submenu-link">Stock Transfers</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('supplierReturn.*') ? 'active' : '' }}">
                            <a href="{{ route('supplierReturn.show') }}" class="submenu-link">Supplier Returns</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Sales</li>
                <li class="sidebar-item has-sub {{ $isSalesMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-cart-check-fill"></i>
                        <span>Sales & POS</span>
                    </a>
                    <ul class="submenu {{ $isSalesMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('pos.show') ? 'active' : '' }}">
                            <a href="{{ route('pos.show') }}" class="submenu-link">POS</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('order.*') ? 'active' : '' }}">
                            <a href="{{ route('order.show') }}" class="submenu-link">Orders</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('loyalty.settings') ? 'active' : '' }}">
                            <a href="{{ route('loyalty.settings') }}" class="submenu-link">Loyalty Settings</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('loyalty.adjust') ? 'active' : '' }}">
                            <a href="{{ route('loyalty.adjust') }}" class="submenu-link">Loyalty Adjust</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('loyalty.report') ? 'active' : '' }}">
                            <a href="{{ route('loyalty.report') }}" class="submenu-link">Loyalty Reports</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Finance</li>
                <li class="sidebar-item has-sub {{ $isExpenseMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Expense</span>
                    </a>
                    <ul class="submenu {{ $isExpenseMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('expenseCategory.*') ? 'active' : '' }}">
                            <a href="{{ route('expenseCategory.show') }}" class="submenu-link">Expense Categories</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('expense.show', 'expense.create', 'expense.edit', 'expense.store', 'expense.update') ? 'active' : '' }}">
                            <a href="{{ route('expense.show') }}" class="submenu-link">Expenses</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('expense.report') ? 'active' : '' }}">
                            <a href="{{ route('expense.report') }}" class="submenu-link">Expense Report</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item has-sub {{ $isAccountingMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-cash-stack"></i>
                        <span>Accounting</span>
                    </a>
                    <ul class="submenu {{ $isAccountingMenu ? 'active' : '' }}">
                        <li class="submenu-item submenu-group-label">
                            <span style="font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.3rem .75rem .1rem;display:block">Transactions</span>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('account.banks') ? 'active' : '' }}">
                            <a href="{{ route('account.banks') }}" class="submenu-link">
                                <i class="bi bi-bank me-1" style="font-size:.8rem"></i>Bank Accounts
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('account.show', 'account.create', 'account.edit') && !request()->routeIs('account.banks') ? 'active' : '' }}">
                            <a href="{{ route('account.show') }}" class="submenu-link">
                                <i class="bi bi-list-ul me-1" style="font-size:.8rem"></i>Chart of Accounts
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.journal', 'accounting.journal.create') ? 'active' : '' }}">
                            <a href="{{ route('accounting.journal') }}" class="submenu-link">
                                <i class="bi bi-journal-text me-1" style="font-size:.8rem"></i>Journals
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.transfer.*') ? 'active' : '' }}">
                            <a href="{{ route('accounting.transfer.create') }}" class="submenu-link">
                                <i class="bi bi-arrow-left-right me-1" style="font-size:.8rem"></i>Fund Transfer
                            </a>
                        </li>

                        <li class="submenu-item submenu-group-label">
                            <span style="font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.5rem .75rem .1rem;display:block">Financial Reports</span>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report','profit_loss') === 'profit_loss' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=profit_loss" class="submenu-link">
                                <i class="bi bi-bar-chart-line me-1" style="font-size:.8rem"></i>Profit &amp; Loss
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'trial_balance' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=trial_balance" class="submenu-link">
                                <i class="bi bi-list-columns me-1" style="font-size:.8rem"></i>Trial Balance
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'balance_sheet' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=balance_sheet" class="submenu-link">
                                <i class="bi bi-layout-split me-1" style="font-size:.8rem"></i>Balance Sheet
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'general_ledger' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=general_ledger" class="submenu-link">
                                <i class="bi bi-journal-text me-1" style="font-size:.8rem"></i>General Ledger
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'cash_book' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=cash_book" class="submenu-link">
                                <i class="bi bi-cash-coin me-1" style="font-size:.8rem"></i>Cash Book
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'bank_book' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=bank_book" class="submenu-link">
                                <i class="bi bi-bank me-1" style="font-size:.8rem"></i>Bank Book
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'receivable' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=receivable" class="submenu-link">
                                <i class="bi bi-arrow-down-circle me-1" style="font-size:.8rem"></i>Receivable
                            </a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('accounting.reports') && request('report') === 'payable' ? 'active' : '' }}">
                            <a href="{{ route('accounting.reports') }}?report=payable" class="submenu-link">
                                <i class="bi bi-arrow-up-circle me-1" style="font-size:.8rem"></i>Payable
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Ecommerce</li>
                <li class="sidebar-item has-sub {{ $isContentMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-megaphone-fill"></i>
                        <span>Marketing & Content</span>
                    </a>
                    <ul class="submenu {{ $isContentMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('banner.*') ? 'active' : '' }}">
                            <a href="{{ route('banner.show') }}" class="submenu-link">Banners</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('categoryPromotion.*') ? 'active' : '' }}">
                            <a href="{{ route('categoryPromotion.show') }}" class="submenu-link">Category Promotions</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('coupon.*') ? 'active' : '' }}">
                            <a href="{{ route('coupon.show') }}" class="submenu-link">Coupons</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('faq.*') ? 'active' : '' }}">
                            <a href="{{ route('faq.show') }}" class="submenu-link">FAQs</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('review.*') ? 'active' : '' }}">
                            <a href="{{ route('review.show') }}" class="submenu-link">Reviews</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('setting.*') ? 'active' : '' }}">
                            <a href="{{ route('setting.show') }}" class="submenu-link">Settings</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Reports</li>
                <li class="sidebar-item has-sub {{ $isReportMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        <span>Reports</span>
                    </a>
                    <ul class="submenu {{ $isReportMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <a href="{{ route('sales.show') }}" class="submenu-link">Sales Report</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('customerSales.*') ? 'active' : '' }}">
                            <a href="{{ route('customerSales.show') }}" class="submenu-link">Customer Sales</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.productSales.*') ? 'active' : '' }}">
                            <a href="{{ route('report.productSales.show') }}" class="submenu-link">Product Sales</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.commission.*') ? 'active' : '' }}">
                            <a href="{{ route('report.commission.show') }}" class="submenu-link">Commission Report</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.purchase.*') ? 'active' : '' }}">
                            <a href="{{ route('report.purchase.show') }}" class="submenu-link">Purchase Report</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                            <a href="{{ route('stocks.show') }}" class="submenu-link">Stock Report</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('inventoryTransaction.*') ? 'active' : '' }}">
                            <a href="{{ route('inventoryTransaction.show') }}" class="submenu-link">Inventory Ledger</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.branchStock.*') ? 'active' : '' }}">
                            <a href="{{ route('report.branchStock.show') }}" class="submenu-link">Branch Stock Summary</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.cartonBox.*') ? 'active' : '' }}">
                            <a href="{{ route('report.cartonBox.show') }}" class="submenu-link">Carton / Box Tracking</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.supplierDue.*') ? 'active' : '' }}">
                            <a href="{{ route('report.supplierDue.show') }}" class="submenu-link">Supplier Due</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.customerDue.*') ? 'active' : '' }}">
                            <a href="{{ route('report.customerDue.show') }}" class="submenu-link">Customer Due</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.lowStock.*') ? 'active' : '' }}">
                            <a href="{{ route('report.lowStock.show') }}" class="submenu-link">Low Stock Alert</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.expiry.*') ? 'active' : '' }}">
                            <a href="{{ route('report.expiry.show') }}" class="submenu-link">Expiry Report</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('report.stockout.*') ? 'active' : '' }}">
                            <a href="{{ route('report.stockout.show') }}" class="submenu-link">&#128308; Stockout Prediction</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-title">Administration</li>
                <li class="sidebar-item has-sub {{ $isAdminMenu ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Administration</span>
                    </a>
                    <ul class="submenu {{ $isAdminMenu ? 'active' : '' }}">
                        <li class="submenu-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
                            <a href="{{ route('user.show') }}" class="submenu-link">Users</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('role.*') ? 'active' : '' }}">
                            <a href="{{ route('role.show') }}" class="submenu-link">Roles</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('permission.*') ? 'active' : '' }}">
                            <a href="{{ route('permission.show') }}" class="submenu-link">Permissions</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                            <a href="{{ route('staff.show') }}" class="submenu-link">Staff</a>
                        </li>
                        <li class="submenu-item {{ request()->routeIs('salesCommissionPlan.*') ? 'active' : '' }}">
                            <a href="{{ route('salesCommissionPlan.show') }}" class="submenu-link">Sales Commission Plans</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
