(function () {
    'use strict';

    function initTabs() {
        const tabItems = document.querySelectorAll('.tab-item');
        tabItems.forEach(function (tab) {
            // avoid initializing twice
            if (tab.dataset.tabInit === '1') return;

            tab.addEventListener('click', function () {
                // Get the parent tab-container so it only affects sibling tabs
                const container = this.closest('.tab-container');
                if (!container) return;

                const containerTabs = container.querySelectorAll('.tab-item');
                const containerContents = container.querySelectorAll('.tab-content');

                // Remove active from all tabs and contents within this container
                containerTabs.forEach(t => t.classList.remove('active'));
                containerContents.forEach(c => c.classList.remove('active'));

                // Activate clicked tab and its matching content
                this.classList.add('active');

                // Prefer data-tab on tab-content inside the same container, then fallback to id
                let target = container.querySelector('.tab-content[data-tab="' + this.dataset.tab + '"]');
                if (!target) target = document.getElementById(this.dataset.tab);
                if (target) target.classList.add('active');
            });

            // mark initialized and ensure focusable
            tab.dataset.tabInit = '1';
            if (!tab.hasAttribute('tabindex')) tab.setAttribute('tabindex', '0');
        });
    }

    document.addEventListener('DOMContentLoaded', initTabs);
    // Re-initialize when page fragments are loaded via page-switcher
    window.addEventListener('page:loaded', function () { setTimeout(initTabs, 10); });
})();