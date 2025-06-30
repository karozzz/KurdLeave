/*
 * CALENDAR JAVASCRIPT - Making Calendars Interactive and Fun! 📅
 * =============================================================
 *
 * Hey there! This JavaScript file turns a boring HTML calendar into an interactive
 * calendar that users actually enjoy using. It's like adding superpowers to your calendar!
 *
 * WHAT THIS DOES:
 * - 🎯 Highlights today's date so you always know where you are
 * - 👆 Makes calendar dates clickable (so you can select dates)
 * - ✨ Adds hover effects (dates light up when you move your mouse over them)
 * - 🔘 Adds "Today" and "Refresh" buttons for easy navigation
 * - 📱 Makes the calendar feel like a modern app interface
 *
 * Think of this as the "user experience designer" for calendars - making them
 * feel smooth, responsive, and intuitive to use! 🎨
 */

// WAIT FOR THE PAGE TO FULLY LOAD: Don't do anything until the calendar is ready
document.addEventListener("DOMContentLoaded", function () {
  // FIND ALL CALENDAR CELLS: Get every date box in the calendar
  const calendarCells = document.querySelectorAll("td");
  const today = new Date().getDate(); // What day is today? (1-31)

  // ENHANCE EACH CALENDAR CELL: Make each date interactive and user-friendly
  calendarCells.forEach(function (cell) {
    // SKIP EMPTY CELLS: Only work with cells that have actual dates in them
    if (cell.textContent.trim() && !isNaN(cell.textContent.trim())) {
      // MAKE IT CLICKABLE: Show that this cell can be clicked
      cell.style.cursor = "pointer";

      // HIGHLIGHT TODAY'S DATE: Make it stand out so users know where they are
      if (cell.textContent.trim() == today) {
        cell.style.backgroundColor = "#e8f5e8"; // Light green background
        cell.style.fontWeight = "bold"; // Make text bold
        cell.style.border = "2px solid #4CAF50"; // Green border
      }

      // CLICK HANDLER: What happens when someone clicks a date
      cell.addEventListener("click", function () {
        // SHOW SELECTED DATE: Give feedback that they clicked something
        showAlert("Selected date: " + this.textContent, "success");
        // TODO: In a real app, this might open a "create leave request" form
        // or show existing leave requests for this date
      });

      // HOVER EFFECTS: Make dates light up when mouse hovers over them
      // This gives users visual feedback that dates are interactive
      cell.addEventListener("mouseenter", function () {
        // LIGHT UP ON HOVER: But don't change today's special highlighting
        if (this.textContent.trim() != today) {
          this.style.backgroundColor = "#f0f8ff"; // Light blue background
        }
      });

      cell.addEventListener("mouseleave", function () {
        // RETURN TO NORMAL: Remove hover effect when mouse leaves
        if (this.textContent.trim() != today) {
          this.style.backgroundColor = ""; // Back to normal background
        }
      });
    }
  });

  // ADD NAVIGATION BUTTONS: Create "Today" and "Refresh" buttons for easy navigation
  const navButtons = document.createElement("div");
  navButtons.style.cssText = "text-align:center; margin:20px 0;";
  navButtons.innerHTML =
    '<button onclick="goToToday()" class="btn-today">Today</button> ' + // Jump to current month
    '<button onclick="refreshCalendar()" class="btn-refresh">Refresh</button>'; // Reload calendar

  // INSERT BUTTONS: Put them above the calendar table
  const calendar = document.querySelector("table");
  if (calendar) {
    calendar.parentElement.insertBefore(navButtons, calendar);

    // ADD BUTTON STYLES: Make the buttons look nice and professional
    const style = document.createElement("style");
    style.textContent =
      ".btn-today, .btn-refresh { " +
      "background:#007bff; color:white; border:none; padding:10px 20px; " +
      "margin:0 5px; border-radius:5px; cursor:pointer; } " +
      ".btn-refresh { background:#28a745; }"; // Green for refresh button
    document.head.appendChild(style);
  }
});

// NAVIGATION FUNCTIONS: These handle the button clicks

// GO TO TODAY: Jump to the current month and year
function goToToday() {
  const now = new Date();
  // REDIRECT TO CURRENT MONTH: Change the URL to show current month/year
  window.location.href =
    "?month=" + now.getMonth() + "&year=" + now.getFullYear();
}

// REFRESH CALENDAR: Reload the page to get fresh data
function refreshCalendar() {
  window.location.reload(); // This reloads the entire page
}
