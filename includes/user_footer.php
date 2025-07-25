</div>
    </div>
    <!-- Ana içerik sonu -->

    <!-- User Panel Footer -->
    <footer class="bg-light border-top mt-auto py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>. 
                        Tüm hakları saklıdır.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Kullanıcı: <strong><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Bilinmiyor'; ?></strong>
                        <?php if (isset($_SESSION['credits'])): ?>
                            | Kredi: <strong class="text-success"><?php echo number_format($_SESSION['credits'], 2); ?> TL</strong>
                        <?php endif; ?>
                        | Son Giriş: <span id="currentTime"></span>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- User Panel JavaScript -->
    <script>
        // Anlık saat gösterimi
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('tr-TR');
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Saati her saniye güncelle
        setInterval(updateTime, 1000);
        updateTime(); // İlk yükleme
        
        // Sidebar aktif link kontrolü
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-nav .nav-link, .modern-sidebar .nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert.classList.contains('show')) {
                        alert.classList.remove('show');
                        setTimeout(function() {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 150);
                    } else {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 300);
                    }
                }, 5000);
            });
        });
        
        // Confirm dialogs for dangerous actions
        function confirmAction(message = 'Bu işlemi yapmak istediğinizden emin misiniz?') {
            return confirm(message);
        }
        
        // File size formatter
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('Panoya kopyalandı!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('Panoya kopyalandı!', 'success');
                } catch (err) {
                    showToast('Kopyalama işlemi başarısız!', 'error');
                }
                document.body.removeChild(textArea);
            }
        }
        
        // Simple toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        }
        
        // Loading overlay for forms
        function showFormLoading(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>İşleniyor...';
            }
        }
        
        function hideFormLoading(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Gönder';
            }
        }
        
        // Form submission with loading state
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-loading="true"]');
            forms.forEach(form => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.getAttribute('data-original-text')) {
                    submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                }
                
                form.addEventListener('submit', function() {
                    showFormLoading(form);
                });
            });
        });
        
        // Dinamik base path hesapla
        const basePath = window.location.pathname.includes('/user/') ? '../' : './';
        // AJAX Loading için global fonksiyonlar
        window.ajaxRequest = function(url, options = {}) {
            const defaultOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            return fetch(url, { ...defaultOptions, ...options })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    showToast('Bir hata oluştu: ' + error.message, 'error');
                    throw error;
                });
        };
        
        // Modal işlemleri
        window.showModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal && typeof bootstrap !== 'undefined') {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            }
        };
        
        window.hideModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal && typeof bootstrap !== 'undefined') {
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
        };
        
        // Notification sistemi için fonksiyonlar
        window.markNotificationRead = function(notificationId) {
            ajaxRequest(basePath + 'ajax/mark-notification-read.php', {
                method: 'POST',
                body: JSON.stringify({ notification_id: notificationId })
            }).then(response => {
                if (response.success) {
                    // Bildirim sayısını güncelle
                    updateNotificationCount();
                }
            }).catch(error => {
                console.error('Notification mark error:', error);
            });
        };
        
        window.markAllNotificationsRead = function() {
            ajaxRequest(basePath + 'ajax/mark-all-notifications-read.php', {
                method: 'POST'
            }).then(response => {
                if (response.success) {
                    // Sayfayı yenile
                    window.location.reload();
                }
            }).catch(error => {
                console.error('Mark all notifications error:', error);
            });
        };
        
        window.updateNotificationCount = function() {
            ajaxRequest(basePath + 'ajax/get-notification-count.php', {
                method: 'GET'
            }).then(response => {
                if (response.success) {
                    const badge = document.querySelector('#userNotificationDropdown .badge');
                    if (badge) {
                        if (response.count > 0) {
                            badge.textContent = response.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            }).catch(error => {
                console.error('Notification count error:', error);
            });
        };
        
        // Session timeout uyarısı
        let sessionWarningShown = false;
        const SESSION_TIMEOUT = 3600000; // 1 saat (milisaniye)
        const WARNING_TIME = 300000; // 5 dakika önceden uyar
        
        setTimeout(() => {
            if (!sessionWarningShown) {
                sessionWarningShown = true;
                if (confirm('Oturumunuz yakında sona erecek. Devam etmek istiyor musunuz?')) {
                    // Session'ı yenileme isteği gönder
                    ajaxRequest(basePath + 'ajax/refresh-session.php', {
                        method: 'POST'
                    }).then(response => {
                        if (response.success) {
                            sessionWarningShown = false;
                            showToast('Oturum yenilendi', 'success');
                        }
                    }).catch(error => {
                        console.error('Session refresh error:', error);
                    });
                } else {
                    window.location.href = '../logout.php';
                }
            }
        }, SESSION_TIMEOUT - WARNING_TIME);
        
        // Page visibility API ile sayfa aktifliğini kontrol et
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Sayfa aktif olduğunda bildirim sayısını güncelle
                updateNotificationCount();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + / için yardım modalı
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                showModal('helpModal');
            }
            
            // Esc tuşu ile aktif modalı kapat
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const modalId = activeModal.getAttribute('id');
                    if (modalId) {
                        hideModal(modalId);
                    }
                }
            }
        });
        
        // Print fonksiyonu
        window.printPage = function() {
            window.print();
        };
        
        // Export fonksiyonları
        window.exportToCSV = function(data, filename = 'export.csv') {
            const csv = data.map(row => 
                row.map(field => `"${field}"`).join(',')
            ).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
        
    </script>
    
    <!-- Ek JavaScript dosyaları için -->
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Sayfa özel JavaScript için -->
    <?php if (isset($pageJS)): ?>
        <script>
            <?php echo $pageJS; ?>
        </script>
    <?php endif; ?>

</body>
</html>