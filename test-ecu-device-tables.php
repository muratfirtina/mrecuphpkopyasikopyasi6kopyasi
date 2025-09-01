<?php
/**
 * Mr ECU - ECU ve Device Tablolarını Test Etme
 * Test sayfası - kurulumdan sonra tabloları kontrol et
 */

require_once 'config/config.php';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECU ve Device Tabloları Test - Mr ECU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-database"></i> ECU ve Device Tabloları Test</h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Kurulum Durumu Kontrolü -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="bi bi-microchip text-primary"></i> ECU Tablosu</h5>
                                <?php
                                try {
                                    $ecuCheck = $pdo->query("SHOW TABLES LIKE 'ecus'");
                                    if ($ecuCheck->rowCount() > 0) {
                                        echo '<div class="alert alert-success"><i class="bi bi-check"></i> ECU tablosu mevcut</div>';
                                        
                                        // Kayıt sayısını kontrol et
                                        $ecuCount = $pdo->query("SELECT COUNT(*) FROM ecus")->fetchColumn();
                                        echo "<p><strong>Toplam ECU sayısı:</strong> {$ecuCount}</p>";
                                        
                                        // İlk 5 kaydı göster
                                        $ecuSample = $pdo->query("SELECT * FROM ecus ORDER BY name ASC LIMIT 5")->fetchAll();
                                        if (!empty($ecuSample)) {
                                            echo '<h6>Örnek ECU\'lar:</h6>';
                                            echo '<ul class="list-group">';
                                            foreach ($ecuSample as $ecu) {
                                                echo '<li class="list-group-item">' . htmlspecialchars($ecu['name']) . ' <small class="text-muted">(' . $ecu['id'] . ')</small></li>';
                                            }
                                            echo '</ul>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> ECU tablosu bulunamadı</div>';
                                    }
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="bi bi-times"></i> ECU tablosu kontrolü hatası: ' . $e->getMessage() . '</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><i class="bi bi-tools text-success"></i> Device Tablosu</h5>
                                <?php
                                try {
                                    $deviceCheck = $pdo->query("SHOW TABLES LIKE 'devices'");
                                    if ($deviceCheck->rowCount() > 0) {
                                        echo '<div class="alert alert-success"><i class="bi bi-check"></i> Device tablosu mevcut</div>';
                                        
                                        // Kayıt sayısını kontrol et
                                        $deviceCount = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
                                        echo "<p><strong>Toplam device sayısı:</strong> {$deviceCount}</p>";
                                        
                                        // İlk 5 kaydı göster
                                        $deviceSample = $pdo->query("SELECT * FROM devices ORDER BY name ASC LIMIT 5")->fetchAll();
                                        if (!empty($deviceSample)) {
                                            echo '<h6>Örnek Device\'lar:</h6>';
                                            echo '<ul class="list-group">';
                                            foreach ($deviceSample as $device) {
                                                echo '<li class="list-group-item">' . htmlspecialchars($device['name']) . ' <small class="text-muted">(' . $device['id'] . ')</small></li>';
                                            }
                                            echo '</ul>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Device tablosu bulunamadı</div>';
                                    }
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="bi bi-times"></i> Device tablosu kontrolü hatası: ' . $e->getMessage() . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Kurulum Linkleri -->
                        <div class="row">
                            <div class="col-12">
                                <h5><i class="bi bi-gear-wide-connected"></i> Kurulum İşlemleri</h5>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="install-ecu-device-tables.php" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Tabloları Kur
                                    </a>
                                    <a href="create_ecus_table.sql" class="btn btn-outline-primary" target="_blank">
                                        <i class="bi bi-file-code"></i> ECU SQL Dosyası
                                    </a>
                                    <a href="create_devices_table.sql" class="btn btn-outline-success" target="_blank">
                                        <i class="bi bi-file-code"></i> Device SQL Dosyası
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admin Panel Linki -->
                        <?php if (isLoggedIn() && isAdmin()): ?>
                        <div class="mt-4">
                            <h5><i class="bi bi-user-shield"></i> Admin İşlemleri</h5>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="admin/ecus.php" class="btn btn-info">
                                    <i class="bi bi-microchip"></i> ECU Yönetimi
                                </a>
                                <a href="admin/devices.php" class="btn btn-success">
                                    <i class="bi bi-tools"></i> Device Yönetimi
                                </a>
                                <a href="admin/" class="btn btn-dark">
                                    <i class="bi bi-tachometer-alt"></i> Admin Panel
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- API Test Linki (sadece admin için) -->
                        <?php if (isLoggedIn() && isAdmin()): ?>
                        <div class="mt-4">
                            <h5><i class="bi bi-code"></i> API Testleri</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ECU API Testleri:</strong></p>
                                    <ul class="list-unstyled">
                                        <li><a href="admin/ajax/ecu-api.php?action=stats" target="_blank">ECU İstatistikleri</a></li>
                                        <li><a href="admin/ajax/ecu-api.php?action=list&per_page=5" target="_blank">ECU Listesi (5 adet)</a></li>
                                        <li><a href="admin/ajax/ecu-api.php?action=search&term=EDC" target="_blank">ECU Arama (EDC)</a></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Device API Testleri:</strong></p>
                                    <ul class="list-unstyled">
                                        <li><a href="admin/ajax/device-api.php?action=stats" target="_blank">Device İstatistikleri</a></li>
                                        <li><a href="admin/ajax/device-api.php?action=list&per_page=5" target="_blank">Device Listesi (5 adet)</a></li>
                                        <li><a href="admin/ajax/device-api.php?action=search&term=Auto" target="_blank">Device Arama (Auto)</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Tabloların yapısını göster -->
                        <div class="mt-4">
                            <h5><i class="bi bi-table"></i> Tablo Yapıları</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php
                                    try {
                                        $ecuStructure = $pdo->query("DESCRIBE ecus")->fetchAll();
                                        if (!empty($ecuStructure)) {
                                            echo '<h6>ECU Tablosu Yapısı:</h6>';
                                            echo '<div class="table-responsive">';
                                            echo '<table class="table table-sm table-bordered">';
                                            echo '<thead><tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
                                            echo '<tbody>';
                                            foreach ($ecuStructure as $column) {
                                                echo '<tr>';
                                                echo '<td>' . $column['Field'] . '</td>';
                                                echo '<td>' . $column['Type'] . '</td>';
                                                echo '<td>' . $column['Null'] . '</td>';
                                                echo '<td>' . $column['Key'] . '</td>';
                                                echo '<td>' . $column['Default'] . '</td>';
                                                echo '</tr>';
                                            }
                                            echo '</tbody></table></div>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<p class="text-muted">ECU tablo yapısı alınamadı.</p>';
                                    }
                                    ?>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    try {
                                        $deviceStructure = $pdo->query("DESCRIBE devices")->fetchAll();
                                        if (!empty($deviceStructure)) {
                                            echo '<h6>Device Tablosu Yapısı:</h6>';
                                            echo '<div class="table-responsive">';
                                            echo '<table class="table table-sm table-bordered">';
                                            echo '<thead><tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
                                            echo '<tbody>';
                                            foreach ($deviceStructure as $column) {
                                                echo '<tr>';
                                                echo '<td>' . $column['Field'] . '</td>';
                                                echo '<td>' . $column['Type'] . '</td>';
                                                echo '<td>' . $column['Null'] . '</td>';
                                                echo '<td>' . $column['Key'] . '</td>';
                                                echo '<td>' . $column['Default'] . '</td>';
                                                echo '</tr>';
                                            }
                                            echo '</tbody></table></div>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<p class="text-muted">Device tablo yapısı alınamadı.</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Test Sayfası - <?= date('d.m.Y H:i:s') ?></small>
                            <a href="index.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-home"></i> Ana Sayfa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
