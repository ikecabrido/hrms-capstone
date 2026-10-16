/**
 * =========================================================
 * RECRUITMENT APPLICATION MODULE
 * =========================================================
 */

var jobs = [];
var applications = [];
var currentJobId = null;

/**
 * =========================================================
 * SUBMISSION LOCK
 * =========================================================
 *
 * Prevents the same application from being submitted
 * multiple times while the POST request is processing.
 *
 * =========================================================
 */
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

  /**
   * -------------------------------------------------------
   * FORM DATA
   * -------------------------------------------------------
   */
  if (options.body instanceof FormData) {
    requestOptions.body = options.body;
  } else if (options.body !== undefined) {
    /**
     * -------------------------------------------------------
     * JSON
     * -------------------------------------------------------
     */
    requestOptions.headers = {
      "Content-Type": "application/json",
    };

    requestOptions.body =
      typeof options.body === "string"
        ? options.body
        : JSON.stringify(options.body);
  }

  /**
   * -------------------------------------------------------
   * SEND REQUEST
   * -------------------------------------------------------
   */
  var response = await fetch(
    "../controller/" + endpoint + "?action=" + encodeURIComponent(action),
    requestOptions,
  );

  /**
   * -------------------------------------------------------
   * READ RESPONSE
   * -------------------------------------------------------
   */
  var data;

  try {
    data = await response.json();
  } catch (error) {
    throw new Error("The server returned an invalid response.");
  }

  /**
   * -------------------------------------------------------
   * HTTP ERROR
   * -------------------------------------------------------
   */
  if (!response.ok) {
    throw new Error(data.error || data.message || "Request failed");
  }

  /**
   * -------------------------------------------------------
   * APPLICATION ERROR
   * -------------------------------------------------------
   */
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
    return j.id == jobId;
  });

  if (!job) {
    return;
  }

  /**
   * -------------------------------------------------------
   * Reset submission lock
   * -------------------------------------------------------
   *
   * A new application can be submitted when the modal
   * is opened again.
   */
  isSubmittingApplication = false;

  currentJobId = jobId;

  /**
   * -------------------------------------------------------
   * Job information
   * -------------------------------------------------------
   */

  var titleElement = document.getElementById("applyModalTitle");

  if (titleElement) {
    titleElement.textContent = "Apply — " + job.title;
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

  /**
   * -------------------------------------------------------
   * Reset form
   * -------------------------------------------------------
   */

  var applyForm = document.getElementById("applyForm");

  if (applyForm) {
    applyForm.reset();

    /**
     * Reset submit button.
     */
    var submitButton = applyForm.querySelector(
      'button[type="submit"], input[type="submit"]',
    );

    if (submitButton) {
      submitButton.disabled = false;

      if (submitButton.tagName === "BUTTON") {
        submitButton.textContent =
          submitButton.dataset.originalText || "Submit Application";
      }
    }
  }

  /**
   * -------------------------------------------------------
   * Reset file labels
   * -------------------------------------------------------
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

  /**
   * -------------------------------------------------------
   * Show form
   * -------------------------------------------------------
   */

  var formWrap = document.getElementById("applyFormWrap");

  if (formWrap) {
    formWrap.style.display = "block";
  }

  /**
   * -------------------------------------------------------
   * Hide success message
   * -------------------------------------------------------
   */

  var successWrap = document.getElementById("applySuccessWrap");

  if (successWrap) {
    successWrap.style.display = "none";
  }

  /**
   * -------------------------------------------------------
   * Open modal
   * -------------------------------------------------------
   */

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
 * HANDLE APPLICATION SUBMISSION
 * =========================================================
 */
async function handleApplySubmit(e) {
  /**
   * -------------------------------------------------------
   * PREVENT NORMAL FORM SUBMISSION
   * -------------------------------------------------------
   */
  e.preventDefault();

  /**
   * -------------------------------------------------------
   * DUPLICATE SUBMISSION PROTECTION
   * -------------------------------------------------------
   *
   * This is the most important addition.
   *
   * Once the first submit starts, every additional click
   * or submit event is ignored until the request finishes.
   */
  if (isSubmittingApplication) {
    console.warn("Duplicate application submission blocked.");

    return;
  }

  isSubmittingApplication = true;

  /**
   * -------------------------------------------------------
   * FORM
   * -------------------------------------------------------
   */
  var applyForm = document.getElementById("applyForm");

  /**
   * -------------------------------------------------------
   * SUBMIT BUTTON
   * -------------------------------------------------------
   */
  var submitButton = null;

  if (applyForm) {
    submitButton = applyForm.querySelector(
      'button[type="submit"], input[type="submit"]',
    );
  }

  /**
   * -------------------------------------------------------
   * DISABLE SUBMIT BUTTON
   * -------------------------------------------------------
   */
  if (submitButton) {
    submitButton.disabled = true;

    if (submitButton.tagName === "BUTTON") {
      submitButton.dataset.originalText = submitButton.textContent;

      submitButton.textContent = "Submitting...";
    }
  }

  /**
   * =======================================================
   * FIND CURRENT JOB
   * =======================================================
   */
  var job = jobs.find(function (j) {
    return j.id == currentJobId;
  });

  if (!job) {
    isSubmittingApplication = false;

    if (submitButton) {
      submitButton.disabled = false;

      if (submitButton.tagName === "BUTTON") {
        submitButton.textContent =
          submitButton.dataset.originalText || "Submit Application";
      }
    }

    alert("This posting is no longer available.");

    closeModal("applyModal");

    return;
  }

  /**
   * =======================================================
   * GET APPLICANT NAME
   * =======================================================
   */

  var firstName = getApplicationInputValue("apFirstName");

  var middleName = getApplicationInputValue("apMiddleName");

  var lastName = getApplicationInputValue("apLastName");

  /**
   * -------------------------------------------------------
   * Legacy name field support
   * -------------------------------------------------------
   */

  var legacyName = getApplicationInputValue("apName");

  if (!firstName && legacyName) {
    var legacyParts = legacyName.split(/\s+/);

    firstName = legacyParts.shift() || "";

    lastName = legacyParts.pop() || "";

    middleName = legacyParts.join(" ");
  }

  /**
   * =======================================================
   * PERSONAL INFORMATION
   * =======================================================
   */

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

  /**
   * =======================================================
   * EDUCATION
   * =======================================================
   */

  var school = getApplicationInputValue("apSchool");

  var degree = getApplicationInputValue("apDegree");

  var yearGraduated = getApplicationInputValue("apYear");

  /**
   * =======================================================
   * EXPERIENCE
   * =======================================================
   */

  var prevEmployer = getApplicationInputValue("apEmployer");

  var prevPosition = getApplicationInputValue("apPrevPosition");

  var expStart = getApplicationInputValue("apStart");

  var expEnd = getApplicationInputValue("apEnd");

  var expDesc = getApplicationInputValue("apExpDesc");

  /**
   * =======================================================
   * FILES
   * =======================================================
   */

  var resumeFile = getApplicationFile("apResume");

  var diplomaFile = getApplicationFile("apDiploma");

  var torFile = getApplicationFile("apTor");

  var certsFile = getApplicationFile("apCerts");

  var otherFile = getApplicationFile("apOther");

  /**
   * =======================================================
   * CREATE SUCCESS INFORMATION
   * =======================================================
   *
   * This is only for the success screen.
   * The actual database ID comes from PHP.
   */
  var app = {
    ref: refNumber(),

    jobId: job.id,

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

  /**
   * =======================================================
   * CREATE FORMDATA
   * =======================================================
   */

  var formData = new FormData();

  /**
   * -------------------------------------------------------
   * APPLICATION INFORMATION
   * -------------------------------------------------------
   */

  formData.append("jobId", job.id);

  formData.append("firstName", firstName);

  formData.append("middleName", middleName);

  formData.append("lastName", lastName);

  formData.append("age", age);

  formData.append("gender", gender);

  formData.append("birthplace", birthplace);

  formData.append("birthdate", birthdate);

  formData.append("civilStatus", civilStatus);

  formData.append("citizenship", citizenship);

  formData.append("religion", religion);

  formData.append("contact", contact);

  formData.append("email", email);

  formData.append("address", address);

  formData.append("currentAddress", address);

  formData.append("permanentAddress", permanentAddress);

  /**
   * -------------------------------------------------------
   * EDUCATION
   * -------------------------------------------------------
   */

  formData.append("school", school);

  formData.append("degree", degree);

  formData.append("yearGraduated", yearGraduated);

  /**
   * -------------------------------------------------------
   * EXPERIENCE
   * -------------------------------------------------------
   */

  formData.append("company", prevEmployer);

  formData.append("position", prevPosition);

  formData.append("startDate", expStart);

  formData.append("endDate", expEnd);

  formData.append("experienceDescription", expDesc);

  /**
   * =======================================================
   * FILE UPLOADS
   * =======================================================
   */

  if (resumeFile) {
    formData.append("resume", resumeFile);
  }

  if (diplomaFile) {
    formData.append("diploma", diplomaFile);
  }

  if (torFile) {
    formData.append("tor", torFile);
  }

  if (certsFile) {
    formData.append("certs", certsFile);
  }

  if (otherFile) {
    formData.append("other", otherFile);
  }

  /**
   * =======================================================
   * SUBMIT APPLICATION
   * =======================================================
   */

  try {
    var submitResponse = await apiRequest("applications", {
      method: "POST",
      body: formData,
    });

    /**
     * -----------------------------------------------------
     * IMPORTANT
     * -----------------------------------------------------
     *
     * Use the database application ID returned by PHP
     * when available.
     */
    if (submitResponse && submitResponse.application_id) {
      app.id = submitResponse.application_id;
    }

    /**
     * -----------------------------------------------------
     * Refresh application list
     * -----------------------------------------------------
     */
    applications = await apiRequest("applications");
  } catch (error) {
    console.error("Application submission error:", error);

    /**
     * Allow retry after failed request.
     */
    isSubmittingApplication = false;

    if (submitButton) {
      submitButton.disabled = false;

      if (submitButton.tagName === "BUTTON") {
        submitButton.textContent =
          submitButton.dataset.originalText || "Submit Application";
      }
    }

    alert(
      error.message === "This job has reached its maximum number of applicants."
        ? "Hindi na available ang posisyong ito dahil puno na ang bilang ng applicants."
        : "Hindi ma-submit ang application: " + error.message,
    );

    return;
  }

  /**
   * =======================================================
   * SUCCESS SCREEN
   * =======================================================
   */

  var formWrap = document.getElementById("applyFormWrap");

  if (formWrap) {
    formWrap.style.display = "none";
  }

  var successWrap = document.getElementById("applySuccessWrap");

  if (successWrap) {
    successWrap.style.display = "block";
  }

  /**
   * -------------------------------------------------------
   * APPLICATION REFERENCE
   * -------------------------------------------------------
   */

  var successRef = document.getElementById("successRef");

  if (successRef) {
    successRef.textContent = app.ref;
  }

  /**
   * -------------------------------------------------------
   * APPLICANT NAME
   * -------------------------------------------------------
   */

  var applicantName = [app.firstName, app.middleName, app.lastName]
    .filter(Boolean)
    .join(" ");

  /**
   * -------------------------------------------------------
   * SUCCESS MESSAGE
   * -------------------------------------------------------
   */

  var successText = document.getElementById("successText");

  if (successText) {
    successText.textContent =
      "Thank you, " +
      applicantName +
      ". Your application for " +
      job.title +
      " has been received. The HR office will contact you through " +
      app.email +
      " or " +
      app.contact +
      " if you are shortlisted.";
  }

  /**
   * -------------------------------------------------------
   * Keep submission locked.
   *
   * A new submission becomes possible when openApply()
   * is called again.
   * -------------------------------------------------------
   */
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

    if (!applications || applications.length === 0) {
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

  /**
   * -----------------------------------------------------
   * Prevent duplicate event handlers.
   * -----------------------------------------------------
   */
  if (applyForm.dataset.submitHandlerAttached === "true") {
    console.warn("Application submit handler already attached.");

    return;
  }

  applyForm.addEventListener("submit", handleApplySubmit);

  applyForm.dataset.submitHandlerAttached = "true";

  console.log("Application form submit handler attached.");
});
