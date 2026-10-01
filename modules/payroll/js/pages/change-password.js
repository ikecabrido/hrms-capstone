/**
 * Change Password
 * ---------------------------------------------------------------------
 * Talks to modules/payroll/controllers/accountController.php (AJAX/JSON),
 * following the same self-dispatching endpoint pattern as the other
 * controllers (e.g. periodController.php, allowanceDeductionController.php).
 *
 * This file is imported once by js/script.js. It re-binds its listeners
 * every time the "page:loaded" event fires, and exits early if the
 * Change Password markup isn't present on the current page.
 */

function initChangePassword() {
  const root = document.getElementById("changePasswordPage");
  if (!root) return; // Not on the Change Password page — nothing to do.
  if (root.dataset.cpInitialized === "true") return;
  root.dataset.cpInitialized = "true";

  const ENDPOINT = "controllers/accountController.php";

  // ---- Element references -------------------------------------------------
  const alertBox = document.getElementById("cpAlert");
  const form = document.getElementById("cpForm");

  const currentPasswordInput = document.getElementById("cpCurrentPassword");
  const newPasswordInput = document.getElementById("cpNewPassword");
  const confirmPasswordInput = document.getElementById("cpConfirmPassword");

  const strengthFill = document.getElementById("cpStrengthFill");
  const strengthLabel = document.getElementById("cpStrengthLabel");
  const requirementItems = document.querySelectorAll("#cpRequirements li");
  const matchMsg = document.getElementById("cpMatchMsg");

  const saveBtn = document.getElementById("cpSaveBtn");
  const resetBtn = document.getElementById("cpResetBtn");

  const RULES = {
    length: (v) => v.length >= 8,
    upper: (v) => /[A-Z]/.test(v),
    number: (v) => /[0-9]/.test(v),
    special: (v) => /[^A-Za-z0-9]/.test(v),
  };

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

  function evaluateRequirements(value) {
    let passed = 0;
    requirementItems.forEach((li) => {
      const rule = li.getAttribute("data-rule");
      const ok = RULES[rule] ? RULES[rule](value) : false;
      li.classList.toggle("cp-rule-met", ok);
      if (ok) passed++;
    });
    return passed;
  }

  function updateStrength() {
    const value = newPasswordInput.value;

    if (!value) {
      evaluateRequirements(value);
      strengthFill.style.width = "0%";
      strengthFill.className = "";
      strengthLabel.textContent = "";
      return;
    }

    const passed = evaluateRequirements(value);
    const levels = [
      { pct: 25, label: "Weak", cls: "cp-strength-weak" },
      { pct: 50, label: "Fair", cls: "cp-strength-fair" },
      { pct: 75, label: "Good", cls: "cp-strength-good" },
      { pct: 100, label: "Strong", cls: "cp-strength-strong" },
    ];
    const level = levels[Math.max(0, Math.min(passed, 4)) - 1] || levels[0];

    strengthFill.style.width = level.pct + "%";
    strengthFill.className = level.cls;
    strengthLabel.textContent = level.label;
  }

  function updateMatch() {
    const newVal = newPasswordInput.value;
    const confirmVal = confirmPasswordInput.value;

    if (!confirmVal) {
      matchMsg.textContent = "";
      matchMsg.className = "cp-match-msg";
      return;
    }

    if (newVal === confirmVal) {
      matchMsg.textContent = "Passwords match";
      matchMsg.className = "cp-match-msg cp-match-ok";
    } else {
      matchMsg.textContent = "Passwords do not match";
      matchMsg.className = "cp-match-msg cp-match-bad";
    }
  }

  function togglePasswordVisibility(e) {
    const btn = e.currentTarget;
    const targetId = btn.getAttribute("data-target");
    const input = document.getElementById(targetId);
    if (!input) return;

    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";

    const icon = btn.querySelector("i");
    if (icon) {
      icon.classList.toggle("fa-eye", !isHidden);
      icon.classList.toggle("fa-eye-slash", isHidden);
    }
    btn.setAttribute(
      "aria-label",
      isHidden ? "Hide password" : "Show password",
    );
  }

  function resetFormState() {
    evaluateRequirements("");
    strengthFill.style.width = "0%";
    strengthFill.className = "";
    strengthLabel.textContent = "";
    matchMsg.textContent = "";
    matchMsg.className = "cp-match-msg";
  }

  async function handleSubmit(e) {
    e.preventDefault();

    if (newPasswordInput.value !== confirmPasswordInput.value) {
      showAlert("New password and confirmation do not match.", "error");
      return;
    }

    if (evaluateRequirements(newPasswordInput.value) < 4) {
      showAlert(
        "Please meet all password requirements before saving.",
        "error",
      );
      return;
    }

    saveBtn.disabled = true;
    const originalHtml = saveBtn.innerHTML;
    saveBtn.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Updating&hellip;';

    try {
      const formData = new FormData(form);
      formData.append("action", "changePassword");

      const res = await fetch(ENDPOINT, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });
      const json = await res.json();

      if (!json.success) {
        showAlert(json.message || "Failed to change password.", "error");
        return;
      }

      showAlert(json.message || "Password changed successfully.", "success");
      form.reset();
      resetFormState();
    } catch (err) {
      console.error("Change password error", err);
      showAlert(
        "Something went wrong while saving. Please try again.",
        "error",
      );
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalHtml;
    }
  }

  // ---- Wire up ----------------------------------------------------------
  newPasswordInput.addEventListener("input", () => {
    updateStrength();
    updateMatch();
  });
  confirmPasswordInput.addEventListener("input", updateMatch);

  document.querySelectorAll(".cp-toggle-visibility").forEach((btn) => {
    btn.addEventListener("click", togglePasswordVisibility);
  });

  form.addEventListener("submit", handleSubmit);
  resetBtn.addEventListener("click", () =>
    window.setTimeout(resetFormState, 0),
  );
}

window.addEventListener("page:loaded", initChangePassword);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initChangePassword);
} else {
  initChangePassword();
}
