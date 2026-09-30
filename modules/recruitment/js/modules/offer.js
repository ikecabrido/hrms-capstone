function autoFillJobAndSalary() {
  const select = document.getElementById("applicationSelect");

  const jobDisplay = document.getElementById("jobTitleDisplay");
  const jobHidden = document.getElementById("jobTitle");
  const positionInput = document.getElementById("positionInput");
  const salarySelect = document.getElementById("salaryOffer");
  const departmentInput = document.getElementById("departmentInput");

  const minimumOption = document.getElementById("minimumSalaryOption");
  const midpointOption = document.getElementById("midpointSalaryOption");
  const maximumOption = document.getElementById("maximumSalaryOption");

  if (
    !select ||
    !jobDisplay ||
    !jobHidden ||
    !positionInput ||
    !salarySelect ||
    !departmentInput
  ) {
    return;
  }

  const selectedOption = select.options[select.selectedIndex];

  // No applicant selected
  if (!selectedOption || !selectedOption.value) {
    jobDisplay.value = "";
    jobHidden.value = "";
    positionInput.value = "";
    departmentInput.value = "";

    salarySelect.value = "";

    if (minimumOption) {
      minimumOption.value = "";
      minimumOption.textContent = "Minimum";
    }

    if (midpointOption) {
      midpointOption.value = "";
      midpointOption.textContent = "Midpoint";
    }

    if (maximumOption) {
      maximumOption.value = "";
      maximumOption.textContent = "Maximum";
    }

    return;
  }

  // Get applicant/job information
  const job = selectedOption.dataset.job || "";
  const position = selectedOption.dataset.position || "";
  const department = selectedOption.dataset.department || "";

  // Get salary structure
  const minimumSalary = parseFloat(selectedOption.dataset.minimumSalary) || 0;

  const midpointSalary = parseFloat(selectedOption.dataset.midpointSalary) || 0;

  const maximumSalary = parseFloat(selectedOption.dataset.maximumSalary) || 0;

  // Fill job information
  jobDisplay.value = job;
  jobHidden.value = job;
  positionInput.value = position;
  departmentInput.value = department;

  // Minimum
  if (minimumOption && minimumSalary > 0) {
    minimumOption.value = minimumSalary.toFixed(2);
    minimumOption.textContent = "Minimum - ₱" + formatSalary(minimumSalary);
  }

  // Midpoint
  if (midpointOption && midpointSalary > 0) {
    midpointOption.value = midpointSalary.toFixed(2);
    midpointOption.textContent = "Midpoint - ₱" + formatSalary(midpointSalary);
  }

  // Maximum
  if (maximumOption && maximumSalary > 0) {
    maximumOption.value = maximumSalary.toFixed(2);
    maximumOption.textContent = "Maximum - ₱" + formatSalary(maximumSalary);
  }

  // Default to midpoint
  if (midpointSalary > 0) {
    salarySelect.value = midpointSalary.toFixed(2);
  } else if (minimumSalary > 0) {
    salarySelect.value = minimumSalary.toFixed(2);
  } else {
    salarySelect.value = "";
  }
}

/**
 * Format salary
 */
function formatSalary(amount) {
  return Number(amount).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

/**
 * Initialize offer form
 */
function initializeOfferForm() {
  const select = document.getElementById("applicationSelect");

  if (!select) {
    return;
  }

  if (select.dataset.offerInit !== "1") {
    select.addEventListener("change", autoFillJobAndSalary);

    select.dataset.offerInit = "1";
  }

  autoFillJobAndSalary();
}

document.addEventListener("DOMContentLoaded", function () {
  initializeOfferForm();
});

window.addEventListener("page:loaded", function (event) {
  if (event.detail?.page !== "offer") {
    return;
  }

  initializeOfferForm();
});

window.autoFillJobAndSalary = autoFillJobAndSalary;
