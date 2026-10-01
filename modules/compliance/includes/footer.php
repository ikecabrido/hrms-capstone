<div>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> School Management System. All rights reserved.</p>
    </footer>
</div>
    <?php include __DIR__ . '/../includes/lala-ai-widget.php'; ?>
    <link rel="stylesheet" href="css/components/calendar.css?v=2">
    <script src="js/fullcalendar.global.min.js"></script>
    <script src="js/calendar.js"></script>
    <?php include __DIR__ . '/../lib/includes/calendar_modal.php'; ?>
    <script type="module" src="js/script.js?v=20260930_2"></script>
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
/* INCIDENT CALENDAR HOVER TOOLTIP */

.incident-calendar-tooltip {
    position: fixed;
    z-index: 99999;
    display: none;
    min-width: 230px;
    max-width: 320px;
    padding: 12px 14px;
    box-sizing: border-box;

    background: #ffffff;
    border: 1px solid #dfe3e8;
    border-radius: 8px;

    box-shadow:
        0 8px 24px rgba(0, 0, 0, 0.12),
        0 2px 6px rgba(0, 0, 0, 0.06);

    color: #2f3439;
    font-size: 13px;
    line-height: 1.45;

    pointer-events: none;
}

.incident-calendar-tooltip.is-visible {
    display: block;
}

.incident-calendar-tooltip-title {
    margin-bottom: 8px;
    padding-bottom: 7px;
    border-bottom: 1px solid #e8ebee;

    font-weight: 700;
    color: #1f2933;
}

.incident-calendar-tooltip-total {
    margin-bottom: 8px;
    font-weight: 600;
}

.incident-calendar-tooltip-section {
    margin-top: 8px;
}

.incident-calendar-tooltip-label {
    margin-bottom: 3px;

    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;

    color: #6b7280;
}

.incident-calendar-tooltip-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 2px 0;
}

.incident-calendar-tooltip-row strong {
    font-weight: 700;
}
</style>

<script>
/* INCIDENT CALENDAR HOVER TOOLTIP */
(function () {
    "use strict";

    if (window.__incidentCalendarTooltipLoaded) {
        return;
    }

    window.__incidentCalendarTooltipLoaded = true;

    var tooltip = document.createElement("div");

    tooltip.className = "incident-calendar-tooltip";
    tooltip.setAttribute("role", "tooltip");

    document.body.appendChild(tooltip);

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(dateString) {
        var parts = dateString.split("-");

        if (parts.length !== 3) {
            return dateString;
        }

        var date = new Date(
            Number(parts[0]),
            Number(parts[1]) - 1,
            Number(parts[2])
        );

        return date.toLocaleDateString(undefined, {
            month: "long",
            day: "numeric",
            year: "numeric"
        });
    }

    function buildTooltip(data) {
        var html = "";

        html += '<div class="incident-calendar-tooltip-title">';
        html += escapeHtml(formatDate(data.date));
        html += "</div>";

        html += '<div class="incident-calendar-tooltip-total">';
        html += escapeHtml(data.total);
        html += data.total === 1 ? " Incident" : " Incidents";
        html += "</div>";

        if (data.categories && Object.keys(data.categories).length) {
            html += '<div class="incident-calendar-tooltip-section">';
            html += '<div class="incident-calendar-tooltip-label">Categories</div>';

            Object.keys(data.categories).forEach(function (category) {
                html += '<div class="incident-calendar-tooltip-row">';
                html += "<span>" + escapeHtml(category) + "</span>";
                html += "<strong>" + escapeHtml(data.categories[category]) + "</strong>";
                html += "</div>";
            });

            html += "</div>";
        }

        if (data.severities && Object.keys(data.severities).length) {
            html += '<div class="incident-calendar-tooltip-section">';
            html += '<div class="incident-calendar-tooltip-label">Severity</div>';

            Object.keys(data.severities).forEach(function (severity) {
                var label = severity.charAt(0).toUpperCase() + severity.slice(1);

                html += '<div class="incident-calendar-tooltip-row">';
                html += "<span>" + escapeHtml(label) + "</span>";
                html += "<strong>" + escapeHtml(data.severities[severity]) + "</strong>";
                html += "</div>";
            });

            html += "</div>";
        }

        return html;
    }

    function positionTooltip(event) {
        var offset = 14;
        var rect = tooltip.getBoundingClientRect();

        var left = event.clientX + offset;
        var top = event.clientY + offset;

        if (left + rect.width > window.innerWidth - 10) {
            left = event.clientX - rect.width - offset;
        }

        if (top + rect.height > window.innerHeight - 10) {
            top = event.clientY - rect.height - offset;
        }

        tooltip.style.left = Math.max(10, left) + "px";
        tooltip.style.top = Math.max(10, top) + "px";
    }

    function getCalendarData(calendar) {
        try {
            return JSON.parse(calendar.getAttribute("data-days") || "{}");
        } catch (error) {
            return {};
        }
    }

    document.addEventListener("mouseover", function (event) {
        var day = event.target.closest(".incident-calendar-day");

        if (!day) {
            return;
        }

        var calendar = day.closest(".incident-calendar");

        if (!calendar) {
            return;
        }

        var label = day.getAttribute("aria-label") || "";

        var match = label.match(/^(\d{4}-\d{2}-\d{2})/);

        if (!match) {
            return;
        }

        var dateKey = match[1];
        var days = getCalendarData(calendar);
        var data = days[dateKey];

        if (!data || !Number(data.total)) {
            return;
        }

        tooltip.innerHTML = buildTooltip(data);
        tooltip.classList.add("is-visible");

        positionTooltip(event);
    });

    document.addEventListener("mousemove", function (event) {
        if (tooltip.classList.contains("is-visible")) {
            positionTooltip(event);
        }
    });

    document.addEventListener("mouseout", function (event) {
        var day = event.target.closest(".incident-calendar-day");

        if (!day) {
            return;
        }

        var related = event.relatedTarget;

        if (related && day.contains(related)) {
            return;
        }

        tooltip.classList.remove("is-visible");
    });

    window.addEventListener("scroll", function () {
        tooltip.classList.remove("is-visible");
    }, { passive: true });
})();
</script>


<style>
/* INCIDENT CALENDAR SMALL TOOLTIP */

.incident-calendar-tooltip {
    min-width: 170px !important;
    max-width: 220px !important;
    padding: 8px 10px !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    line-height: 1.3 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.10) !important;
}

.incident-calendar-tooltip-title {
    margin-bottom: 5px !important;
    padding-bottom: 5px !important;
    font-size: 12px !important;
}

.incident-calendar-tooltip-total {
    margin-bottom: 5px !important;
    font-size: 11px !important;
}

.incident-calendar-tooltip-section {
    margin-top: 5px !important;
}

.incident-calendar-tooltip-label {
    margin-bottom: 2px !important;
    font-size: 9px !important;
}

.incident-calendar-tooltip-row {
    gap: 10px !important;
    padding: 1px 0 !important;
}
</style>


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

