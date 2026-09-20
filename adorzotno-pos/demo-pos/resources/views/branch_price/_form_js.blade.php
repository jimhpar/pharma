<script>
    function formatBranchPriceDisplay(value) {
        if (value === null || value === undefined || value === '') {
            return 'N/A';
        }

        return Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function refreshSkuOptions() {
        const productId = document.getElementById('product_id')?.value || '';
        const skuSelect = document.getElementById('sku_id');

        if (!skuSelect) {
            return;
        }

        const selectedSkuId = skuSelect.value;
        let hasSelectedVisibleOption = false;

        Array.from(skuSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const matchesProduct = !productId || option.dataset.productId === productId;
            option.hidden = !matchesProduct;

            if (option.value === selectedSkuId && matchesProduct) {
                hasSelectedVisibleOption = true;
            }
        });

        if (!hasSelectedVisibleOption && productId) {
            skuSelect.value = '';
        }
    }

    function refreshDefaultPricePanel() {
        const skuSelect = document.getElementById('sku_id');

        if (!skuSelect) {
            return;
        }

        const selectedOption = skuSelect.options[skuSelect.selectedIndex];

        document.getElementById('defaultRetailPrice').textContent = formatBranchPriceDisplay(selectedOption?.dataset.defaultRetail);
        document.getElementById('defaultWholesalePrice').textContent = formatBranchPriceDisplay(selectedOption?.dataset.defaultWholesale);
        document.getElementById('defaultMinimumPrice').textContent = formatBranchPriceDisplay(selectedOption?.dataset.defaultMinimum);
        document.getElementById('defaultOnlinePrice').textContent = formatBranchPriceDisplay(selectedOption?.dataset.defaultOnline);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const productSelect = document.getElementById('product_id');
        const skuSelect = document.getElementById('sku_id');

        if (!productSelect || !skuSelect) {
            return;
        }

        productSelect.addEventListener('change', function () {
            refreshSkuOptions();
            refreshDefaultPricePanel();
        });

        skuSelect.addEventListener('change', function () {
            const selectedOption = skuSelect.options[skuSelect.selectedIndex];
            if (selectedOption?.dataset.productId && productSelect.value !== selectedOption.dataset.productId) {
                productSelect.value = selectedOption.dataset.productId;
                refreshSkuOptions();
            }

            refreshDefaultPricePanel();
        });

        refreshSkuOptions();
        refreshDefaultPricePanel();
    });
</script>
