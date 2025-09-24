<?php
/**
 * Mr ECU - Kullanıcı Email Tercih Ayarları
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

$success = '';
$error = '';
$userId = $_SESSION['user_id'];

// Email tercihlerini getir veya oluştur
function getUserEmailPreferences($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_email_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$preferences) {
            // Varsayılan tercihler oluştur
            $prefId = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO user_email_preferences 
                (id, user_id, file_upload_notifications, file_ready_notifications, 
                 revision_notifications, additional_file_notifications, chat_message_notifications, marketing_emails) 
                VALUES (?, ?, 1, 1, 1, 1, 1, 0)
            ");
            $stmt->execute([$prefId, $userId]);
            
            // Yeni oluşturulan tercihleri getir
            $stmt = $pdo->prepare("SELECT * FROM user_email_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $preferences;
    } catch (PDOException $e) {
        error_log('getUserEmailPreferences error: ' . $e->getMessage());
        return null;
    }
}

// Tercihleri güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fileUploadNotifications = isset($_POST['file_upload_notifications']) ? 1 : 0;
        $fileReadyNotifications = isset($_POST['file_ready_notifications']) ? 1 : 0;
        $revisionNotifications = isset($_POST['revision_notifications']) ? 1 : 0;
        $additionalFileNotifications = isset($_POST['additional_file_notifications']) ? 1 : 0;
        $chatMessageNotifications = isset($_POST['chat_message_notifications']) ? 1 : 0;
        $marketingEmails = isset($_POST['marketing_emails']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE user_email_preferences 
            SET file_upload_notifications = ?, 
                file_ready_notifications = ?, 
                revision_notifications = ?, 
                additional_file_notifications = ?, 
                chat_message_notifications = ?,
                marketing_emails = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $result = $stmt->execute([
            $fileUploadNotifications,
            $fileReadyNotifications, 
            $revisionNotifications,
            $additionalFileNotifications,
            $chatMessageNotifications,
            $marketingEmails,
            $userId
        ]);
        
        if ($result) {
            $success = 'Email tercihleriniz başarıyla güncellendi.';
        } else {
            $error = 'Tercihleri güncellerken hata oluştu.';
        }
        
    } catch (PDOException $e) {
        error_log('Email preferences update error: ' . $e->getMessage());
        $error = 'Veritabanı hatası oluştu.';
    }
}

// Mevcut tercihleri getir
$preferences = getUserEmailPreferences($pdo, $userId);

$pageTitle = 'Email Tercihlerim';
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-envelope-gear me-2"></i>
                    Email Tercihlerim
                </h1>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-bell me-2"></i>
                                Email Bildirim Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($preferences): ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-file-arrow-up me-2"></i>
                                        Dosya İşlem Bildirimleri
                                    </h6>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="file_upload_notifications" name="file_upload_notifications"
                                               <?php echo $preferences['file_upload_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="file_upload_notifications">
                                            <strong>Dosya Yükleme Onayı</strong>
                                            <br><small class="text-muted">
                                                Dosyanız başarıyla yüklendiğinde email bildirimi alın
                                            </small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="file_ready_notifications" name="file_ready_notifications"
                                               <?php echo $preferences['file_ready_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="file_ready_notifications">
                                            <strong>Dosya Hazır Bildirimi</strong>
                                            <br><small class="text-muted">
                                                İşlenmiş dosyanız hazır olduğunda email bildirimi alın
                                            </small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="revision_notifications" name="revision_notifications"
                                               <?php echo $preferences['revision_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="revision_notifications">
                                            <strong>Revizyon Durum Bildirimleri</strong>
                                            <br><small class="text-muted">
                                                Revizyon taleplerinizin durumu hakkında email bildirimi alın
                                            </small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="additional_file_notifications" name="additional_file_notifications"
                                               <?php echo $preferences['additional_file_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="additional_file_notifications">
                                            <strong>Ek Dosya Bildirimleri</strong>
                                            <br><small class="text-muted">
                                                Size ek dosya gönderildiğinde email bildirimi alın
                                            </small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="chat_message_notifications" name="chat_message_notifications"
                                               <?php echo (isset($preferences['chat_message_notifications']) ? $preferences['chat_message_notifications'] : 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="chat_message_notifications">
                                            <strong>💬 Chat Mesajı Bildirimleri</strong>
                                            <br><small class="text-muted">
                                                Size chat mesajı gönderildiğinde email bildirimi alın
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-4">
                                    <h6 class="text-secondary mb-3">
                                        <i class="bi bi-megaphone me-2"></i>
                                        Pazarlama ve Tanıtım
                                    </h6>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               id="marketing_emails" name="marketing_emails"
                                               <?php echo $preferences['marketing_emails'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="marketing_emails">
                                            <strong>Pazarlama Email'leri</strong>
                                            <br><small class="text-muted">
                                                Yeni hizmetler, kampanyalar ve duyurular hakkında email alın
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i>
                                        Tercihleri Kaydet
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Email tercihleri yüklenemedi. Lütfen daha sonra tekrar deneyin.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Email Bilgileri
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Email Adresiniz</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                                </div>
                                <small class="text-muted">
                                    Email adresinizi değiştirmek için profil ayarlarınızı güncelleyin.
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Email Doğrulama Durumu</label>
                                <div>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
                                    $stmt->execute([$userId]);
                                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($user && $user['email_verified']):
                                    ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Doğrulanmış
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            Doğrulanmamış
                                        </span>
                                        <div class="mt-2">
                                            <a href="../verify.php" class="btn btn-sm btn-outline-warning">
                                                Email Doğrula
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-question-circle me-2"></i>
                                Yardım
                            </h6>
                        </div>
                        <div class="card-body">
                            <h6>Email Bildirimleri Hakkında</h6>
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="bi bi-dot"></i>
                                    Önemli bildirimler (dosya hazır, onaylar) kapatılamaz
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-dot"></i>
                                    Tercihlerinizi istediğiniz zaman değiştirebilirsiniz
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-dot"></i>
                                    Spam klasörünüzü kontrol etmeyi unutmayın
                                </li>
                            </ul>
                            
                            <div class="mt-3">
                                <a href="../contact.php" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-envelope me-1"></i>
                                    Destek İletişimi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tercih değişikliklerini takip et
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    let originalValues = {};
    
    // Orijinal değerleri sakla
    checkboxes.forEach(checkbox => {
        originalValues[checkbox.id] = checkbox.checked;
    });
    
    // Değişiklik kontrolü
    function checkForChanges() {
        let hasChanges = false;
        checkboxes.forEach(checkbox => {
            if (originalValues[checkbox.id] !== checkbox.checked) {
                hasChanges = true;
            }
        });
        
        // Save butonunu aktif/pasif et
        const saveBtn = document.querySelector('button[type="submit"]');
        if (saveBtn) {
            if (hasChanges) {
                saveBtn.classList.remove('btn-primary');
                saveBtn.classList.add('btn-success');
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Değişiklikleri Kaydet';
            } else {
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-primary');
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Tercihleri Kaydet';
            }
        }
    }
    
    // Change event'leri dinle
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', checkForChanges);
    });
});
</script>

<?php include '../includes/user_footer.php'; ?>
