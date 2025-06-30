/*
 * ADMIN INTERFACE JAVASCRIPT - Making Admin Pages Professional and Interactive! 👑
 * ================================================================================
 *
 * Hey there! This JavaScript file is like the "polish and shine" crew for admin pages.
 * It adds all the professional touches that make admin interfaces feel modern and responsive.
 *
 * WHAT THIS DOES:
 * - ✨ Adds hover effects to cards and statistics (they lift up when you hover!)
 * - 🎨 Highlights table rows when you move your mouse over them
 * - 🛡️ Adds confirmation dialogs for dangerous actions (like deleting users)
 * - 🔄 Adds a refresh button to easily reload data
 * - ⏰ Shows a live clock in the corner (so admins know what time it is)
 * - 🎯 Makes buttons show loading animations when clicked
 *
 * Think of this as the "user experience team" for admin pages - making everything
 * feel polished, professional, and intuitive to use! 💼
 */

// WAIT FOR THE PAGE TO FULLY LOAD: Don't do anything until everything is ready
document.addEventListener("DOMContentLoaded", function () {
  // FIND ALL THE INTERACTIVE ELEMENTS: Get references to cards, tables, and buttons
  const statCards = document.querySelectorAll(".stat-card, .card"); // Statistics cards and info cards
  const tableRows = document.querySelectorAll("table tr"); // All table rows
  const buttons = document.querySelectorAll("button, .btn"); // All buttons

  // CARD HOVER EFFECTS: Make cards "lift up" when you hover over them
  // This gives a nice 3D effect that makes the interface feel responsive
  statCards.forEach(function (card) {
    card.addEventListener("mouseenter", function () {
      // LIFT THE CARD: Move it up slightly and add shadow
      this.style.transform = "translateY(-5px)"; // Move up 5 pixels
      this.style.boxShadow = "0 8px 20px rgba(0,0,0,0.1)"; // Add drop shadow
      this.style.transition = "all 0.3s ease"; // Smooth animation
    });

    card.addEventListener("mouseleave", function () {
      // RETURN TO NORMAL: Remove the hover effects
      this.style.transform = "translateY(0)"; // Move back to original position
      this.style.boxShadow = ""; // Remove shadow
    });
  });

  // TABLE ROW HOVER EFFECTS: Highlight rows when you hover over them
  // This helps users track which row they're looking at in large tables
  tableRows.forEach(function (row, index) {
    if (index > 0) {
      // Skip the header row (index 0)
      row.addEventListener("mouseenter", function () {
        // HIGHLIGHT THE ROW: Light gray background on hover
        this.style.backgroundColor = "#f8f9fa";
      });

      row.addEventListener("mouseleave", function () {
        // RETURN TO NORMAL: Remove highlight when mouse leaves
        this.style.backgroundColor = "";
      });
    }
  });

  // BUTTON ENHANCEMENTS: Add safety checks and loading animations
  buttons.forEach(function (button) {
    // ONLY ENHANCE ACTION BUTTONS: Buttons that actually do something
    if (button.type === "submit" || button.onclick) {
      button.addEventListener("click", function () {
        // SAFETY CHECK FOR DANGEROUS ACTIONS: Confirm before deleting things
        if (
          this.textContent.includes("Delete") ||
          this.textContent.includes("Remove")
        ) {
          // SHOW CONFIRMATION DIALOG: "Are you sure?" popup
          if (!confirm("Are you sure you want to delete this item?")) {
            event.preventDefault(); // Cancel the action if they click "No"
            return false;
          }
        }

        // SHOW LOADING ANIMATION: Give feedback that something is happening
        startLoading(this); // This function is defined in utils.js
      });
    }
  });

  // ADD REFRESH BUTTON: Quick way to reload data without full page refresh
  const refreshButton = document.createElement("button");
  refreshButton.innerHTML = "🔄 Refresh";
  refreshButton.style.cssText =
    "background:#007bff; color:white; border:none; padding:10px 20px; " +
    "border-radius:5px; cursor:pointer; margin:10px; float:right;";

  refreshButton.addEventListener("click", function () {
    // REFRESH THE PAGE: Show loading, then reload after a short delay
    startLoading(this);
    setTimeout(() => window.location.reload(), 1000); // Wait 1 second then reload
  });

  // INSERT REFRESH BUTTON: Add it to the page header
  const header = document.querySelector("h1, .header, .main-header");
  if (header) {
    header.appendChild(refreshButton);
  }

  // ADD LIVE CLOCK: Show current time in the corner
  // This is helpful for admins who need to track when things happened
  const timeDisplay = document.createElement("div");
  timeDisplay.style.cssText =
    "position:fixed; top:10px; right:10px; background:rgba(0,0,0,0.8); " +
    "color:white; padding:5px 10px; border-radius:5px; font-size:12px; z-index:1000;";
  document.body.appendChild(timeDisplay);

  // UPDATE CLOCK EVERY SECOND: Keep the time current
  setInterval(function () {
    timeDisplay.textContent = new Date().toLocaleTimeString();
  }, 1000);
});
