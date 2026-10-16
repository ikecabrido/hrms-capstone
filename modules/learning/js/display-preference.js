/**
 * Display preferences, applied.
 *
 * index.php hands every page the signed-in employee's saved preferences as
 * window.LD_DISPLAY_PREFS (page_size + view_mode). This module is the one place
 * that acts on them:
 *
 *   page_size — a page size selector marked data-display-pref="page_size" starts
 *               on the saved length. The page's own handler is what re-renders,
 *               so the value is adopted by dispatching a change event rather than
 *               by duplicating each page's pagination code.
 *   view_mode — every grid/list toggle in the module is a [data-view] button
 *               whose own handler sets both the button and the grid. Restoring a
 *               preference therefore clicks the button for the saved view, and a
 *               user's click on any of those buttons is saved back.
 *
 * Saves post one field at a time to pages/shared/ajax/edit-display-preference.php,
 * so the grid/list toggles and the profile card cannot overwrite each other.
 *
 * Re-runs after every sidebar navigation, because the container's HTML (and the
 * toggles inside it) is replaced.
 */
(function () {
    'use strict';

    var ENDPOINT = 'pages/shared/ajax/edit-display-preference.php';

    function prefs() {
        return window.LD_DISPLAY_PREFS || { page_size: 12, view_mode: 'grid' };
    }

    function csrfToken() {
        return window.CSRF_TOKEN
            || (document.querySelector('meta[name="csrf-token"]') || {}).content
            || '';
    }

    function toast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    // Every toggle group on the page, as { button, parent } pairs.
    function viewButtons() {
        return Array.prototype.map.call(document.querySelectorAll('[data-view]'), function (button) {
            return { button: button, parent: button.parentElement };
        });
    }

    function applyViewMode() {
        var wanted = prefs().view_mode;
        if (wanted !== 'grid' && wanted !== 'list') return;

        var groups = [];
        viewButtons().forEach(function (entry) {
            if (entry.parent && groups.indexOf(entry.parent) === -1) {
                groups.push(entry.parent);
            }
        });

        groups.forEach(function (parent) {
            var target = parent.querySelector('[data-view="' + wanted + '"]');
            // Nothing to do when the wanted button is already the active one.
            if (!target || target.classList.contains('active')) return;

            var otherActive = parent.querySelector('[data-view].active');
            if (otherActive === target) return;

            // Untrusted click: the page's own handler applies it, and the save
            // listener below deliberately ignores untrusted events.
            target.click();
        });
    }

    function applyPageSize() {
        var wanted = String(prefs().page_size || '');
        if (!wanted) return;

        document.querySelectorAll('[data-display-pref="page_size"]').forEach(function (select) {
            if (select.value === wanted) return;

            select.value = wanted;
            // Lets the page's own change handler adopt the length (its local
            // page-size variable otherwise keeps the markup default).
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Card grids that pin their length in markup (the notification pages do)
        // still follow the preference: the shared pagination reads this attribute,
        // and this runs before it initialises.
        document.querySelectorAll('.cards-grid[data-page-size]').forEach(function (grid) {
            grid.dataset.pageSize = wanted;
        });
    }

    /**
     * Save one preference. Resolves with the stored preferences; rejects when the
     * server refused, so callers can put their control back.
     */
    function save(patch) {
        var body = {};

        if (patch.page_size !== undefined) body.page_size = patch.page_size;
        if (patch.view_mode !== undefined) body.view_mode = patch.view_mode;

        return fetch(ENDPOINT, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken()
            },
            body: JSON.stringify(body)
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; });
            })
            .then(function (data) {
                if (!data || data.success !== true) {
                    throw new Error((data && data.message) || 'save failed');
                }

                var stored = data.preferences || body;
                window.LD_DISPLAY_PREFS = stored;
                if (stored.page_size !== undefined) {
                    window.LD_DEFAULT_PAGE_SIZE = stored.page_size;
                }

                return stored;
            });
    }

    function setToggleState(parent, activeButton) {
        parent.querySelectorAll('[data-view], [data-display-pref-view]').forEach(function (button) {
            var isActive = button === activeButton;
            button.classList.toggle('is-active', isActive);
            if (button.hasAttribute('data-display-pref-view')) {
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            }
        });
    }

    function labelFor(button) {
        return (button.textContent || '').trim() || 'View';
    }

    // A user picked a grid layout on the catalog / e-learning / training pages.
    // Those pages update themselves; this only records the choice.
    document.addEventListener('click', function (event) {
        if (!event.isTrusted) return;

        var button = event.target.closest && event.target.closest('[data-view]');
        if (!button || !button.dataset.view) return;

        save({ view_mode: button.dataset.view }).catch(function () {
            toast('Could not save your default view.', 'error');
        });
    });

    // A user changed a rows-per-page selector.
    document.addEventListener('change', function (event) {
        if (!event.isTrusted) return;

        var select = event.target.closest && event.target.closest('[data-display-pref="page_size"]');
        if (!select) return;

        var wanted = parseInt(select.value, 10);
        if (!wanted) return;

        save({ page_size: wanted })
            .then(function (stored) {
                toast('Lists now show ' + stored.page_size + ' rows per page.', 'success');
            })
            .catch(function () {
                select.value = String(prefs().page_size || 12);
                toast('Could not save your rows per page.', 'error');
            });
    });

    // The profile card's Grid / List control, which has no page behaviour of its
    // own beyond recording the preference.
    document.addEventListener('click', function (event) {
        if (!event.isTrusted) return;

        var button = event.target.closest && event.target.closest('[data-display-pref-view]');
        if (!button) return;

        var parent = button.parentElement;
        var wanted = button.dataset.displayPrefView;
        if (!wanted) return;

        var group = parent ? Array.prototype.slice.call(parent.querySelectorAll('[data-display-pref-view]')) : [];
        var previous = group.filter(function (el) { return el.classList.contains('is-active'); });

        if (parent) setToggleState(parent, button);

        save({ view_mode: wanted })
            .then(function () {
                toast('Card grids will open in ' + labelFor(button).toLowerCase() + ' view.', 'success');
            })
            .catch(function () {
                if (parent) setToggleState(parent, previous[0] || null);
                toast('Could not save your default view.', 'error');
            });
    });

    function apply() {
        applyViewMode();
        applyPageSize();
    }

    document.addEventListener('DOMContentLoaded', apply);

    // Sidebar navigation swaps the page HTML; the new toggles need the same
    // treatment once the page's own scripts have run.
    window.addEventListener('page:loaded', function () {
        window.setTimeout(apply, 0);
    });

    // For scripts that want the same saver without a second endpoint copy.
    window.LD = window.LD || {};
    window.LD.saveDisplayPreference = save;
    window.LD.applyDisplayPreferences = apply;
})();
