/**
 * Mr ECU - Frontend Güvenlik Koruması
 * DOM Manipülasyonu, XSS ve Client-side saldırılara karşı JavaScript koruması
 */

(function() {
    'use strict';
    
    // Güvenlik event handler'ları
    const SecurityGuard = {
        // Güvenlik olaylarını logla
        logSecurityEvent: function(eventType, details) {
            try {
                fetch('/mrecuphp/security/log-security-event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        event_type: eventType,
                        details: details,
                        timestamp: new Date().toISOString(),
                        user_agent: navigator.userAgent,
                        page_url: window.location.href
                    })
                });
            } catch (e) {
                console.warn('Security event logging failed:', e);
            }
        },
        
        // DOM manipülasyonu tespiti
        detectDOMManipulation: function() {
            const originalMethods = {
                innerHTML: Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML').set,
                outerHTML: Object.getOwnPropertyDescriptor(Element.prototype, 'outerHTML').set,
                insertAdjacentHTML: Element.prototype.insertAdjacentHTML,
                appendChild: Element.prototype.appendChild,
                insertBefore: Element.prototype.insertBefore,
                replaceChild: Element.prototype.replaceChild,
                write: document.write,
                writeln: document.writeln
            };
            
            // innerHTML override
            Object.defineProperty(Element.prototype, 'innerHTML', {
                set: function(value) {
                    if (SecurityGuard.containsMaliciousContent(value)) {
                        SecurityGuard.logSecurityEvent('dom_manipulation_blocked', {
                            method: 'innerHTML',
                            content: value.substring(0, 200),
                            element: this.tagName
                        });
                        return;
                    }
                    originalMethods.innerHTML.call(this, value);
                }
            });
            
            // outerHTML override
            Object.defineProperty(Element.prototype, 'outerHTML', {
                set: function(value) {
                    if (SecurityGuard.containsMaliciousContent(value)) {
                        SecurityGuard.logSecurityEvent('dom_manipulation_blocked', {
                            method: 'outerHTML',
                            content: value.substring(0, 200),
                            element: this.tagName
                        });
                        return;
                    }
                    originalMethods.outerHTML.call(this, value);
                }
            });
            
            // insertAdjacentHTML override
            Element.prototype.insertAdjacentHTML = function(position, html) {
                if (SecurityGuard.containsMaliciousContent(html)) {
                    SecurityGuard.logSecurityEvent('dom_manipulation_blocked', {
                        method: 'insertAdjacentHTML',
                        content: html.substring(0, 200),
                        position: position
                    });
                    return;
                }
                originalMethods.insertAdjacentHTML.call(this, position, html);
            };
            
            // appendChild override
            Element.prototype.appendChild = function(child) {
                if (child.nodeType === Node.ELEMENT_NODE) {
                    const html = child.outerHTML || child.innerHTML;
                    if (SecurityGuard.containsMaliciousContent(html)) {
                        SecurityGuard.logSecurityEvent('dom_manipulation_blocked', {
                            method: 'appendChild',
                            content: html.substring(0, 200),
                            parent: this.tagName
                        });
                        return null;
                    }
                }
                return originalMethods.appendChild.call(this, child);
            };
            
            // document.write override
            document.write = function(content) {
                if (SecurityGuard.containsMaliciousContent(content)) {
                    SecurityGuard.logSecurityEvent('dom_manipulation_blocked', {
                        method: 'document.write',
                        content: content.substring(0, 200)
                    });
                    return;
                }
                originalMethods.write.call(this, content);
            };
        },
        
        // Kötü niyetli içerik kontrolü
        containsMaliciousContent: function(content) {
            if (typeof content !== 'string') return false;
            
            const maliciousPatterns = [
                /<script[^>]*>.*?<\/script>/gi,
                /<iframe[^>]*>.*?<\/iframe>/gi,
                /<object[^>]*>.*?<\/object>/gi,
                /<embed[^>]*>.*?<\/embed>/gi,
                /<link[^>]*rel=["']?stylesheet["']?[^>]*>/gi,
                /<meta[^>]*>/gi,
                /on\w+\s*=\s*["'][^"']*["']/gi,
                /javascript\s*:\s*/gi,
                /vbscript\s*:\s*/gi,
                /data\s*:\s*text\/html/gi,
                /expression\s*\(/gi,
                /eval\s*\(/gi,
                /setTimeout\s*\(/gi,
                /setInterval\s*\(/gi,
                /Function\s*\(/gi,
                /\bexec\b/gi,
                /document\.cookie/gi,
                /window\.location/gi
            ];
            
            return maliciousPatterns.some(pattern => pattern.test(content));
        },
        
        // Form güvenlik kontrolü
        secureForm: function(form) {
            // CSRF token kontrolü
            if (!form.querySelector('input[name="csrf_token"]')) {
                SecurityGuard.logSecurityEvent('csrf_token_missing', {
                    form_id: form.id,
                    form_action: form.action
                });
                return false;
            }
            
            // Input sanitization
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.type !== 'submit') {
                    const value = input.value;
                    if (SecurityGuard.containsMaliciousContent(value)) {
                        SecurityGuard.logSecurityEvent('malicious_input_detected', {
                            input_name: input.name,
                            input_type: input.type,
                            content: value.substring(0, 200)
                        });
                        input.value = SecurityGuard.sanitizeInput(value);
                    }
                }
            });
            
            return true;
        },
        
        // Input sanitization
        sanitizeInput: function(input) {
            if (typeof input !== 'string') return input;
            
            // HTML encode
            const div = document.createElement('div');
            div.textContent = input;
            let sanitized = div.innerHTML;
            
            // Tehlikeli pattern'leri temizle
            sanitized = sanitized.replace(/<script[^>]*>.*?<\/script>/gi, '[SCRIPT_REMOVED]');
            sanitized = sanitized.replace(/on\w+\s*=\s*["'][^"']*["']/gi, '');
            sanitized = sanitized.replace(/javascript\s*:/gi, '');
            sanitized = sanitized.replace(/vbscript\s*:/gi, '');
            
            return sanitized;
        },
        
        // URL güvenlik kontrolü
        validateUrl: function(url) {
            try {
                const urlObj = new URL(url, window.location.origin);
                
                // Protocol kontrolü
                if (!['http:', 'https:'].includes(urlObj.protocol)) {
                    SecurityGuard.logSecurityEvent('unsafe_protocol_detected', {
                        url: url,
                        protocol: urlObj.protocol
                    });
                    return false;
                }
                
                // Domain kontrolü (same-origin policy)
                if (urlObj.origin !== window.location.origin) {
                    SecurityGuard.logSecurityEvent('cross_origin_request', {
                        url: url,
                        origin: urlObj.origin,
                        current_origin: window.location.origin
                    });
                    // Cross-origin isteklere izin ver ama logla
                }
                
                return true;
            } catch (e) {
                SecurityGuard.logSecurityEvent('invalid_url_detected', {
                    url: url,
                    error: e.message
                });
                return false;
            }
        },
        
        // AJAX güvenlik wrapper
        secureAjax: function(options) {
            // URL kontrolü
            if (!SecurityGuard.validateUrl(options.url)) {
                throw new Error('Güvenli olmayan URL tespit edildi');
            }
            
            // Headers ekle
            options.headers = options.headers || {};
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
            
            // CSRF token ekle
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                options.headers['X-CSRF-Token'] = csrfToken.getAttribute('content');
            }
            
            // Data sanitization
            if (options.data && typeof options.data === 'object') {
                options.data = SecurityGuard.sanitizeObject(options.data);
            }
            
            return fetch(options.url, {
                method: options.method || 'GET',
                headers: options.headers,
                body: options.data ? JSON.stringify(options.data) : null
            });
        },
        
        // Object sanitization
        sanitizeObject: function(obj) {
            const sanitized = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    if (typeof obj[key] === 'string') {
                        sanitized[key] = SecurityGuard.sanitizeInput(obj[key]);
                    } else if (typeof obj[key] === 'object' && obj[key] !== null) {
                        sanitized[key] = SecurityGuard.sanitizeObject(obj[key]);
                    } else {
                        sanitized[key] = obj[key];
                    }
                }
            }
            return sanitized;
        },
        
        // Console hijacking koruması
        protectConsole: function() {
            const originalConsole = {
                log: console.log,
                warn: console.warn,
                error: console.error,
                info: console.info
            };
            
            // Console metodlarını override et
            ['log', 'warn', 'error', 'info'].forEach(method => {
                console[method] = function(...args) {
                    // Hassas bilgi kontrolü
                    const message = args.join(' ');
                    if (SecurityGuard.containsSensitiveInfo(message)) {
                        SecurityGuard.logSecurityEvent('sensitive_info_console_log', {
                            method: method,
                            message: message.substring(0, 100)
                        });
                        return; // Hassas bilgiyi console'a yazdırma
                    }
                    originalConsole[method].apply(console, args);
                };
            });
        },
        
        // Hassas bilgi kontrolü
        containsSensitiveInfo: function(text) {
            const sensitivePatterns = [
                /password\s*[:=]\s*[^\s]+/gi,
                /token\s*[:=]\s*[^\s]+/gi,
                /api[_-]?key\s*[:=]\s*[^\s]+/gi,
                /secret\s*[:=]\s*[^\s]+/gi,
                /credit[_-]?card/gi,
                /ssn/gi,
                /social[_-]?security/gi,
                /\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/g // Kredi kartı numarası pattern
            ];
            
            return sensitivePatterns.some(pattern => pattern.test(text));
        },
        
        // Event listener güvenlik kontrolü
        secureEventListener: function(element, event, handler) {
            const secureHandler = function(e) {
                // Event hijacking kontrolü
                if (e.isTrusted === false) {
                    SecurityGuard.logSecurityEvent('untrusted_event_detected', {
                        event_type: e.type,
                        element: element.tagName
                    });
                    return;
                }
                
                // Handler çalıştır
                try {
                    return handler.call(this, e);
                } catch (error) {
                    SecurityGuard.logSecurityEvent('event_handler_error', {
                        event_type: e.type,
                        error: error.message
                    });
                }
            };
            
            element.addEventListener(event, secureHandler);
        },
        
        // LocalStorage güvenlik wrapper
        secureStorage: {
            setItem: function(key, value) {
                try {
                    // Hassas bilgi kontrolü
                    if (SecurityGuard.containsSensitiveInfo(value)) {
                        SecurityGuard.logSecurityEvent('sensitive_info_storage_attempt', {
                            key: key,
                            value_preview: value.substring(0, 50)
                        });
                        throw new Error('Hassas bilgi localStorage\'da saklanamaz');
                    }
                    
                    // Değeri encrypt et (basit XOR encryption)
                    const encryptedValue = SecurityGuard.encrypt(value);
                    localStorage.setItem(key, encryptedValue);
                } catch (e) {
                    console.warn('Secure storage error:', e.message);
                }
            },
            
            getItem: function(key) {
                try {
                    const encryptedValue = localStorage.getItem(key);
                    if (encryptedValue) {
                        return SecurityGuard.decrypt(encryptedValue);
                    }
                    return null;
                } catch (e) {
                    console.warn('Secure storage retrieval error:', e.message);
                    return null;
                }
            },
            
            removeItem: function(key) {
                localStorage.removeItem(key);
            }
        },
        
        // Basit encryption/decryption (XOR)
        encrypt: function(text) {
            const key = 'mrecu_security_key_2025';
            let result = '';
            for (let i = 0; i < text.length; i++) {
                result += String.fromCharCode(text.charCodeAt(i) ^ key.charCodeAt(i % key.length));
            }
            return btoa(result);
        },
        
        decrypt: function(encryptedText) {
            const key = 'mrecu_security_key_2025';
            const decoded = atob(encryptedText);
            let result = '';
            for (let i = 0; i < decoded.length; i++) {
                result += String.fromCharCode(decoded.charCodeAt(i) ^ key.charCodeAt(i % key.length));
            }
            return result;
        },
        
        // Clickjacking koruması
        preventClickjacking: function() {
            // Frame içinde çalışıp çalışmadığını kontrol et
            if (window.top !== window.self) {
                SecurityGuard.logSecurityEvent('clickjacking_attempt_detected', {
                    parent_origin: document.referrer,
                    current_url: window.location.href
                });
                
                // Frame'den çık
                window.top.location = window.self.location;
            }
        },
        
        // Copy-paste güvenlik kontrolü
        secureClipboard: function() {
            document.addEventListener('paste', function(e) {
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                
                if (SecurityGuard.containsMaliciousContent(pastedData)) {
                    SecurityGuard.logSecurityEvent('malicious_paste_detected', {
                        content: pastedData.substring(0, 200),
                        target: e.target.tagName
                    });
                    e.preventDefault();
                    alert('Yapıştırılan içerik güvenlik kontrolünden geçemedi.');
                }
            });
        },
        
        // DevTools açma tespiti
        detectDevTools: function() {
            let devtools = {
                open: false,
                orientation: null
            };
            
            setInterval(function() {
                if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
                    if (!devtools.open) {
                        devtools.open = true;
                        SecurityGuard.logSecurityEvent('devtools_opened', {
                            window_size: {
                                outer: { width: window.outerWidth, height: window.outerHeight },
                                inner: { width: window.innerWidth, height: window.innerHeight }
                            }
                        });
                    }
                } else {
                    devtools.open = false;
                }
            }, 500);
        },
        
        // Initialize güvenlik önlemleri
        init: function() {
            // DOM manipülasyon koruması
            this.detectDOMManipulation();
            
            // Console koruması
            this.protectConsole();
            
            // Clickjacking koruması
            this.preventClickjacking();
            
            // Clipboard koruması
            this.secureClipboard();
            
            // DevTools tespiti
            this.detectDevTools();
            
            // Form submit events
            document.addEventListener('submit', function(e) {
                if (!SecurityGuard.secureForm(e.target)) {
                    e.preventDefault();
                    alert('Form güvenlik kontrolünden geçemedi.');
                }
            });
            
            // AJAX istekleri için global error handler
            window.addEventListener('unhandledrejection', function(e) {
                SecurityGuard.logSecurityEvent('unhandled_promise_rejection', {
                    reason: e.reason ? e.reason.toString() : 'Unknown',
                    stack: e.reason && e.reason.stack ? e.reason.stack : null
                });
            });
            
            // Global error handler
            window.addEventListener('error', function(e) {
                SecurityGuard.logSecurityEvent('javascript_error', {
                    message: e.message,
                    filename: e.filename,
                    lineno: e.lineno,
                    colno: e.colno,
                    stack: e.error ? e.error.stack : null
                });
            });
            
            console.log('🛡️ Mr ECU Security Guard initialized');
        }
    };
    
    // Sayfa yüklendiğinde güvenlik önlemlerini başlat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', SecurityGuard.init.bind(SecurityGuard));
    } else {
        SecurityGuard.init();
    }
    
    // Global SecurityGuard nesnesini window'a ekle
    window.SecurityGuard = SecurityGuard;
    
})();

// jQuery varsa, jQuery AJAX istekleri için güvenlik wrapper
if (typeof jQuery !== 'undefined') {
    (function($) {
        const originalAjax = $.ajax;
        
        $.ajax = function(options) {
            // Güvenlik kontrolleri
            if (options.url && !window.SecurityGuard.validateUrl(options.url)) {
                throw new Error('Güvenli olmayan URL tespit edildi');
            }
            
            // CSRF token ekle
            options.headers = options.headers || {};
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) {
                options.headers['X-CSRF-Token'] = csrfToken;
            }
            
            // Data sanitization
            if (options.data && typeof options.data === 'object') {
                options.data = window.SecurityGuard.sanitizeObject(options.data);
            }
            
            return originalAjax.call(this, options);
        };
    })(jQuery);
}
