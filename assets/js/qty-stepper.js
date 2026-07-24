/**
 * Quantity Stepper - Handles +/- buttons for WooCommerce quantity inputs.
 * Uses event delegation for dynamic HTMX-swapped content.
 */
(function () {
    document.addEventListener("click", function (evt) {
        var btn = evt.target.closest(".qty-change");
        if (!btn) return;

        var action = btn.getAttribute("data-action");
        if (action !== "plus" && action !== "minus") return;

        var container = btn.closest(".quantity");
        if (!container) return;

        var input = container.querySelector('input[type="number"]');
        if (!input) return;

        var min = parseFloat(input.getAttribute("min")) || 1;
        var max = parseFloat(input.getAttribute("max"));
        var step = parseFloat(input.getAttribute("step")) || 1;
        var val = parseFloat(input.value) || min;

        if (action === "plus") {
            val += step;
            if (!isNaN(max) && val > max) val = max;
        } else {
            val -= step;
            if (val < min) val = min;
        }

        input.value = val;
        input.dispatchEvent(new Event("change", { bubbles: true }));
    });
})();
