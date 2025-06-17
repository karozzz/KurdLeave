document.addEventListener("DOMContentLoaded", function () {
  const emailField = document.getElementById("email");
  const passwordField = document.getElementById("password");
  const loginForm = document.querySelector("form");
  const submitButton = document.querySelector(".login-btn");

  if (emailField) {
    emailField.addEventListener("blur", function () {
      if (this.value && !isValidEmail(this.value)) {
        showAlert("Please enter a valid email address", "error");
      }
    });
  }

  if (passwordField) {
    const showPasswordBtn = document.createElement("button");
    showPasswordBtn.type = "button";
    showPasswordBtn.innerHTML = "👁️";
    showPasswordBtn.style.cssText =
      "position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;";

    passwordField.parentElement.style.position = "relative";
    passwordField.style.paddingRight = "35px";
    passwordField.parentElement.appendChild(showPasswordBtn);

    showPasswordBtn.addEventListener("click", function () {
      if (passwordField.type === "password") {
        passwordField.type = "text";
        this.innerHTML = "🙈";
      } else {
        passwordField.type = "password";
        this.innerHTML = "👁️";
      }
    });
  }

  if (loginForm) {
    loginForm.addEventListener("submit", function () {
      if (submitButton) {
        startLoading(submitButton);
      }
    });
  }

  const savedEmail = localStorage.getItem("userEmail");
  if (savedEmail && emailField) {
    emailField.value = savedEmail;
    if (passwordField) passwordField.focus();
  }

  const rememberBox = document.getElementById("remember");
  if (rememberBox && emailField) {
    rememberBox.addEventListener("change", function () {
      if (this.checked) {
        localStorage.setItem("userEmail", emailField.value);
      } else {
        localStorage.removeItem("userEmail");
      }
    });
  }
});
