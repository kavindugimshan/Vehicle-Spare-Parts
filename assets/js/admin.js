/**
 * assets/js/admin.js - image preview, tree toggling and inline-editing
 * helpers for the admin/ pages this module owns. Delete confirmation
 * itself is already handled generically by base.js's data-confirm
 * attribute (see includes/... FROZEN, Module 1) - this file only adds
 * behaviour specific to parts/categories/requests.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Live preview of a selected part image before upload.
    document.querySelectorAll('[data-adm-image-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var preview = input.parentElement.querySelector('.adm-image-preview-target');
            if (!preview) {
                return;
            }

            var file = input.files && input.files[0];
            if (!file) {
                preview.hidden = true;
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.hidden = false;
            };
            reader.readAsDataURL(file);
        });
    });

    // Collapse/expand a category's children in the tree view.
    document.querySelectorAll('[data-adm-tree-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var li = button.closest('li');
            var children = li ? li.querySelector('.adm-tree-children') : null;

            if (!children) {
                return;
            }

            var collapsed = children.hidden;
            children.hidden = !collapsed;
            button.textContent = collapsed ? '−' : '+';
        });
    });

    // Inline stock-quantity fields (admin/parts/stock_alerts.php) submit
    // their own small form - just highlight the field as dirty so the
    // admin can see which rows they've touched before clicking Update.
    document.querySelectorAll('.adm-inline-form input[type="number"]').forEach(function (input) {
        var original = input.value;
        input.addEventListener('input', function () {
            input.classList.toggle('is-invalid', false);
            input.style.fontWeight = input.value !== original ? '700' : '';
        });
    });
});
