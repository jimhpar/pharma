<?php

namespace App\Support;

class PermissionCatalog
{
    public static function definitions(): array
    {
        return [
            ['module' => 'Dashboard', 'action' => 'View', 'slug' => 'dashboard.view', 'description' => 'View dashboard widgets and summaries.'],
            ['module' => 'Branches', 'action' => 'Manage', 'slug' => 'branches.manage', 'description' => 'Create, edit, activate, and deactivate branches.'],
            ['module' => 'Branches', 'action' => 'Cross Access', 'slug' => 'branches.cross_access', 'description' => 'Access and report across multiple branches.'],
            ['module' => 'Warehouses', 'action' => 'Manage', 'slug' => 'warehouses.manage', 'description' => 'Maintain warehouses and defaults.'],
            ['module' => 'Branch Prices', 'action' => 'Manage', 'slug' => 'branch_prices.manage', 'description' => 'Manage branch-wise SKU prices.'],
            ['module' => 'Purchases', 'action' => 'Manage', 'slug' => 'purchases.manage', 'description' => 'Create and maintain purchase orders.'],
            ['module' => 'Products', 'action' => 'Manage Discounts', 'slug' => 'discount.manage', 'description' => 'Apply and remove bulk discounts on products and categories.'],
            ['module' => 'Purchases', 'action' => 'Requisition Manage', 'slug' => 'purchases.requisition_manage', 'description' => 'Create and submit purchase requisitions.'],
            ['module' => 'Purchases', 'action' => 'Requisition Approve', 'slug' => 'purchases.requisition_approve', 'description' => 'Approve or reject purchase requisitions and convert to PO.'],
            ['module' => 'Inventory', 'action' => 'Adjust', 'slug' => 'inventory.adjust', 'description' => 'Create opening stock, stock issues, adjustments, and supplier returns.'],
            ['module' => 'Stock Transfers', 'action' => 'Manage', 'slug' => 'stock_transfers.manage', 'description' => 'Request, approve, dispatch, and receive warehouse and branch transfers.'],
            ['module' => 'Customers', 'action' => 'Manage', 'slug' => 'customers.manage', 'description' => 'Create, update, review statements, and maintain customer profiles.'],
            ['module' => 'POS', 'action' => 'Access', 'slug' => 'pos.access', 'description' => 'Use the POS and cart flow.'],
            ['module' => 'Sales', 'action' => 'Credit Limit Override', 'slug' => 'sales.credit_limit_override', 'description' => 'Allow credit sales that exceed the customer credit limit.'],
            ['module' => 'Orders', 'action' => 'View', 'slug' => 'orders.view', 'description' => 'View sales orders and invoices.'],
            ['module' => 'Orders', 'action' => 'Manage', 'slug' => 'orders.manage', 'description' => 'Change order status and operational actions.'],
            ['module' => 'Payments', 'action' => 'Manage', 'slug' => 'payments.manage', 'description' => 'Collect and record payments.'],
            ['module' => 'Reports', 'action' => 'View Sales', 'slug' => 'reports.sales.view', 'description' => 'View branch and consolidated sales reports.'],
            ['module' => 'Reports', 'action' => 'View Customers', 'slug' => 'reports.customers.view', 'description' => 'View customer-wise sales and receivable reports.'],
            ['module' => 'Reports', 'action' => 'View Inventory', 'slug' => 'reports.inventory.view', 'description' => 'View stock and inventory ledger reports.'],
            ['module' => 'Users', 'action' => 'Manage', 'slug' => 'users.manage', 'description' => 'Manage users.'],
            ['module' => 'Roles', 'action' => 'Manage', 'slug' => 'roles.manage', 'description' => 'Manage roles.'],
            ['module' => 'Permissions', 'action' => 'Manage', 'slug' => 'permissions.manage', 'description' => 'Manage permissions.'],
            ['module' => 'Staff', 'action' => 'Manage', 'slug' => 'staff.manage', 'description' => 'Manage staff assignments.'],
            ['module' => 'Commission Plans', 'action' => 'Manage', 'slug' => 'commission_plans.manage', 'description' => 'Manage sales commission plans.'],
        ];
    }
}
