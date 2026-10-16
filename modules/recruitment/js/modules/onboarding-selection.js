document.addEventListener("DOMContentLoaded", function () {
  const applicantSelect = document.getElementById("applicantSelect");

  const fullName = document.getElementById("full_name");
  const baseJob = document.getElementById("base_job");
  const position = document.getElementById("position");
  const department = document.getElementById("department");

  // Make sure all required elements exist
  if (!applicantSelect || !fullName || !baseJob || !position || !department) {
    console.error("Onboarding auto-fill: Required elements not found.");
    return;
  }

  applicantSelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];

    // No applicant selected
    if (!this.value) {
      fullName.value = "";
      baseJob.value = "";
      position.value = "";
      department.value = "";

      return;
    }

    // Get data from selected option
    const name = selectedOption.dataset.name || "";
    const job = selectedOption.dataset.job || "";
    const selectedPosition = selectedOption.dataset.position || "";
    const selectedDepartment = selectedOption.dataset.department || "";

    // Auto-fill fields
    fullName.value = name;
    baseJob.value = job;
    position.value = selectedPosition;
    department.value = selectedDepartment;

    // Highlight auto-filled fields
    const fields = [fullName, baseJob, position, department];

    fields.forEach(function (field) {
      field.style.borderColor = "#93c5fd";

      setTimeout(function () {
        field.style.borderColor = "#e2e8f0";
      }, 1000);
    });

    console.log("Applicant selected:", {
      applicationId: this.value,
      name: name,
      baseJob: job,
      position: selectedPosition,
      department: selectedDepartment,
    });
  });
});
