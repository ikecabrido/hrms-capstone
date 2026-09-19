/**
 * Period Manager
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/periodController.php (AJAX/JSON).
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML (see js/utils/main.js), it re-binds its
 * listeners every time the "page:loaded" event fires, and simply exits
 * early if the Period Manager markup isn't present on the current page.
 */

function initPeriodManager() {
  const root = document.getElementById("periodManagerPage");
  if (!root) return; // Not on the Period Manager page — nothing to do.
  if (root.dataset.pmInitialized === "true") return;
  root.dataset.pmInitialized = "true";
  const ENDPOINT = "controllers/periodController.php";

  let allPeriods = [];
  let currentEditId = null;
  let pendingAction = null; // { type: 'close' | 'delete', id, name }

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("periodAlert");
  const tableBody = document.getElementById("pmTableBody");
  const emptyState = document.getElementById("pmEmptyState");
  const tableCard = document.querySelector(".pm-table-card");

  const totalCountEl = document.getElementById("pmTotalCount");
  const openCountEl = document.getElementById("pmOpenCount");
  const closedCountEl = document.getElementById("pmClosedCount");
  const currentPeriodEl = document.getElementById("pmCurrentPeriod");

  const searchInput = document.getElementById("pmSearchInput");
  const statusFilter = document.getElementById("pmStatusFilter");

  const btnCreate = document.getElementById("pmBtnCreate");
  const btnCreateEmpty = document.getElementById("pmBtnCreateEmpty");
  const btnGenerateNext = document.getElementById("pmBtnGenerateNext");

  const formModalOverlay = document.getElementById("pmFormModalOverlay");
  const formModalTitle = document.getElementById("pmFormModalTitle");
  const periodForm = document.getElementById("pmPeriodForm");
  const periodIdInput = document.getElementById("pmPeriodId");
  const periodNameInput = document.getElementById("pmPeriodName");
  const startDateInput = document.getElementById("pmStartDate");
  const endDateInput = document.getElementById("pmEndDate");
  const payDateInput = document.getElementById("pmPayDate");
  const formError = document.getElementById("pmFormError");
  const formSubmitBtn = document.getElementById("pmFormSubmitBtn");

  const generateModalOverlay = document.getElementById(
    "pmGenerateModalOverlay",
  );
  const genName = document.getElementById("pmGenName");
  const genStart = document.getElementById("pmGenStart");
  const genEnd = document.getElementById("pmGenEnd");
  const genPay = document.getElementById("pmGenPay");
  const generateError = document.getElementById("pmGenerateError");
  const btnConfirmGenerate = document.getElementById("pmBtnConfirmGenerate");

  const confirmModalOverlay = document.getElementById("pmConfirmModalOverlay");
  const confirmTitle = document.getElementById("pmConfirmTitle");
  const confirmMessage = document.getElementById("pmConfirmMessage");
  const btnConfirmAction = document.getElementById("pmBtnConfirmAction");

  let generatedPeriodData = null;

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
    if (!dateStr) return "—";
    const d = new Date(dateStr + (dateStr.length <= 10 ? "T00:00:00" : ""));
    if (isNaN(d.getTime())) return esc(dateStr);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }

  async function apiRequest(
    action,
    { method = "GET", params = null, body = null } = {},
  ) {
    let url = `${ENDPOINT}?action=${encodeURIComponent(action)}`;
    if (params) {
      const qs = new URLSearchParams(params).toString();
      if (qs) url += `&${qs}`;
    }

    const options = {
      method,
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
    };

    if (body) {
      options.body = body;
    }

    const res = await fetch(url, options);

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

  // ---- Rendering --------------------------------------------------------------
  function renderSummary(summary) {
    if (!summary) return;
    totalCountEl.textContent = summary.total ?? 0;
    openCountEl.textContent = summary.open ?? 0;
    closedCountEl.textContent = summary.closed ?? 0;
    currentPeriodEl.textContent = summary.current
      ? summary.current.period_name
      : "None";
  }

  function getFilteredPeriods() {
    const term = (searchInput.value || "").trim().toLowerCase();
    const status = statusFilter.value;

    return allPeriods.filter(function (p) {
      const matchesTerm =
        !term || (p.period_name || "").toLowerCase().includes(term);
      const matchesStatus = !status || p.status === status;
      return matchesTerm && matchesStatus;
    });
  }

  function renderTable() {
    const periods = getFilteredPeriods();

    if (allPeriods.length === 0) {
      tableCard.querySelector(".pm-table-wrapper").style.display = "none";
      emptyState.style.display = "block";
      return;
    }

    tableCard.querySelector(".pm-table-wrapper").style.display = "";
    emptyState.style.display = "none";

    if (periods.length === 0) {
      tableBody.innerHTML =
        '<tr><td colspan="6" class="pm-loading-row">No periods match your search/filter.</td></tr>';
      return;
    }

    tableBody.innerHTML = periods
      .map(function (p) {
        const isOpen = p.status === "open";
        const badgeClass = isOpen ? "pm-badge-open" : "pm-badge-closed";
        const statusLabel = isOpen ? "OPEN" : "CLOSED";

        let actions = "";
        if (isOpen) {
          actions += `<button type="button" class="pm-icon-btn pm-icon-btn-primary" title="Edit" data-action="edit" data-id="${p.period_id}"><i class="fa-solid fa-pen"></i></button>`;
          actions += `<button type="button" class="pm-icon-btn" title="Close period" data-action="close" data-id="${p.period_id}" data-name="${esc(p.period_name)}"><i class="fa-solid fa-lock"></i></button>`;
          actions += `<button type="button" class="pm-icon-btn pm-icon-btn-danger" title="Delete" data-action="delete" data-id="${p.period_id}" data-name="${esc(p.period_name)}"><i class="fa-solid fa-trash"></i></button>`;
        } else {
          actions += `<button type="button" class="pm-icon-btn" title="View" data-action="view" data-id="${p.period_id}"><i class="fa-solid fa-eye"></i></button>`;
        }

        return `<tr>
                <td class="pm-period-name">${esc(p.period_name)}</td>
                <td>${formatDate(p.start_date)}</td>
                <td>${formatDate(p.end_date)}</td>
                <td>${formatDate(p.pay_date)}</td>
                <td><span class="pm-badge ${badgeClass}">${statusLabel}</span></td>
                <td><div class="pm-row-actions">${actions}</div></td>
            </tr>`;
      })
      .join("");
  }

  async function loadPeriods() {
    tableBody.innerHTML =
      '<tr><td colspan="6" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading payroll periods...</td></tr>';
    try {
      const data = await apiRequest("list");
      if (!data.success) {
        tableBody.innerHTML = `<tr><td colspan="6" class="pm-error-row">${esc(data.message || "Failed to load payroll periods.")}</td></tr>`;
        return;
      }
      allPeriods = data.data || [];
      renderSummary(data.summary);
      renderTable();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="6" class="pm-error-row">${esc(err.message || "Failed to load payroll periods.")}</td></tr>`;
    }
  }

  // ---- Modal helpers --------------------------------------------------------------
  function openModal(overlay) {
    overlay.style.display = "flex";
  }
  function closeModal(overlay) {
    overlay.style.display = "none";
  }

  function closeAllModals() {
    [formModalOverlay, generateModalOverlay, confirmModalOverlay].forEach(
      closeModal,
    );
    formError.style.display = "none";
    generateError.style.display = "none";
    pendingAction = null;
    generatedPeriodData = null;
  }

  document.querySelectorAll("[data-pm-close]").forEach(function (btn) {
    btn.addEventListener("click", closeAllModals);
  });

  [formModalOverlay, generateModalOverlay, confirmModalOverlay].forEach(
    function (overlay) {
      if (!overlay) return;
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) closeAllModals();
      });
    },
  );

  // ---- Create / Edit form --------------------------------------------------------------
  function openCreateModal() {
    currentEditId = null;
    formModalTitle.textContent = "Create Payroll Period";
    periodForm.reset();
    periodIdInput.value = "";
    formError.style.display = "none";
    formSubmitBtn.textContent = "Save Period";
    openModal(formModalOverlay);
    periodNameInput.focus();
  }

  async function openEditModal(id) {
    try {
      const data = await apiRequest("get", { params: { id } });
      if (!data.success) {
        showAlert(data.message || "Unable to load this period.", "error");
        return;
      }
      const p = data.data;
      if (p.status !== "open") {
        showAlert("Period cannot be edited because it is closed.", "error");
        return;
      }
      currentEditId = id;
      formModalTitle.textContent = "Edit Payroll Period";
      periodIdInput.value = p.period_id;
      periodNameInput.value = p.period_name;
      startDateInput.value = p.start_date;
      endDateInput.value = p.end_date;
      payDateInput.value = p.pay_date;
      formError.style.display = "none";
      formSubmitBtn.textContent = "Update Period";
      openModal(formModalOverlay);
    } catch (err) {
      showAlert(err.message || "Unable to load this period.", "error");
    }
  }

  function viewPeriod(id) {
    const p = allPeriods.find(function (x) {
      return String(x.period_id) === String(id);
    });
    if (!p) return;
    formModalTitle.textContent = "View Payroll Period (Closed)";
    periodIdInput.value = p.period_id;
    periodNameInput.value = p.period_name;
    startDateInput.value = p.start_date;
    endDateInput.value = p.end_date;
    payDateInput.value = p.pay_date;
    [periodNameInput, startDateInput, endDateInput, payDateInput].forEach(
      function (el) {
        el.disabled = true;
      },
    );
    formSubmitBtn.style.display = "none";
    formError.style.display = "none";
    openModal(formModalOverlay);

    // Re-enable once closed, so the next create/edit works normally.
    formModalOverlay.addEventListener("click", restoreFormOnce, { once: true });
    document.querySelectorAll("[data-pm-close]").forEach(function (btn) {
      btn.addEventListener("click", restoreFormOnce, { once: true });
    });
  }

  function restoreFormOnce() {
    [periodNameInput, startDateInput, endDateInput, payDateInput].forEach(
      function (el) {
        el.disabled = false;
      },
    );
    formSubmitBtn.style.display = "";
  }

  periodForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    formError.style.display = "none";

    const name = periodNameInput.value.trim();
    const start = startDateInput.value;
    const end = endDateInput.value;
    const pay = payDateInput.value;

    if (!name || !start || !end || !pay) {
      formError.textContent = "Please fill in all required fields.";
      formError.style.display = "block";
      return;
    }
    if (new Date(start) > new Date(end)) {
      formError.textContent = "Start date must be on or before the end date.";
      formError.style.display = "block";
      return;
    }
    if (new Date(pay) < new Date(end)) {
      formError.textContent = "Pay date must be on or after the end date.";
      formError.style.display = "block";
      return;
    }

    const isEdit = !!currentEditId;
    const fd = new FormData();
    fd.append("action", isEdit ? "update" : "create");
    if (isEdit) fd.append("period_id", currentEditId);
    fd.append("period_name", name);
    fd.append("start_date", start);
    fd.append("end_date", end);
    fd.append("pay_date", pay);

    formSubmitBtn.disabled = true;
    try {
      const res = await fetch(ENDPOINT, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();

      if (data.success) {
        closeAllModals();
        showAlert(
          data.message ||
            (isEdit
              ? "Period updated successfully."
              : "Period created successfully."),
          "success",
        );
        loadPeriods();
      } else {
        formError.textContent =
          data.message ||
          (isEdit
            ? "Failed to update payroll period."
            : "Failed to create payroll period.");
        formError.style.display = "block";
      }
    } catch (err) {
      formError.textContent = "Something went wrong. Please try again.";
      formError.style.display = "block";
    } finally {
      formSubmitBtn.disabled = false;
    }
  });

  // ---- Generate next period --------------------------------------------------------------
  async function openGenerateModal() {
    generateError.style.display = "none";
    genName.textContent = "Loading…";
    genStart.textContent = genEnd.textContent = genPay.textContent = "—";
    openModal(generateModalOverlay);

    try {
      const data = await apiRequest("next_period");
      if (!data.success) {
        generateError.textContent =
          data.message || "Failed to generate the next period.";
        generateError.style.display = "block";
        genName.textContent = "—";
        return;
      }
      generatedPeriodData = data.data;
      genName.textContent = data.data.period_name;
      genStart.textContent = formatDate(data.data.start_date);
      genEnd.textContent = formatDate(data.data.end_date);
      genPay.textContent = formatDate(data.data.pay_date);
    } catch (err) {
      generateError.textContent =
        err.message || "Failed to generate the next period.";
      generateError.style.display = "block";
    }
  }

  btnConfirmGenerate.addEventListener("click", async function () {
    if (!generatedPeriodData) return;

    const fd = new FormData();
    fd.append("action", "create");
    fd.append("period_name", generatedPeriodData.period_name);
    fd.append("start_date", generatedPeriodData.start_date);
    fd.append("end_date", generatedPeriodData.end_date);
    fd.append("pay_date", generatedPeriodData.pay_date);

    btnConfirmGenerate.disabled = true;
    try {
      const res = await fetch(ENDPOINT, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      if (data.success) {
        closeAllModals();
        showAlert(
          data.message || "Payroll period created successfully.",
          "success",
        );
        loadPeriods();
      } else {
        generateError.textContent =
          data.message || "Failed to create payroll period.";
        generateError.style.display = "block";
      }
    } catch (err) {
      generateError.textContent = "Something went wrong. Please try again.";
      generateError.style.display = "block";
    } finally {
      btnConfirmGenerate.disabled = false;
    }
  });

  // ---- Close / Delete confirmation --------------------------------------------------------------
  function askConfirm(type, id, name) {
    pendingAction = { type, id, name };
    if (type === "close") {
      confirmTitle.textContent = "Close Payroll Period";
      confirmMessage.textContent = `Are you sure you want to close the payroll period "${name}"? Once closed, it can no longer be edited or deleted.`;
      btnConfirmAction.className = "pm-btn pm-btn-primary";
      btnConfirmAction.textContent = "Close Period";
    } else {
      confirmTitle.textContent = "Delete Payroll Period";
      confirmMessage.textContent = `Are you sure you want to delete the payroll period "${name}"? This cannot be undone.`;
      btnConfirmAction.className = "pm-btn pm-btn-danger";
      btnConfirmAction.textContent = "Delete Period";
    }
    openModal(confirmModalOverlay);
  }

  btnConfirmAction.addEventListener("click", async function () {
    if (!pendingAction) return;
    const { type, id } = pendingAction;

    const fd = new FormData();
    fd.append("action", type);
    fd.append("period_id", id);

    btnConfirmAction.disabled = true;
    try {
      const res = await fetch(ENDPOINT, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeAllModals();
      if (data.success) {
        showAlert(data.message || "Done.", "success");
      } else {
        showAlert(data.message || "Action failed.", "error");
      }
      loadPeriods();
    } catch (err) {
      closeAllModals();
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmAction.disabled = false;
    }
  });

  // ---- Table action delegation --------------------------------------------------------------
  tableBody.addEventListener("click", function (e) {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const action = btn.getAttribute("data-action");
    const id = btn.getAttribute("data-id");
    const name = btn.getAttribute("data-name");

    if (action === "edit") openEditModal(id);
    else if (action === "view") viewPeriod(id);
    else if (action === "close") askConfirm("close", id, name);
    else if (action === "delete") askConfirm("delete", id, name);
  });

  // ---- Toolbar events --------------------------------------------------------------
  btnCreate.addEventListener("click", openCreateModal);
  if (btnCreateEmpty) btnCreateEmpty.addEventListener("click", openCreateModal);
  btnGenerateNext.addEventListener("click", openGenerateModal);
  searchInput.addEventListener("input", renderTable);
  statusFilter.addEventListener("change", renderTable);

  // ---- Init --------------------------------------------------------------
  loadPeriods();
}

window.addEventListener("page:loaded", initPeriodManager);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPeriodManager);
} else {
  initPeriodManager();
}
