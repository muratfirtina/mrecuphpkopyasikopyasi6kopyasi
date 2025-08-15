<?php
/**
 * Mr ECU - Kullanıcı Dosya İptal Talepleri
 * User File Cancellation Requests
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/cancellations.php');
}

$user = new User($pdo);
$userId = $_SESSION['user_id'];

// GUID format kontrolü
if (!isValidUUID($userId)) {
    redirect('../logout.php');
}

// FileCancellationManager'ı yükle
require_once '../includes/FileCancellationManager.php';
$cancellationManager = new FileCancellationManager($pdo);

$error = '';
$success = '';

// Session mesajlarını al
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;

// Kullanıcının iptal taleplerini getir
$cancellations = $cancellationManager->getUserCancellations($userId, $page, $limit);

$pageTitle = 'İptal Taleplerim';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-times-circle me-2 text-danger"></i>İptal Taleplerim
                    </h1>
                    <p class="text-muted mb-0">Gönderdiğiniz dosya iptal taleplerini görüntüleyin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i>Dosyalarıma Dön
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Hata!</strong> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Başarılı!</strong> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- İptal Talepleri Listesi -->
            <?php if (empty($cancellations)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>Henüz iptal talebi bulunmuyor</h5>
                        <p class="text-muted mb-4">
                            Dosyalarınızı iptal etmek istediğinizde buradan takip edebilirsiniz.
                        </p>
                        <a href="files.php" class="btn btn-primary">
                            <i class="fas fa-folder me-2"></i>Dosyalarım
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($cancellations as $cancellation): ?>
                        <div class="col-12 mb-4">
                            <div class="card cancellation-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">
                                            <span class="badge bg-secondary me-2"><?php echo strtoupper($cancellation['file_type']); ?></span>
                                            İptal Talebi
                                        </h6>
                                        <small class="text-muted">
                                            Talep ID: <?php echo htmlspecialchars(substr($cancellation['id'], 0, 8)); ?>...
                                        </small>
                                    </div>
                                    <div>
                                        <?php
                                        $statusConfig = [
                                            'pending' => ['class' => 'warning', 'text' => 'Bekleyen', 'icon' => 'clock'],
                                            'approved' => ['class' => 'success', 'text' => 'Onaylandı', 'icon' => 'check'],
                                            'rejected' => ['class' => 'danger', 'text' => 'Reddedildi', 'icon' => 'times']
                                        ];
                                        $config = $statusConfig[$cancellation['status']] ?? ['class' => 'secondary', 'text' => 'Bilinmiyor', 'icon' => 'question'];
                                        ?>
                                        <span class="badge bg-<?php echo $config['class']; ?> fs-6">
                                            <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i>
                                            <?php echo $config['text']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-comment-dots me-1"></i>
                                                İptal Sebebiniz:
                                            </h6>
                                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($cancellation['reason'])); ?></p>
                                            
                                            <?php if ($cancellation['status'] !== 'pending' && !empty($cancellation['admin_notes'])): ?>
                                                <h6 class="text-info mb-2">
                                                    <i class="fas fa-user-shield me-1"></i>
                                                    Admin Yanıtı:
                                                </h6>
                                                <div class="alert alert-light border-start border-info border-3">
                                                    <?php echo nl2br(htmlspecialchars($cancellation['admin_notes'])); ?>
                                                    <?php if (!empty($cancellation['admin_username'])): ?>
                                                        <br><small class="text-muted">
                                                            - <?php echo htmlspecialchars($cancellation['admin_username']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Dosya ID</small>
                                                        <strong><?php echo htmlspecialchars(substr($cancellation['file_id'], 0, 8)); ?>...</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Dosya Tipi</small>
                                                        <strong><?php echo strtoupper($cancellation['file_type']); ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Talep Tarihi</small>
                                                        <strong><?php echo date('d.m.Y', strtotime($cancellation['requested_at'])); ?></strong>
                                                        <br><small><?php echo date('H:i', strtotime($cancellation['requested_at'])); ?></small>
                                                    </div>
                                                    <div class="col-6">
                                                        <?php if ($cancellation['credits_to_refund'] > 0): ?>
                                                            <small class="text-muted d-block">Kredi İadesi</small>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-coins me-1"></i>
                                                                <?php echo number_format($cancellation['credits_to_refund'], 2); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <small class="text-muted d-block">Kredi İadesi</small>
                                                            <span class="text-muted">Yok</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($cancellation['status'] !== 'pending' && !empty($cancellation['processed_at'])): ?>
                                    <div class="card-footer text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            İşlem tarihi: <?php echo date('d.m.Y H:i', strtotime($cancellation['processed_at'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.cancellation-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.cancellation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.cancellation-card .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 10px 10px 0 0;
}

.info-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #007bff;
}

.info-box small {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.alert-light {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.border-3 {
    border-width: 3px !important;
}
</style>

<?php
// Footer include
include '../includes/user_footer.php';
?>
