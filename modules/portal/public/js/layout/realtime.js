(() => {
  const el = document.getElementById("realtimeClock");
  if (!el) return;

  const update = () =>
    el.textContent = new Date().toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });

  update();
  setInterval(update, 1000);
})();
