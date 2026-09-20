<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BoxStockController;
use App\Http\Controllers\BranchPriceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPromotionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosCustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProductVariationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionPlanController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ShipmentZoneController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\TaxRulesController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\CustomerPrescriptionController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'initialize.branch'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->middleware('require.permission:dashboard.view')->name('dashboard');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    //Banner
    Route::group(['prefix' => 'banner', 'as' => 'banner.'], function () {
    Route::get('/show', [BannerController::class, 'show'])->name('show');
    Route::post('/list', [BannerController::class, 'list'])->name('list');
    Route::get('create', [BannerController::class, 'create'])->name('create');
    Route::post('store', [BannerController::class, 'store'])->name('store');
    Route::get('edit/{id}', [BannerController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [BannerController::class, 'update'])->name('update');
    Route::post('delete', [BannerController::class, 'delete'])->name('delete');
    });

    //Category
    Route::group(['prefix' => 'category', 'as' => 'category.'], function () {
    Route::get('/show', [CategoryController::class, 'show'])->name('show');
    Route::post('/list', [CategoryController::class, 'list'])->name('list');
    Route::get('create', [CategoryController::class, 'create'])->name('create');
    Route::post('store', [CategoryController::class, 'store'])->name('store');
    Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [CategoryController::class, 'update'])->name('update');
    Route::post('delete', [CategoryController::class, 'delete'])->name('delete');
    });

    //Category Promotion
    Route::group(['prefix' => 'category-promotion', 'as' => 'categoryPromotion.'], function () {
    Route::get('/show', [CategoryPromotionController::class, 'show'])->name('show');
    Route::post('/list', [CategoryPromotionController::class, 'list'])->name('list');
    Route::get('create', [CategoryPromotionController::class, 'create'])->name('create');
    Route::post('store', [CategoryPromotionController::class, 'store'])->name('store');
    Route::get('edit/{id}', [CategoryPromotionController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [CategoryPromotionController::class, 'update'])->name('update');
    Route::post('delete', [CategoryPromotionController::class, 'delete'])->name('delete');
    });

    //Brand
    Route::group(['prefix' => 'brand', 'as' => 'brand.'], function () {
    Route::get('/show', [BrandController::class, 'show'])->name('show');
    Route::post('/list', [BrandController::class, 'list'])->name('list');
    Route::get('create', [BrandController::class, 'create'])->name('create');
    Route::post('store', [BrandController::class, 'store'])->name('store');
    Route::get('edit/{id}', [BrandController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [BrandController::class, 'update'])->name('update');
    Route::post('delete', [BrandController::class, 'delete'])->name('delete');
    });

    //Branch
    Route::group(['prefix' => 'branch', 'as' => 'branch.'], function () {
    Route::get('/show', [BranchController::class, 'show'])->middleware('require.permission:branches.manage')->name('show');
    Route::post('/list', [BranchController::class, 'list'])->middleware('require.permission:branches.manage')->name('list');
    Route::post('/switch-current', [BranchController::class, 'switchCurrent'])->name('switchCurrent');
    Route::get('create', [BranchController::class, 'create'])->middleware('require.permission:branches.manage')->name('create');
    Route::post('store', [BranchController::class, 'store'])->middleware('require.permission:branches.manage')->name('store');
    Route::get('edit/{id}', [BranchController::class, 'edit'])->middleware('require.permission:branches.manage')->name('edit');
    Route::post('update/{id}', [BranchController::class, 'update'])->middleware('require.permission:branches.manage')->name('update');
    Route::post('delete', [BranchController::class, 'delete'])->middleware('require.permission:branches.manage')->name('delete');
    });

    //Branch Price
    Route::group(['prefix' => 'branch-price', 'as' => 'branchPrice.', 'middleware' => 'require.permission:branch_prices.manage'], function () {
    Route::get('/show', [BranchPriceController::class, 'show'])->name('show');
    Route::post('/list', [BranchPriceController::class, 'list'])->name('list');
    Route::get('create', [BranchPriceController::class, 'create'])->name('create');
    Route::post('store', [BranchPriceController::class, 'store'])->name('store');
    Route::get('edit/{id}', [BranchPriceController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [BranchPriceController::class, 'update'])->name('update');
    Route::post('delete', [BranchPriceController::class, 'delete'])->name('delete');
    });

    //Warehouse
    Route::group(['prefix' => 'warehouse', 'as' => 'warehouse.', 'middleware' => 'require.permission:warehouses.manage'], function () {
    Route::get('/show', [WarehouseController::class, 'show'])->name('show');
    Route::post('/list', [WarehouseController::class, 'list'])->name('list');
    Route::get('create', [WarehouseController::class, 'create'])->name('create');
    Route::post('store', [WarehouseController::class, 'store'])->name('store');
    Route::get('edit/{id}', [WarehouseController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [WarehouseController::class, 'update'])->name('update');
    Route::post('delete', [WarehouseController::class, 'delete'])->name('delete');
    });

    //Variation
    Route::group(['prefix' => 'variation', 'as' => 'variation.'], function () {
    Route::get('/show', [VariationController::class, 'show'])->name('show');
    Route::post('/list', [VariationController::class, 'list'])->name('list');
    Route::get('create', [VariationController::class, 'create'])->name('create');
    Route::post('store', [VariationController::class, 'store'])->name('store');
    Route::get('edit/{id}', [VariationController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [VariationController::class, 'update'])->name('update');
    Route::post('delete', [VariationController::class, 'delete'])->name('delete');
    });

    //Unit
    Route::group(['prefix' => 'unit', 'as' => 'unit.'], function () {
    Route::get('/show', [UnitController::class, 'show'])->name('show');
    Route::post('/list', [UnitController::class, 'list'])->name('list');
    Route::get('create', [UnitController::class, 'create'])->name('create');
    Route::post('store', [UnitController::class, 'store'])->name('store');
    Route::get('edit/{id}', [UnitController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [UnitController::class, 'update'])->name('update');
    Route::post('delete', [UnitController::class, 'delete'])->name('delete');
    });

    //Tax Rule
    Route::group(['prefix' => 'tax-rule', 'as' => 'taxRule.'], function () {
    Route::get('/show', [TaxRulesController::class, 'show'])->name('show');
    Route::post('/list', [TaxRulesController::class, 'list'])->name('list');
    Route::get('create', [TaxRulesController::class, 'create'])->name('create');
    Route::post('store', [TaxRulesController::class, 'store'])->name('store');
    Route::get('edit/{id}', [TaxRulesController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [TaxRulesController::class, 'update'])->name('update');
    Route::post('delete', [TaxRulesController::class, 'delete'])->name('delete');
    });

    //Product
    Route::group(['prefix' => 'product', 'as' => 'product.'], function () {
    Route::get('/show', [ProductController::class, 'show'])->name('show');
    Route::post('/list', [ProductController::class, 'list'])->name('list');
    Route::get('create', [ProductController::class, 'create'])->name('create');
    Route::post('store', [ProductController::class, 'store'])->name('store');
    Route::get('edit/{id}', [ProductController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [ProductController::class, 'update'])->name('update');
    Route::post('delete', [ProductController::class, 'delete'])->name('delete');
    Route::post('tempList', [ProductVariationController::class, 'tempList'])->name('tempList');
    Route::post('tempStore', [ProductVariationController::class, 'tempStore'])->name('tempStore');
    Route::post('imageDelete', [ProductController::class, 'imageDelete'])->name('imageDelete');   
    
    Route::post('createOrUpdateSku', [ProductVariationController::class, 'createOrUpdateSku'])->name('createOrUpdateSku');
    Route::get('variationEdit', [ProductVariationController::class, 'variationEdit'])->name('variationEdit');
    Route::post('variationDelete', [ProductVariationController::class, 'variationDelete'])->name('variationDelete');
    Route::post('tempvariationDelete', [ProductVariationController::class, 'tempvariationDelete'])->name('tempvariationDelete');

    Route::get('getVariationValues', [ProductVariationController::class, 'getVariationValues'])->name('getVariationValues');

    });

    //Purchase Order
    Route::group(['prefix' => 'purchase-order', 'as' => 'purchaseOrder.', 'middleware' => 'require.permission:purchases.manage'], function () {
    Route::get('/show', [PurchaseOrderController::class, 'show'])->name('show');
    Route::post('/list', [PurchaseOrderController::class, 'list'])->name('list');
    Route::get('create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('store', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('edit/{id}', [PurchaseOrderController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [PurchaseOrderController::class, 'update'])->name('update');
    Route::get('receive/{id}', [PurchaseOrderController::class, 'receive'])->name('receive');
    Route::post('receive/{id}', [PurchaseOrderController::class, 'receiveStore'])->name('receiveStore');
    Route::post('delete', [PurchaseOrderController::class, 'delete'])->name('delete');
    Route::get('sku-search', [PurchaseOrderController::class, 'skuSearch'])->name('skuSearch');
    });

    //Inventory Adjustment
    Route::group(['prefix' => 'inventory-adjustment', 'as' => 'inventoryAdjustment.', 'middleware' => 'require.permission:inventory.adjust'], function () {
    Route::get('/show', [InventoryAdjustmentController::class, 'show'])->name('show');
    Route::post('/list', [InventoryAdjustmentController::class, 'list'])->name('list');
    Route::get('create', [InventoryAdjustmentController::class, 'create'])->name('create');
    Route::get('opening-stock/create', [InventoryAdjustmentController::class, 'createOpeningStock'])->name('openingStock.create');
    Route::get('stock-issue/create', [InventoryAdjustmentController::class, 'createStockIssue'])->name('stockIssue.create');
    Route::post('store', [InventoryAdjustmentController::class, 'store'])->name('store');
    });

    //Stock Transfer
    Route::group(['prefix' => 'stock-transfer', 'as' => 'stockTransfer.', 'middleware' => 'require.permission:stock_transfers.manage'], function () {
    Route::get('/show', [StockTransferController::class, 'show'])->name('show');
    Route::post('/list', [StockTransferController::class, 'list'])->name('list');
    Route::get('create', [StockTransferController::class, 'create'])->name('create');
    Route::post('store', [StockTransferController::class, 'store'])->name('store');
    Route::get('details/{id}', [StockTransferController::class, 'details'])->name('details');
    Route::post('approve/{id}', [StockTransferController::class, 'approve'])->name('approve');
    Route::post('dispatch/{id}', [StockTransferController::class, 'dispatch'])->name('dispatch');
    Route::post('receive/{id}', [StockTransferController::class, 'receive'])->name('receive');
    Route::post('cancel/{id}', [StockTransferController::class, 'cancel'])->name('cancel');
    });

    //Supplier Return
    Route::group(['prefix' => 'supplier-return', 'as' => 'supplierReturn.', 'middleware' => 'require.permission:inventory.adjust'], function () {
    Route::get('/show', [SupplierReturnController::class, 'show'])->name('show');
    Route::post('/list', [SupplierReturnController::class, 'list'])->name('list');
    Route::get('create', [SupplierReturnController::class, 'create'])->name('create');
    Route::post('store', [SupplierReturnController::class, 'store'])->name('store');
    });

    //Supplier
    Route::group(['prefix' => 'supplier', 'as' => 'supplier.'], function () {
    Route::get('/show', [SupplierController::class, 'show'])->name('show');
    Route::post('/list', [SupplierController::class, 'list'])->name('list');
    Route::get('statement/{id}', [SupplierController::class, 'statement'])->name('statement');
    Route::get('create', [SupplierController::class, 'create'])->name('create');
    Route::post('store', [SupplierController::class, 'store'])->name('store');
    Route::get('edit/{id}', [SupplierController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [SupplierController::class, 'update'])->name('update');
    Route::post('delete', [SupplierController::class, 'delete'])->name('delete');
    });

    //Expense Category
    Route::group(['prefix' => 'expense-category', 'as' => 'expenseCategory.'], function () {
    Route::get('/show', [ExpenseCategoryController::class, 'show'])->name('show');
    Route::post('/list', [ExpenseCategoryController::class, 'list'])->name('list');
    Route::get('create', [ExpenseCategoryController::class, 'create'])->name('create');
    Route::post('store', [ExpenseCategoryController::class, 'store'])->name('store');
    Route::get('edit/{id}', [ExpenseCategoryController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [ExpenseCategoryController::class, 'update'])->name('update');
    Route::post('delete', [ExpenseCategoryController::class, 'delete'])->name('delete');
    });

    //Expense
    Route::group(['prefix' => 'expense', 'as' => 'expense.'], function () {
    Route::get('/show', [ExpenseController::class, 'show'])->name('show');
    Route::get('/report', [ExpenseController::class, 'report'])->name('report');
    Route::get('/report/pdf', [ExpenseController::class, 'downloadPdf'])->name('report.pdf');
    Route::get('/report/excel', [ExpenseController::class, 'downloadExcel'])->name('report.excel');
    Route::post('/list', [ExpenseController::class, 'list'])->name('list');
    Route::get('create', [ExpenseController::class, 'create'])->name('create');
    Route::post('store', [ExpenseController::class, 'store'])->name('store');
    Route::get('edit/{id}', [ExpenseController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [ExpenseController::class, 'update'])->name('update');
    Route::post('delete', [ExpenseController::class, 'delete'])->name('delete');
    });

    //Accounts & Financial Management
    Route::group(['prefix' => 'account', 'as' => 'account.'], function () {
        Route::get('/show', [AccountController::class, 'show'])->name('show');
        Route::post('/list', [AccountController::class, 'list'])->name('list');
        Route::get('/banks', [AccountController::class, 'banks'])->name('banks');
        Route::post('/deposit', [AccountController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw', [AccountController::class, 'withdraw'])->name('withdraw');
        Route::get('create', [AccountController::class, 'create'])->name('create');
        Route::post('store', [AccountController::class, 'store'])->name('store');
        Route::get('edit/{id}', [AccountController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [AccountController::class, 'update'])->name('update');
        Route::post('delete', [AccountController::class, 'delete'])->name('delete');
    });

    Route::group(['prefix' => 'accounting', 'as' => 'accounting.'], function () {
    Route::get('/journal', [AccountingController::class, 'journal'])->name('journal');
    Route::get('/journal/create', [AccountingController::class, 'createJournal'])->name('journal.create');
    Route::post('/journal/store', [AccountingController::class, 'storeJournal'])->name('journal.store');
    Route::get('/transfer/create', [AccountingController::class, 'createTransfer'])->name('transfer.create');
    Route::post('/transfer/store', [AccountingController::class, 'storeTransfer'])->name('transfer.store');
    Route::post('/sync-auto-journals', [AccountingController::class, 'syncAutoJournals'])->name('syncAutoJournals');
    Route::get('/reports', [AccountingController::class, 'reports'])->name('reports');
    Route::get('/reports/excel', [AccountingController::class, 'reportsExcel'])->name('reports.excel');
    });

    //Customer
    Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => 'require.permission:customers.manage'], function () {
    Route::get('/show', [CustomerController::class, 'show'])->name('show');
    Route::post('/list', [CustomerController::class, 'list'])->name('list');
    Route::get('statement/{id}', [CustomerController::class, 'statement'])->name('statement');
    Route::get('create', [CustomerController::class, 'create'])->name('create');
    Route::post('store', [CustomerController::class, 'store'])->name('store');
    Route::get('edit/{id}', [CustomerController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [CustomerController::class, 'update'])->name('update');
    Route::post('delete', [CustomerController::class, 'delete'])->name('delete');
    });

    // Customer Prescriptions
    Route::group(['prefix' => 'customer/{customerId}/prescriptions', 'as' => 'prescription.'], function () {
        Route::get('/', [CustomerPrescriptionController::class, 'index'])->name('index');
        Route::post('/', [CustomerPrescriptionController::class, 'store'])->name('store');
        Route::delete('/{prescriptionId}', [CustomerPrescriptionController::class, 'destroy'])->name('destroy');
    });

    //Shipment Zone
    Route::group(['prefix' => 'shipment-zone', 'as' => 'shipmentZone.'], function () {
    Route::get('/show', [ShipmentZoneController::class, 'show'])->name('show');
    Route::post('/list', [ShipmentZoneController::class, 'list'])->name('list');
    Route::get('create', [ShipmentZoneController::class, 'create'])->name('create');
    Route::post('store', [ShipmentZoneController::class, 'store'])->name('store');
    Route::get('edit/{id}', [ShipmentZoneController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [ShipmentZoneController::class, 'update'])->name('update');
    Route::post('delete', [ShipmentZoneController::class, 'delete'])->name('delete');
    });

    //Setting
    Route::group(['prefix' => 'setting', 'as' => 'setting.'], function () {
    Route::get('/show', [SettingController::class, 'show'])->name('show');
    Route::post('/list', [SettingController::class, 'list'])->name('list');
    Route::get('create', [SettingController::class, 'create'])->name('create');
    Route::post('store', [SettingController::class, 'store'])->name('store');
    Route::get('edit/{id}', [SettingController::class, 'edit'])->name('edit');
    Route::post('update/{id}', [SettingController::class, 'update'])->name('update');
    Route::post('delete', [SettingController::class, 'delete'])->name('delete');
    });

    //Batch
    Route::group(['prefix' => 'batch', 'as' => 'batch.'], function () {
        Route::get('/preview/{id}', [BatchController::class, 'preview'])->name('preview');
        Route::post('variationList', [BatchController::class, 'variationList'])->name('variationList');
        Route::post('/selectSkuId', [BatchController::class, 'selectSkuId'])->name('selectSkuId');
        Route::post('/store', [BatchController::class, 'store'])->name('store');
        Route::post('/list', [BatchController::class, 'list'])->name('list');
        // Route::get('/show', [BatchController::class, 'show'])->name('show');
        
        // Route::post('/all', [BatchController::class, 'allPurchaseList'])->name('all');
    });

    //Order
    Route::group(['prefix' => 'order', 'as' => 'order.', 'middleware' => 'require.permission:orders.view'], function () {
        Route::get('/show', [OrderController::class, 'show'])->name('show');
        Route::post('/list', [OrderController::class, 'list'])->name('list');
        Route::get('/details/{id}', [OrderController::class, 'details'])->name('details');
        Route::get('/invoice/{id}', [OrderController::class, 'invoice'])->name('invoice');
        Route::get('/invoice/thermal/{id}', [OrderController::class, 'thermalInvoice'])->name('invoice.thermal');
        Route::post('/invoice/thermal/{id}/direct-print', [OrderController::class, 'directThermalPrint'])->name('invoice.thermal.direct-print');
        Route::get('create', [CategoryController::class, 'create'])->name('create');
        Route::post('store', [CategoryController::class, 'store'])->name('store');
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('update');
        Route::post('delete', [CategoryController::class, 'delete'])->name('delete');
    });

    //Order Status
    Route::group(['prefix' => 'orderStatus', 'as' => 'orderStatus.', 'middleware' => 'require.permission:orders.manage'], function () {
        Route::post('/getStatusModal', [OrderStatusController::class, 'show'])->name('getStatusModal');
        Route::post('/store', [OrderStatusController::class, 'store'])->name('store');
    });

    //Transaction
    Route::group(['prefix' => 'transaction', 'as' => 'transaction.', 'middleware' => 'require.permission:payments.manage'], function () {
        Route::post('/getPaymentModal', [TransactionController::class, 'show'])->name('getPaymentModal');
        Route::post('/savePayment', [TransactionController::class, 'savePayment'])->name('savePayment');
        Route::post('/list', [OrderController::class, 'list'])->name('list');
        Route::get('/details/{id}', [OrderController::class, 'details'])->name('details');
        Route::get('create', [CategoryController::class, 'create'])->name('create');
        Route::post('store', [CategoryController::class, 'store'])->name('store');
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('update');
        Route::post('delete', [CategoryController::class, 'delete'])->name('delete');
    });

    //Pos
    Route::group(['prefix' => 'pos', 'as' => 'pos.', 'middleware' => 'require.permission:pos.access'], function () {
        Route::get('/show', [PosController::class, 'show'])->name('show');
        Route::get('/skuSearch', [PosController::class, 'skuSearch'])->name('skuSearch');
        Route::get('/productDetails/{productId}', [PosController::class, 'productDetails'])->name('productDetails');
        Route::post('/completeSale', [PosController::class, 'completeSale'])->name('completeSale');
        Route::get('/invoice/thermal/{id}', [OrderController::class, 'thermalInvoice'])->name('invoice.thermal');
        Route::post('/invoice/thermal/{id}/direct-print', [OrderController::class, 'directThermalPrint'])->name('invoice.thermal.direct-print');
        Route::get('/customerLoyaltyInfo', [PosController::class, 'customerLoyaltyInfo'])->name('customerLoyaltyInfo');
        Route::get('/sku/{skuId}/boxes', [BoxStockController::class, 'forSku'])->name('sku.boxes');
    });

    // Loyalty
    Route::group(['prefix' => 'loyalty', 'as' => 'loyalty.'], function () {
        Route::get('/settings', [LoyaltyController::class, 'settingsShow'])->name('settings');
        Route::post('/settings', [LoyaltyController::class, 'settingsSave'])->name('settings.save');
        Route::get('/adjust', [LoyaltyController::class, 'adjustShow'])->name('adjust');
        Route::post('/adjust', [LoyaltyController::class, 'adjustStore'])->name('adjust.store');
        Route::get('/report', [LoyaltyController::class, 'reportShow'])->name('report');
        Route::get('/report/list', [LoyaltyController::class, 'reportList'])->name('report.list');
        Route::get('/report/excel', [LoyaltyController::class, 'reportExcel'])->name('report.excel');
    });

    //Pos
    Route::group(['prefix' => 'posCustomer', 'as' => 'posCustomer.', 'middleware' => 'require.permission:pos.access'], function () {
        Route::get('/customerSearch', [PosCustomerController::class, 'customerSearch'])->name('customerSearch');
        Route::post('/store', [PosCustomerController::class, 'store'])->name('store');
        Route::post('/updateNote/{id}', [PosCustomerController::class, 'updateNote'])->name('updateNote');
    });

    //Cart
    Route::group(['prefix' => 'cart', 'as' => 'cart.', 'middleware' => 'require.permission:pos.access'], function () {
        Route::post('/addToCart', [CartController::class, 'addToCart'])->name('addToCart');
        Route::post('/updateCart', [CartController::class, 'updateCart'])->name('updateCart');
        Route::post('/updateItemDiscount', [CartController::class, 'updateItemDiscount'])->name('updateItemDiscount');
        Route::post('/removeCartItem', [CartController::class, 'removeCartItem'])->name('removeCartItem');
        Route::post('/clearCart', [CartController::class, 'clearCart'])->name('clearCart');  
        Route::post('/applyConditions', [CartController::class, 'applyConditions'])->name('applyConditions');  
    });

    //Report
    Route::group(['prefix' => 'sales', 'as' => 'sales.', 'middleware' => 'require.permission:reports.sales.view'], function () {
        Route::get('/show', [SalesReportController::class, 'show'])->name('show');
        Route::post('/list', [SalesReportController::class, 'list'])->name('list');
        Route::get('/excel', [SalesReportController::class, 'excel'])->name('excel');
    });

    Route::group(['prefix' => 'customer-sales', 'as' => 'customerSales.', 'middleware' => 'require.permission:reports.customers.view'], function () {
        Route::get('/show', [CustomerController::class, 'report'])->name('show');
        Route::post('/list', [CustomerController::class, 'reportList'])->name('list');
        Route::get('/excel', [CustomerController::class, 'reportExcel'])->name('excel');
    });

    Route::group(['prefix' => 'review', 'as' => 'review.'], function () {
        Route::get('/show', [ReviewController::class, 'show'])->name('show');
        Route::post('/list', [ReviewController::class, 'list'])->name('list');
        Route::post('/approve', [ReviewController::class, 'approve'])->name('approve');
    });

    Route::group(['prefix' => 'stocks', 'as' => 'stocks.', 'middleware' => 'require.permission:reports.inventory.view'], function () {
        Route::get('/show', [StocksController::class, 'show'])->name('show');
        Route::post('/list', [StocksController::class, 'list'])->name('list');
        Route::post('/summary', [StocksController::class, 'summary'])->name('summary');
        Route::get('/excel', [StocksController::class, 'excel'])->name('excel');
    });

    Route::group(['prefix' => 'inventory-transaction', 'as' => 'inventoryTransaction.', 'middleware' => 'require.permission:reports.inventory.view'], function () {
        Route::get('/show', [InventoryTransactionController::class, 'show'])->name('show');
        Route::post('/list', [InventoryTransactionController::class, 'list'])->name('list');
        Route::get('/excel', [InventoryTransactionController::class, 'excel'])->name('excel');
    });

    // Extended Reports
    Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
        Route::get('/product-sales',       [ReportController::class, 'productSalesShow'])->name('productSales.show');
        Route::post('/product-sales/list', [ReportController::class, 'productSalesList'])->name('productSales.list');
        Route::get('/product-sales/excel', [ReportController::class, 'productSalesExcel'])->name('productSales.excel');

        Route::get('/purchase',            [ReportController::class, 'purchaseShow'])->name('purchase.show');
        Route::post('/purchase/list',      [ReportController::class, 'purchaseList'])->name('purchase.list');
        Route::get('/purchase/excel',      [ReportController::class, 'purchaseExcel'])->name('purchase.excel');

        Route::get('/supplier-due',        [ReportController::class, 'supplierDueShow'])->name('supplierDue.show');
        Route::post('/supplier-due/list',  [ReportController::class, 'supplierDueList'])->name('supplierDue.list');
        Route::get('/supplier-due/excel',  [ReportController::class, 'supplierDueExcel'])->name('supplierDue.excel');

        Route::get('/customer-due',        [ReportController::class, 'customerDueShow'])->name('customerDue.show');
        Route::post('/customer-due/list',  [ReportController::class, 'customerDueList'])->name('customerDue.list');
        Route::get('/customer-due/excel',  [ReportController::class, 'customerDueExcel'])->name('customerDue.excel');

        Route::get('/low-stock',           [ReportController::class, 'lowStockShow'])->name('lowStock.show');
        Route::post('/low-stock/list',     [ReportController::class, 'lowStockList'])->name('lowStock.list');
        Route::get('/low-stock/excel',     [ReportController::class, 'lowStockExcel'])->name('lowStock.excel');

        Route::get('/expiry',              [ReportController::class, 'expiryShow'])->name('expiry.show');
        Route::post('/expiry/list',        [ReportController::class, 'expiryList'])->name('expiry.list');
        Route::get('/expiry/excel',        [ReportController::class, 'expiryExcel'])->name('expiry.excel');

        Route::get('/commission',          [ReportController::class, 'commissionShow'])->name('commission.show');
        Route::post('/commission/data',    [ReportController::class, 'commissionData'])->name('commission.data');
        Route::get('/commission/excel',    [ReportController::class, 'commissionExcel'])->name('commission.excel');

        Route::get('/branch-stock',        [ReportController::class, 'branchStockShow'])->name('branchStock.show');
        Route::post('/branch-stock/list',  [ReportController::class, 'branchStockList'])->name('branchStock.list');
        Route::get('/branch-stock/excel',  [ReportController::class, 'branchStockExcel'])->name('branchStock.excel');

        Route::get('/carton-box',          [ReportController::class, 'cartonBoxShow'])->name('cartonBox.show');
        Route::post('/carton-box/list',    [ReportController::class, 'cartonBoxList'])->name('cartonBox.list');
        Route::get('/carton-box/excel',    [ReportController::class, 'cartonBoxExcel'])->name('cartonBox.excel');

        Route::get('/stockout-prediction',       [ReportController::class, 'stockoutPredictionShow'])->name('stockout.show');
        Route::post('/stockout-prediction/list', [ReportController::class, 'stockoutPredictionList'])->name('stockout.list');
        Route::get('/stockout-prediction/excel', [ReportController::class, 'stockoutPredictionExcel'])->name('stockout.excel');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => 'require.permission:users.manage'], function () {
        Route::get('/show', [UserController::class, 'show'])->name('show');
        Route::post('/list', [UserController::class, 'list'])->name('list');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('store', [UserController::class, 'store'])->name('store');
        Route::get('edit/{id}', [UserController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('delete', [UserController::class, 'delete'])->name('delete');
    });

    Route::group(['prefix' => 'role', 'as' => 'role.', 'middleware' => 'require.permission:roles.manage'], function () {
        Route::get('/show', [RoleController::class, 'show'])->name('show');
        Route::post('/list', [RoleController::class, 'list'])->name('list');
        Route::get('create', [RoleController::class, 'create'])->name('create');
        Route::post('store', [RoleController::class, 'store'])->name('store');
        Route::get('edit/{id}', [RoleController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [RoleController::class, 'update'])->name('update');
        Route::post('delete', [RoleController::class, 'delete'])->name('delete');
    });

    Route::group(['prefix' => 'permission', 'as' => 'permission.', 'middleware' => 'require.permission:permissions.manage'], function () {
        Route::get('/show', [PermissionController::class, 'show'])->name('show');
        Route::post('/list', [PermissionController::class, 'list'])->name('list');
        Route::get('create', [PermissionController::class, 'create'])->name('create');
        Route::post('store', [PermissionController::class, 'store'])->name('store');
        Route::get('edit/{id}', [PermissionController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [PermissionController::class, 'update'])->name('update');
        Route::post('delete', [PermissionController::class, 'delete'])->name('delete');
    });

    Route::group(['prefix' => 'staff', 'as' => 'staff.', 'middleware' => 'require.permission:staff.manage'], function () {
        Route::get('/show', [StaffController::class, 'show'])->name('show');
        Route::post('/list', [StaffController::class, 'list'])->name('list');
        Route::get('create', [StaffController::class, 'create'])->name('create');
        Route::post('store', [StaffController::class, 'store'])->name('store');
        Route::get('edit/{id}', [StaffController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [StaffController::class, 'update'])->name('update');
        Route::post('delete', [StaffController::class, 'delete'])->name('delete');
        Route::post('update-commission', [StaffController::class, 'updateCommission'])->name('updateCommission');
    });

    Route::group(['prefix' => 'sales-commission-plan', 'as' => 'salesCommissionPlan.', 'middleware' => 'require.permission:commission_plans.manage'], function () {
        Route::get('/show', [SalesCommissionPlanController::class, 'show'])->name('show');
        Route::post('/list', [SalesCommissionPlanController::class, 'list'])->name('list');
        Route::get('create', [SalesCommissionPlanController::class, 'create'])->name('create');
        Route::post('store', [SalesCommissionPlanController::class, 'store'])->name('store');
        Route::get('edit/{id}', [SalesCommissionPlanController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [SalesCommissionPlanController::class, 'update'])->name('update');
        Route::post('delete', [SalesCommissionPlanController::class, 'delete'])->name('delete');
    });


});
