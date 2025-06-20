/**
 * BOGO Reset Button Functionality - Buy X Get X
 * This script resets form fields on the BOGO admin settings page.
 */
document.addEventListener("DOMContentLoaded", function () {
    const resetButton = document.getElementById("custom-reset-button");

    if (resetButton) {
        resetButton.addEventListener("click", function () {

            // Reset select dropdowns to default values
            const filterType = document.getElementById("wc_bogo_filter_type");
            const discountType = document.getElementById("discount_type");

            if (filterType) filterType.value = "all_products";
            if (discountType) discountType.value = "free";

            // Clear numeric input fields
            const inputFields = document.querySelectorAll(
                "input[name='min_qty'], input[name='max_qty'], input[name='free_qty'], input[name='discount_value']"
            );
            inputFields.forEach(function (input) {
                input.value = "";
            });

            // Uncheck recursive checkbox
            const recursiveCheckbox = document.querySelector("input[name='recursive']");
            if (recursiveCheckbox) recursiveCheckbox.checked = false;

            // Clear product selection
            const selectedProductIds = document.getElementById("selected_product_ids");
            const selectedProductsDisplay = document.getElementById("bogo_selected_products");

            if (selectedProductIds) selectedProductIds.value = "";
            if (selectedProductsDisplay) selectedProductsDisplay.innerHTML = "";

            // Hide discount value input
            const discountValueInput = document.getElementById("discount_value");
            if (discountValueInput) discountValueInput.style.display = "none";

            // Uncheck all BOGO toggle switches
            const toggleSwitches = document.querySelectorAll(".bogo-toggle");
            toggleSwitches.forEach(function (toggle) {
                toggle.checked = false;
            });

        });
    }
});
