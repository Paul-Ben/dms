// JavaScript to toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
  const togglePassword1 = document.getElementById("togglePassword1");
  const togglePassword2 = document.getElementById("togglePassword2");
  const passwordInput1 = document.getElementById("passwordInput1");
  const passwordInput2 = document.getElementById("passwordInput2");
  const eyeIcon = document.getElementById("eyeIcon");

  // Only add event listeners if elements exist
  if (togglePassword1 && passwordInput1 && eyeIcon) {
    togglePassword1.addEventListener("click", function () {
      // Toggle the input type
      const type1 = passwordInput1.type === "password" ? "text" : "password";
      passwordInput1.type = type1;

      // Toggle the icon
      if (type1 === "password") {
        eyeIcon.classList.remove("bi-eye");
        eyeIcon.classList.add("bi-eye-slash");
      } else {
        eyeIcon.classList.remove("bi-eye-slash");
        eyeIcon.classList.add("bi-eye");
      }
    });
  }

  if (togglePassword2 && passwordInput2 && eyeIcon) {
    togglePassword2.addEventListener("click", function () {
      // Toggle the input type
      const type2 = passwordInput2.type === "password" ? "text" : "password";
      passwordInput2.type = type2;
    
      // Toggle the icon
      if (type2 === "password") {
        eyeIcon.classList.remove("bi-eye");
        eyeIcon.classList.add("bi-eye-slash");
      } else {
        eyeIcon.classList.remove("bi-eye-slash");
        eyeIcon.classList.add("bi-eye");
      }
    });
  }
});