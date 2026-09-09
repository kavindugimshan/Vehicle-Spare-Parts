/**
 * assets/js/catalogue.js - live filtering, price slider sync and chip
 * removal for catalogue/search.php.
 *
 * Every filter control (keyword, price, size, category, brand,
 * country, sort) triggers its own live update, independent of which
 * order the customer sets them in - each update reads the FULL current
 * form state (new FormData(form)), so combining filters in any order
 * always produces the combined result, not just whichever filter was
 * touched last.
 *
 * Progressive enhancement: every control here still works as a normal
 * form submit to search.php if JS fails, since search.php and
 * ajax_search.php share the same query-string filter contract - this
 * script only intercepts that same submission to avoid the full page
 * reload.
 */

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('catFilterForm');
    var resultsContainer = document.getElementById('catResultsContainer');

    if (!form || !resultsContainer) {
        return;
    }

    var baseUrl = form.dataset.baseUrl || '';
    var priceInput = document.getElementById('price');
    var priceSlider = document.getElementById('priceSlider');
    var debounceTimer = null;

    if (priceInput && priceSlider) {
        // Setting .value in JS does NOT fire the other field's own
        // 'input' listener, so the slider needs its own explicit call
        // to searchDebounced() - without this line, dragging the
        // slider updates the number next to it but never searches.
        priceSlider.addEventListener('input', function () {
            priceInput.value = priceSlider.value;
            searchDebounced();
        });
        priceInput.addEventListener('input', function () {
            priceSlider.value = priceInput.value || 0;
        });
    }

    function runSearch(page) {
        var params = new URLSearchParams(new FormData(form));
        if (page) {
            params.set('page', page);
        } else {
            params.delete('page');
        }

        fetch(baseUrl + '/catalogue/ajax_search.php?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (data) {
                resultsContainer.innerHTML = data.cardsHtml
                    ? '<div class="cat-grid">' + data.cardsHtml + '</div>' + data.paginationHtml
                    : '<p class="text-muted">No parts matched your search.</p>';

                var countEl = document.querySelector('[data-cat-result-count]');
                if (countEl) {
                    countEl.textContent = data.total + ' part(s) found.';
                }

                var priceNote = document.querySelector('[data-cat-price-range]');
                if (priceNote) {
                    priceNote.textContent = data.priceRangeText || '';
                    priceNote.hidden = !data.priceRangeText;
                }

                bindPaginationLinks();
            })
            .catch(function () {
                // Fall back silently - the last rendered results stay on
                // screen, and a normal form submit still works.
            });
    }

    function bindPaginationLinks() {
        resultsContainer.querySelectorAll('.pagination-plain a').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                var url = new URL(link.href);
                runSearch(url.searchParams.get('page'));
            });
        });
    }

    function searchNow() {
        clearTimeout(debounceTimer);
        runSearch(null);
    }

    function searchDebounced() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            runSearch(null);
        }, 450);
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        searchNow();
    });

    // Checkboxes (brand, country) and selects (category, sort) update
    // immediately - there's no "still typing" state for those.
    form.querySelectorAll('input[type="checkbox"], select').forEach(function (field) {
        field.addEventListener('change', searchNow);
    });

    // Text/number fields (keyword, price, size) debounce slightly so a
    // search doesn't fire on every keystroke, but still update live -
    // this is what was missing before: typing a price (or size, or
    // keyword) as the LAST filter action now actually applies it,
    // instead of silently doing nothing until another field changed.
    form.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (field) {
        field.addEventListener('input', searchDebounced);
    });

    document.querySelectorAll('.cat-chip.is-removable').forEach(function (chip) {
        chip.addEventListener('click', function (event) {
            event.preventDefault();
            window.location.href = chip.href;
        });
    });

    bindPaginationLinks();
});
