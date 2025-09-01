<?php
/**
 * Admin ve User file-detail.php dosyalarına ek dosya bölümlerini ekleyen güncelleme scripti
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$basePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/';

// Admin file-detail.php dosyasını güncelle
echo "=== Admin file-detail.php güncelleniyor ===\n";
$adminFile = $basePath . 'admin/file-detail.php';
$adminContent = file_get_contents($adminFile);

// Admin dosyasında zaten ek dosya bölümü var mı kontrol et
if (strpos($adminContent, 'uploadAdditionalFileForm') !== false) {
    echo "✓ Admin file-detail.php zaten ek dosya bölümüne sahip.\n";
} else {
    // Backup al
    $backupFile = $adminFile . '.backup_' . date('YmdHis');
    file_put_contents($backupFile, $adminContent);
    echo "✓ Backup alındı: " . basename($backupFile) . "\n";
    
    // Ek dosya bölümünü ekle (Chat Penceresi'nden önce)
    $additionalSection = '
<!-- Ek Dosyalar Bölümü -->
<?php
// Ek dosyaları getir
$additionalFiles = $fileManager->getAdditionalFiles($uploadId, $_SESSION[\'user_id\'], \'admin\');
?>
<div class="card admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-paperclip me-2"></i>Ek Dosyalar
                <?php if (!empty($additionalFiles)): ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($additionalFiles); ?></span>
                <?php endif; ?>
            </h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadAdditionalFileModal">
                <i class="bi bi-plus me-1"></i>Ek Dosya Gönder
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($additionalFiles)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Gönderen</th>
                            <th>Alıcı</th>
                            <th>Tarih</th>
                            <th>Notlar</th>
                            <th>Ücret</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($additionalFiles as $file): ?>
                            <tr class="<?php echo $file[\'is_read\'] ? \'\' : \'table-warning\'; ?>">
                                <td>
                                    <i class="bi bi-file me-1"></i>
                                    <?php echo htmlspecialchars($file[\'original_name\']); ?>
                                    <?php if (!$file[\'is_read\'] && $file[\'receiver_id\'] === $_SESSION[\'user_id\']): ?>
                                        <span class="badge bg-warning ms-2">Yeni</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file[\'sender_type\'] === \'admin\'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                        <?php echo htmlspecialchars($file[\'sender_first_name\'] . \' \' . $file[\'sender_last_name\']); ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Kullanıcı</span>
                                        <?php echo htmlspecialchars($file[\'sender_first_name\'] . \' \' . $file[\'sender_last_name\']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file[\'receiver_type\'] === \'admin\'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                        <?php echo htmlspecialchars($file[\'receiver_first_name\'] . \' \' . $file[\'receiver_last_name\']); ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Kullanıcı</span>
                                        <?php echo htmlspecialchars($file[\'receiver_first_name\'] . \' \' . $file[\'receiver_last_name\']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date(\'d.m.Y H:i\', strtotime($file[\'upload_date\'])); ?></td>
                                <td>
                                    <?php if (!empty($file[\'notes\'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($file[\'notes\'], 0, 50)); ?>...</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file[\'credits\'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $file[\'credits\']; ?> kredi</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Ücretsiz</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../download-additional.php?id=<?php echo $file[\'id\']; ?>" class="btn btn-success btn-sm" title="İndir">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Henüz ek dosya bulunmuyor.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ek Dosya Yükleme Modal -->
<div class="modal fade" id="uploadAdditionalFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadAdditionalFileForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-upload me-2"></i>Kullanıcıya Ek Dosya Gönder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="related_file_id" value="<?php echo $uploadId; ?>">
                    <input type="hidden" name="related_file_type" value="upload">
                    <input type="hidden" name="receiver_id" value="<?php echo $upload[\'user_id\']; ?>">
                    <input type="hidden" name="receiver_type" value="user">
                    
                    <div class="mb-3">
                        <label for="additional_file" class="form-label">Dosya Seç <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="additional_file" name="additional_file" required>
                        <div class="form-text">Maksimum dosya boyutu: <?php echo ini_get(\'upload_max_filesize\'); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="additional_notes" class="form-label">Notlar</label>
                        <textarea class="form-control" id="additional_notes" name="notes" rows="3" placeholder="Dosya hakkında açıklama..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="additional_credits" class="form-label">Ücret (Kredi)</label>
                        <input type="number" class="form-control" id="additional_credits" name="credits" min="0" step="0.01" value="0">
                        <div class="form-text">Bu dosya için kullanıcıdan düşülecek kredi miktarı</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Dikkat:</strong> Ücret belirlerseniz, kullanıcının hesabından otomatik olarak kredi düşülecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>Dosyayı Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ek Dosya JavaScript -->
<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    const additionalFileForm = document.getElementById(\'uploadAdditionalFileForm\');
    if (additionalFileForm) {
        additionalFileForm.addEventListener(\'submit\', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append(\'action\', \'upload_additional_file\');
            
            // Loading göster
            const submitBtn = this.querySelector(\'button[type="submit"]\');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = \'<i class="bi bi-spinner fa-spin me-1"></i>Yükleniyor...\';
            
            fetch(\'../ajax/additional_files.php\', {
                method: \'POST\',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Modal\\\'ı kapat
                    const modal = bootstrap.Modal.getInstance(document.getElementById(\'uploadAdditionalFileModal\'));
                    modal.hide();
                    
                    // Sayfayı yenile
                    location.reload();
                } else {
                    alert(\'Hata: \' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error(\'Error:\', error);
                alert(\'Dosya yüklenirken bir hata oluştu.\');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>

';
    
    // Chat Penceresi'nden önce ekle
    if (strpos($adminContent, '<!-- Chat Penceresi -->') !== false) {
        $adminContent = str_replace('<!-- Chat Penceresi -->', $additionalSection . '<!-- Chat Penceresi -->', $adminContent);
        file_put_contents($adminFile, $adminContent);
        echo "✓ Admin file-detail.php güncellendi.\n";
    } else {
        echo "✗ Admin file-detail.php'de Chat Penceresi bulunamadı.\n";
        echo "  Manuel olarak eklemeniz gerekebilir.\n";
    }
}

// User file-detail.php dosyasını güncelle
echo "\n=== User file-detail.php güncelleniyor ===\n";
$userFile = $basePath . 'user/file-detail.php';
$userContent = file_get_contents($userFile);

// User dosyasında zaten ek dosya bölümü var mı kontrol et
if (strpos($userContent, 'uploadAdditionalFileForm') !== false) {
    echo "✓ User file-detail.php zaten ek dosya bölümüne sahip.\n";
} else {
    // Backup al
    $backupFile = $userFile . '.backup_' . date('YmdHis');
    file_put_contents($backupFile, $userContent);
    echo "✓ Backup alındı: " . basename($backupFile) . "\n";
    
    // Ek dosya bölümünü ekle (Chat Penceresi'nden önce)
    $additionalSection = '
<!-- Ek Dosyalar Bölümü -->
<?php
// Ek dosyaları getir  
$additionalFiles = $fileManager->getAdditionalFiles($fileId, $userId, \'user\');
?>
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-paperclip me-2"></i>Ek Dosyalar
                <?php if (!empty($additionalFiles)): ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($additionalFiles); ?></span>
                <?php endif; ?>
            </h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadAdditionalFileModal">
                <i class="bi bi-plus me-1"></i>Ek Dosya Gönder
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($additionalFiles)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Gönderen</th>
                            <th>Tarih</th>
                            <th>Notlar</th>
                            <th>Ücret</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($additionalFiles as $file): ?>
                            <tr class="<?php echo $file[\'is_read\'] ? \'\' : \'table-warning\'; ?>">
                                <td>
                                    <i class="bi bi-file me-1"></i>
                                    <?php echo htmlspecialchars($file[\'original_name\']); ?>
                                    <?php if (!$file[\'is_read\'] && $file[\'receiver_id\'] === $userId): ?>
                                        <span class="badge bg-warning ms-2">Yeni</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file[\'sender_type\'] === \'admin\'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Kullanıcı</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($file[\'sender_first_name\'] . \' \' . $file[\'sender_last_name\']); ?>
                                </td>
                                <td><?php echo date(\'d.m.Y H:i\', strtotime($file[\'upload_date\'])); ?></td>
                                <td>
                                    <?php if (!empty($file[\'notes\'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($file[\'notes\'], 0, 50)); ?>...</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file[\'credits\'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $file[\'credits\']; ?> kredi</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Ücretsiz</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../download-additional.php?id=<?php echo $file[\'id\']; ?>" class="btn btn-success btn-sm" title="İndir">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Henüz ek dosya bulunmuyor. Yukarıdaki butonu kullanarak admine dosya gönderebilirsiniz.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ek Dosya Yükleme Modal -->
<div class="modal fade" id="uploadAdditionalFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadAdditionalFileForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-upload me-2"></i>Admine Ek Dosya Gönder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="related_file_id" value="<?php echo $fileId; ?>">
                    <input type="hidden" name="related_file_type" value="<?php echo $fileType; ?>">
                    
                    <?php
                    // Admin ID\\\'yi bul
                    $adminId = null;
                    if ($fileType === \'response\' && isset($fileDetail[\'admin_id\'])) {
                        $adminId = $fileDetail[\'admin_id\'];
                    } else {
                        // Son yanıt dosyasından admin ID\\\'yi al
                        try {
                            $stmt = $pdo->prepare("
                                SELECT admin_id FROM file_responses 
                                WHERE upload_id = ? 
                                ORDER BY upload_date DESC 
                                LIMIT 1
                            ");
                            $stmt->execute([$fileId]);
                            $result = $stmt->fetch();
                            if ($result) {
                                $adminId = $result[\'admin_id\'];
                            }
                        } catch(Exception $e) {
                            error_log(\'Admin ID query error: \' . $e->getMessage());
                        }
                    }
                    
                    // Eğer admin ID bulunamazsa, varsayılan admin\\\'i al
                    if (!$adminId) {
                        try {
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = \'admin\' LIMIT 1");
                            $stmt->execute();
                            $result = $stmt->fetch();
                            if ($result) {
                                $adminId = $result[\'id\'];
                            }
                        } catch(Exception $e) {
                            error_log(\'Default admin query error: \' . $e->getMessage());
                        }
                    }
                    ?>
                    
                    <input type="hidden" name="receiver_id" value="<?php echo $adminId; ?>">
                    <input type="hidden" name="receiver_type" value="admin">
                    
                    <div class="mb-3">
                        <label for="additional_file" class="form-label">Dosya Seç <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="additional_file" name="additional_file" required>
                        <div class="form-text">Maksimum dosya boyutu: <?php echo ini_get(\'upload_max_filesize\'); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="additional_notes" class="form-label">Notlar</label>
                        <textarea class="form-control" id="additional_notes" name="notes" rows="3" placeholder="Dosya hakkında açıklama..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Dosyanız admin tarafından incelenecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>Dosyayı Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ek Dosya JavaScript -->
<script>
document.addEventListener(\'DOMContentLoaded\', function() {
    const additionalFileForm = document.getElementById(\'uploadAdditionalFileForm\');
    if (additionalFileForm) {
        additionalFileForm.addEventListener(\'submit\', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append(\'action\', \'upload_additional_file\');
            
            // Loading göster
            const submitBtn = this.querySelector(\'button[type="submit"]\');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = \'<i class="bi bi-spinner fa-spin me-1"></i>Yükleniyor...\';
            
            fetch(\'../ajax/additional_files.php\', {
                method: \'POST\',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Modal\\\'ı kapat
                    const modal = bootstrap.Modal.getInstance(document.getElementById(\'uploadAdditionalFileModal\'));
                    modal.hide();
                    
                    // Sayfayı yenile
                    location.reload();
                } else {
                    alert(\'Hata: \' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error(\'Error:\', error);
                alert(\'Dosya yüklenirken bir hata oluştu.\');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>

';
    
    // Chat Penceresi'nden önce ekle
    if (strpos($userContent, '<!-- Chat Penceresi -->') !== false) {
        $userContent = str_replace('<!-- Chat Penceresi -->', $additionalSection . '<!-- Chat Penceresi -->', $userContent);
        file_put_contents($userFile, $userContent);
        echo "✓ User file-detail.php güncellendi.\n";
    } else {
        echo "✗ User file-detail.php'de Chat Penceresi bulunamadı.\n";
        echo "  Manuel olarak eklemeniz gerekebilir.\n";
    }
}

echo "\n=== GÜNCELLEME TAMAMLANDI ===\n";
echo "Lütfen tarayıcınızı yenileyip tekrar test edin.\n";
?>