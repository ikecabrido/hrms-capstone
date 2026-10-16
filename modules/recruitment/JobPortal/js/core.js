var jobs = [];
var applications = [];
var currentJobId = null;

function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

function refNumber() {
  var y = new Date().getFullYear();
  var n = Math.floor(10000 + Math.random() * 89999);
  return "BCP-" + y + "-" + n;
}

function escapeHtml(s) {
  if (s === undefined || s === null) return "";
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
   * IMPORTANT:
   * When sending FormData, DO NOT manually set
   * Content-Type: application/json.
   *
   * The browser automatically sets:
   * multipart/form-data; boundary=...
   *
   * This is required for PHP to populate $_FILES.
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

  /*
   * Some of your PHP controllers may return:
   * { success: false, message: "..." }
   */
  if (data.success === false) {
    throw new Error(data.error || data.message || "Request failed");
  }

  return data;
}
