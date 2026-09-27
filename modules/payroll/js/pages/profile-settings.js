/**
 * Profile Settings
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/profileController.php (AJAX/JSON),
 * following the same self-dispatching endpoint pattern as the other
 * controllers (e.g. periodController.php, allowanceDeductionController.php).
 *
 * This file is imported once by js/script.js. It re-binds its listeners
 * every time the "page:loaded" event fires, and exits early if the
 * Profile Settings markup isn't present on the current page.
 */

function initProfileSettings() {
  const root = document.getElementById("profileSettingsPage");
  if (!root) return; // Not on the Profile Settings page — nothing to do.
  if (root.dataset.pfsInitialized === "true") return;
  root.dataset.pfsInitialized = "true";

  const ENDPOINT = "controllers/profileController.php";

  let currentProfile = null;

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("pfsAlert");

  const avatarEl = document.getElementById("pfsAvatar");
  const fullNameEl = document.getElementById("pfsFullName");
  const positionEl = document.getElementById("pfsPosition");
  const employeeCodeEl = document.getElementById("pfsEmployeeCode");
  const departmentEl = document.getElementById("pfsDepartment");
  const statusEl = document.getElementById("pfsStatus");

  const infoCode = document.getElementById("pfsInfoCode");
  const infoDepartment = document.getElementById("pfsInfoDepartment");
  const infoPosition = document.getElementById("pfsInfoPosition");
  const infoType = document.getElementById("pfsInfoType");
  const infoStatus = document.getElementById("pfsInfoStatus");
  const infoHireDate = document.getElementById("pfsInfoHireDate");
  const infoLastLogin = document.getElementById("pfsInfoLastLogin");

  const form = document.getElementById("pfsForm");
  const emailInput = document.getElementById("pfsEmail");
  const mobileInput = document.getElementById("pfsMobile");
  const phoneInput = document.getElementById("pfsPhone");
  const currentAddressInput = document.getElementById("pfsCurrentAddress");
  const permanentAddressInput = document.getElementById("pfsPermanentAddress");

  const resetBtn = document.getElementById("pfsResetBtn");
  const saveBtn = document.getElementById("pfsSaveBtn");

  // ---- Helpers --------------------------------------------------------------

  function showAlert(message, type) {
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className = "pm-alert pm-alert-" + type;
    alertBox.style.display = "block";
    window.scrollTo({ top: 0, behavior: "smooth" });
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(() => {
      alertBox.style.display = "none";
    }, 5000);
  }

  function formatDate(value) {
    if (!value) return "&mdash;";
    const d = new Date(value);
    if (isNaN(d.getTime())) return "&mdash;";
    return d.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function formatDateTime(value) {
    if (!value) return "Never";
    const d = new Date(value.replace(" ", "T"));
    if (isNaN(d.getTime())) return "Never";
    return d.toLocaleString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });
  }

  function fillForm(profile) {
    emailInput.value = profile.email || "";
    mobileInput.value = profile.mobile_no || "";
    phoneInput.value = profile.phone_no || "";
    currentAddressInput.value = profile.current_address || "";
    permanentAddressInput.value = profile.permanent_address || "";
  }

  function renderProfile(profile) {
    currentProfile = profile;

    const fullName = [
      profile.first_name,
      profile.middle_name,
      profile.last_name,
    ]
      .filter(Boolean)
      .join(" ");

    avatarEl.textContent = (profile.first_name || "?").charAt(0).toUpperCase();
    fullNameEl.textContent = fullName || "Unknown Employee";
    positionEl.textContent = profile.position_name || "No position assigned";

    employeeCodeEl.innerHTML =
      '<i class="fa-solid fa-id-badge"></i> ' +
      (profile.employee_code || "&mdash;");
    departmentEl.innerHTML =
      '<i class="fa-solid fa-building"></i> ' +
      (profile.department_name || "Unassigned");

    const status = profile.employment_status || "Unknown";
    statusEl.textContent = status;
    statusEl.className =
      "pfs-badge pfs-badge-status pfs-status-" + status.toLowerCase();

    infoCode.textContent = profile.employee_code || "\u2014";
    infoDepartment.textContent = profile.department_name || "Unassigned";
    infoPosition.textContent = profile.position_name || "Unassigned";
    infoType.textContent = profile.employment_type || "\u2014";
    infoStatus.textContent = profile.employment_status || "\u2014";
    infoHireDate.innerHTML = formatDate(profile.hire_date);
    infoLastLogin.textContent = formatDateTime(profile.last_login);

    fillForm(profile);
  }

  async function loadProfile() {
    try {
      const res = await fetch(`${ENDPOINT}?action=get`, {
        credentials: "same-origin",
      });
      const json = await res.json();

      if (!json.success) {
        showAlert(json.message || "Failed to load profile.", "error");
        return;
      }

      renderProfile(json.data);
    } catch (err) {
      console.error("Profile load error", err);
      showAlert("Failed to load profile. Please refresh the page.", "error");
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();

    saveBtn.disabled = true;
    const originalHtml = saveBtn.innerHTML;
    saveBtn.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Saving&hellip;';

    try {
      const formData = new FormData(form);
      formData.append("action", "update");

      const res = await fetch(ENDPOINT, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });
      const json = await res.json();

      if (!json.success) {
        showAlert(json.message || "Failed to update profile.", "error");
        return;
      }

      showAlert(json.message || "Profile updated successfully.", "success");
      loadProfile();
    } catch (err) {
      console.error("Profile update error", err);
      showAlert(
        "Something went wrong while saving. Please try again.",
        "error",
      );
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalHtml;
    }
  }

  function handleReset() {
    if (currentProfile) fillForm(currentProfile);
  }

  // ---- Wire up ----------------------------------------------------------
  form.addEventListener("submit", handleSubmit);
  resetBtn.addEventListener("click", handleReset);

  loadProfile();
}

window.addEventListener("page:loaded", initProfileSettings);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initProfileSettings);
} else {
  initProfileSettings();
}
