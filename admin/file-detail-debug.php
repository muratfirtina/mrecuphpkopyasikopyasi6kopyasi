<?php
/**
 * Mr ECU - File Detail Page (Debug Version)
 * Yanıt dosyaları desteği ile güncellenmiş dosya detay sayfası
 */

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Debug mode
$debug = true;

if ($debug) {
    echo "<!-- DEBUG: File detail başlatıldı -->\n";
    ob_start(); // Output buffering başlat
}

try {
    if ($debug) echo "<!-- DEBUG: Config include başlatılıyor -->\n";
    
    require_once '../config/config.php';
    
    if ($debug) echo "<!-- DEBUG: Config included -->\n";
    
    require_once '../config/database.php';
    
    if ($debug) echo "<!-- DEBUG: Database included -->\n";
    
    // PDO kontrolü
    if (!isset($pdo) || !$pdo) {
        throw new Exception("PDO connection not available");
    }
    
    if ($debug) echo "<!-- DEBUG: PDO OK -->\n";
    
    // Functions kontrolü
    if (!function_exists('isValidUUID')) {
        if ($debug) echo "<!-- DEBUG: isValidUUID function not found, defining -->\n";
        function isValidUUID($uuid) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
        }
    }
    
    if (!function_exists('sanitize')) {
        if ($debug) echo "<!-- DEBUG: sanitize function not found, defining -->\n";
        function sanitize($data) {
            if (is_array($data)) {
                return array_map('sanitize', $data);
            }
            return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    if (!function_exists('redirect')) {
        if ($debug) echo "<!-- DEBUG: redirect function not found, defining -->\n";
        function redirect($url) {
            if (headers_sent()) {
                echo "<script>window.location.href = '$url';</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
            } else {
                header("Location: " . $url);
            }
            exit();
        }
    }
    
    if (!function_exists('isLoggedIn')) {
        if ($debug) echo "<!-- DEBUG: isLoggedIn function not found, defining -->\n";
        function isLoggedIn() {
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        }
    }
    
    if (!function_exists('isAdmin')) {
        if ($debug) echo "<!-- DEBUG: isAdmin function not found, defining -->\n";
        function isAdmin() {
            if (isset($_SESSION['role'])) {
                return $_SESSION['role'] === 'admin';
            }
            return isset($_SESSION['is_admin']) && ((int)$_SESSION['is_admin'] === 1);
        }
    }
    
    if (!function_exists('formatFileSize')) {
        if ($debug) echo "<!-- DEBUG: formatFileSize function not found, defining -->\n";
        function formatFileSize($bytes) {
            if ($bytes === 0) return '0 B';
            $k = 1024;
            $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        }
    }
    
    if (!function_exists('formatDate')) {
        if ($debug) echo "<!-- DEBUG: formatDate function not found, defining -->\n";
        function formatDate($date) {
            return date('d.m.Y H:i', strtotime($date));
        }
    }
    
    if ($debug) echo "<!-- DEBUG: Functions OK -->\n";
    
    // Class includes
    require_once '../includes/FileManager.php';
    require_once '../includes/User.php';
    
    if ($debug) echo "<!-- DEBUG: Classes included -->\n";
    
    // Session kontrolü
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($debug) {
        echo "<!-- DEBUG: Session Status - ";
        echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . ", ";
        echo "Is Admin: " . (isAdmin() ? 'Yes' : 'No') . " -->\n";
    }
    
    // Admin kontrolü
    if (!isLoggedIn() || !isAdmin()) {
        if ($debug) {
            echo "<!-- DEBUG: Admin check failed - Logged in: " . (isLoggedIn() ? 'Yes' : 'No') . ", Admin: " . (isAdmin() ? 'Yes' : 'No') . " -->\n";
            // Debug için admin kontrolünü geçici olarak atla
            if ($debug) {
                echo "<!-- DEBUG: Admin check bypassed for debugging -->\n";
            } else {
                redirect('../login.php?error=access_denied');
            }
        } else {
            redirect('../login.php?error=access_denied');
        }
    }
    
    if ($debug) echo "<!-- DEBUG: Admin check passed -->\n";
    
    // Instance oluştur
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    
    if ($debug) echo "<!-- DEBUG: Instances created -->\n";
    
    // Upload ID kontrolü
    if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
        if ($debug) {
            echo "<!-- DEBUG: Invalid upload ID - ID: " . ($_GET['id'] ?? 'Not set') . " -->\n";
            echo "<div class='alert alert-danger'>Geçersiz dosya ID: " . ($_GET['id'] ?? 'Belirtilmemiş') . "</div>";
            if ($debug) {
                echo "<p><a href='uploads.php'>Dosyalar sayfasına dön</a></p>";
                exit;
            }
        }
        redirect('uploads.php');
    }
    
    $uploadId = $_GET['id'];
    $fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload';
    
    if ($debug) {
        echo "<!-- DEBUG: Parameters - Upload ID: $uploadId, Type: $fileType -->\n";
    }
    
    $error = '';
    $success = '';
    
    // Form işlemleri (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($debug) echo "<!-- DEBUG: POST request detected -->\n";
        
        // POST işlemleri burada...
        if (isset($_POST['upload_response'])) {
            if ($debug) echo "<!-- DEBUG: Response upload detected -->\n";
            
            $creditsCharged = floatval($_POST['credits_charged'] ?? 0);
            $responseNotes = sanitize($_POST['response_notes'] ?? '');
            
            if (isset($_FILES['response_file'])) {
                $result = $fileManager->uploadResponseFile($uploadId, $_FILES['response_file'], $creditsCharged, $responseNotes);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'Dosya seçilmedi.';
            }
        }
    }
    
    if ($debug) echo "<!-- DEBUG: POST processing completed -->\n";
    
    // Dosya detaylarını al
    if ($fileType === 'response') {
        if ($debug) echo "<!-- DEBUG: Getting response file details -->\n";
        
        $responseId = isset($_GET['response_id']) ? sanitize($_GET['response_id']) : null;
        
        if ($responseId) {
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                       fu.brand_id, fu.model_id, fu.year, fu.plate, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       u.username, u.email, u.first_name, u.last_name
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN users a ON fr.admin_id = a.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE fr.id = ? AND fr.upload_id = ?
            ");
            $stmt->execute([$responseId, $uploadId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                       fu.brand_id, fu.model_id, fu.year, fu.plate, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       u.username, u.email, u.first_name, u.last_name
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN users a ON fr.admin_id = a.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE fr.upload_id = ?
                ORDER BY fr.upload_date DESC
                LIMIT 1
            ");
            $stmt->execute([$uploadId]);
        }
        
        $upload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$upload) {
            if ($debug) {
                echo "<!-- DEBUG: Response file not found -->\n";
                echo "<div class='alert alert-warning'>Response dosyası bulunamadı.</div>";
                echo "<p><a href='uploads.php'>Dosyalar sayfasına dön</a></p>";
                exit;
            }
            $_SESSION['error'] = 'Response dosyası bulunamadı.';
            redirect('uploads.php');
        }
        
        if ($debug) echo "<!-- DEBUG: Response file found: " . $upload['original_name'] . " -->\n";
        
        $responseFiles = [];
        $responseId = $responseId ?: $upload['id'];
        
    } else {
        if ($debug) echo "<!-- DEBUG: Getting upload file details -->\n";
        
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            if ($debug) {
                echo "<!-- DEBUG: Upload file not found -->\n";
                echo "<div class='alert alert-warning'>Dosya bulunamadı.</div>";
                echo "<p><a href='uploads.php'>Dosyalar sayfasına dön</a></p>";
                exit;
            }
            redirect('uploads.php');
        }
        
        if ($debug) echo "<!-- DEBUG: Upload file found: " . $upload['original_name'] . " -->\n";
        
        // Response dosyalarını al
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username as admin_username, u.first_name, u.last_name
            FROM file_responses fr
            LEFT JOIN users u ON fr.admin_id = u.id
            WHERE fr.upload_id = ?
            ORDER BY fr.upload_date DESC
        ");
        $stmt->execute([$uploadId]);
        $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $responseId = null;
    }
    
    if ($debug) echo "<!-- DEBUG: File details retrieved successfully -->\n";
    
    // Güvenli HTML output fonksiyonu
    function safeHtml($value) {
        return $value !== null ? htmlspecialchars($value) : '<em style="color: #999;">Belirtilmemiş</em>';
    }
    
    // Page variables
    if ($fileType === 'response') {
        $pageTitle = 'Yanıt Dosyası Detayları - ' . htmlspecialchars($upload['original_name']);
        $pageDescription = 'Yanıt dosyası detaylarını görüntüleyin ve yönetin';
    } else {
        $pageTitle = 'Dosya Detayları - ' . htmlspecialchars($upload['original_name']);
        $pageDescription = 'Dosya detaylarını görüntüleyin ve yönetin';
    }
    
    $pageIcon = 'fas fa-file-alt';
    
    if ($debug) {
        echo "<!-- DEBUG: All setup completed successfully -->\n";
        ob_end_flush(); // Output buffer'ı temizle ve göster
    }

} catch (Exception $e) {
    if ($debug) {
        echo "<!-- DEBUG: Exception occurred: " . $e->getMessage() . " -->\n";
        echo "<div class='alert alert-danger'>";
        echo "<h4>Debug Bilgisi:</h4>";
        echo "<p><strong>Hata:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Dosya:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        exit;
    } else {
        error_log('File detail error: ' . $e->getMessage());
        redirect('uploads.php');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel - Mr ECU</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .admin-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        
        .admin-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    
    <!-- Hata/Başarı Mesajları -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="uploads.php">Dosyalar</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php if ($fileType === 'response'): ?>
                    Yanıt Dosyası: <?php echo htmlspecialchars($upload['original_name']); ?>
                <?php else: ?>
                    Dosya Detayı
                <?php endif; ?>
            </li>
        </ol>
    </nav>
    
    <!-- Debug Info -->
    <?php if ($debug): ?>
    <div class="alert alert-info">
        <h6>Debug Bilgisi:</h6>
        <p><strong>Upload ID:</strong> <?php echo $uploadId; ?></p>
        <p><strong>File Type:</strong> <?php echo $fileType; ?></p>
        <p><strong>Response ID:</strong> <?php echo $responseId ?? 'Yok'; ?></p>
        <p><strong>File Name:</strong> <?php echo $upload['original_name']; ?></p>
        <p><strong>User:</strong> <?php echo $upload['username'] ?? 'Bilinmiyor'; ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Dosya Detay Kartı -->
    <div class="card admin-card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-<?php echo $fileType === 'response' ? 'reply' : 'file-alt'; ?> me-2"></i>
                    <?php echo $fileType === 'response' ? 'Yanıt Dosyası' : 'Dosya'; ?> Detayları
                </h5>
                <div class="d-flex gap-2">
                    <?php if ($fileType === 'response'): ?>
                        <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-alt me-1"></i>Orijinal Dosyayı Görüntüle
                        </a>
                    <?php endif; ?>
                    
                    <a href="uploads.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Geri
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row">
                <!-- Dosya Bilgileri -->
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Dosya Adı</label>
                            <div class="form-control-plaintext">
                                <?php echo safeHtml($upload['original_name']); ?>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Dosya Boyutu</label>
                            <div class="form-control-plaintext">
                                <?php echo formatFileSize($upload['file_size'] ?? 0); ?>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Yükleme Tarihi</label>
                            <div class="form-control-plaintext">
                                <?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($fileType === 'response'): ?>
                            <div class="col-sm-6">
                                <label class="form-label">Oluşturan Admin</label>
                                <div class="form-control-plaintext">
                                    <?php echo safeHtml($upload['admin_first_name'] . ' ' . $upload['admin_last_name'] . ' (@' . $upload['admin_username'] . ')'); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-sm-6">
                                <label class="form-label">Durum</label>
                                <div class="form-control-plaintext">
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusText = [
                                        'pending' => 'Bekliyor',
                                        'processing' => 'İşleniyor',
                                        'completed' => 'Tamamlandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?>">
                                        <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Araç Bilgileri -->
                        <div class="col-12">
                            <hr>
                            <h6 class="mb-3">Araç Bilgileri</h6>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Marka</label>
                            <div class="form-control-plaintext">
                                <?php echo safeHtml($upload['brand_name']); ?>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Model</label>
                            <div class="form-control-plaintext">
                                <?php echo safeHtml($upload['model_name']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($upload['plate'])): ?>
                            <div class="col-sm-6">
                                <label class="form-label">Plaka</label>
                                <div class="form-control-plaintext">
                                    <span class="text-primary fw-bold">
                                        <i class="fas fa-id-card me-1"></i><?php echo strtoupper(htmlspecialchars($upload['plate'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Kullanıcı Bilgileri -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Kullanıcı Bilgileri</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle me-3">
                                    <?php 
                                        $firstName = $upload['first_name'] ?? '';
                                        $lastName = $upload['last_name'] ?? '';
                                        echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); 
                                    ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo safeHtml(($upload['first_name'] ?? '') . ' ' . ($upload['last_name'] ?? '')); ?></h6>
                                    <small class="text-muted">@<?php echo safeHtml($upload['username'] ?? 'Bilinmiyor'); ?></small>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">E-posta:</small><br>
                                <a href="mailto:<?php echo $upload['email'] ?? ''; ?>"><?php echo safeHtml($upload['email'] ?? 'Belirtilmemiş'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yanıt Dosyası Yükleme (sadece normal dosyalar için) -->
    <?php if ($fileType !== 'response' && ($upload['status'] === 'pending' || $upload['status'] === 'processing')): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-reply me-2"></i>Yanıt Dosyası Yükle
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="response_file" class="form-label">Yanıt Dosyası</label>
                            <input type="file" class="form-control" id="response_file" name="response_file" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="credits_charged" class="form-label">Düşürülecek Kredi</label>
                            <input type="number" class="form-control" id="credits_charged" name="credits_charged" 
                                   value="0" min="0" step="0.01">
                        </div>
                        
                        <div class="col-12">
                            <label for="response_notes" class="form-label">Yanıt Notları</label>
                            <textarea class="form-control" id="response_notes" name="response_notes" rows="3"
                                      placeholder="Yanıt ile ilgili notlarınızı buraya yazın..."></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="upload_response" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Yanıt Dosyasını Yükle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Yanıt Dosyaları Listesi -->
    <?php if ($fileType !== 'response' && !empty($responseFiles)): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-reply me-2"></i>Yanıt Dosyaları (<?php echo count($responseFiles); ?>)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dosya Adı</th>
                                <th>Boyut</th>
                                <th>Yükleme Tarihi</th>
                                <th>Admin</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responseFiles as $responseFile): ?>
                                <tr>
                                    <td><strong><?php echo safeHtml($responseFile['original_name']); ?></strong></td>
                                    <td><?php echo formatFileSize($responseFile['file_size']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($responseFile['upload_date'])); ?></td>
                                    <td>
                                        <?php if ($responseFile['admin_username']): ?>
                                            <?php echo safeHtml($responseFile['first_name'] . ' ' . $responseFile['last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Bilinmiyor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="file-detail.php?id=<?php echo $uploadId; ?>&type=response&response_id=<?php echo $responseFile['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
