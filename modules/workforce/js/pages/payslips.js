/**
 * Payslips
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/payslipController.php (AJAX/JSON)
 * for payslip data, and reuses the existing
 * modules/payroll/controllers/periodController.php ("list" action) for
 * the Payroll Period filter, since that endpoint already exposes the
 * exact same pr_periods data.
 *
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML (see js/utils/main.js), it re-binds its
 * listeners every time the "page:loaded" event fires, and simply exits
 * early if the Payslips markup isn't present on the current page.
 */

function initPayslips() {
  const root = document.getElementById("payslipsPage");
  if (!root) return; // Not on the Payslips page — nothing to do.
  if (root.dataset.psInitialized === "true") return;
  root.dataset.psInitialized = "true";

  const PAYSLIP_ENDPOINT = "controllers/payslipController.php";
  const PERIOD_ENDPOINT = "controllers/periodController.php";

  let allPayslips = [];
  let currentPayslip = null; // full detail of whichever payslip is open in the modal

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("psAlert");
  const tableBody = document.getElementById("psTableBody");
  const emptyState = document.getElementById("psEmptyState");
  const tableCard = document.querySelector(".ps-table-card");

  const totalPayslipsEl = document.getElementById("psTotalPayslips");
  const totalGrossEl = document.getElementById("psTotalGross");
  const totalDeductionsEl = document.getElementById("psTotalDeductions");
  const totalNetEl = document.getElementById("psTotalNet");

  const periodFilter = document.getElementById("psPeriodFilter");
  const employeeFilter = document.getElementById("psEmployeeFilter");
  const btnApply = document.getElementById("psBtnApply");
  const btnReset = document.getElementById("psBtnReset");

  const viewModalOverlay = document.getElementById("psViewModalOverlay");
  const viewModalBody = document.getElementById("psViewModalBody");
  const btnPrintFromModal = document.getElementById("psBtnPrintFromModal");

  const printSheet = document.getElementById("psPrintSheet");

  // ---- Helpers --------------------------------------------------------------
  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  function showAlert(message, type) {
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className =
      "ps-alert " +
      (type === "success" ? "ps-alert-success" : "ps-alert-error");
    alertBox.style.display = "block";
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(function () {
      alertBox.style.display = "none";
    }, 5000);
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function formatDate(dateStr) {
    if (!dateStr) return "—";
    const d = new Date(dateStr + (dateStr.length <= 10 ? "T00:00:00" : ""));
    if (isNaN(d.getTime())) return esc(dateStr);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }

  function formatDateTime(dateStr) {
    if (!dateStr) return "—";
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

  function formatCurrency(value) {
    const n = Number(value) || 0;
    return (
      "₱" +
      n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function statusBadgeClass(status) {
    const key = String(status || "").toLowerCase();
    if (key === "finalized" || key === "closed") return "ps-badge-finalized";
    if (key === "draft" || key === "open" || key === "processing")
      return "ps-badge-draft";
    return "ps-badge-default";
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

  function createCombobox({
    inputEl,
    hiddenEl,
    optionsEl,
    clearBtn,
    getId,
    getLabel,
    getSubLabel,
    matchFn,
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

    function filterAndShow(query) {
      const q = query.trim().toLowerCase();
      const test = matchFn || defaultMatch;
      const list =
        q === ""
          ? data
          : data.filter(function (item) {
              return test(item, q);
            });
      renderOptions(list);
      openDropdown();
    }

    function selectItem(item) {
      hiddenEl.value = getId(item);
      inputEl.value = getLabel(item);
      updateClearButton();
      closeDropdown();
    }

    function clearSelection() {
      hiddenEl.value = "";
      inputEl.value = "";
      updateClearButton();
      closeDropdown();
    }

    inputEl.addEventListener("focus", function () {
      filterAndShow("");
      inputEl.select();
    });

    inputEl.addEventListener("input", function () {
      if (inputEl.value === "") hiddenEl.value = "";
      updateClearButton();
      filterAndShow(inputEl.value);
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

    // mousedown (not click) fires before the input's blur, so selecting an
    // option registers before the input loses focus and snaps back.
    optionsEl.addEventListener("mousedown", function (e) {
      const optEl = e.target.closest(".ps-combobox-option");
      if (!optEl) return;
      e.preventDefault();
      const item = filtered[Number(optEl.getAttribute("data-index"))];
      if (item) selectItem(item);
    });

    inputEl.addEventListener("blur", function () {
      window.setTimeout(function () {
        if (hiddenEl.value) {
          const selected = data.find(function (item) {
            return String(getId(item)) === String(hiddenEl.value);
          });
          inputEl.value = selected ? getLabel(selected) : "";
        } else {
          inputEl.value = "";
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

  const periodCombobox = createCombobox({
    inputEl: document.getElementById("psPeriodSearchInput"),
    hiddenEl: periodFilter,
    optionsEl: document.getElementById("psPeriodOptions"),
    clearBtn: document.getElementById("psPeriodClearBtn"),
    getId: function (p) {
      return p.period_id;
    },
    getLabel: function (p) {
      return p.period_name;
    },
    getSubLabel: function (p) {
      return `${formatDate(p.start_date)} – ${formatDate(p.end_date)}`;
    },
  });

  const employeeCombobox = createCombobox({
    inputEl: document.getElementById("psEmployeeSearchInput"),
    hiddenEl: employeeFilter,
    optionsEl: document.getElementById("psEmployeeOptions"),
    clearBtn: document.getElementById("psEmployeeClearBtn"),
    getId: function (e) {
      return e.employee_id;
    },
    getLabel: function (e) {
      return e.employee_name;
    },
    getSubLabel: function (e) {
      return e.employee_code;
    },
    // Match on employee name/code normally, and ALSO match when the typed
    // digits are the tail end of the employee code's digits — so typing
    // "34" finds "EMP-000034" (and "EMP-001234", etc.) without needing the
    // "EMP-" prefix or the leading zeros.
    matchFn: function (emp, q) {
      const name = (emp.employee_name || "").toLowerCase();
      const code = (emp.employee_code || "").toLowerCase();
      if (name.includes(q) || code.includes(q)) return true;

      const codeDigits = code.replace(/\D/g, "");
      const queryDigits = q.replace(/\D/g, "");
      return queryDigits !== "" && codeDigits.endsWith(queryDigits);
    },
  });

  // ---- Filter option loading --------------------------------------------------------------
  async function loadPeriodOptions() {
    try {
      const data = await apiRequest(PERIOD_ENDPOINT, "list");
      if (!data.success) return;
      periodCombobox.setData(data.data || []);
    } catch (err) {
      // Filter is a non-critical enhancement — fail silently.
      console.error("Failed to load payroll periods", err);
    }
  }

  async function loadEmployeeOptions() {
    try {
      const data = await apiRequest(PAYSLIP_ENDPOINT, "employees");
      if (!data.success) return;
      employeeCombobox.setData(data.data || []);
    } catch (err) {
      console.error("Failed to load employees", err);
    }
  }

  // ---- Rendering: summary + table --------------------------------------------------------------
  function renderSummary(summary) {
    if (!summary) return;
    totalPayslipsEl.textContent = summary.payslip_count ?? 0;
    totalGrossEl.textContent = formatCurrency(summary.total_gross_pay);
    totalDeductionsEl.textContent = formatCurrency(summary.total_deductions);
    totalNetEl.textContent = formatCurrency(summary.total_net_pay);
  }

  function renderTable() {
    if (allPayslips.length === 0) {
      tableCard.querySelector(".ps-table-wrapper").style.display = "none";
      emptyState.style.display = "block";
      return;
    }

    tableCard.querySelector(".ps-table-wrapper").style.display = "";
    emptyState.style.display = "none";

    tableBody.innerHTML = allPayslips
      .map(function (p) {
        const badgeClass = statusBadgeClass(p.payroll_status);
        const statusLabel = esc(
          (p.payroll_status || "unknown").toString().toUpperCase(),
        );
        const settlementTag =
          Number(p.is_exit_settlement) === 1
            ? ' <span class="ps-badge ps-badge-settlement" title="Exit settlement payslip">SETTLEMENT</span>'
            : "";

        return `<tr>
                <td>
                    <span class="ps-employee-name">${esc(p.employee_name)}</span>
                    <span class="ps-employee-sub">${esc(p.employment_type || "")}</span>
                </td>
                <td>${esc(p.employee_code)}</td>
                <td>${esc(p.period_name)}</td>
                <td class="ps-amount">${formatCurrency(p.gross_pay)}</td>
                <td class="ps-amount">${formatCurrency(p.total_deductions)}</td>
                <td class="ps-amount ps-amount-net">${formatCurrency(p.net_pay)}</td>
                <td><span class="ps-badge ${badgeClass}">${statusLabel}</span>${settlementTag}</td>
                <td>
                    <div class="ps-row-actions">
                        <button type="button" class="ps-icon-btn ps-icon-btn-primary" title="View payslip" data-action="view" data-id="${p.payslip_id}"><i class="fa-solid fa-eye"></i></button>
                        <button type="button" class="ps-icon-btn" title="Print payslip" data-action="print" data-id="${p.payslip_id}"><i class="fa-solid fa-print"></i></button>
                    </div>
                </td>
            </tr>`;
      })
      .join("");
  }

  async function loadPayslips() {
    tableBody.innerHTML =
      '<tr><td colspan="10" class="ps-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading payslips...</td></tr>';
    tableCard.querySelector(".ps-table-wrapper").style.display = "";
    emptyState.style.display = "none";
    btnApply.disabled = true;

    try {
      const params = {};
      if (periodFilter.value) params.period_id = periodFilter.value;
      if (employeeFilter.value) params.employee_id = employeeFilter.value;

      const data = await apiRequest(PAYSLIP_ENDPOINT, "list", params);

      if (!data.success) {
        tableBody.innerHTML = `<tr><td colspan="10" class="ps-error-row">${esc(data.message || "Unable to load payslips. Please try again.")}</td></tr>`;
        renderSummary({
          payslip_count: 0,
          total_gross_pay: 0,
          total_deductions: 0,
          total_net_pay: 0,
        });
        return;
      }

      allPayslips = data.data || [];
      renderSummary(data.summary);
      renderTable();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="10" class="ps-error-row">${esc(err.message || "Unable to load payslips. Please try again.")}</td></tr>`;
    } finally {
      btnApply.disabled = false;
    }
  }

  // ---- Payslip document renderer (shared by modal + print) --------------------------------------------------------------
  function renderItemsRows(items, emptyLabel) {
    if (!items || items.length === 0) {
      return `<tr class="ps-doc-empty-row"><td colspan="2">${esc(emptyLabel)}</td></tr>`;
    }
    return items
      .map(function (it) {
        return `<tr><td>${esc(it.description)}</td><td>${formatCurrency(it.amount)}</td></tr>`;
      })
      .join("");
  }

  function renderPayslipDocument(p) {
    const settlementNote =
      Number(p.is_exit_settlement) === 1
        ? '<div class="ps-doc-settlement-note"><i class="fa-solid fa-circle-info"></i> This payslip is part of an employee exit settlement.</div>'
        : "";

    return `
        <div class="ps-doc-header">
            <img src="assets/bcp-logo.png" alt="Company logo">
            <div class="ps-doc-header-text">
                <h3>Bestlink College of the Philippines</h3>
                <span>Human Resource Management System</span>
            </div>
        </div>

        <div class="ps-doc-title-row">
            <div>
                <h4>EMPLOYEE PAYSLIP</h4>
                <div class="ps-doc-period">${formatDate(p.start_date)} &ndash; ${formatDate(p.end_date)}</div>
            </div>
        </div>

        <div class="ps-doc-section">
            <div class="ps-doc-section-title">Employee Information</div>
            <div class="ps-doc-grid">
                <div class="ps-doc-field"><span>Employee Name</span><strong>${esc(p.employee_name)}</strong></div>
                <div class="ps-doc-field"><span>Employee Code</span><strong>${esc(p.employee_code)}</strong></div>
                <div class="ps-doc-field"><span>Employment Type</span><strong>${esc(p.employment_type || "—")}</strong></div>
                <div class="ps-doc-field"><span>Employment Status</span><strong>${esc(p.employment_status || "—")}</strong></div>
                ${p.email ? `<div class="ps-doc-field"><span>Email</span><strong>${esc(p.email)}</strong></div>` : ""}
            </div>
        </div>

        <div class="ps-doc-section">
            <div class="ps-doc-section-title">Earnings</div>
            <table class="ps-doc-table">
                <thead><tr><th>Description</th><th>Amount</th></tr></thead>
                <tbody>${renderItemsRows(p.earnings, "No earnings recorded.")}</tbody>
                <tfoot><tr><td>Gross Pay</td><td>${formatCurrency(p.gross_pay)}</td></tr></tfoot>
            </table>
        </div>

        <div class="ps-doc-section">
            <div class="ps-doc-section-title">Deductions</div>
            <table class="ps-doc-table">
                <thead><tr><th>Description</th><th>Amount</th></tr></thead>
                <tbody>${renderItemsRows(p.deductions, "No deductions recorded.")}</tbody>
                <tfoot><tr><td>Total Deductions</td><td>${formatCurrency(p.total_deductions)}</td></tr></tfoot>
            </table>
        </div>

        <div class="ps-doc-net">
            <span>Net Pay</span>
            <strong>${formatCurrency(p.net_pay)}</strong>
        </div>
        ${settlementNote}

        <div class="ps-doc-meta">
            <span>Payslip ID: <strong>${esc(p.payslip_id)}</strong></span>
            <span>Generated: <strong>${formatDateTime(p.generated_at)}</strong></span>
            <span>Payroll Status: <strong>${esc((p.payroll_status || "—").toString().toUpperCase())}</strong></span>
            <span>Payroll Period: <strong>${esc(p.period_name)}</strong></span>
        </div>
    `;
  }

  // ---- View modal --------------------------------------------------------------
  function openModal(overlay) {
    overlay.style.display = "flex";
  }
  function closeModal(overlay) {
    overlay.style.display = "none";
  }

  document.querySelectorAll("[data-ps-close]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      closeModal(viewModalOverlay);
    });
  });

  viewModalOverlay.addEventListener("click", function (e) {
    if (e.target === viewModalOverlay) closeModal(viewModalOverlay);
  });

  async function viewPayslip(id) {
    currentPayslip = null;
    btnPrintFromModal.style.display = "none";
    viewModalBody.innerHTML =
      '<div class="ps-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading payslip...</div>';
    openModal(viewModalOverlay);

    try {
      const data = await apiRequest(PAYSLIP_ENDPOINT, "get", { id });
      if (!data.success) {
        viewModalBody.innerHTML = `<div class="ps-modal-error">${esc(data.message || "Unable to load this payslip.")}</div>`;
        return;
      }
      currentPayslip = data.data;
      viewModalBody.innerHTML = renderPayslipDocument(currentPayslip);
      btnPrintFromModal.style.display = "";
    } catch (err) {
      viewModalBody.innerHTML = `<div class="ps-modal-error">${esc(err.message || "Unable to load this payslip.")}</div>`;
    }
  }

  // ---- Print --------------------------------------------------------------
  function printPayslipDocument(payslip) {
    printSheet.innerHTML = renderPayslipDocument(payslip);
    window.print();
  }

  async function printPayslip(id) {
    // Reuse the already-loaded detail if the modal happens to be open on the same payslip.
    if (currentPayslip && String(currentPayslip.payslip_id) === String(id)) {
      printPayslipDocument(currentPayslip);
      return;
    }

    try {
      const data = await apiRequest(PAYSLIP_ENDPOINT, "get", { id });
      if (!data.success) {
        showAlert(data.message || "Unable to load this payslip.", "error");
        return;
      }
      printPayslipDocument(data.data);
    } catch (err) {
      showAlert(err.message || "Unable to load this payslip.", "error");
    }
  }

  btnPrintFromModal.addEventListener("click", function () {
    if (currentPayslip) printPayslipDocument(currentPayslip);
  });

  // ---- Row action delegation --------------------------------------------------------------
  tableBody.addEventListener("click", function (e) {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const action = btn.getAttribute("data-action");
    const id = btn.getAttribute("data-id");

    if (action === "view") viewPayslip(id);
    else if (action === "print") printPayslip(id);
  });

  // ---- Toolbar events --------------------------------------------------------------
  btnApply.addEventListener("click", loadPayslips);

  btnReset.addEventListener("click", function () {
    periodCombobox.clearSelection();
    employeeCombobox.clearSelection();
    loadPayslips();
  });

  // ---- Init --------------------------------------------------------------
  loadPeriodOptions();
  loadEmployeeOptions();
  loadPayslips();
}

window.addEventListener("page:loaded", initPayslips);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPayslips);
} else {
  initPayslips();
}
