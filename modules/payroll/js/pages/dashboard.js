/**
 * Payroll Dashboard
 * ---------------------------------------------------------------------
 * Talks to controllers/dashboardController.php (AJAX/JSON, action=stats),
 * which is a thin, additive self-dispatching endpoint appended to the
 * existing DashboardController — following the exact same pattern already
 * used by controllers/periodController.php, allowanceDeductionController.php,
 * etc. It does not add, change, or invent any database query: it simply
 * exposes the controller's existing getStats() method as JSON so this page
 * keeps working after client-side navigation (see js/utils/main.js, which
 * swaps pages via innerHTML — inline <script>/data would not survive that).
 *
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML, it re-binds/re-fetches every time the
 * "page:loaded" event fires, and exits early if the Dashboard markup isn't
 * present on the current page.
 */

function initDashboard() {
  const root = document.getElementById("dashboardOverviewPage");
  if (!root) return; // Not on the Dashboard page — nothing to do.

  // Charts must be re-rendered every time this page becomes visible again
  // (data may be stale), but we still guard against the DOMContentLoaded +
  // page:loaded double-fire on the very first load.
  if (root.dataset.dashLoading === "true") return;
  root.dataset.dashLoading = "true";

  const ENDPOINT = "controllers/dashboardController.php";

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("dashAlert");

  const headerPeriodName = document.getElementById("dashHeaderPeriodName");
  const headerStatusBadge = document.getElementById("dashHeaderStatusBadge");

  const activeEmployeesEl = document.getElementById("dashActiveEmployees");
  const grossPayrollEl = document.getElementById("dashGrossPayroll");
  const grossPayrollSubEl = document.getElementById("dashGrossPayrollSub");
  const totalDeductionsEl = document.getElementById("dashTotalDeductions");
  const totalDeductionsSubEl = document.getElementById(
    "dashTotalDeductionsSub",
  );
  const netPayrollEl = document.getElementById("dashNetPayroll");
  const netPayrollSubEl = document.getElementById("dashNetPayrollSub");
  const averageNetPayEl = document.getElementById("dashAverageNetPay");
  const lifetimePayrollEl = document.getElementById("dashLifetimePayroll");

  const periodPanelBody = document.getElementById("dashPeriodPanelBody");
  const progressPanelBody = document.getElementById("dashProgressPanelBody");
  const runCountersPanelBody = document.getElementById(
    "dashRunCountersPanelBody",
  );

  const recentRunsBody = document.getElementById("dashRecentRunsBody");
  const recentRunsEmpty = document.getElementById("dashRecentRunsEmpty");

  let charts = {};

  // ---- Helpers --------------------------------------------------------------
  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  function peso(value) {
    const n = Number(value) || 0;
    return (
      "\u20B1" +
      n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function formatDate(value) {
    if (!value) return "\u2014";
    const d = new Date(value + "T00:00:00");
    if (isNaN(d.getTime())) return esc(value);
    return d.toLocaleDateString("en-PH", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  function showAlert(message, type) {
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className =
      "pm-alert " +
      (type === "success" ? "pm-alert-success" : "pm-alert-error");
    alertBox.style.display = "block";
  }

  function statusBadgeClass(status) {
    switch (String(status || "").toLowerCase()) {
      case "open":
        return "pm-badge pm-badge-open";
      case "processing":
        return "pm-badge pm-badge-processing";
      case "closed":
        return "pm-badge pm-badge-closed";
      case "finalized":
        return "pm-badge pm-badge-finalized";
      case "draft":
        return "pm-badge pm-badge-draft";
      default:
        return "pm-badge pm-badge-closed";
    }
  }

  function statusLabel(status) {
    if (!status) return "Unknown";
    return String(status).charAt(0).toUpperCase() + String(status).slice(1);
  }

  // Count-up animation for numeric summary values (integers only, e.g. Active Employees).
  function countUp(el, target) {
    const start = 0;
    const duration = 700;
    const startTime = performance.now();

    function tick(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.round(start + (target - start) * eased);
      el.textContent = value.toLocaleString("en-PH");
      if (progress < 1) requestAnimationFrame(tick);
    }

    if (target > 0) {
      requestAnimationFrame(tick);
    } else {
      el.textContent = "0";
    }
  }

  function destroyCharts() {
    Object.values(charts).forEach((c) => {
      if (c && typeof c.destroy === "function") c.destroy();
    });
    charts = {};
  }

  function toggleEmpty(canvasId, emptyId, hasData) {
    const canvas = document.getElementById(canvasId);
    const empty = document.getElementById(emptyId);
    if (canvas) canvas.style.display = hasData ? "block" : "none";
    if (empty) empty.style.display = hasData ? "none" : "flex";
  }

  const PALETTE = [
    "#514ab7",
    "#200082",
    "#1e9e64",
    "#e53e3e",
    "#b8860b",
    "#334155",
  ];

  // ---- Render: header + summary cards ---------------------------------------
  function renderHeader(data) {
    const period = data.period || data.latest_period || null;

    if (period) {
      headerPeriodName.textContent = period.period_name || "\u2014";
      headerStatusBadge.textContent = statusLabel(period.status);
      headerStatusBadge.className = statusBadgeClass(period.status);
      headerStatusBadge.style.display = "inline-flex";
    } else {
      headerPeriodName.textContent = "No payroll period found";
      headerStatusBadge.style.display = "none";
    }
  }

  function renderSummaryCards(data) {
    const employees = data.employees || {};
    const payroll = data.payroll || {};
    const latestPayroll = data.latest_payroll || {};

    countUp(activeEmployeesEl, Number(employees.active || 0));

    grossPayrollEl.textContent = peso(payroll.gross || 0);
    totalDeductionsEl.textContent = peso(payroll.deductions || 0);
    netPayrollEl.textContent = peso(payroll.net || 0);
    averageNetPayEl.textContent = peso(payroll.average_net_pay || 0);
    lifetimePayrollEl.textContent = peso(payroll.lifetime_net_payroll || 0);

    const subLabel =
      latestPayroll && latestPayroll.period_name
        ? `From ${esc(latestPayroll.period_name)}`
        : "No finalized payroll yet";

    grossPayrollSubEl.textContent = subLabel;
    totalDeductionsSubEl.textContent = subLabel;
    netPayrollSubEl.textContent = subLabel;
  }

  // ---- Render: current period panel ------------------------------------------
  function renderPeriodPanel(data) {
    const period = data.period;
    const latest = data.latest_period;
    const source = period || latest;

    if (!source) {
      periodPanelBody.innerHTML = `<div class="dash-empty-inline">No payroll periods have been created yet.</div>`;
      return;
    }

    const badge = `<span class="${statusBadgeClass(source.status)}">${esc(
      statusLabel(source.status),
    )}</span>`;

    periodPanelBody.innerHTML = `
      <div class="dash-info-row"><span>Period</span><strong>${esc(source.period_name)}</strong></div>
      <div class="dash-info-row"><span>Start Date</span><strong>${formatDate(source.start_date)}</strong></div>
      <div class="dash-info-row"><span>End Date</span><strong>${formatDate(source.end_date)}</strong></div>
      <div class="dash-info-row"><span>Pay Date</span><strong>${formatDate(source.pay_date)}</strong></div>
      <div class="dash-info-row"><span>Status</span>${badge}</div>
      ${!period ? '<div class="dash-empty-inline" style="padding-top:0.5rem;">No open payroll period &mdash; showing the most recent one.</div>' : ""}
    `;
  }

  // ---- Render: progress panel -------------------------------------------------
  function renderProgressPanel(data) {
    const progress = data.progress || {
      total: 0,
      processed: 0,
      pending: 0,
      percentage: 0,
    };
    const run = data.current_run;

    if (!run || !progress.total) {
      progressPanelBody.innerHTML = `<div class="dash-empty-inline">No payroll run is currently in progress.</div>`;
      return;
    }

    progressPanelBody.innerHTML = `
      <div class="dash-progress-caption">
        <span>${progress.processed} of ${progress.total} employees processed</span>
        <span>${progress.percentage}%</span>
      </div>
      <div class="dash-progress-track">
        <div class="dash-progress-fill" style="width:${Math.min(progress.percentage, 100)}%;"></div>
      </div>
      <div class="dash-info-row"><span>Run Status</span>${`<span class="${statusBadgeClass(run.status)}">${esc(statusLabel(run.status))}</span>`}</div>
      <div class="dash-info-row"><span>Pending</span><strong>${progress.pending}</strong></div>
    `;

    // Animate the progress bar in on next frame for a smooth fill effect.
    requestAnimationFrame(() => {
      const fill = progressPanelBody.querySelector(".dash-progress-fill");
      if (fill) fill.style.width = Math.min(progress.percentage, 100) + "%";
    });
  }

  // ---- Render: run counters panel ----------------------------------------------
  function renderRunCountersPanel(data) {
    const finalized = Number(data.finalized_runs || 0);
    const pending = Number(data.pending_runs || 0);

    runCountersPanelBody.innerHTML = `
      <div class="dash-run-counter-row">
        <div class="dash-run-counter">
          <span class="dash-run-counter-value">${finalized}</span>
          <span class="dash-run-counter-label">Finalized Runs</span>
        </div>
        <div class="dash-run-counter">
          <span class="dash-run-counter-value">${pending}</span>
          <span class="dash-run-counter-label">Draft / Pending Runs</span>
        </div>
      </div>
    `;
  }

  // ---- Render: recent activity table --------------------------------------------
  function renderRecentRuns(rows) {
    if (!rows || !rows.length) {
      recentRunsBody.innerHTML = "";
      recentRunsBody.closest(".pm-table-wrapper").style.display = "none";
      recentRunsEmpty.style.display = "block";
      return;
    }

    recentRunsBody.closest(".pm-table-wrapper").style.display = "block";
    recentRunsEmpty.style.display = "none";

    recentRunsBody.innerHTML = rows
      .map(
        (r) => `
        <tr>
          <td>${esc(r.period_name)}</td>
          <td>${Number(r.employee_count || 0)}</td>
          <td>${peso(r.gross_pay)}</td>
          <td>${peso(r.deductions)}</td>
          <td>${peso(r.net_pay)}</td>
          <td><span class="${statusBadgeClass(r.status)}">${esc(statusLabel(r.status))}</span></td>
          <td>${formatDate(r.pay_date)}</td>
        </tr>
      `,
      )
      .join("");
  }

  // ---- Charts -------------------------------------------------------------------
  function renderCharts(graphs) {
    if (typeof Chart === "undefined") {
      console.error("Chart.js failed to load; dashboard graphs cannot render.");
      return;
    }

    destroyCharts();

    // 1. Payroll Expense Trend (line)
    const trend = graphs.payroll_trend || [];
    toggleEmpty("dashTrendChart", "dashTrendEmpty", trend.length > 0);
    if (trend.length) {
      const ctx = document.getElementById("dashTrendChart").getContext("2d");
      charts.trend = new Chart(ctx, {
        type: "line",
        data: {
          labels: trend.map((r) => r.month_label),
          datasets: [
            {
              label: "Gross Pay",
              data: trend.map((r) => r.gross_pay),
              borderColor: PALETTE[0],
              backgroundColor: "rgba(81, 74, 183, 0.12)",
              tension: 0.35,
              fill: true,
            },
            {
              label: "Deductions",
              data: trend.map((r) => r.deductions),
              borderColor: PALETTE[3],
              backgroundColor: "rgba(229, 62, 62, 0.08)",
              tension: 0.35,
              fill: true,
            },
            {
              label: "Net Pay",
              data: trend.map((r) => r.net_pay),
              borderColor: PALETTE[2],
              backgroundColor: "rgba(30, 158, 100, 0.12)",
              tension: 0.35,
              fill: true,
            },
          ],
        },
        options: chartOptions({
          tooltipCurrency: true,
          yTicksCurrency: true,
        }),
      });
    }

    // 2. Gross vs Net Payroll (bar)
    const composition = graphs.payroll_composition || {};
    const hasComposition =
      (composition.gross_pay || 0) > 0 ||
      (composition.deductions || 0) > 0 ||
      (composition.net_pay || 0) > 0;
    toggleEmpty("dashCompositionChart", "dashCompositionEmpty", hasComposition);
    if (hasComposition) {
      const ctx = document
        .getElementById("dashCompositionChart")
        .getContext("2d");
      charts.composition = new Chart(ctx, {
        type: "bar",
        data: {
          labels: ["Gross Pay", "Deductions", "Net Pay"],
          datasets: [
            {
              data: [
                composition.gross_pay,
                composition.deductions,
                composition.net_pay,
              ],
              backgroundColor: [PALETTE[0], PALETTE[3], PALETTE[2]],
              borderRadius: 6,
              maxBarThickness: 60,
            },
          ],
        },
        options: chartOptions({
          tooltipCurrency: true,
          yTicksCurrency: true,
          hideLegend: true,
        }),
      });
    }

    // 3. Deduction Breakdown (doughnut)
    const deductions = graphs.deduction_breakdown || [];
    toggleEmpty(
      "dashDeductionChart",
      "dashDeductionEmpty",
      deductions.length > 0,
    );
    if (deductions.length) {
      const ctx = document
        .getElementById("dashDeductionChart")
        .getContext("2d");
      charts.deduction = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: deductions.map((r) => r.deduction_category),
          datasets: [
            {
              data: deductions.map((r) => r.total_amount),
              backgroundColor: deductions.map(
                (_, i) => PALETTE[i % PALETTE.length],
              ),
              borderWidth: 2,
              borderColor: "#fff",
            },
          ],
        },
        options: doughnutOptions({ tooltipCurrency: true }),
      });
    }

    // 4. Payroll Cost by Department (bar)
    const byDept = graphs.payroll_by_department || [];
    toggleEmpty(
      "dashDepartmentChart",
      "dashDepartmentEmpty",
      byDept.length > 0,
    );
    if (byDept.length) {
      const ctx = document
        .getElementById("dashDepartmentChart")
        .getContext("2d");
      charts.department = new Chart(ctx, {
        type: "bar",
        data: {
          labels: byDept.map((r) => r.department_name),
          datasets: [
            {
              label: "Net Pay",
              data: byDept.map((r) => r.net_pay),
              backgroundColor: PALETTE[2],
              borderRadius: 6,
              maxBarThickness: 42,
            },
          ],
        },
        options: chartOptions({
          tooltipCurrency: true,
          yTicksCurrency: true,
          hideLegend: true,
        }),
      });
    }

    // 5. Active Employees by Department (doughnut)
    const empByDept = graphs.employees_by_department || [];
    toggleEmpty(
      "dashEmployeeDeptChart",
      "dashEmployeeDeptEmpty",
      empByDept.length > 0,
    );
    if (empByDept.length) {
      const ctx = document
        .getElementById("dashEmployeeDeptChart")
        .getContext("2d");
      charts.employeeDept = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: empByDept.map((r) => r.department_name),
          datasets: [
            {
              data: empByDept.map((r) => r.employee_count),
              backgroundColor: empByDept.map(
                (_, i) => PALETTE[i % PALETTE.length],
              ),
              borderWidth: 2,
              borderColor: "#fff",
            },
          ],
        },
        options: doughnutOptions({ tooltipCurrency: false }),
      });
    }

    // 6. Employees by Employment Type (doughnut)
    const empByType = graphs.employees_by_employment_type || [];
    toggleEmpty(
      "dashEmployeeTypeChart",
      "dashEmployeeTypeEmpty",
      empByType.length > 0,
    );
    if (empByType.length) {
      const ctx = document
        .getElementById("dashEmployeeTypeChart")
        .getContext("2d");
      charts.employeeType = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: empByType.map((r) => r.employment_type),
          datasets: [
            {
              data: empByType.map((r) => r.employee_count),
              backgroundColor: empByType.map(
                (_, i) => PALETTE[(i + 2) % PALETTE.length],
              ),
              borderWidth: 2,
              borderColor: "#fff",
            },
          ],
        },
        options: doughnutOptions({ tooltipCurrency: false }),
      });
    }
  }

  function chartOptions({ tooltipCurrency, yTicksCurrency, hideLegend }) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 650, easing: "easeOutQuart" },
      plugins: {
        legend: {
          display: !hideLegend,
          position: "bottom",
          labels: { boxWidth: 12, font: { size: 11 } },
        },
        tooltip: {
          callbacks: tooltipCurrency
            ? {
                label: (ctx) =>
                  `${ctx.dataset.label ? ctx.dataset.label + ": " : ""}\u20B1${Number(
                    ctx.parsed.y ?? ctx.parsed,
                  ).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })}`,
              }
            : {},
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: yTicksCurrency
            ? {
                callback: (v) => "\u20B1" + Number(v).toLocaleString("en-PH"),
                font: { size: 10 },
              }
            : { font: { size: 10 } },
          grid: { color: "rgba(0,0,0,0.05)" },
        },
        x: {
          ticks: { font: { size: 10 } },
          grid: { display: false },
        },
      },
    };
  }

  function doughnutOptions({ tooltipCurrency }) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 650, easing: "easeOutQuart" },
      plugins: {
        legend: {
          position: "bottom",
          labels: { boxWidth: 12, font: { size: 11 } },
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + Number(b), 0);
              const pct =
                total > 0
                  ? ((Number(ctx.parsed) / total) * 100).toFixed(1)
                  : "0.0";
              const value = tooltipCurrency
                ? "\u20B1" +
                  Number(ctx.parsed).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })
                : Number(ctx.parsed).toLocaleString("en-PH");
              return `${ctx.label}: ${value} (${pct}%)`;
            },
          },
        },
      },
    };
  }

  // ---- Load ---------------------------------------------------------------------
  function loadDashboard() {
    fetch(`${ENDPOINT}?action=stats`, { credentials: "same-origin" })
      .then((response) => {
        if (response.status === 401) {
          return response.json().then((data) => {
            if (data.redirect) window.location.href = data.redirect;
          });
        }
        return response.json();
      })
      .then((result) => {
        if (!result) return;
        if (!result.success) {
          showAlert(
            result.message || "Failed to load dashboard data.",
            "error",
          );
          return;
        }

        const data = result.data || {};

        renderHeader(data);
        renderSummaryCards(data);
        renderPeriodPanel(data);
        renderProgressPanel(data);
        renderRunCountersPanel(data);
        renderRecentRuns(data.recent_runs || []);
        renderCharts(data.graphs || {});
      })
      .catch((err) => {
        console.error("Dashboard load failed", err);
        showAlert(
          "Unable to load the payroll dashboard right now. Please try again.",
          "error",
        );
      })
      .finally(() => {
        root.dataset.dashLoading = "false";
      });
  }

  loadDashboard();
}

window.addEventListener("page:loaded", initDashboard);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initDashboard);
} else {
  initDashboard();
}
