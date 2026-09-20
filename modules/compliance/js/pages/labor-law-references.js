(function() {
    var searchInput = document.querySelector('.llr-table-search input[name="search"]');
    var searchClear = document.querySelector('.llr-table-search .llr-search-clear');

    if (!searchInput) return;

    function updateClearVisibility() {
        if (searchClear) {
            searchClear.hidden = searchInput.value.trim().length === 0;
        }
    }

    searchInput.addEventListener('input', updateClearVisibility);

    if (searchClear) {
        searchClear.addEventListener('click', function() {
            var form = searchInput.closest('form');
            if (form) {
                var pageNumInput = form.querySelector('input[name="page_num"]');
                if (pageNumInput) pageNumInput.value = '1';
                form.submit();
            }
        });
    }

    updateClearVisibility();

    var SCROLL_KEY = 'llr_scroll_' + (new URL(location).searchParams.get('page') || 'labor-compliance');

    function saveScroll() {
        var container = document.querySelector('.container');
        if (container) {
            try { sessionStorage.setItem(SCROLL_KEY, container.scrollTop); } catch (e) {}
        }
    }

    function restoreScroll() {
        var container = document.querySelector('.container');
        if (container) {
            try {
                var saved = sessionStorage.getItem(SCROLL_KEY);
                if (saved !== null) {
                    container.scrollTop = parseInt(saved, 10);
                    sessionStorage.removeItem(SCROLL_KEY);
                }
            } catch (e) {}
        }
    }

    window.addEventListener('scroll', saveScroll, { passive: true });
    window.addEventListener('beforeunload', saveScroll);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreScroll);
    } else {
        restoreScroll();
    }
})();
