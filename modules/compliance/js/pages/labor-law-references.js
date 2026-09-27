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
        try { sessionStorage.setItem(SCROLL_KEY, String(window.scrollY)); } catch (e) {}
    }

    function restoreScroll() {
        try {
            var saved = sessionStorage.getItem(SCROLL_KEY);
            if (saved !== null) {
                window.scrollTo(0, parseInt(saved, 10));
                sessionStorage.removeItem(SCROLL_KEY);
            }
        } catch (e) {}
    }

    var llrScrollTimer = null;
    window.addEventListener('scroll', function() {
        if (llrScrollTimer) return;
        llrScrollTimer = setTimeout(function() {
            llrScrollTimer = null;
            saveScroll();
        }, 100);
    }, { passive: true });
    window.addEventListener('beforeunload', saveScroll);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreScroll);
    } else {
        restoreScroll();
    }
})();
