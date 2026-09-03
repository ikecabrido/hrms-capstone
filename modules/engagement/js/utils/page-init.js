    
    const persistentTabContainers = [
        'communication-tabs',
        'collaboration-tabs'
    ];

    function getPersistentTabKey(container) {
        return 'engagement:active-tab:' + container.id;
    }

    function getPersistentTabKeys(container) {
        const keysByContainer = {
            'communication-tabs': ['engagement:communication:active-tab', 'communication-active-tab'],
            'collaboration-tabs': ['engagement:social:active-tab', 'socialPageActiveTab', 'social-active-tab'],
            'grievance-tabs': ['engagement:grievance:active-tab', 'grievance-active-tab']
        };
        return [getPersistentTabKey(container)].concat(keysByContainer[container.id] || []);
    }

    function getPersistentTabLinks(container) {
        return container.querySelectorAll('a[href^="#"]');
    }

    function getTabId(tabLink) {
        if (tabLink.dataset.grievanceTab) return tabLink.dataset.grievanceTab;
        const href = tabLink.getAttribute('href') || '';
        return href.startsWith('#') ? href.slice(1) : '';
    }

    function savePersistentTab(container, tabLink) {
        const tabId = getTabId(tabLink);
        if (!tabId) return;
        try {
            getPersistentTabKeys(container).forEach(function (key) {
                localStorage.setItem(key, tabId);
                sessionStorage.setItem(key, tabId);
            });
        } catch (error) {
            // Ignore storage errors and keep the tab usable.
        }
    }

    function restorePersistentTab(container) {
        const links = Array.from(getPersistentTabLinks(container));
        if (!links.length) return;

        const validIds = links.map(getTabId).filter(Boolean);
        const hashId = window.location.hash.replace('#', '');
        let savedId = validIds.includes(hashId) && hashId !== 'recognition' ? hashId : '';

        if (!savedId) {
            try {
                getPersistentTabKeys(container).some(function (key) {
                    const sessionValue = sessionStorage.getItem(key) || '';
                    const localValue = localStorage.getItem(key) || '';
                    const candidate = validIds.includes(sessionValue) ? sessionValue : localValue;
                    if (container.id === 'recognition-tabs' && candidate === 'recognition') {
                        return false;
                    }
                    if (validIds.includes(candidate)) {
                        savedId = candidate;
                        return true;
                    }
                    return false;
                });
            } catch (error) {
                savedId = '';
            }
        }

        const targetId = validIds.includes(savedId) ? savedId : getTabId(links[0]);
        const targetLink = links.find(function (link) {
            return getTabId(link) === targetId;
        });
        const targetPane = document.getElementById(targetId);
        if (!targetLink || !targetPane) return;

        links.forEach(function (link) {
            const active = link === targetLink;
            link.classList.toggle('active', active);
            link.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        const content = container.parentElement
            ? container.parentElement.querySelector('.tab-content')
            : null;
        const panes = content
            ? content.querySelectorAll('.tab-pane')
            : document.querySelectorAll('.tab-pane');
        panes.forEach(function (pane) {
            const active = pane === targetPane;
            pane.classList.toggle('show', active);
            pane.classList.toggle('active', active);
        });

        savePersistentTab(container, targetLink);
    }

    function initPersistentTabs() {
        persistentTabContainers.forEach(function (containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            getPersistentTabLinks(container).forEach(function (tabLink) {
                if (tabLink.dataset.persistentTabBound === '1') return;
                tabLink.dataset.persistentTabBound = '1';
                tabLink.addEventListener('click', function () {
                    savePersistentTab(container, tabLink);
                }, true);
            });

            restorePersistentTab(container);
        });
    }

    export function reinitPage(page) {
    initTabs();
    initForms();
    initPersistentTabs();
    window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: page } }));
    }

    if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPersistentTabs, { once: true });
    } else {
    initPersistentTabs();
    }

    // ─── Tab Switcher ─────────────────────────────────────────────────────────────

    export function initTabs() {
    const tabItems = document.querySelectorAll('.tab-item');
    const tabContents = document.querySelectorAll('.tab-content');

    if (!tabItems.length) return;

    tabItems.forEach(function (tab) {
        tab.addEventListener('click', function () {
        tabItems.forEach(function (t) { t.classList.remove('active'); });
        tabContents.forEach(function (c) { c.classList.remove('active'); });

        tab.classList.add('active');
        const target = document.getElementById(tab.getAttribute('data-tab'));
        if (target) target.classList.add('active');
        });
    });
    }

    // ─── Form Submissions ─────────────────────────────────────────────────────────

    export function initForms() {
    const forms = document.querySelectorAll('form:not([data-skip]):not(#approval-upload-form):not(#management-update-form)');

    forms.forEach(function (form) {
        const fresh = form.cloneNode(true);
        form.parentNode.replaceChild(fresh, form);

        fresh.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(fresh);
        const action = fresh.getAttribute('action') || window.location.href;

        fetch(action, {
            method: fresh.getAttribute('method') || 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (response) {
            if (!response.ok) throw new Error('Form submission failed');
            return response.text();
            })
            .then(function (result) {
            console.log('Form submitted successfully', result);
            const current = new URL(location).searchParams.get('page') || 'dashboard-overview';
            // Fire an event so main.js can handle the page reload
            window.dispatchEvent(new CustomEvent('form:success', { detail: { page: current } }));
            })
            .catch(function (err) {
            console.error('Form error', err);
            });
        });
    });
    }