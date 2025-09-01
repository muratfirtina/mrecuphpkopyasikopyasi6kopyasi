<?php

/**
 * Dynamic Footer - Veritabanından Dinamik Footer
 */

// Veritabanı bağlantısını kontrol et
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

// Base path otomatik tanımla
if (!isset($basePath)) {
    $scriptName = $_SERVER['SCRIPT_NAME']; // /klasor/index.php
    $scriptDir = dirname($scriptName);     // /klasor
    if ($scriptDir === '/' || $scriptDir === '\\') {
        $basePath = '/';
    } else {
        $basePath = '/' . trim($scriptDir, '/\\') . '/';
    }
}

// Footer verilerini çek
try {
    if (!isset($pdo)) {
        throw new Exception('PDO connection not found');
    }

    // Hizmetlerimiz
    $servicesQuery = "SELECT name, slug FROM services ORDER BY name LIMIT 6";
    $servicesStmt = $pdo->prepare($servicesQuery);
    $servicesStmt->execute();
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Ürünlerimiz
    $categoriesQuery = "SELECT name, slug FROM categories ORDER BY name LIMIT 6";
    $categoriesStmt = $pdo->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // İletişim bilgisi
    $contactQuery = "SELECT contact_info FROM contact_cards ORDER BY id LIMIT 1";
    $contactStmt = $pdo->prepare($contactQuery);
    $contactStmt->execute();
    $contactInfo = $contactStmt->fetchColumn();

    // Ofis bilgisi
    $officeQuery = "SELECT address, working_hours FROM contact_office ORDER BY id LIMIT 1";
    $officeStmt = $pdo->prepare($officeQuery);
    $officeStmt->execute();
    $officeData = $officeStmt->fetch(PDO::FETCH_ASSOC);

    error_log("Footer Debug - Services: " . count($services) . ", Categories: " . count($categories));
} catch (Exception $e) {
    error_log("Footer Error: " . $e->getMessage());

    $services = [
        ['name' => 'ECU Yazılımları', 'slug' => 'ecu-yazilimlari'],
        ['name' => 'TCU Yazılımları', 'slug' => 'tcu-yazilimlari'],
        ['name' => 'Immobilizer', 'slug' => 'immobilizer'],
        ['name' => 'Chip Tuning', 'slug' => 'chip-tuning']
    ];

    $categories = [
        ['name' => 'ECU Modülleri', 'slug' => 'ecu-modulleri'],
        ['name' => 'TCU Modülleri', 'slug' => 'tcu-modulleri'],
        ['name' => 'Yazılım Araçları', 'slug' => 'yazilim-araclari'],
        ['name' => 'Donanım Ürünleri', 'slug' => 'donanim-urunleri']
    ];

    $contactInfo = 'E-posta: info@mrecu.com\nTelefon: +90 (555) 123 45 67';
    $officeData = ['address' => 'İstanbul, Türkiye', 'working_hours' => '7/24 Teknik Destek'];
}

// Base path ayarlama
$basePath = isset($basePath) ? $basePath : '/';
?>

</main>
<!-- Ana içerik sonu -->

<!-- Footer -->
<footer style="background-color: #071e3d;" class="text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Logo ve Açıklama -->
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="footer-brand mb-3">
                    <img src="<?php echo $basePath; ?>assets/images/mreculogomini.png"
                        alt="<?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>"
                        class="footer-logo mb-3" style="max-height: 60px;">
                    <h5 class="text-white mb-3">
                        <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>
                    </h5>
                </div>
                <p class="text-light mb-3" style="font-size: 0.9rem; line-height: 1.6;">
                    Profesyonel ECU hizmetleri ile araçlarınızın performansını maksimuma çıkarın.
                    Güvenli, hızlı ve kaliteli çözümler için bizi tercih edin.
                </p>
                <div class="social-links">
                    <a href="#" class="text-white me-3 footer-social-link" title="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                            <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
                        </svg>
                    </a>
                    <a href="#" class="text-white me-3 footer-social-link" title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                            <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
                        </svg>
                    </a>
                    <a href="#" class="text-white me-3 footer-social-link" title="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                            <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Hizmetlerimiz -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-white mb-3 footer-heading">
                    <i class="bi bi-gear-wide-connected me-2"></i>Hizmetlerimiz
                </h6>
                <ul class="list-unstyled footer-links">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <li class="mb-2">
                                <a href="<?php echo $basePath; ?>hizmet/<?php echo urlencode($service['slug']); ?>"
                                    class="text-light text-decoration-none footer-link">
                                    <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>hizmet/ecu-yazilimlari" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>ECU Yazılımları
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>hizmet/tcu-yazilimlari" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>TCU Yazılımları
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>hizmet/immobilizer" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Immobilizer
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>hizmet/chip-tuning" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Chip Tuning
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Ürünlerimiz -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-white mb-3 footer-heading">
                    <i class="fas fa-box me-2"></i>Ürünlerimiz
                </h6>
                <ul class="list-unstyled footer-links">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li class="mb-2">
                                <a href="<?php echo $basePath; ?>kategori/<?php echo urlencode($category['slug']); ?>"
                                    class="text-light text-decoration-none footer-link">
                                    <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>kategori/ecu-modulleri" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>ECU Modülleri
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>kategori/yazilim-araclari" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Yazılım Araçları
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>kategori/donanim-urunleri" class="text-light text-decoration-none footer-link">
                                <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Donanım Ürünleri
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Hızlı Bağlantılar -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-white mb-3 footer-heading">
                    <i class="fas fa-link me-2"></i>Hızlı Bağlantılar
                </h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2">
                        <a href="<?php echo $basePath; ?>index.php" class="text-light text-decoration-none footer-link">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo $basePath; ?>index.php#about" class="text-light text-decoration-none footer-link">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Hakkımızda
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo $basePath; ?>contact.php" class="text-light text-decoration-none footer-link">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>İletişim
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo $basePath; ?>blog.php" class="text-light text-decoration-none footer-link">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Blog
                        </a>
                    </li>
                </ul>
            </div>

            <!-- İletişim Bilgileri -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3 footer-heading">
                    <i class="fas fa-map-marker-alt me-2"></i>İletişim Bilgileri
                </h6>
                <ul class="list-unstyled footer-contact">
                    <!-- <?php if (!empty($contactInfo)): ?>
                        <li class="mb-3">
                            <div class="contact-info-block">
                                <?php echo nl2br(htmlspecialchars($contactInfo)); ?>
                            </div>
                        </li>
                    <?php endif; ?> -->

                    <?php if (!empty($officeData['address'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            <span class="text-light"><?php echo htmlspecialchars($officeData['address']); ?></span>
                        </li>
                    <?php endif; ?>

                    <!-- Telefon -->
                    <?php
                    $phoneQuery = "SELECT contact_info, contact_link FROM contact_cards WHERE id = 1 AND is_active = 1";
                    $phoneStmt = $pdo->prepare($phoneQuery);
                    $phoneStmt->execute();
                    $phoneData = $phoneStmt->fetch(PDO::FETCH_ASSOC);

                    if ($phoneData && !empty($phoneData['contact_info'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            <a href="<?php echo htmlspecialchars($phoneData['contact_link']); ?>"
                                class="text-light text-decoration-none footer-link">
                                <?php echo htmlspecialchars($phoneData['contact_info']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- E-posta -->
                    <?php
                    $emailQuery = "SELECT contact_info, contact_link FROM contact_cards WHERE id = 2 AND is_active = 1";
                    $emailStmt = $pdo->prepare($emailQuery);
                    $emailStmt->execute();
                    $emailData = $emailStmt->fetch(PDO::FETCH_ASSOC);

                    if ($emailData && !empty($emailData['contact_info'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <a href="<?php echo htmlspecialchars($emailData['contact_link']); ?>"
                                class="text-light text-decoration-none footer-link">
                                <?php echo htmlspecialchars($emailData['contact_info']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($officeData['working_hours'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2 text-primary"></i>
                            <span class="text-light"><?php echo htmlspecialchars($officeData['working_hours']); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">

        <!-- Alt Footer -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-light">
                    &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>.
                    Tüm hakları saklıdır.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-legal-links">
                    <a href="<?php echo $basePath; ?>privacy.php" class="text-light text-decoration-none me-3 footer-link">
                        Gizlilik Politikası
                    </a>
                    <a href="<?php echo $basePath; ?>terms.php" class="text-light text-decoration-none me-3 footer-link">
                        Kullanım Şartları
                    </a>
                    <a href="<?php echo $basePath; ?>kvkk.php" class="text-light text-decoration-none footer-link">
                        KVKK
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Footer Styles -->
<style>
    /* Footer Styling */
    footer {
        background-color: #071e3d !important;
        background-image: linear-gradient(135deg, #071e3d 0%, #0a2547 100%);
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }

    .footer-logo {
        max-height: 60px;
        filter: brightness(1.1);
        transition: transform 0.3s ease;
    }

    .footer-logo:hover {
        transform: scale(1.05);
    }

    .footer-heading {
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 1rem !important;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #007bff;
        display: inline-block;
    }

    .footer-links li {
        transition: all 0.3s ease;
    }

    .footer-link {
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .footer-link:hover {
        color: #007bff !important;
        transform: translateX(5px);
        text-decoration: none !important;
    }

    .footer-social-link {
        transition: all 0.3s ease;
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }

    .footer-social-link:hover {
        background: #007bff;
        color: white !important;
        transform: translateY(-3px);
        text-decoration: none;
    }

    .footer-contact li {
        margin-bottom: 0.75rem !important;
        display: flex;
        align-items: flex-start;
    }

    .footer-contact i {
        margin-top: 2px;
        width: 20px;
    }

    .contact-info-block {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #007bff;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .footer-legal-links a {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    .footer-legal-links a:hover {
        opacity: 1;
        color: #007bff !important;
    }

    /* Responsive Improvements */
    @media (max-width: 768px) {
        footer {
            text-align: center;
        }

        .footer-heading {
            display: block;
            width: 100%;
        }

        .footer-links,
        .footer-contact {
            text-align: left;
        }

        .social-links {
            text-align: center;
            margin-top: 1rem;
        }

        .footer-legal-links {
            text-align: center !important;
            margin-top: 1rem;
        }

        .footer-legal-links a {
            display: block;
            margin: 0.5rem 0 !important;
        }
    }

    /* Animation on scroll */
    .footer-links li:hover {
        transform: translateX(3px);
    }

    .footer-contact li:hover {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 5px;
        padding: 5px;
        margin: -5px;
    }
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.js"></script>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Main JavaScript -->
<script src="<?php echo $basePath; ?>assets/js/main.js"></script>

<!-- Custom JavaScript -->
<script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');

            if (href === '#' || href === '' || !href ||
                this.hasAttribute('data-bs-toggle') || this.hasAttribute('data-toggle')) {
                return;
            }

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
        if (navbar && window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else if (navbar) {
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

    // Footer animations
    document.addEventListener('DOMContentLoaded', function() {
        // Animate footer links
        const footerLinks = document.querySelectorAll('.footer-link');
        footerLinks.forEach((link, index) => {
            link.style.opacity = '0';
            link.style.transform = 'translateY(20px)';

            setTimeout(() => {
                link.style.transition = 'all 0.6s ease';
                link.style.opacity = '1';
                link.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<!-- Notification system for logged in users -->
<?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
    <script src="<?php echo $basePath; ?>assets/js/notifications.js"></script>
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