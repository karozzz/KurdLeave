/*
 * LEAVE REQUEST JAVASCRIPT - Making Leave Applications User-Friendly! 🏖️
 * =======================================================================
 *
 * Hey there! This JavaScript file is like having a smart assistant helping employees
 * fill out their vacation requests. It makes the whole process smooth and error-free by:
 *
 * - 📅 Preventing them from selecting past dates (you can't take vacation yesterday!)
 * - 🧮 Automatically calculating how many days they're requesting
 * - ✅ Making sure end date isn't before start date (that would be confusing!)
 * - 📝 Counting characters in their reason field (and warning when getting close to limit)
 * - 🚫 Preventing form submission if required fields are missing
 * - 🎯 Providing real-time feedback as they fill out the form
 *
 * Think of this as the "helpful friend" who double-checks your vacation request
 * before you submit it, making sure everything looks good! 👍
 */

// WAIT FOR THE PAGE TO FULLY LOAD: Don't do anything until the form is ready
document.addEventListener("DOMContentLoaded", function () {
  // FIND ALL THE IMPORTANT FORM ELEMENTS: Get references to the form fields
  const startDateInput = document.querySelector('input[name="start_date"]'); // When vacation starts
  const endDateInput = document.querySelector('input[name="end_date"]'); // When vacation ends
  const reasonTextarea = document.querySelector('textarea[name="reason"]'); // Why they need time off
  const leaveForm = document.querySelector("form"); // The whole form

  // START DATE SETUP: Configure the start date picker
  if (startDateInput) {
    // PREVENT PAST DATES: You can't request vacation for yesterday!
    startDateInput.min = new Date().toISOString().split("T")[0]; // Set minimum to today

    // WHEN START DATE CHANGES: Update end date restrictions and recalculate days
    startDateInput.addEventListener("change", function () {
      if (endDateInput) {
        // END DATE MUST BE AFTER START DATE: This prevents confusing situations
        endDateInput.min = this.value; // End date can't be before start date

        // CLEAR INVALID END DATE: If end date is now before start date, clear it
        if (endDateInput.value && endDateInput.value < this.value) {
          endDateInput.value = ""; // Reset end date so they pick a valid one
        }

        // RECALCULATE DAYS: Update the day counter
        calculateDays();
      }
    });
  }

  // END DATE SETUP: When end date changes, recalculate the total days
  if (endDateInput) {
    endDateInput.addEventListener("change", calculateDays);
  }

  // REASON FIELD ENHANCEMENT: Add character counter and styling
  if (reasonTextarea) {
    reasonTextarea.addEventListener("input", function () {
      const maxLength = 500; // Maximum characters allowed
      const remaining = maxLength - this.value.length; // How many characters left
      let counter = document.querySelector(".char-counter");

      // CREATE CHARACTER COUNTER: If it doesn't exist yet
      if (!counter) {
        counter = document.createElement("div");
        counter.className = "char-counter";
        counter.style.cssText =
          "font-size:12px; color:#666; text-align:right; margin-top:5px;";
        this.parentElement.appendChild(counter);
      }

      // UPDATE CHARACTER COUNT: Show how many characters they have left
      counter.textContent = remaining + " characters remaining";

      // COLOR-CODE THE COUNTER: Visual warning when getting close to limit
      if (remaining < 50)
        counter.style.color = "#f44336"; // Red: Almost at limit!
      else if (remaining < 100)
        counter.style.color = "#ff9800"; // Orange: Getting close
      else counter.style.color = "#666"; // Gray: Plenty of room
    });
  }

  // DAY CALCULATION FUNCTION: Figure out how many days they're requesting
  function calculateDays() {
    // CHECK IF WE HAVE BOTH DATES: Need both start and end to calculate
    if (
      !startDateInput ||
      !endDateInput ||
      !startDateInput.value ||
      !endDateInput.value
    ) {
      return; // Exit early if we don't have both dates
    }

    // CALCULATE THE DIFFERENCE: How many days between start and end dates
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    const timeDiff = endDate.getTime() - startDate.getTime(); // Difference in milliseconds
    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Convert to days (+1 to include both start and end days)

    // FIND OR CREATE DAYS DISPLAY: Show the calculated days to the user
    let daysDisplay = document.querySelector(".days-display");
    if (!daysDisplay) {
      // CREATE THE DISPLAY ELEMENT: A nice box showing the day count
      daysDisplay = document.createElement("div");
      daysDisplay.className = "days-display";
      daysDisplay.style.cssText =
        "margin:10px 0; padding:10px; background:#e3f2fd; border-radius:5px; font-weight:bold;";
      endDateInput.parentElement.appendChild(daysDisplay);
    }

    // UPDATE THE DISPLAY: Show valid day count or error message
    if (dayDiff > 0) {
      // VALID DATES: Show the calculated days with green background
      daysDisplay.textContent = "Total days: " + dayDiff;
      daysDisplay.style.background = "#e8f5e8"; // Light green background
    } else {
      // INVALID DATES: Show error message with red background
      daysDisplay.textContent = "Please select valid dates";
      daysDisplay.style.background = "#ffebee"; // Light red background
    }
  }

  // FORM VALIDATION: Check everything before submitting
  if (leaveForm) {
    leaveForm.addEventListener("submit", function (e) {
      // CHECK REQUIRED FIELDS: Make sure all important fields are filled
      if (
        !startDateInput.value ||
        !endDateInput.value ||
        !reasonTextarea.value.trim()
      ) {
        e.preventDefault(); // Stop the form from submitting
        showAlert("Please fill in all required fields", "error");
        return;
      }

      // CHECK REASON LENGTH: Make sure they provided a decent explanation
      if (reasonTextarea.value.length < 10) {
        e.preventDefault(); // Stop the form from submitting
        showAlert(
          "Please provide a more detailed reason (at least 10 characters)",
          "error"
        );
        return;
      }

      // SHOW LOADING ANIMATION: Give feedback that form is being processed
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        startLoading(submitButton); // This function is in utils.js
      }
    });
  }
});
