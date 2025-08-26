    </main>
    <!-- Ana içerik sonu -->

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-white mb-3">
                        <i class="fas fa-microchip me-2"></i>
                        <?php echo SITE_NAME; ?>
                    </h5>
                    <p class="text-light mb-3">
                        Profesyonel ECU hizmetleri ile araçlarınızın performansını maksimuma çıkarın. 
                        Güvenli, hızlı ve kaliteli çözümler için bizi tercih edin.
                    </p>
                    <div class="social-links">
                        <!-- <a href="#" class="text-white me-3" title="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="text-white me-3" title="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-white me-3" title="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white me-3" title="LinkedIn">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                        <a href="#" class="text-white" title="YouTube">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a> -->
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="text-white mb-3">Hızlı Bağlantılar</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php" class="text-light text-decoration-none">
                                <i class="fas fa-home me-1"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#services" class="text-light text-decoration-none">
                                <i class="fas fa-cogs me-1"></i>Hizmetler
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#about" class="text-light text-decoration-none">
                                <i class="fas fa-info-circle me-1"></i>Hakkımızda
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#contact" class="text-light text-decoration-none">
                                <i class="fas fa-envelope me-1"></i>İletişim
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h6 class="text-white mb-3">Hizmetlerimiz</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">
                                <i class="fas fa-microchip me-1"></i>ECU Yazılımları
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">
                                <i class="fas fa-cogs me-1"></i>TCU Yazılımları
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">
                                <i class="fas fa-key me-1"></i>Immobilizer
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none">
                                <i class="fas fa-tachometer-alt me-1"></i>Chip Tuning
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">İletişim Bilgileri</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo defined('SITE_EMAIL') ? SITE_EMAIL : 'info@mrecu.com'; ?>" class="text-light text-decoration-none">
                                <?php echo defined('SITE_EMAIL') ? SITE_EMAIL : 'info@mrecu.com'; ?>
                            </a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <span class="text-light">+90 (555) 123 45 67</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span class="text-light">İstanbul, Türkiye</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            <span class="text-light">7/24 Teknik Destek</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">
                        &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>. 
                        Tüm hakları saklıdır.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">
                        <a href="#" class="text-light text-decoration-none me-3">Gizlilik Politikası</a>
                        <a href="#" class="text-light text-decoration-none me-3">Kullanım Şartları</a>
                        <a href="#" class="text-light text-decoration-none">KVKK</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS JS - CDN değiştir -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.js"></script>
    
    <!-- jQuery - CDN değiştir -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Main JavaScript -->
    <script src="<?php echo isset($basePath) ? $basePath : ''; ?>assets/js/main.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                
                // Skip if href is just "#", empty, or has dropdown/modal attributes
                if (href === '#' || href === '' || !href || 
                    this.hasAttribute('data-bs-toggle') || this.hasAttribute('data-toggle')) {
                    return;
                }
                
                // Validate the selector before using it
                try {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                } catch (error) {
                    console.warn('Invalid selector:', href);
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Auto-hide alerts after 5 seconds
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
                    }
                }, 5000);
            });
        });

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                return isValid;
            }
            return false;
        }

        // Loading overlay helper
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                    <p class="mt-3">Lütfen bekleyin...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
    </script>
    
    <!-- Notification system for logged in users -->
    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
        <script src="<?php echo isset($basePath) ? $basePath : ''; ?>assets/js/notifications.js"></script>
    <?php endif; ?>
    
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
