/*
 * LOGIN PAGE JAVASCRIPT - Making Login Smooth and User-Friendly! 🔐
 * =================================================================
 *
 * Hey there! This JavaScript file is like having a helpful assistant on the login page.
 * It makes the login experience smoother and more user-friendly by:
 *
 * - 👀 Adding a "show/hide password" button (so you can see what you're typing)
 * - ✅ Checking if email addresses look valid as you type
 * - 💾 Remembering your email address if you want (saves typing next time)
 * - 🔄 Showing loading animations when you click login
 * - 🎯 Automatically focusing on the right input field
 *
 * Think of this as the "user experience team" for the login page - making everything
 * feel polished and professional, just like apps you use every day! ✨
 */

// WAIT FOR THE PAGE TO FULLY LOAD: Don't try to do anything until everything is ready
document.addEventListener("DOMContentLoaded", function () {
  // FIND ALL THE IMPORTANT ELEMENTS: Get references to the login form parts
  const emailField = document.getElementById("email"); // Where they type their email
  const passwordField = document.getElementById("password"); // Where they type their password
  const loginForm = document.querySelector("form"); // The whole login form
  const submitButton = document.querySelector(".login-btn"); // The "Login" button

  // EMAIL VALIDATION: Check if their email looks correct when they finish typing
  if (emailField) {
    emailField.addEventListener("blur", function () {
      // "blur" means they clicked away from the field
      // If they typed something AND it doesn't look like a valid email
      if (this.value && !isValidEmail(this.value)) {
        showAlert("Please enter a valid email address", "error"); // Show friendly error message
      }
    });
  }

  // PASSWORD SHOW/HIDE FEATURE: Add a button to toggle password visibility
  // This is like having sunglasses you can put on or take off! 🕶️
  if (passwordField) {
    // CREATE THE SHOW/HIDE BUTTON: A little eye icon button
    const showPasswordBtn = document.createElement("button");
    showPasswordBtn.type = "button"; // Don't submit form when clicked
    showPasswordBtn.innerHTML = "👁️"; // Eye emoji for "show"
    showPasswordBtn.style.cssText =
      "position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;";

    // POSITION THE BUTTON: Put it inside the password field on the right side
    passwordField.parentElement.style.position = "relative"; // Make parent container positioned
    passwordField.style.paddingRight = "35px"; // Make room for the button
    passwordField.parentElement.appendChild(showPasswordBtn); // Add button to the container

    // TOGGLE PASSWORD VISIBILITY: When they click the eye button
    showPasswordBtn.addEventListener("click", function () {
      if (passwordField.type === "password") {
        // SHOW THE PASSWORD: Change field type to text so they can see what they typed
        passwordField.type = "text";
        this.innerHTML = "🙈"; // Change to "hide" emoji
      } else {
        // HIDE THE PASSWORD: Change back to password field (dots/asterisks)
        passwordField.type = "password";
        this.innerHTML = "👁️"; // Change back to "show" emoji
      }
    });
  }

  // LOGIN FORM SUBMISSION: Show loading animation when they click login
  if (loginForm) {
    loginForm.addEventListener("submit", function () {
      if (submitButton) {
        startLoading(submitButton); // This function is defined in utils.js - shows spinner
      }
    });
  }

  // REMEMBER EMAIL ADDRESS: If they used "Remember Me" before, fill in their email
  const savedEmail = localStorage.getItem("userEmail"); // Check browser storage
  if (savedEmail && emailField) {
    emailField.value = savedEmail; // Fill in the saved email
    if (passwordField) passwordField.focus(); // Jump cursor to password field
  }

  // "REMEMBER ME" CHECKBOX: Save or forget their email address
  const rememberBox = document.getElementById("remember");
  if (rememberBox && emailField) {
    rememberBox.addEventListener("change", function () {
      if (this.checked) {
        // SAVE EMAIL: Store it in browser so we can fill it in next time
        localStorage.setItem("userEmail", emailField.value);
      } else {
        // FORGET EMAIL: Remove it from browser storage
        localStorage.removeItem("userEmail");
      }
    });
  }
});
