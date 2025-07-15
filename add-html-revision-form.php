<?php
/**
 * Admin File Detail Dosyasına HTML Revizyon Formu Ekleme
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin HTML Form Ekleme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";

echo "<h1>🔧 Admin HTML Revizyon Formu Ekleme</h1>";

try {
    $filePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/admin/file-detail.php';
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        throw new Exception("Dosya okunamadı: $filePath");
    }
    
    // HTML revizyon formu zaten var mı kontrol et
    if (strpos($content, 'İşlemdeki Revizyon Talepleri') !== false) {
        echo "<p class='success'>✅ HTML revizyon formu zaten mevcut</p>";
    } else {
        echo "<p class='error'>❌ HTML revizyon formu eksik - eklenecek</p>";
        
        // HTML form kodunu oluştur
        $htmlForm = '
<!-- ==================== REVİZYON DOSYASI YÜKLEMESİ ==================== -->
<?php
// Bu dosya için işlemdeki revizyon taleplerini kontrol et
try {
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name, u.username, u.first_name, u.last_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id  
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.upload_id = ? AND r.status = \'in_progress\'
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$uploadId]);
    $activeRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $activeRevisions = [];
    error_log(\'Revizyon sorgusu hatası: \' . $e->getMessage());
}
?>

<?php if (!empty($activeRevisions)): ?>
    <div class="card admin-card mb-4">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
                <i class="fas fa-cogs me-2"></i>
                İşlemdeki Revizyon Talepleri (<?php echo count($activeRevisions); ?> adet)
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bu dosya için işlemdeki revizyon talepleri bulundu.</strong><br>
                Aşağıdaki formları kullanarak revizyon dosyalarını yükleyebilirsiniz.
            </div>
            
            <?php foreach ($activeRevisions as $revIndex => $revision): ?>
                <div class="revision-upload-section mb-4 p-3 border rounded bg-light">
                    <div class="revision-info mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($revision[\'first_name\'] . \' \' . $revision[\'last_name\']); ?> 
                            (@<?php echo htmlspecialchars($revision[\'username\']); ?>)
                        </h6>
                        <div class="revision-details">
                            <p><strong>Talep Tarihi:</strong> <?php echo date(\'d.m.Y H:i\', strtotime($revision[\'requested_at\'])); ?></p>
                            <p><strong>Revizyon ID:</strong> <code><?php echo substr($revision[\'id\'], 0, 8); ?>...</code></p>
                            <div class="bg-white p-3 rounded border">
                                <strong>Kullanıcının Revizyon Talebi:</strong><br>
                                <?php echo nl2br(htmlspecialchars($revision[\'request_notes\'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Revizyon Dosyası Yükleme Formu -->
                    <form method="POST" enctype="multipart/form-data" class="revision-upload-form">
                        <input type="hidden" name="revision_id" value="<?php echo $revision[\'id\']; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="revision_file_<?php echo $revision[\'id\']; ?>" class="form-label">
                                    <i class="fas fa-file-upload me-1"></i>
                                    Revizyon Dosyası <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" 
                                       id="revision_file_<?php echo $revision[\'id\']; ?>" 
                                       name="revision_file" required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Revize edilmiş dosyayı seçin (Max: <?php echo ini_get(\'upload_max_filesize\'); ?>)
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="revision_notes_<?php echo $revision[\'id\']; ?>" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    Admin Notları
                                </label>
                                <textarea class="form-control" 
                                          id="revision_notes_<?php echo $revision[\'id\']; ?>" 
                                          name="revision_notes" rows="3"
                                          placeholder="Revizyon hakkında notlarınızı buraya yazın..."></textarea>
                                <div class="form-text">Yapılan değişiklikler ve açıklamalar</div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" name="upload_revision" class="btn btn-success btn-lg">
                                        <i class="fas fa-upload me-2"></i>Revizyon Dosyasını Yükle
                                    </button>
                                    
                                    <div class="revision-actions">
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="showRejectModal(\'<?php echo $revision[\'id\']; ?>\')">
                                            <i class="fas fa-times me-1"></i>Revizyon Talebini Reddet
                                        </button>
                                    </div>
                                </div>
                                
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    <strong>Önemli:</strong> Dosya yüklendikten sonra revizyon talebi otomatik olarak "Tamamlandı" durumuna geçecek ve kullanıcı dosyayı indirebilecek.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($revIndex < count($activeRevisions) - 1): ?>
                    <hr class="my-4">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- İşlemdeki revizyon talebi yok -->
    <?php
    // Bu dosya için herhangi bir revizyon talebi var mı kontrol et
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ?");
        $stmt->execute([$uploadId]);
        $totalRevisions = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ? AND status = \'pending\'");
        $stmt->execute([$uploadId]);
        $pendingRevisions = $stmt->fetchColumn();
        
    } catch(PDOException $e) {
        $totalRevisions = 0;
        $pendingRevisions = 0;
    }
    ?>
    
    <?php if ($pendingRevisions > 0): ?>
        <div class="card admin-card mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Bekleyen Revizyon Talepleri (<?php echo $pendingRevisions; ?> adet)
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Bu dosya için bekleyen revizyon talepleri var.</strong><br>
                    Revizyon taleplerini işleme almak için <a href="revisions.php" class="alert-link">Revizyon Yönetimi</a> sayfasını ziyaret edin.
                </div>
                
                <div class="d-flex gap-2">
                    <a href="revisions.php?search=<?php echo urlencode($upload[\'original_name\'] ?? \'\'); ?>" 
                       class="btn btn-warning">
                        <i class="fas fa-list me-1"></i>Revizyon Taleplerini Görüntüle
                    </a>
                </div>
            </div>
        </div>
    <?php elseif ($totalRevisions > 0): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Revizyon Geçmişi
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Bu dosya için toplam <?php echo $totalRevisions; ?> revizyon talebi bulunuyor.
                </p>
                
                <a href="revisions.php?search=<?php echo urlencode($upload[\'original_name\'] ?? \'\'); ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-history me-1"></i>Revizyon Geçmişini Görüntüle
                </a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<!-- ==================== REVİZYON DOSYASI YÜKLEMESİ SON ==================== -->

';
        
        // <!-- Revize Reddetme Modal --> dan önce ekle
        $insertPattern = '<!-- Revize Reddetme Modal -->';
        $insertPosition = strpos($content, $insertPattern);
        
        if ($insertPosition !== false) {
            $newContent = substr($content, 0, $insertPosition) . $htmlForm . "\n\n" . substr($content, $insertPosition);
            
            if (file_put_contents($filePath, $newContent)) {
                echo "<p class='success'>✅ HTML revizyon formu başarıyla eklendi!</p>";
            } else {
                echo "<p class='error'>❌ Dosya yazılamadı</p>";
            }
        } else {
            echo "<p class='error'>❌ Ekleme yeri bulunamadı</p>";
        }
    }
    
    echo "<h2>🎯 Test Adımları:</h2>";
    echo "<ol>";
    echo "<li><a href='fix-final-errors.php' target='_blank'>Final hataları düzelt</a></li>";
    echo "<li>Revizyonu 'in_progress' durumuna getir</li>";
    echo "<li>Admin dosya detayında revizyon formu görünüyor mu kontrol et</li>";
    echo "<li>Test dosyası yükle</li>";
    echo "<li><a href='test-revision-system.php' target='_blank'>Sistem testini çalıştır</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
