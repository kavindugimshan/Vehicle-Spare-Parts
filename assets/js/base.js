/**
 * assets/js/base.js
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3, Rule 2.
 *
 * Generic, page-agnostic behaviour shared by every page: dismissing
 * flash messages, confirming destructive actions via a data attribute,
 * and a lightweight required-field check. Module-specific interaction
 * (live filtering, cart totals, image previews, etc.) belongs in each
 * module's own JS file, loaded after this one.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Dismiss flash messages manually or after a short delay.
    document.querySelectorAll('[data-flash]').forEach(function (flash) {
        var timer = setTimeout(function () {
            flash.remove();
        }, 6000);

        var closeBtn = flash.querySelector('[data-flash-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(timer);
                flash.remove();
            });
        }
    });

    // Confirm before following a link or submitting a form marked
    // data-confirm="Message to show".
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            var message = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Basic required-field validation for forms opting in with
    // data-validate, giving instant feedback before the server round trip.
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var valid = true;

            form.querySelectorAll('[required]').forEach(function (field) {
                if (!String(field.value || '').trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                event.preventDefault();
            }
        });
    });
});
