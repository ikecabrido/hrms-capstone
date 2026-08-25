function initDashboard() {
  const root = document.getElementById("analyticsDashboardPage");
  if (!root || root.dataset.initialized === "true") return;

  root.dataset.initialized = "true";
  root.querySelectorAll(".metric-card, .analytics-panel, .process-strip").forEach((item, index) => {
    item.style.setProperty("--reveal-delay", `${index * 70}ms`);
    item.classList.add("analytics-reveal");
  });

  const refreshButton = document.getElementById("analyticsRefresh");
  const syncStatus = document.getElementById("analyticsSyncStatus");

  if (refreshButton) {
    refreshButton.addEventListener("click", () => {
      refreshButton.disabled = true;
      refreshButton.classList.add("is-refreshing");
      refreshButton.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Refreshing...';

      window.setTimeout(() => {
        const time = new Date().toLocaleTimeString("en-PH", { hour: "2-digit", minute: "2-digit" });
        if (syncStatus) syncStatus.textContent = `Last synced today, ${time}`;
        refreshButton.disabled = false;
        refreshButton.classList.remove("is-refreshing");
        refreshButton.innerHTML = '<i class="fa-solid fa-check"></i> Data refreshed';

        window.setTimeout(() => {
          refreshButton.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Refresh data';
        }, 1800);
      }, 650);
    });
  }
}

window.addEventListener("page:loaded", initDashboard);
document.addEventListener("DOMContentLoaded", initDashboard);