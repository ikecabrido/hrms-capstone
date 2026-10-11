import './layout/hamburger.js';
import './utils/main.js';
import './layout/tab-content.js';
import './layout/realtime.js';
import './components/dropdown.js';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-resource-io]').forEach(function (resource) {
        resource.querySelectorAll('[data-resource-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                var tab = button.dataset.resourceTab;
                resource.querySelectorAll('[data-resource-tab]').forEach(function (item) {
                    var isActive = item === button;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                resource.querySelectorAll('[data-resource-panel]').forEach(function (panel) {
                    var isActive = panel.dataset.resourcePanel === tab;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });
            });
        });

        var learningForm = resource.querySelector('[data-learning-form]');
        var learningStatus = resource.querySelector('[data-learning-status]');
        if (learningForm && learningStatus) {
            learningForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!learningForm.checkValidity()) {
                    learningForm.reportValidity();
                    return;
                }
                var engagementStatus = learningForm.querySelector('[name="engagement_status"]').selectedOptions[0].textContent;
                learningStatus.textContent = engagementStatus + ' training engagement data prepared for Learning review.';
                learningStatus.hidden = false;
            });
        }
    });

    var exportButton = document.getElementById('exportWorkforceCsv');
    if (!exportButton) return;

    exportButton.addEventListener('click', function () {
        var rows = Array.from(document.querySelectorAll('.wa-table tr'));
        var csv = rows.map(function (row) {
            return Array.from(row.querySelectorAll('th, td')).map(function (cell) {
                return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'workforce-report.csv';
        link.click();
        setTimeout(function () {
            URL.revokeObjectURL(link.href);
        }, 1000);
    });
});
