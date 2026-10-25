document.addEventListener("DOMContentLoaded", function () {
  const applicantSelect = document.getElementById("applicantSelect");

  // This script only applies to the applicant-selection form.
  // Other recruitment pages may not contain these elements.
  if (!applicantSelect) {
    return;
  }

  const fields = {
    fullName: document.getElementById("full_name"),
    baseJob: document.getElementById("base_job"),
    position: document.getElementById("position"),
    department: document.getElementById("department"),
  };

  const missingFields = Object.entries(fields)
    .filter(([, element]) => !element)
    .map(([name]) => name);

  if (missingFields.length > 0) {
    console.error(
      "Onboarding auto-fill: Missing HTML elements:",
      missingFields,
    );
    return;
  }

  applicantSelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];

    if (!this.value) {
      Object.values(fields).forEach((field) => {
        field.value = "";
      });
      return;
    }

    fields.fullName.value = selectedOption.dataset.name || "";
    fields.baseJob.value = selectedOption.dataset.job || "";
    fields.position.value = selectedOption.dataset.position || "";
    fields.department.value = selectedOption.dataset.department || "";

    Object.values(fields).forEach((field) => {
      field.style.borderColor = "#93c5fd";

      setTimeout(() => {
        field.style.borderColor = "#e2e8f0";
      }, 1000);
    });

    console.log("Applicant selected:", {
      applicationId: this.value,
      name: fields.fullName.value,
      baseJob: fields.baseJob.value,
      position: fields.position.value,
      department: fields.department.value,
    });
  });
});
