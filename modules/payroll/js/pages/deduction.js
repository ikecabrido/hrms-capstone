/**
 * Deductions (manual employee deduction adjustments)
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/allowanceDeductionController.php
 * (AJAX/JSON), following the same self-dispatching endpoint pattern as
 * controllers/periodController.php and controllers/payslipController.php.
 *
 * The Payroll Period / Employee filter fields use a combobox modeled on
 * the one in js/pages/payslips.js (same interaction: type-ahead, arrow
 * keys, clear button), but — unlike the payslips combobox, which loads
 * every period/employee once and filters client-side — this one queries
 * searchPeriods()/searchEmployees() on the server for each keystroke
 * (debounced), per this page's requirement to search the full dataset
 * rather than only the rows already in the browser, and to also surface
 * employees/periods that a "get everything up front" call wouldn't
 * necessarily include (e.g. closed periods).
 *
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML (see js/utils/main.js), it re-binds its
 * listeners every time the "page:loaded" event fires, and simply exits
 * early if the Deductions markup isn't present on the current page.
 */

function initDeductions() {
  const root = document.getElementById("deductionsPage");
  if (!root) return; // Not on the Deductions page — nothing to do.
  if (root.dataset.ddInitialized === "true") return;
  root.dataset.ddInitialized = "true";

  const ENDPOINT = "controllers/allowanceDeductionController.php";
  const MAX_FILE_SIZE = 5 * 1024 * 1024;
  const ALLOWED_EXTENSIONS = ["pdf", "jpg", "jpeg", "png"];

  let currentRecords = [];
  let filtersActive = false;
  let pendingDeleteId = null;

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("ddAlert");
  const tableBody = document.getElementById("ddTableBody");
  const emptyState = document.getElementById("ddEmptyState");
  const emptyStateText = document.getElementById("ddEmptyStateText");
  const tableCard = document.querySelector(".pm-table-card");

  const totalAdjustmentsEl = document.getElementById("ddTotalAdjustments");
  const totalDeductionsEl = document.getElementById("ddTotalDeductions");
  const totalLoansEl = document.getElementById("ddTotalLoans");
  const totalOtherEl = document.getElementById("ddTotalOther");

  const periodFilter = document.getElementById("ddPeriodFilter");
  const employeeFilter = document.getElementById("ddEmployeeFilter");
  const typeFilter = document.getElementById("ddTypeFilter");

  const btnAdd = document.getElementById("ddBtnAdd");
  const btnAddEmpty = document.getElementById("ddBtnAddEmpty");
  const btnSearch = document.getElementById("ddBtnSearch");
  const btnClear = document.getElementById("ddBtnClear");

  const formModalOverlay = document.getElementById("ddFormModalOverlay");
  const formModalTitle = document.getElementById("ddFormModalTitle");
  const deductionForm = document.getElementById("ddDeductionForm");
  const adjustmentIdInput = document.getElementById("ddAdjustmentId");
  const formEmployee = document.getElementById("ddFormEmployee");
  const formPeriod = document.getElementById("ddFormPeriod");
  const formType = document.getElementById("ddFormType");
  const formDescription = document.getElementById("ddFormDescription");
  const formAmount = document.getElementById("ddFormAmount");
  const formFile = document.getElementById("ddFormFile");
  const currentFileBox = document.getElementById("ddCurrentFile");
  const formError = document.getElementById("ddFormError");
  const formSubmitBtn = document.getElementById("ddFormSubmitBtn");

  const viewModalOverlay = document.getElementById("ddViewModalOverlay");
  const viewEmployeeCode = document.getElementById("ddViewEmployeeCode");
  const viewEmployeeName = document.getElementById("ddViewEmployeeName");
  const viewPeriodName = document.getElementById("ddViewPeriodName");
  const viewPeriodDates = document.getElementById("ddViewPeriodDates");
  const viewPayDate = document.getElementById("ddViewPayDate");
  const viewType = document.getElementById("ddViewType");
  const viewDescription = document.getElementById("ddViewDescription");
  const viewAmount = document.getElementById("ddViewAmount");
  const viewDocument = document.getElementById("ddViewDocument");
  const viewCreated = document.getElementById("ddViewCreated");
  const viewStatus = document.getElementById("ddViewStatus");

  const confirmModalOverlay = document.getElementById("ddConfirmModalOverlay");
  const confirmEmployee = document.getElementById("ddConfirmEmployee");
  const confirmPeriod = document.getElementById("ddConfirmPeriod");
  const confirmDescription = document.getElementById("ddConfirmDescription");
  const confirmAmount = document.getElementById("ddConfirmAmount");
  const btnConfirmDelete = document.getElementById("ddBtnConfirmDelete");

  // ---- Modal placement fix --------------------------------------------------
  // The Deductions markup (including these modal overlays) gets injected
  // into a nested layout wrapper (.container / .module-content) via
  // innerHTML. If any ancestor of that wrapper has position/transform/
  // filter/opacity set, it creates a new stacking context, and no
  // z-index on the modal — however high — can ever appear above the
  // fixed page header, which lives in a sibling stacking context closer
  // to <body>. Re-parenting the overlays to <body> guarantees they share
  // the root stacking context with the header, so z-index actually
  // applies as expected. Only runs once per page load, same as the rest
  // of this init (guarded by root.dataset.ddInitialized above).
  [formModalOverlay, viewModalOverlay, confirmModalOverlay].forEach(
    function (overlay) {
      if (overlay && overlay.parentElement !== document.body) {
        document.body.appendChild(overlay);
      }
    },
  );

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

  function typeLabel(subtype) {
    return subtype === "loans" ? "Loans" : "Other";
  }

  function periodRangeLabel(record) {
    const start = formatDate(record.start_date);
    const end = formatDate(record.end_date);
    if (start === "\u2014" && end === "\u2014") return "\u2014";
    return start + " \u2013 " + end;
  }

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  async function apiRequest(action, { params = null } = {}) {
    let url = `${ENDPOINT}?action=${encodeURIComponent(action)}`;
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

  // ---- Searchable combobox (Payroll Period / Employee filters) --------------------------------------------------------------
  // Modeled on createCombobox() in js/pages/payslips.js, but data comes
  // from a debounced server-side search instead of a client-filtered
  // preloaded list.
  function createSearchCombobox({
    inputEl,
    hiddenEl,
    optionsEl,
    clearBtn,
    fetchResults, // async (query) => array of items
    getId,
    getLabel,
    getSubLabel,
  }) {
    let results = [];
    let highlightedIndex = -1;
    let lastQueryToken = 0;

    function updateClearButton() {
      clearBtn.classList.toggle(
        "dd-combobox-clear-visible",
        hiddenEl.value !== "",
      );
    }

    function updateHighlight() {
      const items = optionsEl.querySelectorAll(".dd-combobox-option");
      items.forEach(function (el, idx) {
        el.classList.toggle(
          "dd-combobox-highlighted",
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
          '<div class="dd-combobox-empty">No matches found</div>';
        highlightedIndex = -1;
        return;
      }
      optionsEl.innerHTML = list
        .map(function (item, idx) {
          const sub = getSubLabel ? getSubLabel(item) : "";
          return `<div class="dd-combobox-option" role="option" data-index="${idx}">
                    <span class="dd-combobox-option-label">${esc(getLabel(item))}</span>
                    ${sub ? `<span class="dd-combobox-option-sub">${esc(sub)}</span>` : ""}
                  </div>`;
        })
        .join("");
      highlightedIndex = 0;
      updateHighlight();
    }

    function showLoading() {
      optionsEl.innerHTML =
        '<div class="dd-combobox-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
      highlightedIndex = -1;
      openDropdown();
    }

    function openDropdown() {
      optionsEl.classList.add("dd-combobox-open");
      inputEl.setAttribute("aria-expanded", "true");
    }

    function closeDropdown() {
      optionsEl.classList.remove("dd-combobox-open");
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
        if (token !== lastQueryToken) return; // stale response
        renderOptions(items);
        openDropdown();
      } catch (err) {
        if (token !== lastQueryToken) return;
        optionsEl.innerHTML =
          '<div class="dd-combobox-empty">Search failed. Try again.</div>';
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
      const optEl = e.target.closest(".dd-combobox-option");
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
      const combobox = inputEl.closest(".dd-combobox");
      if (combobox && !combobox.contains(e.target)) closeDropdown();
    });

    return { clearSelection };
  }

  const periodCombobox = createSearchCombobox({
    inputEl: document.getElementById("ddPeriodSearchInput"),
    hiddenEl: periodFilter,
    optionsEl: document.getElementById("ddPeriodOptions"),
    clearBtn: document.getElementById("ddPeriodClearBtn"),
    fetchResults: async function (q) {
      const data = await apiRequest("search_periods", { params: { q } });
      return data.success ? data.data || [] : [];
    },
    getId: function (p) {
      return p.period_id;
    },
    getLabel: function (p) {
      return p.period_name;
    },
    getSubLabel: function (p) {
      return `${formatDate(p.start_date)} \u2013 ${formatDate(p.end_date)} \u00b7 Pay: ${formatDate(p.pay_date)} \u00b7 ${(p.status || "").toUpperCase()}`;
    },
  });

  const employeeCombobox = createSearchCombobox({
    inputEl: document.getElementById("ddEmployeeSearchInput"),
    hiddenEl: employeeFilter,
    optionsEl: document.getElementById("ddEmployeeOptions"),
    clearBtn: document.getElementById("ddEmployeeClearBtn"),
    fetchResults: async function (q) {
      const data = await apiRequest("search_employees", { params: { q } });
      return data.success ? data.data || [] : [];
    },
    getId: function (e) {
      return e.employee_id;
    },
    getLabel: function (e) {
      return `${e.employee_code} \u2014 ${e.employee_name}`;
    },
    getSubLabel: function () {
      return "";
    },
  });

  // ---- Summary --------------------------------------------------------------
  function renderSummary(summary) {
    if (!summary) return;
    totalAdjustmentsEl.textContent = summary.adjustment_count ?? 0;
    totalDeductionsEl.textContent = peso(summary.total_amount ?? 0);
    totalLoansEl.textContent = peso(summary.total_loans ?? 0);
    totalOtherEl.textContent = peso(summary.total_other ?? 0);
  }

  // ---- Table rendering --------------------------------------------------------------
  function renderTable() {
    if (currentRecords.length === 0) {
      tableCard.querySelector(".pm-table-wrapper").style.display = "none";
      emptyStateText.textContent = filtersActive
        ? "No deductions match the selected filters."
        : "No deduction adjustments found.";
      emptyState.style.display = "block";
      return;
    }

    tableCard.querySelector(".pm-table-wrapper").style.display = "";
    emptyState.style.display = "none";

    tableBody.innerHTML = currentRecords
      .map(function (r) {
        const isOpen = (r.period_status || "").toLowerCase() === "open";
        const badgeClass = isOpen ? "pm-badge-open" : "pm-badge-closed";
        const statusLabel = isOpen ? "OPEN" : "CLOSED";
        const typeClass =
          r.deduction_subtype === "loans" ? "dd-type-loans" : "";

        const docCell = r.file_path
          ? `<a class="dd-doc-link" href="${esc(r.file_path)}" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> View</a>`
          : `<span class="dd-doc-none">No attachment</span>`;

        let actions = `<button type="button" class="pm-icon-btn" title="View" data-action="view" data-id="${r.adjustment_id}"><i class="fa-solid fa-eye"></i></button>`;
        actions += `<button type="button" class="pm-icon-btn pm-icon-btn-primary" title="Edit" data-action="edit" data-id="${r.adjustment_id}" ${isOpen ? "" : "disabled"}><i class="fa-solid fa-pen"></i></button>`;
        actions += `<button type="button" class="pm-icon-btn pm-icon-btn-danger" title="Delete" data-action="delete" data-id="${r.adjustment_id}" ${isOpen ? "" : "disabled"}><i class="fa-solid fa-trash"></i></button>`;

        return `<tr>
                <td>
                    <div class="dd-employee-code">${esc(r.employee_code)}</div>
                    <div class="dd-employee-name">${esc(r.employee_name)}</div>
                </td>
                <td>
                    <div class="pm-period-name">${esc(r.period_name)}</div>
                    <div class="dd-period-dates">Pay Date: ${formatDate(r.pay_date)}</div>
                </td>
                <td><span class="dd-type-badge ${typeClass}">${typeLabel(r.deduction_subtype)}</span></td>
                <td>${esc(r.description)}</td>
                <td class="dd-amount-cell">${peso(r.amount)}</td>
                <td>${docCell}</td>
                <td>${formatDate(r.created_at)}</td>
                <td><span class="pm-badge ${badgeClass}">${statusLabel}</span></td>
                <td><div class="pm-row-actions">${actions}</div></td>
            </tr>`;
      })
      .join("");
  }

  async function loadDeductions() {
    tableBody.innerHTML =
      '<tr><td colspan="9" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading deductions...</td></tr>';
    tableCard.querySelector(".pm-table-wrapper").style.display = "";
    emptyState.style.display = "none";
    btnSearch.disabled = true;

    const params = {};
    if (periodFilter.value) params.period_id = periodFilter.value;
    if (employeeFilter.value) params.employee_id = employeeFilter.value;
    if (typeFilter.value) params.deduction_subtype = typeFilter.value;

    filtersActive = !!(
      periodFilter.value ||
      employeeFilter.value ||
      typeFilter.value
    );

    try {
      const data = await apiRequest("list", { params });
      if (!data.success) {
        tableBody.innerHTML = `<tr><td colspan="9" class="pm-error-row">${esc(data.message || "Failed to load deductions.")}</td></tr>`;
        return;
      }
      currentRecords = data.data || [];
      renderSummary(data.summary);
      renderTable();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="9" class="pm-error-row">${esc(err.message || "Failed to load deductions.")}</td></tr>`;
    } finally {
      btnSearch.disabled = false;
    }
  }

  // ---- Filter bar actions --------------------------------------------------------------
  btnSearch.addEventListener("click", loadDeductions);

  btnClear.addEventListener("click", function () {
    periodCombobox.clearSelection();
    employeeCombobox.clearSelection();
    typeFilter.value = "";
    loadDeductions();
  });

  // ---- Modal helpers --------------------------------------------------------------
  function openModal(overlay) {
    overlay.style.display = "flex";
  }
  function closeModal(overlay) {
    overlay.style.display = "none";
  }

  function closeAllModals() {
    [formModalOverlay, viewModalOverlay, confirmModalOverlay].forEach(
      closeModal,
    );
    formError.style.display = "none";
    pendingDeleteId = null;
  }

  document.querySelectorAll("[data-dd-close]").forEach(function (btn) {
    btn.addEventListener("click", closeAllModals);
  });

  [formModalOverlay, viewModalOverlay, confirmModalOverlay].forEach(
    function (overlay) {
      if (!overlay) return;
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) closeAllModals();
      });
    },
  );

  // ---- Add / Edit form: populate dropdowns --------------------------------------------------------------
  let employeesLoaded = false;
  let periodsLoaded = false;

  async function ensureEmployeeOptions() {
    if (employeesLoaded) return;
    try {
      const data = await apiRequest("employees");
      const employees = data.success ? data.data || [] : [];
      formEmployee.innerHTML =
        '<option value="">Select employee...</option>' +
        employees
          .map(function (emp) {
            return `<option value="${emp.employee_id}">${esc(emp.employee_code)} - ${esc(emp.employee_name)}</option>`;
          })
          .join("");
      employeesLoaded = true;
    } catch (err) {
      /* leave default option */
    }
  }

  async function ensureOpenPeriodOptions() {
    if (periodsLoaded) return;
    try {
      const data = await apiRequest("open_periods");
      const periods = data.success ? data.data || [] : [];
      formPeriod.innerHTML =
        '<option value="">Select payroll period...</option>' +
        periods
          .map(function (p) {
            return `<option value="${p.period_id}">${esc(p.period_name)} (${formatDate(p.start_date)} \u2013 ${formatDate(p.end_date)}, Pay: ${formatDate(p.pay_date)})</option>`;
          })
          .join("");
      periodsLoaded = true;
    } catch (err) {
      /* leave default option */
    }
  }

  function resetForm() {
    deductionForm.reset();
    adjustmentIdInput.value = "";
    formError.style.display = "none";
    currentFileBox.style.display = "none";
    currentFileBox.innerHTML = "";
  }

  async function openAddModal() {
    resetForm();
    formModalTitle.textContent = "Add Deduction";
    formSubmitBtn.textContent = "Save Deduction";
    await Promise.all([ensureEmployeeOptions(), ensureOpenPeriodOptions()]);
    openModal(formModalOverlay);
  }

  async function openEditModal(id) {
    try {
      const data = await apiRequest("get", { params: { id } });
      if (!data.success) {
        showAlert(data.message || "Unable to load this deduction.", "error");
        return;
      }
      const r = data.data;

      if ((r.period_status || "").toLowerCase() === "closed") {
        showAlert(
          "This payroll period is already closed. Deductions can no longer be modified.",
          "error",
        );
        return;
      }

      resetForm();
      await Promise.all([ensureEmployeeOptions(), ensureOpenPeriodOptions()]);

      formModalTitle.textContent = "Edit Deduction";
      formSubmitBtn.textContent = "Update Deduction";
      adjustmentIdInput.value = r.adjustment_id;

      if (!formPeriod.querySelector(`option[value="${r.period_id}"]`)) {
        const opt = document.createElement("option");
        opt.value = r.period_id;
        opt.textContent = `${r.period_name} (current)`;
        formPeriod.appendChild(opt);
      }

      formEmployee.value = r.employee_id;
      formPeriod.value = r.period_id;
      formType.value = r.deduction_subtype;
      formDescription.value = r.description;
      formAmount.value = r.amount;

      if (r.file_path) {
        currentFileBox.style.display = "block";
        currentFileBox.innerHTML = `Current document: <a href="${esc(r.file_path)}" target="_blank" rel="noopener">View Attachment</a>`;
      }

      openModal(formModalOverlay);
    } catch (err) {
      showAlert(err.message || "Unable to load this deduction.", "error");
    }
  }

  function validateFile(file) {
    if (!file) return null;
    const ext = file.name.split(".").pop().toLowerCase();
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
      return "Invalid supporting document type. Accepted formats: PDF, JPG, JPEG, PNG.";
    }
    if (file.size > MAX_FILE_SIZE) {
      return "Supporting document must not exceed 5 MB.";
    }
    return null;
  }

  deductionForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    formError.style.display = "none";

    const employeeId = formEmployee.value;
    const periodId = formPeriod.value;
    const type = formType.value;
    const description = formDescription.value.trim();
    const amount = parseFloat(formAmount.value);
    const file = formFile.files[0] || null;

    if (!employeeId) {
      formError.textContent = "Please select an employee.";
      formError.style.display = "block";
      return;
    }
    if (!periodId) {
      formError.textContent = "Please select a payroll period.";
      formError.style.display = "block";
      return;
    }
    if (!type) {
      formError.textContent = "Please select a deduction type.";
      formError.style.display = "block";
      return;
    }
    if (!description) {
      formError.textContent = "Please provide a deduction description.";
      formError.style.display = "block";
      return;
    }
    if (!amount || amount <= 0) {
      formError.textContent = "Deduction amount must be greater than zero.";
      formError.style.display = "block";
      return;
    }

    const fileError = validateFile(file);
    if (fileError) {
      formError.textContent = fileError;
      formError.style.display = "block";
      return;
    }

    const isEdit = !!adjustmentIdInput.value;
    const fd = new FormData();
    if (isEdit) fd.append("adjustment_id", adjustmentIdInput.value);
    fd.append("employee_id", employeeId);
    fd.append("period_id", periodId);
    fd.append("deduction_subtype", type);
    fd.append("description", description);
    fd.append("amount", amount);
    if (file) fd.append("supporting_document", file);

    formSubmitBtn.disabled = true;
    try {
      const res = await fetch(
        `${ENDPOINT}?action=${isEdit ? "update" : "create"}`,
        {
          method: "POST",
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          body: fd,
        },
      );
      const data = await res.json();

      if (data.success) {
        closeAllModals();
        showAlert(
          data.message ||
            (isEdit
              ? "Deduction updated successfully."
              : "Deduction added successfully."),
          "success",
        );
        loadDeductions();
      } else {
        formError.textContent =
          data.message ||
          (isEdit ? "Failed to update deduction." : "Failed to add deduction.");
        formError.style.display = "block";
      }
    } catch (err) {
      formError.textContent = "Something went wrong. Please try again.";
      formError.style.display = "block";
    } finally {
      formSubmitBtn.disabled = false;
    }
  });

  // ---- View modal --------------------------------------------------------------
  async function openViewModal(id) {
    try {
      const data = await apiRequest("get", { params: { id } });
      if (!data.success) {
        showAlert(data.message || "Unable to load this deduction.", "error");
        return;
      }
      const r = data.data;

      viewEmployeeCode.textContent = r.employee_code || "\u2014";
      viewEmployeeName.textContent = r.employee_name || "\u2014";
      viewPeriodName.textContent = r.period_name || "\u2014";
      viewPeriodDates.textContent = periodRangeLabel(r);
      viewPayDate.textContent = "Pay Date: " + formatDate(r.pay_date);
      viewType.textContent = typeLabel(r.deduction_subtype);
      viewDescription.textContent = r.description || "\u2014";
      viewAmount.textContent = peso(r.amount);
      viewDocument.innerHTML = r.file_path
        ? `<a class="dd-doc-link" href="${esc(r.file_path)}" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> View Attachment</a>`
        : "No attachment";
      viewCreated.textContent = formatDateTime(r.created_at);

      const isOpen = (r.period_status || "").toLowerCase() === "open";
      viewStatus.innerHTML = `<span class="pm-badge ${isOpen ? "pm-badge-open" : "pm-badge-closed"}">${isOpen ? "OPEN" : "CLOSED"}</span>`;

      openModal(viewModalOverlay);
    } catch (err) {
      showAlert(err.message || "Unable to load this deduction.", "error");
    }
  }

  // ---- Delete confirmation --------------------------------------------------------------
  function askDelete(record) {
    pendingDeleteId = record.adjustment_id;
    confirmEmployee.textContent = `${record.employee_code} \u2014 ${record.employee_name}`;
    confirmPeriod.textContent = record.period_name;
    confirmDescription.textContent = record.description;
    confirmAmount.textContent = peso(record.amount);
    openModal(confirmModalOverlay);
  }

  btnConfirmDelete.addEventListener("click", async function () {
    if (!pendingDeleteId) return;

    const fd = new FormData();
    fd.append("adjustment_id", pendingDeleteId);

    btnConfirmDelete.disabled = true;
    try {
      const res = await fetch(`${ENDPOINT}?action=delete`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeAllModals();
      if (data.success) {
        showAlert(data.message || "Deduction deleted successfully.", "success");
      } else {
        showAlert(data.message || "Failed to delete deduction.", "error");
      }
      loadDeductions();
    } catch (err) {
      closeAllModals();
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmDelete.disabled = false;
    }
  });

  // ---- Table action delegation --------------------------------------------------------------
  tableBody.addEventListener("click", function (e) {
    const btn = e.target.closest("button[data-action]");
    if (!btn || btn.disabled) return;
    const action = btn.getAttribute("data-action");
    const id = btn.getAttribute("data-id");
    const record = currentRecords.find(function (r) {
      return String(r.adjustment_id) === String(id);
    });
    if (!record) return;

    if (action === "view") openViewModal(id);
    else if (action === "edit") openEditModal(id);
    else if (action === "delete") askDelete(record);
  });

  // ---- Toolbar events --------------------------------------------------------------
  btnAdd.addEventListener("click", openAddModal);
  if (btnAddEmpty) btnAddEmpty.addEventListener("click", openAddModal);

  // ---- Init --------------------------------------------------------------
  loadDeductions();
}

window.addEventListener("page:loaded", initDeductions);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initDeductions);
} else {
  initDeductions();
}
