function showAlert(message, type) {
  const alert = document.createElement("div");
  alert.className = "alert alert-" + type;
  alert.textContent = message;
  alert.style.cssText =
    "position:fixed; top:20px; right:20px; padding:15px; border-radius:5px; z-index:1000; background:" +
    (type === "error" ? "#f44336" : "#4CAF50") +
    "; color:white; animation:slideIn 0.3s;";

  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 3000);
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function startLoading(button) {
  button.disabled = true;
  button.textContent = "Loading...";
}

function stopLoading(button, originalText) {
  button.disabled = false;
  button.textContent = originalText || "Submit";
}
