    
    export function reinitPage(page) {
    initTabs();
    initForms();
    initDocumentForms();
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
        const forms = document.querySelectorAll('form:not([data-skip]):not(#approval-upload-form):not([method="get"]):not([method="GET"])');
        console.log('[initForms] Found forms:', forms.length);

        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                console.log('[initForms] Submit intercepted for form:', form);
                e.preventDefault();
                const formData = new FormData(form);
                const action = form.getAttribute('action') || window.location.href;
                console.log('[initForms] Posting to:', action);

                fetch(action, {
                    method: (form.getAttribute('method') || 'POST').toUpperCase(),
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) {
                        console.log('[initForms] Response status:', response.status);
                        if (!response.ok) throw new Error('Form submission failed');
                        return response.text();
                    })
                    .then(function (result) {
                        console.log('[initForms] Response length:', result.length);
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(result, 'text/html');
                        const newContainer = doc.querySelector('.container');
                        const container = document.querySelector('.container');
                        if (!container || !newContainer) {
                            console.log('[initForms] Container not found');
                            return;
                        }

                        container.innerHTML = newContainer.innerHTML;

                        container.querySelectorAll('script').forEach(function (oldScript) {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(function (attr) {
                                newScript.setAttribute(attr.name, attr.value);
                            });
                            newScript.textContent = oldScript.textContent;
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        });

                        const current = new URL(location).searchParams.get('page') || 'dashboard-overview';
                        reinitPage(current);
                    })
                    .catch(function (err) {
                        console.error('[initForms] Form error', err);
                    });
            });
        });
    }

    export function initDocumentForms() {
        const forms = document.querySelectorAll('form.cd-date-form:not([method="POST"]):not([method="post"])');

        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

        var url = new URL(form.getAttribute('action') || window.location.href);
        var formData = new FormData(form);

        formData.forEach(function (value, key) {
            url.searchParams.set(key, value);
        });

        window.location.href = url.toString();
        });
    });
    }