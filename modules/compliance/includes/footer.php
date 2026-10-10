<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> School Management System. All rights reserved.</p>
    </footer>
</div>
    <?php include __DIR__ . '/../includes/lala-ai-widget.php'; ?>
    <script src="js/fullcalendar.global.min.js"></script>
    <script src="js/calendar.js"></script>
    <?php include __DIR__ . '/../lib/includes/calendar_modal.php'; ?>
    <script type="module" src="js/script.js?v=20261009_horizontal_bar_fix3"></script>
<!-- incident-calendar-standalone-handler -->
<script>
(function () {
    'use strict';

    function initStandaloneIncidentCalendar() {
        var calendar = document.querySelector('.incident-calendar');
        if (!calendar) {
            return;
        }

        if (calendar.dataset.standaloneHandler === '1') {
            return;
        }

        calendar.dataset.standaloneHandler = '1';

        calendar.addEventListener('click', function (event) {
            var cell = event.target.closest(
                '.incident-calendar-day[role="button"]'
            );

            if (!cell || !calendar.contains(cell)) {
                return;
            }

            var label = cell.getAttribute('aria-label') || '';
            var match = label.match(/^(\d{4}-\d{2}-\d{2})/);

            if (!match) {
                return;
            }

            var dateStr = match[1];

            event.preventDefault();
            event.stopPropagation();

            var wasSelected = cell.classList.contains(
                'incident-calendar-day--selected'
            );

            calendar
                .querySelectorAll('.incident-calendar-day--selected')
                .forEach(function (selectedCell) {
                    selectedCell.classList.remove(
                        'incident-calendar-day--selected'
                    );
                });

            var dateElement = document.querySelector(
                '.incident-selected-date'
            );

            var totalElement = document.querySelector(
                '.incident-selected-total'
            );

            if (wasSelected) {
                if (dateElement) {
                    dateElement.textContent = 'Select a date';
                }

                if (totalElement) {
                    totalElement.textContent = '0 incidents';
                }

                return;
            }

            cell.classList.add('incident-calendar-day--selected');

            var days = {};

            try {
                days = JSON.parse(calendar.dataset.days || '{}');
            } catch (error) {
                console.error(
                    'Incident calendar data could not be parsed:',
                    error
                );
            }

            var dayData = days[dateStr] || {};
            var total = Number(dayData.total || 0);

            var dateObject = new Date(dateStr + 'T00:00:00');

            var formattedDate = dateObject.toLocaleDateString(
                undefined,
                {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                }
            );

            if (dateElement) {
                dateElement.textContent = formattedDate;
            }

            if (totalElement) {
                totalElement.textContent =
                    total + (total === 1 ? ' incident' : ' incidents');
            }

            calendar.dispatchEvent(
                new CustomEvent('incident-calendar-date-selected', {
                    bubbles: true,
                    detail: {
                        date: dateStr,
                        total: total,
                        data: dayData
                    }
                })
            );
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initStandaloneIncidentCalendar
        );
    } else {
        initStandaloneIncidentCalendar();
    }

    window.addEventListener(
        'page:loaded',
        initStandaloneIncidentCalendar
    );
})();
</script>
<!-- /incident-calendar-standalone-handler -->


<style>
/* RISK DISTRIBUTION NO UNDERLINE */

.chart-panel h4,
.chart-panel h4 a,
.chart-panel a {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
}

.chart-panel h4 {
    color: inherit !important;
    font-weight: 600;
}
</style>


<style>
/* FORCE RISK DISTRIBUTION NO UNDERLINE */

.chart-panel .chart-panel-head h4,
.chart-panel .chart-panel-head h4 *,
.chart-panel .chart-panel-head a,
.chart-panel .chart-panel-head a *,
.chart-panel .chart-panel-head::before,
.chart-panel .chart-panel-head::after,
.chart-panel h4::before,
.chart-panel h4::after {
    text-decoration: none !important;
    -webkit-text-decoration: none !important;
    border-bottom: 0 !important;
    border-bottom-width: 0 !important;
    border-bottom-style: none !important;
    box-shadow: none !important;
    background-image: none !important;
}

.chart-panel .chart-panel-head h4 {
    display: block !important;
    text-decoration-line: none !important;
    text-decoration-style: none !important;
    text-decoration-color: transparent !important;
    text-underline-offset: 0 !important;
}
</style>



<style>
/* INCIDENT CALENDAR TOOLTIP FIX v20261007 */

.incident-calendar-tooltip-fixed {
    position: fixed !important;
    display: none;
    z-index: 2147483647 !important;
    width: max-content;
    max-width: 280px;
    padding: 9px 12px;
    border-radius: 8px;
    background: #1f2937;
    color: #ffffff;
    font-size: 12px;
    line-height: 1.45;
    text-align: left;
    white-space: normal;
    pointer-events: none;
    box-shadow: 0 8px 24px rgba(0,0,0,.18);
    opacity: 0;
    transform: translateY(4px);
    transition: opacity .12s ease, transform .12s ease;
}

.incident-calendar-tooltip-fixed.is-visible {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.incident-calendar-day[aria-label] {
    position: relative;
}

.incident-calendar-day:hover {
    z-index: 10;
}
</style>

<script>
(function () {
    'use strict';

    var TOOLTIP_CLASS = 'incident-calendar-tooltip-fixed';
    var tooltip = null;
    var activeCell = null;

    function getTooltip() {
        if (tooltip && document.body.contains(tooltip)) {
            return tooltip;
        }

        tooltip = document.createElement('div');
        tooltip.className = TOOLTIP_CLASS;
        tooltip.setAttribute('role', 'tooltip');

        document.body.appendChild(tooltip);

        return tooltip;
    }

    function parseAriaLabel(cell) {
        var label = cell.getAttribute('aria-label') || '';

        var match = label.match(
            /^(\d{4}-\d{2}-\d{2}),\s*(\d+)\s+incidents?$/i
        );

        if (!match) {
            return {
                date: '',
                count: ''
            };
        }

        return {
            date: match[1],
            count: match[2]
        };
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '';
        }

        var parts = dateString.split('-');

        if (parts.length !== 3) {
            return dateString;
        }

        var y = Number(parts[0]);
        var m = Number(parts[1]);
        var d = Number(parts[2]);

        var date = new Date(y, m - 1, d);

        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function buildContent(cell) {
        var data = parseAriaLabel(cell);

        if (!data.date) {
            return '';
        }

        var count = Number(data.count || 0);
        var text = count === 1 ? 'incident' : 'incidents';

        return (
            '<div style="font-weight:600;margin-bottom:3px;">' +
                escapeHtml(formatDate(data.date)) +
            '</div>' +
            '<div>' +
                escapeHtml(String(count)) +
                ' ' +
                text +
            '</div>'
        );
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function positionTooltip(cell) {
        var tip = getTooltip();

        if (!tip || !cell) {
            return;
        }

        var rect = cell.getBoundingClientRect();

        tip.style.left = '0px';
        tip.style.top = '0px';

        var tipRect = tip.getBoundingClientRect();
        var gap = 8;

        var left = rect.left + (rect.width / 2) - (tipRect.width / 2);
        var top = rect.bottom + gap;

        var viewportPadding = 8;

        if (left < viewportPadding) {
            left = viewportPadding;
        }

        if (left + tipRect.width > window.innerWidth - viewportPadding) {
            left = window.innerWidth - tipRect.width - viewportPadding;
        }

        if (top + tipRect.height > window.innerHeight - viewportPadding) {
            top = rect.top - tipRect.height - gap;
        }

        if (top < viewportPadding) {
            top = viewportPadding;
        }

        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(top) + 'px';
    }

    function showTooltip(cell) {
        if (!cell || cell.classList.contains('incident-calendar-day--empty')) {
            return;
        }

        var content = buildContent(cell);

        if (!content) {
            return;
        }

        var tip = getTooltip();

        tip.innerHTML = content;
        tip.classList.add('is-visible');

        activeCell = cell;

        requestAnimationFrame(function () {
            positionTooltip(cell);
        });
    }

    function hideTooltip() {
        if (!tooltip) {
            return;
        }

        tooltip.classList.remove('is-visible');
        activeCell = null;
    }

    document.addEventListener('mouseover', function (event) {
        var cell = event.target.closest('.incident-calendar-day');

        if (!cell) {
            return;
        }

        if (cell === activeCell) {
            return;
        }

        showTooltip(cell);
    });

    document.addEventListener('mouseout', function (event) {
        var cell = event.target.closest('.incident-calendar-day');

        if (!cell) {
            return;
        }

        var next = event.relatedTarget;

        if (next && cell.contains(next)) {
            return;
        }

        hideTooltip();
    });

    document.addEventListener('focusin', function (event) {
        var cell = event.target.closest('.incident-calendar-day');

        if (cell) {
            showTooltip(cell);
        }
    });

    document.addEventListener('focusout', function (event) {
        var cell = event.target.closest('.incident-calendar-day');

        if (cell) {
            hideTooltip();
        }
    });

    window.addEventListener('resize', function () {
        if (activeCell) {
            positionTooltip(activeCell);
        }
    });

    window.addEventListener('scroll', function () {
        if (activeCell) {
            positionTooltip(activeCell);
        }
    }, true);

    console.log('[Incident Calendar] Fixed tooltip handler loaded.');

})();
</script>



<style>
/* INCIDENT CALENDAR MONTH NAVIGATION FIX v20261007 */

.incident-calendar-btn {
    pointer-events: auto !important;
    cursor: pointer !important;
}

.incident-occurrence-panel.incident-calendar-loading {
    opacity: .72;
    transition: opacity .15s ease;
}

.incident-occurrence-panel.incident-calendar-loading
.incident-calendar-btn {
    pointer-events: none !important;
}
</style>

<script>
(function () {
    'use strict';

    var MARKER = 'incident-calendar-ajax-navigation-v20261007';

    if (window[MARKER]) {
        return;
    }

    window[MARKER] = true;

    var isLoading = false;

    function getUrl() {
        return new URL(window.location.href);
    }

    function setLoading(panel, loading) {
        if (!panel) return;

        panel.classList.toggle(
            'incident-calendar-loading',
            !!loading
        );
    }

    async function loadIncidentMonth(
        year,
        month,
        updateHistory
    ) {
        if (isLoading) {
            return;
        }

        var oldPanel = document.querySelector(
            '.incident-occurrence-panel'
        );

        if (!oldPanel) {
            console.warn(
                '[Incident Calendar] Panel not found.'
            );
            return;
        }

        isLoading = true;

        setLoading(oldPanel, true);

        var scrollY = window.scrollY;

        try {
            var url = getUrl();

            url.searchParams.set(
                'incident_year',
                String(year)
            );

            url.searchParams.set(
                'incident_month',
                String(month)
            );

            url.searchParams.set(
                '_incident_calendar_ajax',
                '1'
            );

            var response = await fetch(
                url.toString(),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',

                        'X-Incident-Calendar':
                            '1'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(
                    'HTTP ' + response.status
                );
            }

            var html = await response.text();

            var parser = new DOMParser();

            var doc = parser.parseFromString(
                html,
                'text/html'
            );

            var newPanel = doc.querySelector(
                '.incident-occurrence-panel'
            );

            if (!newPanel) {
                throw new Error(
                    'New Incident Occurrence panel not found.'
                );
            }

            var currentPanel = document.querySelector(
                '.incident-occurrence-panel'
            );

            if (!currentPanel) {
                throw new Error(
                    'Current Incident Occurrence panel not found.'
                );
            }

            currentPanel.replaceWith(
                newPanel
            );

            /*
             * Update the browser URL without
             * navigating/reloading.
             */
            var visibleUrl = getUrl();

            visibleUrl.searchParams.set(
                'incident_year',
                String(year)
            );

            visibleUrl.searchParams.set(
                'incident_month',
                String(month)
            );

            visibleUrl.searchParams.delete(
                '_incident_calendar_ajax'
            );

            if (updateHistory !== false) {
                window.history.pushState(
                    {
                        incidentYear: year,
                        incidentMonth: month
                    },
                    '',
                    visibleUrl.toString()
                );
            } else {
                window.history.replaceState(
                    {
                        incidentYear: year,
                        incidentMonth: month
                    },
                    '',
                    visibleUrl.toString()
                );
            }

            /*
             * Restore exactly the same page position.
             */
            window.scrollTo(
                0,
                scrollY
            );

            /*
             * Notify any existing calendar code
             * that the panel was replaced.
             */
            document.dispatchEvent(
                new CustomEvent(
                    'page:loaded',
                    {
                        detail: {
                            source:
                                'incident-calendar-ajax',

                            year: year,

                            month: month
                        }
                    }
                )
            );

            console.log(
                '[Incident Calendar] Loaded ' +
                year +
                '-' +
                String(month).padStart(2, '0') +
                ' without page reload.'
            );

        } catch (error) {

            console.error(
                '[Incident Calendar] Month change failed:',
                error
            );

        } finally {

            var latestPanel =
                document.querySelector(
                    '.incident-occurrence-panel'
                );

            setLoading(
                latestPanel,
                false
            );

            isLoading = false;
        }
    }

    document.addEventListener(
        'click',
        function (event) {

            var button =
                event.target.closest(
                    '.incident-calendar-btn'
                );

            if (!button) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var panel =
                button.closest(
                    '.incident-occurrence-panel'
                );

            var calendar =
                panel
                    ? panel.querySelector(
                        '.incident-calendar'
                    )
                    : document.querySelector(
                        '.incident-calendar'
                    );

            if (!calendar) {
                console.warn(
                    '[Incident Calendar] Calendar not found.'
                );
                return;
            }

            var year = parseInt(
                calendar.getAttribute(
                    'data-year'
                ),
                10
            );

            var zeroBasedMonth =
                parseInt(
                    calendar.getAttribute(
                        'data-month'
                    ),
                    10
                );

            var direction =
                button.getAttribute(
                    'data-direction'
                );

            if (
                !Number.isFinite(year) ||
                !Number.isFinite(
                    zeroBasedMonth
                )
            ) {
                console.warn(
                    '[Incident Calendar] Invalid calendar date.'
                );
                return;
            }

            if (
                direction !== 'prev' &&
                direction !== 'next'
            ) {
                return;
            }

            var month =
                zeroBasedMonth + 1;

            if (direction === 'prev') {

                month--;

                if (month < 1) {
                    month = 12;
                    year--;
                }

            } else {

                month++;

                if (month > 12) {
                    month = 1;
                    year++;
                }
            }

            loadIncidentMonth(
                year,
                month,
                true
            );
        },
        true
    );

    /*
     * Browser Back/Forward also changes only
     * the Incident Occurrence panel.
     */
    window.addEventListener(
        'popstate',
        function (event) {

            var url = getUrl();

            var state =
                event.state || {};

            var year = parseInt(
                state.incidentYear ||
                url.searchParams.get(
                    'incident_year'
                ),
                10
            );

            var month = parseInt(
                state.incidentMonth ||
                url.searchParams.get(
                    'incident_month'
                ),
                10
            );

            if (
                !Number.isFinite(year) ||
                !Number.isFinite(month)
            ) {
                return;
            }

            loadIncidentMonth(
                year,
                month,
                false
            );
        }
    );

    console.log(
        '[Incident Calendar] Persistent AJAX month navigation loaded.'
    );

})();
</script>




<!-- HRMS LOGIN DISCLAIMER -->
<?php require_once __DIR__ . '/../../../auth/login-disclaimer.php'; ?>
<!-- /HRMS LOGIN DISCLAIMER -->
</body>
</html>

<style>
/* FORCE RISK DISTRIBUTION LINK NO UNDERLINE */

.chart-panel-link,
.chart-panel-link *,
.chart-panel-link:hover,
.chart-panel-link:focus,
.chart-panel-link:active,
.chart-panel-link:visited {
    text-decoration: none !important;
    -webkit-text-decoration: none !important;
    text-decoration-line: none !important;
    text-decoration-style: none !important;
    text-decoration-color: transparent !important;
    border-bottom: 0 !important;
    border-bottom-width: 0 !important;
    border-bottom-style: none !important;
    box-shadow: none !important;
}

.chart-panel-link::before,
.chart-panel-link::after,
.chart-panel-link *::before,
.chart-panel-link *::after {
    text-decoration: none !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
    background-image: none !important;
}

.chart-panel-link .chart-panel-head,
.chart-panel-link .chart-panel-head h4,
.chart-panel-link .chart-panel-head h4 *,
.chart-panel-link .chart-panel-head .chart-panel-meta {
    text-decoration: none !important;
    -webkit-text-decoration: none !important;
    text-decoration-line: none !important;
    border-bottom: 0 !important;
    box-shadow: none !important;
}

.chart-panel-link .chart-panel-head h4 {
    display: block !important;
}
</style>

