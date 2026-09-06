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

  // ---- Table renderers per report type --------------------------------------------------------------
  function renderPayrollRegisterTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }
    return `
      <table class="pm-table rp-report-table">
        <thead>
          <tr>
            <th>Employee Code</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Type</th>
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
              <td>${esc(r.employee_code)}</td>
              <td>${esc(r.employee_name)}</td>
              <td>${esc(r.department_name || "\u2014")}</td>
              <td>${esc(r.position_name || "\u2014")}</td>
              <td>${esc(r.employment_type || "\u2014")}</td>
              <td class="rp-num">${formatCurrency(r.gross_pay)}</td>
              <td class="rp-num">${formatCurrency(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.net_pay)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
      </table>
    `;
  }

  function renderItemSummaryTable(rows) {
    if (rows.length === 0) {
      return '<p class="rp-no-data">No data found for the selected criteria.</p>';
    }
    return `
      <table class="pm-table rp-report-table">
        <thead>
          <tr>
            <th>Item Type</th>
            <th>Description</th>
            <th class="rp-num">Count</th>
            <th class="rp-num">Total Amount</th>
          </tr>
        </thead>
        <tbody>
          ${rows
            .map(
              (r) => `
            <tr>
              <td>${esc(r.item_type)}</td>
              <td>${esc(r.description)}</td>
              <td class="rp-num">${esc(r.item_count)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.total_amount)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
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
              <td>${formatDate(r.start_date)} \u2013 ${formatDate(r.end_date)}</td>
              <td>${formatDate(r.pay_date)}</td>
              <td class="rp-num">${formatCurrency(r.gross_pay)}</td>
              <td class="rp-num">${formatCurrency(r.total_deductions)}</td>
              <td class="rp-num rp-num-strong">${formatCurrency(r.net_pay)}</td>
              <td>${formatDateTime(r.generated_at)}</td>
            </tr>`,
            )
            .join("")}
        </tbody>
      </table>
    `;
  }

  // ---- Main render --------------------------------------------------------------
  function renderReport() {
    const r = currentReport;
    reportTitleEl.textContent = REPORT_TYPE_LABELS[r.type] || "Payroll Report";

    if (r.type === "employee_history") {
      reportPeriodEl.textContent = `Employee: ${r.employee.employee_code} \u2014 ${r.employee.employee_name}`;
      reportGeneratedEl.textContent = `Generated: ${formatDateTime(new Date().toISOString())}`;
      reportBodyEl.innerHTML = renderEmployeeHistoryTable(r.history);
      previewCard.style.display = "";
      emptyState.style.display = "none";
      btnPrint.disabled = false;
      return;
    }

    reportPeriodEl.textContent = `Payroll Period: ${r.period.period_name} (${formatDate(r.period.start_date)} \u2013 ${formatDate(r.period.end_date)})`;
    reportGeneratedEl.textContent = `Generated: ${formatDateTime(new Date().toISOString())}`;

    if (!r.has_finalized_payroll) {
      reportBodyEl.innerHTML =
        '<p class="rp-no-data">No finalized payroll is available for this payroll period.</p>';
      previewCard.style.display = "";
      emptyState.style.display = "none";
      btnPrint.disabled = true;
      return;
    }

    let tableHtml = "";
    if (r.type === "payroll_register") {
      tableHtml = renderPayrollRegisterTable(r.payroll);
    } else if (r.type === "item_summary") {
      tableHtml = renderItemSummaryTable(r.item_summary);
    } else if (r.type === "department_summary") {
      tableHtml = renderDepartmentSummaryTable(r.department_summary);
    }
    // payroll_summary has no row-level table — the summary grid below is the report.

    reportBodyEl.innerHTML = `
      ${renderSummaryBlock(r.summary)}
      ${tableHtml ? `<div class="rp-table-wrapper">${tableHtml}</div>` : ""}
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

  function buildPrintDocument(r) {
    const now = formatDateTime(new Date().toISOString());
    const periodLine =
      r.type === "employee_history"
        ? `Employee: ${esc(r.employee.employee_code)} \u2014 ${esc(r.employee.employee_name)}`
        : `Payroll Period: ${esc(r.period.period_name)} (${formatDate(r.period.start_date)} \u2013 ${formatDate(r.period.end_date)})`;

    let bodyHtml = "";

    if (r.type === "employee_history") {
      bodyHtml = printSectionHtml(
        "Payroll History",
        renderEmployeeHistoryTable(r.history).replace(
          /pm-table/,
          "rp-doc-table",
        ),
      );
    } else if (!r.has_finalized_payroll) {
      bodyHtml =
        '<p class="rp-no-data">No finalized payroll is available for this payroll period.</p>';
    } else {
      const summaryHtml = `
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

      let tableSection = "";
      if (r.type === "payroll_register") {
        tableSection = printSectionHtml(
          "Payroll Register",
          renderPayrollRegisterTable(r.payroll).replace(
            /pm-table/,
            "rp-doc-table",
          ),
        );
      } else if (r.type === "item_summary") {
        tableSection = printSectionHtml(
          "Earnings & Deductions Breakdown",
          renderItemSummaryTable(r.item_summary).replace(
            /pm-table/,
            "rp-doc-table",
          ),
        );
      } else if (r.type === "department_summary") {
        tableSection = printSectionHtml(
          "Department Summary",
          renderDepartmentSummaryTable(r.department_summary).replace(
            /pm-table/,
            "rp-doc-table",
          ),
        );
      }

      bodyHtml = printSectionHtml("Payroll Totals", summaryHtml) + tableSection;
    }

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
          <h4>${esc(REPORT_TYPE_LABELS[r.type] || "Payroll Report")}</h4>
          <div class="rp-doc-period">${periodLine}</div>
        </div>
        <div class="rp-doc-generated">Generated: ${now}</div>
      </div>

      ${bodyHtml}

      <div class="rp-doc-signatures">
        <div class="rp-doc-signature-line">
          <span>Prepared by:</span>
          <div class="rp-doc-signature-space"></div>
        </div>
        <div class="rp-doc-signature-line">
          <span>Approved by:</span>
          <div class="rp-doc-signature-space"></div>
        </div>
        <div class="rp-doc-signature-line">
          <span>Date:</span>
          <div class="rp-doc-signature-space"></div>
        </div>
      </div>
    `;
  }

  btnPrint.addEventListener("click", function () {
    if (!currentReport) return;
    printSheet.innerHTML = buildPrintDocument(currentReport);
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
