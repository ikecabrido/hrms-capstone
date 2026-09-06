/**
 * Final Settlement
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/settlementController.php (AJAX/JSON),
 * which wraps the existing SettlementController / SettlementModel.
 *
 * Workflow implemented here:
 *
 *   Exit Management sends a request
 *     -> Payroll accepts it              (Settlement Requests tab)
 *     -> Payroll adds earnings/deductions
 *        and calculates the settlement   (Processing & Calculation tab)
 *     -> Payroll submits it for approval
 *     -> an authorized user approves it
 *     -> Payroll releases the payment     (Approval & Release tab)
 *
 * There is no "Create Settlement" action — settlements only come into
 * existence through Accept Request, which calls
 * SettlementController::acceptRequest().
 *
 * This file is imported once by js/script.js. Because pages are swapped
 * into .container via innerHTML (see js/utils/main.js), it re-binds its
 * listeners every time the "page:loaded" event fires, and simply exits
 * early if the Final Settlement markup isn't present on the current page.
 */

function initFinalSettlement() {
  const root = document.getElementById("finalSettlementPage");
  if (!root) return; // Not on the Final Settlement page — nothing to do.
  if (root.dataset.fsInitialized === "true") return;
  root.dataset.fsInitialized = "true";

  const ENDPOINT = "controllers/settlementController.php";

  // ---- State ------------------------------------------------------------

  let currentDetailSettlementId = null; // settlement open in the workspace modal
  let currentDetailSettlement = null;
  let currentDetailItems = [];

  let currentRequestId = null; // exit settlement request open in the request modal
  let currentRequestRecord = null;

  let pendingItemAction = null; // "add" | "edit"
  let pendingDeleteItemId = null;
  let pendingCancelSettlementId = null;

  // ---- Labels -------------------------------------------------------------

  const STATUS_LABELS = {
    draft: "Draft",
    requested: "Requested",
    processing: "Processing",
    calculated: "Calculated",
    for_approval: "For Approval",
    approved: "Approved",
    paid: "Paid",
    cancelled: "Cancelled",
    pending: "Pending",
  };

  const WORKFLOW_STEPS = [
    { key: "processing", label: "Processing" },
    { key: "calculated", label: "Calculated" },
    { key: "for_approval", label: "For Approval" },
    { key: "approved", label: "Approved" },
    { key: "paid", label: "Paid" },
  ];

  // ---- Element references ------------------------------------------------

  const alertBox = document.getElementById("fsAlert");

  // Summary cards
  const pendingRequestsCountEl = document.getElementById(
    "fsPendingRequestsCount",
  );
  const forApprovalCountEl = document.getElementById("fsForApprovalCount");
  const approvedCountEl = document.getElementById("fsApprovedCount");
  const paidCountEl = document.getElementById("fsPaidCount");
  const totalPaidEl = document.getElementById("fsTotalPaid");

  // Tab counters
  const tabRequestsCountEl = document.getElementById("fsTabRequestsCount");
  const tabProcessingCountEl = document.getElementById("fsTabProcessingCount");
  const tabApprovalCountEl = document.getElementById("fsTabApprovalCount");

  // Tab 1 — Settlement Requests
  const reqSearchInput = document.getElementById("fsReqSearchInput");
  const reqStatusFilter = document.getElementById("fsReqStatusFilter");
  const reqExitTypeFilter = document.getElementById("fsReqExitTypeFilter");
  const reqBtnSearch = document.getElementById("fsReqBtnSearch");
  const reqBtnClear = document.getElementById("fsReqBtnClear");
  const reqTableBody = document.getElementById("fsReqTableBody");
  const reqEmptyState = document.getElementById("fsReqEmptyState");
  const reqEmptyStateText = document.getElementById("fsReqEmptyStateText");

  // Tab 2 — Processing & Calculation
  const procSearchInput = document.getElementById("fsProcSearchInput");
  const procStatusFilter = document.getElementById("fsProcStatusFilter");
  const procExitTypeFilter = document.getElementById("fsProcExitTypeFilter");
  const procBtnSearch = document.getElementById("fsProcBtnSearch");
  const procBtnClear = document.getElementById("fsProcBtnClear");
  const procTableBody = document.getElementById("fsProcTableBody");
  const procEmptyState = document.getElementById("fsProcEmptyState");
  const procEmptyStateText = document.getElementById("fsProcEmptyStateText");

  // Tab 3 — Approval & Release
  const apprSearchInput = document.getElementById("fsApprSearchInput");
  const apprStatusFilter = document.getElementById("fsApprStatusFilter");
  const apprExitTypeFilter = document.getElementById("fsApprExitTypeFilter");
  const apprBtnSearch = document.getElementById("fsApprBtnSearch");
  const apprBtnClear = document.getElementById("fsApprBtnClear");
  const apprTableBody = document.getElementById("fsApprTableBody");
  const apprEmptyState = document.getElementById("fsApprEmptyState");
  const apprEmptyStateText = document.getElementById("fsApprEmptyStateText");

  // Request detail modal
  const requestOverlay = document.getElementById("fsRequestModalOverlay");
  const requestSubtitle = document.getElementById("fsRequestSubtitle");
  const requestEmployeeInfo = document.getElementById("fsRequestEmployeeInfo");
  const requestExitInfo = document.getElementById("fsRequestExitInfo");
  const requestInfo = document.getElementById("fsRequestInfo");
  const requestActions = document.getElementById("fsRequestActions");

  // Accept confirmation modal
  const acceptOverlay = document.getElementById("fsAcceptModalOverlay");
  const acceptConfirmText = document.getElementById("fsAcceptConfirmText");
  const btnConfirmAccept = document.getElementById("fsBtnConfirmAccept");

  // Settlement detail / workspace modal
  const detailOverlay = document.getElementById("fsDetailModalOverlay");
  const detailSubtitle = document.getElementById("fsDetailSubtitle");
  const workflowEl = document.getElementById("fsWorkflow");
  const employeeInfoEl = document.getElementById("fsEmployeeInfo");
  const exitInfoEl = document.getElementById("fsExitInfo");
  const settlementInfoEl = document.getElementById("fsSettlementInfo");
  const earningsBody = document.getElementById("fsEarningsBody");
  const totalEarningsEl = document.getElementById("fsTotalEarnings");
  const deductionsBody = document.getElementById("fsDeductionsBody");
  const totalDeductionsEl = document.getElementById("fsTotalDeductions");
  const calcEarningsEl = document.getElementById("fsCalcEarnings");
  const calcDeductionsEl = document.getElementById("fsCalcDeductions");
  const calcNetEl = document.getElementById("fsCalcNet");
  const paymentSection = document.getElementById("fsPaymentSection");
  const paymentInfoEl = document.getElementById("fsPaymentInfo");
  const activitySection = document.getElementById("fsActivitySection");
  const activityInfoEl = document.getElementById("fsActivityInfo");
  const detailActions = document.getElementById("fsDetailActions");
  const btnAddEarning = document.getElementById("fsBtnAddEarning");
  const btnAddDeduction = document.getElementById("fsBtnAddDeduction");

  // Add/Edit item modal
  const itemOverlay = document.getElementById("fsItemModalOverlay");
  const itemModalTitle = document.getElementById("fsItemModalTitle");
  const itemForm = document.getElementById("fsItemForm");
  const itemIdInput = document.getElementById("fsItemId");
  const itemSettlementIdInput = document.getElementById("fsItemSettlementId");
  const itemTypeSelect = document.getElementById("fsItemType");
  const itemCategoryInput = document.getElementById("fsItemCategory");
  const itemDescriptionInput = document.getElementById("fsItemDescription");
  const itemAmountInput = document.getElementById("fsItemAmount");
  const itemCodeInput = document.getElementById("fsItemCode");
  const itemFormError = document.getElementById("fsItemFormError");
  const itemSubmitBtn = document.getElementById("fsItemSubmitBtn");

  // Delete item modal
  const deleteItemOverlay = document.getElementById("fsDeleteItemModalOverlay");
  const btnConfirmDeleteItem = document.getElementById(
    "fsBtnConfirmDeleteItem",
  );

  // Calculate modal
  const calculateOverlay = document.getElementById("fsCalculateModalOverlay");
  const btnConfirmCalculate = document.getElementById("fsBtnConfirmCalculate");

  // Submit for approval modal
  const submitApprovalOverlay = document.getElementById(
    "fsSubmitApprovalModalOverlay",
  );
  const btnConfirmSubmitApproval = document.getElementById(
    "fsBtnConfirmSubmitApproval",
  );

  // Approve modal
  const approveOverlay = document.getElementById("fsApproveModalOverlay");
  const approveConfirmText = document.getElementById("fsApproveConfirmText");
  const btnConfirmApprove = document.getElementById("fsBtnConfirmApprove");

  // Release modal
  const releaseOverlay = document.getElementById("fsReleaseModalOverlay");
  const releaseForm = document.getElementById("fsReleaseForm");
  const releasePaymentMethod = document.getElementById(
    "fsReleasePaymentMethod",
  );
  const releasePaymentReference = document.getElementById(
    "fsReleasePaymentReference",
  );
  const releaseFormError = document.getElementById("fsReleaseFormError");

  // Cancel modal
  const cancelOverlay = document.getElementById("fsCancelModalOverlay");
  const cancelRemarks = document.getElementById("fsCancelRemarks");
  const btnConfirmCancel = document.getElementById("fsBtnConfirmCancel");

  const printSheet = document.getElementById("fsPrintSheet");

  const ALL_OVERLAYS = [
    requestOverlay,
    acceptOverlay,
    detailOverlay,
    itemOverlay,
    deleteItemOverlay,
    calculateOverlay,
    submitApprovalOverlay,
    approveOverlay,
    releaseOverlay,
    cancelOverlay,
  ];

  // ---- Helpers ------------------------------------------------------------

  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  function peso(n) {
    const num = Number(n) || 0;
    return (
      "\u20b1" +
      num.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function formatDate(value) {
    if (!value) return "\u2014";
    const raw = String(value).split(" ")[0].split("T")[0];
    const d = new Date(raw + "T00:00:00");
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function formatDateTime(value) {
    if (!value) return "\u2014";
    const normalized = String(value).replace(" ", "T");
    const d = new Date(normalized);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  }

  function showAlert(message, type) {
    alertBox.className = "pm-alert pm-alert-" + (type || "success");
    alertBox.textContent = message;
    alertBox.style.display = "block";
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(() => {
      alertBox.style.display = "none";
    }, 3500);
    // Scroll the alert into view so the user actually sees it, since the
    // page can be scrolled down while a modal is open.
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function openModal(overlay) {
    if (!overlay) return;
    // Portal the overlay to <body> so it escapes any stacking context
    // created by ancestors inside .container.
    if (overlay.parentElement !== document.body) {
      if (!overlay.dataset.fsHomeMarker) {
        const marker = document.createComment("fs-modal-home:" + overlay.id);
        overlay.parentElement.insertBefore(marker, overlay);
        overlay.dataset.fsHomeMarker = overlay.id;
        overlay._fsHomeMarker = marker;
      }
      document.body.appendChild(overlay);
    }
    overlay.style.display = "flex";
  }

  function closeModal(overlay) {
    if (!overlay) return;
    overlay.style.display = "none";
    if (overlay._fsHomeMarker && overlay._fsHomeMarker.parentNode) {
      overlay._fsHomeMarker.parentNode.insertBefore(
        overlay,
        overlay._fsHomeMarker,
      );
    }
  }

  function badgeHtml(status) {
    const label = STATUS_LABELS[status] || status || "\u2014";
    return `<span class="fs-status-badge fs-status-${esc(status)}">${esc(label)}</span>`;
  }

  function exitTypeLabel(type) {
    if (!type) return "\u2014";
    return type.charAt(0).toUpperCase() + type.slice(1);
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
    if (body) options.body = body;

    const res = await fetch(url, options);

    if (res.status === 401) {
      let data = {};
      try {
        data = await res.json();
      } catch (e) {
        /* ignore */
      }
      if (data.redirect) window.location.href = data.redirect;
      throw new Error("Unauthorized");
    }

    return res.json();
  }

  // ---- Modal close wiring (shared across all modals on this page) --------

  document.addEventListener("click", (e) => {
    const closeTrigger = e.target.closest("[data-fs-close]");
    if (closeTrigger) {
      closeModal(
        document.getElementById(closeTrigger.getAttribute("data-fs-close")),
      );
      return;
    }
    if (
      e.target.classList &&
      e.target.classList.contains("pm-modal-overlay") &&
      ALL_OVERLAYS.includes(e.target)
    ) {
      closeModal(e.target);
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    ALL_OVERLAYS.forEach((ov) => {
      if (ov && ov.style.display === "flex") closeModal(ov);
    });
  });

  // ==========================================================================
  // SUMMARY CARDS + TAB COUNTERS
  // ==========================================================================

  function applySummary(summary, requestSummary) {
    if (summary) {
      forApprovalCountEl.textContent = summary.for_approval_count ?? 0;
      approvedCountEl.textContent = summary.approved_count ?? 0;
      paidCountEl.textContent = summary.paid_count ?? 0;
      totalPaidEl.textContent = peso(summary.total_paid ?? 0);

      tabProcessingCountEl.textContent =
        (summary.processing_count ?? 0) + (summary.calculated_count ?? 0);
      tabApprovalCountEl.textContent =
        (summary.for_approval_count ?? 0) + (summary.approved_count ?? 0);
    }

    if (requestSummary) {
      pendingRequestsCountEl.textContent = requestSummary.pending_requests ?? 0;
      tabRequestsCountEl.textContent = requestSummary.pending_requests ?? 0;
    }
  }

  async function loadSummary() {
    try {
      const data = await apiRequest("summary");
      if (data.success) {
        applySummary(data.summary, data.request_summary);
      }
    } catch (err) {
      // Non-fatal — summary cards simply keep their last known values.
    }
  }

  // ==========================================================================
  // TAB 1 — SETTLEMENT REQUESTS
  // ==========================================================================

  function requestRowActionsHtml(r) {
    const buttons = [
      `<button type="button" class="pm-icon-btn pm-icon-btn-primary" title="View Request" data-fs-view-request="${r.exit_settlement_id}"><i class="fa-solid fa-eye"></i></button>`,
    ];
    if (r.status === "requested") {
      buttons.push(
        `<button type="button" class="pm-icon-btn pm-icon-btn-success" title="Accept Request" data-fs-accept-request="${r.exit_settlement_id}"><i class="fa-solid fa-check"></i></button>`,
      );
    }
    return `<div class="pm-row-actions">${buttons.join("")}</div>`;
  }

  function renderRequestsTable(rows) {
    if (!rows.length) {
      reqTableBody.innerHTML = "";
      reqEmptyState.style.display = "block";
      reqEmptyStateText.textContent =
        "No settlement requests match your filters.";
      return;
    }

    reqEmptyState.style.display = "none";
    reqTableBody.innerHTML = rows
      .map(
        (r) => `
      <tr>
        <td>REQ-${String(r.exit_settlement_id).padStart(4, "0")}</td>
        <td>${esc(r.employee_name)}</td>
        <td class="fs-employee-code">${esc(r.employee_code)}</td>
        <td>${esc(exitTypeLabel(r.exit_case_type))}</td>
        <td>${formatDate(r.last_working_date)}</td>
        <td>${formatDate(r.requested_at || r.created_at)}</td>
        <td>${badgeHtml(r.status)}</td>
        <td class="pm-actions-col">${requestRowActionsHtml(r)}</td>
      </tr>`,
      )
      .join("");
  }

  async function loadRequests() {
    reqTableBody.innerHTML = `<tr><td colspan="8" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlement requests...</td></tr>`;
    reqEmptyState.style.display = "none";

    try {
      const data = await apiRequest("requests", {
        params: {
          search: reqSearchInput.value.trim(),
          status: reqStatusFilter.value,
          exit_type: reqExitTypeFilter.value,
        },
      });

      if (!data.success) {
        reqTableBody.innerHTML = "";
        reqEmptyState.style.display = "block";
        reqEmptyStateText.textContent =
          data.message || "Failed to load settlement requests.";
        return;
      }

      renderRequestsTable(data.data || []);
      applySummary(data.summary, data.request_summary);
    } catch (err) {
      reqTableBody.innerHTML = "";
      reqEmptyState.style.display = "block";
      reqEmptyStateText.textContent = "Something went wrong. Please try again.";
    }
  }

  reqBtnSearch.addEventListener("click", loadRequests);
  reqSearchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadRequests();
  });
  reqStatusFilter.addEventListener("change", loadRequests);
  reqExitTypeFilter.addEventListener("change", loadRequests);
  reqBtnClear.addEventListener("click", () => {
    reqSearchInput.value = "";
    reqStatusFilter.value = "";
    reqExitTypeFilter.value = "";
    loadRequests();
  });

  reqTableBody.addEventListener("click", (e) => {
    const viewBtn = e.target.closest("[data-fs-view-request]");
    if (viewBtn) {
      openRequestDetail(
        parseInt(viewBtn.getAttribute("data-fs-view-request"), 10),
      );
      return;
    }
    const acceptBtn = e.target.closest("[data-fs-accept-request]");
    if (acceptBtn) {
      askAcceptRequest(
        parseInt(acceptBtn.getAttribute("data-fs-accept-request"), 10),
      );
    }
  });

  // ---- Request detail modal ----------------------------------------------

  async function openRequestDetail(exitSettlementId) {
    currentRequestId = exitSettlementId;
    requestSubtitle.textContent = "Loading\u2026";
    requestEmployeeInfo.innerHTML = "";
    requestExitInfo.innerHTML = "";
    requestInfo.innerHTML = "";
    requestActions.innerHTML = "";
    openModal(requestOverlay);

    try {
      const data = await apiRequest("request", {
        params: { id: exitSettlementId },
      });

      if (!data.success || !data.data) {
        requestSubtitle.textContent = "\u2014";
        requestInfo.innerHTML = `<div class="fs-info-row"><span class="fs-detail-value">${esc(data.message || "Settlement request not found.")}</span></div>`;
        return;
      }

      currentRequestRecord = data.data;
      renderRequestDetail(data.data);
    } catch (err) {
      requestSubtitle.textContent = "\u2014";
      requestInfo.innerHTML = `<div class="fs-info-row"><span class="fs-detail-value">Failed to load settlement request.</span></div>`;
    }
  }

  function infoRow(label, value) {
    return `
      <div class="fs-info-row">
        <span class="fs-detail-label">${esc(label)}</span>
        <span class="fs-detail-value">${value}</span>
      </div>`;
  }

  function renderRequestDetail(r) {
    requestSubtitle.textContent = `${r.employee_code} \u2014 ${r.employee_name}`;

    requestEmployeeInfo.innerHTML =
      infoRow("Employee Code", esc(r.employee_code)) +
      infoRow("Employee Name", esc(r.employee_name));

    requestExitInfo.innerHTML =
      infoRow("Exit Type", esc(exitTypeLabel(r.exit_case_type))) +
      infoRow("Exit Case ID", esc(r.exit_case_id ?? "\u2014")) +
      infoRow("Last Working Date", formatDate(r.last_working_date));

    let requestInfoHtml =
      infoRow(
        "Settlement Request ID",
        "REQ-" + String(r.settlement_id).padStart(4, "0"),
      ) +
      infoRow(
        "Requested Date",
        formatDateTime(r.requested_at || r.created_at),
      ) +
      infoRow("Status", badgeHtml(r.status)) +
      infoRow("Remarks", r.remarks ? esc(r.remarks) : "\u2014");

    if (r.payroll_settlement_id) {
      requestInfoHtml += infoRow(
        "Payroll Settlement",
        `Settlement #${r.payroll_settlement_id} \u2014 ${badgeHtml(r.payroll_status || r.status)}`,
      );
    }
    requestInfo.innerHTML = requestInfoHtml;

    let actionsHtml = "";
    if (r.status === "requested") {
      actionsHtml = `<button type="button" class="pm-btn pm-btn-primary" id="fsRequestActAccept"><i class="fa-solid fa-check"></i> Accept Request</button>`;
    }
    requestActions.innerHTML = actionsHtml;

    const acceptBtn = document.getElementById("fsRequestActAccept");
    if (acceptBtn) {
      acceptBtn.addEventListener("click", () => {
        closeModal(requestOverlay);
        askAcceptRequest(r.settlement_id);
      });
    }
  }

  // ---- Accept request -----------------------------------------------------

  function askAcceptRequest(exitSettlementId) {
    currentRequestId = exitSettlementId;
    acceptConfirmText.textContent =
      "Accept this settlement request from Exit Management and begin Payroll processing?";
    openModal(acceptOverlay);
  }

  btnConfirmAccept.addEventListener("click", async () => {
    if (!currentRequestId) return;
    btnConfirmAccept.disabled = true;

    const fd = new FormData();
    fd.append("exit_settlement_id", currentRequestId);

    try {
      const res = await fetch(`${ENDPOINT}?action=accept_request`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(acceptOverlay);

      if (data.success) {
        showAlert(data.message || "Settlement request accepted.", "success");
        const newSettlementId = data.data && data.data.settlement_id;
        loadRequests();
        loadProcessing();
        loadSummary();
        setActiveTab("fsTabProcessing");
        if (newSettlementId) {
          openSettlementDetail(newSettlementId);
        }
      } else {
        showAlert(
          data.message || "Failed to accept settlement request.",
          "error",
        );
      }
    } catch (err) {
      closeModal(acceptOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmAccept.disabled = false;
    }
  });

  // ==========================================================================
  // TAB 2 — PROCESSING & CALCULATION
  // ==========================================================================

  function settlementRowActionsHtml(s) {
    return `
      <div class="pm-row-actions">
        <button type="button" class="pm-icon-btn pm-icon-btn-primary" title="View" data-fs-view-settlement="${s.settlement_id}"><i class="fa-solid fa-eye"></i></button>
      </div>`;
  }

  function renderSettlementRows(body, rows) {
    return rows
      .map(
        (s) => `
      <tr>
        <td>${esc(s.employee_name)}</td>
        <td class="fs-employee-code">${esc(s.employee_code)}</td>
        <td>${esc(exitTypeLabel(s.exit_case_type))}</td>
        <td>${formatDate(s.last_working_date)}</td>
        <td class="fs-amount-cell">${peso(s.total_earnings)}</td>
        <td class="fs-amount-cell">${peso(s.total_deductions)}</td>
        <td class="fs-amount-cell">${peso(s.net_settlement)}</td>
        <td>${badgeHtml(s.status)}</td>
        <td class="pm-actions-col">${settlementRowActionsHtml(s)}</td>
      </tr>`,
      )
      .join("");
  }

  async function loadProcessing() {
    procTableBody.innerHTML = `<tr><td colspan="9" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlements...</td></tr>`;
    procEmptyState.style.display = "none";

    const status = procStatusFilter.value;

    try {
      const data = await apiRequest("list", {
        params: {
          search: procSearchInput.value.trim(),
          status: status,
          exit_type: procExitTypeFilter.value,
        },
      });

      if (!data.success) {
        procTableBody.innerHTML = "";
        procEmptyState.style.display = "block";
        procEmptyStateText.textContent =
          data.message || "Failed to load settlements.";
        return;
      }

      let rows = data.data || [];
      if (!status) {
        rows = rows.filter((s) =>
          ["draft", "processing", "calculated", "cancelled"].includes(s.status),
        );
      }

      if (!rows.length) {
        procTableBody.innerHTML = "";
        procEmptyState.style.display = "block";
        procEmptyStateText.textContent =
          "No settlements are currently being processed.";
        return;
      }

      procEmptyState.style.display = "none";
      procTableBody.innerHTML = renderSettlementRows(procTableBody, rows);
      applySummary(data.summary, null);
    } catch (err) {
      procTableBody.innerHTML = "";
      procEmptyState.style.display = "block";
      procEmptyStateText.textContent =
        "Something went wrong. Please try again.";
    }
  }

  procBtnSearch.addEventListener("click", loadProcessing);
  procSearchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadProcessing();
  });
  procStatusFilter.addEventListener("change", loadProcessing);
  procExitTypeFilter.addEventListener("change", loadProcessing);
  procBtnClear.addEventListener("click", () => {
    procSearchInput.value = "";
    procStatusFilter.value = "";
    procExitTypeFilter.value = "";
    loadProcessing();
  });

  procTableBody.addEventListener("click", (e) => {
    const viewBtn = e.target.closest("[data-fs-view-settlement]");
    if (viewBtn) {
      openSettlementDetail(
        parseInt(viewBtn.getAttribute("data-fs-view-settlement"), 10),
      );
    }
  });

  // ==========================================================================
  // TAB 3 — APPROVAL & RELEASE
  // ==========================================================================

  async function loadApproval() {
    apprTableBody.innerHTML = `<tr><td colspan="9" class="pm-loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Loading settlements...</td></tr>`;
    apprEmptyState.style.display = "none";

    const status = apprStatusFilter.value;

    try {
      const data = await apiRequest("list", {
        params: {
          search: apprSearchInput.value.trim(),
          status: status,
          exit_type: apprExitTypeFilter.value,
        },
      });

      if (!data.success) {
        apprTableBody.innerHTML = "";
        apprEmptyState.style.display = "block";
        apprEmptyStateText.textContent =
          data.message || "Failed to load settlements.";
        return;
      }

      let rows = data.data || [];
      if (!status) {
        rows = rows.filter((s) =>
          ["for_approval", "approved", "paid"].includes(s.status),
        );
      }

      if (!rows.length) {
        apprTableBody.innerHTML = "";
        apprEmptyState.style.display = "block";
        apprEmptyStateText.textContent =
          "Nothing is awaiting approval or release.";
        return;
      }

      apprEmptyState.style.display = "none";
      apprTableBody.innerHTML = renderSettlementRows(apprTableBody, rows);
      applySummary(data.summary, null);
    } catch (err) {
      apprTableBody.innerHTML = "";
      apprEmptyState.style.display = "block";
      apprEmptyStateText.textContent =
        "Something went wrong. Please try again.";
    }
  }

  apprBtnSearch.addEventListener("click", loadApproval);
  apprSearchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadApproval();
  });
  apprStatusFilter.addEventListener("change", loadApproval);
  apprExitTypeFilter.addEventListener("change", loadApproval);
  apprBtnClear.addEventListener("click", () => {
    apprSearchInput.value = "";
    apprStatusFilter.value = "";
    apprExitTypeFilter.value = "";
    loadApproval();
  });

  apprTableBody.addEventListener("click", (e) => {
    const viewBtn = e.target.closest("[data-fs-view-settlement]");
    if (viewBtn) {
      openSettlementDetail(
        parseInt(viewBtn.getAttribute("data-fs-view-settlement"), 10),
      );
    }
  });

  // ---- Tab switching (drives which list is (re)loaded) --------------------

  function setActiveTab(tabId) {
    const container = document.querySelector(".fs-main-tabs");
    if (!container) return;
    container.querySelectorAll(".tab-item").forEach((t) => {
      t.classList.toggle("active", t.getAttribute("data-tab") === tabId);
    });
    container.querySelectorAll(".tab-content").forEach((c) => {
      c.classList.toggle("active", c.id === tabId);
    });
  }

  document
    .querySelectorAll(".fs-main-tabs > .fs-main-tab-list > .tab-item")
    .forEach((tabBtn) => {
      tabBtn.addEventListener("click", () => {
        const tabId = tabBtn.getAttribute("data-tab");
        if (tabId === "fsTabProcessing") loadProcessing();
        if (tabId === "fsTabApproval") loadApproval();
      });
    });

  // ==========================================================================
  // SETTLEMENT DETAIL / PROCESSING WORKSPACE
  // ==========================================================================

  function computeTotalsFromItems(items) {
    const totalEarnings = items
      .filter((i) => i.item_type === "earning")
      .reduce((sum, i) => sum + Number(i.amount || 0), 0);
    const totalDeductions = items
      .filter((i) => i.item_type === "deduction")
      .reduce((sum, i) => sum + Number(i.amount || 0), 0);
    return {
      totalEarnings,
      totalDeductions,
      net: totalEarnings - totalDeductions,
    };
  }

  async function openSettlementDetail(settlementId) {
    currentDetailSettlementId = settlementId;
    detailSubtitle.textContent = "Loading\u2026";
    openModal(detailOverlay);

    try {
      const data = await apiRequest("get_settlement", {
        params: { id: settlementId },
      });

      if (!data.success || !data.data) {
        detailSubtitle.textContent = "\u2014";
        showAlert(data.message || "Failed to load settlement.", "error");
        closeModal(detailOverlay);
        return;
      }

      currentDetailSettlement = data.data;
      currentDetailItems = data.items || [];
      renderDetailModal();
    } catch (err) {
      detailSubtitle.textContent = "\u2014";
      showAlert("Failed to load settlement details.", "error");
      closeModal(detailOverlay);
    }
  }

  function renderItemsTable(body, totalEl, items) {
    if (!items.length) {
      body.innerHTML = `<tr><td colspan="4" class="pm-loading-row">No items recorded.</td></tr>`;
      totalEl.textContent = peso(0);
      return;
    }

    const canModify =
      currentDetailSettlement &&
      ["draft", "processing"].includes(currentDetailSettlement.status);

    body.innerHTML = items
      .map((item) => {
        const actions = canModify
          ? `
          <div class="pm-row-actions">
            <button type="button" class="pm-icon-btn" title="Edit" data-fs-edit-item="${item.item_id}"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="pm-icon-btn pm-icon-btn-danger" title="Remove" data-fs-delete-item="${item.item_id}"><i class="fa-solid fa-trash"></i></button>
          </div>`
          : `<span class="fs-locked-note"><i class="fa-solid fa-lock"></i></span>`;
        return `
        <tr>
          <td>${esc(item.item_category)}</td>
          <td>${esc(item.description)}</td>
          <td class="fs-amount-col">${peso(item.amount)}</td>
          <td class="pm-actions-col">${actions}</td>
        </tr>`;
      })
      .join("");

    const total = items.reduce((sum, i) => sum + Number(i.amount || 0), 0);
    totalEl.textContent = peso(total);
  }

  function renderWorkflowInto(el, status) {
    el.classList.toggle("fs-workflow-cancelled", status === "cancelled");

    if (status === "cancelled") {
      el.innerHTML = `
        <div class="fs-workflow-step fs-step-current">
          <div class="fs-workflow-dot"><i class="fa-solid fa-xmark"></i></div>
          <div class="fs-workflow-label">Cancelled</div>
        </div>`;
      return;
    }

    // "draft" behaves like the very start of the Processing stage for
    // workflow display purposes (the backend treats draft/processing the
    // same way for item modification and calculation).
    const effectiveStatus = status === "draft" ? "processing" : status;
    const currentIdx = WORKFLOW_STEPS.findIndex(
      (s) => s.key === effectiveStatus,
    );

    el.innerHTML = WORKFLOW_STEPS.map((s, i) => {
      let cls = "";
      let icon = String(i + 1);
      if (i < currentIdx) {
        cls = "fs-step-done";
        icon = '<i class="fa-solid fa-check"></i>';
      } else if (i === currentIdx) {
        cls = "fs-step-current";
      }
      return `
        <div class="fs-workflow-step ${cls}">
          <div class="fs-workflow-dot">${icon}</div>
          <div class="fs-workflow-label">${s.label}</div>
        </div>`;
    }).join("");
  }

  function renderDetailActions(s) {
    let lockedNote = "";
    let actions = "";

    if (s.status === "draft" || s.status === "processing") {
      actions = `<button type="button" class="pm-btn pm-btn-primary" id="fsActCalculate"><i class="fa-solid fa-calculator"></i> Calculate Settlement</button>`;
    } else if (s.status === "calculated") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-lock"></i> Items are read-only once calculated.</div>`;
      actions = `<button type="button" class="pm-btn pm-btn-primary" id="fsActSubmitApproval"><i class="fa-solid fa-paper-plane"></i> Submit for Approval</button>`;
    } else if (s.status === "for_approval") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-hourglass-half"></i> Waiting for approval.</div>`;
      actions = `<button type="button" class="pm-btn pm-btn-primary" id="fsActApprove"><i class="fa-solid fa-check"></i> Approve Settlement</button>`;
    } else if (s.status === "approved") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-check"></i> Approved. Ready for payment release.</div>`;
      actions = `<button type="button" class="pm-btn pm-btn-primary" id="fsActRelease"><i class="fa-solid fa-money-bill-wave"></i> Release Settlement</button>`;
    } else if (s.status === "paid") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-lock"></i> This settlement is paid and locked.</div>`;
      actions = `<button type="button" class="pm-btn pm-btn-secondary" id="fsActPrint"><i class="fa-solid fa-print"></i> Print</button>`;
    } else if (s.status === "cancelled") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-ban"></i> This settlement has been cancelled.</div>`;
      actions = `<button type="button" class="pm-btn pm-btn-secondary" id="fsActPrint"><i class="fa-solid fa-print"></i> Print</button>`;
    }

    // Cancel is available for anything except approved/paid (enforced by
    // the backend's SettlementModel::cancel() as well).
    if (!["approved", "paid", "cancelled"].includes(s.status)) {
      actions += `<button type="button" class="pm-btn pm-btn-danger" id="fsActCancel"><i class="fa-solid fa-ban"></i> Cancel</button>`;
    }

    detailActions.innerHTML = `${lockedNote || "<div></div>"}<div class="fs-detail-footer-actions">${actions}</div>`;

    const calcBtn = document.getElementById("fsActCalculate");
    if (calcBtn)
      calcBtn.addEventListener("click", () => openModal(calculateOverlay));

    const submitBtn = document.getElementById("fsActSubmitApproval");
    if (submitBtn)
      submitBtn.addEventListener("click", () =>
        openModal(submitApprovalOverlay),
      );

    const approveBtn = document.getElementById("fsActApprove");
    if (approveBtn)
      approveBtn.addEventListener("click", () => {
        approveConfirmText.textContent = `You are about to approve the final settlement for ${s.employee_name}.`;
        openModal(approveOverlay);
      });

    const releaseBtn = document.getElementById("fsActRelease");
    if (releaseBtn)
      releaseBtn.addEventListener("click", () => {
        releaseForm.reset();
        releaseFormError.style.display = "none";
        openModal(releaseOverlay);
      });

    const printBtn = document.getElementById("fsActPrint");
    if (printBtn)
      printBtn.addEventListener("click", () => printStatement(s.settlement_id));

    const cancelBtn = document.getElementById("fsActCancel");
    if (cancelBtn)
      cancelBtn.addEventListener("click", () => {
        pendingCancelSettlementId = s.settlement_id;
        cancelRemarks.value = "";
        openModal(cancelOverlay);
      });

    const canAddItems = ["draft", "processing"].includes(s.status);
    btnAddEarning.style.display = canAddItems ? "inline-flex" : "none";
    btnAddDeduction.style.display = canAddItems ? "inline-flex" : "none";
  }

  function renderDetailModal() {
    const s = currentDetailSettlement;
    if (!s) return;

    detailSubtitle.textContent = `${s.employee_code} \u2014 ${s.employee_name}`;
    renderWorkflowInto(workflowEl, s.status);

    employeeInfoEl.innerHTML =
      infoRow("Employee Code", esc(s.employee_code)) +
      infoRow("Employee Name", esc(s.employee_name)) +
      infoRow("Employee ID", esc(s.employee_id));

    exitInfoEl.innerHTML =
      infoRow("Exit Type", esc(exitTypeLabel(s.exit_case_type))) +
      infoRow("Exit Case ID", esc(s.exit_case_id ?? "\u2014")) +
      infoRow("Last Working Date", formatDate(s.last_working_date)) +
      infoRow(
        "Exit Settlement ID",
        s.exit_settlement_id
          ? "REQ-" + String(s.exit_settlement_id).padStart(4, "0")
          : "\u2014",
      );

    settlementInfoEl.innerHTML =
      infoRow(
        "Settlement ID",
        "STL-" + String(s.settlement_id).padStart(4, "0"),
      ) +
      infoRow("Settlement Date", formatDate(s.settlement_date)) +
      infoRow("Current Status", badgeHtml(s.status)) +
      infoRow("Created", formatDateTime(s.created_at));

    const earnings = currentDetailItems.filter(
      (i) => i.item_type === "earning",
    );
    const deductions = currentDetailItems.filter(
      (i) => i.item_type === "deduction",
    );

    renderItemsTable(earningsBody, totalEarningsEl, earnings);
    renderItemsTable(deductionsBody, totalDeductionsEl, deductions);

    // Prefer the backend-calculated totals once the settlement has been
    // calculated; otherwise show a live preview computed from the items
    // currently on screen (still not authoritative — Calculate is what
    // actually persists the totals server-side).
    const calculated = [
      "calculated",
      "for_approval",
      "approved",
      "paid",
    ].includes(s.status);
    const totals = calculated
      ? {
          totalEarnings: Number(s.total_earnings || 0),
          totalDeductions: Number(s.total_deductions || 0),
          net: Number(s.net_settlement || 0),
        }
      : computeTotalsFromItems(currentDetailItems);

    calcEarningsEl.textContent = peso(totals.totalEarnings);
    calcDeductionsEl.textContent = peso(totals.totalDeductions);
    calcNetEl.textContent = peso(totals.net);

    // Payment information
    if (s.status === "paid") {
      paymentSection.style.display = "block";
      paymentInfoEl.innerHTML =
        infoRow("Payment Method", esc(s.payment_method ?? "\u2014")) +
        infoRow("Payment Reference", esc(s.payment_reference ?? "\u2014")) +
        infoRow("Paid Date", formatDateTime(s.paid_at));
    } else {
      paymentSection.style.display = "none";
      paymentInfoEl.innerHTML = "";
    }

    // Approval / cancellation activity
    let activityHtml = "";
    if (s.approved_at) {
      activityHtml += infoRow("Approved At", formatDateTime(s.approved_at));
    }
    if (s.status === "cancelled" && s.remarks) {
      activityHtml += infoRow("Cancellation Remarks", esc(s.remarks));
    }
    if (activityHtml) {
      activitySection.style.display = "block";
      activityInfoEl.innerHTML = activityHtml;
    } else {
      activitySection.style.display = "none";
      activityInfoEl.innerHTML = "";
    }

    renderDetailActions(s);
  }

  earningsBody.addEventListener("click", (e) =>
    handleItemRowClick(e, "earning"),
  );
  deductionsBody.addEventListener("click", (e) =>
    handleItemRowClick(e, "deduction"),
  );

  function handleItemRowClick(e, itemType) {
    const editBtn = e.target.closest("[data-fs-edit-item]");
    if (editBtn) {
      openEditItemModal(
        parseInt(editBtn.getAttribute("data-fs-edit-item"), 10),
      );
      return;
    }
    const deleteBtn = e.target.closest("[data-fs-delete-item]");
    if (deleteBtn) {
      pendingDeleteItemId = parseInt(
        deleteBtn.getAttribute("data-fs-delete-item"),
        10,
      );
      openModal(deleteItemOverlay);
    }
  }

  // ---- Add / Edit item modal ----------------------------------------------

  function openAddItemModal(itemType) {
    pendingItemAction = "add";
    itemModalTitle.textContent =
      itemType === "deduction" ? "Add Deduction" : "Add Earning";
    itemForm.reset();
    itemIdInput.value = "";
    itemSettlementIdInput.value = currentDetailSettlementId;
    itemTypeSelect.value = itemType;
    itemFormError.style.display = "none";
    openModal(itemOverlay);
  }

  function openEditItemModal(itemId) {
    const item = currentDetailItems.find((i) => i.item_id === itemId);
    if (!item) return;

    pendingItemAction = "edit";
    itemModalTitle.textContent = "Edit Settlement Item";
    itemIdInput.value = item.item_id;
    itemSettlementIdInput.value = item.settlement_id;
    itemTypeSelect.value = item.item_type;
    itemCategoryInput.value = item.item_category || "";
    itemDescriptionInput.value = item.description || "";
    itemAmountInput.value = item.amount;
    itemCodeInput.value = item.item_code || "";
    itemFormError.style.display = "none";
    openModal(itemOverlay);
  }

  btnAddEarning.addEventListener("click", () => openAddItemModal("earning"));
  btnAddDeduction.addEventListener("click", () =>
    openAddItemModal("deduction"),
  );

  itemForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const category = itemCategoryInput.value.trim();
    const description = itemDescriptionInput.value.trim();
    const amount = parseFloat(itemAmountInput.value);

    if (!category) {
      itemFormError.textContent = "Item category is required.";
      itemFormError.style.display = "block";
      return;
    }
    if (!description) {
      itemFormError.textContent = "Item description is required.";
      itemFormError.style.display = "block";
      return;
    }
    if (!amount || amount <= 0) {
      itemFormError.textContent = "Amount must be greater than zero.";
      itemFormError.style.display = "block";
      return;
    }

    const fd = new FormData();
    fd.append("settlement_id", itemSettlementIdInput.value);
    fd.append("item_type", itemTypeSelect.value);
    fd.append("item_category", category);
    fd.append("description", description);
    fd.append("amount", amount);
    if (itemCodeInput.value.trim()) {
      fd.append("item_code", itemCodeInput.value.trim());
    }
    if (pendingItemAction === "edit") {
      fd.append("item_id", itemIdInput.value);
    }

    const action = pendingItemAction === "edit" ? "update_item" : "add_item";
    itemSubmitBtn.disabled = true;

    try {
      const res = await fetch(`${ENDPOINT}?action=${action}`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();

      if (data.success) {
        closeModal(itemOverlay);
        showAlert(data.message || "Settlement item saved.", "success");
        await openSettlementDetail(currentDetailSettlementId);
      } else {
        itemFormError.textContent =
          data.message || "Failed to save settlement item.";
        itemFormError.style.display = "block";
      }
    } catch (err) {
      itemFormError.textContent = "Something went wrong. Please try again.";
      itemFormError.style.display = "block";
    } finally {
      itemSubmitBtn.disabled = false;
    }
  });

  btnConfirmDeleteItem.addEventListener("click", async () => {
    if (!pendingDeleteItemId) return;
    btnConfirmDeleteItem.disabled = true;

    const fd = new FormData();
    fd.append("item_id", pendingDeleteItemId);

    try {
      const res = await fetch(`${ENDPOINT}?action=delete_item`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(deleteItemOverlay);

      if (data.success) {
        showAlert(data.message || "Settlement item removed.", "success");
        await openSettlementDetail(currentDetailSettlementId);
      } else {
        showAlert(data.message || "Failed to remove settlement item.", "error");
      }
    } catch (err) {
      closeModal(deleteItemOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmDeleteItem.disabled = false;
      pendingDeleteItemId = null;
    }
  });

  // ---- Calculate ------------------------------------------------------

  btnConfirmCalculate.addEventListener("click", async () => {
    if (!currentDetailSettlementId) return;
    btnConfirmCalculate.disabled = true;

    const fd = new FormData();
    fd.append("settlement_id", currentDetailSettlementId);

    try {
      const res = await fetch(`${ENDPOINT}?action=calculate`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(calculateOverlay);

      if (data.success) {
        showAlert(data.message || "Settlement calculated.", "success");
        await openSettlementDetail(currentDetailSettlementId);
        loadProcessing();
        loadSummary();
      } else {
        showAlert(data.message || "Failed to calculate settlement.", "error");
      }
    } catch (err) {
      closeModal(calculateOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmCalculate.disabled = false;
    }
  });

  // ---- Submit for approval ----------------------------------------------

  btnConfirmSubmitApproval.addEventListener("click", async () => {
    if (!currentDetailSettlementId) return;
    btnConfirmSubmitApproval.disabled = true;

    const fd = new FormData();
    fd.append("settlement_id", currentDetailSettlementId);

    try {
      const res = await fetch(`${ENDPOINT}?action=submit_for_approval`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(submitApprovalOverlay);

      if (data.success) {
        showAlert(data.message || "Submitted for approval.", "success");
        await openSettlementDetail(currentDetailSettlementId);
        loadProcessing();
        loadApproval();
        loadSummary();
      } else {
        showAlert(data.message || "Failed to submit for approval.", "error");
      }
    } catch (err) {
      closeModal(submitApprovalOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmSubmitApproval.disabled = false;
    }
  });

  // ---- Approve -------------------------------------------------------

  btnConfirmApprove.addEventListener("click", async () => {
    if (!currentDetailSettlementId) return;
    btnConfirmApprove.disabled = true;

    const fd = new FormData();
    fd.append("settlement_id", currentDetailSettlementId);

    try {
      const res = await fetch(`${ENDPOINT}?action=approve`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(approveOverlay);

      if (data.success) {
        showAlert(data.message || "Settlement approved.", "success");
        await openSettlementDetail(currentDetailSettlementId);
        loadApproval();
        loadSummary();
      } else {
        showAlert(data.message || "Failed to approve settlement.", "error");
      }
    } catch (err) {
      closeModal(approveOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmApprove.disabled = false;
    }
  });

  // ---- Release ---------------------------------------------------------

  releaseForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!currentDetailSettlementId) return;

    const paymentMethod = releasePaymentMethod.value;
    if (!paymentMethod) {
      releaseFormError.textContent = "Please select a payment method.";
      releaseFormError.style.display = "block";
      return;
    }

    const fd = new FormData();
    fd.append("settlement_id", currentDetailSettlementId);
    fd.append("payment_method", paymentMethod);
    if (releasePaymentReference.value.trim()) {
      fd.append("payment_reference", releasePaymentReference.value.trim());
    }

    try {
      const res = await fetch(`${ENDPOINT}?action=release`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();

      if (data.success) {
        closeModal(releaseOverlay);
        showAlert(data.message || "Settlement released.", "success");
        await openSettlementDetail(currentDetailSettlementId);
        loadApproval();
        loadSummary();
      } else {
        releaseFormError.textContent =
          data.message || "Failed to release settlement.";
        releaseFormError.style.display = "block";
      }
    } catch (err) {
      releaseFormError.textContent = "Something went wrong. Please try again.";
      releaseFormError.style.display = "block";
    }
  });

  // ---- Cancel ---------------------------------------------------------

  btnConfirmCancel.addEventListener("click", async () => {
    if (!pendingCancelSettlementId) return;
    btnConfirmCancel.disabled = true;

    const fd = new FormData();
    fd.append("settlement_id", pendingCancelSettlementId);
    if (cancelRemarks.value.trim()) {
      fd.append("remarks", cancelRemarks.value.trim());
    }

    try {
      const res = await fetch(`${ENDPOINT}?action=cancel`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();
      closeModal(cancelOverlay);

      if (data.success) {
        showAlert(data.message || "Settlement cancelled.", "success");
        await openSettlementDetail(pendingCancelSettlementId);
        loadRequests();
        loadProcessing();
        loadApproval();
        loadSummary();
      } else {
        showAlert(data.message || "Failed to cancel settlement.", "error");
      }
    } catch (err) {
      closeModal(cancelOverlay);
      showAlert("Something went wrong. Please try again.", "error");
    } finally {
      btnConfirmCancel.disabled = false;
      pendingCancelSettlementId = null;
    }
  });

  // ==========================================================================
  // PRINT STATEMENT
  // ==========================================================================

  function printSectionRows(items) {
    return items
      .map(
        (item) => `
      <tr>
        <td>${esc(item.item_category)}</td>
        <td>${esc(item.description)}</td>
        <td>${peso(item.amount)}</td>
      </tr>`,
      )
      .join("");
  }

  function buildPrintDocument(s, items) {
    const earnings = items.filter((i) => i.item_type === "earning");
    const deductions = items.filter((i) => i.item_type === "deduction");
    const now = new Date().toLocaleString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });

    let paymentBlock = "";
    if (s.status === "paid") {
      paymentBlock = `
        <div class="fs-doc-section">
          <div class="fs-doc-section-title">Payment Information</div>
          <div class="fs-doc-info-grid">
            <div><span>Payment Method</span><strong>${esc(s.payment_method ?? "\u2014")}</strong></div>
            <div><span>Payment Reference</span><strong>${esc(s.payment_reference ?? "\u2014")}</strong></div>
            <div><span>Paid Date</span><strong>${formatDateTime(s.paid_at)}</strong></div>
          </div>
        </div>`;
    }

    return `
      <div class="fs-doc-header">
        <img src="assets/bcp-logo.png" alt="Company logo">
        <div class="fs-doc-header-text">
          <h3>Bestlink College of the Philippines</h3>
          <span>Human Resource / Payroll Department</span>
          <span>Final Settlement Statement</span>
        </div>
      </div>

      <div class="fs-doc-title-row">
        <h4>Final Settlement Statement</h4>
        <div class="fs-doc-generated">Generated: ${now}</div>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Employee Information</div>
        <div class="fs-doc-info-grid">
          <div><span>Employee</span><strong>${esc(s.employee_name)}</strong></div>
          <div><span>Employee Code</span><strong>${esc(s.employee_code)}</strong></div>
        </div>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Exit Information</div>
        <div class="fs-doc-info-grid">
          <div><span>Exit Type</span><strong>${esc(exitTypeLabel(s.exit_case_type))}</strong></div>
          <div><span>Last Working Date</span><strong>${formatDate(s.last_working_date)}</strong></div>
          <div><span>Settlement Date</span><strong>${formatDate(s.settlement_date)}</strong></div>
        </div>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Earnings</div>
        <table class="fs-doc-table">
          <thead><tr><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
          <tbody>
            ${printSectionRows(earnings)}
            <tr class="fs-doc-total-row"><td colspan="2">Total Earnings</td><td>${peso(s.total_earnings)}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Deductions</div>
        <table class="fs-doc-table">
          <thead><tr><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
          <tbody>
            ${printSectionRows(deductions)}
            <tr class="fs-doc-total-row"><td colspan="2">Total Deductions</td><td>${peso(s.total_deductions)}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-net-card">
          <span>Net Final Settlement</span>
          <strong>${peso(s.net_settlement)}</strong>
        </div>
      </div>

      ${paymentBlock}

      <div class="fs-doc-signatures">
        <div class="fs-doc-signature-line">
          <span>Prepared By:</span>
          <div class="fs-doc-signature-space"></div>
        </div>
        <div class="fs-doc-signature-line">
          <span>Approved By:</span>
          <div class="fs-doc-signature-space"></div>
        </div>
      </div>

      <div class="fs-doc-ack">
        <p>Employee Acknowledgment: I acknowledge receipt of the above final settlement.</p>
        <div class="fs-doc-ack-line">
          <span>Employee Signature:</span>
          <div class="fs-doc-ack-space"></div>
        </div>
        <div class="fs-doc-ack-line">
          <span>Date:</span>
          <div class="fs-doc-ack-space"></div>
        </div>
      </div>
    `;
  }

  async function printStatement(settlementId) {
    try {
      const data = await apiRequest("get_settlement", {
        params: { id: settlementId },
      });
      if (!data.success || !data.data) {
        showAlert("Failed to load settlement for printing.", "error");
        return;
      }
      printSheet.innerHTML = buildPrintDocument(data.data, data.items || []);
      window.print();
    } catch (err) {
      showAlert("Failed to load settlement for printing.", "error");
    }
  }

  // ==========================================================================
  // INIT
  // ==========================================================================

  loadRequests();
  loadSummary();
}

window.addEventListener("page:loaded", initFinalSettlement);
