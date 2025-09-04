/**
 * Mr ECU - Custom JavaScript Functions
 * Site geneli JavaScript fonksiyonları
 */

// Global değişkenler - Check if already defined in header
if (typeof window.MrEcu === 'undefined') {
    window.MrEcu = {
        baseUrl: window.location.origin,  // Production için dinamik
        currentUser: null,
        csrf_token: null,
        ecuSpinner: null
    };
}

// Sayfa yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    initializeGlobalFeatures();
    initializeTooltips();
    initializeModals();
    initializeFormValidation();
    initializeECUSpinner();
});

/**
 * Global özellikleri başlat
 */
function initializeGlobalFeatures() {
    // Loading overlay
    createLoadingOverlay();
    
    // Notification system
    initializeNotifications();
    
    // Auto logout timer
    initializeAutoLogout();
    
    // CSRF token setup
    setupCSRFProtection();
    
    // ECU Spinner setup
    setupECUSpinner();
}

/**
 * Bootstrap tooltiplerini başlat
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Modal event listeners
 */
function initializeModals() {
    // Modal açıldığında formu temizle
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) {
                form.classList.remove('was-validated');
            }
        });
    });
}

/**
 * Form validation setup
 */
function initializeFormValidation() {
    // Bootstrap form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Loading overlay oluştur
 */
function createLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <div class="loading-text mt-3">Yükleniyor...</div>
        </div>
    `;
    
    // CSS stilleri
    const style = document.createElement('style');
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

/**
 * Loading göster/gizle
 */
function showLoading(text = 'Yükleniyor...') {
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = overlay.querySelector('.loading-text');
    loadingText.textContent = text;
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = 'none';
}

/**
 * Notification sistemi
 */
function initializeNotifications() {
    // Notification container oluştur
    if (!document.getElementById('notificationContainer')) {
        const container = document.createElement('div');
        container.id = 'notificationContainer';
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

/**
 * Notification göster
 */
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    const typeClasses = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const icons = {
        'success': 'bi bi-check-circle',
        'error': 'bi bi-exclamation-triangle',
        'warning': 'bi bi-exclamation-circle',
        'info': 'bi bi-info-circle'
    };
    
    notification.className = `alert ${typeClasses[type]} alert-dismissible fade show`;
    notification.innerHTML = `
        <i class="${icons[type]} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(notification);
    
    // Otomatik kaldır
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
}

/**
 * AJAX isteği gönder
 */
async function sendAjaxRequest(url, data = {}, method = 'POST') {
    try {
        showLoading();
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (method !== 'GET' && Object.keys(data).length > 0) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        const result = await response.json();
        
        hideLoading();
        
        if (!response.ok) {
            throw new Error(result.message || 'Bir hata oluştu');
        }
        
        return result;
        
    } catch (error) {
        hideLoading();
        showNotification(error.message, 'error');
        throw error;
    }
}

/**
 * Dosya boyutunu formatla
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Tarihi formatla
 */
function formatDate(dateString, includeTime = true) {
    const date = new Date(dateString);
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleDateString('tr-TR', options);
}

/**
 * Sayıyı formatla
 */
function formatNumber(number, decimals = 2) {
    return Number(number).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Şifre güçlülüğünü kontrol et
 */
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    // Uzunluk kontrolü
    if (password.length >= 8) {
        strength += 1;
    } else {
        feedback.push('En az 8 karakter olmalı');
    }
    
    // Küçük harf kontrolü
    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Küçük harf içermeli');
    }
    
    // Büyük harf kontrolü
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Büyük harf içermeli');
    }
    
    // Rakam kontrolü
    if (/\d/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Rakam içermeli');
    }
    
    // Özel karakter kontrolü
    if (/[^a-zA-Z0-9]/.test(password)) {
        strength += 1;
    } else {
        feedback.push('Özel karakter içermeli');
    }
    
    const levels = ['Çok Zayıf', 'Zayıf', 'Orta', 'Güçlü', 'Çok Güçlü'];
    const colors = ['danger', 'warning', 'info', 'success', 'success'];
    
    return {
        strength: strength,
        level: levels[strength],
        color: colors[strength],
        feedback: feedback,
        percentage: (strength / 5) * 100
    };
}

/**
 * Form verilerini serialize et
 */
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

/**
 * URL parametrelerini al
 */
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Otomatik çıkış timer'ı
 */
function initializeAutoLogout() {
    let timeout;
    const TIMEOUT_DURATION = 30 * 60 * 1000; // 30 dakika
    
    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            showNotification('Oturum süresi doldu. Tekrar giriş yapmanız gerekiyor.', 'warning');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        }, TIMEOUT_DURATION);
    }
    
    // User activity events
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });
    
    resetTimer();
}

/**
 * CSRF protection setup
 */
function setupCSRFProtection() {
    // Meta tag'den CSRF token'ı al
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        MrEcu.csrf_token = csrfToken.getAttribute('content');
    }
    
    // Tüm AJAX isteklerine CSRF token ekle
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (options.method && options.method !== 'GET' && MrEcu.csrf_token) {
            options.headers = options.headers || {};
            options.headers['X-CSRF-Token'] = MrEcu.csrf_token;
        }
        return originalFetch(url, options);
    };
}

/**
 * Drag & Drop dosya yükleme
 */
function initializeDragAndDrop(element, callback) {
    element.addEventListener('dragover', function(e) {
        e.preventDefault();
        element.classList.add('dragover');
    });
    
    element.addEventListener('dragleave', function(e) {
        e.preventDefault();
        element.classList.remove('dragover');
    });
    
    element.addEventListener('drop', function(e) {
        e.preventDefault();
        element.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0 && callback) {
            callback(files);
        }
    });
}

/**
 * Sayfa yenilemeyi engelle (unsaved changes)
 */
function enableUnsavedChangesWarning() {
    let hasUnsavedChanges = false;
    
    // Form değişikliklerini izle
    document.querySelectorAll('form input, form textarea, form select').forEach(function(element) {
        element.addEventListener('change', function() {
            hasUnsavedChanges = true;
        });
    });
    
    // Form submit edildiğinde warning'i kaldır
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    });
    
    // Sayfa kapatılırken uyar
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'Kaydedilmemiş değişiklikler var. Sayfayı kapatmak istediğinizden emin misiniz?';
            return e.returnValue;
        }
    });
}

/**
 * Dark mode toggle
 */
function toggleDarkMode() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-mode');
    
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    
    // Icon değiştir
    const icon = document.querySelector('.dark-mode-toggle i');
    if (icon) {
        icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon';
    }
}

// Dark mode durumunu yükle
if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark-mode');
}

/**
 * Infinite scroll
 */
function initializeInfiniteScroll(container, loadMoreCallback) {
    const observer = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting) {
            loadMoreCallback();
        }
    });
    
    const loadingElement = container.querySelector('.loading-more');
    if (loadingElement) {
        observer.observe(loadingElement);
    }
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showNotification('Panoya kopyalandı!', 'success', 2000);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Panoya kopyalandı!', 'success', 2000);
    }
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showNotification('Beklenmeyen bir hata oluştu.', 'error');
});

/**
 * ===== ECU SPINNER FUNCTIONS =====
 */

/**
 * ECU Spinner setup
 */
function setupECUSpinner() {
    MrEcu.ecuSpinner = document.getElementById('ecuSpinner');
    
    if (!MrEcu.ecuSpinner) {
        console.warn('ECU Spinner element not found');
        return;
    }
    
    // Setup page navigation spinner
    setupPageNavigationSpinner();
}

/**
 * Initialize ECU Spinner
 */
function initializeECUSpinner() {
    // ECU Spinner artık inline script ile kontrol ediliyor
}

/**
 * Setup page navigation spinner
 */
function setupPageNavigationSpinner() {
    // Navigation links için basit spinner setup
    const navLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([target="_blank"]):not([data-bs-toggle])');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // External links, anchors ve modals için spinner gösterme
            if (href.startsWith('#') || 
                href.startsWith('http') && !href.includes(window.location.hostname) ||
                this.hasAttribute('data-bs-toggle') ||
                this.hasAttribute('target')) {
                return;
            }
            
            // Basit spinner göster
            const spinner = document.getElementById('ecuSpinner');
            if (spinner) {
                spinner.style.display = 'flex';
                spinner.style.opacity = '1';
                document.body.style.overflow = 'hidden';
            }
        });
    });
}

/**
 * Show ECU Spinner - Simplified
 */
function showECUSpinner(text = 'Sistem Yükleniyor...') {
    const spinner = document.getElementById('ecuSpinner');
    
    if (spinner) {
        spinner.style.display = 'flex';
        spinner.style.opacity = '1';
        document.body.style.overflow = 'hidden';
        
        // Update text if provided
        const loadingText = spinner.querySelector('.spinner-text p');
        if (loadingText && text) {
            loadingText.textContent = text;
        }
    }
}

/**
 * Hide ECU Spinner - Simplified
 */
function hideECUSpinner() {
    const spinner = document.getElementById('ecuSpinner');
    
    if (spinner) {
        spinner.style.opacity = '0';
        
        setTimeout(() => {
            spinner.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
}

/**
 * Show ECU Spinner for specific duration
 */
function showECUSpinnerFor(duration = 2000, text = 'Sistem Yükleniyor...') {
    showECUSpinner(text);
    
    setTimeout(() => {
        hideECUSpinner();
    }, duration);
}

/**
 * Manual spinner control for specific operations
 */
window.ECUSpinner = {
    show: showECUSpinner,
    hide: hideECUSpinner,
    showFor: showECUSpinnerFor
};

/**
 * ===== END ECU SPINNER FUNCTIONS =====
 */

// Export functions for module usage
if (typeof module !== 'undefined' && module.exports) {
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
        copyToClipboard,
        showECUSpinner,
        hideECUSpinner,
        showECUSpinnerFor
    };
}
