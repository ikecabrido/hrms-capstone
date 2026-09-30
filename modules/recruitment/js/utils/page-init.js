    
    export function reinitPage(page) {
    initTabs();
    initForms();
    if (page === 'schedule-tracker') initScheduleTracker();
    if (page === 'interview-results') initInterviewResults();
    window.dispatchEvent(new CustomEvent('page:loaded', { detail: { page: page } }));
    }

    export function initInterviewResults() {
    document.querySelectorAll('.interview-result-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, select, textarea')) return;
        window.location.href = row.dataset.resultUrl;
        });

        row.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        window.location.href = row.dataset.resultUrl;
        });
    });
    }

    export function initScheduleTracker() {
    document.querySelectorAll('.schedule-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, select, textarea')) return;
        if (row.dataset.readOnly === 'true') return;
        window.location.href = row.dataset.scheduleUrl;
        });

        row.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        if (row.dataset.readOnly === 'true') return;
        window.location.href = row.dataset.scheduleUrl;
        });
    });

    document.querySelectorAll('.stage-filter').forEach(function (button) {
        button.addEventListener('click', function () {
        const selectedStage = this.dataset.stageFilter;

        document.querySelectorAll('.stage-filter').forEach(function (filterButton) {
            const isActive = filterButton === button;
            filterButton.classList.toggle('active-all', isActive && selectedStage === 'all');
            filterButton.classList.toggle('active-initial', isActive && selectedStage === '1');
            filterButton.classList.toggle('active-technical', isActive && selectedStage === '2');
            filterButton.classList.toggle('active-final', isActive && selectedStage === '3');
            filterButton.classList.toggle('active-completed', isActive && selectedStage === 'completed');
        });

        const scheduleTable = document.querySelector('.schedule-table');
        if (scheduleTable) {
            scheduleTable.classList.toggle('completed-view', selectedStage === 'completed');
        }

        document.querySelectorAll('[data-interview-stage]').forEach(function (row) {
            const isCompleted = row.dataset.interviewStatus === 'completed';
            const matchesStage = (selectedStage === 'all' || row.dataset.interviewStage === selectedStage)
                && !(selectedStage === '3' && isCompleted);
            const matchesCompleted = selectedStage === 'completed' && isCompleted;
            row.style.display = matchesStage || matchesCompleted ? '' : 'none';
        });
        });
    });
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