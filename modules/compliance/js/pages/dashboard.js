// js/pages/dashboard.js

const DASHBOARD_PAGE = 'dashboard-overview';

let trendChartInstance = null;
let riskChartInstance = null;
let complianceChartInstance = null;
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
        await ensureChartJs();
        destroyCharts();
        initKpiInteractions();
        initTrendLine();
        initRiskDonut();
        initDashboardInteractions();
        initFilterInteractions();
        initMobileResizeObserver();
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
        });
    });

    const trendWrap = document.querySelector('.sparkline-wrap');
    const riskWrap = document.querySelector('.risk-pie-wrap');
    if (trendWrap) resizeObserver.observe(trendWrap);
    if (riskWrap) resizeObserver.observe(riskWrap);
}

async function ensureChartJs() {
    if (typeof Chart !== 'undefined') return;

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        script.onload = resolve;
        script.onerror = () => reject(new Error('Failed to load Chart.js'));
        document.head.appendChild(script);
    });
}

function destroyCharts() {
    [trendChartInstance, riskChartInstance, complianceChartInstance].forEach(function (instance) {
        if (instance) {
            instance.destroy();
        }
    });
    trendChartInstance = null;
    riskChartInstance = null;
    complianceChartInstance = null;
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
            labels: window.RISK_DIST_LABELS || ['Low Risk', 'Medium Risk', 'High Risk'],
            data: window.RISK_DIST_VALUES,
            colors: window.RISK_DIST_COLORS || ['rgba(16, 185, 129, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(220, 38, 38, 0.85)'],
        };
    }
    return {
        labels: ['Low Risk', 'Medium Risk', 'High Risk'],
        data: [3, 2, 0],
        colors: ['rgba(16, 185, 129, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(220, 38, 38, 0.85)'],
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

function initTrendLine() {
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
        ? 'rgba(16, 185, 129, 0.9)'
        : (values.length > 0 && values[values.length - 1] >= 75
            ? 'rgba(245, 158, 11, 0.9)'
            : 'rgba(220, 38, 38, 0.9)');

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
        if (!document.getElementById('dashTrendChart') && !document.getElementById('riskPieChart')) return;

        const wasDesktop = !isMobileView();
        const sparkWrap = document.querySelector('.sparkline-wrap');
        const riskWrap = document.querySelector('.risk-pie-wrap');

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
    }, 200);
});

export { initDashboard, DASHBOARD_PAGE };
