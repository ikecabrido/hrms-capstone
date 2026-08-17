/**
 * Final Settlement — FRONT-END MOCKUP ONLY
 * ---------------------------------------------------------------------
 * No backend exists yet for this module. Every record below is
 * hardcoded mock data. All filtering, calculation, and workflow-status
 * changes (Submit for Review / Approve / Release / Add Adjustment) run
 * entirely in the browser and are lost on refresh.
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

  // ---- Mock data ------------------------------------------------------

  let settlements = [
    {
      id: "STL-0001",
      employeeCode: "EMP-000035",
      employeeName: "Juan Dela Cruz",
      department: "Information Technology",
      position: "Software Developer",
      employmentStatus: "Resigned",
      monthlySalary: 30000,
      exitType: "Resignation",
      exitReason: "Personal Reasons",
      exitDate: "2026-08-15",
      lastWorkingDay: "2026-08-15",
      exitStatus: "Approved",
      status: "for_review",
      earnings: [
        { label: "Unpaid Salary", amount: 20000 },
        { label: "Pro-rated Salary", amount: 8500 },
        { label: "Unused Leave Conversion", amount: 5000 },
        { label: "Allowances", amount: 2000 },
        { label: "Other Earnings", amount: 1500 },
      ],
      deductions: [
        { label: "SSS", amount: 1125 },
        { label: "PhilHealth", amount: 500 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 800 },
        { label: "Employee Loan", amount: 3000 },
        { label: "Other Deductions", amount: 500 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0002",
      employeeCode: "EMP-000042",
      employeeName: "Maria Santos",
      department: "Human Resources",
      position: "HR Specialist",
      employmentStatus: "Terminated",
      monthlySalary: 45000,
      exitType: "Termination",
      exitReason: "Policy Violation",
      exitDate: "2026-08-12",
      lastWorkingDay: "2026-08-12",
      exitStatus: "Approved",
      status: "approved",
      earnings: [
        { label: "Unpaid Salary", amount: 20000 },
        { label: "Pro-rated Salary", amount: 15000 },
        { label: "Unused Leave Conversion", amount: 6000 },
        { label: "Allowances", amount: 2500 },
        { label: "Other Earnings", amount: 1500 },
      ],
      deductions: [
        { label: "SSS", amount: 1350 },
        { label: "PhilHealth", amount: 600 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 350 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0003",
      employeeCode: "EMP-000051",
      employeeName: "Pedro Reyes",
      department: "Sales",
      position: "Sales Associate",
      employmentStatus: "Resigned",
      monthlySalary: 28000,
      exitType: "Resignation",
      exitReason: "Personal Reasons",
      exitDate: "2026-07-30",
      lastWorkingDay: "2026-07-30",
      exitStatus: "Approved",
      status: "released",
      earnings: [
        { label: "Unpaid Salary", amount: 15000 },
        { label: "Pro-rated Salary", amount: 12000 },
        { label: "Unused Leave Conversion", amount: 4000 },
        { label: "Allowances", amount: 1500 },
        { label: "Other Earnings", amount: 700 },
      ],
      deductions: [
        { label: "SSS", amount: 1000 },
        { label: "PhilHealth", amount: 450 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 2500 },
        { label: "Other Deductions", amount: 850 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0004",
      employeeCode: "EMP-000063",
      employeeName: "Ana Garcia",
      department: "Finance",
      position: "Finance Manager",
      employmentStatus: "Retired",
      monthlySalary: 45000,
      exitType: "Retirement",
      exitReason: "Optional Retirement",
      exitDate: "2026-07-20",
      lastWorkingDay: "2026-07-20",
      exitStatus: "Approved",
      status: "draft",
      earnings: [
        { label: "Unpaid Salary", amount: 22000 },
        { label: "Pro-rated Salary", amount: 18000 },
        { label: "Unused Leave Conversion", amount: 12000 },
        { label: "Allowances", amount: 5000 },
        { label: "Other Earnings", amount: 3500 },
      ],
      deductions: [
        { label: "SSS", amount: 1500 },
        { label: "PhilHealth", amount: 700 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 2000 },
        { label: "Other Deductions", amount: 350 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0005",
      employeeCode: "EMP-000071",
      employeeName: "Carlos Mendoza",
      department: "Operations",
      position: "Operations Staff",
      employmentStatus: "End of Contract",
      monthlySalary: 24000,
      exitType: "End of Contract",
      exitReason: "Contract Expired",
      exitDate: "2026-07-15",
      lastWorkingDay: "2026-07-15",
      exitStatus: "Approved",
      status: "released",
      earnings: [
        { label: "Unpaid Salary", amount: 12000 },
        { label: "Pro-rated Salary", amount: 10000 },
        { label: "Unused Leave Conversion", amount: 3000 },
        { label: "Allowances", amount: 1200 },
        { label: "Other Earnings", amount: 800 },
      ],
      deductions: [
        { label: "SSS", amount: 900 },
        { label: "PhilHealth", amount: 400 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 1000 },
        { label: "Other Deductions", amount: 200 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0006",
      employeeCode: "EMP-000082",
      employeeName: "Liza Fernandez",
      department: "Marketing",
      position: "Marketing Associate",
      employmentStatus: "Resigned",
      monthlySalary: 32000,
      exitType: "Resignation",
      exitReason: "Career Growth",
      exitDate: "2026-07-10",
      lastWorkingDay: "2026-07-10",
      exitStatus: "Approved",
      status: "released",
      earnings: [
        { label: "Unpaid Salary", amount: 16000 },
        { label: "Pro-rated Salary", amount: 13000 },
        { label: "Unused Leave Conversion", amount: 4500 },
        { label: "Allowances", amount: 1800 },
        { label: "Other Earnings", amount: 700 },
      ],
      deductions: [
        { label: "SSS", amount: 1050 },
        { label: "PhilHealth", amount: 480 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 900 },
        { label: "Other Deductions", amount: 370 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0007",
      employeeCode: "EMP-000090",
      employeeName: "Michael Torres",
      department: "Information Technology",
      position: "IT Support",
      employmentStatus: "Terminated",
      monthlySalary: 26000,
      exitType: "Termination",
      exitReason: "Performance Issues",
      exitDate: "2026-07-05",
      lastWorkingDay: "2026-07-05",
      exitStatus: "Approved",
      status: "cancelled",
      earnings: [{ label: "Unpaid Salary", amount: 10000 }],
      deductions: [],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0008",
      employeeCode: "EMP-000104",
      employeeName: "Grace Villanueva",
      department: "Compliance",
      position: "Compliance Officer",
      employmentStatus: "Retired",
      monthlySalary: 38000,
      exitType: "Retirement",
      exitReason: "Optional Retirement",
      exitDate: "2026-06-28",
      lastWorkingDay: "2026-06-28",
      exitStatus: "Approved",
      status: "released",
      earnings: [
        { label: "Unpaid Salary", amount: 19000 },
        { label: "Pro-rated Salary", amount: 15000 },
        { label: "Unused Leave Conversion", amount: 8000 },
        { label: "Allowances", amount: 2500 },
        { label: "Other Earnings", amount: 1000 },
      ],
      deductions: [
        { label: "SSS", amount: 1200 },
        { label: "PhilHealth", amount: 550 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 1800 },
        { label: "Other Deductions", amount: 250 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
    {
      id: "STL-0009",
      employeeCode: "EMP-000112",
      employeeName: "Ramon Aquino",
      department: "Warehouse",
      position: "Warehouse Supervisor",
      employmentStatus: "Resigned",
      monthlySalary: 30000,
      exitType: "Resignation",
      exitReason: "Personal Reasons",
      exitDate: "2026-06-20",
      lastWorkingDay: "2026-06-20",
      exitStatus: "Approved",
      status: "draft",
      earnings: [
        { label: "Unpaid Salary", amount: 15000 },
        { label: "Pro-rated Salary", amount: 12000 },
        { label: "Unused Leave Conversion", amount: 4000 },
        { label: "Allowances", amount: 1200 },
        { label: "Other Earnings", amount: 500 },
      ],
      deductions: [
        { label: "SSS", amount: 1050 },
        { label: "PhilHealth", amount: 450 },
        { label: "Pag-IBIG", amount: 200 },
        { label: "Withholding Tax", amount: 800 },
        { label: "Other Deductions", amount: 200 },
      ],
      additionalEarnings: [],
      additionalDeductions: [],
    },
  ];

  // Employees with an approved exit record who don't have a settlement yet.
  const pendingExits = [
    {
      employeeCode: "EMP-000120",
      employeeName: "Sarah Lim",
      department: "Customer Support",
      position: "Support Agent",
      employmentStatus: "Resigned",
      monthlySalary: 35000,
      exitType: "Resignation",
      exitReason: "Personal Reasons",
      exitDate: "2026-08-20",
      lastWorkingDay: "2026-08-20",
      unpaidEstimate: 23000,
      leaveEstimate: 5800,
    },
    {
      employeeCode: "EMP-000133",
      employeeName: "Kevin Ramos",
      department: "Logistics",
      position: "Logistics Manager",
      employmentStatus: "Retired",
      monthlySalary: 42000,
      exitType: "Retirement",
      exitReason: "Optional Retirement",
      exitDate: "2026-08-18",
      lastWorkingDay: "2026-08-18",
      unpaidEstimate: 25200,
      leaveEstimate: 9800,
    },
    {
      employeeCode: "EMP-000141",
      employeeName: "Diana Cruz",
      department: "Design",
      position: "UI/UX Designer",
      employmentStatus: "End of Contract",
      monthlySalary: 27000,
      exitType: "End of Contract",
      exitReason: "Contract Expired",
      exitDate: "2026-08-10",
      lastWorkingDay: "2026-08-10",
      unpaidEstimate: 18000,
      leaveEstimate: 4500,
    },
  ];

  let nextStlNumber = settlements.length + 1;
  let currentDetailId = null; // settlement currently open in the detail modal
  let selectedPendingExit = null; // employee currently selected in the Create modal

  // ---- Element references ----------------------------------------------

  const alertBox = document.getElementById("fsAlert");

  const btnCreate = document.getElementById("fsBtnCreate");
  const btnCreateEmpty = document.getElementById("fsBtnCreateEmpty");

  const searchInput = document.getElementById("fsSearchInput");
  const statusFilter = document.getElementById("fsStatusFilter");
  const exitTypeFilter = document.getElementById("fsExitTypeFilter");
  const btnSearch = document.getElementById("fsBtnSearch");
  const btnClear = document.getElementById("fsBtnClear");

  const tableBody = document.getElementById("fsTableBody");
  const emptyState = document.getElementById("fsEmptyState");

  const pendingCountEl = document.getElementById("fsPendingCount");
  const forReviewCountEl = document.getElementById("fsForReviewCount");
  const approvedCountEl = document.getElementById("fsApprovedCount");
  const releasedCountEl = document.getElementById("fsReleasedCount");
  const totalFinalPayEl = document.getElementById("fsTotalFinalPay");

  const createOverlay = document.getElementById("fsCreateModalOverlay");
  const createForm = document.getElementById("fsCreateForm");
  const createSearchInput = document.getElementById(
    "fsCreateEmployeeSearchInput",
  );
  const createEmployeeId = document.getElementById("fsCreateEmployeeId");
  const createOptions = document.getElementById("fsCreateEmployeeOptions");
  const createDetails = document.getElementById("fsCreateDetails");
  const createExitType = document.getElementById("fsCreateExitType");
  const createExitDate = document.getElementById("fsCreateExitDate");
  const createLastWorkingDay = document.getElementById(
    "fsCreateLastWorkingDay",
  );
  const previewMonthlySalary = document.getElementById(
    "fsPreviewMonthlySalary",
  );
  const previewUnpaidSalary = document.getElementById("fsPreviewUnpaidSalary");
  const previewLeaveConversion = document.getElementById(
    "fsPreviewLeaveConversion",
  );
  const createFormError = document.getElementById("fsCreateFormError");

  const detailOverlay = document.getElementById("fsDetailModalOverlay");
  const detailSubtitle = document.getElementById("fsDetailSubtitle");
  const workflowEl = document.getElementById("fsWorkflow");
  const employeeInfoEl = document.getElementById("fsEmployeeInfo");
  const exitInfoEl = document.getElementById("fsExitInfo");
  const earningsBody = document.getElementById("fsEarningsBody");
  const totalEarningsEl = document.getElementById("fsTotalEarnings");
  const deductionsBody = document.getElementById("fsDeductionsBody");
  const totalDeductionsEl = document.getElementById("fsTotalDeductions");
  const calcEarningsEl = document.getElementById("fsCalcEarnings");
  const calcDeductionsEl = document.getElementById("fsCalcDeductions");
  const calcNetEl = document.getElementById("fsCalcNet");
  const adjEarningsBody = document.getElementById("fsAdjEarningsBody");
  const adjEarningsEmpty = document.getElementById("fsAdjEarningsEmpty");
  const adjDeductionsBody = document.getElementById("fsAdjDeductionsBody");
  const adjDeductionsEmpty = document.getElementById("fsAdjDeductionsEmpty");
  const detailActions = document.getElementById("fsDetailActions");
  const btnAddAdjustment = document.getElementById("fsBtnAddAdjustment");

  const adjustOverlay = document.getElementById("fsAdjustModalOverlay");
  const adjustForm = document.getElementById("fsAdjustForm");
  const adjustType = document.getElementById("fsAdjustType");
  const adjustDescription = document.getElementById("fsAdjustDescription");
  const adjustAmount = document.getElementById("fsAdjustAmount");
  const adjustFormError = document.getElementById("fsAdjustFormError");

  const approveOverlay = document.getElementById("fsApproveModalOverlay");
  const approveConfirmText = document.getElementById("fsApproveConfirmText");
  const btnConfirmApprove = document.getElementById("fsBtnConfirmApprove");

  const releaseOverlay = document.getElementById("fsReleaseModalOverlay");
  const btnConfirmRelease = document.getElementById("fsBtnConfirmRelease");

  const printSheet = document.getElementById("fsPrintSheet");

  const STATUS_LABELS = {
    draft: "Draft",
    for_review: "For Review",
    approved: "Approved",
    released: "Released",
    cancelled: "Cancelled",
  };

  const WORKFLOW_STEPS = [
    { key: "draft", label: "Draft" },
    { key: "for_review", label: "For Review" },
    { key: "approved", label: "Approved" },
    { key: "released", label: "Released" },
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

  function formatDate(isoStr) {
    if (!isoStr) return "\u2014";
    const d = new Date(isoStr + "T00:00:00");
    if (isNaN(d.getTime())) return isoStr;
    return d.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function computeTotals(s) {
    const earnings = [...s.earnings, ...s.additionalEarnings];
    const deductions = [...s.deductions, ...s.additionalDeductions];
    const totalEarnings = earnings.reduce((sum, e) => sum + e.amount, 0);
    const totalDeductions = deductions.reduce((sum, d) => sum + d.amount, 0);
    return {
      totalEarnings,
      totalDeductions,
      net: totalEarnings - totalDeductions,
    };
  }

  function findSettlement(id) {
    return settlements.find((s) => s.id === id) || null;
  }

  function showAlert(message, type) {
    alertBox.className = "pm-alert pm-alert-" + (type || "success");
    alertBox.textContent = message;
    alertBox.style.display = "block";
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(() => {
      alertBox.style.display = "none";
    }, 3200);
  }

  function openModal(overlay) {
    // Portal the overlay to <body> so it escapes any stacking context
    // created by ancestors inside .container (this is why z-index: 9999
    // on the overlay alone wasn't enough to clear the app header).
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
    overlay.style.display = "none";
    // Move it back to its original spot in the page markup so it isn't
    // left dangling on <body> if this page gets swapped out.
    if (overlay._fsHomeMarker && overlay._fsHomeMarker.parentNode) {
      overlay._fsHomeMarker.parentNode.insertBefore(
        overlay,
        overlay._fsHomeMarker,
      );
    }
  }

  // ---- Summary cards --------------------------------------------------

  function renderSummaryCards() {
    const pending = settlements.filter((s) => s.status === "draft").length;
    const forReview = settlements.filter(
      (s) => s.status === "for_review",
    ).length;
    const approved = settlements.filter((s) => s.status === "approved").length;
    const released = settlements.filter((s) => s.status === "released").length;
    const totalFinalPay = settlements
      .filter((s) => s.status !== "cancelled")
      .reduce((sum, s) => sum + computeTotals(s).net, 0);

    pendingCountEl.textContent = pending;
    forReviewCountEl.textContent = forReview;
    approvedCountEl.textContent = approved;
    releasedCountEl.textContent = released;
    totalFinalPayEl.textContent = peso(totalFinalPay);
  }

  // ---- List / filtering -------------------------------------------------

  function getFilteredSettlements() {
    const term = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value;
    const exitType = exitTypeFilter.value;

    return settlements.filter((s) => {
      const matchesTerm =
        !term ||
        s.employeeName.toLowerCase().includes(term) ||
        s.employeeCode.toLowerCase().includes(term);
      const matchesStatus = !status || s.status === status;
      const matchesExitType = !exitType || s.exitType === exitType;
      return matchesTerm && matchesStatus && matchesExitType;
    });
  }

  function badgeHtml(status) {
    return `<span class="fs-status-badge fs-status-${status}">${STATUS_LABELS[status]}</span>`;
  }

  function rowActionsHtml(s) {
    if (s.status === "draft" || s.status === "for_review") {
      return `
        <div class="pm-row-actions">
          <button type="button" class="pm-icon-btn pm-icon-btn-primary" title="View" data-fs-view="${s.id}"><i class="fa-solid fa-eye"></i></button>
          <button type="button" class="pm-icon-btn" title="Edit" data-fs-edit="${s.id}"><i class="fa-solid fa-pen"></i></button>
        </div>`;
    }
    if (s.status === "approved" || s.status === "released") {
      return `
        <div class="pm-row-actions">
          <button type="button" class="pm-icon-btn pm-icon-btn-primary" title="View" data-fs-view="${s.id}"><i class="fa-solid fa-eye"></i></button>
          <button type="button" class="pm-icon-btn" title="Print" data-fs-print="${s.id}"><i class="fa-solid fa-print"></i></button>
        </div>`;
    }
    return `
      <div class="pm-row-actions">
        <button type="button" class="pm-icon-btn pm-icon-btn-primary" title="View" data-fs-view="${s.id}"><i class="fa-solid fa-eye"></i></button>
      </div>`;
  }

  function renderTable() {
    const rows = getFilteredSettlements();

    if (!rows.length) {
      tableBody.innerHTML = "";
      emptyState.style.display = "block";
      document.getElementById("fsEmptyStateText").textContent =
        settlements.length
          ? "No settlement records match your filters."
          : "No settlement records found.";
      return;
    }

    emptyState.style.display = "none";

    tableBody.innerHTML = rows
      .map((s) => {
        const net = computeTotals(s).net;
        return `
        <tr>
          <td>${esc(s.employeeName)}</td>
          <td class="fs-employee-code">${esc(s.employeeCode)}</td>
          <td>${esc(s.exitType)}</td>
          <td>${formatDate(s.exitDate)}</td>
          <td class="fs-amount-cell">${peso(net)}</td>
          <td>${badgeHtml(s.status)}</td>
          <td class="pm-actions-col">${rowActionsHtml(s)}</td>
        </tr>`;
      })
      .join("");
  }

  function renderAll() {
    renderSummaryCards();
    renderTable();
  }

  // ---- Filter bar wiring --------------------------------------------------

  searchInput.addEventListener("input", renderTable);
  statusFilter.addEventListener("change", renderTable);
  exitTypeFilter.addEventListener("change", renderTable);
  btnSearch.addEventListener("click", renderTable);
  btnClear.addEventListener("click", () => {
    searchInput.value = "";
    statusFilter.value = "";
    exitTypeFilter.value = "";
    renderTable();
  });

  // ---- Row action delegation --------------------------------------------

  tableBody.addEventListener("click", (e) => {
    const viewBtn = e.target.closest("[data-fs-view]");
    if (viewBtn) {
      openDetail(viewBtn.getAttribute("data-fs-view"));
      return;
    }
    const editBtn = e.target.closest("[data-fs-edit]");
    if (editBtn) {
      openDetail(editBtn.getAttribute("data-fs-edit"));
      return;
    }
    const printBtn = e.target.closest("[data-fs-print]");
    if (printBtn) {
      printStatement(printBtn.getAttribute("data-fs-print"));
    }
  });

  // ---- Create Settlement modal --------------------------------------------

  function resetCreateModal() {
    createSearchInput.value = "";
    createEmployeeId.value = "";
    createOptions.classList.remove("fs-combobox-open");
    createOptions.innerHTML = "";
    createDetails.style.display = "none";
    createFormError.style.display = "none";
    selectedPendingExit = null;
  }

  function renderPendingExitOptions(term) {
    const t = (term || "").trim().toLowerCase();
    const matches = pendingExits.filter(
      (p) =>
        !settlements.some((s) => s.employeeCode === p.employeeCode) &&
        (!t ||
          p.employeeName.toLowerCase().includes(t) ||
          p.employeeCode.toLowerCase().includes(t)),
    );

    if (!matches.length) {
      createOptions.innerHTML = `<div class="fs-combobox-empty">No approved exit records found.</div>`;
    } else {
      createOptions.innerHTML = matches
        .map(
          (p) => `
        <div class="fs-combobox-option" data-employee-code="${esc(p.employeeCode)}">
          <span class="fs-combobox-option-label">${esc(p.employeeCode)} \u2014 ${esc(p.employeeName)}</span>
          <span class="fs-combobox-option-sub">${esc(p.exitType)} \u00b7 Exit: ${formatDate(p.exitDate)}</span>
        </div>`,
        )
        .join("");
    }
    createOptions.classList.add("fs-combobox-open");
  }

  createSearchInput.addEventListener("focus", () =>
    renderPendingExitOptions(createSearchInput.value),
  );
  createSearchInput.addEventListener("input", () => {
    selectedPendingExit = null;
    createEmployeeId.value = "";
    createDetails.style.display = "none";
    renderPendingExitOptions(createSearchInput.value);
  });

  createOptions.addEventListener("click", (e) => {
    const opt = e.target.closest("[data-employee-code]");
    if (!opt) return;
    const code = opt.getAttribute("data-employee-code");
    const emp = pendingExits.find((p) => p.employeeCode === code);
    if (!emp) return;

    selectedPendingExit = emp;
    createEmployeeId.value = emp.employeeCode;
    createSearchInput.value = `${emp.employeeCode} \u2014 ${emp.employeeName}`;
    createOptions.classList.remove("fs-combobox-open");

    createExitType.textContent = emp.exitType;
    createExitDate.textContent = formatDate(emp.exitDate);
    createLastWorkingDay.textContent = formatDate(emp.lastWorkingDay);
    previewMonthlySalary.textContent = peso(emp.monthlySalary);
    previewUnpaidSalary.textContent = peso(emp.unpaidEstimate);
    previewLeaveConversion.textContent = peso(emp.leaveEstimate);
    createDetails.style.display = "flex";
    createFormError.style.display = "none";
  });

  document.addEventListener("click", (e) => {
    if (!createOptions.contains(e.target) && e.target !== createSearchInput) {
      createOptions.classList.remove("fs-combobox-open");
    }
  });

  btnCreate.addEventListener("click", () => {
    resetCreateModal();
    openModal(createOverlay);
  });
  if (btnCreateEmpty) {
    btnCreateEmpty.addEventListener("click", () => {
      resetCreateModal();
      openModal(createOverlay);
    });
  }

  createForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!selectedPendingExit) {
      createFormError.textContent =
        "Please select an employee with an approved exit record.";
      createFormError.style.display = "block";
      return;
    }

    const emp = selectedPendingExit;
    const id = "STL-" + String(nextStlNumber++).padStart(4, "0");

    const newSettlement = {
      id,
      employeeCode: emp.employeeCode,
      employeeName: emp.employeeName,
      department: emp.department,
      position: emp.position,
      employmentStatus: emp.employmentStatus,
      monthlySalary: emp.monthlySalary,
      exitType: emp.exitType,
      exitReason: emp.exitReason,
      exitDate: emp.exitDate,
      lastWorkingDay: emp.lastWorkingDay,
      exitStatus: "Approved",
      status: "draft",
      earnings: [
        { label: "Unpaid Salary", amount: emp.unpaidEstimate },
        { label: "Unused Leave Conversion", amount: emp.leaveEstimate },
      ],
      deductions: [],
      additionalEarnings: [],
      additionalDeductions: [],
    };

    settlements.unshift(newSettlement);
    closeModal(createOverlay);
    renderAll();
    showAlert(`Draft settlement created for ${emp.employeeName}.`, "success");
    openDetail(id);
  });

  // ---- Detail modal ---------------------------------------------------

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

    const currentIdx = WORKFLOW_STEPS.findIndex((s) => s.key === status);

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

  function infoRow(label, value) {
    return `
      <div class="fs-info-row">
        <span class="fs-detail-label">${esc(label)}</span>
        <span class="fs-detail-value">${value}</span>
      </div>`;
  }

  function renderCalcTable(body, items) {
    if (!items.length) {
      body.innerHTML = `<tr><td colspan="2" class="pm-loading-row">No components recorded.</td></tr>`;
      return;
    }
    body.innerHTML = items
      .map(
        (item) => `
      <tr>
        <td>${esc(item.label)}</td>
        <td class="fs-amount-col">${peso(item.amount)}</td>
      </tr>`,
      )
      .join("");
  }

  function renderAdjustmentsTable(body, emptyEl, items) {
    if (!items.length) {
      body.innerHTML = "";
      emptyEl.style.display = "block";
      return;
    }
    emptyEl.style.display = "none";
    body.innerHTML = items
      .map(
        (item) => `
      <tr>
        <td>${esc(item.label)}</td>
        <td class="fs-amount-col">${peso(item.amount)}</td>
      </tr>`,
      )
      .join("");
  }

  function renderDetailActions(s) {
    let lockedNote = "";
    let actions = "";

    if (s.status === "draft") {
      actions = `
        <button type="button" class="pm-btn pm-btn-secondary" id="fsActEdit"><i class="fa-solid fa-pen"></i> Edit</button>
        <button type="button" class="pm-btn pm-btn-primary" id="fsActSubmitReview"><i class="fa-solid fa-paper-plane"></i> Submit for Review</button>`;
    } else if (s.status === "for_review") {
      actions = `
        <button type="button" class="pm-btn pm-btn-secondary" id="fsActReturn"><i class="fa-solid fa-rotate-left"></i> Return</button>
        <button type="button" class="pm-btn pm-btn-primary" id="fsActApprove"><i class="fa-solid fa-check"></i> Approve</button>`;
    } else if (s.status === "approved") {
      actions = `
        <button type="button" class="pm-btn pm-btn-secondary" id="fsActPrint"><i class="fa-solid fa-print"></i> Print Statement</button>
        <button type="button" class="pm-btn pm-btn-primary" id="fsActRelease"><i class="fa-solid fa-lock"></i> Mark as Released</button>`;
    } else if (s.status === "released") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-lock"></i> This settlement is released and locked.</div>`;
      actions = `
        <button type="button" class="pm-btn pm-btn-secondary" id="fsActPrint"><i class="fa-solid fa-print"></i> Print Statement</button>`;
    } else if (s.status === "cancelled") {
      lockedNote = `<div class="fs-locked-note"><i class="fa-solid fa-ban"></i> This settlement has been cancelled.</div>`;
    }

    detailActions.innerHTML = `${lockedNote || "<div></div>"}<div class="fs-detail-footer-actions">${actions}</div>`;

    const editBtn = document.getElementById("fsActEdit");
    if (editBtn)
      editBtn.addEventListener("click", () =>
        showAlert(
          "Editing form is not yet implemented in this mockup.",
          "error",
        ),
      );

    const submitBtn = document.getElementById("fsActSubmitReview");
    if (submitBtn)
      submitBtn.addEventListener("click", () => {
        s.status = "for_review";
        renderAll();
        renderDetailModal();
        showAlert(
          `Settlement for ${s.employeeName} submitted for review.`,
          "success",
        );
      });

    const returnBtn = document.getElementById("fsActReturn");
    if (returnBtn)
      returnBtn.addEventListener("click", () => {
        s.status = "draft";
        renderAll();
        renderDetailModal();
        showAlert(
          `Settlement for ${s.employeeName} returned to draft.`,
          "success",
        );
      });

    const approveBtn = document.getElementById("fsActApprove");
    if (approveBtn)
      approveBtn.addEventListener("click", () => {
        approveConfirmText.textContent = `You are about to approve the final settlement for ${s.employeeName}.`;
        openModal(approveOverlay);
      });

    const releaseBtn = document.getElementById("fsActRelease");
    if (releaseBtn)
      releaseBtn.addEventListener("click", () => openModal(releaseOverlay));

    const printBtn = document.getElementById("fsActPrint");
    if (printBtn)
      printBtn.addEventListener("click", () => printStatement(s.id));

    const canAdjust = s.status === "draft" || s.status === "for_review";
    btnAddAdjustment.disabled = !canAdjust;
    btnAddAdjustment.style.display = canAdjust ? "inline-flex" : "none";
  }

  function renderDetailModal() {
    const s = findSettlement(currentDetailId);
    if (!s) return;

    const totals = computeTotals(s);

    detailSubtitle.textContent = `${s.employeeCode} \u2014 ${s.employeeName}`;
    renderWorkflowInto(workflowEl, s.status);

    employeeInfoEl.innerHTML =
      infoRow("Employee", esc(s.employeeName)) +
      infoRow("Employee Code", esc(s.employeeCode)) +
      infoRow("Department", esc(s.department)) +
      infoRow("Position", esc(s.position)) +
      infoRow("Employment Status", esc(s.employmentStatus)) +
      infoRow("Monthly Salary", peso(s.monthlySalary));

    exitInfoEl.innerHTML =
      infoRow("Exit Type", esc(s.exitType)) +
      infoRow("Exit Reason", esc(s.exitReason)) +
      infoRow("Exit Date", formatDate(s.exitDate)) +
      infoRow("Last Working Day", formatDate(s.lastWorkingDay)) +
      infoRow(
        "Exit Status",
        `<span class="fs-status-badge fs-status-approved">${esc(s.exitStatus)}</span>`,
      );

    renderCalcTable(earningsBody, s.earnings);
    totalEarningsEl.textContent = peso(
      s.earnings.reduce((sum, e) => sum + e.amount, 0),
    );

    renderCalcTable(deductionsBody, s.deductions);
    totalDeductionsEl.textContent = peso(
      s.deductions.reduce((sum, d) => sum + d.amount, 0),
    );

    calcEarningsEl.textContent = peso(totals.totalEarnings);
    calcDeductionsEl.textContent = peso(totals.totalDeductions);
    calcNetEl.textContent = peso(totals.net);

    renderAdjustmentsTable(
      adjEarningsBody,
      adjEarningsEmpty,
      s.additionalEarnings,
    );
    renderAdjustmentsTable(
      adjDeductionsBody,
      adjDeductionsEmpty,
      s.additionalDeductions,
    );

    // Reset adjustments tabs to the first tab each time the modal (re)opens.
    const tabItems = detailOverlay.querySelectorAll(
      ".fs-adjust-tabs .tab-item",
    );
    const tabContents = detailOverlay.querySelectorAll(
      ".fs-adjust-tabs .tab-content",
    );
    tabItems.forEach((t, i) => t.classList.toggle("active", i === 0));
    tabContents.forEach((c, i) => c.classList.toggle("active", i === 0));

    renderDetailActions(s);
  }

  function openDetail(id) {
    currentDetailId = id;
    renderDetailModal();
    openModal(detailOverlay);
  }

  // ---- Add Adjustment modal --------------------------------------------

  btnAddAdjustment.addEventListener("click", () => {
    adjustForm.reset();
    adjustFormError.style.display = "none";
    openModal(adjustOverlay);
  });

  adjustForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const s = findSettlement(currentDetailId);
    if (!s) return;

    const description = adjustDescription.value.trim();
    const amount = parseFloat(adjustAmount.value);

    if (!description || !amount || amount <= 0) {
      adjustFormError.textContent =
        "Please enter a description and a valid amount.";
      adjustFormError.style.display = "block";
      return;
    }

    if (adjustType.value === "earning") {
      s.additionalEarnings.push({ label: description, amount });
    } else {
      s.additionalDeductions.push({ label: description, amount });
    }

    closeModal(adjustOverlay);
    renderDetailModal();
    renderAll();
    showAlert("Adjustment added to the settlement.", "success");
  });

  // ---- Approve / Release confirmations -----------------------------------

  btnConfirmApprove.addEventListener("click", () => {
    const s = findSettlement(currentDetailId);
    if (!s) return;
    s.status = "approved";
    closeModal(approveOverlay);
    renderAll();
    renderDetailModal();
    showAlert(`Settlement for ${s.employeeName} approved.`, "success");
  });

  btnConfirmRelease.addEventListener("click", () => {
    const s = findSettlement(currentDetailId);
    if (!s) return;
    s.status = "released";
    closeModal(releaseOverlay);
    renderAll();
    renderDetailModal();
    showAlert(`Settlement for ${s.employeeName} released.`, "success");
  });

  // ---- Print Statement --------------------------------------------------

  function printSectionRows(items) {
    return items
      .map(
        (item) => `
      <tr>
        <td>${esc(item.label)}</td>
        <td>${peso(item.amount)}</td>
      </tr>`,
      )
      .join("");
  }

  function buildPrintDocument(s) {
    const totals = computeTotals(s);
    const now = new Date().toLocaleString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
    const earnings = [...s.earnings, ...s.additionalEarnings];
    const deductions = [...s.deductions, ...s.additionalDeductions];

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
          <div><span>Employee</span><strong>${esc(s.employeeName)}</strong></div>
          <div><span>Employee Code</span><strong>${esc(s.employeeCode)}</strong></div>
          <div><span>Department</span><strong>${esc(s.department)}</strong></div>
          <div><span>Position</span><strong>${esc(s.position)}</strong></div>
        </div>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Exit Information</div>
        <div class="fs-doc-info-grid">
          <div><span>Exit Type</span><strong>${esc(s.exitType)}</strong></div>
          <div><span>Exit Date</span><strong>${formatDate(s.exitDate)}</strong></div>
          <div><span>Last Working Day</span><strong>${formatDate(s.lastWorkingDay)}</strong></div>
        </div>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Earnings</div>
        <table class="fs-doc-table">
          <thead><tr><th>Component</th><th>Amount</th></tr></thead>
          <tbody>
            ${printSectionRows(earnings)}
            <tr class="fs-doc-total-row"><td>Total Earnings</td><td>${peso(totals.totalEarnings)}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-section-title">Deductions</div>
        <table class="fs-doc-table">
          <thead><tr><th>Component</th><th>Amount</th></tr></thead>
          <tbody>
            ${printSectionRows(deductions)}
            <tr class="fs-doc-total-row"><td>Total Deductions</td><td>${peso(totals.totalDeductions)}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="fs-doc-section">
        <div class="fs-doc-net-card">
          <span>Net Final Settlement</span>
          <strong>${peso(totals.net)}</strong>
        </div>
      </div>

      <div class="fs-doc-signatures">
        <div class="fs-doc-signature-line">
          <span>Prepared By:</span>
          <div class="fs-doc-signature-space"></div>
        </div>
        <div class="fs-doc-signature-line">
          <span>Reviewed By:</span>
          <div class="fs-doc-signature-space"></div>
        </div>
        <div class="fs-doc-signature-line">
          <span>Approved By:</span>
          <div class="fs-doc-signature-space"></div>
        </div>
        <div class="fs-doc-signature-line">
          <span>Released By:</span>
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

  function printStatement(id) {
    const s = findSettlement(id);
    if (!s) return;
    printSheet.innerHTML = buildPrintDocument(s);
    window.print();
  }

  // ---- Modal close wiring -------------------------------------------------

  document.addEventListener("click", (e) => {
    const closeTrigger = e.target.closest("[data-fs-close]");
    if (closeTrigger) {
      closeModal(
        document.getElementById(closeTrigger.getAttribute("data-fs-close")),
      );
      return;
    }
    // Click on the overlay backdrop itself (not its modal content) closes it.
    if (e.target.classList && e.target.classList.contains("pm-modal-overlay")) {
      const overlays = [
        createOverlay,
        detailOverlay,
        adjustOverlay,
        approveOverlay,
        releaseOverlay,
      ];
      if (overlays.includes(e.target)) closeModal(e.target);
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    [
      releaseOverlay,
      approveOverlay,
      adjustOverlay,
      detailOverlay,
      createOverlay,
    ].forEach((ov) => {
      if (ov.style.display === "flex") closeModal(ov);
    });
  });

  // ---- Init -----------------------------------------------------------

  renderAll();
}

window.addEventListener("page:loaded", initFinalSettlement);
