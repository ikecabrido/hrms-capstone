// js/pages/dashboard.js

const DASHBOARD_PAGE = 'dashboard-overview';

let trendChartInstance = null;
let riskChartInstance = null;
let complianceChartInstance = null;
let legalChartInstance = null;
let resizeObserver = null;

function isMobileView() {
    return window.innerWidth <= 768;
}

function getMobileChartHeight() {
    if (window.innerWidth <= 360) return 130;
    if (window.innerWidth <= 480) return 150;
    if (window.innerWidth <= 768) return 170;
    return 180;
}

async function initDashboard() {
    try {
        const chartLoaded = await ensureChartJs();
        destroyCharts();
        initKpiInteractions();
        initTrendLine();
        initRiskDonut();
        initDeptComplianceChart();
        initLegalCasesChart();
        initDashboardInteractions();
        initFilterInteractions();
        initDocumentHealthInteractions();
        initIncidentAnalytics();
        initActionComplianceOverview();
        initMobileResizeObserver();
        if (!chartLoaded) {
            console.warn('Chart.js not available. Charts will not render.');
        }
    } catch (error) {
        console.error('Dashboard initialization failed:', error);
    }
}

function initMobileResizeObserver() {
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }

    resizeObserver = new ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
            const canvas = entry.target.querySelector('canvas');
            if (canvas && trendChartInstance && entry.target.id === 'dashTrendChart') {
                trendChartInstance.resize();
            }
            if (canvas && riskChartInstance && entry.target.id === 'riskPieChart') {
                riskChartInstance.resize();
            }
            if (canvas && legalChartInstance && entry.target.id === 'legalCasesChart') {
                legalChartInstance.resize();
            }
        });
    });

    const trendWrap = document.querySelector('.sparkline-wrap');
    const riskWrap = document.querySelector('.risk-pie-wrap');
    const legalWrap = document.querySelector('.legal-chart-wrap');
    if (trendWrap) resizeObserver.observe(trendWrap);
    if (riskWrap) resizeObserver.observe(riskWrap);
    if (legalWrap) resizeObserver.observe(legalWrap);
}

async function ensureChartJs() {
    if (typeof Chart !== 'undefined') return true;

    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = '/modules/compliance/lib/chart.js/chart.umd.min.js';
        script.onload = () => resolve(true);
        script.onerror = () => resolve(false);
        document.head.appendChild(script);
    });
}

function destroyCharts() {
    [trendChartInstance, riskChartInstance, complianceChartInstance, legalChartInstance].forEach(function (instance) {
        if (instance) {
            instance.destroy();
        }
    });
    trendChartInstance = null;
    riskChartInstance = null;
    complianceChartInstance = null;
    legalChartInstance = null;
}

function getTrendLabels() {
    const labels = window.TREND_LABELS;
    if (labels && Array.isArray(labels)) return labels;
    return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
}

function getTrendValues() {
    const values = window.TREND_VALUES;
    if (values && Array.isArray(values)) return values;
    return [85, 87, 88, 89, 92, 91];
}

function getRiskDistData() {
    if (window.RISK_DIST_VALUES && Array.isArray(window.RISK_DIST_VALUES)) {
        return {
            labels: window.RISK_DIST_LABELS || ['Critical', 'High', 'Medium', 'Low'],
            data: window.RISK_DIST_VALUES,
            colors: window.RISK_DIST_COLORS || ['rgba(30, 64, 175, 0.85)', 'rgba(37, 99, 235, 0.85)', 'rgba(59, 130, 196, 0.85)', 'rgba(147, 197, 253, 0.85)'],
        };
    }
    return {
        labels: ['Critical', 'High', 'Medium', 'Low'],
        data: [0, 0, 0, 0],
        colors: ['rgba(30, 64, 175, 0.85)', 'rgba(37, 99, 235, 0.85)', 'rgba(59, 130, 196, 0.85)', 'rgba(147, 197, 253, 0.85)'],
    };
}

function initKpiInteractions() {
    document.querySelectorAll('.kpi-card').forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = '';
        });
    });
}

function initDashboardInteractions() {
    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            this.querySelector('i').classList.add('fa-spin');
            setTimeout(() => {
                this.querySelector('i').classList.remove('fa-spin');
                location.reload();
            }, 600);
        });
    }

    document.querySelectorAll('.period-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelector('.period-btn--active').classList.remove('period-btn--active');
            this.classList.add('period-btn--active');
        });
    });
}

function initFilterInteractions() {
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            const label = this.options[this.selectedIndex]?.text || '';
            const customEvent = new CustomEvent('dashboardFilterChange', {
                detail: { filter: label }
            });
            window.dispatchEvent(customEvent);
        });
    });
}

function initDocumentHealthInteractions() {
    const container = document.querySelector('.document-health');
    if (!container) return;

    const panel = container;
    const tooltip = container.querySelector('.document-health-tooltip');

    if (!tooltip) return;

    panel.addEventListener('mouseenter', function () {
        tooltip.style.display = 'block';
        positionTooltip();
    });

    panel.addEventListener('mouseleave', function () {
        tooltip.style.display = 'none';
    });

    panel.addEventListener('mousemove', function (e) {
        positionTooltip(e);
    });

    function positionTooltip(e) {
        const x = e ? e.clientX : tooltip.getBoundingClientRect().left;
        const y = e ? e.clientY : tooltip.getBoundingClientRect().top;
        tooltip.style.left = (x + 12) + 'px';
        tooltip.style.top = (y - 12) + 'px';
    }
}

function initTrendLine() {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('dashTrendChart');
    if (!canvas) return;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    const ctx = canvas.getContext('2d');
    const labels = getTrendLabels();
    const values = getTrendValues();

    const scoreColor = values.length > 0 && values[values.length - 1] >= 90
        ? 'rgba(37, 99, 235, 0.9)'
        : (values.length > 0 && values[values.length - 1] >= 75
            ? 'rgba(96, 165, 250, 0.9)'
            : 'rgba(30, 64, 175, 0.9)');

    const mobileHeight = getMobileChartHeight();
    const sparklineWrap = document.querySelector('.sparkline-wrap');
    if (sparklineWrap && isMobileView()) {
        sparklineWrap.style.height = mobileHeight + 'px';
    }

    trendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Compliance Score',
                data: values,
                borderColor: scoreColor,
                backgroundColor: scoreColor.replace('0.9', '0.08'),
                borderWidth: isMobileView() ? 2 : 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: scoreColor,
                pointBorderWidth: isMobileView() ? 1.5 : 2,
                pointRadius: isMobileView() ? 2 : 5,
                pointHoverRadius: isMobileView() ? 4 : 7,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    padding: isMobileView() ? 10 : 14,
                    cornerRadius: isMobileView() ? 6 : 10,
                    titleFont: { size: isMobileView() ? 11 : 13, weight: '600', family: 'Inter' },
                    bodyFont: { size: isMobileView() ? 11 : 12, family: 'Inter' },
                    callbacks: {
                        label: function (context) {
                            return ' Score: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        maxRotation: 0
                    },
                    border: { display: false }
                },
                y: {
                    beginAtZero: false,
                    min: 70,
                    max: 100,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        callback: function (value) {
                            return value + '%';
                        },
                        maxTicksLimit: isMobileView() ? 5 : 7
                    },
                    border: { display: false }
                }
            },
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            }
        }
    });
}

function initRiskDonut() {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('riskPieChart');
    if (!canvas) return;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    const ctx = canvas.getContext('2d');
    const data = getRiskDistData();

    const mobileHeight = getMobileChartHeight();
    const riskWrap = document.querySelector('.risk-pie-wrap');
    if (riskWrap && isMobileView()) {
        riskWrap.style.height = mobileHeight + 'px';
    }

    riskChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: data.colors,
                borderColor: '#ffffff',
                borderWidth: isMobileView() ? 2 : 3,
                hoverBorderColor: '#ffffff',
                hoverBorderWidth: isMobileView() ? 2 : 3,
                hoverOffset: isMobileView() ? 4 : 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: isMobileView() ? '60%' : '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: isMobileView() ? 10 : 24,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        pointStyleWidth: isMobileView() ? 8 : 10,
                        font: { size: isMobileView() ? 10 : 12, family: 'Inter', weight: '500' },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    padding: isMobileView() ? 10 : 14,
                    cornerRadius: isMobileView() ? 6 : 10,
                    titleFont: { size: isMobileView() ? 11 : 13, weight: '600', family: 'Inter' },
                    bodyFont: { size: isMobileView() ? 11 : 12, family: 'Inter' },
                    callbacks: {
                        label: function (context) {
                            const total = context.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                            const value = context.parsed;
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return ' ' + context.label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 800,
                easing: 'easeOutQuart'
            }
        }
    });
}

function initDeptComplianceChart() {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('deptComplianceChart');
    if (!canvas) return;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    const labels = window.DEPT_COMPLIANCE_LABELS || [];
    const scores = window.DEPT_COMPLIANCE_SCORES || [];
    if (!labels.length || !scores.length) return;

    const ctx = canvas.getContext('2d');
    const backgroundColors = [
        'rgba(37, 99, 235, 0.85)',
        'rgba(59, 130, 196, 0.85)',
        'rgba(30, 64, 175, 0.85)',
        'rgba(96, 165, 250, 0.85)',
        'rgba(29, 78, 216, 0.85)',
        'rgba(37, 99, 235, 0.85)',
        'rgba(59, 130, 196, 0.85)',
    ];

    const borderColors = [
        'rgba(37, 99, 235, 1)',
        'rgba(59, 130, 196, 1)',
        'rgba(30, 64, 175, 1)',
        'rgba(96, 165, 250, 1)',
        'rgba(29, 78, 216, 1)',
        'rgba(37, 99, 235, 1)',
        'rgba(59, 130, 196, 1)',
    ];

    complianceChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Compliance Score',
                data: scores,
                backgroundColor: backgroundColors.slice(0, labels.length),
                borderColor: borderColors.slice(0, labels.length),
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 48,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    padding: isMobileView() ? 10 : 14,
                    cornerRadius: isMobileView() ? 6 : 10,
                    titleFont: { size: isMobileView() ? 11 : 13, weight: '600', family: 'Inter' },
                    bodyFont: { size: isMobileView() ? 11 : 12, family: 'Inter' },
                    callbacks: {
                        label: function (context) {
                            return ' Score: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        maxRotation: isMobileView() ? 45 : 30,
                        minRotation: isMobileView() ? 45 : 30,
                    },
                    border: { display: false }
                },
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min.apply(null, scores) - 10),
                    max: 100,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        callback: function (value) {
                            return value + '%';
                        },
                        maxTicksLimit: isMobileView() ? 5 : 7
                    },
                    border: { display: false }
                }
            },
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            }
        }
    });
}

function initLegalCasesChart() {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('legalCasesChart');
    if (!canvas) return;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    const labels = window.LEGAL_CASES_LABELS || [];
    const values = window.LEGAL_CASES_VALUES || [];
    if (!labels.length || !values.length) {
        const wrap = document.querySelector('.legal-chart-wrap');
        if (wrap) {
            wrap.innerHTML = '<div class="empty-state"><i class="fa-solid fa-gavel"></i><div class="es-title">No legal cases recorded yet</div></div>';
        }
        return;
    }

    const ctx = canvas.getContext('2d');
    const backgroundColors = [
        'rgba(37, 99, 235, 0.85)',
        'rgba(59, 130, 196, 0.85)',
        'rgba(30, 64, 175, 0.85)',
        'rgba(96, 165, 250, 0.85)',
        'rgba(29, 78, 216, 0.85)',
        'rgba(37, 99, 235, 0.85)',
        'rgba(59, 130, 196, 0.85)',
    ];

    const borderColors = [
        'rgba(37, 99, 235, 1)',
        'rgba(59, 130, 196, 1)',
        'rgba(30, 64, 175, 1)',
        'rgba(96, 165, 250, 1)',
        'rgba(29, 78, 216, 1)',
        'rgba(37, 99, 235, 1)',
        'rgba(59, 130, 196, 1)',
    ];

    legalChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cases',
                data: values,
                backgroundColor: backgroundColors.slice(0, labels.length),
                borderColor: borderColors.slice(0, labels.length),
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: isMobileView() ? 32 : 48,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    padding: isMobileView() ? 10 : 14,
                    cornerRadius: isMobileView() ? 6 : 10,
                    titleFont: { size: isMobileView() ? 11 : 13, weight: '600', family: 'Inter' },
                    bodyFont: { size: isMobileView() ? 11 : 12, family: 'Inter' },
                    callbacks: {
                        title: function (items) {
                            if (!items.length) return '';
                            return items[0].label || '';
                        },
                        label: function (context) {
                            return ' ' + context.parsed.y + ' cases';
                        }
                    }
                }
            },
            scales: {
                x: {
                    position: 'bottom',
                    title: {
                        display: false
                    },
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        maxRotation: isMobileView() ? 45 : 30,
                        minRotation: isMobileView() ? 45 : 30,
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: isMobileView() ? 9 : 11, family: 'Inter' },
                        stepSize: 1,
                        maxTicksLimit: isMobileView() ? 5 : 7
                    },
                    border: { display: false }
                }
            },
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            }
        }
    });
}

// ──────────────────────────────────────────────────────────────────────────────
// Event Listeners
// ──────────────────────────────────────────────────────────────────────────────

window.addEventListener('page:loaded', function (e) {
    if (e.detail && e.detail.page === DASHBOARD_PAGE) {
        initDashboard();
    } else {
        destroyCharts();
        if (resizeObserver) {
            resizeObserver.disconnect();
            resizeObserver = null;
        }
    }
});

let dashboardResizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(dashboardResizeTimer);
    dashboardResizeTimer = setTimeout(function () {
        if (!document.getElementById('dashTrendChart') && !document.getElementById('riskPieChart') && !document.getElementById('deptComplianceChart') && !document.getElementById('legalCasesChart')) return;

        const wasDesktop = !isMobileView();
        const sparkWrap = document.querySelector('.sparkline-wrap');
        const riskWrap = document.querySelector('.risk-pie-wrap');
        const deptWrap = document.querySelector('.dept-chart-wrap');
        const legalWrap = document.querySelector('.legal-chart-wrap');

        if (sparkWrap && trendChartInstance) {
            if (isMobileView()) {
                sparkWrap.style.height = getMobileChartHeight() + 'px';
            } else {
                sparkWrap.style.height = '';
            }
            trendChartInstance.resize();
        }

        if (riskWrap && riskChartInstance) {
            if (isMobileView()) {
                riskWrap.style.height = getMobileChartHeight() + 'px';
            } else {
                riskWrap.style.height = '';
            }
            riskChartInstance.resize();
        }

        if (deptWrap && complianceChartInstance) {
            if (isMobileView()) {
                deptWrap.style.height = '220px';
            } else {
                deptWrap.style.height = '260px';
            }
            complianceChartInstance.resize();
        }

        if (legalWrap && legalChartInstance) {
            if (isMobileView()) {
                legalWrap.style.height = getMobileChartHeight() + 'px';
            } else {
                legalWrap.style.height = '';
            }
            legalChartInstance.resize();
        }
    }, 200);
});

// ── Incident Analytics ──────────────────────────────────────────────────────────

function initIncidentAnalytics() {
    initIncidentCalendar();
    initIncidentCategoryFilter();
}

function initIncidentCalendar() {
    const calendar = document.querySelector('.incident-calendar');
    if (!calendar) return;

    // Prevent duplicate initialization when dashboard/page lifecycle
    // events trigger initialization more than once.
    if (calendar.dataset.incidentCalendarInitialized === '1') {
        return;
    }

    calendar.dataset.incidentCalendarInitialized = '1';

    const grid = calendar.querySelector('.incident-calendar-grid');
    const panel = calendar.closest('.chart-panel');
    const monthLabel = panel ? panel.querySelector('.incident-calendar-month') : null;
    const prevBtn = panel ? panel.querySelector('[data-direction="prev"]') : null;
    const nextBtn = panel ? panel.querySelector('[data-direction="next"]') : null;
    const daysData = calendar.dataset.days ? JSON.parse(calendar.dataset.days) : {};

    function getUrlParam(name) {
        const value = new URLSearchParams(window.location.search).get(name);
        return value ? parseInt(value, 10) : null;
    }

    function updateUrl(year, month) {
        const url = new URL(window.location.href);
        url.searchParams.set('incident_year', year);
        url.searchParams.set('incident_month', month);
        window.history.replaceState({}, '', url.toString());
    }

    let year = getUrlParam('incident_year') || parseInt(calendar.dataset.year, 10) || parseInt(monthLabel?.textContent?.match(/\d{4}/)?.[0] || '2026', 10);
    let month = getUrlParam('incident_month') || parseInt(calendar.dataset.month, 10) || 1;
    let selectedDate = null;
    let activeCategory = null;

    const tooltip = document.createElement('div');
    tooltip.className = 'incident-tooltip';

    // Tooltip is informational only and must never capture clicks.
    tooltip.style.pointerEvents = 'none';
    tooltip.style.userSelect = 'none';
    tooltip.style.zIndex = '-1';

    document.body.appendChild(tooltip);

    const intensityColors = {
        0: '',
        1: 'incident-calendar-day--low',
        2: 'incident-calendar-day--medium',
        3: 'incident-calendar-day--high',
        4: 'incident-calendar-day--critical',
    };

    function getIntensityClass(count, hasCategoryMatch) {
        if (hasCategoryMatch) return 'incident-calendar-day--selected';
        if (count <= 0) return intensityColors[0];
        if (count === 1) return intensityColors[1];
        if (count === 2) return intensityColors[2];
        if (count === 3) return intensityColors[3];
        return intensityColors[4];
    }

    function showTooltip(x, y, html) {
        tooltip.innerHTML = html;
        tooltip.style.pointerEvents = 'none';
        tooltip.style.display = 'block';

        const rect = tooltip.getBoundingClientRect();
        const padding = 10;

        let left = x - (rect.width / 2);
        let top = y - rect.height - 10;

        if (left < padding) {
            left = padding;
        }

        if (left + rect.width > window.innerWidth - padding) {
            left = window.innerWidth - rect.width - padding;
        }

        if (top < padding) {
            top = y + 12;
        }

        if (top + rect.height > window.innerHeight - padding) {
            top = window.innerHeight - rect.height - padding;
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function hideTooltip() {
        tooltip.style.display = 'none';
    }

    function render() {
        if (!grid) return;
        grid.innerHTML = '';

        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        let startDay = firstDay.getDay() - 1;
        if (startDay < 0) startDay = 6;

        for (let i = 0; i < startDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'incident-calendar-day incident-calendar-day--empty';
            grid.appendChild(empty);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            const dayData = daysData[dateStr] || null;
            const count = dayData ? dayData.total : 0;
            const hasCategoryMatch = activeCategory && dayData && dayData.categories && dayData.categories[activeCategory];
            const cell = document.createElement('div');
            const todayStr = new Date().toISOString().slice(0, 10);
            cell.className = 'incident-calendar-day ' + getIntensityClass(count, hasCategoryMatch);
            if (selectedDate === dateStr) {
                cell.classList.add('incident-calendar-day--selected');
            }
            if (dateStr === todayStr && !selectedDate) {
                cell.classList.add('incident-calendar-day--today');
            }
            cell.setAttribute('role', 'button');
            cell.setAttribute('tabindex', '0');
            cell.setAttribute('aria-label', dateStr + ', ' + count + ' incidents');

            // Force generated calendar cells to remain interactive.
            cell.style.pointerEvents = 'auto';
            cell.style.cursor = 'pointer';
            cell.style.userSelect = 'none';
            cell.style.webkitUserSelect = 'none';
            cell.style.touchAction = 'manipulation';

            const numberEl = document.createElement('div');
            numberEl.className = 'incident-calendar-day-number';
            numberEl.textContent = day;
            cell.appendChild(numberEl);

            if (count > 0) {
                const countEl = document.createElement('div');
                countEl.className = 'incident-calendar-day-count';
                countEl.textContent = count;
                cell.appendChild(countEl);

                cell.addEventListener('mouseenter', function (e) {
                    let tooltipHtml = '<div>' + dateStr + '</div>';
                    tooltipHtml += '<div style=\"font-weight:700;margin-top:2px;\">' + count + ' incidents</div>';
                    if (dayData && dayData.categories) {
                        Object.entries(dayData.categories).forEach(function (entry) {
                            tooltipHtml += '<div style=\"font-weight:400;opacity:.85;margin-top:1px;\">' + entry[0] + ' — ' + entry[1] + '</div>';
                        });
                    }
                    if (dayData && dayData.severities) {
                        Object.entries(dayData.severities).forEach(function (entry) {
                            tooltipHtml += '<div style=\"font-weight:400;opacity:.85;margin-top:1px;\">' + entry[0] + ' — ' + entry[1] + '</div>';
                        });
                    }
                    showTooltip(e.clientX, e.clientY, tooltipHtml);
                });

                cell.addEventListener('mouseleave', hideTooltip);
            }

            /*
             * Direct pointer interaction.
             *
             * The calendar cells are generated dynamically, so
             * bind the interaction directly to each generated
             * cell. This runs before the normal click event.
             */
            cell.addEventListener('pointerdown', function (e) {
                if (e.button !== undefined && e.button !== 0) {
                    return;
                }

                e.stopPropagation();

                if (selectedDate === dateStr) {
                    selectedDate = null;
                } else {
                    selectedDate = dateStr;
                }

                render();
                dispatchIncidentDateFilter(selectedDate);
            }, true);

            /*
             * Keep the normal click handler for compatibility
             * with existing dashboard behaviour.
             */
            cell.addEventListener('click', function (e) {
                /*
                 * pointerdown already performs the selection.
                 * Prevent this click from selecting the same date
                 * a second time.
                 */
                e.stopPropagation();
            }, true);

            cell.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    cell.click();
                }
            });

            grid.appendChild(cell);
        }

        if (monthLabel) {
            monthLabel.textContent = new Date(year, month - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' });
        }
    }

    window.addEventListener('incidentCategoryFilter', function (e) {
        activeCategory = e.detail && e.detail.category ? e.detail.category : null;
        render();
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            month--;
            if (month < 1) {
                month = 12;
                year--;
            }
            updateUrl(year, month);
            render();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            month++;
            if (month > 12) {
                month = 1;
                year++;
            }
            updateUrl(year, month);
            render();
        });
    }

    // Delegated Incident Calendar Click Handler
    // Handles clicks on generated calendar cells even when the grid
    // is rebuilt by render().
    if (grid) {
        grid.addEventListener('click', function (e) {
            const cell = e.target.closest('.incident-calendar-day[role="button"]');

            if (!cell || !grid.contains(cell)) {
                return;
            }

            const label = cell.getAttribute('aria-label') || '';
            const match = label.match(/^(\d{4}-\d{2}-\d{2})/);

            if (!match) {
                return;
            }

            const dateStr = match[1];

            if (selectedDate === dateStr) {
                selectedDate = null;
            } else {
                selectedDate = dateStr;
            }

            e.preventDefault();
            e.stopPropagation();

            render();
            dispatchIncidentDateFilter(selectedDate);
        });

        grid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }

            const cell = e.target.closest('.incident-calendar-day[role="button"]');

            if (!cell || !grid.contains(cell)) {
                return;
            }

            const label = cell.getAttribute('aria-label') || '';
            const match = label.match(/^(\d{4}-\d{2}-\d{2})/);

            if (!match) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const dateStr = match[1];

            if (selectedDate === dateStr) {
                selectedDate = null;
            } else {
                selectedDate = dateStr;
            }

            render();
            dispatchIncidentDateFilter(selectedDate);
        });
    }

    // End Delegated Incident Calendar Click Handler

    render();
}

/*
 * Independent Incident Calendar bootstrap.
 *
 * This deliberately initializes the Incident Occurrence calendar even if
 * another dashboard initializer throws before initIncidentAnalytics().
 * The initialization guard above prevents duplicate handlers.
 */
function bootstrapIncidentCalendar() {
    if (document.querySelector('.incident-calendar')) {
        initIncidentCalendar();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapIncidentCalendar);
} else {
    bootstrapIncidentCalendar();
}

window.addEventListener('page:loaded', bootstrapIncidentCalendar);

function dispatchIncidentDateFilter(date) {
    const event = new CustomEvent('incidentDateFilter', {
        detail: { date: date },
        bubbles: true
    });
    window.dispatchEvent(event);
}

function initIncidentCategoryFilter() {
    const rows = document.querySelectorAll('.incident-cat-row[data-category]');
    rows.forEach(function (row) {
        row.addEventListener('click', function () {
            const category = this.dataset.category;
            if (activeCategory === category) {
                activeCategory = null;
                this.dataset.categoryFiltered = 'false';
            } else {
                rows.forEach(function (r) { r.dataset.categoryFiltered = 'false'; });
                activeCategory = category;
                this.dataset.categoryFiltered = 'true';
            }
            dispatchIncidentCategoryFilter(activeCategory);
        });
    });
}

function dispatchIncidentCategoryFilter(category) {
    const event = new CustomEvent('incidentCategoryFilter', {
        detail: { category: category },
        bubbles: true
    });
    window.dispatchEvent(event);
}

function initActionComplianceOverview() {
    document.querySelectorAll('.action-group-action').forEach(function (btn) {
        if (btn.tagName.toLowerCase() === 'a') return;
        btn.addEventListener('click', function () {
            const group = this.closest('.action-group');
            if (!group) return;
            const url = group.dataset.actionUrl;
            if (url) {
                window.location.href = url;
                return;
            }
            const title = group.querySelector('.action-group-title')?.textContent?.trim() || 'Action';
            const event = new CustomEvent('actionGroupView', {
                detail: { title: title },
                bubbles: true
            });
            window.dispatchEvent(event);
        });
    });
}

export { initDashboard, initDeptComplianceChart, initIncidentAnalytics, initActionComplianceOverview, DASHBOARD_PAGE };

