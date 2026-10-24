/**
 * Payroll Reports
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/reportController.php (AJAX/JSON)
 * for report data, and reuses modules/payroll/controllers/periodController.php
 * ("list" action) for the Payroll Period filter — same pattern payslips.js
 * already uses, since ReportController doesn't expose a dedicated period
 * search endpoint (only getPeriods(), which returns everything).
 *
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML (see js/utils/main.js), it re-binds its
 * listeners every time the "page:loaded" event fires, and simply exits
 * early if the Reports markup isn't present on the current page.
 */

function initReports() {
  const root = document.getElementById("reportsPage");
  if (!root) return; // Not on the Reports page — nothing to do.
  if (root.dataset.rpInitialized === "true") return;
  root.dataset.rpInitialized = "true";

  const REPORT_ENDPOINT = "controllers/reportController.php";
  const PERIOD_ENDPOINT = "controllers/periodController.php";

  let currentReport = null; // { type, period, employee, ...data } of the last successfully generated report

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("rpAlert");

  const reportTypeSelect = document.getElementById("rpReportType");
  const periodFieldGroup = document.getElementById("rpPeriodFieldGroup");
  const employeeFieldGroup = document.getElementById("rpEmployeeFieldGroup");
  const periodFilter = document.getElementById("rpPeriodFilter");
  const employeeFilter = document.getElementById("rpEmployeeFilter");

  const btnGenerate = document.getElementById("rpBtnGenerate");
  const btnReset = document.getElementById("rpBtnReset");
  const btnPrint = document.getElementById("rpBtnPrint");

  const emptyState = document.getElementById("rpEmptyState");
  const previewCard = document.getElementById("rpPreviewCard");
  const reportTitleEl = document.getElementById("rpReportTitle");
  const reportPeriodEl = document.getElementById("rpReportPeriod");
  const reportGeneratedEl = document.getElementById("rpReportGenerated");
  const reportRunEl = document.getElementById("rpReportRun");
  const reportBodyEl = document.getElementById("rpReportBody");

  const printSheet = document.getElementById("rpPrintSheet");

  const REPORT_TYPE_LABELS = {
    payroll_register: "Payroll Register",
    payroll_summary: "Payroll Summary",
    item_summary: "Earnings & Deductions Breakdown",
    department_summary: "Department Summary",
    employee_history: "Employee Payroll History",
  };

  // ---- Helpers --------------------------------------------------------------
  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  function formatCurrency(value) {
    const n = Number(value) || 0;
    return (
      "\u20B1" +
      n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function formatDate(dateStr) {
    if (!dateStr) return "\u2014";
    const d = new Date(dateStr + (dateStr.length <= 10 ? "T00:00:00" : ""));
    if (isNaN(d.getTime())) return esc(dateStr);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }

  function formatDateTime(dateStr) {
    if (!dateStr) return "\u2014";
    const d = new Date(dateStr.replace(" ", "T"));
    if (isNaN(d.getTime())) return esc(dateStr);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  }

  function showAlert(message, type) {
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className =
      "pm-alert " +
      (type === "success" ? "pm-alert-success" : "pm-alert-error");
    alertBox.style.display = "block";
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(function () {
      alertBox.style.display = "none";
    }, 5000);
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  async function apiRequest(endpoint, action, params = null) {
    let url = `${endpoint}?action=${encodeURIComponent(action)}`;
    if (params) {
      const qs = new URLSearchParams(params).toString();
      if (qs) url += `&${qs}`;
    }

    const res = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    if (res.status === 401) {
      let data = {};
      try {
        data = await res.json();
      } catch (e) {
        /* ignore */
      }
      if (data.redirect) window.location.href = data.redirect;
      throw new Error("Session expired. Please log in again.");
    }

    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error("Unexpected server response.");
    }

    return data;
  }

  // ---- Payroll Period combobox (client-filtered, mirrors payslips.js) --------------------------------------------------------------
  function createClientCombobox({
    inputEl,
    hiddenEl,
    optionsEl,
    clearBtn,
    getId,
    getLabel,
    getSubLabel,
  }) {
    let data = [];
    let filtered = [];
    let highlightedIndex = -1;

    function setData(items) {
      data = items || [];
    }

    function defaultMatch(item, q) {
      const hay = (
        getLabel(item) +
        " " +
        (getSubLabel ? getSubLabel(item) : "")
      ).toLowerCase();
      return hay.includes(q);
    }

    function updateClearButton() {
      clearBtn.classList.toggle(
        "ps-combobox-clear-visible",
        hiddenEl.value !== "",
      );
    }

    function updateHighlight() {
      const items = optionsEl.querySelectorAll(".ps-combobox-option");
      items.forEach(function (el, idx) {
        el.classList.toggle(
          "ps-combobox-highlighted",
          idx === highlightedIndex,
        );
      });
      if (items[highlightedIndex]) {
        items[highlightedIndex].scrollIntoView({ block: "nearest" });
      }
    }

    function renderOptions(list) {
      filtered = list;
      if (list.length === 0) {
        optionsEl.innerHTML =
          '<div class="ps-combobox-empty">No matches found</div>';
        highlightedIndex = -1;
        return;
      }
      optionsEl.innerHTML = list
        .map(function (item, idx) {
          const sub = getSubLabel ? getSubLabel(item) : "";
          return `<div class="ps-combobox-option" role="option" data-index="${idx}">
                    <span class="ps-combobox-option-label">${esc(getLabel(item))}</span>
                    ${sub ? `<span class="ps-combobox-option-sub">${esc(sub)}</span>` : ""}
                  </div>`;
        })
        .join("");
      highlightedIndex = 0;
      updateHighlight();
    }

    function openDropdown() {
      optionsEl.classList.add("ps-combobox-open");
      inputEl.setAttribute("aria-expanded", "true");
    }

    function closeDropdown() {
      optionsEl.classList.remove("ps-combobox-open");
      inputEl.setAttribute("aria-expanded", "false");
      highlightedIndex = -1;
    }

    function runFilter(query) {
      const q = query.trim().toLowerCase();
      if (q === "") {
        closeDropdown();
        return;
      }
      const list = data.filter((item) => defaultMatch(item, q));
      renderOptions(list);
      openDropdown();
    }

    function selectItem(item) {
      hiddenEl.value = getId(item);
      inputEl.value = getLabel(item);
      inputEl.dataset.selectedLabel = getLabel(item);
      updateClearButton();
      closeDropdown();
    }

    function clearSelection() {
      hiddenEl.value = "";
      inputEl.value = "";
      inputEl.dataset.selectedLabel = "";
      updateClearButton();
      closeDropdown();
    }

    inputEl.addEventListener("input", function () {
      if (inputEl.value === "") hiddenEl.value = "";
      updateClearButton();
      runFilter(inputEl.value);
    });

    inputEl.addEventListener("focus", function () {
      if (inputEl.value.trim() !== "") runFilter(inputEl.value);
    });

    inputEl.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        if (!filtered.length) return;
        highlightedIndex = Math.min(highlightedIndex + 1, filtered.length - 1);
        updateHighlight();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        if (!filtered.length) return;
        highlightedIndex = Math.max(highlightedIndex - 1, 0);
        updateHighlight();
      } else if (e.key === "Enter") {
        e.preventDefault();
        if (highlightedIndex >= 0 && filtered[highlightedIndex]) {
          selectItem(filtered[highlightedIndex]);
        }
      } else if (e.key === "Escape") {
        closeDropdown();
      }
    });

    optionsEl.addEventListener("mousedown", function (e) {
      const optEl = e.target.closest(".ps-combobox-option");
      if (!optEl) return;
      e.preventDefault();
      const item = filtered[Number(optEl.getAttribute("data-index"))];
      if (item) selectItem(item);
    });

    inputEl.addEventListener("blur", function () {
      window.setTimeout(function () {
        if (!hiddenEl.value) {
          inputEl.value = "";
        } else if (inputEl.dataset.selectedLabel) {
          inputEl.value = inputEl.dataset.selectedLabel;
        }
        closeDropdown();
      }, 120);
    });

    clearBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      clearSelection();
      inputEl.focus();
    });

    document.addEventListener("click", function (e) {
      const combobox = inputEl.closest(".ps-combobox");
      if (combobox && !combobox.contains(e.target)) closeDropdown();
    });

    return { setData, clearSelection };
  }

  const periodCombobox = createClientCombobox({
    inputEl: document.getElementById("rpPeriodSearchInput"),
    hiddenEl: periodFilter,
    optionsEl: document.getElementById("rpPeriodOptions"),
    clearBtn: document.getElementById("rpPeriodClearBtn"),
    getId: (p) => p.period_id,
    getLabel: (p) => p.period_name,
    getSubLabel: (p) =>
      `${formatDate(p.start_date)} \u2013 ${formatDate(p.end_date)} \u00b7 Pay: ${formatDate(p.pay_date)} \u00b7 ${(p.status || "").toUpperCase()}`,
  });

  async function loadPeriodOptions() {
    try {
      const data = await apiRequest(PERIOD_ENDPOINT, "list");
      periodCombobox.setData(data.success ? data.data || [] : []);
    } catch (err) {
      /* combobox just stays empty */
    }
  }

  // ---- Employee combobox (server search, mirrors deductions.js) --------------------------------------------------------------
  function createSearchCombobox({
    inputEl,
    hiddenEl,
    optionsEl,
    clearBtn,
    fetchResults,
    getId,
    getLabel,
    getSubLabel,
  }) {
    let results = [];
    let highlightedIndex = -1;
    let lastQueryToken = 0;

    function updateClearButton() {
      clearBtn.classList.toggle(
        "ps-combobox-clear-visible",
        hiddenEl.value !== "",
      );
    }

    function updateHighlight() {
      const items = optionsEl.querySelectorAll(".ps-combobox-option");
      items.forEach(function (el, idx) {
        el.classList.toggle(
          "ps-combobox-highlighted",
          idx === highlightedIndex,
        );
      });
      if (items[highlightedIndex]) {
        items[highlightedIndex].scrollIntoView({ block: "nearest" });
      }
    }

    function renderOptions(list) {
      results = list;
      if (list.length === 0) {
        optionsEl.innerHTML =
          '<div class="ps-combobox-empty">No matches found</div>';
        highlightedIndex = -1;
        return;
      }
      optionsEl.innerHTML = list
        .map(function (item, idx) {
          const sub = getSubLabel ? getSubLabel(item) : "";
          return `<div class="ps-combobox-option" role="option" data-index="${idx}">
                    <span class="ps-combobox-option-label">${esc(getLabel(item))}</span>
                    ${sub ? `<span class="ps-combobox-option-sub">${esc(sub)}</span>` : ""}
                  </div>`;
        })
        .join("");
      highlightedIndex = 0;
      updateHighlight();
    }

    function showLoading() {
      optionsEl.innerHTML =
        '<div class="ps-combobox-empty"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
      highlightedIndex = -1;
      openDropdown();
    }

    function openDropdown() {
      optionsEl.classList.add("ps-combobox-open");
      inputEl.setAttribute("aria-expanded", "true");
    }

    function closeDropdown() {
      optionsEl.classList.remove("ps-combobox-open");
      inputEl.setAttribute("aria-expanded", "false");
      highlightedIndex = -1;
    }

    const runSearch = debounce(async function (query) {
      const token = ++lastQueryToken;
      if (query.trim() === "") {
        closeDropdown();
        return;
      }
      showLoading();
      try {
        const items = await fetchResults(query.trim());
        if (token !== lastQueryToken) return;
        renderOptions(items);
        openDropdown();
      } catch (err) {
        if (token !== lastQueryToken) return;
        optionsEl.innerHTML =
          '<div class="ps-combobox-empty">Search failed. Try again.</div>';
      }
    }, 300);

    function selectItem(item) {
      hiddenEl.value = getId(item);
      inputEl.value = getLabel(item);
      inputEl.dataset.selectedLabel = getLabel(item);
      updateClearButton();
      closeDropdown();
    }

    function clearSelection() {
      hiddenEl.value = "";
      inputEl.value = "";
      inputEl.dataset.selectedLabel = "";
      updateClearButton();
      closeDropdown();
    }

    inputEl.addEventListener("input", function () {
      if (inputEl.value === "") hiddenEl.value = "";
      updateClearButton();
      runSearch(inputEl.value);
    });

    inputEl.addEventListener("focus", function () {
      if (inputEl.value.trim() !== "") runSearch(inputEl.value);
    });

    inputEl.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        if (!results.length) return;
        highlightedIndex = Math.min(highlightedIndex + 1, results.length - 1);
        updateHighlight();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        if (!results.length) return;
        highlightedIndex = Math.max(highlightedIndex - 1, 0);
        updateHighlight();
      } else if (e.key === "Enter") {
        e.preventDefault();
        if (highlightedIndex >= 0 && results[highlightedIndex]) {
          selectItem(results[highlightedIndex]);
        }
      } else if (e.key === "Escape") {
        closeDropdown();
      }
    });

    optionsEl.addEventListener("mousedown", function (e) {
      const optEl = e.target.closest(".ps-combobox-option");
      if (!optEl) return;
      e.preventDefault();
      const item = results[Number(optEl.getAttribute("data-index"))];
      if (item) selectItem(item);
    });

    inputEl.addEventListener("blur", function () {
      window.setTimeout(function () {
        if (!hiddenEl.value) {
          inputEl.value = "";
        } else if (inputEl.dataset.selectedLabel) {
          inputEl.value = inputEl.dataset.selectedLabel;
        }
        closeDropdown();
      }, 120);
    });

    clearBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      clearSelection();
      inputEl.focus();
    });

    document.addEventListener("click", function (e) {
      const combobox = inputEl.closest(".ps-combobox");
      if (combobox && !combobox.contains(e.target)) closeDropdown();
    });

    return { clearSelection };
  }

  const employeeCombobox = createSearchCombobox({
    inputEl: document.getElementById("rpEmployeeSearchInput"),
    hiddenEl: employeeFilter,
    optionsEl: document.getElementById("rpEmployeeOptions"),
    clearBtn: document.getElementById("rpEmployeeClearBtn"),
    fetchResults: async function (q) {
      const data = await apiRequest(REPORT_ENDPOINT, "search_employees", { q });
      return data.success ? data.data || [] : [];
    },
    getId: (e) => e.employee_id,
    getLabel: (e) => `${e.employee_code} \u2014 ${e.employee_name}`,
    getSubLabel: (e) =>
      [e.position_name, e.department_name].filter(Boolean).join(" \u00b7 "),
  });

  // ---- Report type switching --------------------------------------------------------------
  function syncFieldsToReportType() {
    const type = reportTypeSelect.value;
    if (type === "employee_history") {
      periodFieldGroup.style.display = "none";
      employeeFieldGroup.style.display = "";
    } else {
      periodFieldGroup.style.display = "";
      employeeFieldGroup.style.display = "none";
    }
  }

  reportTypeSelect.addEventListener("change", syncFieldsToReportType);
  syncFieldsToReportType();

  // ---- Number helpers for dense report tables --------------------------------------------------------------
  // Money columns omit the peso sign (the column headers say "₱") so that
  // eight or more amount columns stay readable on screen and on paper.
  function formatAmount(value) {
    const n = Number(value) || 0;
    return n.toLocaleString("en-PH", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function formatAmountOrDash(value) {
    return Number(value) ? formatAmount(value) : "–";
  }

  function sumOf(rows, key) {
    return rows.reduce(function (total, r) {
      return total + (Number(r[key]) || 0);
    }, 0);
  }

  function formatPercent(part, total) {
    const t = Number(total) || 0;
    if (t <= 0) return "—";
    return (((Number(part) || 0) / t) * 100).toFixed(1) + "%";
  }

  function plural(n, one, many) {
    return n + " " + (n === 1 ? one : many);
  }

  // ---- Payroll run reference (run number, finalized date, who finalized it) ----------------------------------------
  function describeRun(run) {
    if (!run) return "";
    const parts = ["Payroll Run #" + run.run_id];
    if (run.finalized_at) {
      parts.push("Finalized " + formatDateTime(run.finalized_at));
    }
    if (run.finalized_by_name) {
      parts.push("by " + run.finalized_by_name);
    }
    return parts.join(" · ");
  }

  function employeeLine(e) {
    return [
      `${e.employee_code} — ${e.employee_name}`,
      e.position_name,
      e.department_name,
    ]
      .filter(Boolean)
      .join(" · ");
  }

  // ---- Summary block (shared by all period-based report types) --------------------------------------------------------------
  function renderSummaryBlock(summary) {
    return `
      <div class="rp-summary-grid">
        <div class="rp-summary-item">
          <span>Total Employees</span>
          <strong>${esc(summary.total_employees)}</strong>
        </div>
        <div class="rp-summary-item">
          <span>Total Gross Pay</span>
          <strong>${formatCurrency(summary.total_gross)}</strong>
        </div>
        <div class="rp-summary-item">
          <span>Total Deductions</span>
          <strong>${formatCurrency(summary.total_deductions)}</strong>
        </div>
        <div class="rp-summary-item rp-summary-item-highlight">
          <span>Net Payroll</span>
          <strong>${formatCurrency(summary.total_net)}</strong>
        </div>
        <div class="rp-summary-item">
          <span>Average Net Pay</span>
          <strong>${formatCurrency(summary.average_net)}</strong>
        </div>
      </div>
    `;
  }

  // Shown under a report when the itemised lines do not add up to the
  // payslip totals (for example an old payslip with no line items).
  function renderReconciliationNote(summary) {
    const earningsDiff =
      (Number(summary.total_gross) || 0) -
      (Number(summary.earnings_items_total) || 0);
    const deductionsDiff =
      (Number(summary.total_deductions) || 0) -
      (Number(summary.deduction_items_total) || 0);

    if (Math.abs(earningsDiff) < 0.01 && Math.abs(deductionsDiff) < 0.01) {
      return "";
    }

    return `<p class="rp-note">Note: itemised lines total
      ${formatCurrency(summary.earnings_items_total)} earnings and
      ${formatCurrency(summary.deduction_items_total)} deductions, which differs from the
      payslip totals above by ${formatCurrency(Math.abs(earningsDiff))} and
      ${formatCurrency(Math.abs(deductionsDiff))}. This usually means some payslips
      have no itemised lines or had deductions capped at gross pay.</p>`;
  }

  // ---- Table renderers per report type --------------------------------------------------------------
  function renderPayrollRegisterTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }

    const money = [
      "basic_pay",
      "other_earnings",
      "gross_pay",
      "sss",
      "philhealth",
      "pagibig",
      "tax",
      "late_absence",
      "other_deductions",
      "total_deductions",
      "net_pay",
    ];

    const totalCells = money
      .map(function (key) {
        const strong =
          key === "gross_pay" ||
          key === "total_deductions" ||
          key === "net_pay";
        return `<td class="rp-num${strong ? " rp-num-strong" : ""}">${formatAmount(sumOf(rows, key))}</td>`;
      })
      .join("");

    return `
      <table class="pm-table rp-report-table rp-register-table">
        <thead>
          <tr>
            <th class="rp-col-no">#</th>
            <th>Employee</th>
            <th class="rp-num">Basic Pay (₱)</th>
            <th class="rp-num">Other Earnings</th>
            <th class="rp-num">Gross Pay</th>
            <th class="rp-num">SSS</th>
            <th class="rp-num">PhilHealth</th>
            <th class="rp-num">Pag-IBIG</th>
            <th class="rp-num">W/Tax</th>
            <th class="rp-num">Late / Absence</th>
            <th class="rp-num">Other Ded.</th>
            <th class="rp-num">Total Ded.</th>
            <th class="rp-num">Net Pay</th>
          </tr>
        </thead>
        <tbody>
          ${rows
            .map(
              (r, i) => `
            <tr>
              <td class="rp-col-no">${i + 1}</td>
              <td>
                <span class="rp-emp-name">${esc(r.employee_name)}</span>
                <span class="rp-emp-sub">${esc(
                  [r.employee_code, r.position_name, r.employment_type]
                    .filter(Boolean)
                    .join(" · "),
                )}</span>
                <span class="rp-emp-sub">${esc(r.department_name || "—")}</span>
              </td>
              <td class="rp-num">${formatAmount(r.basic_pay)}</td>
              <td class="rp-num">${formatAmountOrDash(r.other_earnings)}</td>
              <td class="rp-num rp-num-strong">${formatAmount(r.gross_pay)}</td>
              <td class="rp-num">${formatAmountOrDash(r.sss)}</td>
              <td class="rp-num">${formatAmountOrDash(r.philhealth)}</td>
              <td class="rp-num">${formatAmountOrDash(r.pagibig)}</td>
              <td class="rp-num">${formatAmountOrDash(r.tax)}</td>
              <td class="rp-num">${formatAmountOrDash(r.late_absence)}</td>
              <td class="rp-num">${formatAmountOrDash(r.other_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatAmountOrDash(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatAmount(r.net_pay)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
        <tfoot>
          <tr class="rp-total-row">
            <td colspan="2">Total — ${esc(plural(rows.length, "employee", "employees"))}</td>
            ${totalCells}
          </tr>
        </tfoot>
      </table>
    `;
  }

  // Earnings and deductions by category, with a subtotal for each side.
  function renderItemSummaryTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }

    const earnings = rows.filter((r) => r.item_type === "earning");
    const deductions = rows.filter((r) => r.item_type !== "earning");

    function section(title, items, subtotalLabel) {
      if (!items.length) return "";
      const total = sumOf(items, "total_amount");
      return `
        <tr class="rp-group-row"><td colspan="4">${esc(title)}</td></tr>
        ${items
          .map(
            (r) => `
          <tr>
            <td>${esc(r.label || r.description)}</td>
            <td class="rp-num">${esc(r.employee_count)}</td>
            <td class="rp-num">${formatCurrency(r.total_amount)}</td>
            <td class="rp-num">${formatPercent(r.total_amount, total)}</td>
          </tr>`,
          )
          .join("")}
        <tr class="rp-subtotal-row">
          <td>${esc(subtotalLabel)}</td>
          <td class="rp-num"></td>
          <td class="rp-num rp-num-strong">${formatCurrency(total)}</td>
          <td class="rp-num">100.0%</td>
        </tr>`;
    }

    return `
      <table class="pm-table rp-report-table rp-breakdown-table">
        <thead>
          <tr>
            <th>Category</th>
            <th class="rp-num">Employees</th>
            <th class="rp-num">Total Amount</th>
            <th class="rp-num">Share</th>
          </tr>
        </thead>
        <tbody>
          ${section("Earnings", earnings, "Total Earnings")}
          ${section("Deductions", deductions, "Total Deductions")}
        </tbody>
      </table>
    `;
  }

  // Gross-to-net statement for the Payroll Summary report.
  function renderGrossToNetTable(itemRows, summary) {
    const earnings = itemRows.filter((r) => r.item_type === "earning");
    const deductions = itemRows.filter((r) => r.item_type !== "earning");

    return `
      <table class="pm-table rp-report-table rp-statement-table">
        <tbody>
          ${earnings
            .map(
              (r) => `
            <tr>
              <td>${esc(r.label || r.description)}</td>
              <td class="rp-num">${formatCurrency(r.total_amount)}</td>
            </tr>`,
            )
            .join("")}
          <tr class="rp-subtotal-row">
            <td>Gross Pay</td>
            <td class="rp-num rp-num-strong">${formatCurrency(summary.total_gross)}</td>
          </tr>
          ${deductions
            .map(
              (r) => `
            <tr>
              <td>Less: ${esc(r.label || r.description)}</td>
              <td class="rp-num">${formatCurrency(r.total_amount)}</td>
            </tr>`,
            )
            .join("")}
          <tr class="rp-subtotal-row">
            <td>Total Deductions</td>
            <td class="rp-num rp-num-strong">${formatCurrency(summary.total_deductions)}</td>
          </tr>
          <tr class="rp-total-row">
            <td>Net Payroll</td>
            <td class="rp-num">${formatCurrency(summary.total_net)}</td>
          </tr>
        </tbody>
      </table>
    `;
  }

  function renderEmploymentSummaryTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }
    return `
      <table class="pm-table rp-report-table">
        <thead>
          <tr>
            <th>Employment Type</th>
            <th class="rp-num">Employees</th>
            <th class="rp-num">Gross Pay</th>
            <th class="rp-num">Deductions</th>
            <th class="rp-num">Net Pay</th>
          </tr>
        </thead>
        <tbody>
          ${rows
            .map(
              (r) => `
            <tr>
              <td>${esc(r.employment_type || "Unspecified")}</td>
              <td class="rp-num">${esc(r.total_employees)}</td>
              <td class="rp-num">${formatCurrency(r.total_gross)}</td>
              <td class="rp-num">${formatCurrency(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.total_net)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
        <tfoot>
          <tr class="rp-total-row">
            <td>Total</td>
            <td class="rp-num">${sumOf(rows, "total_employees")}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_gross"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_deductions"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_net"))}</td>
          </tr>
        </tfoot>
      </table>
    `;
  }

  function renderDepartmentSummaryTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }
    return `
      <table class="pm-table rp-report-table">
        <thead>
          <tr>
            <th>Department</th>
            <th class="rp-num">Employees</th>
            <th class="rp-num">Gross Pay</th>
            <th class="rp-num">Deductions</th>
            <th class="rp-num">Net Pay</th>
          </tr>
        </thead>
        <tbody>
          ${rows
            .map(
              (r) => `
            <tr>
              <td>${esc(r.department_name || "Unassigned")}</td>
              <td class="rp-num">${esc(r.total_employees)}</td>
              <td class="rp-num">${formatCurrency(r.total_gross)}</td>
              <td class="rp-num">${formatCurrency(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.total_net)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
        <tfoot>
          <tr class="rp-total-row">
            <td>Total</td>
            <td class="rp-num">${sumOf(rows, "total_employees")}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_gross"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_deductions"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(rows, "total_net"))}</td>
          </tr>
        </tfoot>
      </table>
    `;
  }

  function renderEmployeeHistoryTable(history) {
    if (history.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }
    return `
      <table class="pm-table rp-report-table">
        <thead>
          <tr>
            <th>Payroll Period</th>
            <th>Date Range</th>
            <th>Pay Date</th>
            <th class="rp-num">Gross Pay</th>
            <th class="rp-num">Deductions</th>
            <th class="rp-num">Net Pay</th>
            <th>Generated</th>
          </tr>
        </thead>
        <tbody>
          ${history
            .map(
              (r) => `
            <tr>
              <td>${esc(r.period_name)}</td>
              <td>${formatDate(r.start_date)} – ${formatDate(r.end_date)}</td>
              <td>${formatDate(r.pay_date)}</td>
              <td class="rp-num">${formatCurrency(r.gross_pay)}</td>
              <td class="rp-num">${formatCurrency(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.net_pay)}</td>
              <td>${formatDateTime(r.generated_at)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
        <tfoot>
          <tr class="rp-total-row">
            <td colspan="3">Total — ${esc(plural(history.length, "payslip", "payslips"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(history, "gross_pay"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(history, "total_deductions"))}</td>
            <td class="rp-num">${formatCurrency(sumOf(history, "net_pay"))}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    `;
  }

  // Titled sections used by the on-screen preview of the Summary report.
  function renderTitledBlock(title, innerHtml) {
    return `
      <div class="rp-block">
        <div class="rp-block-title">${esc(title)}</div>
        ${innerHtml}
      </div>
    `;
  }

  // ---- Main render --------------------------------------------------------------
  function renderReport() {
    const r = currentReport;
    reportTitleEl.textContent = REPORT_TYPE_LABELS[r.type] || "Payroll Report";
    reportRunEl.textContent = "";

    if (r.type === "employee_history") {
      reportPeriodEl.textContent = `Employee: ${employeeLine(r.employee)}`;
      reportGeneratedEl.textContent = `Generated: ${formatDateTime(new Date().toISOString())}`;
      reportBodyEl.innerHTML = `<div class="rp-table-wrapper">${renderEmployeeHistoryTable(r.history)}</div>`;
      previewCard.style.display = "";
      emptyState.style.display = "none";
      btnPrint.disabled = false;
      return;
    }

    reportPeriodEl.textContent = `Payroll Period: ${r.period.period_name} (${formatDate(r.period.start_date)} – ${formatDate(r.period.end_date)})`;
    reportGeneratedEl.textContent = `Generated: ${formatDateTime(new Date().toISOString())}`;

    if (!r.has_finalized_payroll) {
      reportBodyEl.innerHTML =
        '<p class="rp-no-data">No finalized payroll is available for this payroll period.</p>';
      previewCard.style.display = "";
      emptyState.style.display = "none";
      btnPrint.disabled = true;
      return;
    }

    reportRunEl.textContent = describeRun(r.run);

    let tableHtml = "";
    if (r.type === "payroll_register") {
      tableHtml = renderPayrollRegisterTable(r.payroll);
    } else if (r.type === "item_summary") {
      tableHtml = renderItemSummaryTable(r.item_summary);
    } else if (r.type === "department_summary") {
      tableHtml = renderDepartmentSummaryTable(r.department_summary);
    } else if (r.type === "payroll_summary") {
      tableHtml =
        renderTitledBlock(
          "Gross-to-Net Statement",
          renderGrossToNetTable(r.item_summary, r.summary),
        ) +
        renderTitledBlock(
          "By Employment Type",
          renderEmploymentSummaryTable(r.employment_summary || []),
        );
    }

    const note =
      r.type === "item_summary" || r.type === "payroll_summary"
        ? renderReconciliationNote(r.summary)
        : "";

    reportBodyEl.innerHTML = `
      ${renderSummaryBlock(r.summary)}
      ${tableHtml ? `<div class="rp-table-wrapper">${tableHtml}</div>` : ""}
      ${note}
    `;

    previewCard.style.display = "";
    emptyState.style.display = "none";
    btnPrint.disabled = false;
  }

  // ---- Generate --------------------------------------------------------------
  async function generateReport() {
    const type = reportTypeSelect.value;

    if (type === "employee_history") {
      if (!employeeFilter.value) {
        showAlert("Please select an employee.", "error");
        return;
      }
    } else if (!periodFilter.value) {
      showAlert("Please select a payroll period.", "error");
      return;
    }

    btnGenerate.disabled = true;
    btnGenerate.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
    btnPrint.disabled = true;

    try {
      if (type === "employee_history") {
        const data = await apiRequest(REPORT_ENDPOINT, "employee_history", {
          employee_id: employeeFilter.value,
        });
        if (!data.success) {
          showAlert(
            data.message || "Unable to generate the report. Please try again.",
            "error",
          );
          return;
        }
        currentReport = {
          type,
          employee: data.data.employee,
          history: data.data.history,
        };
      } else {
        const data = await apiRequest(REPORT_ENDPOINT, "period_report", {
          period_id: periodFilter.value,
        });
        if (!data.success) {
          showAlert(
            data.message || "Unable to generate the report. Please try again.",
            "error",
          );
          return;
        }
        currentReport = { type, ...data.data };
      }

      renderReport();
    } catch (err) {
      showAlert(
        err.message || "Unable to generate the report. Please try again.",
        "error",
      );
    } finally {
      btnGenerate.disabled = false;
      btnGenerate.innerHTML =
        '<i class="fa-solid fa-file-invoice"></i> Generate Report';
    }
  }

  btnGenerate.addEventListener("click", generateReport);

  btnReset.addEventListener("click", function () {
    reportTypeSelect.value = "payroll_register";
    syncFieldsToReportType();
    periodCombobox.clearSelection();
    employeeCombobox.clearSelection();
    currentReport = null;
    previewCard.style.display = "none";
    emptyState.style.display = "";
    btnPrint.disabled = true;
  });

  // ---- Print --------------------------------------------------------------
  function printSectionHtml(title, tableHtml) {
    return `
      <div class="rp-doc-section">
        <div class="rp-doc-section-title">${esc(title)}</div>
        ${tableHtml}
      </div>
    `;
  }

  // Turns an on-screen table into its print version.
  function toPrintTable(html) {
    return html.replace(/pm-table/, "rp-doc-table");
  }

  function buildPrintDocument(r) {
    const now = formatDateTime(new Date().toISOString());
    const title = REPORT_TYPE_LABELS[r.type] || "Payroll Report";
    const isHistory = r.type === "employee_history";

    const periodLine = isHistory
      ? `Employee: ${esc(employeeLine(r.employee))}`
      : `Payroll Period: ${esc(r.period.period_name)} (${formatDate(r.period.start_date)} – ${formatDate(r.period.end_date)})`;

    const runLine = !isHistory && r.run ? esc(describeRun(r.run)) : "";

    // Footer reference printed on every page.
    const footerRef = isHistory
      ? `${title} · ${r.employee.employee_code}`
      : `${title} · ${r.period.period_name}${r.run ? " · Run #" + r.run.run_id : ""}`;

    let bodyHtml = "";

    if (isHistory) {
      bodyHtml = printSectionHtml(
        "Payroll History",
        toPrintTable(renderEmployeeHistoryTable(r.history)),
      );
    } else if (!r.has_finalized_payroll) {
      bodyHtml =
        '<p class="rp-no-data">No finalized payroll is available for this payroll period.</p>';
    } else if (r.type === "payroll_register") {
      // The table's own totals row replaces the separate totals block.
      bodyHtml = printSectionHtml(
        "Payroll Register",
        toPrintTable(renderPayrollRegisterTable(r.payroll)),
      );
    } else if (r.type === "item_summary") {
      bodyHtml =
        printSectionHtml(
          "Earnings & Deductions Breakdown",
          toPrintTable(renderItemSummaryTable(r.item_summary)),
        ) + renderReconciliationNote(r.summary);
    } else if (r.type === "department_summary") {
      bodyHtml = printSectionHtml(
        "Department Summary",
        toPrintTable(renderDepartmentSummaryTable(r.department_summary)),
      );
    } else {
      // payroll_summary
      const totalsHtml = `
        <table class="rp-doc-table rp-doc-totals-table">
          <tbody>
            <tr><td>Total Employees</td><td>${esc(r.summary.total_employees)}</td></tr>
            <tr><td>Total Gross Pay</td><td>${formatCurrency(r.summary.total_gross)}</td></tr>
            <tr><td>Total Deductions</td><td>${formatCurrency(r.summary.total_deductions)}</td></tr>
            <tr><td>Average Net Pay</td><td>${formatCurrency(r.summary.average_net)}</td></tr>
            <tr class="rp-doc-net-row"><td>Net Payroll</td><td>${formatCurrency(r.summary.total_net)}</td></tr>
          </tbody>
        </table>
      `;
      bodyHtml =
        printSectionHtml("Payroll Totals", totalsHtml) +
        printSectionHtml(
          "Gross-to-Net Statement",
          toPrintTable(renderGrossToNetTable(r.item_summary, r.summary)),
        ) +
        printSectionHtml(
          "By Employment Type",
          toPrintTable(
            renderEmploymentSummaryTable(r.employment_summary || []),
          ),
        ) +
        renderReconciliationNote(r.summary);
    }

    const signature = (label) => `
      <div class="rp-doc-signature">
        <span class="rp-doc-signature-label">${label}</span>
        <div class="rp-doc-signature-space"></div>
        <span class="rp-doc-signature-caption">Signature over printed name</span>
        <span class="rp-doc-signature-caption">Position / Date</span>
      </div>`;

    return `
      <div class="rp-doc-header">
        <img src="assets/bcp-logo.png" alt="Company logo">
        <div class="rp-doc-header-text">
          <h3>Bestlink College of the Philippines</h3>
          <span>Bulacan Campus</span>
          <span>Payroll and Compensation Management System</span>
        </div>
      </div>

      <div class="rp-doc-title-row">
        <div>
          <h4>${esc(title)}</h4>
          <div class="rp-doc-period">${periodLine}</div>
          ${runLine ? `<div class="rp-doc-period">${runLine}</div>` : ""}
        </div>
        <div class="rp-doc-generated">Printed: ${now}</div>
      </div>

      ${bodyHtml}

      <div class="rp-doc-signatures">
        ${signature("Prepared by:")}
        ${signature("Checked by:")}
        ${signature("Approved by:")}
      </div>

      <div class="rp-doc-footer">
        <span>${esc(footerRef)}</span>
        <span>Confidential &mdash; for payroll use only</span>
      </div>
    `;
  }

  // The payroll register is wide, so it prints on landscape paper; every
  // other report prints portrait. Applied just before printing and removed
  // afterwards so it never affects other pages.
  function applyPageOrientation(type) {
    removePageOrientation();
    const style = document.createElement("style");
    style.id = "rpPageOrientation";
    style.textContent =
      type === "payroll_register"
        ? "@page { size: A4 landscape; margin: 0.4in; }"
        : "@page { size: A4 portrait; margin: 0.5in; }";
    document.head.appendChild(style);
  }

  function removePageOrientation() {
    const old = document.getElementById("rpPageOrientation");
    if (old) old.remove();
  }

  // Wait for the logo to load so it is never missing from the printout.
  function waitForImages(root, timeoutMs) {
    const imgs = Array.from(root.querySelectorAll("img"));
    const loaded = Promise.all(
      imgs.map(function (img) {
        if (img.complete) return Promise.resolve();
        return new Promise(function (resolve) {
          img.addEventListener("load", resolve, { once: true });
          img.addEventListener("error", resolve, { once: true });
        });
      }),
    );
    const timeout = new Promise(function (resolve) {
      window.setTimeout(resolve, timeoutMs);
    });
    return Promise.race([loaded, timeout]);
  }

  btnPrint.addEventListener("click", async function () {
    if (!currentReport) return;
    printSheet.innerHTML = buildPrintDocument(currentReport);
    applyPageOrientation(currentReport.type);
    await waitForImages(printSheet, 2000);
    window.addEventListener("afterprint", removePageOrientation, {
      once: true,
    });
    window.print();
  });

  // ---- Init --------------------------------------------------------------
  loadPeriodOptions();
}

window.addEventListener("page:loaded", initReports);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initReports);
} else {
  initReports();
}
