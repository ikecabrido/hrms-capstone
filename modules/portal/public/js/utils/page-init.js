    
    export function reinitPage(page) {
    initTabs();
    initForms();
    window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: page } }));
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
    const forms = document.querySelectorAll('form:not([data-skip]):not(#approval-upload-form)');

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