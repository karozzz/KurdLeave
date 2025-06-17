document.addEventListener("DOMContentLoaded", function () {
  const statCards = document.querySelectorAll(".stat-card, .card");
  const tableRows = document.querySelectorAll("table tr");
  const buttons = document.querySelectorAll("button, .btn");

  statCards.forEach(function (card) {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)";
      this.style.boxShadow = "0 8px 20px rgba(0,0,0,0.1)";
      this.style.transition = "all 0.3s ease";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
      this.style.boxShadow = "";
    });
  });

  tableRows.forEach(function (row, index) {
    if (index > 0) {
      row.addEventListener("mouseenter", function () {
        this.style.backgroundColor = "#f8f9fa";
      });

      row.addEventListener("mouseleave", function () {
        this.style.backgroundColor = "";
      });
    }
  });

  buttons.forEach(function (button) {
    if (button.type === "submit" || button.onclick) {
      button.addEventListener("click", function () {
        if (
          this.textContent.includes("Delete") ||
          this.textContent.includes("Remove")
        ) {
          if (!confirm("Are you sure you want to delete this item?")) {
            event.preventDefault();
            return false;
          }
        }
        startLoading(this);
      });
    }
  });

  const refreshButton = document.createElement("button");
  refreshButton.innerHTML = "🔄 Refresh";
  refreshButton.style.cssText =
    "background:#007bff; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin:10px; float:right;";
  refreshButton.addEventListener("click", function () {
    startLoading(this);
    setTimeout(() => window.location.reload(), 1000);
  });

  const header = document.querySelector("h1, .header, .main-header");
  if (header) {
    header.appendChild(refreshButton);
  }

  const timeDisplay = document.createElement("div");
  timeDisplay.style.cssText =
    "position:fixed; top:10px; right:10px; background:rgba(0,0,0,0.8); color:white; padding:5px 10px; border-radius:5px; font-size:12px; z-index:1000;";
  document.body.appendChild(timeDisplay);

  setInterval(function () {
    timeDisplay.textContent = new Date().toLocaleTimeString();
  }, 1000);
});
