(() => {
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __commonJS = (cb, mod) => function __require() {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  };

  // assets/js/main.js
  var require_main = __commonJS({
    "assets/js/main.js"(exports, module) {
      var MrEcu = {
        baseUrl: window.location.origin + "/",
        currentUser: null,
        csrf_token: null
      };
      document.addEventListener("DOMContentLoaded", function() {
        initializeGlobalFeatures();
        initializeTooltips();
        initializeModals();
        initializeFormValidation();
      });
      function initializeGlobalFeatures() {
        createLoadingOverlay();
        initializeNotifications();
        initializeAutoLogout();
        setupCSRFProtection();
      }
      function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      }
      function initializeModals() {
        document.querySelectorAll(".modal").forEach(function(modal) {
          modal.addEventListener("shown.bs.modal", function() {
            const form = this.querySelector("form");
            if (form) {
              form.classList.remove("was-validated");
            }
          });
        });
      }
      function initializeFormValidation() {
        const forms = document.querySelectorAll(".needs-validation");
        Array.prototype.slice.call(forms).forEach(function(form) {
          form.addEventListener("submit", function(event) {
            if (!form.checkValidity()) {
              event.preventDefault();
              event.stopPropagation();
            }
            form.classList.add("was-validated");
          }, false);
        });
      }
      function createLoadingOverlay() {
        const overlay = document.createElement("div");
        overlay.id = "loadingOverlay";
        overlay.className = "loading-overlay";
        overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Y\xFCkleniyor...</span>
            </div>
            <div class="loading-text mt-3">Y\xFCkleniyor...</div>
        </div>
    `;
        const style = document.createElement("style");
        style.textContent = `
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .loading-content {
            text-align: center;
        }
        .loading-text {
            font-weight: 500;
            color: #666;
        }
    `;
        document.head.appendChild(style);
        document.body.appendChild(overlay);
      }
      function showLoading(text = "Y\xFCkleniyor...") {
        const overlay = document.getElementById("loadingOverlay");
        const loadingText = overlay.querySelector(".loading-text");
        loadingText.textContent = text;
        overlay.style.display = "flex";
      }
      function hideLoading() {
        const overlay = document.getElementById("loadingOverlay");
        overlay.style.display = "none";
      }
      function initializeNotifications() {
        if (!document.getElementById("notificationContainer")) {
          const container = document.createElement("div");
          container.id = "notificationContainer";
          container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 350px;
        `;
          document.body.appendChild(container);
        }
      }
      function showNotification(message, type = "info", duration = 5e3) {
        const container = document.getElementById("notificationContainer");
        const notification = document.createElement("div");
        const typeClasses = {
          "success": "alert-success",
          "error": "alert-danger",
          "warning": "alert-warning",
          "info": "alert-info"
        };
        const icons = {
          "success": "bi bi-check-circle",
          "error": "bi bi-exclamation-triangle",
          "warning": "bi bi-exclamation-circle",
          "info": "bi bi-info-circle"
        };
        notification.className = `alert ${typeClasses[type]} alert-dismissible fade show`;
        notification.innerHTML = `
        <i class="${icons[type]} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
        container.appendChild(notification);
        if (duration > 0) {
          setTimeout(() => {
            if (notification.parentNode) {
              notification.remove();
            }
          }, duration);
        }
      }
      async function sendAjaxRequest(url, data = {}, method = "POST") {
        try {
          showLoading();
          const options = {
            method,
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            }
          };
          if (method !== "GET" && Object.keys(data).length > 0) {
            options.body = JSON.stringify(data);
          }
          const response = await fetch(url, options);
          const result = await response.json();
          hideLoading();
          if (!response.ok) {
            throw new Error(result.message || "Bir hata olu\u015Ftu");
          }
          return result;
        } catch (error) {
          hideLoading();
          showNotification(error.message, "error");
          throw error;
        }
      }
      function formatFileSize(bytes) {
        if (bytes === 0)
          return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
      }
      function formatDate(dateString, includeTime = true) {
        const date = new Date(dateString);
        const options = {
          day: "2-digit",
          month: "2-digit",
          year: "numeric"
        };
        if (includeTime) {
          options.hour = "2-digit";
          options.minute = "2-digit";
        }
        return date.toLocaleDateString("tr-TR", options);
      }
      function formatNumber(number, decimals = 2) {
        return Number(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }
      function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];
        if (password.length >= 8) {
          strength += 1;
        } else {
          feedback.push("En az 8 karakter olmal\u0131");
        }
        if (/[a-z]/.test(password)) {
          strength += 1;
        } else {
          feedback.push("K\xFC\xE7\xFCk harf i\xE7ermeli");
        }
        if (/[A-Z]/.test(password)) {
          strength += 1;
        } else {
          feedback.push("B\xFCy\xFCk harf i\xE7ermeli");
        }
        if (/\d/.test(password)) {
          strength += 1;
        } else {
          feedback.push("Rakam i\xE7ermeli");
        }
        if (/[^a-zA-Z0-9]/.test(password)) {
          strength += 1;
        } else {
          feedback.push("\xD6zel karakter i\xE7ermeli");
        }
        const levels = ["\xC7ok Zay\u0131f", "Zay\u0131f", "Orta", "G\xFC\xE7l\xFC", "\xC7ok G\xFC\xE7l\xFC"];
        const colors = ["danger", "warning", "info", "success", "success"];
        return {
          strength,
          level: levels[strength],
          color: colors[strength],
          feedback,
          percentage: strength / 5 * 100
        };
      }
      function serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
          data[key] = value;
        }
        return data;
      }
      function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
      }
      function initializeAutoLogout() {
        let timeout;
        const TIMEOUT_DURATION = 30 * 60 * 1e3;
        function resetTimer() {
          clearTimeout(timeout);
          timeout = setTimeout(() => {
            showNotification("Oturum s\xFCresi doldu. Tekrar giri\u015F yapman\u0131z gerekiyor.", "warning");
            setTimeout(() => {
              window.location.href = "login.php";
            }, 3e3);
          }, TIMEOUT_DURATION);
        }
        ["mousedown", "mousemove", "keypress", "scroll", "touchstart"].forEach((event) => {
          document.addEventListener(event, resetTimer, true);
        });
        resetTimer();
      }
      function setupCSRFProtection() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
          MrEcu.csrf_token = csrfToken.getAttribute("content");
        }
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
          if (options.method && options.method !== "GET" && MrEcu.csrf_token) {
            options.headers = options.headers || {};
            options.headers["X-CSRF-Token"] = MrEcu.csrf_token;
          }
          return originalFetch(url, options);
        };
      }
      if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark-mode");
      }
      function copyToClipboard(text) {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text).then(function() {
            showNotification("Panoya kopyaland\u0131!", "success", 2e3);
          });
        } else {
          const textArea = document.createElement("textarea");
          textArea.value = text;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand("copy");
          document.body.removeChild(textArea);
          showNotification("Panoya kopyaland\u0131!", "success", 2e3);
        }
      }
      window.addEventListener("error", function(e) {
        console.error("JavaScript Error:", e.error);
        showNotification("Beklenmeyen bir hata olu\u015Ftu.", "error");
      });
      if (typeof module !== "undefined" && module.exports) {
        module.exports = {
          showNotification,
          showLoading,
          hideLoading,
          sendAjaxRequest,
          formatFileSize,
          formatDate,
          formatNumber,
          checkPasswordStrength,
          serializeForm,
          getUrlParameter,
          copyToClipboard
        };
      }
    }
  });
  require_main();
})();
