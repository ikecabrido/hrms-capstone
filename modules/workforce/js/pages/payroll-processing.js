/**
 * Payroll Processing
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/payrollController.php (AJAX/JSON).
 * Follows the exact same conventions as period-manager.js: listens for
 * the "page:loaded" event (since injected <script> tags don't execute
 * on SPA navigation), and does nothing if this page isn't the one on
 * screen.
 *
 * All payroll math (earnings, deductions, contributions, tax, net pay)
 * comes from PayrollModel via PayrollController — nothing is
 * recalculated here. This file only fetches, categorizes for display,
 * and renders.
 */

function initPayrollProcessing() {
  const root = document.getElementById("payrollProcessingPage");
  if (!root) return; // Not on the Payroll Processing page — nothing to do.
  if (root.dataset.ppInitialized === "true") return;
  root.dataset.ppInitialized = "true";

  const ENDPOINT = "controllers/payrollController.php";

  let periods = [];
  let selectedPeriod = null;
  let employees = [];
  let selectedEmployeeId = null;
  const attendanceCache = {};
  const facultyScheduleCache = {};
  let isFinalized = false;

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("ppAlert");
  const periodSelect = document.getElementById("ppPeriodSelect");
  const btnCalculate = document.getElementById("ppBtnCalculate");
  const periodInfo = document.getElementById("ppPeriodInfo");
  const infoName = document.getElementById("ppInfoName");
  const infoStart = document.getElementById("ppInfoStart");
  const infoEnd = document.getElementById("ppInfoEnd");
  const infoPay = document.getElementById("ppInfoPay");
  const infoStatus = document.getElementById("ppInfoStatus");
  const closedNotice = document.getElementById("ppClosedNotice");

  const processingState = document.getElementById("ppProcessingState");
  const summaryCards = document.getElementById("ppSummaryCards");
  const sumProcessed = document.getElementById("ppSumProcessed");
  const sumEarnings = document.getElementById("ppSumEarnings");
  const sumDeductions = document.getElementById("ppSumDeductions");
  const sumGross = document.getElementById("ppSumGross");
  const sumTotalDeductions = document.getElementById("ppSumTotalDeductions");
  const sumNet = document.getElementById("ppSumNet");

  const employeeSection = document.getElementById("ppEmployeeSection");
  const employeeSearch = document.getElementById("ppEmployeeSearch");
  const employeeList = document.getElementById("ppEmployeeList");

  const breakdownSection = document.getElementById("ppBreakdownSection");
  const breakdownBody = document.getElementById("ppBreakdownBody");

  const finalizeBar = document.getElementById("ppFinalizeBar");
  const btnFinalize = document.getElementById("ppBtnFinalize");
  const successPanel = document.getElementById("ppSuccessPanel");
  const successRunId = document.getElementById("ppSuccessRunId");
  const successCount = document.getElementById("ppSuccessCount");

  const finalizeModalOverlay = document.getElementById(
    "ppFinalizeModalOverlay",
  );
  const finalizeError = document.getElementById("ppFinalizeError");
  const btnConfirmFinalize = document.getElementById("ppBtnConfirmFinalize");

  // ---- Helpers --------------------------------------------------------------
  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  function money(amount) {
    const n = Number(amount) || 0;
    return (
      "\u20b1" +
      n.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function formatDate(dateStr) {
    if (!dateStr) return "—";
    const d = new Date(
      dateStr + (String(dateStr).length <= 10 ? "T00:00:00" : ""),
    );
    if (isNaN(d.getTime())) return esc(dateStr);
    return d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  }

  function formatTime(timeStr) {
    if (!timeStr) return "—";
    const parts = String(timeStr).split(":");
    if (parts.length < 2) return esc(timeStr);
    const d = new Date(
      1970,
      0,
      1,
      parseInt(parts[0], 10),
      parseInt(parts[1], 10),
    );
    if (isNaN(d.getTime())) return esc(timeStr);
    return d.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
    });
  }

  function fullName(e) {
    return [e.first_name, e.middle_name, e.last_name].filter(Boolean).join(" ");
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
    }, 6000);
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  async function apiGet(action, params = {}) {
    const qs = new URLSearchParams(
      Object.assign({ action }, params),
    ).toString();
    const res = await fetch(`${ENDPOINT}?${qs}`, {
      method: "GET",
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    return handleResponse(res);
  }

  async function apiPost(action, body = {}) {
    const fd = new FormData();
    fd.append("action", action);
    Object.keys(body).forEach(function (k) {
      fd.append(k, body[k]);
    });

    const res = await fetch(ENDPOINT, {
      method: "POST",
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: fd,
    });
    return handleResponse(res);
  }

  async function handleResponse(res) {
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
    try {
      return await res.json();
    } catch (e) {
      throw new Error("Unexpected server response.");
    }
  }

  // ---- Deduction categorization (display-only; values are untouched) -------
  function classifyDeduction(desc) {
    if (desc === "SSS" || desc === "PhilHealth" || desc === "Pag-IBIG")
      return "contribution";
    if (desc === "Withholding Tax") return "tax";
    if (desc.indexOf("Unexcused Absence") === 0) return "absence";
    if (desc.indexOf("Late (") === 0) return "late";
    return "adjustment";
  }

  // ---- STEP 1: Load periods --------------------------------------------------------------
  async function loadPeriods() {
    try {
      const res = await apiGet("periods");
      if (!res.success) {
        showAlert(res.message || "Failed to load payroll periods.", "error");
        return;
      }
      periods = res.data || [];

      periodSelect.innerHTML =
        '<option value="">Select a payroll period...</option>' +
        periods
          .map(function (p) {
            return `<option value="${p.period_id}">${esc(p.period_name)} (${p.status.toUpperCase()})</option>`;
          })
          .join("");

      const openPeriod = periods.find(function (p) {
        return p.status === "open";
      });
      if (openPeriod) {
        periodSelect.value = openPeriod.period_id;
        onPeriodChange();
      }
    } catch (err) {
      showAlert(err.message || "Failed to load payroll periods.", "error");
    }
  }

  function resetDownstream() {
    processingState.style.display = "none";
    summaryCards.style.display = "none";
    employeeSection.style.display = "none";
    breakdownSection.style.display = "none";
    finalizeBar.style.display = "none";
    successPanel.style.display = "none";
    employees = [];
    selectedEmployeeId = null;
    isFinalized = false;
  }

  function onPeriodChange() {
    const id = periodSelect.value;
    resetDownstream();

    if (!id) {
      periodInfo.style.display = "none";
      closedNotice.style.display = "none";
      btnCalculate.disabled = true;
      return;
    }

    selectedPeriod = periods.find(function (p) {
      return String(p.period_id) === String(id);
    });
    if (!selectedPeriod) return;

    periodInfo.style.display = "grid";
    infoName.textContent = selectedPeriod.period_name;
    infoStart.textContent = formatDate(selectedPeriod.start_date);
    infoEnd.textContent = formatDate(selectedPeriod.end_date);
    infoPay.textContent = formatDate(selectedPeriod.pay_date);

    const isOpen = selectedPeriod.status === "open";
    infoStatus.textContent = selectedPeriod.status.toUpperCase();
    infoStatus.className =
      "pm-badge " + (isOpen ? "pm-badge-open" : "pm-badge-closed");

    closedNotice.style.display = isOpen ? "none" : "flex";
    btnCalculate.disabled = !isOpen;
  }

  periodSelect.addEventListener("change", onPeriodChange);

  // ---- STEP 2/3: Calculate payroll --------------------------------------------------------------
  async function calculatePayroll() {
    if (!selectedPeriod || selectedPeriod.status !== "open") return;

    resetDownstream();
    processingState.style.display = "block";
    btnCalculate.disabled = true;

    try {
      const res = await apiPost("calculate", {
        period_id: selectedPeriod.period_id,
      });
      processingState.style.display = "none";

      if (!res.success) {
        showAlert(res.message || "Failed to calculate payroll.", "error");
        btnCalculate.disabled = false;
        return;
      }

      employees = res.data.employees || [];
      renderSummary(res.data.summary);
      renderEmployeeList();

      summaryCards.style.display = "grid";
      employeeSection.style.display = employees.length ? "block" : "none";

      if (!employees.length) {
        showAlert(
          "Payroll was calculated, but no active employees were found for this period.",
          "error",
        );
      } else {
        showAlert(
          "Payroll calculated successfully. Select an employee below to review the breakdown.",
          "success",
        );
        finalizeBar.style.display = "flex";
      }
    } catch (err) {
      processingState.style.display = "none";
      showAlert(err.message || "Failed to calculate payroll.", "error");
    } finally {
      btnCalculate.disabled = selectedPeriod.status !== "open";
    }
  }

  btnCalculate.addEventListener("click", calculatePayroll);

  function renderSummary(summary) {
    sumProcessed.textContent = summary.employees_processed ?? 0;
    sumEarnings.textContent = summary.employees_with_earnings ?? 0;
    sumDeductions.textContent = summary.employees_with_deductions ?? 0;
    sumGross.textContent = money(summary.total_gross_pay);
    sumTotalDeductions.textContent = money(summary.total_deductions);
    sumNet.textContent = money(summary.total_net_pay);
  }

  // ---- STEP 5: Employee list --------------------------------------------------------------
  function renderEmployeeList() {
    const term = (employeeSearch.value || "").trim().toLowerCase();

    const filtered = employees.filter(function (e) {
      if (!term) return true;
      const haystack = (
        fullName(e) +
        " " +
        (e.employee_num || "")
      ).toLowerCase();
      return haystack.includes(term);
    });

    if (!filtered.length) {
      employeeList.innerHTML =
        '<p class="pp-empty-line">No employees match your search.</p>';
      return;
    }

    employeeList.innerHTML = filtered
      .map(function (e) {
        const tags = [];
        if (e.is_faculty)
          tags.push('<span class="pp-tag pp-tag-faculty">Faculty</span>');
        if (e.is_part_time)
          tags.push('<span class="pp-tag pp-tag-parttime">Part-time</span>');

        const activeClass =
          String(e.employee_id) === String(selectedEmployeeId)
            ? " pp-employee-row-active"
            : "";

        return `<div class="pp-employee-row${activeClass}" data-employee-id="${e.employee_id}">
                <div class="pp-employee-main">
                    <span class="pp-employee-name">${esc(fullName(e))}</span>
                    <span class="pp-employee-meta">${esc(e.employee_code || "")} &middot; ${esc(e.employment_type || "")} ${tags.join("")}</span>
                </div>
                <span class="pp-employee-net">${money(e.net_pay)}</span>
            </div>`;
      })
      .join("");
  }

  employeeSearch.addEventListener("input", renderEmployeeList);

  employeeList.addEventListener("click", function (e) {
    const row = e.target.closest(".pp-employee-row[data-employee-id]");
    if (!row) return;
    selectEmployee(row.getAttribute("data-employee-id"));
  });

  // ---- Breakdown --------------------------------------------------------------
  function selectEmployee(employeeId) {
    selectedEmployeeId = employeeId;
    renderEmployeeList();

    const emp = employees.find(function (e) {
      return String(e.employee_id) === String(employeeId);
    });
    if (!emp) return;

    renderBreakdown(emp);
    breakdownSection.style.display = "block";
    breakdownSection.scrollIntoView({ behavior: "smooth", block: "nearest" });

    loadAttendanceFor(emp);
    if (emp.is_faculty) {
      loadFacultyScheduleFor(emp);
    }
  }

  function buildEarningsHtml(emp) {
    if (!emp.earnings || !emp.earnings.length) {
      return '<p class="pp-empty-line">No earnings recorded for this period.</p>';
    }
    const rows = emp.earnings
      .map(function (item) {
        return `<div class="pp-line-item">
                <span class="pp-line-desc">${esc(item.description)}</span>
                <span class="pp-line-amount">${money(item.amount)}</span>
            </div>`;
      })
      .join("");
    return `<div class="pp-line-list">${rows}</div>`;
  }

  function buildDeductionGroupHtml(items, emptyText) {
    if (!items.length) {
      return `<p class="pp-empty-line">${esc(emptyText)}</p>`;
    }
    const rows = items
      .map(function (item) {
        return `<div class="pp-line-item">
                <span class="pp-line-desc">${esc(item.description)}</span>
                <span class="pp-line-amount pp-line-amount-deduction">-${money(item.amount)}</span>
            </div>`;
      })
      .join("");
    return `<div class="pp-line-list">${rows}</div>`;
  }

  function buildContributionsHtml(emp) {
    const status = emp.contribution_status || {};
    const deductions = emp.deductions || [];

    function findAmount(desc) {
      const found = deductions.find(function (d) {
        return d.description === desc;
      });
      return found ? found.amount : null;
    }

    function card(label, eligible, amount) {
      const pillClass = eligible ? "pp-status-submitted" : "pp-status-pending";
      const pillText = eligible ? "Submitted" : "Pending";
      const amountText = eligible
        ? amount !== null
          ? money(amount)
          : money(0)
        : "Not deducted";

      return `<div class="pp-contrib-card">
                <span class="pp-contrib-label">${esc(label)}</span>
                <strong>${amountText}</strong>
                <span class="pp-status-pill ${pillClass}">${pillText}</span>
            </div>`;
    }

    return `<div class="pp-contrib-grid">
            ${card("SSS", !!status.sss, findAmount("SSS"))}
            ${card("PhilHealth", !!status.philhealth, findAmount("PhilHealth"))}
            ${card("Pag-IBIG", !!status.pagibig, findAmount("Pag-IBIG"))}
            ${card("BIR", !!status.bir, findAmount("Withholding Tax"))}
        </div>`;
  }

  function renderBreakdown(emp) {
    const deductions = emp.deductions || [];
    const grouped = { absence: [], late: [], adjustment: [] };

    deductions.forEach(function (d) {
      const cat = classifyDeduction(d.description);
      if (cat === "absence") grouped.absence.push(d);
      else if (cat === "late") grouped.late.push(d);
      else if (cat === "adjustment") grouped.adjustment.push(d);
      // 'contribution' and 'tax' are rendered in their own dedicated section
    });

    let classificationLabel = emp.employment_type || "Unknown";
    if (emp.is_faculty) classificationLabel += " &middot; Faculty";

    const negotiatedSalaryHtml = emp.negotiated_salary
      ? `<div><span>Negotiated Salary</span><strong>${money(emp.negotiated_salary)}</strong></div>`
      : "";

    const html = `
            <div class="pp-breakdown-header">
                <div>
                    <h3 class="pp-breakdown-name">${esc(fullName(emp))}</h3>
                    <div class="pp-breakdown-sub">${esc(emp.employee_code || "")} &middot; ${classificationLabel}</div>
                </div>
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-id-card"></i> Employee Information</div>
                <div class="pp-info-grid">
                    <div><span>Employee No.</span><strong>${esc(emp.employee_code || "—")}</strong></div>
                    <div><span>Employment Type</span><strong>${esc(emp.employment_type || "—")}</strong></div>
                    <div><span>Qualification</span><strong>${esc(emp.graduate_level || "—")}</strong></div>
                    ${negotiatedSalaryHtml}
                </div>
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-calendar-days"></i> Payroll Period</div>
                <div class="pp-info-grid">
                    <div><span>Period</span><strong>${esc(selectedPeriod.period_name)}</strong></div>
                    <div><span>Start</span><strong>${formatDate(selectedPeriod.start_date)}</strong></div>
                    <div><span>End</span><strong>${formatDate(selectedPeriod.end_date)}</strong></div>
                    <div><span>Pay Date</span><strong>${formatDate(selectedPeriod.pay_date)}</strong></div>
                </div>
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-clock"></i> Attendance</div>
                <div id="ppAttendanceBox" class="pp-loading-inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading attendance...</div>
            </div>

            ${
              emp.is_faculty
                ? `
            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-chalkboard-user"></i> Faculty Schedule / Teaching Load</div>
                <div id="ppScheduleBox" class="pp-loading-inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading schedule...</div>
            </div>`
                : ""
            }

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-coins"></i> Earnings</div>
                ${buildEarningsHtml(emp)}
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-calendar-xmark"></i> Absence &amp; Late Deductions</div>
                ${buildDeductionGroupHtml(grouped.absence.concat(grouped.late), "No absence or late deductions for this period.")}
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-file-pen"></i> Employee Adjustments</div>
                ${buildDeductionGroupHtml(grouped.adjustment, "No additional adjustments for this period.")}
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-hand-holding-dollar"></i> Government Contributions</div>
                ${buildContributionsHtml(emp)}
            </div>

            <div class="pp-section">
                <div class="pp-section-title"><i class="fa-solid fa-scale-balanced"></i> Withholding Tax</div>
                ${buildDeductionGroupHtml(
                  deductions.filter(function (d) {
                    return d.description === "Withholding Tax";
                  }),
                  "No withholding tax applied for this period.",
                )}
            </div>

            <div class="pp-section">
                <div class="pp-pay-summary">
                    <div class="pp-pay-row"><span>Gross Pay</span><strong>${money(emp.gross_pay)}</strong></div>
                    <div class="pp-pay-row"><span>Total Deductions</span><strong>-${money(emp.total_deductions)}</strong></div>
                    <div class="pp-pay-row pp-pay-row-total"><span>Net Pay</span><strong>${money(emp.net_pay)}</strong></div>
                </div>
            </div>
        `;

    breakdownBody.innerHTML = html;
  }

  async function loadAttendanceFor(emp) {
    const box = document.getElementById("ppAttendanceBox");
    if (!box) return;

    try {
      let data = attendanceCache[emp.employee_id];
      if (!data) {
        const res = await apiGet("attendance", {
          employee_id: emp.employee_id,
          period_id: selectedPeriod.period_id,
        });
        if (!res.success) {
          box.innerHTML = `<p class="pp-empty-line">${esc(res.message || "Attendance data is unavailable.")}</p>`;
          return;
        }
        data = res.data;
        attendanceCache[emp.employee_id] = data;
      }

      // The box may have been replaced if the user switched employees quickly.
      const currentBox = document.getElementById("ppAttendanceBox");
      if (!currentBox) return;

      currentBox.className = "";
      currentBox.innerHTML = `
                <div class="pp-info-grid">
                    <div><span>Present Days</span><strong>${data.present_days ?? 0}</strong></div>
                    <div><span>Absent Days</span><strong>${data.absent_days ?? 0}</strong></div>
                    <div><span>Late Days</span><strong>${data.late_days ?? 0}</strong></div>
                    <div><span>Total Hours Worked</span><strong>${Number(data.total_hours_worked ?? 0).toFixed(2)}</strong></div>
                    <div><span>Total Late Minutes</span><strong>${data.total_late_minutes ?? 0}</strong></div>
                    <div><span>Total Early-Out Minutes</span><strong>${data.total_early_out_minutes ?? 0}</strong></div>
                </div>
            `;
    } catch (err) {
      if (box)
        box.innerHTML = `<p class="pp-empty-line">${esc(err.message || "Attendance data is unavailable.")}</p>`;
    }
  }

  async function loadFacultyScheduleFor(emp) {
    const box = document.getElementById("ppScheduleBox");
    if (!box) return;

    try {
      let data = facultyScheduleCache[emp.employee_id];
      if (!data) {
        const res = await apiGet("faculty_schedule", {
          employee_id: emp.employee_id,
        });
        if (!res.success) {
          box.innerHTML = `<p class="pp-empty-line">${esc(res.message || "Faculty schedule is unavailable.")}</p>`;
          return;
        }
        data = res.data || [];
        facultyScheduleCache[emp.employee_id] = data;
      }

      const currentBox = document.getElementById("ppScheduleBox");
      if (!currentBox) return;

      if (!data.length) {
        currentBox.innerHTML =
          '<p class="pp-empty-line">No recurring schedule found for this faculty member.</p>';
        return;
      }

      const rows = data
        .map(function (c) {
          return `<tr>
                    <td>${esc(c.day_of_week || "—")}</td>
                    <td>${formatTime(c.start_time)} &ndash; ${formatTime(c.end_time)}</td>
                    <td>${esc(c.subject_name || "—")}</td>
                    <td>${esc(c.subject_code || "—")}</td>
                    <td>${c.units ?? "—"}</td>
                </tr>`;
        })
        .join("");

      currentBox.innerHTML = `
                <table class="pp-schedule-table">
                    <thead>
                        <tr><th>Day</th><th>Time</th><th>Subject</th><th>Code</th><th>Units</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
    } catch (err) {
      if (box)
        box.innerHTML = `<p class="pp-empty-line">${esc(err.message || "Faculty schedule is unavailable.")}</p>`;
    }
  }

  // ---- Finalization --------------------------------------------------------------
  function openFinalizeModal() {
    finalizeError.style.display = "none";
    btnConfirmFinalize.disabled = false;
    finalizeModalOverlay.style.display = "flex";
  }

  function closeFinalizeModal() {
    finalizeModalOverlay.style.display = "none";
  }

  btnFinalize.addEventListener("click", openFinalizeModal);

  document.querySelectorAll("[data-pp-close]").forEach(function (btn) {
    btn.addEventListener("click", closeFinalizeModal);
  });

  finalizeModalOverlay.addEventListener("click", function (e) {
    if (e.target === finalizeModalOverlay) closeFinalizeModal();
  });

  btnConfirmFinalize.addEventListener("click", async function () {
    if (!selectedPeriod) return;

    btnConfirmFinalize.disabled = true;
    finalizeError.style.display = "none";

    try {
      const res = await apiPost("finalize", {
        period_id: selectedPeriod.period_id,
      });

      if (!res.success) {
        finalizeError.textContent =
          res.message || "Failed to finalize payroll.";
        finalizeError.style.display = "block";
        btnConfirmFinalize.disabled = false;
        return;
      }

      closeFinalizeModal();
      isFinalized = true;

      successRunId.textContent = "#" + res.data.run_id;
      successCount.textContent = res.data.generated_count;
      successPanel.style.display = "block";
      finalizeBar.style.display = "none";

      // Reflect the now-closed period in the UI without a full reload.
      selectedPeriod.status = "closed";
      const periodInList = periods.find(function (p) {
        return String(p.period_id) === String(selectedPeriod.period_id);
      });
      if (periodInList) periodInList.status = "closed";

      infoStatus.textContent = "CLOSED";
      infoStatus.className = "pm-badge pm-badge-closed";
      closedNotice.style.display = "flex";
      btnCalculate.disabled = true;

      const opt = periodSelect.querySelector(
        `option[value="${selectedPeriod.period_id}"]`,
      );
      if (opt) opt.textContent = `${selectedPeriod.period_name} (CLOSED)`;

      showAlert(
        "Payroll finalized successfully. Payslips have been generated.",
        "success",
      );
      successPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
    } catch (err) {
      finalizeError.textContent = err.message || "Failed to finalize payroll.";
      finalizeError.style.display = "block";
      btnConfirmFinalize.disabled = false;
    }
  });

  // ---- Init --------------------------------------------------------------
  loadPeriods();
}

window.addEventListener("page:loaded", initPayrollProcessing);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPayrollProcessing);
} else {
  initPayrollProcessing();
}
