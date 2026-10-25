/**
 * =========================================================
 * RECRUITMENT APPLICATION MODULE
 * =========================================================
 */

var jobs = [];
var applications = [];
var currentJobId = null;
var isSubmittingApplication = false;

/**
 * =========================================================
 * GENERATE UNIQUE ID
 * =========================================================
 */
function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

/**
 * =========================================================
 * GENERATE APPLICATION REFERENCE NUMBER
 * =========================================================
 */
function refNumber() {
  var y = new Date().getFullYear();
  var n = Math.floor(10000 + Math.random() * 89999);

  return "BCP-" + y + "-" + n;
}

/**
 * =========================================================
 * ESCAPE HTML
 * =========================================================
 */
function escapeHtml(s) {
  if (s === undefined || s === null) {
    return "";
  }

  return String(s).replace(/[&<>"']/g, function (c) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[c];
  });
}

/**
 * =========================================================
 * API REQUEST
 * =========================================================
 */
async function apiRequest(action, options) {
  options = options || {};

  var endpoint =
    action === "applications"
      ? "applicationController.php"
      : "jobsController.php";

  var requestOptions = {
    method: options.method || "GET",
  };

  /*
   * For FormData, let the browser set the multipart
   * Content-Type and boundary automatically.
   */
  if (options.body instanceof FormData) {
    requestOptions.body = options.body;
  } else if (options.body !== undefined) {
    requestOptions.headers = {
      "Content-Type": "application/json",
    };

    requestOptions.body =
      typeof options.body === "string"
        ? options.body
        : JSON.stringify(options.body);
  }

  var response = await fetch(
    "../controller/" + endpoint + "?action=" + encodeURIComponent(action),
    requestOptions,
  );

  var data;

  try {
    data = await response.json();
  } catch (error) {
    throw new Error("The server returned an invalid response.");
  }

  if (!response.ok) {
    throw new Error(data.error || data.message || "Request failed");
  }

  if (data.success === false) {
    throw new Error(data.error || data.message || "Request failed");
  }

  return data;
}

/**
 * =========================================================
 * OPEN APPLY MODAL
 * =========================================================
 */
function openApply(jobId) {
  var job = jobs.find(function (j) {
    return String(j.id) === String(jobId);
  });

  if (
    !job ||
    job.id === undefined ||
    job.id === null ||
    String(job.id).trim() === ""
  ) {
    console.error("Invalid selected job:", jobId, job);
    alert("Unable to apply because the job ID is missing.");
    return;
  }

  isSubmittingApplication = false;
  currentJobId = job.id;

  console.log("Selected job:", job);
  console.log("Selected database job ID:", currentJobId);

  /*
   * JOB INFORMATION
   */
  var titleElement = document.getElementById("applyModalTitle");

  if (titleElement) {
    titleElement.textContent = "Apply — " + (job.title || "Position");
  }

  var jobTitleElement = document.getElementById("pfJobTitle");

  if (jobTitleElement) {
    jobTitleElement.textContent = job.title || "—";
  }

  var positionElement = document.getElementById("pfPosition");

  if (positionElement) {
    positionElement.textContent = job.position || "—";
  }

  var departmentElement = document.getElementById("pfDept");

  if (departmentElement) {
    departmentElement.textContent = job.department || job.dept || "—";
  }

  var typeElement = document.getElementById("pfType");

  if (typeElement) {
    typeElement.textContent = job.type || "—";
  }

  var locationElement = document.getElementById("pfLocation");

  if (locationElement) {
    locationElement.textContent = job.location || "—";
  }

  var salaryElement = document.getElementById("pfSalary");

  if (salaryElement) {
    salaryElement.textContent = job.salary || "Not specified";
  }

  var vacancyElement = document.getElementById("pfVacancy");

  if (vacancyElement) {
    vacancyElement.textContent =
      Number(job.max_applicants) > 0
        ? job.max_applicants + " applicant slots"
        : "Unlimited applicants";
  }

  var descriptionElement = document.getElementById("pfDesc");

  if (descriptionElement) {
    descriptionElement.textContent = job.desc || "—";
  }

  var qualificationElement = document.getElementById("pfQual");

  if (qualificationElement) {
    qualificationElement.textContent = job.qual || "—";
  }

  /*
   * RESET FORM
   */
  var applyForm = document.getElementById("applyForm");

  if (applyForm) {
    applyForm.reset();

    var submitButton = applyForm.querySelector(
      'button[type="submit"], input[type="submit"]',
    );

    if (submitButton) {
      submitButton.disabled = false;

      if (submitButton.tagName === "BUTTON") {
        submitButton.textContent =
          submitButton.dataset.originalText || "Submit application";
      }
    }
  }

  /*
   * RESTORE SELECTED JOB ID AFTER RESET
   */
  var jobIdInput = document.getElementById("jobId");

  if (jobIdInput) {
    jobIdInput.value = String(currentJobId);
  }

  /*
   * RESET FILE LABELS.
   * Hidden document inputs are safely ignored.
   */
  ["fn-resume", "fn-diploma", "fn-tor", "fn-certs", "fn-other"].forEach(
    function (id) {
      var el = document.getElementById(id);

      if (el) {
        el.textContent = "No file selected";
        el.classList.remove("picked");
      }
    },
  );

  /*
   * SHOW APPLICATION FORM
   */
  var formWrap = document.getElementById("applyFormWrap");

  if (formWrap) {
    formWrap.style.display = "block";
  }

  /*
   * HIDE SUCCESS MESSAGE
   */
  var successWrap = document.getElementById("applySuccessWrap");

  if (successWrap) {
    successWrap.style.display = "none";
  }

  openModal("applyModal");
}

/**
 * =========================================================
 * FILE PICKED
 * =========================================================
 */
function onFilePicked(inputId, labelId) {
  var input = document.getElementById(inputId);
  var label = document.getElementById(labelId);

  if (!input || !label) {
    return;
  }

  if (input.files && input.files.length > 0) {
    label.textContent = input.files[0].name;
    label.classList.add("picked");
  } else {
    label.textContent = "No file selected";
    label.classList.remove("picked");
  }
}

/**
 * =========================================================
 * GET APPLICATION INPUT VALUE
 * =========================================================
 */
function getApplicationInputValue(id) {
  var input = document.getElementById(id);

  return input ? input.value.trim() : "";
}

/**
 * =========================================================
 * GET ACTUAL FILE
 * =========================================================
 */
function getApplicationFile(id) {
  var input = document.getElementById(id);

  if (!input || !input.files || input.files.length === 0) {
    return null;
  }

  return input.files[0];
}

/**
 * =========================================================
 * RESTORE SUBMIT BUTTON AFTER ERROR
 * =========================================================
 */
function restoreApplicationSubmitButton(button) {
  isSubmittingApplication = false;

  if (!button) {
    return;
  }

  button.disabled = false;

  if (button.tagName === "BUTTON") {
    button.textContent = button.dataset.originalText || "Submit application";
  }
}

/**
 * =========================================================
 * HANDLE APPLICATION SUBMISSION
 * =========================================================
 */
async function handleApplySubmit(e) {
  e.preventDefault();

  /*
   * PREVENT DUPLICATE SUBMISSION
   */
  if (isSubmittingApplication) {
    console.warn("Duplicate application submission blocked.");
    return;
  }

  var applyForm = document.getElementById("applyForm");

  if (!applyForm) {
    alert("Application form not found.");
    return;
  }

  var submitButton = applyForm.querySelector(
    'button[type="submit"], input[type="submit"]',
  );

  /*
   * VALIDATE SELECTED JOB
   */
  var jobIdInput = document.getElementById("jobId");
  var selectedJobId = jobIdInput ? jobIdInput.value.trim() : "";

  if (!selectedJobId && currentJobId !== null && currentJobId !== undefined) {
    selectedJobId = String(currentJobId).trim();
  }

  if (!selectedJobId || selectedJobId === "null") {
    console.error("Missing job ID:", {
      currentJobId: currentJobId,
      hiddenJobId: jobIdInput ? jobIdInput.value : null,
    });

    alert("Missing job ID. Please close the form and select the job again.");
    return;
  }

  /*
   * FIND CURRENT JOB
   */
  var job = jobs.find(function (j) {
    return String(j.id) === String(selectedJobId);
  });

  if (!job) {
    console.error("Selected job was not found:", selectedJobId);

    alert("This job posting is no longer available. Please refresh the page.");
    return;
  }

  /*
   * VALIDATE RESUME BEFORE LOCKING SUBMISSION
   *
   * Resume / CV is the only required document.
   */
  var resumeFile = getApplicationFile("apResume");

  if (!resumeFile) {
    alert("Please attach your resume / CV.");
    return;
  }

  /*
   * LOCK SUBMISSION
   */
  isSubmittingApplication = true;

  if (submitButton) {
    submitButton.disabled = true;

    if (submitButton.tagName === "BUTTON") {
      submitButton.dataset.originalText = submitButton.textContent;
      submitButton.textContent = "Submitting...";
    }
  }

  /*
   * PERSONAL INFORMATION
   */
  var firstName = getApplicationInputValue("apFirstName");
  var middleName = getApplicationInputValue("apMiddleName");
  var lastName = getApplicationInputValue("apLastName");

  // Support older forms that use a single name field.
  var legacyName = getApplicationInputValue("apName");

  if (!firstName && legacyName) {
    var legacyParts = legacyName.split(/\s+/);

    firstName = legacyParts.shift() || "";
    lastName = legacyParts.pop() || "";
    middleName = legacyParts.join(" ");
  }

  var age = getApplicationInputValue("apAge");
  var gender = getApplicationInputValue("apGender");
  var birthplace = getApplicationInputValue("apBirthplace");
  var birthdate = getApplicationInputValue("apDob");
  var civilStatus = getApplicationInputValue("apCivilStatus");
  var citizenship = getApplicationInputValue("apCitizenship");
  var religion = getApplicationInputValue("apReligion");
  var contact = getApplicationInputValue("apContact");
  var email = getApplicationInputValue("apEmail");
  var address = getApplicationInputValue("apAddress");
  var permanentAddress = getApplicationInputValue("apPermanentAddress");

  /*
   * EDUCATION
   */
  var school = getApplicationInputValue("apSchool");
  var degree = getApplicationInputValue("apDegree");
  var yearGraduated = getApplicationInputValue("apYear");

  /*
   * EXPERIENCE
   */
  var prevEmployer = getApplicationInputValue("apEmployer");
  var prevPosition = getApplicationInputValue("apPrevPosition");
  var expStart = getApplicationInputValue("apStart");
  var expEnd = getApplicationInputValue("apEnd");
  var expDesc = getApplicationInputValue("apExpDesc");

  /*
   * APPLICATION DISPLAY INFORMATION
   */
  var app = {
    ref: refNumber(),
    jobId: selectedJobId,
    jobTitle: job.title,
    jobDept: job.department || job.dept || "",
    submittedAt: Date.now(),
    firstName: firstName,
    middleName: middleName,
    lastName: lastName,
    age: age,
    gender: gender,
    birthplace: birthplace,
    birthdate: birthdate,
    dob: birthdate,
    civilStatus: civilStatus,
    citizenship: citizenship,
    religion: religion,
    contact: contact,
    email: email,
    address: address,
    currentAddress: address,
    permanentAddress: permanentAddress,
    school: school,
    degree: degree,
    yearGraduated: yearGraduated,
    prevEmployer: prevEmployer,
    prevPosition: prevPosition,
    expStart: expStart,
    expEnd: expEnd,
    expDesc: expDesc,
  };

  /*
   * CREATE FORMDATA
   */
  var formData = new FormData();

  /*
   * APPLICATION AND JOB INFORMATION
   */
  formData.set("jobId", selectedJobId);

  formData.set("firstName", firstName);
  formData.set("middleName", middleName);
  formData.set("lastName", lastName);

  formData.set("age", age);
  formData.set("gender", gender);
  formData.set("birthplace", birthplace);
  formData.set("birthdate", birthdate);
  formData.set("civilStatus", civilStatus);
  formData.set("citizenship", citizenship);
  formData.set("religion", religion);

  formData.set("contact", contact);
  formData.set("email", email);

  formData.set("address", address);
  formData.set("currentAddress", address);
  formData.set("permanentAddress", permanentAddress);

  /*
   * EDUCATION
   */
  formData.set("school", school);
  formData.set("degree", degree);
  formData.set("yearGraduated", yearGraduated);

  /*
   * EXPERIENCE
   *
   * Send both company and employer for compatibility
   * with controllers using either field name.
   */
  formData.set("company", prevEmployer);
  formData.set("employer", prevEmployer);
  formData.set("position", prevPosition);
  formData.set("startDate", expStart);
  formData.set("endDate", expEnd);
  formData.set("experienceDescription", expDesc);

  /*
   * FILE UPLOADS
   *
   * TEMPORARY CONFIGURATION:
   * Resume / CV only.
   * Diploma, TOR, certificates, and other documents
   * are intentionally not accessed or submitted.
   */
  formData.set("resume", resumeFile);

  /*
   * DEBUG REQUEST BEFORE SUBMISSION
   */
  console.log("Submitting application:", {
    jobId: formData.get("jobId"),
    firstName: formData.get("firstName"),
    lastName: formData.get("lastName"),
    email: formData.get("email"),
    hasResume: formData.has("resume"),
  });

  /*
   * SUBMIT APPLICATION
   */
  try {
    var submitResponse = await apiRequest("applications", {
      method: "POST",
      body: formData,
    });

    /*
     * REFRESH APPLICATION LIST
     *
     * A failed refresh must not be reported as a failed
     * submission because the application may already
     * have been saved.
     */
    if (submitResponse && submitResponse.application_id) {
      app.id = submitResponse.application_id;
    } else if (submitResponse && submitResponse.id) {
      app.id = submitResponse.id;
    }

    try {
      applications = await apiRequest("applications");
    } catch (refreshError) {
      console.error(
        "Application submitted, but the list could not refresh:",
        refreshError,
      );
    }
  } catch (error) {
    console.error("Application submission error:", error);

    restoreApplicationSubmitButton(submitButton);

    if (
      error.message === "This job has reached its maximum number of applicants."
    ) {
      alert(
        "Hindi na available ang posisyong ito dahil puno na ang bilang ng applicants.",
      );
    } else {
      alert("Hindi ma-submit ang application: " + error.message);
    }

    return;
  }

  /*
   * SUCCESS SCREEN
   */
  var formWrap = document.getElementById("applyFormWrap");

  if (formWrap) {
    formWrap.style.display = "none";
  }

  var successWrap = document.getElementById("applySuccessWrap");

  if (successWrap) {
    successWrap.style.display = "block";
  }

  /*
   * APPLICATION REFERENCE
   */
  var successRef = document.getElementById("successRef");

  if (successRef) {
    successRef.textContent = app.ref;
  }

  /*
   * APPLICANT NAME
   */
  var applicantName = [app.firstName, app.middleName, app.lastName]
    .filter(Boolean)
    .join(" ");

  /*
   * SUCCESS MESSAGE
   */
  var successText = document.getElementById("successText");

  if (successText) {
    successText.textContent =
      "Thank you, " +
      applicantName +
      ". Your application for " +
      (job.title || "the selected position") +
      " has been received. The HR office will contact you through " +
      app.email +
      " or " +
      app.contact +
      " if you are shortlisted.";
  }

  // Keep submission locked until another job is selected.
}

/**
 * =========================================================
 * OPEN APPLICATIONS
 * =========================================================
 */
async function openApplications() {
  var body = document.getElementById("applicationsBody");

  if (!body) {
    return;
  }

  body.innerHTML = '<p class="form-note">Loading applications...</p>';

  openModal("applicationsModal");

  try {
    applications = await apiRequest("applications");

    if (!Array.isArray(applications) || applications.length === 0) {
      body.innerHTML =
        '<div class="empty-state">' +
        "<h3>No applications yet</h3>" +
        "<p>Submitted applications will appear here.</p>" +
        "</div>";

      return;
    }

    body.innerHTML = applications
      .map(function (app) {
        return (
          '<div class="app-row">' +
          "<div>" +
          '<div class="who">' +
          escapeHtml(app.full_name) +
          "</div>" +
          '<div class="what">' +
          escapeHtml(app.job_title) +
          " · " +
          escapeHtml(app.job_position) +
          " · " +
          escapeHtml(app.job_dept) +
          " · " +
          escapeHtml(app.email) +
          "</div>" +
          '<div class="ref">' +
          "Application #" +
          escapeHtml(app.id) +
          "</div>" +
          "</div>" +
          '<div class="ref">' +
          escapeHtml(app.created_at) +
          "</div>" +
          "</div>"
        );
      })
      .join("");
  } catch (error) {
    console.error("Unable to load applications:", error);

    body.innerHTML =
      '<div class="empty-state">' +
      "<h3>Unable to load applications</h3>" +
      "<p>" +
      escapeHtml(error.message) +
      "</p>" +
      "</div>";
  }
}

/**
 * =========================================================
 * ATTACH APPLICATION FORM SUBMIT EVENT
 * =========================================================
 */
document.addEventListener("DOMContentLoaded", function () {
  var applyForm = document.getElementById("applyForm");

  if (!applyForm) {
    console.warn("Application form #applyForm was not found.");
    return;
  }

  if (applyForm.dataset.submitHandlerAttached === "true") {
    console.warn("Application submit handler already attached.");
    return;
  }

  applyForm.addEventListener("submit", handleApplySubmit);

  applyForm.dataset.submitHandlerAttached = "true";

  console.log("Application form submit handler attached.");
});
