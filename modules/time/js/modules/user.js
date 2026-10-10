window.addEventListener("page:loaded", function () {
  const base = window.location.origin + "/hrms-capstone-master";

  // ── Shared Helper ─────────────────────────────────────────────────────────
  function showAlert(id, message, success = false) {
    const msg = document.getElementById(id);
    if (!msg) return;
    msg.textContent = message;
    msg.style.display = "block";
    msg.style.padding = "10px 15px";
    msg.style.borderRadius = "5px";
    msg.style.marginBottom = "15px";
    msg.style.backgroundColor = success ? "#d4edda" : "#f8d7da";
    msg.style.color = success ? "#155724" : "#721c24";
    msg.style.border = success ? "1px solid #c3e6cb" : "1px solid #f5c6cb";
    setTimeout(() => {
      msg.style.display = "none";
    }, 4000);
  }

  function esc(str) {
    const d = document.createElement("div");
    d.textContent = String(str ?? "");
    return d.innerHTML;
  }

  // ── Load Employee Table ───────────────────────────────────────────────────
  async function loadEmployeeTable() {
    const tbody = document.getElementById("employee-table-body");
    if (!tbody) return;

    try {
      const res = await fetch(
        `${base}/modules/recruitment/controllers/UserController.php?action=get_employees`,
      );
      const json = await res.json();

      if (!json.success || !json.data?.length) {
        tbody.innerHTML = `<tr><td colspan="6">No employees found.</td></tr>`;
        return;
      }

      tbody.innerHTML = json.data
        .map(
          (e) => `
                <tr class="employee-row">
                    <td>${esc(e.first_name ?? "—")}</td>
                    <td>${esc(e.middle_name ?? "—")}</td>
                    <td>${esc(e.last_name ?? "—")}</td>
                    <td>${esc(e.department_name ?? "—")}</td>
                    <td>${esc(e.position_name ?? "—")}</td>
                    <td>${esc(e.status ? e.status.charAt(0).toUpperCase() + e.status.slice(1).toLowerCase() : "—")}</td>
                </tr>
            `,
        )
        .join("");

      // re-apply filters and pagination after reload
      applyFilters();
    } catch (err) {
      console.error("loadEmployeeTable error:", err);
    }
  }

  // ── Employee List: Filter + Pagination ────────────────────────────────────
  const tbody = document.getElementById("employee-table-body");
  const prevBtn = document.getElementById("emp-prev-btn");
  const nextBtn = document.getElementById("emp-next-btn");
  const pageInfo = document.getElementById("emp-page-info");
  const filterDepartment = document.getElementById("filter-department");
  const filterPosition = document.getElementById("filter-position");
  const filterResetBtn = document.getElementById("filter-reset-btn");

  const rowsPerPage = 10;
  let currentPage = 1;
  let filteredRows = [];

  function getColText(row, index) {
    return row.querySelectorAll("td")[index]?.textContent.trim() ?? "";
  }

  function applyFilters() {
    const allEmpRows = Array.from(document.querySelectorAll(".employee-row"));
    const dept = filterDepartment?.value.toLowerCase() ?? "";
    const pos = filterPosition?.value.toLowerCase() ?? "";

    filteredRows = allEmpRows.filter((row) => {
      const rowDept = getColText(row, 3).toLowerCase();
      const rowPos = getColText(row, 4).toLowerCase();
      return (!dept || rowDept === dept) && (!pos || rowPos === pos);
    });

    currentPage = 1;
    renderPage();
  }

  function renderPage() {
    const allEmpRows = Array.from(document.querySelectorAll(".employee-row"));
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    allEmpRows.forEach((row) => (row.style.display = "none"));
    filteredRows.slice(start, end).forEach((row) => (row.style.display = ""));

    if (pageInfo)
      pageInfo.textContent = filteredRows.length
        ? `Page ${currentPage} of ${totalPages}`
        : "No results found";

    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn)
      nextBtn.disabled = currentPage >= totalPages || filteredRows.length === 0;
  }

  filterDepartment?.addEventListener("change", () => {
    if (filterPosition) filterPosition.value = "";
    applyFilters();
  });
  filterPosition?.addEventListener("change", applyFilters);
  filterResetBtn?.addEventListener("click", () => {
    if (filterDepartment) filterDepartment.value = "";
    if (filterPosition) filterPosition.value = "";
    applyFilters();
  });

  if (prevBtn)
    prevBtn.addEventListener("click", () => {
      currentPage--;
      renderPage();
    });
  if (nextBtn)
    nextBtn.addEventListener("click", () => {
      currentPage++;
      renderPage();
    });

  // initial load
  loadEmployeeTable();

  // ── Employee Registration ─────────────────────────────────────────────────
  const registrationForm = document.getElementById("add-user-form");

  if (registrationForm) {
    const freshForm = registrationForm.cloneNode(true);
    registrationForm.parentNode.replaceChild(freshForm, registrationForm);

    const deptSelect = freshForm.querySelector("#department");
    const posSelect = freshForm.querySelector("#position");
    const roleInput = freshForm.querySelector("#role");

    deptSelect.addEventListener("change", function () {
      const deptId = this.value;

      posSelect.innerHTML = '<option value="">Select Position</option>';
      posSelect.disabled = true;
      roleInput.value = "";
      roleInput.disabled = true;

      if (!deptId || deptId === "default") return;

      fetch(
        `${base}/modules/recruitment/controllers/UserController.php?action=get_positions_roles&department_id=${deptId}`,
      )
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            console.error(data.message);
            return;
          }

          if (data.positions.length > 0) {
            data.positions.forEach((pos) => {
              const opt = document.createElement("option");
              opt.value = pos.position_id;
              opt.textContent = pos.position_name;
              posSelect.appendChild(opt);
            });
            posSelect.disabled = false;
          } else {
            posSelect.innerHTML =
              '<option value="">No positions available</option>';
          }

          roleInput.value =
            data.roles.length > 0
              ? data.roles[0].role_name
              : "No role assigned";
          roleInput.disabled = false;
        })
        .catch((err) => console.error("AJAX error:", err));
    });

    freshForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(freshForm);
      formData.append("action", "register_employee");

      fetch(`${base}/modules/recruitment/controllers/UserController.php`, {
        method: "POST",
        body: formData,
      })
        .then((res) => {
          if (!res.ok) throw new Error(`Server error: ${res.status}`);
          return res.text(); // ← text() first, not json()
        })
        .then((text) => {
          console.log("Raw response:", text); // ← add this temporarily to debug
          const data = JSON.parse(text); // ← now parse manually
          showAlert("registration-message", data.message, data.success);
          if (data.success) {
            freshForm.reset();
            posSelect.disabled = true;
            roleInput.disabled = true;
            loadEmployeeTable();
          }
        })
        .catch((err) => console.error("Submit error:", err));
    });
  }

  // ── Employee Management ───────────────────────────────────────────────────
  const manageForm = document.getElementById("manage-employee-form");

  if (manageForm) {
    const manageEmployeeSelect = document.getElementById("manage-employee");
    const manageFullname = document.getElementById("manage-fullname");
    const manageDeptSelect = document.getElementById("manage-department");
    const managePosSelect = document.getElementById("manage-position");
    const manageRoleInput = document.getElementById("manage-role");
    const manageSubmitBtn = document.getElementById("manage-submit-btn");

    function populatePositions(positions, selectedId = null) {
      managePosSelect.innerHTML = '<option value="">Select Position</option>';
      positions.forEach((pos) => {
        const opt = document.createElement("option");
        opt.value = pos.position_id;
        opt.textContent = pos.position_name;
        if (selectedId && pos.position_id == selectedId) opt.selected = true;
        managePosSelect.appendChild(opt);
      });
      managePosSelect.disabled = positions.length === 0;
    }

    function resetManageForm() {
      manageFullname.value = "";
      manageDeptSelect.value = "default";
      manageDeptSelect.disabled = true;
      managePosSelect.innerHTML = '<option value="">Select Position</option>';
      managePosSelect.disabled = true;
      manageRoleInput.value = "";
      manageRoleInput.disabled = true;
      manageSubmitBtn.disabled = true;
    }

    manageEmployeeSelect.addEventListener("change", function () {
      const employeeId = this.value;
      resetManageForm();

      if (!employeeId || employeeId === "default") return;

      fetch(
        `${base}/modules/recruitment/controllers/UserController.php?action=get_employee_details&employee_id=${employeeId}`,
      )
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            showAlert("management-message", data.message, false);
            return;
          }

          const e = data.employee;
          manageFullname.value = [e.first_name, e.middle_name, e.last_name]
            .filter(Boolean)
            .join(" ");

          if (e.department) {
            manageDeptSelect.value = e.department;
            manageDeptSelect.disabled = false;
          }

          populatePositions(data.positions, e.position);
          manageRoleInput.value = e.role_name || "No role assigned";
          manageRoleInput.disabled = false;
          manageSubmitBtn.disabled = false;
        })
        .catch((err) => console.error("Fetch employee error:", err));
    });

    manageDeptSelect.addEventListener("change", function () {
      const deptId = this.value;

      managePosSelect.innerHTML = '<option value="">Select Position</option>';
      managePosSelect.disabled = true;
      manageRoleInput.value = "";

      if (!deptId || deptId === "default") return;

      fetch(
        `${base}/modules/recruitment/controllers/UserController.php?action=get_positions_roles&department_id=${deptId}`,
      )
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            console.error(data.message);
            return;
          }
          populatePositions(data.positions);
          manageRoleInput.value =
            data.roles.length > 0
              ? data.roles[0].role_name
              : "No role assigned";
          manageRoleInput.disabled = false;
        })
        .catch((err) => console.error("AJAX error:", err));
    });

    manageForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(manageForm);
      formData.append("action", "update_employee");

      fetch(`${base}/modules/recruitment/controllers/UserController.php`, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          showAlert("management-message", data.message, data.success);
          if (data.success) loadEmployeeTable(); // ← refresh after update
        })
        .catch((err) => console.error("Update error:", err));
    });
  }
});
