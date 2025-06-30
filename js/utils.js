/*
 * UTILITY JAVASCRIPT FUNCTIONS - The Helpful Toolkit! 🛠️
 * ========================================================
 *
 * Hey there! This file contains all the handy little functions that other JavaScript
 * files use throughout the system. Think of these as your "utility belt" functions -
 * small but super useful tools that make everything work smoothly!
 *
 * WHAT'S IN THIS TOOLKIT:
 * - 🚨 Alert messages (success, error, info notifications)
 * - ✅ Email validation (checking if emails look correct)
 * - 🔄 Loading animations (showing spinners when things are processing)
 * - 🎨 UI enhancements (making things look and feel professional)
 *
 * These functions are used by login.js, leave.js, admin.js, and other files
 * to create a consistent, polished user experience across the whole app! 🎯
 */

// SHOW ALERT MESSAGES: Display notifications to users
// This is like having a friendly messenger that pops up to tell users what's happening
function showAlert(message, type) {
  // CREATE THE ALERT ELEMENT: A nice-looking notification box
  const alert = document.createElement("div");
  alert.className = "alert alert-" + type;
  alert.textContent = message;
  alert.style.cssText =
    "position:fixed; top:20px; right:20px; padding:15px; border-radius:5px; " +
    "z-index:1000; background:" +
    (type === "error" ? "#f44336" : "#4CAF50") + // Red for errors, green for success
    "; color:white; animation:slideIn 0.3s;"; // White text with slide-in animation

  // SHOW THE ALERT: Add it to the page
  document.body.appendChild(alert);

  // AUTO-REMOVE ALERT: Remove it after 3 seconds so it doesn't clutter the screen
  setTimeout(() => alert.remove(), 3000);
}

// EMAIL VALIDATION: Check if an email address looks valid
// This uses a "regular expression" (regex) - a pattern-matching tool
function isValidEmail(email) {
  // EMAIL PATTERN: Must have text@text.text format
  // This regex breaks down as:
  // ^[^\s@]+ = Start with one or more characters that aren't spaces or @
  // @ = Must have an @ symbol
  // [^\s@]+ = One or more characters that aren't spaces or @
  // \. = Must have a period (dot)
  // [^\s@]+$ = End with one or more characters that aren't spaces or @
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// START LOADING ANIMATION: Show that something is processing
// This prevents users from clicking buttons multiple times and gives feedback
function startLoading(button) {
  button.disabled = true; // Disable the button so they can't click it again
  button.textContent = "Loading..."; // Change text to show something is happening
  // Note: You could also add a spinner icon here for extra visual feedback
}

// STOP LOADING ANIMATION: Return button to normal state
// This re-enables the button and restores its original text
function stopLoading(button, originalText) {
  button.disabled = false; // Re-enable the button
  button.textContent = originalText || "Submit"; // Restore original text (or default to "Submit")
}
