document.addEventListener("DOMContentLoaded", function () {
  const calendarCells = document.querySelectorAll("td");
  const today = new Date().getDate();

  calendarCells.forEach(function (cell) {
    if (cell.textContent.trim() && !isNaN(cell.textContent.trim())) {
      cell.style.cursor = "pointer";

      if (cell.textContent.trim() == today) {
        cell.style.backgroundColor = "#e8f5e8";
        cell.style.fontWeight = "bold";
        cell.style.border = "2px solid #4CAF50";
      }

      cell.addEventListener("click", function () {
        showAlert("Selected date: " + this.textContent, "success");
      });

      cell.addEventListener("mouseenter", function () {
        if (this.textContent.trim() != today) {
          this.style.backgroundColor = "#f0f8ff";
        }
      });

      cell.addEventListener("mouseleave", function () {
        if (this.textContent.trim() != today) {
          this.style.backgroundColor = "";
        }
      });
    }
  });

  const navButtons = document.createElement("div");
  navButtons.style.cssText = "text-align:center; margin:20px 0;";
  navButtons.innerHTML =
    '<button onclick="goToToday()" class="btn-today">Today</button> ' +
    '<button onclick="refreshCalendar()" class="btn-refresh">Refresh</button>';

  const calendar = document.querySelector("table");
  if (calendar) {
    calendar.parentElement.insertBefore(navButtons, calendar);

    const style = document.createElement("style");
    style.textContent =
      ".btn-today, .btn-refresh { background:#007bff; color:white; border:none; padding:10px 20px; margin:0 5px; border-radius:5px; cursor:pointer; } .btn-refresh { background:#28a745; }";
    document.head.appendChild(style);
  }
});

function goToToday() {
  const now = new Date();
  window.location.href =
    "?month=" + now.getMonth() + "&year=" + now.getFullYear();
}

function refreshCalendar() {
  window.location.reload();
}
