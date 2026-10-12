/**
 * CampusConnect - Client-Side Form Validation Module
 * Provides real-time feedback using standard Bootstrap 5 validation styles.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    // --------------------------------------------------------
    // General Bootstrap Form Validation
    // --------------------------------------------------------
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // --------------------------------------------------------
    // Registration Form Real-Time Validation
    // --------------------------------------------------------
    const regForm = document.getElementById('registrationForm');
    if (regForm) {
      const nameInput = document.getElementById('reg_name');
      const emailInput = document.getElementById('reg_email');
      const passInput = document.getElementById('reg_password');
      const confirmPassInput = document.getElementById('reg_confirm_password');

      if (passInput && confirmPassInput) {
        function validatePasswordMatch() {
          if (confirmPassInput.value && passInput.value !== confirmPassInput.value) {
            confirmPassInput.setCustomValidity('Passwords do not match');
            confirmPassInput.classList.add('is-invalid');
            confirmPassInput.classList.remove('is-valid');
          } else if (confirmPassInput.value) {
            confirmPassInput.setCustomValidity('');
            confirmPassInput.classList.remove('is-invalid');
            confirmPassInput.classList.add('is-valid');
          }
        }

        passInput.addEventListener('input', validatePasswordMatch);
        confirmPassInput.addEventListener('input', validatePasswordMatch);
      }

      if (emailInput) {
        emailInput.addEventListener('blur', () => {
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (emailInput.value && !emailPattern.test(emailInput.value)) {
            emailInput.setCustomValidity('Please enter a valid email address.');
            emailInput.classList.add('is-invalid');
          } else if (emailInput.value) {
            emailInput.setCustomValidity('');
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
          }
        });
      }
    }
  });
})();
