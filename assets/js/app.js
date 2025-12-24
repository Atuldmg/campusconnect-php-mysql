/**
 * CampusConnect - Client-Side Application Scripts
 * Manages theme mode switching (Light/Dark/System), password visibility,
 * interactive confirmations, and UI helpers.
 */

(function () {
  'use strict';

  // ==========================================================
  // THEME MANAGEMENT (System, Light, Dark)
  // ==========================================================
  const THEME_STORAGE_KEY = 'campusconnect_theme';

  function getStoredTheme() {
    return localStorage.getItem(THEME_STORAGE_KEY) || 'system';
  }

  function getPreferredTheme() {
    const stored = getStoredTheme();
    if (stored === 'light' || stored === 'dark') {
      return stored;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    const resolvedTheme = theme === 'system' ? getPreferredTheme() : theme;
    document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
    updateThemeSelectorUI(theme);
  }

  function updateThemeSelectorUI(currentSetting) {
    const selectorButtons = document.querySelectorAll('[data-theme-value]');
    const activeIcon = document.getElementById('theme-active-icon');

    selectorButtons.forEach(btn => {
      const val = btn.getAttribute('data-theme-value');
      const checkIcon = btn.querySelector('.theme-check-icon');
      if (val === currentSetting) {
        btn.classList.add('active');
        if (checkIcon) checkIcon.classList.remove('d-none');
      } else {
        btn.classList.remove('active');
        if (checkIcon) checkIcon.classList.add('d-none');
      }
    });

    if (activeIcon) {
      if (currentSetting === 'dark') {
        activeIcon.className = 'bi bi-moon-stars-fill';
      } else if (currentSetting === 'light') {
        activeIcon.className = 'bi bi-sun-fill';
      } else {
        activeIcon.className = 'bi bi-circle-half';
      }
    }
  }

  // Initialize theme on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = getStoredTheme();
    applyTheme(currentTheme);

    // Theme selector click bindings
    document.querySelectorAll('[data-theme-value]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const selected = btn.getAttribute('data-theme-value');
        localStorage.setItem(THEME_STORAGE_KEY, selected);
        applyTheme(selected);
      });
    });

    // Listen to OS system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (getStoredTheme() === 'system') {
        applyTheme('system');
      }
    });

    // Listen to storage events from other tabs
    window.addEventListener('storage', (e) => {
      if (e.key === THEME_STORAGE_KEY) {
        applyTheme(e.newValue || 'system');
      }
    });

    // ==========================================================
    // PASSWORD VISIBILITY TOGGLE
    // ==========================================================
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const icon = btn.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
          }
        } else {
          input.type = 'password';
          if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
          }
        }
      });
    });

    // ==========================================================
    // DELETE / DESTRUCTIVE CONFIRMATION MODALS
    // ==========================================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click', (e) => {
        const msg = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
        if (!confirm(msg)) {
          e.preventDefault();
        }
      });
    });

    // ==========================================================
    // CHARACTER COUNTER (e.g. for feedback message)
    // ==========================================================
    const charCounterInputs = document.querySelectorAll('[data-char-count]');
    charCounterInputs.forEach(input => {
      const targetId = input.getAttribute('data-char-count');
      const display = document.getElementById(targetId);
      const max = input.getAttribute('maxlength') || 1000;

      function updateCount() {
        const len = input.value.length;
        if (display) {
          display.textContent = `${len} / ${max} characters`;
        }
      }

      input.addEventListener('input', updateCount);
      updateCount();
    });

    // Auto-dismiss alert banners after 6 seconds
    const autoAlerts = document.querySelectorAll('.alert-dismissible');
    autoAlerts.forEach(alert => {
      setTimeout(() => {
        try {
          const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
          if (bsAlert) bsAlert.close();
        } catch (err) {}
      }, 6000);
    });
  });

  // Global Toast Notification Helper
  window.showToast = function (message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
      toastEl.remove();
    });
  };

})();
