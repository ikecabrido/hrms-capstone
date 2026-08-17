    
    export function reinitPage(page) {
        console.log('[TA INIT] reinitPage:', page);
        initTabs();
        initForms();

        // Centralized page initializer dispatch
        try {
            // Map page names to init function names
            switch (page) {
                case 'shifts':
                    console.log('[TA INIT] Calling initializer:', 'initShiftsPage');
                    if (typeof initShiftsPage === 'function') initShiftsPage();
                    break;
                case 'qr_scanner':
                    console.log('[TA INIT] Calling initializer:', 'initQRScannerPage');
                    if (typeof initQRScannerPage === 'function') initQRScannerPage();
                    break;
                case 'schedule_calendar':
                    console.log('[TA INIT] Calling initializer:', 'initScheduleCalendarPage');
                    if (typeof initScheduleCalendarPage === 'function') initScheduleCalendarPage();
                    break;
                case 'holidays':
                    console.log('[TA INIT] Calling initializer:', 'initHolidaysPage');
                    if (typeof initHolidaysPage === 'function') initHolidaysPage();
                    break;
                case 'dashboard-overview':
                    console.log('[TA INIT] Calling initializer:', 'initTimeDashboardPage');
                    if (typeof initTimeDashboardPage === 'function') initTimeDashboardPage();
                    break;
                case 'employee_qr_list':
                    console.log('[TA INIT] Calling initializer:', 'initEmployeeQRListPage');
                    if (typeof initEmployeeQRListPage === 'function') initEmployeeQRListPage();
                    break;
                case 'absence_late_management':
                    console.log('[TA INIT] Calling initializer:', 'initAbsenceLateManagementPage');
                    if (typeof initAbsenceLateManagementPage === 'function') initAbsenceLateManagementPage();
                    break;
                case 'leave_approvals':
                    console.log('[TA INIT] Calling initializer:', 'initLeaveApprovalsPage');
                    if (typeof initLeaveApprovalsPage === 'function') initLeaveApprovalsPage();
                    break;
                default:
                    // pages without explicit init will still receive the page:loaded event
                    break;
            }
        } catch (err) {
            console.error('reinitPage initializer error for', page, err);
        }

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