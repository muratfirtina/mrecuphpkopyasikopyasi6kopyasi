<?php

/**
 * Enhanced Dynamic Footer - WhatsApp ve Scroll to Top Butonları ile
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
    
    // WhatsApp numarasını al (contact_cards tablosundan WhatsApp kaydını ara)
    $whatsappQuery = "SELECT contact_info, contact_link FROM contact_cards WHERE contact_info LIKE '%whatsapp%' OR contact_link LIKE '%wa.me%' OR contact_link LIKE '%whatsapp%' LIMIT 1";
    $whatsappStmt = $pdo->prepare($whatsappQuery);
    $whatsappStmt->execute();
    $whatsappData = $whatsappStmt->fetch(PDO::FETCH_ASSOC);
    
    // Sosyal medya linklerini al
    $socialQuery = "SELECT name, icon, url FROM social_media_links WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
    $socialStmt = $pdo->prepare($socialQuery);
    $socialStmt->execute();
    $socialLinks = $socialStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Design ayarlarını al (Terms ve Privacy için)
    $settingsQuery = "SELECT setting_key, setting_value FROM design_settings WHERE setting_key IN ('terms_of_service_title', 'terms_of_service_content', 'privacy_policy_title', 'privacy_policy_content')";
    $settingsStmt = $pdo->prepare($settingsQuery);
    $settingsStmt->execute();
    $designSettings = [];
    while ($settingRow = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $designSettings[$settingRow['setting_key']] = $settingRow['setting_value'];
    }
    
    // Eğer WhatsApp kaydı bulunamazsa, telefon numarasını kullan
    if (!$whatsappData) {
        $phoneQuery = "SELECT contact_info FROM contact_cards WHERE contact_link LIKE '%tel:%' LIMIT 1";
        $phoneStmt = $pdo->prepare($phoneQuery);
        $phoneStmt->execute();
        $phoneData = $phoneStmt->fetch(PDO::FETCH_ASSOC);
        if ($phoneData) {
            $whatsappData = ['contact_info' => $phoneData['contact_info']];
        }
    }

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
    $officeData = ['address' => 'İstanbul, Türkiye', 'working_hours' => 'Teknik Destek'];
    $whatsappData = ['contact_info' => '+90 (555) 123 45 67']; // Varsayılan WhatsApp numarası
    
    // Varsayılan sosyal medya linkleri
    $socialLinks = [
        ['name' => 'Facebook', 'icon' => 'bi-facebook', 'url' => ''],
        ['name' => 'Instagram', 'icon' => 'bi-instagram', 'url' => ''],
        ['name' => 'LinkedIn', 'icon' => 'bi-linkedin', 'url' => '']
    ];
    
    // Varsayılan design ayarları
    $designSettings = [
        'terms_of_service_title' => 'Kullanım Şartları',
        'terms_of_service_content' => '',
        'privacy_policy_title' => 'Gizlilik Politikası',
        'privacy_policy_content' => ''
    ];
}

// Base path ayarlama
$basePath = isset($basePath) ? $basePath : '/';
?>

</main>
<!-- Ana içerik sonu -->

<!-- WhatsApp Floating Button -->
<?php if (!empty($whatsappData['contact_info'])): ?>
    <?php 
    // Telefon numarasından sadece rakamları al
    $whatsappNumber = preg_replace('/[^0-9]/', '', $whatsappData['contact_info']); 
    // Türkiye için +90 ile başlamıyorsa ekle
    if (strlen($whatsappNumber) === 10 && substr($whatsappNumber, 0, 2) !== '90') {
        $whatsappNumber = '90' . $whatsappNumber;
    }
    ?>
    <div class="whatsapp-floating-btn">
        <a href="https://wa.me/<?php echo $whatsappNumber; ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-btn" title="WhatsApp ile İletişim">
            <i class="bi bi-whatsapp"></i>
            <span class="whatsapp-tooltip">WhatsApp ile İletişim</span>
        </a>
    </div>
<?php endif; ?>

<!-- Phone Floating Button -->
<?php 
// Telefon numarasını al
$phoneQuery = "SELECT contact_info, contact_link FROM contact_cards WHERE id = 1 AND is_active = 1";
$phoneStmt = $pdo->prepare($phoneQuery);
$phoneStmt->execute();
$phoneCallData = $phoneStmt->fetch(PDO::FETCH_ASSOC);

if ($phoneCallData && !empty($phoneCallData['contact_info'])): ?>
    <div class="phone-floating-btn">
        <a href="<?php echo htmlspecialchars($phoneCallData['contact_link']); ?>" class="phone-btn" title="Telefon ile Ara">
            <i class="bi bi-telephone"></i>
            <span class="phone-tooltip">Telefon ile Ara</span>
        </a>
    </div>
<?php endif; ?>

<!-- Scroll to Top Button -->
<div class="scroll-to-top-btn" id="scrollToTopBtn">
    <button type="button" class="scroll-btn" onclick="scrollToTop()" title="Sayfa Başına Git">
        <i class="bi bi-arrow-up"></i>
    </button>
</div>

<!-- Footer -->
<footer style="background-color: #071e3d;" class="text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Logo ve Açıklama -->
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="footer-brand mb-3">
                    <img src="<?php echo $basePath; ?>assets/images/mrecutuning.png"
                        alt="<?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>"
                        class="footer-logo mb-3" style="max-height: 160px;">
<!--                     <h5 class="text-white mb-3">
                        <?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?>
                    </h5> -->
                </div>
<!--                 <p class="text-light mb-3" style="font-size: 0.9rem; line-height: 1.6;">
                    Profesyonel ECU hizmetleri ile araçlarınızın performansını maksimuma çıkarın.
                    Güvenli, hızlı ve kaliteli çözümler için bizi tercih edin.
                </p> -->
                <div class="social-links">
                    <?php if (!empty($socialLinks)): ?>
                        <?php foreach ($socialLinks as $link): ?>
                            <?php if (!empty($link['url'])): ?>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer"
                                   class="text-white me-3 footer-social-link" title="<?php echo htmlspecialchars($link['name']); ?>">
                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-white me-3 footer-social-link opacity-50" 
                                      title="<?php echo htmlspecialchars($link['name']); ?> (Link henüz eklenmemiş)">
                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Varsayılan sosyal medya linkleri -->
                        <a href="#" class="text-white me-3 footer-social-link" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="text-white me-3 footer-social-link" title="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="text-white me-3 footer-social-link" title="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hizmetlerimiz -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-white mb-3 footer-heading">
                    <i class="fas fa-cogs me-2"></i>Hizmetlerimiz
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
                    <i class="bi bi-geo-alt me-2"></i>İletişim Bilgileri
                </h6>
                <ul class="list-unstyled footer-contact">
                    <?php if (!empty($officeData['address'])): ?>
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2 text-primary"></i>
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
                            <i class="bi bi-telephone me-2 text-primary"></i>
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
                            <i class="bi bi-envelope-at me-2 text-primary"></i>
                            <a href="<?php echo htmlspecialchars($emailData['contact_link']); ?>"
                                class="text-light text-decoration-none footer-link">
                                <?php echo htmlspecialchars($emailData['contact_info']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($officeData['working_hours'])): ?>
                        <li class="mb-2">
                            <i class="bi bi-clock me-2 text-primary"></i>
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

<!-- Floating Buttons ve Footer Styles -->
<style>
    /* WhatsApp Floating Button */
    .whatsapp-floating-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .whatsapp-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: white !important;
        border-radius: 50%;
        text-decoration: none !important;
        box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .whatsapp-btn i {
        font-size: 28px;
        transition: transform 0.3s ease;
    }

    .whatsapp-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(37, 211, 102, 0.6);
        color: white !important;
    }

    .whatsapp-btn:hover i {
        transform: scale(1.1);
    }

    .whatsapp-tooltip {
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
        white-space: nowrap;
        margin-right: 15px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .whatsapp-tooltip::after {
        content: '';
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 5px solid transparent;
        border-left-color: rgba(0, 0, 0, 0.8);
    }

    .whatsapp-btn:hover .whatsapp-tooltip {
        opacity: 1;
        visibility: visible;
        margin-right: 10px;
    }

    /* Scroll to Top Button */
    .scroll-to-top-btn {
        position: fixed;
        bottom: 50px;
        left: 50px;
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .scroll-to-top-btn.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .scroll-btn i {
        font-size: 18px;
        transition: transform 0.3s ease;
    }

    .scroll-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6);
        background: linear-gradient(135deg, #c82333, #a71e2a);
    }

    .scroll-btn:hover i {
        transform: translateY(-2px);
    }

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
        .whatsapp-floating-btn {
            bottom: 20px;
            right: 20px;
        }


        .whatsapp-btn i {
            font-size: 24px;
        }

        .scroll-to-top-btn {
            bottom: 20px;
            left: 20px;
        }

        .scroll-btn {
            width: 45px;
            height: 45px;
        }

        .whatsapp-tooltip,
        .phone-tooltip {
            display: none;
        }

        .phone-floating-btn {
            bottom: 85px; /* Mobilde WhatsApp butonunun üstünde */
            right: 20px;
        }

        .phone-btn i {
            font-size: 24px;
        }

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

    /* Pulse animation for WhatsApp button */
    @keyframes whatsappPulse {
        0% {
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
        }
        50% {
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.8);
        }
        100% {
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
        }
    }

    .whatsapp-btn {
        animation: whatsappPulse 2s ease-in-out infinite;
    }

    /* Phone Floating Button */
    .phone-floating-btn {
        position: fixed;
        bottom: 95px; /* WhatsApp butonunun üstüne yerleştir */
        right: 20px;
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .phone-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white !important;
        border-radius: 50%;
        text-decoration: none !important;
        box-shadow: 0 4px 20px rgba(0, 123, 255, 0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .phone-btn i {
        font-size: 28px;
        transition: transform 0.3s ease;
    }

    .phone-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(0, 123, 255, 0.6);
        color: white !important;
    }

    .phone-btn:hover i {
        transform: scale(1.1);
    }

    .phone-tooltip {
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
        white-space: nowrap;
        margin-right: 15px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .phone-tooltip::after {
        content: '';
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 5px solid transparent;
        border-left-color: rgba(0, 0, 0, 0.8);
    }

    .phone-btn:hover .phone-tooltip {
        opacity: 1;
        visibility: visible;
        margin-right: 10px;
    }

    /* Phone button pulse animation */
    @keyframes phonePulse {
        0% {
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.4);
        }
        50% {
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.8);
        }
        100% {
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.4);
        }
    }

    .phone-btn {
        animation: phonePulse 2.5s ease-in-out infinite;
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

<!-- Enhanced Footer JavaScript -->
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

    // Scroll to Top Function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Show/Hide Scroll to Top Button
    window.addEventListener('scroll', function() {
        const scrollBtn = document.getElementById('scrollToTopBtn');
        const navbar = document.querySelector('.navbar');
        
        // Scroll to top button
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
        
        // Navbar scroll effect
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

        // WhatsApp Button Click Analytics (optional)
        const whatsappBtn = document.querySelector('.whatsapp-btn');
        if (whatsappBtn) {
            whatsappBtn.addEventListener('click', function() {
                // Buraya analytics kodu ekleyebilirsiniz
                console.log('WhatsApp button clicked');
                
                // Google Analytics event örneği (eğer GA kuruluysa)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        'event_category': 'WhatsApp',
                        'event_label': 'Floating Button'
                    });
                }
            });
        }

        // Phone Button Click Analytics (optional)
        const phoneBtn = document.querySelector('.phone-btn');
        if (phoneBtn) {
            phoneBtn.addEventListener('click', function() {
                // Buraya analytics kodu ekleyebilirsiniz
                console.log('Phone button clicked');
                
                // Google Analytics event örneği (eğer GA kuruluysa)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        'event_category': 'Phone',
                        'event_label': 'Floating Button'
                    });
                }
            });
        }
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

    // Keyboard accessibility for floating buttons
    document.addEventListener('keydown', function(e) {
        // Alt + W for WhatsApp
        if (e.altKey && e.key === 'w') {
            const whatsappBtn = document.querySelector('.whatsapp-btn');
            if (whatsappBtn) {
                whatsappBtn.click();
            }
        }
        
        // Alt + P for Phone
        if (e.altKey && e.key === 'p') {
            const phoneBtn = document.querySelector('.phone-btn');
            if (phoneBtn) {
                phoneBtn.click();
            }
        }
        
        // Alt + T for Top
        if (e.altKey && e.key === 't') {
            scrollToTop();
        }
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