<?php
/**
 * Mr ECU - Admin File Detail Additional Cancel Functionality
 * Admin dosya iptal özelliği eklentisi
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

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

// Admin tarafından direkt dosya iptal etme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_cancel_file'])) {
    $cancelFileId = sanitize($_POST['file_id']);
    $cancelFileType = sanitize($_POST['file_type']);
    $adminNotes = sanitize($_POST['admin_notes']);
    
    if (!isValidUUID($cancelFileId)) {
        $_SESSION['error'] = 'Geçersiz dosya ID formatı.';
    } else {
        // FileCancellationManager'ı yükle
        require_once '../includes/FileCancellationManager.php';
        $cancellationManager = new FileCancellationManager($pdo);
        
        $result = $cancellationManager->adminDirectCancellation($cancelFileId, $cancelFileType, $_SESSION['user_id'], $adminNotes);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
    exit;
}

// Bu dosyayı mevcut file-detail.php dosyasına dahil etmek için kullan
// Include this file in your existing file-detail.php for admin cancel functionality

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Cancel Addon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Admin İptal Modal (file-detail.php'ye eklenecek) -->
    <div class="modal fade" id="adminCancelModal" tabindex="-1" aria-labelledby="adminCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-danger text-white border-0">
                    <h5 class="modal-title d-flex align-items-center" id="adminCancelModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Dosya İptal Onayı
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form method="POST" id="adminCancelForm">
                    <div class="modal-body py-4">
                        <input type="hidden" name="admin_cancel_file" value="1">
                        <input type="hidden" name="file_id" id="cancelFileId">
                        <input type="hidden" name="file_type" id="cancelFileType">
                        
                        <div class="mb-4">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 50%;">
                                <i class="bi bi-times text-white fa-2x"></i>
                            </div>
                            <h6 class="mb-2 text-dark text-center">Bu dosyayı iptal etmek istediğinizden emin misiniz?</h6>
                            <p class="text-muted mb-3 text-center">
                                <strong>Dosya:</strong> <span id="cancelFileName"></span>
                            </p>
                            <div class="alert alert-warning d-flex align-items-center mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>Bu işlem dosyayı gizleyecek ve eğer ücretli ise kullanıcıya kredi iadesi yapacaktır.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adminNotes" class="form-label">
                                <i class="bi bi-sticky-note me-1"></i>
                                İptal Sebebi (Opsiyonel)
                            </label>
                            <textarea class="form-control" id="adminNotes" name="admin_notes" rows="3" 
                                      placeholder="İptal sebebinizi yazabilirsiniz..."></textarea>
                            <small class="text-muted">Bu not kullanıcıya gönderilecek bildirimde yer alacaktır.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-3">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="bi bi-times me-1"></i>
                            İptal
                        </button>
                        <button type="submit" class="btn btn-danger px-4" style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <i class="bi bi-check me-1"></i>
                            Evet, İptal Et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    /* Admin Cancel Modal Styling */
    .bg-gradient-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    }

    #adminCancelModal .modal-content {
        border-radius: 1rem;
        overflow: hidden;
    }

    #adminCancelModal .modal-header {
        padding: 1.5rem 2rem 1rem;
        border-bottom: none;
    }

    #adminCancelModal .modal-body {
        padding: 1rem 2rem 1.5rem;
    }

    #adminCancelModal .modal-footer {
        padding: 0rem 3rem 3rem 0rem;
        background: #f8f9fa;
        margin: 0 -2rem -2rem;
        padding-top: 1.5rem;
    }

    #adminCancelModal .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        border-color: #c82333;
        transform: translateY(-2px);
    }

    #adminCancelModal .btn-secondary:hover {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        border-color: #6c757d;
        transform: translateY(-2px);
    }

    /* Cancel modal animation */
    #adminCancelModal.fade .modal-dialog {
        transition: transform 0.4s ease-out;
        transform: scale(0.8) translateY(-50px);
    }

    #adminCancelModal.show .modal-dialog {
        transform: scale(1) translateY(0);
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Admin Cancel Modal Functions
    function showCancelModal(fileId, fileType, fileName) {
        document.getElementById('cancelFileId').value = fileId;
        document.getElementById('cancelFileType').value = fileType;
        document.getElementById('cancelFileName').textContent = fileName;
        document.getElementById('adminNotes').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
        modal.show();
    }
    </script>
    
    <!-- 
    Admin İptal Butonu HTML Kodu (file-detail.php'deki button group'a eklenecek):
    
    <?php if (!isset($upload['is_cancelled']) || !$upload['is_cancelled']): ?>
        <button type="button" class="btn btn-danger" 
                onclick="showCancelModal('<?php echo $uploadId; ?>', 'upload', '<?php echo htmlspecialchars($upload['original_name'] ?? 'Bilinmeyen dosya'); ?>')">
            <i class="bi bi-times me-1"></i>Dosyayı İptal Et
        </button>
    <?php else: ?>
        <span class="btn btn-secondary disabled">
            <i class="bi bi-ban me-1"></i>İptal Edilmiş
        </span>
    <?php endif; ?>
    -->
</body>
</html>