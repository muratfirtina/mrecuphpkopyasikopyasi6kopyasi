<?php
/**
 * Complete Additional Files System Installer
 * Ek dosya sistemini tamamen kuran script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Additional Files System Complete Installer</h1>";
echo "<hr>";

// 1. Veritabanı tablosunu kontrol et
echo "<h2>1. Veritabanı Kontrolü</h2>";
try {
    // additional_files tablosu var mı?
    $stmt = $pdo->query("SHOW TABLES LIKE 'additional_files'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ additional_files tablosu mevcut</p>";
    } else {
        echo "<p style='color:red'>✗ additional_files tablosu yok, oluşturuluyor...</p>";
        // Tablo oluştur
        $sql = "CREATE TABLE `additional_files` (
            `id` varchar(36) NOT NULL DEFAULT (UUID()),
            `related_file_id` varchar(36) NOT NULL,
            `related_file_type` enum('upload','response','revision') DEFAULT 'upload',
            `sender_id` varchar(36) NOT NULL,
            `sender_type` enum('user','admin') NOT NULL,
            `receiver_id` varchar(36) NOT NULL,
            `receiver_type` enum('user','admin') NOT NULL,
            `original_name` varchar(255) NOT NULL,
            `file_name` varchar(255) NOT NULL,
            `file_path` varchar(500) NOT NULL,
            `file_size` bigint(20) NOT NULL,
            `file_type` varchar(100) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `credits` decimal(10,2) DEFAULT 0.00,
            `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `is_read` tinyint(1) DEFAULT 0,
            `read_date` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_related_file` (`related_file_id`),
            KEY `idx_sender` (`sender_id`,`sender_type`),
            KEY `idx_receiver` (`receiver_id`,`receiver_type`),
            KEY `idx_upload_date` (`upload_date`),
            KEY `idx_is_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✓ Tablo oluşturuldu</p>";
    }
} catch(Exception $e) {
    echo "<p style='color:red'>✗ Veritabanı hatası: " . $e->getMessage() . "</p>";
}

// 2. User.php'de additional_file_charge tipini kontrol et
echo "<h2>2. User.php Güncelleme</h2>";
$userFile = __DIR__ . '/includes/User.php';
$userContent = file_get_contents($userFile);

if (strpos($userContent, "'additional_file_charge'") === false) {
    echo "<p style='color:orange'>! User.php'de additional_file_charge tipi eksik, ekleniyor...</p>";
    
    // addCreditDirectSimple fonksiyonunu bul ve güncelle
    $searchPattern = "} else if (\$type === 'usage_remove') {";
    $replaceWith = "} else if (\$type === 'additional_file_charge') {
                // Ek dosya ücreti için kredi düşürme (ters kredi sistemi)
                \$stmt = \$this->pdo->prepare(\"UPDATE users SET credit_used = credit_used + ? WHERE id = ?\");
                \$result = \$stmt->execute([\$amount, \$userId]);
                
            } else if (\$type === 'usage_remove') {";
    
    $userContent = str_replace($searchPattern, $replaceWith, $userContent);
    file_put_contents($userFile, $userContent);
    echo "<p style='color:green'>✓ User.php güncellendi</p>";
} else {
    echo "<p style='color:green'>✓ User.php zaten güncel</p>";
}

// 3. Upload dizinini kontrol et
echo "<h2>3. Upload Dizini</h2>";
$uploadDir = __DIR__ . '/uploads/additional_files/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "<p style='color:green'>✓ Upload dizini oluşturuldu</p>";
} else {
    echo "<p style='color:green'>✓ Upload dizini mevcut</p>";
}

// 4. Admin file-detail.php'yi güncelle
echo "<h2>4. Admin file-detail.php Güncelleme</h2>";
$adminFile = __DIR__ . '/admin/file-detail.php';
$adminContent = file_get_contents($adminFile);

if (strpos($adminContent, 'getAdditionalFiles') === false) {
    echo "<p style='color:orange'>! Admin file-detail.php'de ek dosya bölümü yok, ekleniyor...</p>";
    
    // Backup al
    file_put_contents($adminFile . '.backup_' . date('YmdHis'), $adminContent);
    
    // Ek dosya bölümü HTML'i
    $additionalSection = file_get_contents(__DIR__ . '/temp/admin_additional_section.txt');
    
    // Chat Penceresi'nden önce ekle
    $adminContent = str_replace('<!-- Chat Penceresi -->', $additionalSection . "\n<!-- Chat Penceresi -->", $adminContent);
    file_put_contents($adminFile, $adminContent);
    
    echo "<p style='color:green'>✓ Admin file-detail.php güncellendi</p>";
} else {
    echo "<p style='color:green'>✓ Admin file-detail.php zaten güncel</p>";
}

// 5. User file-detail.php'yi güncelle
echo "<h2>5. User file-detail.php Güncelleme</h2>";
$userDetailFile = __DIR__ . '/user/file-detail.php';
$userDetailContent = file_get_contents($userDetailFile);

if (strpos($userDetailContent, 'getAdditionalFiles') === false) {
    echo "<p style='color:orange'>! User file-detail.php'de ek dosya bölümü yok, ekleniyor...</p>";
    
    // Backup al
    file_put_contents($userDetailFile . '.backup_' . date('YmdHis'), $userDetailContent);
    
    // Ek dosya bölümü HTML'i
    $additionalSection = file_get_contents(__DIR__ . '/temp/user_additional_section.txt');
    
    // Chat Penceresi'nden önce ekle
    $userDetailContent = str_replace('<!-- Chat Penceresi -->', $additionalSection . "\n<!-- Chat Penceresi -->", $userDetailContent);
    file_put_contents($userDetailFile, $userDetailContent);
    
    echo "<p style='color:green'>✓ User file-detail.php güncellendi</p>";
} else {
    echo "<p style='color:green'>✓ User file-detail.php zaten güncel</p>";
}

echo "<hr>";
echo "<h2>✅ Kurulum Tamamlandı!</h2>";
echo "<p>Sistem kullanıma hazır. Test için:</p>";
echo "<ol>";
echo "<li><a href='admin/' target='_blank'>Admin paneline giriş yapın</a></li>";
echo "<li>Bir dosya detay sayfasını açın</li>";
echo "<li>'Ek Dosya Gönder' butonunu kullanın</li>";
echo "<li>User panelinde dosyanın görünüp görünmediğini kontrol edin</li>";
echo "</ol>";

// Template dosyaları oluştur
if (!file_exists(__DIR__ . '/temp/admin_additional_section.txt')) {
    file_put_contents(__DIR__ . '/temp/admin_additional_section.txt', '
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
                                    <i class="bi bi-folder2-open me-1"></i>
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
                    // Modal\'ı kapat
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
');
    echo "<p style='color:green'>✓ Admin template dosyası oluşturuldu</p>";
}

if (!file_exists(__DIR__ . '/temp/user_additional_section.txt')) {
    file_put_contents(__DIR__ . '/temp/user_additional_section.txt', '
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
                                    <i class="bi bi-folder2-open me-1"></i>
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
                    // Admin ID\'yi bul
                    $adminId = null;
                    if ($fileType === \'response\' && isset($fileDetail[\'admin_id\'])) {
                        $adminId = $fileDetail[\'admin_id\'];
                    } else {
                        // Son yanıt dosyasından admin ID\'yi al
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
                    
                    // Eğer admin ID bulunamazsa, varsayılan admin\'i al
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
            
            // Debug için
            console.log(\'Sending additional file upload...\');
            for (let [key, value] of formData.entries()) {
                console.log(key + \': \' + value);
            }
            
            // Loading göster
            const submitBtn = this.querySelector(\'button[type="submit"]\');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = \'<i class="bi bi-spinner fa-spin me-1"></i>Yükleniyor...\';
            
            fetch(\'../ajax/additional_files.php\', {
                method: \'POST\',
                body: formData
            })
            .then(response => {
                console.log(\'Response status:\', response.status);
                return response.text();
            })
            .then(text => {
                console.log(\'Response text:\', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // Modal\'ı kapat
                        const modal = bootstrap.Modal.getInstance(document.getElementById(\'uploadAdditionalFileModal\'));
                        modal.hide();
                        
                        // Sayfayı yenile
                        location.reload();
                    } else {
                        alert(\'Hata: \' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                } catch(e) {
                    console.error(\'JSON parse error:\', e);
                    alert(\'Sunucudan geçersiz yanıt alındı.\');
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
');
    echo "<p style='color:green'>✓ User template dosyası oluşturuldu</p>";
}
?>