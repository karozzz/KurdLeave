document.addEventListener("DOMContentLoaded", function () {
  const startDateInput = document.querySelector('input[name="start_date"]');
  const endDateInput = document.querySelector('input[name="end_date"]');
  const reasonTextarea = document.querySelector('textarea[name="reason"]');
  const leaveForm = document.querySelector("form");

  if (startDateInput) {
    startDateInput.min = new Date().toISOString().split("T")[0];

    startDateInput.addEventListener("change", function () {
      if (endDateInput) {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
          endDateInput.value = "";
        }
        calculateDays();
      }
    });
  }

  if (endDateInput) {
    endDateInput.addEventListener("change", calculateDays);
  }

  if (reasonTextarea) {
    reasonTextarea.addEventListener("input", function () {
      const maxLength = 500;
      const remaining = maxLength - this.value.length;
      let counter = document.querySelector(".char-counter");

      if (!counter) {
        counter = document.createElement("div");
        counter.className = "char-counter";
        counter.style.cssText =
          "font-size:12px; color:#666; text-align:right; margin-top:5px;";
        this.parentElement.appendChild(counter);
      }

      counter.textContent = remaining + " characters remaining";
      if (remaining < 50) counter.style.color = "#f44336";
      else if (remaining < 100) counter.style.color = "#ff9800";
      else counter.style.color = "#666";
    });
  }

  function calculateDays() {
    if (
      !startDateInput ||
      !endDateInput ||
      !startDateInput.value ||
      !endDateInput.value
    ) {
      return;
    }

    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    const timeDiff = endDate.getTime() - startDate.getTime();
    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

    let daysDisplay = document.querySelector(".days-display");
    if (!daysDisplay) {
      daysDisplay = document.createElement("div");
      daysDisplay.className = "days-display";
      daysDisplay.style.cssText =
        "margin:10px 0; padding:10px; background:#e3f2fd; border-radius:5px; font-weight:bold;";
      endDateInput.parentElement.appendChild(daysDisplay);
    }

    if (dayDiff > 0) {
      daysDisplay.textContent = "Total days: " + dayDiff;
      daysDisplay.style.background = "#e8f5e8";
    } else {
      daysDisplay.textContent = "Please select valid dates";
      daysDisplay.style.background = "#ffebee";
    }
  }

  if (leaveForm) {
    leaveForm.addEventListener("submit", function (e) {
      if (
        !startDateInput.value ||
        !endDateInput.value ||
        !reasonTextarea.value.trim()
      ) {
        e.preventDefault();
        showAlert("Please fill in all required fields", "error");
        return;
      }

      if (reasonTextarea.value.length < 10) {
        e.preventDefault();
        showAlert(
          "Please provide a more detailed reason (at least 10 characters)",
          "error"
        );
        return;
      }

      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        startLoading(submitButton);
      }
    });
  }
});
