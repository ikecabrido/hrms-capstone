(function () {
    'use strict';

    const PAGE_SIZE = 12;

    function syncPageSize(select) {
        const moduleContent = select.closest('.module-content');
        if (!moduleContent) return;

        const activeTab = moduleContent.querySelector('.tab-content.active');
        const grid = activeTab && activeTab.querySelector('.cards-grid');
        const pageSize = Number(select.value || PAGE_SIZE);

        if (grid) {
            grid.dataset.pageSize = String(pageSize);
            const cards = Array.from(grid.querySelectorAll('.content-card-item'));
            setupPagination(grid, cards);
        }
    }

    function showAddTooltip(button, label) {
        if (!button) return;

        let tooltip = button.querySelector('.toolbar-add-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('span');
            tooltip.className = 'toolbar-add-tooltip';
            button.appendChild(tooltip);
        }

        tooltip.textContent = label;
        tooltip.classList.add('visible');

        clearTimeout(button._addTooltipTimer);
        button._addTooltipTimer = setTimeout(function () {
            tooltip.classList.remove('visible');
        }, 1000);
    }

    function updateAddButton(tab) {
        const toolbar = tab.closest('.module-content');
        if (!toolbar) return;

        const addButton = toolbar.querySelector('.toolbar-add-btn');
        if (!addButton) return;

        const label = tab.dataset.addLabel || 'Add Item';
        const url = tab.dataset.addUrl || '#';
        addButton.textContent = '+';
        addButton.setAttribute('href', url);
        addButton.setAttribute('aria-label', label);
        addButton.setAttribute('title', label);
        showAddTooltip(addButton, label);
    }

    function setupPagination(grid, cards) {
        if (!grid) return;

        const pageSize = Number(grid.dataset.pageSize || PAGE_SIZE);
        const paginator = grid.parentElement.querySelector('.pagination-row');
        const prevButton = paginator && paginator.querySelector('.page-btn[data-action="prev"]');
        const nextButton = paginator && paginator.querySelector('.page-btn[data-action="next"]');
        const indicator = paginator && paginator.querySelector('.page-indicator');
        let currentPage = 1;

        function syncPage() {
            const total = cards.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            cards.forEach(function (card, index) {
                card.style.display = (index >= start && index < end) ? '' : 'none';
            });

            if (indicator) {
                indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
            }

            if (prevButton) prevButton.disabled = currentPage <= 1;
            if (nextButton) nextButton.disabled = currentPage >= totalPages;
        }

        prevButton && prevButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                syncPage();
            }
        });

        nextButton && nextButton.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(cards.length / pageSize));
            if (currentPage < totalPages) {
                currentPage += 1;
                syncPage();
            }
        });

        syncPage();
    }

    function applySearchFilter(searchInput) {
        const moduleContent = searchInput.closest('.module-content');
        if (!moduleContent) return;

        const filterSelector = moduleContent.querySelector('.toolbar-filter');
        const query = (searchInput.value || '').trim().toLowerCase();
        const activeTab = moduleContent.querySelector('.tab-content.active');
        if (!activeTab) return;

        const cards = Array.from(activeTab.querySelectorAll('.content-card-item'));
        const selectedStatus = filterSelector ? filterSelector.value : 'all';

        let filteredCards = cards.filter(function (card) {
            const text = (card.textContent || '').toLowerCase();
            const status = (card.dataset.status || '').toLowerCase();
            const matchesQuery = !query || text.indexOf(query) !== -1;
            const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
            return matchesQuery && matchesStatus;
        });

        cards.forEach(function (card) {
            card.style.display = 'none';
        });

        filteredCards.forEach(function (card) {
            card.style.display = '';
        });

        const grid = activeTab.querySelector('.cards-grid');
        if (grid) {
            setupPagination(grid, filteredCards);
        }
    }

    function toggleGridView(button) {
        const moduleContent = button.closest('.module-content');
        if (!moduleContent) return;

        const activeTab = moduleContent.querySelector('.tab-content.active');
        const grid = activeTab && activeTab.querySelector('.cards-grid');
        if (!grid) return;

        const nextView = button.dataset.view === 'grid' ? 'list' : 'grid';
        button.dataset.view = nextView;
        button.textContent = nextView === 'grid' ? 'Grid' : 'List';

        grid.classList.toggle('list-view', nextView === 'list');
        const searchInput = moduleContent.querySelector('.toolbar-search input');
        if (searchInput) {
            applySearchFilter(searchInput);
        }
    }

    function initTabs() {
        const tabItems = document.querySelectorAll('.tab-item');
        tabItems.forEach(function (tab) {
            if (tab.dataset.tabInit === '1') return;

            tab.addEventListener('click', function () {
                const container = this.closest('.tab-container');
                if (!container) return;

                const containerTabs = container.querySelectorAll('.tab-item');
                const containerContents = container.querySelectorAll('.tab-content');

                containerTabs.forEach(t => t.classList.remove('active'));
                containerContents.forEach(c => c.classList.remove('active'));

                this.classList.add('active');
                updateAddButton(this);

                let target = container.querySelector('.tab-content[data-tab="' + this.dataset.tab + '"]');
                if (!target) target = document.getElementById(this.dataset.tab);
                if (target) target.classList.add('active');

                const moduleContent = this.closest('.module-content');
                const searchInput = moduleContent && moduleContent.querySelector('.toolbar-search input');
                const pageSizeSelect = moduleContent && moduleContent.querySelector('.toolbar-page-size');
                if (searchInput) {
                    applySearchFilter(searchInput);
                }
                if (pageSizeSelect) {
                    syncPageSize(pageSizeSelect);
                }
            });

            tab.dataset.tabInit = '1';
            if (!tab.hasAttribute('tabindex')) tab.setAttribute('tabindex', '0');

            if (tab.classList.contains('active')) {
                updateAddButton(tab);
            }
        });

        document.querySelectorAll('.toolbar-search input').forEach(function (input) {
            if (input.dataset.searchInit === '1') return;

            input.addEventListener('input', function () {
                applySearchFilter(this);
            });

            input.dataset.searchInit = '1';
        });

        document.querySelectorAll('.toolbar-filter').forEach(function (filter) {
            if (filter.dataset.filterInit === '1') return;

            filter.addEventListener('change', function () {
                const searchInput = this.closest('.module-content')?.querySelector('.toolbar-search input');
                if (searchInput) applySearchFilter(searchInput);
            });

            filter.dataset.filterInit = '1';
        });

        document.querySelectorAll('.toolbar-page-size').forEach(function (select) {
            if (select.dataset.pageSizeInit === '1') return;

            select.addEventListener('change', function () {
                syncPageSize(this);
            });

            select.dataset.pageSizeInit = '1';
            syncPageSize(select);
        });

        document.querySelectorAll('.toolbar-mode-toggle').forEach(function (button) {
            if (button.dataset.modeInit === '1') return;

            button.addEventListener('click', function () {
                toggleGridView(this);
            });

            button.dataset.modeInit = '1';
        });

        document.querySelectorAll('.tab-content.active .cards-grid').forEach(function (grid) {
            const cards = Array.from(grid.querySelectorAll('.content-card-item'));
            const pageSelect = grid.closest('.module-content')?.querySelector('.toolbar-page-size');
            if (pageSelect) {
                grid.dataset.pageSize = pageSelect.value || String(PAGE_SIZE);
            }
            setupPagination(grid, cards);
        });
    }

    // --- Content-Aware Modal Sizing ---
    function sizeEntityModal(overlay, entityType) {
        var panel = overlay && overlay.querySelector('.entity-content-box');
        if (!panel) return;

        // Size mapping: entity type -> data-size attribute
        var sizeMap = {
            'course': 'standard',
            'module': 'compact',
            'lesson': 'compact',
            'quiz': 'compact',
            'program': 'wide',
            'learning-path': 'wide',
            'video-conference': 'standard',
            'evaluation': 'compact'
        };

        var size = sizeMap[entityType] || 'standard';
        panel.setAttribute('data-size', size);

        // Auto-adjust based on content height
        requestAnimationFrame(function() {
            var body = panel.querySelector('.entity-content-body');
            if (!body) return;
            var contentHeight = body.scrollHeight;
            var viewportHeight = window.innerHeight * 0.75;

            // If content is short, shrink the modal
            if (contentHeight < 200) {
                panel.style.maxHeight = (contentHeight + 120) + 'px';
            } else {
                panel.style.maxHeight = '82vh';
            }
        });
    }

    // Expose for other scripts
    window.sizeEntityModal = sizeEntityModal;

    document.addEventListener('DOMContentLoaded', initTabs);
    window.addEventListener('page:loaded', function () { setTimeout(initTabs, 10); });
})();