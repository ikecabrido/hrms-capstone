document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("onboardingModal");

  if (!modal) return;

  const endpoint =
    "/hrms-capstone/modules/recruitment/controllers/OnboardingChecklistController.php";

  const title = document.getElementById("onboardingModalTitle");
  const position = document.getElementById("onboardingEmployeePosition");
  const message = document.getElementById("onboardingMessage");
  const progress = document.getElementById("onboardingProgress");
  const accountNumber = document.getElementById("bdoAccountNumber");

  const closeButton = document.getElementById("closeOnboardingModal");
  const footerCloseButton = document.getElementById("closeOnboardingFooter");

  const checkboxes = [...modal.querySelectorAll("[data-item]")];

  let currentId = null;
  let requestVersion = 0;

  // ---------------------------------------------------------
  // REQUEST HELPER
  // ---------------------------------------------------------
  async function request(action, options = {}) {
    const url = new URL(endpoint, window.location.origin);

    url.searchParams.set("action", action);

    const response = await fetch(url.toString(), {
      ...options,
      headers: {
        Accept: "application/json",
        ...(options.headers || {}),
      },
    });

    const responseText = await response.text();

    let result;

    try {
      result = JSON.parse(responseText);
    } catch (error) {
      console.error("Onboarding request failed to return JSON.");
      console.error("Request URL:", url.toString());
      console.error("Response URL:", response.url);
      console.error("HTTP status:", response.status);
      console.error("Content-Type:", response.headers.get("content-type"));
      console.error("Response preview:", responseText.slice(0, 3000));

      throw new Error(
        "The server returned HTML or invalid JSON. " +
          "Open F12 > Console and inspect Response preview.",
      );
    }

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || `Request failed with HTTP ${response.status}.`,
      );
    }

    return result;
  }

  // ---------------------------------------------------------
  // MESSAGE
  // ---------------------------------------------------------
  function showMessage(text, isError = false) {
    if (!message) return;

    message.textContent = text;
    message.className =
      "onboarding-message show " + (isError ? "error" : "success");
  }

  function clearMessage() {
    if (!message) return;

    message.textContent = "";
    message.className = "onboarding-message";
  }

  // ---------------------------------------------------------
  // PROGRESS
  // ---------------------------------------------------------
  function updateProgress() {
    if (!progress) return;

    const completed = checkboxes.filter((box) => box.checked).length;

    progress.textContent = `${completed} of ${checkboxes.length} completed`;
  }

  // ---------------------------------------------------------
  // CLEAR MODAL DATA
  // ---------------------------------------------------------
  function clearModal() {
    checkboxes.forEach((box) => {
      box.checked = false;
    });

    modal.querySelectorAll("[data-file]").forEach((element) => {
      element.textContent = "";
    });

    modal.querySelectorAll("[data-upload]").forEach((input) => {
      input.value = "";
    });

    if (accountNumber) {
      accountNumber.value = "";
    }

    updateProgress();
  }

  // ---------------------------------------------------------
  // LOAD CHECKLIST
  // ---------------------------------------------------------
  async function loadChecklist(id, version) {
    const result = await request("load", {
      method: "GET",
      cache: "no-store",
      // The controller reads onboarding_id from GET.
      headers: {
        Accept: "application/json",
      },
    });

    // The ID is sent separately below using the query string.
    // This function is replaced by the ID-aware request immediately
    // after declaration in the request helper usage.
  }

  // ID-aware GET request for loading an employee checklist.
  async function fetchChecklist(id) {
    const url = new URL(endpoint, window.location.origin);

    url.searchParams.set("action", "load");
    url.searchParams.set("onboarding_id", String(id));

    const response = await fetch(url.toString(), {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
      cache: "no-store",
    });

    const responseText = await response.text();

    let result;

    try {
      result = JSON.parse(responseText);
    } catch (error) {
      console.error("Checklist load URL:", url.toString());
      console.error("HTTP status:", response.status);
      console.error("Response preview:", responseText.slice(0, 3000));

      throw new Error(
        "The controller returned HTML or invalid JSON. " +
          "Check the browser console for the response preview.",
      );
    }

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || `Failed to load checklist (${response.status}).`,
      );
    }

    return result;
  }

  async function loadEmployeeChecklist(id, version) {
    const result = await fetchChecklist(id);

    // Ignore a response if another employee was opened or the modal closed.
    if (version !== requestVersion || String(currentId) !== String(id)) {
      return;
    }

    clearModal();

    const items = result.items || {};

    checkboxes.forEach((box) => {
      const key = box.dataset.item;
      const item = items[key];

      if (!item) return;

      box.checked =
        item.completed === true ||
        item.completed === 1 ||
        item.completed === "1";

      const fileLabel = modal.querySelector(`[data-file="${key}"]`);

      if (fileLabel && item.file_path) {
        const filename = String(item.file_path).split("/").pop();

        fileLabel.textContent = `Saved: ${filename}`;
      }

      if (key === "bdo_cash_card" && accountNumber && item.account_number) {
        accountNumber.value = item.account_number;
      }
    });

    updateProgress();
  }

  // ---------------------------------------------------------
  // OPEN ONBOARDING MODAL
  // ---------------------------------------------------------
  document.querySelectorAll(".manage-onboarding").forEach((button) => {
    button.addEventListener("click", async () => {
      const id = button.dataset.id;

      if (!id) {
        showMessage("The employee onboarding ID is missing.", true);
        return;
      }

      currentId = id;
      const version = ++requestVersion;

      if (title) {
        title.textContent = `Onboarding Checklist — ${button.dataset.name || "Employee"}`;
      }

      if (position) {
        position.textContent = button.dataset.position || "";
      }

      clearModal();
      clearMessage();

      modal.classList.add("show");
      modal.setAttribute("aria-hidden", "false");

      try {
        await loadEmployeeChecklist(id, version);
      } catch (error) {
        if (version === requestVersion && String(currentId) === String(id)) {
          showMessage(error.message, true);
          console.error("Could not load onboarding checklist:", error);
        }
      }
    });
  });

  // ---------------------------------------------------------
  // SAVE CHECKLIST ITEM
  // ---------------------------------------------------------
  async function saveItem(key, completed) {
    if (!currentId) {
      throw new Error("Please select an employee first.");
    }

    const data = new FormData();

    data.append("onboarding_id", currentId);
    data.append("item_key", key);
    data.append("is_completed", completed ? "1" : "0");

    if (key === "bdo_cash_card" && accountNumber) {
      data.append("account_number", accountNumber.value.trim());
    }

    return request("save", {
      method: "POST",
      body: data,
    });
  }

  checkboxes.forEach((box) => {
    box.addEventListener("change", async () => {
      if (!currentId) return;

      const idAtStart = currentId;
      const versionAtStart = requestVersion;
      const key = box.dataset.item;
      const newValue = box.checked;
      const previousValue = !newValue;

      box.disabled = true;

      try {
        await saveItem(key, newValue);

        if (
          versionAtStart !== requestVersion ||
          String(currentId) !== String(idAtStart)
        ) {
          return;
        }

        showMessage("Checklist saved successfully.");
        updateProgress();
      } catch (error) {
        if (
          versionAtStart === requestVersion &&
          String(currentId) === String(idAtStart)
        ) {
          box.checked = previousValue;
          showMessage(error.message, true);
        }
      } finally {
        box.disabled = false;
      }
    });
  });

  // ---------------------------------------------------------
  // SAVE BDO CASH CARD ACCOUNT NUMBER
  // ---------------------------------------------------------
  if (accountNumber) {
    accountNumber.addEventListener("change", async () => {
      if (!currentId) return;

      const idAtStart = currentId;
      const versionAtStart = requestVersion;

      const box = modal.querySelector('[data-item="bdo_cash_card"]');

      if (!box) {
        showMessage("BDO checklist item was not found.", true);
        return;
      }

      try {
        await saveItem("bdo_cash_card", box.checked);

        if (
          versionAtStart !== requestVersion ||
          String(currentId) !== String(idAtStart)
        ) {
          return;
        }

        showMessage("BDO account number saved successfully.");
      } catch (error) {
        if (
          versionAtStart === requestVersion &&
          String(currentId) === String(idAtStart)
        ) {
          showMessage(error.message, true);
        }
      }
    });
  }

  // ---------------------------------------------------------
  // OPEN FILE PICKER
  // ---------------------------------------------------------
  modal.querySelectorAll("[data-pick]").forEach((button) => {
    button.addEventListener("click", () => {
      const key = button.dataset.pick;

      const input = modal.querySelector(`[data-upload="${key}"]`);

      if (input) {
        input.click();
      }
    });
  });

  // ---------------------------------------------------------
  // UPLOAD MEDICAL / CONTRACT DOCUMENT
  // ---------------------------------------------------------
  modal.querySelectorAll("[data-upload]").forEach((input) => {
    input.addEventListener("change", async () => {
      if (!currentId || !input.files || !input.files.length) {
        return;
      }

      const idAtStart = currentId;
      const versionAtStart = requestVersion;
      const key = input.dataset.upload;
      const file = input.files[0];

      // Match the controller's 10 MB limit.
      if (file.size > 10 * 1024 * 1024) {
        showMessage("Maximum file size is 10 MB.", true);
        input.value = "";
        return;
      }

      const allowedExtensions = ["pdf", "jpg", "jpeg", "png"];
      const extension = file.name.split(".").pop().toLowerCase();

      if (!allowedExtensions.includes(extension)) {
        showMessage("Only PDF, JPG, and PNG files are allowed.", true);
        input.value = "";
        return;
      }

      if (!["medical", "contract"].includes(key)) {
        showMessage("Uploads are not allowed for this checklist item.", true);
        input.value = "";
        return;
      }

      const data = new FormData();

      data.append("onboarding_id", idAtStart);
      data.append("item_key", key);
      data.append("document", file);

      input.disabled = true;

      const pickButton = modal.querySelector(`[data-pick="${key}"]`);

      if (pickButton) {
        pickButton.disabled = true;
      }

      try {
        const result = await request("upload", {
          method: "POST",
          body: data,
        });

        if (
          versionAtStart !== requestVersion ||
          String(currentId) !== String(idAtStart)
        ) {
          return;
        }

        const fileLabel = modal.querySelector(`[data-file="${key}"]`);

        if (fileLabel) {
          fileLabel.textContent = `Saved: ${result.file_name || file.name}`;
        }

        const checkbox = modal.querySelector(`[data-item="${key}"]`);

        if (checkbox) {
          checkbox.checked = true;
        }

        updateProgress();
        showMessage("File uploaded and saved successfully.");
      } catch (error) {
        if (
          versionAtStart === requestVersion &&
          String(currentId) === String(idAtStart)
        ) {
          showMessage(error.message, true);
          console.error("Document upload failed:", error);
        }
      } finally {
        input.disabled = false;
        input.value = "";

        if (pickButton) {
          pickButton.disabled = false;
        }
      }
    });
  });

  // ---------------------------------------------------------
  // CLOSE MODAL
  // ---------------------------------------------------------
  function closeModal() {
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");

    currentId = null;
    requestVersion++;
    clearMessage();
  }

  if (closeButton) {
    closeButton.addEventListener("click", closeModal);
  }

  if (footerCloseButton) {
    footerCloseButton.addEventListener("click", closeModal);
  }

  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && modal.classList.contains("show")) {
      closeModal();
    }
  });

  // Initial progress display.
  updateProgress();
});
