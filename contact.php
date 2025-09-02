<?php
/**
 * Mr ECU - İletişim Sayfası (Modern & Profesyonel Tasarım)
 */

require_once 'config/config.php';
require_once 'config/database.php';

$error = '';
$success = '';

// Database'den verileri çek
try {
    $settings_stmt = $pdo->prepare("SELECT * FROM contact_settings WHERE is_active = 1 LIMIT 1");
    $settings_stmt->execute();
    $contact_settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

    $cards_stmt = $pdo->prepare("SELECT * FROM contact_cards WHERE is_active = 1 ORDER BY order_no ASC");
    $cards_stmt->execute();
    $contact_cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);

    $office_stmt = $pdo->prepare("SELECT * FROM contact_office WHERE is_active = 1 LIMIT 1");
    $office_stmt->execute();
    $office_info = $office_stmt->fetch(PDO::FETCH_ASSOC);

    $form_stmt = $pdo->prepare("SELECT * FROM contact_form_settings WHERE is_active = 1 LIMIT 1");
    $form_stmt->execute();
    $form_settings = $form_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $contact_settings = [];
    $contact_cards = [];
    $office_info = [];
    $form_settings = [];
    error_log("Contact page error: " . $e->getMessage());
}

// Sayfa Bilgileri
$pageTitle = $contact_settings['page_title'] ?? 'İletişim | Mr ECU';
$pageDescription = $contact_settings['page_description'] ?? 'Mr ECU ekibiyle iletişime geçin. Uzman teknik destek, ECU & chip tuning çözümleri için buradayız.';
$pageKeywords = 'ecu tuning, chip tuning, immobilizer, iletisim, mr ecu, teknik destek';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz e-posta adresi.';
    } elseif (strlen($message) < 10) {
        $error = 'Mesaj en az 10 karakter olmalı.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $name, $email, $phone, $subject, $message,
                $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            $success = $form_settings['success_message'] ?? 'Mesajınız gönderildi. En kısa sürede dönüş yapacağız.';
        } catch (PDOException $e) {
            error_log('Form error: ' . $e->getMessage());
            $success = 'Mesaj alındı. 24 saat içinde dönüş yapacağız.';
        }
    }
}

// Konu Seçenekleri
$subject_options = json_decode($form_settings['subject_options'] ?? '', true) ?: [
    'Genel Bilgi', 'ECU Tuning', 'Chip Tuning', 'İmmobilizer', 'DPF/EGR Off', 'Teknik Destek', 'Faturalama', 'Diğer'
];

include 'includes/header.php';
?>

<style>
/* === MODERN TASARIM STİLLERİ === */
:root {
    --primary: #001f3f;
    --primary-dark: #00142a;
    --secondary: #0074D9;
    --success: #2ECC40;
    --light: #f8f9fa;
    --dark: #111;
    --gray: #6c757d;
    --border-radius: 16px;
    --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
    --transition: all 0.3s ease;
}

/* === Page Header === */
.page-header {
    background: linear-gradient(135deg, var(--primary), #003366);
    color: white;
    border-radius: 0 0 40px 40px;
    height: 380px;
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: url('assets/images/contact-bg.svg') no-repeat center;
    background-size: cover;
    opacity: 0.1;
    z-index: 1;
}
.page-header .container {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    align-items: center;
}
.page-header h1 {
    font-size: 3.5rem;
    font-weight: 800;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
.page-header p {
    font-size: 1.25rem;
    opacity: 0.9;
}

/* === Breadcrumb === */
.breadcrumb {
    background: transparent;
    padding: 0;
}
.breadcrumb .breadcrumb-item a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
}
.breadcrumb .breadcrumb-item.active {
    color: white;
    font-weight: 600;
}

/* === Contact Cards === */
.contact-cards {
    margin-top: -60px;
    position: relative;
    z-index: 3;
}
.contact-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #eee;
}
.contact-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}
.contact-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 2rem auto 1rem;
    font-size: 1.8rem;
    box-shadow: 0 8px 20px rgba(0, 31, 63, 0.2);
}
.contact-card h5 {
    color: var(--primary);
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.contact-card p.text-muted {
    color: #666 !important;
    font-size: 0.95rem;
}
.contact-details h6 {
    color: var(--primary-dark);
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.contact-details small {
    color: #888;
    font-size: 0.9rem;
}
.contact-card .btn {
    margin-top: auto;
    align-self: flex-start;
    margin-left: 1.5rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    padding: 0.6rem 1.4rem;
    border-radius: 30px;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(0, 31, 63, 0.2);
}
.contact-card .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 31, 63, 0.3);
}

/* === Form Section === */
.form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}
.form-header {
    background: linear-gradient(135deg, var(--primary), #003366);
    color: white;
    padding: 2.5rem;
    text-align: center;
}
.form-header h3 {
    font-weight: 700;
    font-size: 1.8rem;
}
.form-body {
    padding: 3rem;
}
.form-control, .form-select {
    border: 1.5px solid #ddd;
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    transition: var(--transition);
}
.form-control:focus, .form-select:focus {
    border-color: var(--secondary);
    box-shadow: 0 0 0 0.2rem rgba(0, 116, 217, 0.15);
}
label {
    font-weight: 600;
    color: var(--primary);
}
textarea.form-control {
    min-height: 140px;
    resize: vertical;
}
.btn-submit {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    padding: 0.9rem 2.5rem;
    font-size: 1.1rem;
    border-radius: 30px;
    font-weight: 600;
    box-shadow: 0 6px 18px rgba(0, 31, 63, 0.3);
    transition: var(--transition);
    width: 100%;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 31, 63, 0.4);
}

/* === Office Section === */
.office-info {
    background: var(--light);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: var(--shadow-sm);
}
.office-info h2 {
    color: var(--primary);
    font-weight: 700;
    margin-bottom: 1.5rem;
}
.info-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}
.info-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
    color: white;
}
.info-icon.bg-primary { background: var(--primary); }
.info-icon.bg-success { background: var(--success); }
.info-icon.bg-info { background: #00C9FF; }
.info-content h6 {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 0.3rem;
}
.info-content p {
    color: #555;
    margin-bottom: 0;
}
.map-container iframe {
    width: 100%;
    height: 400px;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    border: none;
}

/* === Modal === */
.modal-content {
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
}
.modal-header {
    background: var(--primary);
    color: white;
    border-bottom: none;
    border-radius: calc(var(--border-radius) - 1px) calc(var(--border-radius) - 1px) 0 0;
}
.modal-footer {
    border-top: none;
}

/* === Responsive === */
@media (max-width: 768px) {
    .page-header h1 { font-size: 2.5rem; }
    .form-body, .office-info { padding: 1.5rem; }
    .contact-card .btn { margin-left: 1rem; }
    .info-icon { width: 44px; height: 44px; font-size: 1rem; }
}
</style>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1><?php echo htmlspecialchars($contact_settings['header_title'] ?? 'İletişim'); ?></h1>
                <p class="lead"><?php echo htmlspecialchars($contact_settings['header_subtitle'] ?? 'Uzman ekibimize ulaşın. Her soruya çözüm üretiyoruz.'); ?></p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-home"></i> Ana Sayfa</a></li>
                        <li class="breadcrumb-item active">İletişim</li>
                    </ol>
                </nav>
            </div>
            <div class="col-lg-4 d-flex align-items-center justify-content-center">
                <i class="bi bi-comments fa-7x opacity-20 text-white"></i>
            </div>
        </div>
    </div>
</section>

<!-- İletişim Kartları -->
<?php if (!empty($contact_cards)): ?>
<section class="py-5">
    <div class="container contact-cards">
        <div class="row g-4 justify-content-center">
            <?php foreach ($contact_cards as $card): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="<?php echo htmlspecialchars($card['icon']); ?>"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($card['title']); ?></h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($card['description'])); ?></p>
                            <div class="contact-details">
                                <h6><?php echo htmlspecialchars($card['contact_info']); ?></h6>
                                <?php if ($card['availability_text']): ?>
                                    <small><?php echo htmlspecialchars($card['availability_text']); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if ($card['contact_link'] && $card['button_text']): ?>
                                <a href="<?php echo htmlspecialchars($card['contact_link']); ?>"
                                   class="btn btn-sm"
                                   target="_blank">
                                    <i class="<?php echo htmlspecialchars($card['icon']); ?> me-1"></i>
                                    <?php echo htmlspecialchars($card['button_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- İletişim Formu -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="form-container">
                    <div class="form-header">
                        <h3>
                            <i class="bi bi-send me-2"></i>
                            <?php echo htmlspecialchars($form_settings['form_title'] ?? 'Bize Mesaj Gönderin'); ?>
                        </h3>
                        <p><?php echo htmlspecialchars($form_settings['form_subtitle'] ?? 'İhtiyacınız olan her şey için buradayız.'); ?></p>
                    </div>
                    <div class="form-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger fade show">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success fade show">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$success): ?>
                            <form method="POST" id="contactForm">
                                <input type="hidden" name="send_message" value="1">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Ad Soyad *</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-posta *</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="subject" class="form-label">Konu *</label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Seçiniz...</option>
                                            <?php foreach ($subject_options as $opt): ?>
                                                <option value="<?php echo htmlspecialchars($opt); ?>"
                                                    <?php echo (($_POST['subject'] ?? '') === $opt) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($opt); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4 mt-4">
                                    <label for="message" class="form-label">Mesajınız *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5"
                                              placeholder="Detaylı bilgi verin..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                    <div class="form-text text-end" id="messageCounter">0/1000</div>
                                </div>
                                <?php if ($form_settings['enable_privacy_checkbox']): ?>
                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input" id="privacy" name="privacy" required>
                                        <label class="form-check-label" for="privacy">
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Gizlilik Politikası</a>'nı kabul ediyorum. *
                                        </label>
                                    </div>
                                <?php endif; ?>
                                <button type="submit" class="btn-submit">Mesajı Gönder</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Ofis Bilgileri -->
<?php if (!empty($office_info)): ?>
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="office-info">
                    <h2><?php echo htmlspecialchars($office_info['title']); ?></h2>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($office_info['description'])); ?></p>

                    <div class="info-item">
                        <div class="info-icon bg-primary"><i class="bi bi-map-marker-alt"></i></div>
                        <div class="info-content">
                            <h6>Adres</h6>
                            <p><?php echo nl2br(htmlspecialchars($office_info['address'])); ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon bg-success"><i class="bi bi-clock"></i></div>
                        <div class="info-content">
                            <h6>Çalışma Saatleri</h6>
                            <p><?php echo nl2br(htmlspecialchars($office_info['working_hours'])); ?></p>
                        </div>
                    </div>

                    <?php if ($office_info['transportation']): ?>
                        <div class="info-item">
                            <div class="info-icon bg-info"><i class="bi bi-subway"></i></div>
                            <div class="info-content">
                                <h6>Ulaşım</h6>
                                <p><?php echo nl2br(htmlspecialchars($office_info['transportation'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($office_info['google_maps_link']): ?>
                        <a href="<?php echo htmlspecialchars($office_info['google_maps_link']); ?>" target="_blank"
                           class="btn btn-outline-primary mt-3">
                            <i class="bi bi-map-marker-alt me-2"></i>Yol Tarifi Al
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="map-container">
                    <?php echo $contact_settings['google_maps_embed'] ?? '<iframe src="https://www.google.com/maps/embed?..."></iframe>'; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Privacy Modal -->
<?php if ($form_settings['enable_privacy_checkbox']): ?>
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gizlilik Politikası</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php echo $contact_settings['privacy_policy_content'] ?? '
                <p>İletişim formundan aldığımız kişisel veriler sadece size dönüş yapmak amacıyla kullanılır.</p>
                <ul><li>Ad, e-posta, telefon, mesaj içeriği.</li><li>Hiçbir veri üçüncü tarafla paylaşılmaz.</li></ul>
                '; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// JavaScript
$pageJS = "
document.getElementById('message').addEventListener('input', function() {
    const counter = document.getElementById('messageCounter');
    const len = this.value.length;
    counter.textContent = len + '/1000';
    if (len > 900) counter.className = 'form-text text-end text-danger';
    else if (len > 800) counter.className = 'form-text text-end text-warning';
    else counter.className = 'form-text text-end text-muted';
});

// Auto-resize textarea
const textarea = document.getElementById('message');
textarea.style.height = textarea.scrollHeight + 'px';
textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Phone formatting
const phone = document.getElementById('phone');
if (phone) {
    phone.addEventListener('input', function(e) {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 10) val = val.slice(0, 10);
        if (val.length > 6) {
            e.target.value = val.slice(0,3) + ' ' + val.slice(3,6) + ' ' + val.slice(6);
        } else if (val.length > 3) {
            e.target.value = val.slice(0,3) + ' ' + val.slice(3);
        } else {
            e.target.value = val;
        }
    });
}
";

include 'includes/footer.php';
?>