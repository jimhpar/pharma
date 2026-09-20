<script>
let currentProductDetails = null;
let selectedSkuId = null;
let selectedSaleUnit = 'strip';

const productDetailsUrlTemplate = @json(route('pos.productDetails', ['productId' => '__PRODUCT_ID__']));
const productSearchUrl = @json(route('pos.skuSearch'));
const orderInvoiceUrlTemplate = @json(route('order.invoice', ['id' => '__ORDER_ID__']));
const orderThermalInvoiceUrlTemplate = @json(route('pos.invoice.thermal', ['id' => '__ORDER_ID__']));
const posBatchesUrlTemplate = @json(route('pos.sku.batches', ['skuId' => '__SKU_ID__']));
const posBoxesUrlTemplate = @json(route('pos.sku.boxes', ['skuId' => '__SKU_ID__']));
const alternativeMedicinesUrl = @json(route('pos.alternativeMedicines'));
const posWarehouseId = @json($warehouse?->id);
const currencyCode = @json(\App\Support\Currency::code());
const PAYMENT_METHOD_OPTIONS = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer'];
const POS_PRODUCTS_PER_PAGE = 20;
let currentProductPage = 1;
let lastProductPage = 1;
let hasKnownProductLastPage = false;
let isProductLoading = false;
let activeProductQuery = '';
let activeBrandId = '';
let posSelectedCats = [];
let selectedBoxId = null;
let selectedBatchId = null;
let _boxPickerCallback = null;

function formatCurrency(value) {
    const amount = Number(String(value ?? 0).replace(/,/g, '')) || 0;

    return `${currencyCode} ${amount.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function formatLineTotalHtml(value) {
    const amount = Number(String(value ?? 0).replace(/,/g, '')) || 0;
    const formattedAmount = amount.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    return `<span class="ci-line-currency">${currencyCode}</span><span class="ci-line-amount">${formattedAmount}</span>`;
}

function openInvoicePreview(orderId, autoPrint = false) {
    const modalElement = document.getElementById('invoicePreviewModal');
    const frame = document.getElementById('invoicePreviewFrame');

    if (!modalElement || !frame || !orderId) {
        return;
    }

    document.body.classList.add('invoice-preview-open');

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const invoiceUrl = orderThermalInvoiceUrlTemplate.replace('__ORDER_ID__', orderId) + '?embed=1';

    frame.onload = function () {
        if (autoPrint) {
            setTimeout(() => {
                try {
                    frame.contentWindow?.focus();
                    frame.contentWindow?.print();
                } catch (error) {
                    console.error('Unable to auto print invoice preview.', error);
                }
            }, 250);
        }

        frame.onload = null;
    };

    frame.src = invoiceUrl;
    modal.show();
}

function reserveReceiptPreviewWindow() {
    const previewWindow = window.open('', 'posReceiptPreview', 'width=420,height=900,left=120,top=40,scrollbars=yes,resizable=yes');

    if (!previewWindow) {
        return null;
    }

    previewWindow.document.write(`
        <!doctype html>
        <html>
            <head>
                <title>Loading Receipt</title>
                <style>
                    body {
                        margin: 0;
                        min-height: 100vh;
                        display: grid;
                        place-items: center;
                        font-family: Arial, sans-serif;
                        color: #111827;
                        background: #f8fafc;
                    }
                </style>
            </head>
            <body>Loading receipt...</body>
        </html>
    `);
    previewWindow.document.close();

    return previewWindow;
}

function openReceiptPreview(orderId, previewWindow = null) {
    if (!orderId) {
        return;
    }

    const invoiceUrl = orderThermalInvoiceUrlTemplate.replace('__ORDER_ID__', orderId);
    const targetWindow = previewWindow || window.open('', 'posReceiptPreview', 'width=420,height=900,left=120,top=40,scrollbars=yes,resizable=yes');

    if (targetWindow) {
        targetWindow.location.href = invoiceUrl;
        targetWindow.focus();
        return;
    }

    toastr.warning('Receipt preview popup was blocked. Please allow popups for this POS page.');
}

window.setDeliveryChargeFromZone = function(shipmentZoneId = '', fallbackCharge = null) {
    const zoneSelect = document.getElementById('shipment_zone_id');
    const deliveryInput = document.getElementById('delivery_charge');

    if (!zoneSelect || !deliveryInput) {
        return;
    }

    if (shipmentZoneId !== '' && shipmentZoneId !== null && shipmentZoneId !== undefined) {
        zoneSelect.value = String(shipmentZoneId);
    }

    const selectedOption = zoneSelect.options[zoneSelect.selectedIndex];
    let deliveryCharge = 0;

    if (selectedOption && selectedOption.value) {
        deliveryCharge = parseFloat(selectedOption.dataset.charge || '0') || 0;
    } else if (fallbackCharge !== null && fallbackCharge !== undefined) {
        deliveryCharge = parseFloat(fallbackCharge) || 0;
    }

    deliveryInput.value = deliveryCharge > 0 ? deliveryCharge.toFixed(2) : '';
    updateCartTotal();
};

function refreshCartPanel() {
    $("#cart-container").load(location.href + " #cart-container > *", function () {
        updateCartTotal();
    });
    $("#total-cart-items").load(location.href + " #total-cart-items");
}

function cleanupModalBackdrop() {
    if ($('.modal.show').length > 0) {
        return;
    }

    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css({
        overflow: '',
        paddingRight: ''
    });
}

function closeVariantSelectorModal() {
    const modalElement = document.getElementById('variantSelectorModal');
    if (!modalElement) {
        cleanupModalBackdrop();
        return;
    }

    const modal = bootstrap.Modal.getInstance(modalElement) || bootstrap.Modal.getOrCreateInstance(modalElement, {
        backdrop: false
    });
    modal.hide();
    setTimeout(cleanupModalBackdrop, 250);
}

function getGrandTotalAmount() {
    return parseFloat($('#grand_total_amount').val()) || 0;
}

function buildPaymentRow(method = 'Cash', amount = '', autoFill = false) {
    const optionsHtml = PAYMENT_METHOD_OPTIONS.map((option) => `
        <option value="${option}" ${option === method ? 'selected' : ''}>${option}</option>
    `).join('');

    return `
        <div class="payment-row" data-auto-fill="${autoFill ? '1' : '0'}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="mb-1">Method</label>
                    <select class="form-select payment-method-select">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-8 col-md-5">
                    <label class="mb-1">Amount</label>
                    <input type="number" class="form-control payment-amount-input" min="0" step="0.01" value="${amount}">
                </div>
                <div class="col-4 col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-payment-row">Remove</button>
                </div>
            </div>
        </div>
    `;
}

function appendPaymentRow(method = 'Cash', amount = '', autoFill = false) {
    $('#paymentRows').append(buildPaymentRow(method, amount, autoFill));
    if (typeof initSelect2 === 'function') initSelect2($('#paymentRows').children().last()[0]);
    syncPaymentState();
}

function resetPaymentRows(useGrandTotal = true) {
    const grandTotal = getGrandTotalAmount();
    const defaultAmount = useGrandTotal && grandTotal > 0 ? grandTotal.toFixed(2) : '';

    $('#paymentRows').html(buildPaymentRow('Cash', defaultAmount, true));
    if (typeof initSelect2 === 'function') initSelect2($('#paymentRows')[0]);
    syncPaymentState();
}

function collectPaymentEntries() {
    return $('.payment-row').map(function () {
        const amount = parseFloat($(this).find('.payment-amount-input').val()) || 0;

        if (amount <= 0) {
            return null;
        }

        return {
            method: $(this).find('.payment-method-select').val(),
            amount: Number(amount.toFixed(2)),
        };
    }).get();
}

function getPaymentMethodSummary(entries = collectPaymentEntries()) {
    const uniqueMethods = [...new Set(entries.map((entry) => entry.method).filter(Boolean))];

    if (uniqueMethods.length === 0) {
        return '';
    }

    if (uniqueMethods.length === 1) {
        return uniqueMethods[0];
    }

    return `Mixed (${uniqueMethods.join(' + ')})`;
}

function syncPaymentState() {
    const grandTotal = getGrandTotalAmount();
    const paymentEntries = collectPaymentEntries();
    const paidNow = paymentEntries.reduce((carry, entry) => carry + entry.amount, 0);
    const dueAmount = Math.max(0, grandTotal - paidNow);

    $('#payment_method').val(getPaymentMethodSummary(paymentEntries));
    $('#payment-total-display').text(formatCurrency(paidNow));
    $('#payment-due-display')
        .text(formatCurrency(dueAmount))
        .toggleClass('is-due', dueAmount > 0.009);
}

function syncSingleAutoFillPayment(previousGrandTotal, newGrandTotal) {
    const rows = $('.payment-row');

    if (rows.length !== 1) {
        return;
    }

    const row = rows.first();
    const input = row.find('.payment-amount-input');
    const currentValue = parseFloat(input.val()) || 0;
    const autoFillEnabled = row.data('auto-fill') === 1 || row.attr('data-auto-fill') === '1';

    if (autoFillEnabled) {
        if (Math.abs(currentValue - previousGrandTotal) > 0.009 && currentValue !== 0) {
            // user changed amount manually — only cap if it now exceeds new total
            if (currentValue > newGrandTotal + 0.009) {
                input.val(newGrandTotal > 0 ? newGrandTotal.toFixed(2) : '');
            }
            return;
        }
        input.val(newGrandTotal > 0 ? newGrandTotal.toFixed(2) : '');
    } else {
        // auto-fill off (user typed an amount) — still cap if it exceeds new total
        if (currentValue > newGrandTotal + 0.009) {
            input.val(newGrandTotal > 0 ? newGrandTotal.toFixed(2) : '');
        }
    }
}

function capPaymentsToGrandTotal(grandTotal) {
    let totalPaid = 0;
    $('.payment-row .payment-amount-input').each(function () {
        totalPaid += parseFloat($(this).val()) || 0;
    });

    if (totalPaid <= grandTotal + 0.009) return;

    // reduce from the last row downward until we're within total
    const rows = $('.payment-row').get().reverse();
    let excess = Math.round((totalPaid - grandTotal) * 100) / 100;

    $(rows).each(function () {
        if (excess <= 0) return false;
        const input = $(this).find('.payment-amount-input');
        const amt   = parseFloat(input.val()) || 0;
        const cut   = Math.min(amt, excess);
        input.val((amt - cut) > 0 ? (amt - cut).toFixed(2) : '');
        excess = Math.round((excess - cut) * 100) / 100;
    });
}

function fillRemainingPaymentAmount() {
    const grandTotal = getGrandTotalAmount();
    const rows = $('.payment-row');

    if (rows.length === 0) {
        resetPaymentRows(true);
        return;
    }

    let collectedBeforeLastRow = 0;
    rows.each(function (index) {
        if (index === rows.length - 1) {
            return false;
        }

        collectedBeforeLastRow += parseFloat($(this).find('.payment-amount-input').val()) || 0;
    });

    const remainingAmount = Math.max(0, grandTotal - collectedBeforeLastRow);
    const lastRow = rows.last();
    lastRow.attr('data-auto-fill', '0');
    lastRow.find('.payment-amount-input').val(remainingAmount > 0 ? remainingAmount.toFixed(2) : '');
    syncPaymentState();
}

function updateProductCount(count) {
    const countLabel = document.getElementById('productCountLabel');
    const countBadge = document.getElementById('productCountBadge');
    const safeCount = Number(count) || 0;
    const labelText = `${safeCount} Found`;
    const badgeText = `${safeCount} item${safeCount === 1 ? '' : 's'}`;

    if (countLabel) {
        countLabel.textContent = labelText;
    }

    if (countBadge) {
        countBadge.textContent = badgeText;
    }
}

function setFullscreenButtonState() {
    const button = document.getElementById('toggleFullscreenBtn');
    if (!button) {
        return;
    }

    button.textContent = document.fullscreenElement ? 'Exit Full Screen' : 'Full Screen';
}

function setFocusModeButtonState() {
    const button = document.getElementById('toggleFocusModeBtn');
    const app = document.getElementById('app');

    if (!button || !app) {
        return;
    }

    button.textContent = app.classList.contains('pos-focus-mode') ? 'Exit Focus Mode' : 'Focus Mode';
}

function toggleFocusMode() {
    const app = document.getElementById('app');

    if (!app) {
        return;
    }

    const isEnteringFocusMode = !app.classList.contains('pos-focus-mode');

    if (isEnteringFocusMode) {
        app.dataset.focusPreviousSidebarState = app.classList.contains('sidebar-collapsed') ? '1' : '0';

        if (typeof window.setSidebarCollapsed === 'function') {
            window.setSidebarCollapsed(true, false, 'pos-focus');
        }

        app.classList.add('pos-focus-mode');
    } else {
        app.classList.remove('pos-focus-mode');

        if (typeof window.setSidebarCollapsed === 'function') {
            window.setSidebarCollapsed(app.dataset.focusPreviousSidebarState === '1', false, 'pos-focus');
        }

        delete app.dataset.focusPreviousSidebarState;
    }

    setFocusModeButtonState();
}

function renderProductCard(product, _searchQuery) {
    const q = _searchQuery !== undefined ? String(_searchQuery || '') : activeProductQuery;
    const thumbnailHtml = product.thumbnail_url
        ? `<img src="${product.thumbnail_url}" alt="${escapeHtml(product.name)}">`
        : `<span class="product-thumb-fallback">No Image</span>`;
    const searchAttr = (product.name + ' ' + (product.card_badge || '')).toLowerCase();

    const pharmaParts = [product.dosage_form, product.strength, product.coating_type].filter(Boolean);
    const pharmaStr   = pharmaParts.join(' · ');

    const isOutOfStock = product.available_stock <= 0;

    const nameHtml    = highlightText(product.name, q);
    const genericHtml = product.generic_name ? highlightText(product.generic_name, q) : '';
    const pharmaHtml  = pharmaStr ? highlightText(pharmaStr, q) : '';

    return `
    <div class="" data-search="${searchAttr}" onclick="openProductSelection(${product.id}, ${product.action_sku_id ?? 'null'}, ${product.is_variant_product ? 'true' : 'false'}, ${product.is_medicine ? 'true' : 'false'})">
        <div class="product-card${isOutOfStock ? ' out-of-stock' : ''}">
            <div class="product-thumb">
                ${thumbnailHtml}
            </div>
            <div class="product-card-body">
                <h3 class="product-card-title line-clamp-2">${nameHtml}</h3>
                ${genericHtml ? `<div class="pos-generic-name">${genericHtml}</div>` : ''}
                ${pharmaHtml  ? `<div class="pos-pharma-sub">${pharmaHtml}</div>`  : ''}
                <hr class="pc-divider">
                <div class="product-card-meta">
                    <div class="product-card-code-row">
                        <span class="rx-badge">${escapeHtml(product.card_badge)}</span>
                    </div>
                    <p class="product-card-category mb-0">${escapeHtml(product.category_name ?? 'Uncategorized')} | ${escapeHtml(product.brand_name ?? 'No Brand')}</p>
                </div>
                <div class="product-card-stats">
                    <p class="small fw-semibold mb-0">${escapeHtml(product.price_label)}</p>
                    <span class="product-card-stock">Stock: ${product.available_stock}</span>
                </div>
                <div class="product-card-footer">
                    <span class="product-action-chip">${product.is_variant_product ? 'Choose Options' : 'Tap To Add'}</span>
                    <span class="product-card-sku-count">${product.sku_count} SKU</span>
                </div>
            </div>
        </div>
    </div>`;
}

let _searchDebounce = null;
let _ajaxRequest    = null;

function setProductLoadingState(isLoading, appendMode = false) {
    isProductLoading = isLoading;
    const loadMoreButton = document.getElementById('loadMoreProductsBtn');
    const loadingIndicator = document.getElementById('productLoadingIndicator');

    if (loadMoreButton) {
        loadMoreButton.disabled = isLoading;
        loadMoreButton.textContent = appendMode && isLoading ? 'Loading...' : 'Load More';
    }

    if (loadingIndicator) {
        loadingIndicator.classList.toggle('d-none', !isLoading || appendMode);
    }
}

function updateProductPager(meta = {}, appendMode = false) {
    currentProductPage = Number(meta.current_page || 1);
    lastProductPage = Number(meta.last_page || 1);
    hasKnownProductLastPage = true;

    const loadMoreWrap = document.getElementById('loadMoreProductsWrap');
    const pageSummary = document.getElementById('productPageSummary');

    if (pageSummary) {
        if (Number(meta.total || 0) === 0) {
            pageSummary.textContent = 'No products found';
        } else {
            pageSummary.textContent = `Showing ${meta.from || 0}-${meta.to || 0} of ${meta.total || 0}`;
        }
    }

    if (loadMoreWrap) {
        const hasMorePages = Boolean(meta.has_more_pages);
        loadMoreWrap.classList.toggle('d-none', !hasMorePages && !appendMode);
        if (!hasMorePages) {
            loadMoreWrap.classList.add('d-none');
        }
    }

    updateProductCount(meta.total || 0);
}

function renderProductsFromData(products, meta = {}, appendMode = false) {
    // Clear any previous alternatives section (only on fresh loads)
    if (!appendMode) {
        $('#alternativeMedicinesSection').remove();
    }

    if (!appendMode && products.length === 0) {
        const q = $('#skuSearch').val().trim();
        $('#skuList').html('<p class="text-muted col-12 py-3">No products found.</p>');
        updateProductPager(meta, false);
        if (q) loadAlternativeMedicines({ q, excludeIds: [] });
        return;
    }

    const html = products.map(product => renderProductCard(product)).join('');
    if (appendMode) {
        $('#skuList').append(html);
    } else {
        $('#skuList').html(html);
    }

    updateProductPager(meta, appendMode);

    // After fresh render, check if all results are out of stock
    if (!appendMode) {
        const q = $('#skuSearch').val().trim();
        const allOutOfStock = products.length > 0 && products.every(p => p.available_stock <= 0);
        if (allOutOfStock && q) {
            const genericNames = [...new Set(products.map(p => p.generic_name).filter(Boolean))];
            const excludeIds   = products.map(p => p.id);
            loadAlternativeMedicines({ q, genericNames, excludeIds });
        }
    }
}

let _altRequest = null;
function loadAlternativeMedicines({ q = '', genericNames = [], excludeIds = [] }) {
    if (_altRequest) _altRequest.abort();
    _altRequest = $.ajax({
        url: alternativeMedicinesUrl,
        type: 'GET',
        data: { q, generic_names: genericNames, exclude_ids: excludeIds },
        success: function (response) {
            const alts = response.data || [];
            if (!alts.length) return;
            renderAlternativeMedicines(alts, genericNames);
        },
        complete: function () { _altRequest = null; }
    });
}

function renderAlternativeMedicines(products, genericNames) {
    $('#alternativeMedicinesSection').remove();

    const cards = products.map(p => renderProductCard(p)).join('');
    const label = genericNames.length ? genericNames.join(', ') : 'Same Generic';

    const section = `
    <div id="alternativeMedicinesSection" class="col-12 mt-3">
        <div class="alt-med-header">
            <span class="alt-med-icon"><i class="bi bi-capsule"></i></span>
            <div>
                <div class="alt-med-title">Alternative Generic Medicines</div>
                <div class="alt-med-sub">Generic: <strong>${escapeHtml(label)}</strong> &mdash; available substitutes</div>
            </div>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 mt-1">
            ${cards}
        </div>
    </div>`;

    const skuListEl = document.getElementById('skuList');
    if (skuListEl) skuListEl.insertAdjacentHTML('afterend', section);
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function highlightText(text, query) {
    if (!query || !text) return escapeHtml(text || '');
    const safe = escapeHtml(text);
    const pattern = String(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return safe.replace(new RegExp('(' + pattern + ')', 'gi'), '<mark class="pos-hl">$1</mark>');
}

function loadProducts(page = 1, appendMode = false) {
    activeProductQuery = $('#skuSearch').val().trim();
    activeBrandId = $('#brandFilter').val();

    if (_ajaxRequest) {
        _ajaxRequest.abort();
    }

    setProductLoadingState(true, appendMode);

    _ajaxRequest = $.ajax({
        url: productSearchUrl,
        type: 'GET',
        data: {
            q: activeProductQuery,
            category_ids: posSelectedCats,
            brand_id: activeBrandId,
            page: page,
            per_page: POS_PRODUCTS_PER_PAGE
        },
        success: function (response) {
            renderProductsFromData(response.data || [], response.meta || {}, appendMode);
        },
        error: function (xhr, status) {
            if (status === 'abort') {
                return;
            }

            $('#skuList').html('<p class="text-danger col-12 py-3 mb-0">Unable to load products right now.</p>');
            $('#loadMoreProductsWrap').addClass('d-none');
            $('#productPageSummary').text('Load failed');
        },
        complete: function () {
            setProductLoadingState(false, appendMode);
            _ajaxRequest = null;
        }
    });
}

function queueProductReload() {
    clearTimeout(_searchDebounce);
    _searchDebounce = setTimeout(function () {
        loadProducts(1, false);
    }, 250);
}

function openProductSelection(productId, actionSkuId, isVariantProduct, isMedicineProduct = false) {
    if (actionSkuId && !isMedicineProduct) {
        fetchBatches(actionSkuId, function (batches) {
            if (!batches.length) {
                addToCart(actionSkuId);
            } else {
                _boxPickerCallback = function (batchId, boxId) { addToCart(actionSkuId, false, 'unit', boxId, batchId); };
                openBoxPickerModal(batches, actionSkuId);
            }
        });
        return;
    }

    if (!isVariantProduct && !isMedicineProduct) {
        return;
    }

    $.ajax({
        url: productDetailsUrlTemplate.replace('__PRODUCT_ID__', productId),
        type: 'GET',
        data: {
            q: $('#skuSearch').val()
        },
        success: function (response) {
            const hasSellableSku = (response.skus || []).some((sku) => Number(sku.available_stock) > 0);
            if (!hasSellableSku) {
                toastr.error('Stock not available for this product.');
                cleanupModalBackdrop();
                return;
            }

            currentProductDetails = response;
            selectedSkuId = null;
            selectedSaleUnit = response.skus?.[0]?.is_medicine ? 'strip' : 'unit';

            applyPreferredVariantSelection();
            renderVariantSelector();

            const modalElement = document.getElementById('variantSelectorModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement, {
                backdrop: false
            });
            modal.show();
        },
        error: function () {
            toastr.error('Unable to load product variations.');
        }
    });
}

function applyPreferredVariantSelection() {
    if (!currentProductDetails) {
        return;
    }

    const preferredSku = currentProductDetails.skus.find((sku) => sku.id === currentProductDetails.preferred_sku_id);

    if (preferredSku && Number(preferredSku.available_stock) > 0) {
        selectedSkuId = preferredSku.id;
        return;
    }

    const firstAvailableSku = currentProductDetails.skus.find((sku) => Number(sku.available_stock) > 0);
    selectedSkuId = firstAvailableSku?.id ?? null;
}

function getAllSkus() {
    return currentProductDetails?.skus ?? [];
}

function getSelectedSku() {
    if (!currentProductDetails) {
        return null;
    }

    return currentProductDetails.skus.find((sku) => sku.id === selectedSkuId) ?? null;
}

function renderSkuCombinationList() {
    const skus = getAllSkus().filter((sku) => Number(sku.available_stock) > 0);
    const currentSelectedSkuId = selectedSkuId;

    if (skus.length === 0) {
        return `
        <div class="variant-summary">
            <div class="small text-muted">No sellable combination is available for this product right now.</div>
        </div>
    `;
    }

    if (skus.length === 1) {
        return '';
    }

    return `
        <div class="variant-group">
            <div class="variant-label">Available Combinations</div>
            <div class="variant-options">
                ${skus.map((sku) => {
                    return `
                        <button type="button"
                            class="variant-option-btn variant-sku-btn ${currentSelectedSkuId === sku.id ? 'active' : ''}"
                            data-sku-id="${sku.id}">
                            <span class="variant-option-title">${sku.combination_label || sku.product_code}</span>
                            <span class="variant-option-meta">${sku.product_code} | Stock: ${sku.available_stock} | ${formatCurrency(sku.display_price)}</span>
                        </button>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

function renderVariantSummary() {
    const selectedSku = getSelectedSku();

    if (!selectedSku) {
        return `
        <div class="variant-summary">
            <div class="small text-muted">Choose one available combination to continue.</div>
        </div>`;
    }

    const variationText = (selectedSku.variation_values ?? [])
        .map((variation) => `${variation.type}: ${variation.value}`)
        .join(' | ');
    const isMedicine = Boolean(selectedSku.is_medicine);
    const activePrice = selectedSaleUnit === 'medicine'
        ? Number(selectedSku.medicine_unit_price || 0)
        : Number(selectedSku.display_price || 0);
    const activeLabel = isMedicine && selectedSaleUnit === 'medicine' ? 'Per Medicine' : (isMedicine ? 'Per Strip' : 'Unit');

    return `
    <div class="variant-summary">
        <div class="fw-semibold">${selectedSku.product_code}</div>
        <div class="small text-muted">${variationText || 'Single SKU'}</div>
        ${isMedicine ? `
            <div class="sale-unit-grid">
                <button type="button" class="sale-unit-btn ${selectedSaleUnit === 'strip' ? 'active' : ''}" data-sale-unit="strip">
                    Strip
                    <small>${selectedSku.units_per_strip || 1} pcs | ${formatCurrency(selectedSku.display_price)}</small>
                </button>
                <button type="button" class="sale-unit-btn ${selectedSaleUnit === 'medicine' ? 'active' : ''}" data-sale-unit="medicine">
                    Medicine
                    <small>1 pc | ${formatCurrency(selectedSku.medicine_unit_price || 0)}</small>
                </button>
            </div>
        ` : ''}
        <div class="variant-price-row">
            <span>${activeLabel}: <strong>${formatCurrency(activePrice)}</strong></span>
            <span>Stock: <strong>${selectedSku.available_stock}</strong></span>
        </div>
    </div>`;
}

// ── Box Picker ────────────────────────────────────────────────────────────────

function fetchBatches(skuId, callback) {
    if (!posWarehouseId) { callback([]); return; }
    $.getJSON(
        posBatchesUrlTemplate.replace('__SKU_ID__', skuId),
        { warehouse_id: posWarehouseId },
        function (data) {
            callback(data.batches || []);
        }
    ).fail(function () { callback([]); });
}

function fetchBoxes(skuId, batchId, callback) {
    if (!posWarehouseId) { callback([]); return; }
    $.getJSON(
        posBoxesUrlTemplate.replace('__SKU_ID__', skuId),
        { warehouse_id: posWarehouseId, batch_id: batchId || '' },
        function (data) {
            const boxes = (data.boxes || []).slice().sort((a, b) => a.units_available - b.units_available);
            callback(boxes);
        }
    ).fail(function () { callback([]); });
}

function renderBatchOptions(batches) {
    return batches.map(function (batch, i) {
        const isLow = Number(batch.available_quantity || 0) <= 5;
        const meta = [
            batch.expiry_date ? 'Exp: ' + batch.expiry_date : null,
            batch.received_at ? 'Received: ' + batch.received_at : null
        ].filter(Boolean).join(' | ');

        return `
            <label class="bp-item ${i === 0 ? 'bp-active' : ''}">
                <input type="radio" name="batch_pick" value="${batch.id}" ${i === 0 ? 'checked' : ''}>
                <div class="bp-body">
                    <div class="bp-code">${escapeHtml(batch.batch_no)}</div>
                    <div class="bp-sub">${meta || 'Batch stock'}</div>
                </div>
                <div class="bp-right">
                    <span class="bp-price">${formatCurrency(batch.sale_price)}</span>
                    <span class="bp-units ${isLow ? 'bp-low' : ''}">${batch.available_quantity} stock</span>
                </div>
            </label>`;
    }).join('');
}

function renderBoxOptions(boxes) {
    return boxes.map(function (b, i) {
        const isLow = b.units_available <= 5;
        return `
            <label class="bp-item ${i === 0 ? 'bp-active' : ''}">
                <input type="radio" name="bx_pick" value="${b.id}" ${i === 0 ? 'checked' : ''}>
                <div class="bp-body">
                    <div class="bp-code">${b.box_code}</div>
                    <div class="bp-sub">${b.carton_code ? 'CTN: ' + b.carton_code + ' &middot; ' : ''}Box #${b.box_number}</div>
                </div>
                <div class="bp-right">
                    <span class="bp-units ${isLow ? 'bp-low' : ''}">${b.units_available} units</span>
                    <span class="bp-badge" style="background:${b.status_bg || '#e2e8f0'};color:${b.status_color || '#374151'}">${b.status}</span>
                </div>
            </label>`;
    }).join('');
}

function wireBoxRadios($container) {
    $container.find('input[name="bx_pick"]').on('change', function () {
        const val = $(this).val();
        selectedBoxId = val ? parseInt(val) : null;
        $container.find('.bp-item').removeClass('bp-active');
        $(this).closest('.bp-item').addClass('bp-active');
    });
}

function wireBatchRadios($container, skuId) {
    $container.find('input[name="batch_pick"]').on('change', function () {
        const val = $(this).val();
        selectedBatchId = val ? parseInt(val) : null;
        $container.find('.bp-item').removeClass('bp-active');
        $(this).closest('.bp-item').addClass('bp-active');
        loadBoxesForSelectedBatch(skuId, $container.find('.batch-box-section'));
    });
}

function loadBoxesForSelectedBatch(skuId, $section) {
    selectedBoxId = null;
    if (!skuId || !selectedBatchId || !$section.length) { return; }

    $section.html('<div class="vbs-loading"><span class="spinner-border spinner-border-sm"></span> Loading boxes...</div>');

    fetchBoxes(skuId, selectedBatchId, function (boxes) {
        if (!boxes.length) {
            $section.html('');
            return;
        }

        selectedBoxId = boxes[0].id;
        $section.html(`
            <div class="vbs-label mt-2">
                <i class="bi bi-box-seam"></i> Optional Box
                <small>from selected batch</small>
            </div>
            <div class="bp-list">${renderBoxOptions(boxes)}</div>
        `);
        wireBoxRadios($section);
    });
}

function loadVariantBatches() {
    const sku = getSelectedSku();
    const $section = $('#variantBoxSection');
    selectedBoxId = null;
    selectedBatchId = null;

    if (!sku || !posWarehouseId) { $section.html(''); return; }

    $section.html('<div class="vbs-loading"><span class="spinner-border spinner-border-sm"></span> Loading batch stock...</div>');

    fetchBatches(sku.id, function (batches) {
        if (!batches.length) { $section.html(''); return; }

        selectedBatchId = batches[0].id;

        $section.html(`
            <div class="vbs-block">
                <div class="vbs-label">
                    <i class="bi bi-layers"></i> Sell From Batch
                    <small>first expiring batch pre-selected</small>
                </div>
                <div class="bp-list">${renderBatchOptions(batches)}</div>
                <div class="batch-box-section"></div>
            </div>
        `);

        wireBatchRadios($section, sku.id);
        loadBoxesForSelectedBatch(sku.id, $section.find('.batch-box-section'));
    });
}

function openBoxPickerModal(batches, skuId) {
    selectedBatchId = batches.length ? batches[0].id : null;
    selectedBoxId = null;

    const $body = $('#boxPickerBody');
    $body.html(
        batches.length
            ? `<div class="bp-list">${renderBatchOptions(batches)}</div><div class="batch-box-section mt-2"></div>`
            : '<p class="text-muted text-center py-3">No batch stock found.</p>'
    );
    wireBatchRadios($body, skuId);
    loadBoxesForSelectedBatch(skuId, $body.find('.batch-box-section'));

    bootstrap.Modal.getOrCreateInstance(
        document.getElementById('boxPickerModal'), { backdrop: false }
    ).show();
}

// ─────────────────────────────────────────────────────────────────────────────

function renderVariantSelector() {
    if (!currentProductDetails) {
        return;
    }

    const body = document.getElementById('variantSelectorBody');
    const selectedSku = getSelectedSku();

    body.innerHTML = `
        <div class="variant-product-head">
            <div class="variant-product-thumb">
                ${currentProductDetails.thumbnail_url
                    ? `<img src="${currentProductDetails.thumbnail_url}" alt="${currentProductDetails.name}">`
                    : `<span class="product-thumb-fallback">No Image</span>`}
            </div>
            <div>
                <div class="variant-product-title">${currentProductDetails.name}</div>
                <div class="variant-product-meta">${currentProductDetails.category_name ?? 'Uncategorized'} | ${currentProductDetails.brand_name ?? 'No Brand'}</div>
            </div>
        </div>
        ${renderSkuCombinationList()}
        ${renderVariantSummary()}
        <div id="variantBoxSection"></div>
    `;

    const addButton = document.getElementById('variantAddToCartBtn');
    addButton.disabled = !selectedSku || Number(selectedSku.available_stock) <= 0;

    loadVariantBatches();
}

$(document).ready(function () {
    /* ── Category fdd dropdown ─────────────────── */
    (function () {
        var $btn    = $('#posDdCatBtn');
        var $panel  = $('#posDdCatPanel');
        var $search = $('#posCatSearch');
        var $opts   = $('#posCatOptions .fdd-option');
        var $noOpts = $('#posCatOptions .fdd-no-opts');
        var $count  = $('#posCatCount');

        function openCatDd()  { $('.fdd-panel').not($panel).removeClass('open'); $('.fdd-btn').not($btn).removeClass('is-open'); $panel.addClass('open'); $btn.addClass('is-open'); $search.focus(); }
        function closeCatDd() { $panel.removeClass('open'); $btn.removeClass('is-open'); }

        $btn.on('click', function (e) { e.stopPropagation(); $panel.hasClass('open') ? closeCatDd() : openCatDd(); });

        $search.on('input', function () {
            var q = $(this).val().toLowerCase().trim();
            var vis = 0;
            $opts.each(function () { var show = !q || $(this).data('search').includes(q); $(this).toggle(show); if (show) vis++; });
            $noOpts.toggleClass('d-none', vis > 0);
        });

        $opts.on('click', function () {
            var val = String($(this).data('value'));
            var idx = posSelectedCats.indexOf(val);
            if (idx === -1) posSelectedCats.push(val); else posSelectedCats.splice(idx, 1);
            $(this).toggleClass('checked', idx === -1);
            updateCatBtnState();
            loadProducts(1, false);
            renderPosChips();
        });

        $('#posCatClear').on('click', function () {
            posSelectedCats = [];
            $opts.removeClass('checked');
            updateCatBtnState();
            loadProducts(1, false);
            renderPosChips();
        });

        function updateCatBtnState() {
            var n = posSelectedCats.length;
            $count.text(n).toggleClass('d-none', n === 0);
            $btn.toggleClass('has-value', n > 0);
            if (n === 0) {
                $('#posCatLabel').text('Category');
            } else if (n === 1) {
                var txt = $('#posCatOptions .fdd-option[data-value="' + posSelectedCats[0] + '"]').data('text') || 'Category';
                $('#posCatLabel').text(txt);
            } else {
                $('#posCatLabel').text(n + ' selected');
            }
        }

        $(document).on('click', function (e) { if (!$(e.target).closest('#posDdCat').length) closeCatDd(); });
    })();

    /* ── Search ──────────────────────────────── */
    $('#skuSearch').on('input', function () {
        $('#skuSearchClear').toggle(!!$(this).val());
        queueProductReload();
        renderPosChips();
    });
    $('#skuSearchClear').on('click', function () {
        $('#skuSearch').val('');
        $('#skuSearchClear').hide();
        loadProducts(1, false);
        renderPosChips();
    });

    /* ── Brand filter ───────────────────────── */
    $('#brandFilter').on('change', function () {
        $(this).toggleClass('has-value', !!$(this).val());
        loadProducts(1, false);
        renderPosChips();
    });

    /* ── Reset ───────────────────────────────── */
    $('#posFilterReset').on('click', function () {
        $('#skuSearch').val(''); $('#skuSearchClear').hide();
        $('#brandFilter').val('').removeClass('has-value');
        posSelectedCats = [];
        $('#posDdCatPanel .fdd-option').removeClass('checked');
        $('#posDdCatBtn').removeClass('has-value');
        $('#posCatCount').addClass('d-none');
        $('#posCatLabel').text('Category');
        $('#posCatSearch').val('');
        $('#posDdCatPanel .fdd-option').show();
        $('#posDdCatPanel .fdd-no-opts').addClass('d-none');
        loadProducts(1, false);
        renderPosChips();
    });

    function renderPosChips() {
        var c = [];
        if ($('#skuSearch').val().trim()) c.push('Search: "' + $('#skuSearch').val().trim() + '"');
        posSelectedCats.forEach(function (v) {
            var txt = $('#posDdCatPanel .fdd-option[data-value="' + v + '"]').data('text') || v;
            c.push('Cat: ' + txt);
        });
        if ($('#brandFilter').val()) c.push('Brand: ' + $('#brandFilter option:selected').text());
        $('#posActiveBar').toggleClass('d-none', !c.length);
        $('#posActiveChips').html(c.map(function (x) { return '<span class="a-chip" style="font-size:11px;padding:3px 6px 3px 10px">' + x + '</span>'; }).join(''));
    }
    resetPaymentRows(true);

    $('#loadMoreProductsBtn').on('click', function () {
        if (isProductLoading || (hasKnownProductLastPage && currentProductPage >= lastProductPage)) {
            return;
        }

        loadProducts(currentProductPage + 1, true);
    });

    $(document).on('click', '.variant-sku-btn', function () {
        selectedSkuId = Number($(this).data('sku-id'));
        selectedSaleUnit = getSelectedSku()?.is_medicine ? 'strip' : 'unit';
        selectedBoxId = null;
        renderVariantSelector();
    });

    $(document).on('click', '.sale-unit-btn', function () {
        selectedSaleUnit = $(this).data('sale-unit') || 'strip';
        renderVariantSelector();
    });

    $(document).on('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        if ($('#boxPickerModal').hasClass('show')) {
            event.preventDefault();
            const confirmBtn = document.getElementById('boxPickerConfirmBtn');
            if (confirmBtn && !confirmBtn.disabled) confirmBtn.click();
            return;
        }

        if (!$('#variantSelectorModal').hasClass('show')) {
            return;
        }

        event.preventDefault();
        const addButton = document.getElementById('variantAddToCartBtn');
        if (addButton && !addButton.disabled) {
            addButton.click();
        }
    });

    $(document).on('input change', '.payment-amount-input, .payment-method-select', function () {
        $(this).closest('.payment-row').attr('data-auto-fill', '0');
        syncPaymentState();
    });

    $(document).on('click', '.remove-payment-row', function () {
        $(this).closest('.payment-row').remove();

        if ($('.payment-row').length === 0) {
            resetPaymentRows(false);
            return;
        }

        syncPaymentState();
    });

    $('#addPaymentRowBtn').on('click', function () {
        appendPaymentRow('Cash', '', false);
    });

    $('#fillRemainingPaymentBtn').on('click', function () {
        fillRemainingPaymentAmount();
    });

    $('#resetPaymentsBtn').on('click', function () {
        resetPaymentRows(true);
    });

    $('#variantAddToCartBtn').on('click', function () {
        const selectedSku = getSelectedSku();
        if (!selectedSku) {
            return;
        }

        $(this).prop('disabled', true);
        addToCart(selectedSku.id, true, selectedSku.is_medicine ? selectedSaleUnit : 'unit', selectedBoxId, selectedBatchId);
    });

    $('#variantSelectorModal').on('hidden.bs.modal', function () {
        $('#variantAddToCartBtn').prop('disabled', !getSelectedSku());
        cleanupModalBackdrop();
    });

    // Box picker modal handlers
    $(document).on('click', '#boxPickerConfirmBtn', function () {
        const cb = _boxPickerCallback;
        _boxPickerCallback = null;
        bootstrap.Modal.getInstance(document.getElementById('boxPickerModal'))?.hide();
        setTimeout(function () { cleanupModalBackdrop(); if (cb) cb(selectedBatchId, selectedBoxId); }, 250);
    });

    $('#boxPickerModal').on('hide.bs.modal', function () {
        // User dismissed without confirming — cancel pending action
        if (_boxPickerCallback) {
            _boxPickerCallback = null;
        }
    });

    $('#boxPickerModal').on('hidden.bs.modal', function () {
        cleanupModalBackdrop();
    });

    $('#invoicePrintBtn').on('click', function () {
        const frame = document.getElementById('invoicePreviewFrame');

        if (!frame?.contentWindow) {
            return;
        }

        frame.contentWindow.focus();
        frame.contentWindow.print();
    });

    $('#invoicePreviewModal').on('hidden.bs.modal', function () {
        const frame = document.getElementById('invoicePreviewFrame');
        document.body.classList.remove('invoice-preview-open');

        if (frame) {
            frame.src = 'about:blank';
        }
    });

    $('#toggleFullscreenBtn').on('click', async function () {
        const workspace = document.getElementById('posWorkspace');

        if (!workspace) {
            return;
        }

        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await workspace.requestFullscreen();
            }
        } catch (error) {
            toastr.error('Full screen mode is not available in this browser.');
        }

        setFullscreenButtonState();
    });

    document.addEventListener('fullscreenchange', setFullscreenButtonState);
    $('#toggleFocusModeBtn').on('click', function () {
        toggleFocusMode();
    });

    loadProducts(1, false);
    setFullscreenButtonState();
    setFocusModeButtonState();
});

// Add to cart
function addToCart(skuId, closeModal = false, saleUnit = 'unit', boxId = null, batchId = null) {
    let quantity = 1;
    $.ajax({
        type: 'POST',
        url: "{{ route('cart.addToCart') }}",
        data: {
            _token: '{{ csrf_token() }}',
            sku: skuId,
            quantity: quantity,
            sale_unit: saleUnit,
            batch_id: batchId || '',
            box_id: boxId || '',
        },
        success: function(response) {
            if (closeModal) {
                closeVariantSelectorModal();
            }

            toastr.success(response.message || 'Added to cart successfully');
            refreshCartPanel();
            $('#sub_total').val(response.total);
            $('#base-subtotal').val(response.total);
            updateCartTotal();
        },
        error: function(xhr) {
            $('#variantAddToCartBtn').prop('disabled', false);
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error('Something went wrong. Please try again.');
            }
        }
    });
}

//Update Quantity
function updateQuantity(skuId, change) {
    var inputSelector = '#quantity_' + skuId;
    var currentQuantity = parseInt($(inputSelector).val());
    var newQuantity = currentQuantity + change;
    if (newQuantity < 1) return;
    $(inputSelector).val(newQuantity);

    $.ajax({
        type: 'post',
        url: "{{route('cart.updateCart')}}",
        data: {
            _token: '{{ csrf_token() }}',
            sku: skuId,
            quantity: newQuantity
        },
        success: function(response) {
            toastr.success('Quantity updated successfully');
            $('#cart-subtotal').text(response.subtotal);
            $('#sub_total').val(response.subtotal);
            $('#base-subtotal').val(response.subtotal);
            $('.cart-price-total[data-sku="' + skuId + '"]').html(formatLineTotalHtml(response.itemTotal));
            const discountInput = $('#discount_' + skuId);
            if (discountInput.length && response.discount !== undefined) {
                discountInput.val(response.discount);
                discountInput.data('previous', response.discount);
                discountInput.attr('data-discount-amount', response.discount_amount || '0.00');
            }
            updateCartTotal();
        },
        error: function(response) {
            toastr.error(response.responseJSON.message || 'Stock not available');
            $(inputSelector).val(currentQuantity);
        }
    });
}

function updateItemDiscount(skuId) {
    const inputSelector = '#discount_' + skuId;
    const input = $(inputSelector);
    const previousDiscount = parseFloat(input.data('previous') || input.val()) || 0;
    const discount = parseFloat(input.val()) || 0;
    const discountType = $('#discount_type_' + skuId).val() || 'amount';

    $.ajax({
        type: 'post',
        url: "{{ route('cart.updateItemDiscount') }}",
        data: {
            _token: '{{ csrf_token() }}',
            sku: skuId,
            discount: discount,
            discount_type: discountType
        },
        success: function(response) {
            input.val(response.discount);
            input.data('previous', response.discount);
            input.attr('data-discount-amount', response.discount_amount || '0.00');
            $('#discount_type_' + skuId).val(response.discount_type || 'amount');
            $('.cart-price-total[data-sku="' + skuId + '"]').html(formatLineTotalHtml(response.itemTotal));
            toastr.success('Product discount updated');
            updateCartTotal();
        },
        error: function(response) {
            toastr.error(response.responseJSON?.message || 'Discount update failed');
            input.val(previousDiscount.toFixed(2));
        }
    });
}

//Remove item
function removeCartItem(skuId) {
    if (confirm("Are you sure to delete the cart")) {
        $.ajax({
            type: 'post',
            url: "{{ route('cart.removeCartItem') }}",
            data: {
                _token: '{{ csrf_token() }}',
                sku: skuId,
            },
            success: function(response) {
                toastr.success('Cart item removed successfully');
                $('.cart-item[data-id="' + skuId + '"]').remove();
                refreshCartPanel();
                $('#sub_total').val(response.subtotal);
                $('#base-subtotal').val(response.subtotal);
                updateCartTotal();
            },
            error: function(response) {
                toastr.error(response.responseJSON?.message || 'Error occurred');
            }
        });
    }
}

//Clear Cart
$(document).on('click', '#clearCartBtn', function(e) {
    e.preventDefault();
    if (!confirm("Are you sure you want to clear the entire cart?")) return;
    $.ajax({
        type: 'POST',
        url: "{{ route('cart.clearCart') }}",
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            toastr.success('Cart cleared successfully');
            $('#cart-subtotal').text('0.00');
            $('#grand-total').text(formatCurrency(0));
            $('#payment-total-display').text(formatCurrency(0));
            $('#payment-due-display').text(formatCurrency(0)).removeClass('is-due');
            $('#grand_total_amount').val('0.00');
            $('#base-subtotal').val('0.00');
            $('#sub_total').val(0.0);
            $('#delivery_charge').val('');
            $('#order_discount').val('');
            $('#order_discount_type').val('amount');
            $('#vat_percent').val(getDefaultVatPercent());
            $('#vat_amount').val('0');
            resetPaymentRows(false);
            refreshCartPanel();
            updateCartTotal();
        },
        error: function(response) {
            toastr.error('Failed to clear cart.');
        }
    });
});

// Update Total
function getDefaultVatPercent() {
    return "{{ $vatRule ? number_format((float) $vatRule->rate_percent, 2, '.', '') : '0' }}";
}

function getItemDiscountTotal() {
    let total = 0;

    $('.ci-discount-input').each(function () {
        total += parseFloat($(this).attr('data-discount-amount') || '0') || 0;
    });

    return Math.round(total * 100) / 100;
}

function calculateOrderDiscountAmount(baseAmount) {
    const value = parseFloat($('#order_discount').val()) || 0;
    const type = $('#order_discount_type').val() || 'amount';

    if (type === 'percent') {
        return Math.round(baseAmount * value / 100 * 100) / 100;
    }

    return Math.round(value * 100) / 100;
}

function updateCartTotal() {
    let baseSubtotal = parseFloat($('#sub_total').val()) || 0;
    let delivery_charge = parseFloat($('#delivery_charge').val()) || 0;
    let item_discount_total = getItemDiscountTotal();
    let order_discount_base = Math.max(0, baseSubtotal - item_discount_total);
    let order_discount = calculateOrderDiscountAmount(order_discount_base);
    let vat_percent = parseFloat($('#vat_percent').val()) || 0;
    let vat_is_inclusive = parseInt($('#vat_is_inclusive').val(), 10) === 1 ? 1 : 0;
    const previousGrandTotal = getGrandTotalAmount();

    if (($('#order_discount_type').val() || 'amount') === 'percent' && (parseFloat($('#order_discount').val()) || 0) > 100) {
        alert("Discount percent cannot be greater than 100!");
        order_discount = 0;
        $('#order_discount').val('0');
    }

    if (order_discount > order_discount_base) {
        alert("Discount cannot be greater than subtotal after item discounts!");
        order_discount = 0;
        $('#order_discount').val('0');
    }

    const taxableAmount = Math.max(0, order_discount_base - order_discount);
    const vat_amount = vat_is_inclusive
        ? Math.round((taxableAmount - (taxableAmount / (1 + (vat_percent / 100)))) * 100) / 100
        : Math.round(taxableAmount * vat_percent / 100 * 100) / 100;
    $('#vat_amount').val(vat_amount.toFixed(2));

    $.ajax({
        url: "{{ route('cart.applyConditions') }}",
        type: "POST",
        data: {
            delivery_charge: delivery_charge,
            order_discount: order_discount,
            order_discount_type: $('#order_discount_type').val() || 'amount',
            order_discount_value: parseFloat($('#order_discount').val()) || 0,
            vat_amount: vat_amount,
            vat_is_inclusive: vat_is_inclusive,
            _token: "{{ csrf_token() }}"
        },
        success: function(response) {
            const nextGrandTotal = parseFloat(response.total) || 0;
            $('#grand_total_amount').val(nextGrandTotal.toFixed(2));
            $('#grand-total').text(formatCurrency(nextGrandTotal));
            syncSingleAutoFillPayment(previousGrandTotal, nextGrandTotal);
            capPaymentsToGrandTotal(nextGrandTotal);
            syncPaymentState();
        }
    });
}

$(document).ready(function () {
    updateCartTotal();

    $(document).on('change', '#shipment_zone_id', function () {
        window.setDeliveryChargeFromZone($(this).val());
    });

    $(document).on('input change', '#delivery_charge, #order_discount, #order_discount_type, #vat_percent', function () {
        updateCartTotal();
    });

    // Sale Note toggle
    $(document).on('click', '#toggleSaleNoteBtn', function () {
        const box   = document.getElementById('saleNoteBox');
        const label = document.getElementById('saleNoteToggleLabel');
        const icon  = this.querySelector('i');
        if (!box) return;
        const isOpen = box.style.display !== 'none';
        box.style.display  = isOpen ? 'none' : 'block';
        label.textContent  = isOpen ? 'Add Sale Note' : 'Remove Note';
        icon.className     = isOpen ? 'fas fa-pen-to-square' : 'fas fa-times';
        this.style.background = isOpen ? '#f7f9fc' : '#fff3cd';
        this.style.borderColor = isOpen ? '#c8d8e8' : '#f0c040';
        this.style.color   = isOpen ? '#6b7c93' : '#856404';
        if (!isOpen) setTimeout(() => document.getElementById('customer_note')?.focus(), 50);
        else if (document.getElementById('customer_note')) document.getElementById('customer_note').value = '';
    });
});

let isCompletingSale = false;

function completeSale(saleType = 'Sale') {
    if (isCompletingSale) {
        return;
    }

    $('#sale_type').val(saleType);
    const shouldOpenPrint = !['Draft', 'Suspend'].includes(saleType);
    const paymentEntries = collectPaymentEntries();
    const grandTotal = getGrandTotalAmount();
    const paidNow = paymentEntries.reduce((carry, entry) => carry + entry.amount, 0);

    let cartCount = $("#cart-container .cart-item").length;

    if (cartCount <= 0) {
        toastr.warning("Cannot place order. Cart is empty.");
        return;
    }

    if (paidNow - grandTotal > 0.009) {
        toastr.warning("Collected payment cannot exceed the invoice total.");
        return;
    }

    const confirmMessage = `${saleType === 'Sale' ? 'Complete Sale' : saleType} for ${formatCurrency(grandTotal)}?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    const receiptPreviewWindow = shouldOpenPrint ? reserveReceiptPreviewWindow() : null;

    isCompletingSale = true;

    let data = {
        _token: "{{ csrf_token() }}",
        customer_id: $("#customer_id").val(),
        shipment_zone_id: $("#shipment_zone_id").val(),
        delivery_charge: $("#delivery_charge").val(),
        order_discount: calculateOrderDiscountAmount(Math.max(0, (parseFloat($('#sub_total').val()) || 0) - getItemDiscountTotal())),
        order_discount_type: $("#order_discount_type").val(),
        order_discount_value: $("#order_discount").val(),
        vat_amount: $("#vat_amount").val(),
        vat_is_inclusive: $("#vat_is_inclusive").val(),
        payment_method: getPaymentMethodSummary(paymentEntries),
        payments: paymentEntries,
        sale_type: saleType,
        redeemed_points: parseInt($("#redeemed_points").val(), 10) || 0,
        customer_note: $("#customer_note").val() || '',
    };

    $.post("{{ route('pos.completeSale') }}", data)
        .done(function (response) {
            const successMessage = saleType === 'Sale'
                ? 'Order placed successfully'
                : `${saleType} saved successfully`;

            toastr.success(successMessage);
            refreshCartPanel();
            $("#customer_id").val('');
            $("#customerSearch").val('');
            $("#shipment_zone_id").val('');
            $("#delivery_charge").val('');
            $("#order_discount").val('');
            $("#order_discount_type").val('amount');
            $("#vat_percent").val(getDefaultVatPercent());
            $("#vat_amount").val('0');
            $("#grand-total").text(formatCurrency(0));
            $("#payment-total-display").text(formatCurrency(0));
            $("#payment-due-display").text(formatCurrency(0)).removeClass('is-due');
            $("#grand_total_amount").val('0.00');
            $("#base-subtotal").val('0.00');
            $('#sub_total').val('0.00');
            $('#sale_type').val('Sale');
            $("#selectedCustomer").html('');
            $("#customer_note").val('');
            const saleNoteBox = document.getElementById('saleNoteBox');
            const saleNoteBtn = document.getElementById('toggleSaleNoteBtn');
            const saleNoteLabel = document.getElementById('saleNoteToggleLabel');
            if (saleNoteBox) saleNoteBox.style.display = 'none';
            if (saleNoteBtn) { saleNoteBtn.style.background='#f7f9fc'; saleNoteBtn.style.borderColor='#c8d8e8'; saleNoteBtn.style.color='#6b7c93'; }
            if (saleNoteLabel) saleNoteLabel.textContent = 'Add Sale Note';
            if (typeof hideCustomerExtraPanel === 'function') hideCustomerExtraPanel();
            $("#payment_method").val('Cash');
            resetPaymentRows(false);

            // Reset loyalty panel after successful sale
            $('#redeemed_points').val('0');
            $('#loyaltyPanel').addClass('d-none');

            if (shouldOpenPrint) {
                if (response.sales_order?.id) {
                    openReceiptPreview(response.sales_order.id, receiptPreviewWindow);
                } else if (receiptPreviewWindow && !receiptPreviewWindow.closed) {
                    receiptPreviewWindow.close();
                }
            }
        })
        .fail(function (xhr) {
            if (receiptPreviewWindow && !receiptPreviewWindow.closed) {
                receiptPreviewWindow.close();
            }

            toastr.error(xhr.responseJSON?.error || "Error completing sale");
        })
        .always(function () {
            isCompletingSale = false;
        });
}

// Submit order
$(document).on("click", ".sale-action-btn", function (e) {
    e.preventDefault();
    completeSale($(this).data('sale-type') || 'Sale');
});

$(document).on('submit', '#checkout-form', function (e) {
    e.preventDefault();
    completeSale('Sale');
});

$(document).on('keydown', function (e) {
    if (e.key !== 'Enter' || e.shiftKey || e.ctrlKey || e.altKey || e.metaKey) {
        return;
    }

    // #skuSearch Enter is handled separately below
    if ($('.modal.show').length > 0 || $(e.target).is('textarea, #skuSearch')) {
        return;
    }

    e.preventDefault();
    $(e.target).trigger('change');
    completeSale('Sale');
});

// ── Barcode / SKU Search → Enter to Add to Cart ──────────────────────────────
let _searchEnterRequest = null;

$('#skuSearch').on('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    e.stopPropagation();

    const q = $(this).val().trim();
    if (!q) return;

    // Abort any pending debounce + in-flight request
    clearTimeout(_searchDebounce);
    if (_searchEnterRequest) { _searchEnterRequest.abort(); }

    if (_ajaxRequest) { _ajaxRequest.abort(); }

    _searchEnterRequest = $.ajax({
        url: productSearchUrl,
        type: 'GET',
        data: { q: q, per_page: 5 },
        success: function (response) {
            const products = response.data || [];

            if (products.length === 0) {
                toastr.warning('No product found for "' + q + '".');
                return;
            }

            const hit = products.length === 1 ? products[0] : products.find(function (p) {
                // Prefer exact barcode / sku_code match
                return (p.card_badge || '').toLowerCase() === q.toLowerCase();
            }) || (products.length === 1 ? products[0] : null);

            if (!hit) {
                // Multiple ambiguous results — just show them in the grid
                renderProductsFromData(products, response.meta || {}, false);
                return;
            }

            // Clear search and reload full list in background
            $('#skuSearch').val('');
            queueProductReload();

            openProductSelection(hit.id, hit.action_sku_id, hit.is_variant_product, hit.is_medicine);
        },
        error: function (xhr) {
            if (xhr.statusText === 'abort') return;
            toastr.error('Search failed. Please try again.');
        },
        complete: function () {
            _searchEnterRequest = null;
        }
    });
});

</script>
