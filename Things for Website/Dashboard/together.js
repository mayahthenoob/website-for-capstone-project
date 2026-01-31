const sidebar = document.getElementById("sidebar");
const mainContainer = document.getElementById("main-container");
const toggleBtn = document.getElementById("toggle-btn");

toggleBtn.onclick = () => {
  sidebar.classList.toggle("collapsed");
  mainContainer.classList.toggle("expanded");
  toggleBtn.classList.toggle("bx-chevron-right");
};

// Expand/collapse tables with animation
document.querySelectorAll(".expand-icon").forEach(icon => {
  const table = icon.closest(".table-card").querySelector("table");
  table.style.maxHeight = table.scrollHeight + "px"; // initial max-height

  icon.onclick = e => {
    if (table.classList.contains("collapsed")) {
      table.style.maxHeight = table.scrollHeight + "px";
      table.style.opacity = "1";
      table.classList.remove("collapsed");
      icon.classList.add("bx-chevron-up");
      icon.classList.remove("bx-chevron-down");
    } else {
      table.style.maxHeight = "0px";
      table.style.opacity = "0";
      table.classList.add("collapsed");
      icon.classList.add("bx-chevron-down");
      icon.classList.remove("bx-chevron-up");
    }
  };
});
