<?php
/**
 * Mr ECU - API Test Sayfası
 * API endpoint'lerini hızlı test etmek için
 */

require_once 'config/config.php';

// Test edilen API'lar
$apis = [
    'ECU Stats' => 'admin/ajax/ecu-api.php?action=stats',
    'ECU List (5)' => 'admin/ajax/ecu-api.php?action=list&per_page=5',
    'Device Stats' => 'admin/ajax/device-api.php?action=stats',
    'Device List (5)' => 'admin/ajax/device-api.php?action=list&per_page=5',
];

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test - Mr ECU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="bi bi-code"></i> API Test Sayfası</h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Login Durumu Kontrolü -->
                        <?php if (!isLoggedIn()): ?>
                        <div class="alert alert-warning mb-4">
                            <h5><i class="bi bi-exclamation-triangle"></i> Login Gerekli</h5>
                            <p>API'ları test edebilmek için admin olarak giriş yapmanız gerekiyor.</p>
                            <a href="login.php" class="btn btn-primary">Giriş Yap</a>
                        </div>
                        <?php elseif (!isAdmin()): ?>
                        <div class="alert alert-danger mb-4">
                            <h5><i class="bi bi-ban"></i> Admin Yetkisi Gerekli</h5>
                            <p>API'ları test edebilmek için admin yetkisine sahip olmanız gerekiyor.</p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success mb-4">
                            <h5><i class="bi bi-check-circle"></i> Yetki Onaylandı</h5>
                            <p>Admin olarak giriş yaptınız. API testleri başlatılıyor...</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Database Durumu -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>ECU Tablosu</h5>
                                <?php
                                try {
                                    $ecuCount = $pdo->query("SELECT COUNT(*) FROM ecus")->fetchColumn();
                                    echo "<div class='alert alert-success'>✅ {$ecuCount} ECU mevcut</div>";
                                } catch (Exception $e) {
                                    echo "<div class='alert alert-danger'>❌ ECU tablosu hatası: " . $e->getMessage() . "</div>";
                                }
                                ?>
                            </div>
                            <div class="col-md-6">
                                <h5>Device Tablosu</h5>
                                <?php
                                try {
                                    $deviceCount = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
                                    echo "<div class='alert alert-success'>✅ {$deviceCount} Device mevcut</div>";
                                } catch (Exception $e) {
                                    echo "<div class='alert alert-danger'>❌ Device tablosu hatası: " . $e->getMessage() . "</div>";
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- API Testleri -->
                        <h5>API Test Sonuçları</h5>
                        <div class="row">
                            <?php foreach ($apis as $name => $url): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h6><?= $name ?></h6>
                                        <small class="text-muted"><?= $url ?></small>
                                    </div>
                                    <div class="card-body">
                                        <div id="result-<?= md5($name) ?>">
                                            <div class="text-center">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                Yükleniyor...
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <a href="<?= $url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-external-link-alt"></i> Aç
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Hızlı Linkler -->
                        <div class="mt-4">
                            <h5>Hızlı Linkler</h5>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="admin/ecus.php" class="btn btn-info">ECU Yönetimi</a>
                                <a href="admin/devices.php" class="btn btn-success">Device Yönetimi</a>
                                <a href="test-ecu-device-tables.php" class="btn btn-warning">Tablo Testi</a>
                                <a href="install-ecu-device-tables.php" class="btn btn-danger">Kurulum</a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Sadece admin kullanıcılar için API testi yap
    <?php if (isLoggedIn() && isAdmin()): ?>
    // API'ları test et
    const apis = <?= json_encode($apis) ?>;
    
    Object.entries(apis).forEach(([name, url]) => {
        const resultDiv = document.getElementById('result-' + md5(name));
        
        fetch(url, {
            credentials: 'same-origin',  // Session cookie'leri için
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(data => {
                try {
                    const json = JSON.parse(data);
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ Başarılı!</strong>
                            <pre class="mt-2 mb-0">${JSON.stringify(json, null, 2)}</pre>
                        </div>
                    `;
                } catch (e) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <strong>⚠ JSON Parse Hatası!</strong>
                            <details class="mt-2">
                                <summary>Raw Response (${data.length} karakter)</summary>
                                <pre class="mt-2 mb-0 small">${data.substring(0, 1000)}${data.length > 1000 ? '...' : ''}</pre>
                            </details>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ Hata!</strong>
                        <p class="mb-1"><strong>Tip:</strong> ${error.constructor.name}</p>
                        <p class="mb-0"><strong>Mesaj:</strong> ${error.message}</p>
                        <small class="text-muted mt-2 d-block">Bu hata session veya yetki sorunlarından kaynaklanabilir.</small>
                    </div>
                `;
            });
    });
    <?php else: ?>
    // Login olmayan kullanıcılar için API test kartlarını gizle
    document.querySelectorAll('[id^="result-"]').forEach(div => {
        div.innerHTML = '<div class="alert alert-secondary">Giriş gerekli</div>';
    });
    <?php endif; ?>
    
    function md5(str) {
        // Basit hash fonksiyonu (MD5 değil ama yeterli)
        let hash = 0;
        if (str.length === 0) return hash.toString();
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString();
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
