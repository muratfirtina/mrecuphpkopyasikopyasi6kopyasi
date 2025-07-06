                    <!-- Sayfa İçeriği Sonu -->
                    
                </div>
            </div>
        </div>
    </div>
    <!-- Ana içerik sonu -->

    <!-- Admin Panel Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>
                        &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?> Admin Panel. 
                        Tüm hakları saklıdır.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>
                        Admin: <strong class="text-warning"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Bilinmiyor'; ?></strong>
                        | IP: <span class="text-info"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                        | Saat: <span id="currentTime" class="text-light"></span>
                        | <a href="logs.php" class="text-decoration-none text-light">Sistem Logları</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Admin Panel JavaScript -->
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
            const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
            
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
                }, 7000); // Admin panelinde biraz daha uzun süre göster
            });
        });
        
        // Confirm dialogs for dangerous admin actions
        function confirmAdminAction(message = 'Bu işlemi yapmak istediğinizden emin misiniz? Bu işlem geri alınamaz!') {
            return confirm(message);
        }
        
        // Bulk actions for admin tables
        function toggleAllCheckboxes(source) {
            const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
            const bulkActionsDiv = document.getElementById('bulkActions');
            
            if (bulkActionsDiv) {
                if (checkboxes.length > 0) {
                    bulkActionsDiv.style.display = 'block';
                    document.getElementById('selectedCount').textContent = checkboxes.length;
                } else {
                    bulkActionsDiv.style.display = 'none';
                }
            }
        }
        
        // Data table search functionality
        function filterTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        }
        
        // Admin notification system
        function showAdminNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, duration);
        }
        
        // Loading state for admin forms
        function showAdminFormLoading(form) {
            const submitBtns = form.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>İşleniyor...';
            });
        }
        
        function hideAdminFormLoading(form) {
            const submitBtns = form.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = btn.getAttribute('data-original-text') || 'Kaydet';
            });
        }
        
        // AJAX helper for admin operations
        function adminAjaxRequest(url, data, callback, method = 'POST') {
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: method === 'POST' ? JSON.stringify(data) : null
            })
            .then(response => response.json())
            .then(data => {
                if (callback) callback(data);
            })
            .catch(error => {
                console.error('Admin AJAX Error:', error);
                showAdminNotification('Bir hata oluştu: ' + error.message, 'danger');
            });
        }
        
        // Chart colors for admin charts
        const adminChartColors = {
            primary: '#007bff',
            secondary: '#6c757d',
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8',
            light: '#f8f9fa',
            dark: '#343a40'
        };
        
        // Format numbers for admin display
        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
        
        // Auto-refresh for admin dashboard (every 5 minutes)
        if (window.location.pathname.includes('admin/index.php') || window.location.pathname.endsWith('admin/')) {
            setInterval(function() {
                // Sadece sayfa açık ve görünürse yenile
                if (!document.hidden) {
                    location.reload();
                }
            }, 300000); // 5 dakika
        }
        
        // Session timeout warning for admin
        let sessionTimeout = <?php echo (ini_get('session.gc_maxlifetime') - 300) * 1000; ?>; // 5 dakika önceden uyar
        setTimeout(function() {
            if (confirm('Oturumunuz yakında sona erecek. Devam etmek istiyor musunuz?')) {
                // AJAX ile session yenile
                adminAjaxRequest('../session-refresh.php', {}, function(response) {
                    if (response.success) {
                        showAdminNotification('Oturumunuz yenilendi.', 'success');
                    }
                });
            }
        }, sessionTimeout);
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
