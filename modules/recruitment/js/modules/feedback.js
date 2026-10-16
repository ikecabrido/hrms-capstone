// ========================================
// FEEDBACK MODULE
// ========================================

let feedbackInitialized = false;

// ========================================
// INITIALIZE FEEDBACK
// ========================================

function initFeedback() {
  const form = document.getElementById("feedbackForm");

  // This page does not contain the feedback modal
  if (!form) {
    return;
  }

  // Prevent duplicate initialization
  if (feedbackInitialized) {
    return;
  }

  feedbackInitialized = true;

  const stars = document.querySelectorAll(".rating-star");
  const ratingInput = document.getElementById("rating_value");
  const starContainer = document.querySelector(".star-rating");

  // ========================================
  // STAR HOVER + CLICK
  // ========================================

  stars.forEach((star) => {
    star.addEventListener("mouseenter", function () {
      const rating = parseInt(this.dataset.rating);

      stars.forEach((s) => {
        const starRating = parseInt(s.dataset.rating);

        if (starRating <= rating) {
          s.classList.add("active");
        } else {
          s.classList.remove("active");
        }
      });
    });

    star.addEventListener("click", function () {
      const rating = parseInt(this.dataset.rating);

      ratingInput.value = rating;

      stars.forEach((s) => {
        const starRating = parseInt(s.dataset.rating);

        if (starRating <= rating) {
          s.classList.add("active");
        } else {
          s.classList.remove("active");
        }
      });

      console.log("Selected rating:", rating);
    });
  });

  // ========================================
  // MOUSE LEAVE
  // ========================================

  if (starContainer) {
    starContainer.addEventListener("mouseleave", function () {
      const selectedRating = parseInt(ratingInput.value) || 0;

      stars.forEach((star) => {
        const rating = parseInt(star.dataset.rating);

        if (rating <= selectedRating) {
          star.classList.add("active");
        } else {
          star.classList.remove("active");
        }
      });
    });
  }

  // ========================================
  // FORM SUBMIT
  // ========================================

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    console.log("Feedback form submitted");

    const rating = parseInt(document.getElementById("rating_value").value) || 0;

    const interviewId = document.getElementById("modal_inter_id").value;

    const applicationId = document.getElementById("modal_app_id").value;

    console.log("Interview ID:", interviewId);
    console.log("Application ID:", applicationId);
    console.log("Rating:", rating);

    // ========================================
    // VALIDATION
    // ========================================

    if (!interviewId || interviewId <= 0) {
      alert("Invalid interview ID.");

      return;
    }

    if (!applicationId || applicationId <= 0) {
      alert("Invalid application ID.");

      return;
    }

    if (rating < 1 || rating > 5) {
      alert("Please select a star rating.");

      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerText = "Saving...";

    // ========================================
    // FORM DATA
    // ========================================

    const formData = new FormData(form);

    // Debug
    console.log("Sending feedback:");

    for (const [key, value] of formData.entries()) {
      console.log(key, "=", value);
    }

    // ========================================
    // SEND TO PHP
    // ========================================

    try {
      const response = await fetch("index.php?page=submit-feedback", {
        method: "POST",
        body: formData,
      });

      console.log("HTTP status:", response.status);

      const text = await response.text();

      console.log("Server response:", text);

      let data;

      try {
        data = JSON.parse(text);
      } catch (jsonError) {
        console.error("Invalid JSON response:", text);

        throw new Error("Server did not return valid JSON.");
      }

      // ========================================
      // SUCCESS
      // ========================================

      if (data.success) {
        console.log("Feedback successfully saved.");

        closeFeedbackModal();

        const toast = document.getElementById("statusToast");

        if (toast) {
          toast.style.display = "block";
        }

        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } else {
        alert(data.message || "Feedback could not be saved.");

        submitBtn.disabled = false;
        submitBtn.innerText = "Save Feedback";
      }
    } catch (error) {
      console.error("Feedback submission error:", error);

      alert("Unable to save feedback. Check the browser console.");

      submitBtn.disabled = false;
      submitBtn.innerText = "Save Feedback";
    }
  });
}

// ========================================
// OPEN MODAL
// ========================================

window.openFeedbackModal = function (interviewId, appId) {
  console.log("Opening feedback:", interviewId, appId);

  document.getElementById("modal_inter_id").value = interviewId;

  document.getElementById("modal_app_id").value = appId;

  document.getElementById("rating_value").value = 0;

  // Reset stars
  document.querySelectorAll(".rating-star").forEach((star) => {
    star.classList.remove("active");
  });

  const feedbackForm = document.getElementById("feedbackForm");

  if (feedbackForm) {
    feedbackForm.reset();

    // reset() clears hidden inputs,
    // so restore the IDs after reset
    document.getElementById("modal_inter_id").value = interviewId;

    document.getElementById("modal_app_id").value = appId;

    document.getElementById("rating_value").value = 0;
  }

  document.getElementById("feedbackModal").style.display = "flex";
};

// ========================================
// CLOSE MODAL
// ========================================

window.closeFeedbackModal = function () {
  const modal = document.getElementById("feedbackModal");

  if (modal) {
    modal.style.display = "none";
  }
};

// ========================================
// INITIALIZE
// ========================================

document.addEventListener("DOMContentLoaded", initFeedback);
