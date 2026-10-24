// modules/offered-list.js

// =========================
// OPEN APPLICANT MODAL
// =========================

document.addEventListener("click", function (event) {
  const row = event.target.closest(".candidate-row");

  if (!row) {
    return;
  }

  // Don't open modal when clicking action buttons/forms
  if (event.target.closest(".action-group")) {
    return;
  }

  const modal = document.getElementById("applicantDetailsModal");

  if (!modal) {
    return;
  }

  const offerData = row.dataset.offer;

  if (!offerData) {
    return;
  }

  let offer;

  try {
    offer = JSON.parse(offerData);
  } catch (error) {
    console.error("Invalid offer data:", error);
    return;
  }

  // =========================
  // APPLICANT INFORMATION
  // =========================

  const candidateName =
    `${offer.first_name || ""} ${offer.last_name || ""}`.trim();

  const candidateNameElement = document.getElementById("modalCandidateName");

  const applicationIdElement = document.getElementById("modalApplicationId");

  const firstNameElement = document.getElementById("modalFirstName");

  const lastNameElement = document.getElementById("modalLastName");

  if (candidateNameElement) {
    candidateNameElement.textContent = candidateName || "Applicant Details";
  }

  if (applicationIdElement) {
    applicationIdElement.textContent = `Application #${offer.application_id || "-"}`;
  }

  if (firstNameElement) {
    firstNameElement.textContent = offer.first_name || "-";
  }

  if (lastNameElement) {
    lastNameElement.textContent = offer.last_name || "-";
  }

  // =========================
  // POSITION DETAILS
  // =========================

  const positionElement = document.getElementById("modalPosition");

  const baseJobElement = document.getElementById("modalBaseJob");

  if (positionElement) {
    positionElement.textContent = offer.position || "-";
  }

  if (baseJobElement) {
    baseJobElement.textContent = offer.base_job || "-";
  }

  // =========================
  // OFFER DETAILS
  // =========================

  const salaryElement = document.getElementById("modalSalary");

  const statusElement = document.getElementById("modalOfferStatus");

  const salary = Number(offer.salary || 0);

  if (salaryElement) {
    salaryElement.textContent =
      "₱" +
      salary.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
  }

  if (statusElement) {
    statusElement.textContent = offer.offer_status || "-";
  }

  // =========================
  // SET SELECTED OFFER ID
  // =========================

  const modalOfferId = document.getElementById("modalOfferId");

  if (modalOfferId) {
    modalOfferId.value = offer.offer_id || "";
  }

  // =========================
  // SHOW / HIDE HIRE BUTTON
  // =========================

  const hireButton = document.getElementById("hireApplicantButton");

  if (offer.offer_status === "Accepted" && Number(offer.is_hired || 0) !== 1) {
    hireButton.style.display = "inline-flex";
    hireButton.disabled = false;
  } else {
    hireButton.style.display = "none";
    hireButton.disabled = true;
  }

  // =========================
  // SHOW MODAL
  // =========================

  modal.classList.add("show");
});

// =========================
// CLOSE MODAL
// =========================

document.addEventListener("click", function (event) {
  if (event.target.closest("#closeApplicantModal")) {
    const modal = document.getElementById("applicantDetailsModal");

    if (modal) {
      modal.classList.remove("show");
    }
  }
});

// =========================
// CLICK OUTSIDE MODAL
// =========================

document.addEventListener("click", function (event) {
  const modal = document.getElementById("applicantDetailsModal");

  if (!modal) {
    return;
  }

  if (event.target === modal) {
    modal.classList.remove("show");
  }
});

// =========================
// ESC KEY
// =========================

document.addEventListener("keydown", function (event) {
  if (event.key !== "Escape") {
    return;
  }

  const modal = document.getElementById("applicantDetailsModal");

  if (modal) {
    modal.classList.remove("show");
  }
});
